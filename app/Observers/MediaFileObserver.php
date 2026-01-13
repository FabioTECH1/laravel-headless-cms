<?php

namespace App\Observers;

use App\Models\MediaFile;
use App\Services\WebhookService;

class MediaFileObserver
{
    /**
     * Handle the MediaFile "created" event.
     */
    public function created(MediaFile $mediaFile): void
    {
        WebhookService::trigger('media.upload', [
            'id' => $mediaFile->id,
            'filename' => $mediaFile->filename,
            'path' => $mediaFile->path,
            'mime_type' => $mediaFile->mime_type,
            'size' => $mediaFile->size,
        ]);
    }

    /**
     * Handle the MediaFile "deleted" event.
     */
    public function deleted(MediaFile $mediaFile): void
    {
        WebhookService::trigger('media.delete', [
            'id' => $mediaFile->id,
            'filename' => $mediaFile->filename,
            'path' => $mediaFile->path,
        ]);
    }
}
