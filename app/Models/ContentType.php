<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'has_ownership',
    ];

    public function fields()
    {
        return $this->hasMany(ContentField::class);
    }
}
