"""
Visueco ML Service — Self-Hosted Waste Classification
=====================================================
FastAPI microservice yang mengklasifikasikan gambar sampah menggunakan
MobileNetV2 (pre-trained ImageNet, CPU-only). Mengembalikan JSON yang
kontraknya identik dengan AiPredictorService::mapResponse() di Laravel.

Endpoint:
  GET  /health   -> health check
  POST /predict  -> terima multipart 'image', kembalikan prediksi kategori
"""

import io

import numpy as np
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from PIL import Image
from tensorflow.keras.applications.mobilenet_v2 import (
    MobileNetV2,
    decode_predictions,
    preprocess_input,
)

from category_map import CATEGORIES, match_category

app = FastAPI(
    title="Visueco ML Service",
    description="Self-hosted waste image classifier (MobileNetV2)",
    version="1.0.0",
)

# Muat model sekali saat startup (bobot di-cache di image/volume).
model = MobileNetV2(weights="imagenet")


def _read_image(raw: bytes) -> np.ndarray:
    """Decode bytes -> tensor (1, 224, 224, 3) siap untuk MobileNetV2."""
    image = Image.open(io.BytesIO(raw)).convert("RGB").resize((224, 224))
    array = np.array(image, dtype=np.float32)
    array = np.expand_dims(array, axis=0)
    return preprocess_input(array)


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model": "MobileNetV2", "service": "visueco-ml"}


@app.post("/predict")
async def predict(image: UploadFile = File(...)) -> JSONResponse:
    raw = await image.read()

    try:
        tensor = _read_image(raw)
    except Exception:
        return JSONResponse(
            status_code=422,
            content={"error": "Gambar tidak dapat diproses."},
        )

    preds = model.predict(tensor, verbose=0)
    # top-5 prediksi: list of (synset_id, label, score)
    decoded = decode_predictions(preds, top=5)[0]

    detected_item = decoded[0][1].replace("_", " ")
    top_confidence = float(decoded[0][2])

    # Cari label pertama (urut confidence) yang cocok ke salah satu kategori.
    matched_id = None
    matched_cat = None
    matched_label = None
    matched_score = top_confidence

    for _synset, label, score in decoded:
        cat_id, cat = match_category(label)
        if cat_id is not None:
            matched_id = cat_id
            matched_cat = cat
            matched_label = label.replace("_", " ")
            matched_score = float(score)
            break

    if matched_id is None:
        # Tidak ada yang cocok -> biarkan gerbang anti-fraud Laravel menolak.
        return JSONResponse(
            content={
                "detected_item": detected_item,
                "category_id": 0,
                "category_name": "Tidak Dikenali",
                "type_detail": decoded[0][1],
                "confidence_score": round(top_confidence, 2),
                "is_recyclable": False,
                "instructions": [],
            }
        )

    return JSONResponse(
        content={
            "detected_item": matched_label,
            "category_id": matched_id,
            "category_name": matched_cat["name"],
            "type_detail": matched_label,
            "confidence_score": round(matched_score, 2),
            "is_recyclable": matched_cat["is_recyclable"],
            "instructions": matched_cat["instructions"],
        }
    )
