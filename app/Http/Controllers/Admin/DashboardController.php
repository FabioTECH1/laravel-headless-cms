<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $contentStats = ContentType::all()->map(function ($type) {
            $tableName = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($type->name));
            $count = 0;
            if (Schema::hasTable($tableName)) {
                $count = DB::table($tableName)->count();
            }

            return [
                'name' => $type->name,
                'slug' => $type->slug,
                'count' => $count,
                'url' => route('admin.content.index', $type->slug),
            ];
        });

        // Calculate media size in MB or GB
        $mediaSize = MediaFile::sum('size'); // Assuming size is in bytes
        $mediaSizeFormatted = $this->formatSize($mediaSize);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'schemas' => ContentType::count(),
                'media_count' => MediaFile::count(),
                'media_size' => $mediaSizeFormatted,
                'content_breakdown' => $contentStats,
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
            ],
        ]);
    }

    private function formatSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } elseif ($bytes > 1) {
            return $bytes.' bytes';
        } elseif ($bytes == 1) {
            return $bytes.' byte';
        } else {
            return '0 bytes';
        }
    }
}
