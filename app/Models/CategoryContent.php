<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CategoryContent extends Model
{
    // Mengarahkan model bernama CategoryContent ke tabel 'categories' di MySQL
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Relasi balik ke tabel contents
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(
            Contents::class,
            'category_content',
            'category_id',
            'content_id'
        )->using(PivotCategoryContent::class);
    }
}