<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\anime_data;
use App\Models\anime_image;
use App\Models\anime_trailer;
use App\Models\anime_producer;
use App\Models\anime_studio;
use App\Models\anime_genre;


class GrabAnimeDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $mal_id;
    /**
     * Create a new job instance.
     */
    public function __construct($mal_id)
    {
        $this->mal_id = $mal_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::timeout(10)->get("https://api.jikan.moe/v4/anime/{$this->mal_id}");

            if (!$response->successful() || !isset($response['data'])) {
                Log::warning("Gagal ambil data mal_id {$this->mal_id}: " . $response->status());
                return;
            }

            $anime = $response['data'];

            // Cek duplikat
            $existing = anime_data::where('judul', $anime['title'])
                ->where('tahun_rilis', $anime['year'] ?? now()->year)
                ->first();

            if ($existing) return;

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

            // Genre many-to-many
            foreach ($anime['genres'] as $genre) {
                $genreModel = anime_genre::firstOrCreate([
                    'nama_genre' => $genre['name']
                ]);

                $animeData->genres()->attach($genreModel->id);
            }

            DB::commit();
            // Hitung total data anime yang sudah masuk
            $grabbedCount = anime_data::count();
            $totalTarget = 1000;
            $percentage = round(($grabbedCount / $totalTarget) * 100, 1);

            echo "Grabbed {$grabbedCount}/{$totalTarget} ({$percentage}%)\n";
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error mal_id {$this->mal_id}: " . $e->getMessage());
        }
    }
}
