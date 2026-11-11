<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        Clinic::firstOrCreate(
            ['clinic_name' => 'Smart Clinic Vinh'],
            [
                'address' => 'Số 126 Nguyễn Thị Minh Khai, Phường Hưng Bình, TP. Vinh, Nghệ An',
                'phone' => '02383123456',
                'description' => 'Cơ sở khám bệnh thông minh đa khoa Smart Clinic - Cơ sở Vinh.',
            ]
        );
    }
}
