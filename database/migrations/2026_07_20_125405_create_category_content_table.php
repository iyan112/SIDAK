<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; // <-- Pastikan ini yang di-import, bukan Route

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // GANTI 'Route::create' MENJADI 'Schema::create'
        Schema::create('category_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_content');
    }
};