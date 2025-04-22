<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class anime_data extends Model
{
    use HasFactory;
    protected $fillable = [
        'url',
        'judul',
        'judul_inggris',
        'judul_jepang',
        'tipe',
        'source',
        'episode',
        'status',
        'durasi',
        'rating',
        'sinopsis',
        'season',
        'tahun_rilis',
        'anime_image_id',
        'anime_trailer_id',
        'anime_produser_id',
        'anime_studio_id',
        'anime_genre_id',
        'anime_genre_h_id',
        'mal_id',
    ]; 
    public function image()
{
    return $this->belongsTo(anime_image::class, 'anime_image_id');
}
public function trailer()
{
    return $this->belongsTo(anime_trailer::class, 'anime_trailer_id');
}
public function producer()
{
    return $this->belongsTo(anime_producer::class, 'anime_produser_id');
}
public function studio()
{
    return $this->belongsTo(anime_studio::class, 'anime_studio_id');
}
public function genres()
{
    return $this->belongsToMany(anime_genre::class, 'anime_data_genre');
}
public function genreUtama()
{
    return $this->belongsTo(anime_genre::class, 'anime_genre_id');
}
public function genreTambahan()
{
    return $this->belongsTo(anime_genre::class, 'anime_genre_h_id');
}

   
}
