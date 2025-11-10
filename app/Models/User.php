<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'client_id',
        'role_id',
        'email',
        'password',
        'is_active',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(VacancySubmission::class, 'submitted_by_user_id');
    }

    // FIX: Tambahkan method untuk Filament
    public function getFilamentName(): string
    {
        return $this->email; // atau return $this->client->contact_person jika ada relasi
    }

    // Opsional: Jika ingin menggunakan name field
    public function getNameAttribute(): string
    {
        if ($this->client) {
            return $this->client->contact_person;
        }
        return 'Admin User';
    }
}