<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedules';

    protected $primaryKey = 'schedule_id';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_FULL = 'full';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'work_date',
        'start_time',
        'end_time',
        'max_patients',
        'status',
        'is_day_off',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'is_day_off' => 'boolean',
            'max_patients' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id', 'clinic_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'schedule_id', 'schedule_id');
    }

    /**
     * Kiểm tra khung giờ có thể đặt được hay không (LH03, LH04):
     * - Chưa bị ngày nghỉ
     * - Trạng thái available
     * - Thời điểm bắt đầu chưa trôi qua
     */
    public function isBookable(): bool
    {
        if ($this->is_day_off || $this->status !== self::STATUS_AVAILABLE) {
            return false;
        }

        $startDateTime = Carbon::parse($this->work_date->format('Y-m-d').' '.$this->start_time);

        return $startDateTime->isFuture();
    }

    public function getActiveAppointmentsCount(): int
    {
        return $this->appointments()
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CHECKED_IN,
                Appointment::STATUS_IN_PROGRESS,
            ])
            ->count();
    }
}
