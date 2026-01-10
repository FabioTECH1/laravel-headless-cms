<?php

namespace App\Services;

use App\Models\ContentType;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SchemaManager
{
    public function createType(string $name, array $fields, bool $isPublic = false, bool $hasOwnership = false): ContentType
    {
        if (! ctype_alnum(str_replace(' ', '', $name))) {
            throw new InvalidArgumentException('Content Type name must be alphanumeric.');
        }

        $slug = Str::slug($name);
        $tableName = Str::plural(Str::snake($name));

        if (Schema::hasTable($tableName)) {
            throw new Exception("Table {$tableName} already exists.");
        }

        return DB::transaction(function () use ($name, $slug, $tableName, $fields, $isPublic, $hasOwnership) {
            $contentType = ContentType::create([
                'name' => $name,
                'slug' => $slug,
                'has_ownership' => $hasOwnership,
                'is_public' => $isPublic,
            ]);

            foreach ($fields as $fieldData) {
                $contentType->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'settings' => $fieldData['settings'] ?? [],
                ]);
            }

            Schema::create($tableName, function (Blueprint $table) use ($fields, $hasOwnership) {
                $table->ulid('id')->primary();

                // Add user_id foreign key if ownership is enabled
                if ($hasOwnership) {
                    $table->foreignUlid('user_id')->nullable()->constrained()->onDelete('cascade');
                    $table->index('user_id');
                }

                foreach ($fields as $fieldData) {
                    $this->addColumn($table, $fieldData);
                }

                $table->timestamps();
                $table->softDeletes();
                $table->timestamp('published_at')->nullable();
            });

            return $contentType;
        });
    }

    public function updateType(string $slug, array $newFields, bool $isPublic, bool $hasOwnership): void
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();
        // Recalculate table name from original name or just use slug convention?
        // Since we store name in DB, we can use it.
        $tableName = Str::plural(Str::snake($contentType->name));

        DB::transaction(function () use ($contentType, $tableName, $newFields, $isPublic, $hasOwnership) {
            $wasOwned = $contentType->has_ownership;

            // Update content type settings
            $contentType->update([
                'is_public' => $isPublic,
                'has_ownership' => $hasOwnership,
            ]);

            // Add user_id column if ownership is being enabled
            if ($hasOwnership && ! $wasOwned) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignUlid('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                    $table->index('user_id');
                });
            }

            // Add new fields if provided
            foreach ($newFields as $fieldData) {
                $contentType->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'settings' => $fieldData['settings'] ?? [],
                ]);
            }

            // Add columns to database table for new fields
            if (count($newFields) > 0) {
                Schema::table($tableName, function (Blueprint $table) use ($newFields) {
                    foreach ($newFields as $fieldData) {
                        $this->addColumn($table, $fieldData);
                    }
                });
            }
        });
    }

    protected function addColumn(Blueprint $table, array $fieldData): void
    {
        $name = $fieldData['name'];
        $type = $fieldData['type'];

        $column = match ($type) {
            'text' => $table->string($name),
            'longtext' => $table->text($name),
            'integer' => $table->integer($name),
            'boolean' => $table->boolean($name),
            'datetime' => $table->dateTime($name),
            'relation' => $table->ulid($name.'_id')->nullable()->index(),
            'media' => $table->ulid($name.'_id')->nullable()->index(),
            default => throw new InvalidArgumentException("Unsupported field type: {$type}"),
        };

        // Relations and media are already nullable by default above, so we skip them.
        if (! in_array($type, ['relation', 'media'])) {
            $isRequired = $fieldData['settings']['required'] ?? false;

            if (! $isRequired) {
                $column->nullable();
            }
        }
    }

    public function deleteType(string $slug): void
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();
        $tableName = Str::plural(Str::snake($contentType->name));

        DB::transaction(function () use ($contentType, $tableName) {
            $contentType->delete();
            Schema::dropIfExists($tableName);
        });
    }
}
