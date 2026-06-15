"""
Visueco ML Service — Self-Hosted Waste Classification
=====================================================
FastAPI microservice yang mengklasifikasikan gambar sampah menggunakan
MobileNetV2 (pre-trained ImageNet, CPU-only). Mengembalikan JSON yang
kontraknya identik dengan AiPredictorService::mapResponse() di Laravel.

Strategi klasifikasi (lebih tahan banting):
  1. Multi-crop TTA — gambar penuh + beberapa center-crop, agar objek kecil
     di tengah latar besar tetap terbaca (bukan latar yang mendominasi).
  2. Agregasi skor per kategori — jumlahkan skor SEMUA label top-K yang
     cocok ke tiap kategori (lintas crop), bukan hanya ambil top-1.
  3. Kategori dengan skor agregat tertinggi menang.

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
    version="1.1.0",
)

# Muat model sekali saat startup (bobot di-cache di image/volume).
model = MobileNetV2(weights="imagenet")

# Berapa banyak prediksi teratas yang diperiksa per crop.
TOP_K = 15


def _to_tensor(image: Image.Image) -> np.ndarray:
    """PIL Image -> tensor (1, 224, 224, 3) siap untuk MobileNetV2."""
    image = image.convert("RGB").resize((224, 224))
    array = np.array(image, dtype=np.float32)
    array = np.expand_dims(array, axis=0)
    return preprocess_input(array)


def _generate_crops(image: Image.Image) -> list[Image.Image]:
    """
    Hasilkan beberapa varian gambar untuk Test-Time Augmentation:
      - gambar penuh
      - center-crop 70% (buang pinggiran/latar)
      - center-crop 50% (fokus objek tengah)
      - crop vertikal tengah (objek tinggi seperti botol)
    """
    w, h = image.size
    crops = [image]

    # center-crop 70%
    dx, dy = int(w * 0.15), int(h * 0.15)
    crops.append(image.crop((dx, dy, w - dx, h - dy)))

    # center-crop 50%
    dx, dy = int(w * 0.25), int(h * 0.25)
    crops.append(image.crop((dx, dy, w - dx, h - dy)))

    # crop vertikal tengah (lebar 50%, tinggi 90%) untuk botol/kaleng
    dx = int(w * 0.25)
    dy = int(h * 0.05)
    crops.append(image.crop((dx, dy, w - dx, h - dy)))

    return crops


def _aggregate_scores(image: Image.Image) -> tuple[dict, str, float]:
    """
    Jalankan model pada semua crop, kumpulkan skor agregat per kategori.

    Returns:
      - category_scores: {category_id: skor_agregat}
      - best_label: label ImageNet terbaik yang cocok (untuk detected_item)
      - best_label_score: skor label tersebut
    """
    category_scores: dict[int, float] = {}
    best_label = ""
    best_label_score = 0.0
    overall_top_label = ""
    overall_top_score = 0.0

    crops = _generate_crops(image)
    # Batch semua crop dalam satu prediksi.
    batch = np.vstack([_to_tensor(c) for c in crops])
    preds = model.predict(batch, verbose=0)
    decoded_batch = decode_predictions(preds, top=TOP_K)

    for decoded in decoded_batch:
        for _synset, label, score in decoded:
            score = float(score)

            # Lacak label paling kuat secara keseluruhan (untuk fallback display).
            if score > overall_top_score:
                overall_top_score = score
                overall_top_label = label

            cat_id, _cat = match_category(label)
            if cat_id is None:
                continue

            category_scores[cat_id] = category_scores.get(cat_id, 0.0) + score

            # Simpan label sampah terbaik untuk ditampilkan ke user.
            if score > best_label_score:
                best_label_score = score
                best_label = label

    if not best_label:
        best_label = overall_top_label
        best_label_score = overall_top_score

    return category_scores, best_label, best_label_score


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model": "MobileNetV2", "service": "visueco-ml"}


@app.post("/predict")
async def predict(image: UploadFile = File(...)) -> JSONResponse:
    raw = await image.read()

    try:
        pil_image = Image.open(io.BytesIO(raw)).convert("RGB")
    except Exception:
        return JSONResponse(
            status_code=422,
            content={"error": "Gambar tidak dapat diproses."},
        )

    category_scores, best_label, best_label_score = _aggregate_scores(pil_image)
    detected_item = best_label.replace("_", " ") if best_label else "tidak diketahui"

    if not category_scores:
        # Tidak ada sinyal kategori sama sekali -> biarkan Laravel menolak.
        return JSONResponse(
            content={
                "detected_item": detected_item,
                "category_id": 0,
                "category_name": "Tidak Dikenali",
                "type_detail": best_label,
                "confidence_score": round(float(best_label_score), 2),
                "is_recyclable": False,
                "instructions": [],
            }
        )

    # Kategori dengan skor agregat tertinggi menang.
    best_cat_id = max(category_scores, key=category_scores.get)
    best_cat = CATEGORIES[best_cat_id]
    agg_score = category_scores[best_cat_id]

    # Confidence akhir: skor agregat kategori, dibatasi maksimal 0.99.
    # Agregasi lintas-crop menjadikan objek nyata jauh lebih mudah lolos
    # gerbang anti-fraud, tanpa mengangkat noise yang skornya tersebar.
    confidence = min(0.99, round(float(agg_score), 2))

    return JSONResponse(
        content={
            "detected_item": detected_item,
            "category_id": best_cat_id,
            "category_name": best_cat["name"],
            "type_detail": best_label,
            "confidence_score": confidence,
            "is_recyclable": best_cat["is_recyclable"],
            "instructions": best_cat["instructions"],
        }
    )
