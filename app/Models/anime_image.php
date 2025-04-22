<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class anime_image extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected function image_url(): Attribute 
    {
        return Attribute::make(
            get: fn($image_url) => url('/storage/anime/' . $image_url),
        );
    }
}
