<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel data latih: tiap foto yang dikonfirmasi/dikoreksi warga menjadi
     * satu sampel berlabel untuk fine-tune model AI.
     */
    public function up(): void
    {
        Schema::create('training_samples', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Label kebenaran (ground truth) hasil konfirmasi user.
            $table->foreignId('waste_category_id')->constrained('waste_categories');

            // Apa yang ditebak model saat scan (untuk audit & metrik akurasi).
            $table->unsignedBigInteger('predicted_category_id')->nullable();
            $table->decimal('confidence_score', 3, 2)->default(0);

            $table->string('image_path');

            // true jika user mengonfirmasi tebakan benar; false jika mengoreksi.
            $table->boolean('was_prediction_correct')->default(false);

            // Penanda agar tidak dilatih ulang berkali-kali.
            $table->boolean('used_in_training')->default(false);

            $table->timestamp('created_at')->useCurrent();

            $table->index('used_in_training');
            $table->index('waste_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_samples');
    }
};
