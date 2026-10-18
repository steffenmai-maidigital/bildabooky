<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Schulklasse / School class */
class SchoolClass extends Model
{
    protected $fillable = ['name'];

    public function members(): HasMany
    {
        return $this->hasMany(User::class)->orderBy('last_name')->orderBy('first_name');
    }

    public function pupils(): HasMany
    {
        return $this->members()->where('role', User::PUPIL);
    }

    /** Lehrkräfte der Klasse — Klassenlehrkräfte zuerst / Teachers of the class — form teachers first */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_leader')->withPivot('is_class_teacher')
            ->orderByDesc('class_leader.is_class_teacher')->orderBy('last_name');
    }

    /**
     * Klassenlehrkräfte: verantwortlich für die Klasse (reine Kennzeichnung, mehrere möglich)
     *
     * Form teachers: responsible for the class (a mere label, several possible)
     */
    public function classTeachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_leader')->wherePivot('is_class_teacher', true)->orderBy('last_name');
    }

    /**
     * Klassenlehrkräfte festlegen; sie werden dabei auch Lehrkräfte der Klasse
     *
     * Set the form teachers; they also become teachers of the class
     */
    public function setClassTeachers(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $this->leaders()->syncWithoutDetaching($ids);
        \Illuminate\Support\Facades\DB::table('class_leader')->where('school_class_id', $this->id)
            ->update(['is_class_teacher' => false]);
        if ($ids) {
            \Illuminate\Support\Facades\DB::table('class_leader')->where('school_class_id', $this->id)
                ->whereIn('user_id', $ids)->update(['is_class_teacher' => true]);
        }
    }

    /** Fächer der Klasse, sortiert / Subjects of the class, sorted */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject')->orderBy('subjects.position')->orderBy('subjects.name');
    }

    /**
     * Zuordnung Fach → Lehrkräfte in dieser Klasse: [subject_id => [user_id, …]]
     *
     * Mapping subject → teachers in this class: [subject_id => [user_id, …]]
     */
    public function teaching(): array
    {
        return \Illuminate\Support\Facades\DB::table('class_subject_teacher')->where('school_class_id', $this->id)->get()
            ->groupBy('subject_id')->map(fn ($z) => $z->pluck('user_id')->map(fn ($v) => (int) $v)->all())->all();
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
