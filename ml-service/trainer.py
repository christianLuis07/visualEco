"""
trainer.py — Fine-tune head classifier di atas MobileNetV2 (feature extractor beku).

Strategi (ringan untuk CPU):
  1. MobileNetV2 (tanpa softmax akhir) jadi feature extractor 1280-dim — BEKU.
  2. Tiap sampel berlabel disimpan sebagai file gambar di dataset/{category_id}/.
  3. Saat train: ekstrak embedding semua gambar (+ augmentasi ringan), lalu latih
     head Dense kecil (1280 -> 128 -> 5). Cepat di CPU.
  4. Bobot head + metadata versi disimpan ke MODEL_STORE (named volume).

Sumber data latih:
  - /learn  : 1 gambar dari konfirmasi warga (real-time).
  - /seed   : batch foto dari folder host (seed_dataset/<n>_<nama>/).

Kategori tetap (sinkron dengan tabel waste_categories di MySQL):
  1=Plastik  2=Kertas  3=Logam  4=Kaca  5=Organik
"""

import hashlib
import io
import json
import os
import uuid

import numpy as np
from PIL import Image

MODEL_STORE = os.environ.get("MODEL_STORE", "/app/model_store")
SEED_DIR = os.environ.get("SEED_DIR", "/app/seed_dataset")
DATASET_DIR = os.path.join(MODEL_STORE, "dataset")
HEAD_PATH = os.path.join(MODEL_STORE, "head.keras")
META_PATH = os.path.join(MODEL_STORE, "meta.json")

CATEGORY_IDS = [1, 2, 3, 4, 5]
NUM_CLASSES = 5

# Pemetaan nama folder seed -> category_id.
SEED_FOLDERS = {
    "1_plastik": 1,
    "2_kertas": 2,
    "3_logam": 3,
    "4_kaca": 4,
    "5_organik": 5,
}

VALID_EXT = (".jpg", ".jpeg", ".png")


def _ensure_dirs() -> None:
    for cid in CATEGORY_IDS:
        os.makedirs(os.path.join(DATASET_DIR, str(cid)), exist_ok=True)


def _save_image(img: Image.Image, category_id: int, stem: str) -> str:
    """Simpan gambar JPEG ke dataset/<id>/<stem>.jpg. Return path."""
    path = os.path.join(DATASET_DIR, str(category_id), f"{stem}.jpg")
    img.convert("RGB").save(path, "JPEG", quality=90)
    return path


def add_sample(image_bytes: bytes, category_id: int) -> str:
    """Simpan satu gambar berlabel ke dataset (dari konfirmasi warga)."""
    if category_id not in CATEGORY_IDS:
        raise ValueError(f"category_id tidak valid: {category_id}")
    _ensure_dirs()
    img = Image.open(io.BytesIO(image_bytes))
    return _save_image(img, category_id, uuid.uuid4().hex)


def ingest_seed() -> dict:
    """
    Salin semua foto dari folder seed host ke dataset internal.
    Idempotent: nama file = hash isi, jadi foto sama tak digandakan.
    Return ringkasan: {added, skipped, per_category, counts}.
    """
    _ensure_dirs()
    added = 0
    skipped = 0
    per_category = {cid: 0 for cid in CATEGORY_IDS}

    if not os.path.isdir(SEED_DIR):
        return {"added": 0, "skipped": 0, "per_category": per_category,
                "counts": count_samples(), "note": "folder seed tidak ditemukan"}

    for folder, cid in SEED_FOLDERS.items():
        src = os.path.join(SEED_DIR, folder)
        if not os.path.isdir(src):
            continue
        for fname in os.listdir(src):
            if not fname.lower().endswith(VALID_EXT):
                continue
            fpath = os.path.join(src, fname)
            try:
                with open(fpath, "rb") as f:
                    data = f.read()
                digest = hashlib.sha1(data).hexdigest()[:16]
                stem = f"seed_{digest}"
                dest = os.path.join(DATASET_DIR, str(cid), f"{stem}.jpg")
                if os.path.exists(dest):
                    skipped += 1
                    continue
                img = Image.open(io.BytesIO(data))
                _save_image(img, cid, stem)
                added += 1
                per_category[cid] += 1
            except Exception:
                skipped += 1
                continue

    return {
        "added": added,
        "skipped": skipped,
        "per_category": {str(k): v for k, v in per_category.items()},
        "counts": count_samples(),
    }


def count_samples() -> dict:
    """Jumlah sampel per kategori."""
    _ensure_dirs()
    counts = {}
    for cid in CATEGORY_IDS:
        d = os.path.join(DATASET_DIR, str(cid))
        counts[cid] = len([f for f in os.listdir(d) if f.endswith(".jpg")])
    return counts


def load_meta() -> dict:
    if os.path.exists(META_PATH):
        with open(META_PATH) as f:
            return json.load(f)
    return {}


def load_head():
    """Muat head terlatih jika ada, else None."""
    if os.path.exists(HEAD_PATH):
        import tensorflow as tf

        return tf.keras.models.load_model(HEAD_PATH)
    return None


def _augment(arr: np.ndarray) -> list:
    """
    Augmentasi ringan (numpy, tanpa TF) untuk memperbanyak variasi:
    asli + flip horizontal. Murah di CPU, menggandakan data efektif.
    """
    return [arr, arr[:, ::-1, :]]


def _feature_set(feature_extractor, preprocess_fn):
    """
    Ekstrak embedding semua gambar dataset (dengan augmentasi).
    Return (X, y) numpy + counts.
    """
    features, labels = [], []
    for cid in CATEGORY_IDS:
        d = os.path.join(DATASET_DIR, str(cid))
        for fname in os.listdir(d):
            if not fname.endswith(".jpg"):
                continue
            img = Image.open(os.path.join(d, fname)).convert("RGB").resize((224, 224))
            base = np.array(img, dtype=np.float32)
            for aug in _augment(base):
                tensor = preprocess_fn(np.expand_dims(aug, axis=0))
                feat = feature_extractor.predict(tensor, verbose=0)[0]
                features.append(feat)
                labels.append(cid - 1)
    return np.array(features, dtype=np.float32), np.array(labels, dtype=np.int64)


def train(feature_extractor, preprocess_fn, epochs: int = 60) -> dict:
    """
    Latih ulang head dari seluruh dataset. Return metadata versi baru.
    Melempar ValueError jika data belum cukup (perlu >=2 kategori berisi).
    """
    import tensorflow as tf

    _ensure_dirs()
    counts = count_samples()
    classes_present = [cid for cid, c in counts.items() if c > 0]
    total_images = sum(counts.values())

    if len(classes_present) < 2:
        raise ValueError(
            "Butuh sampel dari minimal 2 kategori berbeda untuk melatih model."
        )

    x, y = _feature_set(feature_extractor, preprocess_fn)

    # val 'reliable' hanya jika tiap kelas punya cukup contoh
    # (>=5 gambar asli per kelas → >=10 setelah augmentasi).
    min_per_class = min(counts[c] for c in classes_present)
    reliable = (len(classes_present) == NUM_CLASSES) and (min_per_class >= 5)

    # ── STRATIFIED SPLIT MANUAL ──
    # Keras validation_split mengambil 20% TERAKHIR tanpa acak; karena data
    # tersusun per-kelas, itu membuat val set timpang (1 kelas saja).
    # Di sini kita acak + ambil 20% dari TIAP kelas secara proporsional.
    rng = np.random.default_rng(42)
    val_idx = []
    train_idx = []
    for cls in range(NUM_CLASSES):
        idx = np.where(y == cls)[0]
        if len(idx) == 0:
            continue
        rng.shuffle(idx)
        n_val = max(1, int(round(len(idx) * 0.2))) if reliable else 0
        val_idx.extend(idx[:n_val].tolist())
        train_idx.extend(idx[n_val:].tolist())

    rng.shuffle(train_idx)
    rng.shuffle(val_idx)
    x_train, y_train = x[train_idx], y[train_idx]
    has_val = reliable and len(val_idx) > 0
    validation_data = (x[val_idx], y[val_idx]) if has_val else None

    # class_weight dihitung dari distribusi data TRAIN.
    class_weight = {}
    unique, counts_arr = np.unique(y_train, return_counts=True)
    train_total = len(y_train)
    count_map = dict(zip(unique.tolist(), counts_arr.tolist()))
    for cls in range(NUM_CLASSES):
        n = count_map.get(cls, 0)
        class_weight[cls] = (train_total / (NUM_CLASSES * n)) if n > 0 else 0.0

    head = tf.keras.Sequential([
        tf.keras.layers.Input(shape=(x.shape[1],)),
        tf.keras.layers.Dropout(0.4),
        tf.keras.layers.Dense(128, activation="relu"),
        tf.keras.layers.Dropout(0.3),
        tf.keras.layers.Dense(NUM_CLASSES, activation="softmax"),
    ])
    head.compile(
        optimizer="adam",
        loss="sparse_categorical_crossentropy",
        metrics=["accuracy"],
    )

    callbacks = []
    if has_val:
        callbacks.append(tf.keras.callbacks.EarlyStopping(
            monitor="val_loss", patience=10, restore_best_weights=True
        ))

    history = head.fit(
        x_train, y_train,
        epochs=epochs,
        batch_size=16,
        verbose=0,
        validation_data=validation_data,
        shuffle=True,
        class_weight=class_weight,
        callbacks=callbacks,
    )

    train_acc = float(history.history["accuracy"][-1])
    val_acc = float(history.history["val_accuracy"][-1]) if has_val else None

    head.save(HEAD_PATH)

    meta = {
        "version": int(load_meta().get("version", 0)) + 1,
        "accuracy": round(val_acc if val_acc is not None else train_acc, 4),
        "train_accuracy": round(train_acc, 4),
        "val_accuracy": round(val_acc, 4) if val_acc is not None else None,
        "reliable": reliable and has_val,
        "sample_count": int(total_images),
        "per_category": {str(k): v for k, v in counts.items()},
        "classes": CATEGORY_IDS,
    }
    with open(META_PATH, "w") as f:
        json.dump(meta, f)

    return meta
