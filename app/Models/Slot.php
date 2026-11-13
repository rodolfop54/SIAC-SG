<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'starts_at', 'ends_at', 'enabled'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'enabled' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeAvailable($q)
    {
        return $q->where('enabled', true)->where('starts_at', '>', now())->whereHas('service', fn ($s) => $s->where('enabled', true))->whereDoesntHave('appointments', fn ($a) => $a->where('status', 'CONFIRMADA'));
    }
}
