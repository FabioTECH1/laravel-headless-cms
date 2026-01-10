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
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'has_ownership' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(ContentField::class);
    }
}
