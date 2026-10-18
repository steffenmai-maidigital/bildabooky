<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eine Anmeldung eines Kindes während einer Klassen-Sitzung / One sign-in of a child during a class session */
class ClassSessionLogin extends Model
{
    protected $table = 'class_session_logins';
    public const UPDATED_AT = null;
    protected $fillable = ['class_session_id', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }
}
