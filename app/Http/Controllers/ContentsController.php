<?php

namespace App\Http\Controllers;

use App\Models\Contents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ContentsController extends Controller
{
    /**
     * Tampilkan semua daftar konten beserta kategori-kategorinya.
     */
    public function index()
    {
        $contents = Contents::with('categories')->latest()->get();
        return response()->json($contents, 200);
    }

    /**
     * Simpan konten baru ke database (Create).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'ustadz' => 'required|string|max:255',
            'description' => 'required|string',
            'video_source' => 'required|in:upload,youtube',
            'video_file' => 'required_if:video_source,upload|file|mimes:mp4,mov,avi,mkv|max:512000',
            'video_url' => 'required_if:video_source,youtube|url',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:draft,published',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $videoUrl = null;
        $fileName = null;
        $fileSizeMb = null;
        $durationInSeconds = 0;
        $thumbnailPath = null;

        // 1. Olah Thumbnail File jika ada yang diupload
        if ($request->hasFile('thumbnail_file')) {
            $thumbnailPath = $request->file('thumbnail_file')->store('thumbnails', 'public');
            $thumbnailPath = asset('storage/' . $thumbnailPath);
        }

        // 2. Olah Sumber Video (Upload Lokal vs Youtube Link)
        if ($request->video_source === 'upload') {
            $file = $request->file('video_file');
            $fileName = $file->getClientOriginalName();
            $fileSizeMb = round($file->getSize() / (1024 * 1024), 2); // Konversi byte ke MB

            $path = $file->store('videos', 'public');
            $videoUrl = asset('storage/' . $path);

            // Hitung durasi otomatis dari file lokal
            $durationInSeconds = $this->getLocalVideoDuration(storage_path('app/public/' . $path));
        } else {
            $videoUrl = $request->video_url;
            // Hitung durasi otomatis dari link Youtube
            $durationInSeconds = $this->getYoutubeDuration($videoUrl);
        }

        // 3. Ubah format detik ke Label Durasi (Contoh: "05:23" atau "01:14:02")
        $durationLabel = $this->formatDurationLabel($durationInSeconds);

        // 4. Simpan ke tabel contents sesuai skema asli database Anda
        $content = Contents::create([
            'category_id' => $request->category_ids[0] ?? 0, // Fallback untuk kolom legacy
            'title' => $request->title,
            'ustadz' => $request->ustadz,
            'description' => $request->description,
            'video_source' => $request->video_source,
            'video_url' => $videoUrl,
            'file_name' => $fileName,
            'file_size_mb' => $fileSizeMb,
            'thumbnail' => $thumbnailPath,
            'duration_label' => $durationLabel,
            'status' => $request->status,
            'views' => 0,
            'published_at' => $request->status === 'published' ? Carbon::now() : null,
        ]);

        // 5. Simpan banyak kategori sekaligus ke tabel pivot 'category_content'
        $content->categories()->attach($request->category_ids);

        return response()->json([
            'message' => 'Konten dakwah berhasil disimpan.',
            'data' => $content->load('categories')
        ], 201);
    }

    /**
     * Tampilkan detail satu konten tertentu (Read).
     */
    public function show($id)
    {
        $content = Contents::with('categories')->findOrFail($id);
        return response()->json($content, 200);
    }

    /**
     * Perbarui data konten beserta multi-kategorinya (Update).
     */
    public function update(Request $request, $id)
    {
        $content = Contents::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'ustadz' => 'required|string|max:255',
            'description' => 'required|string',
            'video_source' => 'required|in:upload,youtube',
            'video_url' => 'required_if:video_source,youtube|url',
            'status' => 'required|in:draft,published',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $durationLabel = $content->duration_label;
        $videoUrl = $content->video_url;

        // Jika mengubah link youtube baru, hitung ulang durasi labelnya
        if ($request->video_source === 'youtube' && $request->video_url !== $content->video_url) {
            $videoUrl = $request->video_url;
            $durationInSeconds = $this->getYoutubeDuration($videoUrl);
            $durationLabel = $this->formatDurationLabel($durationInSeconds);
        }

        // Update data dasar
        $content->update([
            'category_id' => $request->category_ids[0] ?? $content->category_id,
            'title' => $request->title,
            'ustadz' => $request->ustadz,
            'description' => $request->description,
            'video_source' => $request->video_source,
            'video_url' => $videoUrl,
            'duration_label' => $durationLabel,
            'status' => $request->status,
            'published_at' => ($request->status === 'published' && !$content->published_at) ? Carbon::now() : $content->published_at,
        ]);

        // Sinkronkan multi kategori pada tabel pivot
        $content->categories()->sync($request->category_ids);

        return response()->json([
            'message' => 'Konten dakwah berhasil diperbarui.',
            'data' => $content->load('categories')
        ], 200);
    }

    /**
     * Hapus konten beserta relasinya (Delete).
     */
    public function destroy($id)
    {
        $content = Contents::findOrFail($id);

        // Hapus file video lokal fisik jika bertipe upload
        if ($content->video_source === 'upload' && $content->video_url) {
            $parsedUrl = parse_url($content->video_url, PHP_URL_PATH);
            $relativePath = str_replace('/storage/', '', $parsedUrl);
            Storage::disk('public')->delete($relativePath);
        }

        $content->delete(); // Hubungan tabel pivot ikut terhapus otomatis karena cascade constraint

        return response()->json([
            'message' => 'Konten dakwah berhasil dihapus dari sistem.'
        ], 200);
    }

    /**
     * HELPER 1: Menghitung detik video lokal via ffprobe
     */
    private function getLocalVideoDuration($fullPath)
    {
        if (shell_exec("command -v ffprobe") || shell_exec("where ffprobe")) {
            $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"" . $fullPath . "\"";
            $output = shell_exec($cmd);
            if ($output) {
                return (int) round(floatval(trim($output)));
            }
        }
        return 180; // Default 3 menit jika ffprobe tidak aktif di server local
    }

    /**
     * HELPER 2: Mendapatkan estimasi durasi video dari link Youtube
     */
    private function getYoutubeDuration($url)
    {
        // 1. Ekstrak Video ID dari link YouTube
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match);

        if (isset($match[1])) {
            $video_id = $match[1];
            $cleanUrl = "https://www.youtube.com/watch?v={$video_id}";

            // 2. Ambil isi source code HTML dari halaman video YouTube
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            $html = @file_get_contents($cleanUrl, false, $context);

            if ($html) {
                // 3. Cari tag <meta itemprop="duration" content="PT...S"> menggunakan Regex
                if (preg_match('/<meta\s+itemprop="duration"\s+content="([^"]+)"/i', $html, $metaMatch)) {
                    $isoDuration = $metaMatch[1]; // Hasil contoh: PT14M25S atau PT1H5M30S

                    try {
                        // 4. Konversi format waktu ISO 8601 ke total detik
                        $interval = new \DateInterval($isoDuration);
                        $seconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
                        return $seconds;
                    } catch (\Exception $e) {
                        // Jika DateInterval gagal karena format string tidak standar, gunakan fallback manual regex
                        $hours = $minutes = $seconds = 0;
                        if (preg_match('/(\d+)H/', $isoDuration, $h))
                            $hours = (int) $h[1];
                        if (preg_match('/(\d+)M/', $isoDuration, $m))
                            $minutes = (int) $m[1];
                        if (preg_match('/(\d+)S/', $isoDuration, $s))
                            $seconds = (int) $s[1];

                        return ($hours * 3600) + ($minutes * 60) + $seconds;
                    }
                }
            }
        }

        return 180; // Fallback standar 3 menit jika gagal melakukan koneksi ke halaman YouTube
    }
    /**
     * HELPER 3: Format total detik ke bentuk String "HH:MM:SS" atau "MM:SS"
     */
    private function formatDurationLabel($seconds)
    {
        if ($seconds <= 0)
            return "00:00";

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}