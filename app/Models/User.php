<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'phone',
        'address',
        'points',
        'membership_level',
        'role',
        'is_active',
        'oauth_provider',
        'oauth_id',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'password' => 'hashed',
        ];
    }

    /**
     * C\u1ed9ng \u0111i\u1ec3m t\u00edch l\u0169y v\u00e0 t\u1ef1 \u0111\u1ed9ng n\u00e2ng h\u1ea1ng th\u00e0nh vi\u00ean.
     *
     * T\u1ef7 l\u1ec7: 1 \u0111i\u1ec3m = 1.000 VN\u0110 (d\u1ef1a tr\u00ean s\u1ed1 ti\u1ec1n th\u1ef1c tr\u1ea3)
     * Ng\u01b0\u1ee1ng h\u1ea1ng:
     *   new     :     0 \u2013   499 \u0111i\u1ec3m
     *   silver  :   500 \u2013 1.999 \u0111i\u1ec3m
     *   gold    : 2.000 \u2013 4.999 \u0111i\u1ec3m
     *   diamond : \u2265 5.000 \u0111i\u1ec3m
     */
    public function awardPoints(int|float $amount): void
    {
        $earned = (int) floor($amount / 1000);
        if ($earned <= 0) return;

        $total = (int) ($this->points ?? 0) + $earned;

        if ($total >= 5000)      $level = 'diamond';
        elseif ($total >= 2000)  $level = 'gold';
        elseif ($total >= 500)   $level = 'silver';
        else                     $level = 'new';

        $this->points           = $total;
        $this->membership_level = $level;
        $this->save();

        \Illuminate\Support\Facades\Log::info(
            "[Points] User #{$this->id} ({$this->name}): +{$earned} \u0111i\u1ec3m \u2192 t\u1ed5ng {$total} \u0111i\u1ec3m | H\u1ea1ng: {$level}"
        );
    }
}
