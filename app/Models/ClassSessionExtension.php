<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eine Verlängerung einer Klassen-Sitzung / One extension of a class session */
class ClassSessionExtension extends Model
{
    protected $table = 'class_session_extensions';
    public const UPDATED_AT = null;
    protected $fillable = ['class_session_id', 'user_id', 'old_end', 'new_end'];
    protected $casts = ['old_end' => 'datetime', 'new_end' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
