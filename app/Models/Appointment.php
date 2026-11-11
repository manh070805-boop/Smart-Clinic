<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $primaryKey = 'appointment_id';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'appointment_code',
        'patient_id',
        'doctor_id',
        'schedule_id',
        'appointment_date',
        'start_time',
        'queue_number',
        'status',
        'symptom_description',
        'ai_extracted_data',
        'cancel_reason',
        'is_affected_by_leave',
        'rescheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'is_affected_by_leave' => 'boolean',
            'rescheduled_at' => 'datetime',
            'queue_number' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id', 'id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }

    /**
     * Thời điểm bắt đầu khám (Carbon datetime)
     */
    public function getStartDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->appointment_date->format('Y-m-d').' '.$this->start_time);
    }

    /**
     * Kiểm tra điều kiện Hủy hoặc Đổi lịch (SC03, LH07, LH08):
     * - Trạng thái phải là Pending (chưa check-in, chưa khám, chưa hủy).
     * - Thời điểm hiện tại phải cách giờ khám ít nhất 2 giờ (>= 120 phút).
     */
    public function canBeCancelledOrRescheduled(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $now = Carbon::now();
        $startDateTime = $this->start_date_time;

        // Cho phép nếu còn ít nhất 2 giờ (120 phút)
        return $now->diffInMinutes($startDateTime, false) >= 120;
    }

    /**
     * Kiểm tra điều kiện đánh dấu Vắng mặt (No-Show theo SC04):
     * - Trạng thái là Pending.
     * - Thời điểm hiện tại đã quá giờ bắt đầu từ 15 phút trở lên.
     */
    public function isNoShowEligible(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $now = Carbon::now();
        $startDateTime = $this->start_date_time;

        return $now->diffInMinutes($startDateTime, false) <= -15;
    }
}
