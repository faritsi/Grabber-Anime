<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\anime_data;
use App\Models\anime_genre;
use App\Models\anime_image;
use App\Models\anime_producer;
use App\Models\anime_studio;
use App\Models\anime_trailer;
use App\Http\Resources\DataResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\GrabAnimeDataJob;

class anime_dataController extends Controller
{
    public function index()
    {
        $anime_data = anime_data::select('id', 'judul', 'judul_inggris', 'tahun_rilis')->with([
            'image:id,anime_image_id,image_url',
            'trailer:id,anime_trailer_id,trailer_url',
            'studio:id,nama_studio',
            'producer:id,nama_produser',
            'genre:id,nama_genre',
            'genreH:id,nama_genre',
        ])->orderByDesc('id')->get();
        return new DataResource(true, 'List Anime', $anime_data);
    }

    public function show($id)
    {
        $animeData = anime_data::select('id', 'judul', 'tahun_rilis', 'rating')->with(['image:id,anime_image_id,image_url'])->find($id);
        if (!$animeData) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return new DataResource(true, 'Detail Anime', $animeData);
    }

    public function grabber($mal_id)
    {
        $response = Http::get("https://api.jikan.moe/v4/anime/{$mal_id}");

        if (!$response->successful() || !isset($response['data'])) {
            return response()->json(['message' => 'Gagal mengambil data dari Jikan API'], 500);
        }

        $anime = $response['data'];

        $animeImage = anime_image::create([
            'image_url' => $anime['images']['jpg']['large_image_url'] ?? ''
        ]);

        $animeTrailer = anime_trailer::create([
            'trailer_url' => $anime['trailer']['url'] ?? 'https://youtube.com'
        ]);

        $animeProducer = anime_producer::create([
            'nama_produser' => $anime['producers'][0]['name'] ?? 'Unknown'
        ]);

        $animeStudio = anime_studio::create([
            'nama_studio' => $anime['studios'][0]['name'] ?? 'Unknown'
        ]);

        $animeData = anime_data::create([
            'url' => $anime['url'],
            'anime_image_id' => $animeImage->id,
            'judul' => $anime['title'],
            'judul_inggris' => $anime['title_english'] ?? $anime['title'],
            'judul_jepang' => $anime['title_japanese'] ?? $anime['title'],
            'tipe' => $anime['type'],
            'source' => $anime['source'],
            'episode' => $anime['episodes'] ?? 0,
            'status' => $anime['status'],
            'durasi' => $anime['duration'],
            'rating' => $anime['score'] ?? 0,
            'sinopsis' => $anime['synopsis'],
            'season' => ucfirst($anime['season'] ?? 'Unknown'),
            'tahun_rilis' => $anime['year'] ?? now()->year,
            'anime_trailer_id' => $animeTrailer->id,
            'anime_produser_id' => $animeProducer->id,
            'anime_studio_id' => $animeStudio->id,
        ]);

        $genreList = $anime['genres'];
        foreach ($genreList as $genre) {
            $genreModel = anime_genre::firstOrCreate([
                'nama_genre' => $genre['name']
            ]);

            $animeData->genres()->attach($genreModel->id);
        }

        return new DataResource(true, 'Data berhasil di-grab dari MyAnimeList!', $animeData);
    }

    public function grabberIndex()
    {
        $animeData = anime_data::with([
            // 'image_url',
            'trailer',
            'producer',
            'studio',
            'genreUtama',
            'genreTambahan'
        ])->latest()->paginate(10);
    
        return new DataResource(true, 'List Anime dari Grabber', $animeData);
    }

    public function Maling()
    {
        ini_set('max_execution_time', 0);

        $maxRequests = 1000;
        $delaySeconds = 1;
        $batchSize = 15;

        $mal_id = 1;
        $grabbed = 0;

        while ($grabbed < $maxRequests) {
            for ($i = 0; $i < $batchSize && $grabbed < $maxRequests; $i++) {
                try {
                    $response = Http::timeout(10)->get("https://api.jikan.moe/v4/anime/{$mal_id}");

                    if (!$response->successful() || !isset($response['data'])) {
                        Log::warning("Gagal ambil data mal_id {$mal_id}: " . $response->status());
                        $mal_id++;
                        continue;
                    }

                    $anime = $response['data'];

                    $existing = anime_data::where('judul', $anime['title'])
                        ->where('tahun_rilis', $anime['year'] ?? now()->year)
                        ->first();

                    if ($existing) {
                        $mal_id++;
                        continue;
                    }

                    DB::beginTransaction();

                    $animeImage = anime_image::create([
                        'image_url' => $anime['images']['jpg']['large_image_url'] ?? ''
                    ]);

                    $animeTrailer = anime_trailer::create([
                        'trailer_url' => $anime['trailer']['url'] ?? 'https://youtube.com'
                    ]);

                    $animeProducer = anime_producer::create([
                        'nama_produser' => $anime['producers'][0]['name'] ?? 'Unknown'
                    ]);

                    $animeStudio = anime_studio::create([
                        'nama_studio' => $anime['studios'][0]['name'] ?? 'Unknown'
                    ]);

                    $animeData = anime_data::create([
                        'url' => $anime['url'],
                        'anime_image_id' => $animeImage->id,
                        'judul' => $anime['title'],
                        'judul_inggris' => $anime['title_english'] ?? $anime['title'],
                        'judul_jepang' => $anime['title_japanese'] ?? $anime['title'],
                        'tipe' => $anime['type'],
                        'source' => $anime['source'],
                        'episode' => $anime['episodes'] ?? 0,
                        'status' => $anime['status'],
                        'durasi' => $anime['duration'],
                        'rating' => $anime['score'] ?? 0,
                        'sinopsis' => $anime['synopsis'],
                        'season' => ucfirst($anime['season'] ?? 'Unknown'),
                        'tahun_rilis' => $anime['year'] ?? now()->year,
                        'anime_trailer_id' => $animeTrailer->id,
                        'anime_produser_id' => $animeProducer->id,
                        'anime_studio_id' => $animeStudio->id,
                    ]);

                    foreach ($anime['genres'] as $genre) {
                        $genreModel = anime_genre::firstOrCreate([
                            'nama_genre' => $genre['name']
                        ]);
                        $animeData->genres()->attach($genreModel->id);
                    }

                    DB::commit();
                    $grabbed++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error di mal_id {$mal_id}: " . $e->getMessage());
                }

                $mal_id++;
            }

            sleep($delaySeconds);
        }

        return response()->json([
            'status' => true,
            'message' => "Selesai grab {$grabbed} anime dari Jikan API."
        ]);
    }

    public function grabQueue()
    {
        $max = 1000;
        $start = 1;
    
        for ($mal_id = $start; $mal_id < $start + $max; $mal_id++) {
            GrabAnimeDataJob::dispatch($mal_id)->onQueue('anime');
        }
    
        return response()->json([
            'message' => 'Proses grab data anime sedang dijalankan di background (queue)',
            'status' => true
        ]);
    }   
}