<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'headers',
        'enabled',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Webhook $webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = Str::random(32);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'headers' => 'array',
            'enabled' => 'boolean',
        ];
    }
}
