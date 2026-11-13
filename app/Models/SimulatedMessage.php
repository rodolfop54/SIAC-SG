<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulatedMessage extends Model
{
    protected $fillable = ['user_id', 'appointment_id', 'sender', 'type', 'content', 'options', 'event_key', 'read_at'];

    protected function casts(): array
    {
        return ['options' => 'array', 'read_at' => 'datetime'];
    }
}
