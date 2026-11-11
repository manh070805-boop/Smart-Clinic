<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'cccd',
        'phone',
        'dob',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'user_id', 'id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'id');
    }

    public function isAdmin(): bool
    {
        return $this->role?->role_name === Role::ROLE_ADMIN;
    }

    public function isDoctor(): bool
    {
        return $this->role?->role_name === Role::ROLE_DOCTOR;
    }

    public function isReceptionist(): bool
    {
        return $this->role?->role_name === Role::ROLE_RECEPTIONIST;
    }

    public function isPatient(): bool
    {
        return $this->role?->role_name === Role::ROLE_PATIENT;
    }

    /**
     * Kiểm tra hồ sơ có đủ điều kiện đặt lịch theo quy tắc SC03:
     * Cần đủ họ tên, ngày sinh, số điện thoại và CCCD.
     */
    public function hasCompleteBookingProfile(): bool
    {
        return !empty($this->name) &&
               !empty($this->dob) &&
               !empty($this->phone) &&
               !empty($this->cccd);
    }
}
