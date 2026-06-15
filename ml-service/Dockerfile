# ============================================================
#  Visueco ML Service — Self-Hosted Waste Classifier
#  Base : python:3.11-slim | MobileNetV2 (TensorFlow CPU)
# ============================================================
FROM python:3.11-slim

LABEL maintainer="Visueco DevOps Team"
LABEL description="Self-hosted ML waste classification service (MobileNetV2)"

# Dependensi sistem minimal untuk Pillow & TensorFlow
RUN apt-get update && apt-get install -y --no-install-recommends \
        libgl1 \
        libglib2.0-0 \
        curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Install dependency Python lebih dulu (cache layer)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Salin kode aplikasi
COPY . .

# Pre-download bobot MobileNetV2 saat build agar container offline-ready.
RUN python -c "from tensorflow.keras.applications.mobilenet_v2 import MobileNetV2; MobileNetV2(weights='imagenet')"

EXPOSE 8001

# Health check internal
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8001/health || exit 1

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8001"]
