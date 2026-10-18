<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = ['book_id', 'user_id', 'kind', 'path', 'mime', 'size'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
