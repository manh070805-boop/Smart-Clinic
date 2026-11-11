<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    public const ROLE_PATIENT = 'Patient';
    public const ROLE_RECEPTIONIST = 'Receptionist';
    public const ROLE_DOCTOR = 'Doctor';
    public const ROLE_ADMIN = 'Admin';

    protected $fillable = [
        'role_name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
}
