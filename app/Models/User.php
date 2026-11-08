<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_PERSONA_NATURAL = 'persona_natural';

    public const ROL_EMPRESARIO = 'empresario';

    protected $table = 'usuarios_b2b';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ruc_empresa',
        'razon_social',
        'slug',
        'rubro',
        'descripcion',
        'color_primario',
        'horario_atencion',
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

    /**
     * RN-02: solo el Empresario validado puede publicar y tener micrositio.
     */
    public function esEmpresario(): bool
    {
        return $this->rol === self::ROL_EMPRESARIO;
    }

    /**
     * RN-01: el rol inicial, que puede navegar y cotizar sin RUC.
     */
    public function esPersonaNatural(): bool
    {
        return $this->rol === self::ROL_PERSONA_NATURAL;
    }

    /**
     * Etiqueta visible del rol, sin exponer el valor tecnico en la interfaz.
     */
    public function getRolEtiquetaAttribute(): string
    {
        return $this->esEmpresario() ? 'Empresario' : 'Persona natural';
    }

    public function getEmailAttribute(): string
    {
        return $this->email_contacto ?? '';
    }

    /**
     * RN-03: direccion publica del micrositio. Devuelve null mientras el
     * Empresario no tenga slug, para que la interfaz no enlace a una pagina
     * que respondería 404.
     */
    public function getMicrositioUrlAttribute(): ?string
    {
        return $this->esEmpresario() && filled($this->slug)
            ? route('microsite.show', $this->slug)
            : null;
    }

    /**
     * RN-03: color de texto que mantiene legible el hero del micrositio sobre
     * cualquier color primario. Usa la luminancia percibida (YIQ) para elegir
     * entre texto claro y oscuro.
     */
    public function getColorPrimarioTextoAttribute(): string
    {
        $hex = ltrim((string) ($this->color_primario ?: '#23497b'), '#');

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#ffffff';
        }

        $rojo = hexdec(substr($hex, 0, 2));
        $verde = hexdec(substr($hex, 2, 2));
        $azul = hexdec(substr($hex, 4, 2));

        $luminancia = ($rojo * 299 + $verde * 587 + $azul * 114) / 1000;

        return $luminancia >= 150 ? '#0f172a' : '#ffffff';
    }

    /**
     * Genera un slug unico para el micrositio a partir de la razon social.
     * Se usa al convertir a Empresario y cuando la edicion encuentra una
     * cuenta heredada sin slug.
     */
    public static function generarSlugMicrositio(string $razonSocial, ?int $ignorarId = null): string
    {
        $base = Str::slug($razonSocial);
        $base = rtrim(mb_substr($base !== '' ? $base : 'empresa', 0, 70), '-');
        $base = $base !== '' ? $base : 'empresa';

        $slug = $base;
        $contador = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignorarId !== null, fn ($query) => $query->whereKeyNot($ignorarId))
            ->exists()
        ) {
            $slug = $base.'-'.$contador;
            $contador++;
        }

        return $slug;
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
