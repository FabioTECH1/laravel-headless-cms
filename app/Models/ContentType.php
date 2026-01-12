<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ContentType extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'has_ownership',
        'is_component',
        'is_single',
        'is_localized',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'has_ownership' => 'boolean',
        'is_component' => 'boolean',
        'is_single' => 'boolean',
        'is_localized' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(ContentField::class);
    }
}
