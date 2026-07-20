<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotCategoryContent extends Pivot
{
    // Tentukan nama tabel pivot fisiknya
    protected $table = 'category_content';

    // Jika kamu ingin mencatat timestamps (created_at & updated_at) di tabel pivot
    public $timestamps = true;

    protected $fillable = [
        'content_id',
        'category_id',
    ];
}