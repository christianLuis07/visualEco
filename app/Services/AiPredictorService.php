<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class AiPredictorService
{
    private readonly string $endpoint;
    private readonly int $timeout;
    private readonly int $trainTimeout;

    public function __construct()
    {
        $this->endpoint = config('services.ai_ecosort.endpoint');
        $this->timeout = config('services.ai_ecosort.timeout', 30);
        $this->trainTimeout = config('services.ai_ecosort.train_timeout', 600);
    }

    /**
     * Base URL ML service (tanpa path /predict), untuk endpoint lain
     * seperti /learn, /train, /model/info.
     */
    private function baseUrl(): string
    {
        // Buang segmen terakhir (/predict) dari endpoint.
        return preg_replace('#/[^/]+$#', '', $this->endpoint);
    }

    /**
     * Kirim gambar sampah ke API AI dan kembalikan hasil prediksi
     * dalam format array yang sudah dinormalisasi.
     *
     * @throws AiServiceException
     */
    public function analyzeTrashImage(UploadedFile $imageFile): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach(
                    'image',
                    $imageFile->get(),
                    $imageFile->getClientOriginalName()
                )
                ->post($this->endpoint);
        } catch (ConnectionException $e) {
            throw new AiServiceException(
                'Koneksi ke server AI terputus atau timeout.',
                503,
                $e
            );
        }

        if ($response->failed()) {
            throw new AiServiceException(
                'Server AI mengembalikan respons tidak valid.',
                $response->status()
            );
        }

        return $this->mapResponse($response->json());
    }

    /**
     * Kirim 1 gambar berlabel ke ML service untuk disimpan sebagai data latih.
     *
     * @throws AiServiceException
     */
    public function learnSample(string $imageContents, string $filename, int $categoryId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach('image', $imageContents, $filename)
                ->post($this->baseUrl() . '/learn', [
                    'category_id' => $categoryId,
                ]);
        } catch (ConnectionException $e) {
            throw new AiServiceException('Koneksi ke server AI terputus.', 503, $e);
        }

        if ($response->failed()) {
            throw new AiServiceException(
                'Gagal menyimpan data latih ke server AI.',
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Impor foto dari folder seed host ke dataset internal ML.
     *
     * @throws AiServiceException
     */
    public function seedDataset(): array
    {
        try {
            $response = Http::timeout($this->trainTimeout)
                ->post($this->baseUrl() . '/seed');
        } catch (ConnectionException $e) {
            throw new AiServiceException('Koneksi ke server AI terputus.', 503, $e);
        }

        if ($response->failed()) {
            throw new AiServiceException(
                'Server AI gagal mengimpor folder seed.',
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Picu pelatihan ulang model dari seluruh dataset.
     *
     * @throws AiServiceException
     */
    public function trainModel(): array
    {
        try {
            $response = Http::timeout($this->trainTimeout)
                ->post($this->baseUrl() . '/train');
        } catch (ConnectionException $e) {
            throw new AiServiceException('Koneksi ke server AI terputus.', 503, $e);
        }

        if ($response->status() === 422) {
            // Data belum cukup untuk melatih.
            throw new AiServiceException(
                $response->json('message') ?? 'Data latih belum mencukupi.',
                422
            );
        }

        if ($response->failed()) {
            throw new AiServiceException(
                'Server AI gagal melatih model.',
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Ambil info versi & metrik model aktif.
     *
     * @throws AiServiceException
     */
    public function modelInfo(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl() . '/model/info');
        } catch (ConnectionException $e) {
            throw new AiServiceException('Koneksi ke server AI terputus.', 503, $e);
        }

        if ($response->failed()) {
            throw new AiServiceException(
                'Gagal mengambil info model dari server AI.',
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Petakan JSON mentah dari API AI ke struktur array standar internal.
     */
    private function mapResponse(array $data): array
    {
        return [
            'detected_item'   => (string) ($data['detected_item'] ?? ''),
            'category_id'     => (int)    ($data['category_id'] ?? 0),
            'category_name'   => (string) ($data['category_name'] ?? ''),
            'type_detail'     => (string) ($data['type_detail'] ?? ''),
            'confidence_score' => (float) ($data['confidence_score'] ?? 0.0),
            'is_recyclable'   => (bool)   ($data['is_recyclable'] ?? false),
            'instructions'    => (array)  ($data['instructions'] ?? []),
        ];
    }
}
