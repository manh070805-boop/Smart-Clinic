<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialty extends Model
{
    use HasFactory;

    protected $table = 'specialties';

    protected $primaryKey = 'specialty_id';

    protected $fillable = [
        'specialty_name',
        'description',
    ];

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class, 'specialty_id', 'specialty_id');
    }

    public function symptoms(): HasMany
    {
        return $this->hasMany(Symptom::class, 'specialty_id', 'specialty_id');
    }
}
