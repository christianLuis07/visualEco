"""
Visueco ML Service — Self-Hosted Waste Classification (Learning Edition)
========================================================================
FastAPI microservice yang mengklasifikasikan gambar sampah menggunakan
MobileNetV2 (CPU-only) DAN dapat BELAJAR dari konfirmasi warga.

Dua mode prediksi:
  A. HEAD TERLATIH (jika sudah ada hasil training) — classifier 5 kategori
     yang dilatih dari foto sampah asli warga. Akurat untuk data lokal.
  B. FALLBACK keyword-map ImageNet + multi-crop TTA — dipakai saat head
     belum dilatih (cold start). Perilaku seperti versi sebelumnya.

Endpoint:
  GET  /health      -> health check
  POST /predict     -> klasifikasi gambar (head terlatih > fallback)
  POST /learn       -> simpan 1 gambar berlabel ke dataset (dari konfirmasi user)
  POST /train       -> latih ulang head dari seluruh dataset
  GET  /model/info  -> versi & metrik model aktif
"""

import io

import numpy as np
from fastapi import FastAPI, File, Form, UploadFile
from fastapi.responses import JSONResponse
from PIL import Image
from tensorflow.keras.applications.mobilenet_v2 import (
    MobileNetV2,
    decode_predictions,
    preprocess_input,
)
from tensorflow.keras.models import Model

import trainer
from category_map import CATEGORIES, match_category

app = FastAPI(
    title="Visueco ML Service",
    description="Self-hosted waste classifier that learns from user confirmations",
    version="2.0.0",
)

# Model lengkap (dengan softmax ImageNet) untuk fallback keyword-map.
full_model = MobileNetV2(weights="imagenet")

# Feature extractor (output 1280-dim global-pooled) untuk head terlatih.
feature_extractor = Model(
    inputs=full_model.input,
    outputs=full_model.get_layer("global_average_pooling2d").output,
)

# Head terlatih (di-cache di memori; di-reload setelah /train).
trained_head = trainer.load_head()

TOP_K = 15


def _to_tensor(image: Image.Image) -> np.ndarray:
    image = image.convert("RGB").resize((224, 224))
    arr = np.array(image, dtype=np.float32)
    return preprocess_input(np.expand_dims(arr, axis=0))


def _generate_crops(image: Image.Image) -> list:
    """Multi-crop TTA: gambar penuh + beberapa center-crop."""
    w, h = image.size
    crops = [image]
    dx, dy = int(w * 0.15), int(h * 0.15)
    crops.append(image.crop((dx, dy, w - dx, h - dy)))
    dx, dy = int(w * 0.25), int(h * 0.25)
    crops.append(image.crop((dx, dy, w - dx, h - dy)))
    dx, dy = int(w * 0.25), int(h * 0.05)
    crops.append(image.crop((dx, dy, w - dx, h - dy)))
    return crops


def _build_response(cat_id: int, label: str, confidence: float) -> dict:
    cat = CATEGORIES[cat_id]
    return {
        "detected_item": label.replace("_", " "),
        "category_id": cat_id,
        "category_name": cat["name"],
        "type_detail": label,
        "confidence_score": round(float(confidence), 2),
        "is_recyclable": cat["is_recyclable"],
        "instructions": cat["instructions"],
    }


def _predict_with_head(image: Image.Image) -> dict:
    """Mode A: prediksi pakai head terlatih (rata-rata lintas crop)."""
    crops = _generate_crops(image)
    batch = np.vstack([_to_tensor(c) for c in crops])
    feats = feature_extractor.predict(batch, verbose=0)
    probs = trained_head.predict(feats, verbose=0)
    mean_probs = probs.mean(axis=0)  # rata-rata lintas crop
    idx = int(np.argmax(mean_probs))
    cat_id = idx + 1
    confidence = float(mean_probs[idx])
    label = CATEGORIES[cat_id]["name"].lower()
    return _build_response(cat_id, label, confidence)


def _predict_with_fallback(image: Image.Image) -> dict:
    """Mode B: keyword-map ImageNet + agregasi skor lintas crop."""
    crops = _generate_crops(image)
    batch = np.vstack([_to_tensor(c) for c in crops])
    preds = full_model.predict(batch, verbose=0)
    decoded_batch = decode_predictions(preds, top=TOP_K)

    category_scores: dict = {}
    best_label = ""
    best_label_score = 0.0
    overall_top_label = ""
    overall_top_score = 0.0

    for decoded in decoded_batch:
        for _synset, label, score in decoded:
            score = float(score)
            if score > overall_top_score:
                overall_top_score = score
                overall_top_label = label
            cat_id, _cat = match_category(label)
            if cat_id is None:
                continue
            category_scores[cat_id] = category_scores.get(cat_id, 0.0) + score
            if score > best_label_score:
                best_label_score = score
                best_label = label

    if not category_scores:
        label = overall_top_label or "tidak diketahui"
        return {
            "detected_item": label.replace("_", " "),
            "category_id": 0,
            "category_name": "Tidak Dikenali",
            "type_detail": label,
            "confidence_score": round(float(overall_top_score), 2),
            "is_recyclable": False,
            "instructions": [],
        }

    best_cat_id = max(category_scores, key=category_scores.get)
    confidence = min(0.99, category_scores[best_cat_id])
    resp = _build_response(best_cat_id, best_label or "", confidence)
    return resp


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model": "MobileNetV2", "service": "visueco-ml"}


@app.get("/model/info")
def model_info() -> dict:
    meta = trainer.load_meta()
    return {
        "has_trained_head": trained_head is not None,
        "version": int(meta.get("version", 0)),
        "accuracy": meta.get("accuracy"),
        "train_accuracy": meta.get("train_accuracy"),
        "val_accuracy": meta.get("val_accuracy"),
        "reliable": meta.get("reliable", False),
        "sample_count": int(meta.get("sample_count", 0)),
        "per_category": meta.get("per_category", {}),
        "dataset_counts": trainer.count_samples(),
    }


@app.post("/seed")
def seed_dataset() -> JSONResponse:
    """Salin foto dari folder seed host ke dataset internal ML."""
    try:
        result = trainer.ingest_seed()
    except Exception:
        return JSONResponse(
            status_code=500,
            content={"success": False, "message": "Gagal mengimpor folder seed."},
        )
    return JSONResponse(content={"success": True, **result})


@app.post("/predict")
async def predict(image: UploadFile = File(...)) -> JSONResponse:
    raw = await image.read()
    try:
        pil = Image.open(io.BytesIO(raw)).convert("RGB")
    except Exception:
        return JSONResponse(
            status_code=422, content={"error": "Gambar tidak dapat diproses."}
        )

    if trained_head is not None:
        result = _predict_with_head(pil)
    else:
        result = _predict_with_fallback(pil)

    return JSONResponse(content=result)


@app.post("/learn")
async def learn(
    image: UploadFile = File(...),
    category_id: int = Form(...),
) -> JSONResponse:
    """Simpan 1 gambar berlabel ke dataset (hasil konfirmasi/koreksi user)."""
    raw = await image.read()
    try:
        path = trainer.add_sample(raw, category_id)
    except ValueError as e:
        return JSONResponse(status_code=422, content={"error": str(e)})
    except Exception:
        return JSONResponse(
            status_code=422, content={"error": "Gambar tidak dapat disimpan."}
        )

    return JSONResponse(
        content={
            "success": True,
            "stored_path": path,
            "dataset_counts": trainer.count_samples(),
        }
    )


@app.post("/train")
def train_model() -> JSONResponse:
    """Latih ulang head dari seluruh dataset, lalu reload ke memori."""
    global trained_head
    try:
        meta = trainer.train(feature_extractor, preprocess_input)
    except ValueError as e:
        return JSONResponse(status_code=422, content={"success": False, "message": str(e)})

    # Reload head yang baru dilatih.
    trained_head = trainer.load_head()

    return JSONResponse(
        content={
            "success": True,
            "message": "Model berhasil dilatih ulang.",
            "version": meta["version"],
            "accuracy": meta["accuracy"],
            "sample_count": meta["sample_count"],
            "per_category": meta["per_category"],
        }
    )
