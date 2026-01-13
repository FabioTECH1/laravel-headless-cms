<?php

namespace App\Observers;

use App\Models\DynamicEntity;
use App\Services\WebhookService;

class DynamicEntityObserver
{
    /**
     * Handle the DynamicEntity "created" event.
     */
    public function created(DynamicEntity $entity): void
    {
        WebhookService::trigger('content.create', [
            'id' => $entity->id,
            'content_type' => $entity->getTable(),
            'data' => $entity->toArray(),
        ]);

        // Trigger publish webhook if published
        if (! empty($entity->published_at)) {
            WebhookService::trigger('content.publish', [
                'id' => $entity->id,
                'content_type' => $entity->getTable(),
                'data' => $entity->toArray(),
            ]);
        }
    }

    /**
     * Handle the DynamicEntity "updated" event.
     */
    public function updated(DynamicEntity $entity): void
    {
        WebhookService::trigger('content.update', [
            'id' => $entity->id,
            'content_type' => $entity->getTable(),
            'data' => $entity->toArray(),
        ]);

        // Trigger publish webhook if just published
        $wasPublished = ! empty($entity->getOriginal('published_at'));
        $isNowPublished = ! empty($entity->published_at);

        if (! $wasPublished && $isNowPublished) {
            WebhookService::trigger('content.publish', [
                'id' => $entity->id,
                'content_type' => $entity->getTable(),
                'data' => $entity->toArray(),
            ]);
        }
    }

    /**
     * Handle the DynamicEntity "deleted" event.
     */
    public function deleted(DynamicEntity $entity): void
    {
        WebhookService::trigger('content.delete', [
            'id' => $entity->id,
            'content_type' => $entity->getTable(),
            'data' => $entity->toArray(),
        ]);
    }
}
