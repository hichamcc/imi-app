<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PersonFile extends Model
{
    protected $fillable = [
        'person_id',
        'uploaded_by',
        'original_name',
        'path',
        'mime_type',
        'size_bytes',
        'label',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }

    protected static function booted(): void
    {
        static::deleting(function (PersonFile $file) {
            if ($file->path && Storage::disk('local')->exists($file->path)) {
                Storage::disk('local')->delete($file->path);
            }
        });
    }
}
