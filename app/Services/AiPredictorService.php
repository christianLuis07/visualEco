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

    public function __construct()
    {
        $this->endpoint = config('services.ai_ecosort.endpoint');
        $this->timeout = config('services.ai_ecosort.timeout', 10);
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
