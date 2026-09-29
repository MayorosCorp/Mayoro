<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios_b2b';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ruc_empresa',
        'razon_social',
        'email_contacto',
        'telefono_whatsapp',
        'direccion',
        'password',
        'rol',
        'is_premium',
    ];

    public function getNameAttribute(): string
    {
        return $this->razon_social ?? '';
    }

    public function getEmailAttribute(): string
    {
        return $this->email_contacto ?? '';
    }

    /**
     * Catálogo mayorista publicado por el comercio distribuidor.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Product::class, 'distribuidor_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
