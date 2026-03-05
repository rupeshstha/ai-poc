<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RagDocument extends Model
{
    protected $fillable = ['title', 'content', 'tfidf_vector'];

    protected function casts(): array
    {
        return [
            'tfidf_vector' => 'array',
        ];
    }
}
