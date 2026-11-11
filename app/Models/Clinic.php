<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use HasFactory;

    protected $table = 'clinics';
    protected $primaryKey = 'clinic_id';

    protected $fillable = [
        'clinic_name',
        'address',
        'phone',
        'description',
    ];

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class, 'clinic_id', 'clinic_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'clinic_id', 'clinic_id');
    }
}
