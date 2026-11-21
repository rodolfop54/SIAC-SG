<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'alias', 'password', 'role'])]
#[Hidden(['password', 'remember_token', 'document_number', 'document_key', 'email'])]
class User extends Authenticatable
{
    protected $attributes = ['auth_version' => 0];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'document_number' => 'encrypted',
            'web_registered_at' => 'immutable_datetime',
            'email' => 'encrypted',
            'email_verified_at' => 'immutable_datetime',
            'password_set_at' => 'immutable_datetime',
            'auth_version' => 'integer',
        ];
    }

    public function maskedDocument(): ?string
    {
        return $this->document_number ? $this->document_type.' · ••••'.substr($this->document_number, -4) : null;
    }
}
