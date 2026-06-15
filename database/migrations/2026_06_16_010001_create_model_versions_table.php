<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat versi model AI. Tiap kali model dilatih ulang, satu baris baru
     * dibuat berisi metrik latih. Hanya satu versi yang aktif (is_active).
     */
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version');
            $table->unsignedInteger('sample_count')->default(0);
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
