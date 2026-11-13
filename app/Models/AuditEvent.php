<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    protected $fillable = ['actor_id', 'entity', 'entity_id', 'operation', 'before', 'after', 'channel'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
