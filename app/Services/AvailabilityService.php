<?php

namespace App\Services;

use App\Models\Slot;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityService
{
    public function query(?int $serviceId = null): Builder
    {
        return Slot::available()->when($serviceId, fn (Builder $q) => $q->where('service_id', $serviceId))->orderBy('starts_at');
    }
}
