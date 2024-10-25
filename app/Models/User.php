<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'otp',
        'password',
        'gender',
        'birthdate'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, );
    // }

    // public function hasRole($role)
    // {
    //     return $this->roles->contains('name', $role);
    // }
    
    
    // public function hasAnyRole($roles)
    // {
    //     return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    // }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    // Check if the user has a specific role
    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    // Check if the user has any of the roles
    public function hasAnyRole(array $roles)
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

      public function agentServices()
    {
        return $this->hasMany(AgentService::class);
    }


    /**
     * Generate a random OTP and store it for the user.
     *
     * @return int
     */
    public function generateOTP()
    {
        return rand(pow(10, 5), pow(10, 6) - 1);  // Adjust the length if needed
    }
}
