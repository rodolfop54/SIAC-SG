<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'duration_minutes', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }
}
