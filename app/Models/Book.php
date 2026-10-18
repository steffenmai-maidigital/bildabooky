<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    public const FORMATS = ['portrait' => 'Hochformat', 'square' => 'Quadrat', 'landscape' => 'Querformat'];

    // Art des Buchs
    // Kind of book
    public const KIND_OWN = 'own';       // gehört einer Person / belongs to one person
    public const KIND_CLASS = 'class';     // Klassenbuch: die ganze Klasse schreibt mit / class book: whole class writes
    public const KIND_TEMPLATE = 'template';   // Vorlage: jedes Kind legt seine eigene Version an / template: child versions

    // Teilen (nur bei eigenen Büchern)
    // Sharing (own books only)
    public const SHARING_PRIVATE = 'private';
    public const SHARING_TEACHER = 'teacher';
    public const SHARING_CLASS = 'class';

    protected $fillable = ['user_id', 'school_class_id', 'title', 'format', 'kind', 'sharing', 'template_id', 'submitted_at', 'returned_at', 'return_note', 'returned_by', 'subject_id'];
    protected $casts = ['submitted_at' => 'datetime', 'returned_at' => 'datetime'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('position')->orderBy('id');
    }

    public function firstPage(): HasOne
    {
        return $this->hasOne(Page::class)->orderBy('position')->orderBy('id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookLog::class);
    }

    /** Vorlage, aus der diese Version stammt / Template this version comes from */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'template_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Fächer, die für dieses Buch erlaubt sind: die der Klasse; ohne Klasse alle
     *
     * Subjects allowed for this book: those of the class; without a class, all
     */
    public function allowedSubjects(): \Illuminate\Support\Collection
    {
        return $this->school_class_id
            ? ($this->schoolClass?->subjects()->get() ?? collect())
            : Subject::sorted()->get();
    }

    /** Lehrkraft, die die Version zuletzt zurückgegeben hat / Teacher who last gave the version back */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /** Zurückgegeben und seitdem nicht erneut abgegeben / Given back and not handed in again since */
    public function isReturned(): bool
    {
        return $this->returned_at !== null && $this->submitted_at === null;
    }

    /**
     * Lehrkraft (bzw. unterrichtender Admin) darf eine abgegebene Version zurückgeben
     *
     * Teacher (or teaching admin) may give back a handed-in version
     */
    public function returnableBy(User $user): bool
    {
        return $this->isVersion() && $this->submitted_at !== null && $this->user_id != $user->id && $this->isTeacherOf($user);
    }

    /** Versionen der Kinder (nur bei Vorlagen) / Children's versions (templates only) */
    public function versions(): HasMany
    {
        return $this->hasMany(Book::class, 'template_id');
    }

    public function isClassBook(): bool
    {
        return $this->kind === self::KIND_CLASS;
    }

    public function isTemplate(): bool
    {
        return $this->kind === self::KIND_TEMPLATE;
    }

    /** Version eines Kindes aus einer Vorlage / A child's version from a template */
    public function isVersion(): bool
    {
        return $this->template_id !== null;
    }

    // ---- Rechte / Permissions -----------------------------------------
    //
    //  Lehrkraft:      sieht ALLE Bücher ihrer Klasse und darf helfen
    //                  (bearbeiten), verwalten und das Protokoll lesen.
    //  Eigene Bücher:  andere Kinder sehen sie nur, wenn „mit der Klasse“
    //                  geteilt. „Mit der Lehrkraft teilen“ ist ein Hinweis
    //                  an die Lehrkraft (Bereich „Mit dir geteilt“).
    //  Klassenbuch:    alle Kinder der Klasse sehen UND bearbeiten.
    //  Vorlage:        die Klasse sieht sie; bearbeiten nur Lehrkräfte.
    //  Version:        gehört dem Kind.
    //  Admin:          wie eine Lehrkraft — nur die Klassen, die er selbst
    //                  unterrichtet. Die Verwaltungsrolle öffnet keine Bücher.
    //
    //  Teacher:        sees ALL books of their class and may help
    //                  (edit), manage and read the log.
    //  Own books:      other children only see them if shared
    //                  “with the class”. “Share with the teacher” is a note
    //                  to the teacher (section “Shared with you”).
    //  Class book:     all children of the class see AND edit it.
    //  Template:       the class sees it; only teachers edit it.
    //  Version:        belongs to the child.
    //  Admin:          like a teacher — only the classes they teach
    //                  themselves. The admin role opens no books.

    private function inClass(User $user): bool
    {
        if (! $this->school_class_id) {
            return false;
        }
        return $user->isPupil()
            ? $user->school_class_id == $this->school_class_id
            : in_array((int) $this->school_class_id, $user->teachingClassIds(), true);
    }

    /**
     * Lehrkraft (oder unterrichtender Admin), die für dieses Buch zuständig ist
     *
     * Teacher (or teaching admin) responsible for this book
     */
    public function isTeacherOf(User $user): bool
    {
        $classes = $user->teachingClassIds();
        if (! $classes) {
            return false;
        }
        // Buch der Klasse, oder Buch eines Kindes dieser Klasse (auch ohne Klassenzuordnung)
        // Book of the class, or book of a child in this class (even without a class assignment)
        return in_array((int) $this->school_class_id, $classes, true)
            || ($this->owner && $this->owner->isPupil() && in_array((int) $this->owner->school_class_id, $classes, true));
    }

    public function visibleTo(User $user): bool
    {
        if ($this->user_id == $user->id || $this->isTeacherOf($user)) {
            return true;
        }
        if ($this->isClassBook() || $this->isTemplate()) {
            return $this->inClass($user);
        }
        return $this->sharing === self::SHARING_CLASS && ! $this->isVersion() && $this->inClass($user);
    }

    /** Seiten und Inhalte bearbeiten / Edit pages and content */
    public function editableBy(User $user): bool
    {
        if ($this->user_id == $user->id || $this->isTeacherOf($user)) {
            return true;
        }
        return $this->isClassBook() && $this->inClass($user);
    }

    /**
     * Titel, Format, Teilen, Löschen, Seiten löschen und umsortieren
     *
     * Title, format, sharing, deleting, deleting and reordering pages
     */
    public function manageableBy(User $user): bool
    {
        return $this->user_id == $user->id || $this->isTeacherOf($user);
    }

    /**
     * Änderungsprotokoll: nur Lehrkräfte bzw. unterrichtende Admins der Klasse
     *
     * Change log: only teachers or teaching admins of the class
     */
    public function logVisibleTo(User $user): bool
    {
        return ($user->isTeacher() || $user->isAdmin()) && ($this->user_id == $user->id || $this->isTeacherOf($user));
    }

    /** In der Klassenbibliothek sichtbare eigene Bücher / Own books visible in the class library */
    public function scopeSharedIn(Builder $query, array $classIds): Builder
    {
        return $query->where('kind', self::KIND_OWN)->where('sharing', self::SHARING_CLASS)->whereIn('school_class_id', $classIds);
    }

    // ---- Daten für die Oberfläche / Data for the interface ---------------

    public function toEditorArray(): array
    {
        return [
            'id'      => $this->id,
            'title'   => $this->title,
            'format'  => $this->format,
            'kind'    => $this->kind,
            'sharing' => $this->sharing,
            'class'   => $this->school_class_id,
            'pages'   => $this->pages->map->toEditorArray()->values()->all(),
        ];
    }

    public function deleteWithMedia(): void
    {
        Storage::disk('media')->deleteDirectory((string) $this->id);
        $this->delete();
    }

    /**
     * Vollständige Kopie inkl. Mediendateien (neue IDs). $asVersion: die Kopie
     * ist die Version eines Kindes aus einer Vorlage (gleicher Titel, für die
     * Lehrkraft sichtbar).
     *
     * Full copy incl. media files (new IDs). $asVersion: the copy
     * is a child's version from a template (same title, visible to the
     * teacher).
     */
    public function duplicateFor(User $user, bool $asVersion = false): Book
    {
        $copy = Book::create([
            'user_id'         => $user->id,
            'school_class_id' => $user->isPupil() ? $user->school_class_id : $this->school_class_id,
            'title'           => $asVersion ? $this->title : mb_substr($this->title . ' (' . __('Kopie') . ')', 0, 120),
            'format'          => $this->format,
            'kind'            => self::KIND_OWN,
            'sharing'         => $asVersion ? self::SHARING_TEACHER : self::SHARING_PRIVATE,
            'template_id'     => $asVersion ? $this->id : null,
            'subject_id'      => $this->subject_id,
        ]);

        $map = [];
        $disk = Storage::disk('media');
        foreach ($this->media as $m) {
            $newPath = $copy->id . '/' . basename($m->path);
            if ($disk->exists($m->path)) {
                $disk->copy($m->path, $newPath);
            }
            $n = $copy->media()->create([
                'user_id' => $user->id, 'kind' => $m->kind, 'path' => $newPath,
                'mime' => $m->mime, 'size' => $m->size,
            ]);
            $map[$m->id] = $n->id;
        }

        foreach ($this->pages as $p) {
            $elements = array_map(function ($e) use ($map) {
                if (isset($e['media'])) {
                    $e['media'] = $map[$e['media']] ?? $e['media'];
                }
                return $e;
            }, $p->elementList());
            $copy->pages()->create([
                'position' => $p->position, 'background' => $p->background,
                'elements' => json_encode($elements),
            ]);
        }
        return $copy;
    }
}
