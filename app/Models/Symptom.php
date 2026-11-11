<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Symptom extends Model
{
    use HasFactory;

    protected $table = 'symptoms';

    protected $primaryKey = 'symptom_id';

    protected $fillable = [
        'symptom_code',
        'symptom_name',
        'specialty_id',
        'description',
    ];

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id', 'specialty_id');
    }
}
