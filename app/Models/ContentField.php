<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ContentField extends Model
{
    use HasUlids;

    protected $fillable = [
        'content_type_id',
        'name',
        'type',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function contentType()
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }
}
