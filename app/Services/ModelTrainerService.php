<?php

namespace App\Services;

use App\Exceptions\AiServiceException;
use App\Models\ModelVersion;
use App\Models\TrainingSample;
use Illuminate\Support\Facades\DB;

class ModelTrainerService
{
    public function __construct(
        private readonly AiPredictorService $aiService,
    ) {}

    /**
     * Picu pelatihan ulang model di ML service, lalu catat versi baru
     * dan tandai sampel sebagai sudah dipakai.
     *
     * @throws AiServiceException
     */
    public function train(): ModelVersion
    {
        $result = $this->aiService->trainModel();

        return DB::transaction(function () use ($result): ModelVersion {
            // Nonaktifkan versi lama.
            ModelVersion::where('is_active', true)->update(['is_active' => false]);

            $version = ModelVersion::create([
                'version'      => (int) ($result['version'] ?? 1),
                'sample_count' => (int) ($result['sample_count'] ?? 0),
                'accuracy'     => (float) ($result['accuracy'] ?? 0),
                'is_active'    => true,
                'trained_at'   => now(),
            ]);

            // Tandai semua sampel yang belum dilatih sebagai sudah dipakai.
            TrainingSample::where('used_in_training', false)
                ->update(['used_in_training' => true]);

            return $version;
        });
    }

    /**
     * Impor foto dari folder seed lalu latih model. Mengembalikan versi baru
     * beserta jumlah foto yang diimpor.
     *
     * @throws AiServiceException
     */
    public function seedAndTrain(): array
    {
        $seed = $this->aiService->seedDataset();
        $version = $this->train();

        return [
            'version'      => $version,
            'seed_added'   => (int) ($seed['added'] ?? 0),
            'seed_skipped' => (int) ($seed['skipped'] ?? 0),
        ];
    }

    /**
     * Statistik gabungan: data lokal + info dari ML service.
     */
    public function stats(): array
    {
        $local = [
            'total_samples'       => TrainingSample::count(),
            'pending_samples'     => TrainingSample::where('used_in_training', false)->count(),
            'correct_predictions' => TrainingSample::where('was_prediction_correct', true)->count(),
            'active_version'      => ModelVersion::active(),
        ];

        try {
            $local['ml_info'] = $this->aiService->modelInfo();
        } catch (AiServiceException) {
            $local['ml_info'] = null;
        }

        return $local;
    }
}
