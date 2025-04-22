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
        Schema::create('anime_datas', function (Blueprint $table) {
        $table->id();
        $table->string('url');
        $table->string('judul');
        $table->string('judul_inggris')->nullable();
        $table->string('judul_jepang')->nullable();
        $table->string('tipe');
        $table->string('source');
        $table->integer('episode')->nullable();
        $table->string('status');
        $table->string('durasi')->nullable();
        $table->string('rating')->nullable();
        $table->text('sinopsis')->nullable();
        $table->string('season')->nullable();
        $table->integer('tahun_rilis')->nullable();
        $table->foreignId('anime_image_id')->constrained('anime_images')->onDelete('cascade');
        $table->foreignId('anime_trailer_id')->constrained('anime_trailers')->onDelete('cascade');
        $table->foreignId('anime_produser_id')->constrained('anime_producers')->onDelete('cascade');
        $table->foreignId('anime_studio_id')->constrained('anime_studios')->onDelete('cascade');
        // $table->foreignId('anime_genre_id')->constrained('anime_genres')->onDelete('cascade');
        // $table->foreignId('anime_genre_h_id')->constrained('anime_genres')->onDelete('cascade');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anime_datas');
    }
};
