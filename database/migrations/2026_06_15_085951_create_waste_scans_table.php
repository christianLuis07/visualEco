<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('waste_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignId('waste_category_id')->nullable()->index()->constrained('waste_categories')->nullOnDelete();
            $table->string('image_path');
            $table->string('detected_label');
            $table->decimal('confidence_score', 3, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('points_awarded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_scans');
    }
};
