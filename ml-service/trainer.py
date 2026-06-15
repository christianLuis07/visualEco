"""
trainer.py — Fine-tune head classifier di atas MobileNetV2 (feature extractor beku).

Strategi (ringan untuk CPU):
  1. MobileNetV2 (tanpa softmax akhir) jadi feature extractor 1280-dim — BEKU.
  2. Tiap sampel berlabel disimpan sebagai file gambar di dataset/{category_id}/.
  3. Saat train: ekstrak embedding semua gambar (precompute), lalu latih head
     Dense kecil (1280 -> 128 -> 5). Karena head kecil & fitur precomputed,
     training selesai dalam hitungan detik meski di CPU.
  4. Bobot head + metadata versi disimpan ke MODEL_STORE (named volume).

Kategori tetap (sinkron dengan tabel waste_categories di MySQL):
  1=Plastik  2=Kertas  3=Logam  4=Kaca  5=Organik
"""

import io
import json
import os
import uuid

import numpy as np
from PIL import Image

MODEL_STORE = os.environ.get("MODEL_STORE", "/app/model_store")
DATASET_DIR = os.path.join(MODEL_STORE, "dataset")
HEAD_PATH = os.path.join(MODEL_STORE, "head.keras")
META_PATH = os.path.join(MODEL_STORE, "meta.json")

CATEGORY_IDS = [1, 2, 3, 4, 5]
NUM_CLASSES = 5


def _ensure_dirs() -> None:
    for cid in CATEGORY_IDS:
        os.makedirs(os.path.join(DATASET_DIR, str(cid)), exist_ok=True)


def add_sample(image_bytes: bytes, category_id: int) -> str:
    """Simpan satu gambar berlabel ke dataset. Return path file."""
    if category_id not in CATEGORY_IDS:
        raise ValueError(f"category_id tidak valid: {category_id}")
    _ensure_dirs()
    img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    name = f"{uuid.uuid4().hex}.jpg"
    path = os.path.join(DATASET_DIR, str(category_id), name)
    img.save(path, "JPEG", quality=90)
    return path


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


def _preprocess(preprocess_fn, img: Image.Image) -> np.ndarray:
    img = img.convert("RGB").resize((224, 224))
    arr = np.array(img, dtype=np.float32)
    return preprocess_fn(np.expand_dims(arr, axis=0))


def train(feature_extractor, preprocess_fn, epochs: int = 40) -> dict:
    """
    Latih ulang head dari seluruh dataset. Return metadata versi baru.
    Melempar ValueError jika data belum cukup (perlu >=2 kategori berisi).
    """
    import tensorflow as tf

    _ensure_dirs()
    counts = count_samples()
    classes_present = [cid for cid, c in counts.items() if c > 0]
    total = sum(counts.values())

    if len(classes_present) < 2:
        raise ValueError(
            "Butuh sampel dari minimal 2 kategori berbeda untuk melatih model."
        )

    # Precompute embedding semua gambar.
    features = []
    labels = []
    for cid in CATEGORY_IDS:
        d = os.path.join(DATASET_DIR, str(cid))
        for fname in os.listdir(d):
            if not fname.endswith(".jpg"):
                continue
            img = Image.open(os.path.join(d, fname))
            tensor = _preprocess(preprocess_fn, img)
            feat = feature_extractor.predict(tensor, verbose=0)[0]
            features.append(feat)
            labels.append(cid - 1)  # index 0..4

    x = np.array(features, dtype=np.float32)
    y = np.array(labels, dtype=np.int64)

    head = tf.keras.Sequential(
        [
            tf.keras.layers.Input(shape=(x.shape[1],)),
            tf.keras.layers.Dropout(0.3),
            tf.keras.layers.Dense(128, activation="relu"),
            tf.keras.layers.Dropout(0.2),
            tf.keras.layers.Dense(NUM_CLASSES, activation="softmax"),
        ]
    )
    head.compile(
        optimizer="adam",
        loss="sparse_categorical_crossentropy",
        metrics=["accuracy"],
    )

    # Validasi hanya jika data cukup banyak agar split tidak kosong.
    val_split = 0.2 if total >= 15 else 0.0
    history = head.fit(
        x,
        y,
        epochs=epochs,
        batch_size=16,
        verbose=0,
        validation_split=val_split,
        shuffle=True,
    )

    if "val_accuracy" in history.history:
        accuracy = float(history.history["val_accuracy"][-1])
    else:
        accuracy = float(history.history["accuracy"][-1])

    head.save(HEAD_PATH)

    meta = {
        "version": int(load_meta().get("version", 0)) + 1,
        "accuracy": round(accuracy, 4),
        "sample_count": int(total),
        "per_category": {str(k): v for k, v in counts.items()},
        "classes": CATEGORY_IDS,
    }
    with open(META_PATH, "w") as f:
        json.dump(meta, f)

    return meta
