<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            [
                'specialty_name' => 'Nội tổng quát',
                'description' => 'Khám các bệnh lý nội khoa chung, tiêu hóa, hô hấp cơ bản',
            ],
            [
                'specialty_name' => 'Tai Mũi Họng',
                'description' => 'Khám các bệnh lý vùng đầu cổ, hô hấp trên',
            ],
            [
                'specialty_name' => 'Da liễu',
                'description' => 'Khám các bệnh lý về da, mẩn ngứa, dị ứng',
            ],
            [
                'specialty_name' => 'Cơ xương khớp',
                'description' => 'Khám đau nhức mỏi cơ, xương, khớp',
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::firstOrCreate(
                ['specialty_name' => $specialty['specialty_name']],
                ['description' => $specialty['description']]
            );
        }
    }
}
