<?php

namespace App\Services;

use App\Models\ContentType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchemaManager
{
    public function createType(string $name, array $fields): ContentType
    {
        if (! ctype_alnum(str_replace(' ', '', $name))) {
            throw new \InvalidArgumentException('Content Type name must be alphanumeric.');
        }

        $slug = Str::slug($name);
        $tableName = Str::plural(Str::snake($name));

        if (Schema::hasTable($tableName)) {
            throw new \Exception("Table {$tableName} already exists.");
        }

        return DB::transaction(function () use ($name, $slug, $tableName, $fields) {
            $contentType = ContentType::create([
                'name' => $name,
                'slug' => $slug,
                'has_ownership' => false, // Default to false strictly as per plan, user can update later if needed
                'is_public' => false,
            ]);

            foreach ($fields as $fieldData) {
                $contentType->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'settings' => $fieldData['settings'] ?? [],
                ]);
            }

            Schema::create($tableName, function (Blueprint $table) use ($fields) {
                $table->id();

                foreach ($fields as $fieldData) {
                    $this->addColumn($table, $fieldData);
                }

                $table->timestamps();
                $table->softDeletes();
            });

            return $contentType;
        });
    }

    public function updateType(string $slug, array $newFields): void
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();
        // Recalculate table name from original name or just use slug convention?
        // Since we store name in DB, we can use it.
        $tableName = Str::plural(Str::snake($contentType->name));

        DB::transaction(function () use ($contentType, $tableName, $newFields) {
            foreach ($newFields as $fieldData) {
                $contentType->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'settings' => $fieldData['settings'] ?? [],
                ]);
            }

            Schema::table($tableName, function (Blueprint $table) use ($newFields) {
                foreach ($newFields as $fieldData) {
                    $this->addColumn($table, $fieldData);
                }
            });
        });
    }

    protected function addColumn(Blueprint $table, array $fieldData): void
    {
        $name = $fieldData['name'];
        $type = $fieldData['type'];

        match ($type) {
            'text' => $table->string($name),
            'longtext' => $table->text($name),
            'integer' => $table->integer($name),
            'boolean' => $table->boolean($name),
            'datetime' => $table->dateTime($name),
            default => throw new \InvalidArgumentException("Unsupported field type: {$type}"),
        };
    }
}
