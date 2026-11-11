<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('role_name', Role::ROLE_ADMIN)->first();
        $doctorRole = Role::where('role_name', Role::ROLE_DOCTOR)->first();
        $receptionistRole = Role::where('role_name', Role::ROLE_RECEPTIONIST)->first();
        $patientRole = Role::where('role_name', Role::ROLE_PATIENT)->first();

        $clinic = Clinic::first();
        $password = Hash::make('password');

        // 1. Quản trị viên (Admin)
        User::firstOrCreate(
            ['email' => 'admin@smartclinic.vn'],
            [
                'role_id' => $adminRole?->role_id,
                'name' => 'Quản trị viên Hệ thống',
                'password' => $password,
                'cccd' => '038090000001',
                'phone' => '0987654321',
                'dob' => '1990-01-01',
            ]
        );

        // 2. Lễ tân (Receptionist)
        User::firstOrCreate(
            ['email' => 'receptionist@smartclinic.vn'],
            [
                'role_id' => $receptionistRole?->role_id,
                'name' => 'Lễ tân Smart Clinic',
                'password' => $password,
                'cccd' => '038090000002',
                'phone' => '0987654322',
                'dob' => '1996-06-15',
            ]
        );

        // 3. Danh sách 5 Bác sĩ (theo SC06)
        $doctorData = [
            [
                'email' => 'bs.nguyenvana@smartclinic.vn',
                'name' => 'BS. Nguyễn Văn A',
                'specialty' => 'Nội tổng quát',
                'degree' => 'Bác sĩ Chuyên khoa II',
                'experience_years' => 15,
                'bio' => 'Trưởng khoa Nội tổng quát, có nhiều năm kinh nghiệm điều trị các bệnh tiêu hóa và chuyển hóa.',
            ],
            [
                'email' => 'bs.tranthib@smartclinic.vn',
                'name' => 'BS. Trần Thị B',
                'specialty' => 'Nội tổng quát',
                'degree' => 'Thạc sĩ Bác sĩ',
                'experience_years' => 8,
                'bio' => 'Bác sĩ khoa Nội tổng quát, tận tâm và giàu kinh nghiệm điều trị bệnh nhân ngoại trú.',
            ],
            [
                'email' => 'bs.levanc@smartclinic.vn',
                'name' => 'BS. Lê Văn C',
                'specialty' => 'Tai Mũi Họng',
                'degree' => 'Bác sĩ Chuyên khoa I',
                'experience_years' => 10,
                'bio' => 'Chuyên gia Tai Mũi Họng, chuyên xử lý các bệnh lý đường hô hấp trên và dị ứng mũi xoang.',
            ],
            [
                'email' => 'bs.phamthid@smartclinic.vn',
                'name' => 'BS. Phạm Thị D',
                'specialty' => 'Da liễu',
                'degree' => 'Thạc sĩ Da liễu',
                'experience_years' => 7,
                'bio' => 'Bác sĩ chuyên khoa Da liễu, chuyên trị mẩn ngứa, phát ban và dị ứng cơ địa.',
            ],
            [
                'email' => 'bs.hoangvane@smartclinic.vn',
                'name' => 'BS. Hoàng Văn E',
                'specialty' => 'Cơ xương khớp',
                'degree' => 'Bác sĩ Chuyên khoa I',
                'experience_years' => 12,
                'bio' => 'Chuyên gia Cơ xương khớp, chẩn đoán và điều trị thoái hóa khớp, thoát vị đĩa đệm.',
            ],
        ];

        foreach ($doctorData as $idx => $doc) {
            $specialty = Specialty::where('specialty_name', $doc['specialty'])->first();

            $user = User::firstOrCreate(
                ['email' => $doc['email']],
                [
                    'role_id' => $doctorRole?->role_id,
                    'name' => $doc['name'],
                    'password' => $password,
                    'cccd' => '03809000010'.($idx + 1),
                    'phone' => '098111222'.($idx + 1),
                    'dob' => '1985-0'.($idx + 1).'-10',
                ]
            );

            Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialty_id' => $specialty?->specialty_id,
                    'clinic_id' => $clinic?->clinic_id,
                    'degree' => $doc['degree'],
                    'experience_years' => $doc['experience_years'],
                    'bio' => $doc['bio'],
                ]
            );
        }

        // 4. Bệnh nhân mẫu 1 (Đã đủ CCCD, đủ điều kiện đặt lịch)
        User::firstOrCreate(
            ['email' => 'patient1@smartclinic.vn'],
            [
                'role_id' => $patientRole?->role_id,
                'name' => 'Nguyễn Văn Bệnh Nhân 1',
                'password' => $password,
                'cccd' => '038090001234',
                'phone' => '0912345678',
                'dob' => '1995-05-15',
            ]
        );

        // 5. Bệnh nhân mẫu 2 (Chưa có CCCD, dùng để test chặn đặt lịch LH17)
        User::firstOrCreate(
            ['email' => 'patient2@smartclinic.vn'],
            [
                'role_id' => $patientRole?->role_id,
                'name' => 'Trần Thị Bệnh Nhân 2 (Chưa CCCD)',
                'password' => $password,
                'cccd' => null,
                'phone' => '0912345679',
                'dob' => '1998-08-20',
            ]
        );
    }
}
