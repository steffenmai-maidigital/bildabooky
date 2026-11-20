<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = ['book_id', 'user_id', 'kind', 'path', 'mime', 'size'];

    // unused_since: seit wann das Medium auf keiner Seite mehr steht (bildabooky:prune-media)
    // unused_since: since when the medium is no longer on any page (bildabooky:prune-media)
    protected $casts = ['unused_since' => 'datetime'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
