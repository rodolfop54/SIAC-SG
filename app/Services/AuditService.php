<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(User $actor, Model $entity, string $operation, ?array $before, array $after, string $channel = 'web'): AuditEvent
    {
        return AuditEvent::create(['actor_id' => $actor->id, 'entity' => $entity->getTable(), 'entity_id' => $entity->id, 'operation' => $operation, 'before' => $before, 'after' => $after, 'channel' => $channel]);
    }
}
