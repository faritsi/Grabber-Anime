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

class anime_dataController extends Controller
{
    public function index()
    {
        $anime_data = anime_data::with(['image', 'trailer', 'studio', 'producer', 'genre', 'genreH'])->latest()->paginate(5);
        return new DataResource(true, 'List Anime Data', $anime_data);
    }

    public function show($id)
    {
        $animeData = anime_data::with(['image', 'trailer', 'studio', 'producer', 'genre', 'genreH'])->find($id);
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

        // Proses genre
        $genreList = $anime['genres'];
        foreach ($genreList as $genre) {
            // Cek apakah genre sudah ada
            $genreModel = anime_genre::firstOrCreate([
                'nama_genre' => $genre['name']
            ]);

            // Attach genre ke animeData (many-to-many)
            $animeData->genres()->attach($genreModel->id);
        }

        return new DataResource(true, 'Data berhasil di-grab dari MyAnimeList!', $animeData);
    }

    // public function grabber($mal_id)
    // {
    //     $response = Http::get("https://api.jikan.moe/v4/anime/{$mal_id}");

    //     if (!$response->successful() || !isset($response['data'])) {
    //         return response()->json(['message' => 'Gagal mengambil data dari Jikan API'], 500);
    //     }

    //     $anime = $response['data'];

    //     $animeImage = anime_image::create([
    //         'image_url' => $anime['images']['jpg']['large_image_url'] ?? ''
    //     ]);

    //     $animeTrailer = anime_trailer::create([
    //         'trailer_url' => $anime['trailer']['url'] ?? 'https://youtube.com'
    //     ]);

    //     $animeProducer = anime_producer::create([
    //         'nama_produser' => $anime['producers'][0]['name'] ?? 'Unknown'
    //     ]);

    //     $animeStudio = anime_studio::create([
    //         'nama_studio' => $anime['studios'][0]['name'] ?? 'Unknown'
    //     ]);

    //     $genreList = $anime['genres'];
    //     $animeGenre = anime_genre::create([
    //         'nama_genre' => $genreList[0]['name'] ?? 'Unknown'
    //     ]);
    //     $animeGenreH = anime_genre::create([
    //         'nama_genre' => $genreList[1]['name'] ?? $genreList[0]['name'] ?? 'Unknown'
    //     ]);

    //     $animeData = anime_data::create([
    //         'url' => $anime['url'],
    //         'anime_image_id' => $animeImage->id,
    //         'judul' => $anime['title'],
    //         'judul_inggris' => $anime['title_english'] ?? $anime['title'],
    //         'judul_jepang' => $anime['title_japanese'] ?? $anime['title'],
    //         'tipe' => $anime['type'],
    //         'source' => $anime['source'],
    //         'episode' => $anime['episodes'] ?? 0,
    //         'status' => $anime['status'],
    //         'durasi' => $anime['duration'],
    //         'rating' => $anime['score'] ?? 0,
    //         'sinopsis' => $anime['synopsis'],
    //         'season' => ucfirst($anime['season'] ?? 'Unknown'),
    //         'tahun_rilis' => $anime['year'] ?? now()->year,
    //         'anime_trailer_id' => $animeTrailer->id,
    //         'anime_produser_id' => $animeProducer->id,
    //         'anime_studio_id' => $animeStudio->id,
    //         'anime_genre_id' => $animeGenre->id,
    //         'anime_genre_h_id' => $animeGenreH->id,
    //     ]);

    //     return new DataResource(true, 'Data berhasil di-grab dari MyAnimeList!', $animeData);
    // }
    public function grabberIndex()
    {
        $animeData = anime_data::with([
            'image',
            'trailer',
            'producer',
            'studio',
            'genreUtama',
            'genreTambahan'
        ])->latest()->paginate(10);
    
        return new DataResource(true, 'List Anime dari Grabber', $animeData);
    }

    public function bulkGrabber()
    {
        $inserted = [];
        $skipped = [];
        $limit = 20;
        $max = 1000;

        try {
            for ($page = 1; $page <= ceil($max / $limit); $page++) {
                $response = Http::retry(3, 200)
                    ->timeout(10)
                    ->get("https://api.jikan.moe/v4/anime", [
                        'limit' => $limit,
                        'page' => $page,
                        'order_by' => 'popularity',
                        'sort' => 'desc'
                    ]);

                if (!$response->successful() || !isset($response['data'])) {
                    break;
                }

                foreach ($response['data'] as $anime) {
                    if (anime_data::where('mal_id', $anime['mal_id'])->exists()) {
                        $skipped[] = $anime['title'];
                        continue;
                    }

                    $image = anime_image::create([
                        'image_url' => $anime['images']['jpg']['image_url'] ?? 'default.jpg'
                    ]);

                    $trailer = anime_trailer::create([
                        'trailer_url' => $anime['trailer']['url'] ?? 'https://youtube.com'
                    ]);

                    $producer = anime_producer::firstOrCreate([
                        'nama_produser' => $anime['producers'][0]['name'] ?? 'Unknown'
                    ]);

                    $studio = anime_studio::firstOrCreate([
                        'nama_studio' => $anime['studios'][0]['name'] ?? 'Unknown'
                    ]);

                    $data = anime_data::create([
                        'mal_id' => $anime['mal_id'],
                        'url' => $anime['url'],
                        'anime_image_id' => $image->id,
                        'judul' => $anime['title'],
                        'judul_inggris' => $anime['title_english'] ?? $anime['title'],
                        'judul_jepang' => $anime['title_japanese'] ?? '-',
                        'tipe' => $anime['type'] ?? 'TV',
                        'source' => $anime['source'] ?? 'Unknown',
                        'episode' => $anime['episodes'] ?? 0,
                        'status' => $anime['status'] ?? 'Unknown',
                        'durasi' => $anime['duration'] ?? '-',
                        'rating' => $anime['score'] ?? 'N/A',
                        'sinopsis' => $anime['synopsis'] ?? 'Tidak ada sinopsis.',
                        'season' => ucfirst($anime['season'] ?? 'Unknown'),
                        'tahun_rilis' => $anime['year'] ?? 0,
                        'anime_trailer_id' => $trailer->id,
                        'anime_produser_id' => $producer->id,
                        'anime_studio_id' => $studio->id,
                    ]);

                    if (!empty($anime['genres'])) {
                        foreach ($anime['genres'] as $genre) {
                            $genreModel = anime_genre::firstOrCreate([
                                'nama_genre' => $genre['name']
                            ]);
                            $data->genres()->syncWithoutDetaching([$genreModel->id]);
                        }
                    }

                    $inserted[] = $data;

                    // Stop jika sudah mencapai 1000
                    if (count($inserted) >= $max) {
                        break 2;
                    }
                }

                // Delay 1 detik antar request
                sleep(1);
            }

            return response()->json([
                'success' => true,
                'message' => count($inserted) . ' anime berhasil di-grab. ' . count($skipped) . ' dilewati.',
                'inserted_count' => count($inserted),
                'skipped_titles' => $skipped
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi error saat ambil data dari Jikan API.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // public function bulkGrabber()
    // {
    //     $response = Http::get("https://api.jikan.moe/v4/anime", [
    //         'limit' => 5,
    //         'order_by' => 'popularity',
    //         'sort' => 'desc'
    //     ]);
    
    //     if (!$response->successful() || !isset($response['data'])) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal mengambil data dari Jikan API',
    //             'status' => $response->status()
    //         ], 500);
    //     }
    
    //     $inserted = [];
    
    //     foreach ($response['data'] as $anime) {
    //         // Skip jika sudah ada
    //         if (anime_data::where('mal_id', $anime['mal_id'])->exists()) {
    //             continue;
    //         }
    
    //         // Simpan relasi satu-satu
    //         $image = anime_image::create([
    //             'image_url' => $anime['images']['jpg']['image_url'] ?? 'default.jpg'
    //         ]);
    
    //         $trailer = anime_trailer::create([
    //             'trailer_url' => $anime['trailer']['url'] ?? 'https://youtube.com'
    //         ]);
    
    //         $producer = anime_producer::create([
    //             'nama_produser' => $anime['producers'][0]['name'] ?? 'Unknown'
    //         ]);
    
    //         $studio = anime_studio::create([
    //             'nama_studio' => $anime['studios'][0]['name'] ?? 'Unknown'
    //         ]);
    
    //         $genreUtama = anime_genre::create([
    //             'nama_genre' => $anime['genres'][0]['name'] ?? 'Action'
    //         ]);
    
    //         $genreTambahan = anime_genre::create([
    //             'nama_genre' => $anime['genres'][1]['name'] ?? 'Adventure'
    //         ]);
    
    //         // Simpan anime_data
    //         $data = anime_data::create([
    //             'mal_id' => $anime['mal_id'],
    //             'url' => $anime['url'],
    //             'anime_image_id' => $image->id,
    //             'judul' => $anime['title'],
    //             'judul_inggris' => $anime['title_english'] ?? $anime['title'],
    //             'judul_jepang' => $anime['title_japanese'] ?? '-',
    //             'tipe' => $anime['type'] ?? 'TV',
    //             'source' => $anime['source'] ?? 'Unknown',
    //             'episode' => $anime['episodes'] ?? 0,
    //             'status' => $anime['status'] ?? 'Unknown',
    //             'durasi' => $anime['duration'] ?? '-',
    //             'rating' => $anime['score'] ?? 'N/A',
    //             'sinopsis' => $anime['synopsis'] ?? 'Tidak ada sinopsis.',
    //             'season' => $anime['season'] ?? 'Unknown',
    //             'tahun_rilis' => $anime['year'] ?? 0,
    //             'anime_trailer_id' => $trailer->id,
    //             'anime_produser_id' => $producer->id,
    //             'anime_studio_id' => $studio->id,
    //             'anime_genre_id' => $genreUtama->id,
    //             'anime_genre_h_id' => $genreTambahan->id,
    //         ]);
    
    //         $inserted[] = $data;
    //     }
    
    //     return new DataResource(true, count($inserted) . ' anime berhasil di-grab dan disimpan.', $inserted);
    // }
           
}