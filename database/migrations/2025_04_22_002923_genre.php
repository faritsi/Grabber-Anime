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
        Schema::create('anime_data_genre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anime_data_id')->constrained()->onDelete('cascade');
            $table->foreignId('anime_genre_id')->constrained()->onDelete('cascade');
            $table->timestamps();
    
            $table->unique(['anime_data_id', 'anime_genre_id']); // agar tidak duplikat
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
