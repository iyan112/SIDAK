<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contents extends Model
{
    protected $table = 'contents';

    // Sesuaikan 100% dengan skema kolom pada gambar phpMyAdmin Anda
    protected $fillable = [
        'category_id', // Bisa diisi ID utama atau diabaikan karena kita memakai pivot
        'title',
        'ustadz',
        'description',
        'video_source',
        'video_url',
        'file_name',
        'file_size_mb',
        'thumbnail',
        'duration_label',
        'status',
        'views',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'file_size_mb' => 'double',
        'views' => 'integer',
    ];

    /**
     * Relasi Multi Kategori via Tabel Pivot Custom
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            CategoryContent::class,
            'category_content',     // Nama tabel pivot fisiknya
            'content_id',           // Foreign key untuk tabel contents
            'category_id'           // Foreign key untuk tabel categories
        )->using(PivotCategoryContent::class)->withTimestamps();
    }
}