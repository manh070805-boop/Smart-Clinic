<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::first();
        $doctors = Doctor::all();

        $timeSlots = [
            ['start' => '08:00:00', 'end' => '08:30:00'],
            ['start' => '08:30:00', 'end' => '09:00:00'],
            ['start' => '09:00:00', 'end' => '09:30:00'],
            ['start' => '09:30:00', 'end' => '10:00:00'],
            ['start' => '10:00:00', 'end' => '10:30:00'],
            ['start' => '10:30:00', 'end' => '11:00:00'],
            ['start' => '13:30:00', 'end' => '14:00:00'],
            ['start' => '14:00:00', 'end' => '14:30:00'],
            ['start' => '14:30:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '15:30:00'],
            ['start' => '15:30:00', 'end' => '16:00:00'],
            ['start' => '16:00:00', 'end' => '16:30:00'],
        ];

        // Tạo lịch trong 7 ngày tới (bắt đầu từ ngày mai)
        $startDate = Carbon::tomorrow();

        foreach ($doctors as $doctor) {
            for ($day = 0; $day < 7; $day++) {
                $workDate = $startDate->copy()->addDays($day)->format('Y-m-d');

                foreach ($timeSlots as $slot) {
                    // Tạo 1 ca nghỉ đột xuất thử nghiệm cho BS A vào ngày thứ 3 buổi chiều để test LH16
                    $isDayOff = ($doctor->experience_years === 15 && $day === 2 && $slot['start'] >= '14:00:00');

                    Schedule::firstOrCreate(
                        [
                            'doctor_id' => $doctor->doctor_id,
                            'clinic_id' => $clinic->clinic_id,
                            'work_date' => $workDate,
                            'start_time' => $slot['start'],
                        ],
                        [
                            'end_time' => $slot['end'],
                            'max_patients' => 1,
                            'status' => $isDayOff ? Schedule::STATUS_CANCELLED : Schedule::STATUS_AVAILABLE,
                            'is_day_off' => $isDayOff,
                        ]
                    );
                }
            }
        }
    }
}

