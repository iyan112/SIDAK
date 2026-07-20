<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class contents extends Model
{
    // 1. Definisikan nama tabel yang ada di database Anda
    protected $table = 'contents';

    // 2. Tentukan kolom mana saja yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Relasi ke tabel contents (Satu kategori memiliki banyak konten)
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'category_id', 'id');
    }
}