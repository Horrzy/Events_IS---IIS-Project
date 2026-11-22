<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Mass-assignable attributes
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
    ];

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
        'auth_version' => 1,
    ];

    // Attributes hidden from serialization
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Attribute type casts
    protected $casts = [
        'is_active' => 'boolean',
        'auth_version' => 'integer',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['moderator', 'admin'], true);
    }

    public function managedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'manager_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function proposedVenues(): HasMany
    {
        return $this->hasMany(Venue::class, 'proposed_by');
    }

    public function proposedCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'proposed_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
