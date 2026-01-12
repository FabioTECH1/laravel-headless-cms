<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'filename',
        'alt_text',
        'caption',
        'folder_id',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'disk',
    ];

    protected $appends = ['url'];

    public function folder()
    {
        return $this->belongsTo(MediaFolder::class);
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk($this->disk)->url($this->path),
        );
    }
}
