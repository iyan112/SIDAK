<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryContent extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
    ];
}