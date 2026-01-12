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
    public function createType(string $name, array $fields, bool $isPublic = false, bool $hasOwnership = false, bool $isComponent = false, bool $isSingle = false, bool $isLocalized = false): ContentType
    {
        if (! ctype_alnum(str_replace(' ', '', $name))) {
            throw new InvalidArgumentException('Content Type name must be alphanumeric.');
        }

        $slug = Str::slug($name);
        $tableName = Str::plural(Str::snake($name));

        if (! $isComponent && Schema::hasTable($tableName)) {
            throw new Exception("Table {$tableName} already exists.");
        }

        return DB::transaction(function () use ($name, $slug, $tableName, $fields, $isPublic, $hasOwnership, $isComponent, $isSingle, $isLocalized) {
            $contentType = ContentType::create([
                'name' => $name,
                'slug' => $slug,
                'has_ownership' => $hasOwnership,
                'is_public' => $isPublic,
                'is_component' => $isComponent,
                'is_single' => $isSingle,
                'is_localized' => $isLocalized,
            ]);

            foreach ($fields as $fieldData) {
                $contentType->fields()->create([
                    'name' => $fieldData['name'],
                    'type' => $fieldData['type'],
                    'settings' => $fieldData['settings'] ?? [],
                ]);
            }

            if ($isComponent) {
                return $contentType;
            }

            Schema::create($tableName, function (Blueprint $table) use ($fields, $hasOwnership, $isLocalized) {
                $table->ulid('id')->primary();

                // Add user_id foreign key if ownership is enabled
                if ($hasOwnership) {
                    $table->foreignUlid('user_id')->nullable()->constrained()->onDelete('cascade');
                    $table->index('user_id');
                }

                // Add locale column if localized
                if ($isLocalized) {
                    $table->string('locale')->index()->default('en');
                }

                foreach ($fields as $fieldData) {
                    if (($fieldData['type'] === 'relation') && ($fieldData['settings']['multiple'] ?? false)) {
                        continue; // Skip adding column to main table
                    }
                    $this->addColumn($table, $fieldData);
                }

                $table->timestamps();
                $table->softDeletes();
                $table->timestamp('published_at')->nullable();
            });

            // Create Pivot Tables for Many-to-Many
            foreach ($fields as $fieldData) {
                if (($fieldData['type'] === 'relation') && ($fieldData['settings']['multiple'] ?? false)) {
                    $this->ensurePivotTable($slug, $fieldData);
                }
            }

            return $contentType;
        });
    }

    public function updateType(string $slug, array $newFields, bool $isPublic, bool $hasOwnership, bool $isComponent = false, bool $isSingle = false, bool $isLocalized = false): void
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();
        $tableName = Str::plural(Str::snake($contentType->name));

        DB::transaction(function () use ($contentType, $tableName, $newFields, $isPublic, $hasOwnership, $isComponent, $isSingle, $isLocalized) {
            $wasOwned = $contentType->has_ownership;
            $wasLocalized = $contentType->is_localized;

            // Update content type settings
            $contentType->update([
                'is_public' => $isPublic,
                'has_ownership' => $hasOwnership,
                'is_component' => $isComponent,
                'is_single' => $isSingle,
                'is_localized' => $isLocalized,
            ]);

            // Add user_id column if ownership is being enabled
            if ($hasOwnership && ! $wasOwned) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignUlid('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                    $table->index('user_id');
                });
            }

            // Add locale column if localization is being enabled
            if ($isLocalized && ! $wasLocalized) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('locale')->index()->default('en')->after('id');
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
                        if (($fieldData['type'] === 'relation') && ($fieldData['settings']['multiple'] ?? false)) {
                            continue;
                        }
                        $this->addColumn($table, $fieldData);
                    }
                });

                // Handle M2M pivot tables for new fields
                foreach ($newFields as $fieldData) {
                    if (($fieldData['type'] === 'relation') && ($fieldData['settings']['multiple'] ?? false)) {
                        $this->ensurePivotTable($contentType->slug, $fieldData);
                    }
                }
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
            'json', 'component', 'dynamic_zone' => $table->json($name),
            'enum' => $table->string($name),
            'email' => $table->string($name),
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

    protected function ensurePivotTable(string $parentSlug, array $fieldData): void
    {
        $relatedTypeId = $fieldData['settings']['related_content_type_id'];
        $relatedType = ContentType::find($relatedTypeId);

        if (! $relatedType) {
            return;
        }

        // Convention: alphabetical order of singular slugs
        $slugs = [Str::singular($parentSlug), Str::singular($relatedType->slug)];
        sort($slugs);
        $pivotTableName = implode('_', $slugs);

        if (Schema::hasTable($pivotTableName)) {
            return;
        }

        Schema::create($pivotTableName, function (Blueprint $table) use ($slugs) {
            $table->ulid($slugs[0].'_id')->index();
            $table->ulid($slugs[1].'_id')->index();
            $table->timestamps();

            $table->unique([$slugs[0].'_id', $slugs[1].'_id']);
        });
    }
}
