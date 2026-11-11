<?php

namespace Database\Seeders;

use App\Models\Specialty;
use App\Models\Symptom;
use Illuminate\Database\Seeder;

class SymptomSeeder extends Seeder
{
    public function run(): void
    {
        $noiTongQuat = Specialty::where('specialty_name', 'Nội tổng quát')->first();
        $taiMuiHong = Specialty::where('specialty_name', 'Tai Mũi Họng')->first();
        $daLieu = Specialty::where('specialty_name', 'Da liễu')->first();
        $coXuongKhop = Specialty::where('specialty_name', 'Cơ xương khớp')->first();

        $symptoms = [
            // Nội tổng quát
            ['code' => 'SYM_DAU_BUNG', 'name' => 'Đau bụng', 'specialty_id' => $noiTongQuat?->specialty_id],
            ['code' => 'SYM_BUON_NON', 'name' => 'Buồn nôn', 'specialty_id' => $noiTongQuat?->specialty_id],
            ['code' => 'SYM_TIEU_CHAY', 'name' => 'Tiêu chảy', 'specialty_id' => $noiTongQuat?->specialty_id],
            ['code' => 'SYM_SOT_CAO', 'name' => 'Sốt cao', 'specialty_id' => $noiTongQuat?->specialty_id],
            ['code' => 'SYM_MET_MOI', 'name' => 'Mệt mỏi', 'specialty_id' => $noiTongQuat?->specialty_id],
            ['code' => 'SYM_TUC_NGUC', 'name' => 'Tức ngực', 'specialty_id' => $noiTongQuat?->specialty_id],

            // Tai Mũi Họng
            ['code' => 'SYM_DAU_HONG', 'name' => 'Đau họng', 'specialty_id' => $taiMuiHong?->specialty_id],
            ['code' => 'SYM_HO_DOM', 'name' => 'Ho có đờm', 'specialty_id' => $taiMuiHong?->specialty_id],
            ['code' => 'SYM_SO_MUI', 'name' => 'Sổ mũi', 'specialty_id' => $taiMuiHong?->specialty_id],
            ['code' => 'SYM_NGHET_MUI', 'name' => 'Nghẹt mũi', 'specialty_id' => $taiMuiHong?->specialty_id],
            ['code' => 'SYM_U_TAI', 'name' => 'Ù tai', 'specialty_id' => $taiMuiHong?->specialty_id],

            // Da liễu
            ['code' => 'SYM_MAN_DO', 'name' => 'Nổi mẩn đỏ', 'specialty_id' => $daLieu?->specialty_id],
            ['code' => 'SYM_NGUA', 'name' => 'Ngứa ngáy', 'specialty_id' => $daLieu?->specialty_id],
            ['code' => 'SYM_PHAT_BAN', 'name' => 'Phát ban', 'specialty_id' => $daLieu?->specialty_id],
            ['code' => 'SYM_MUN_NUOC', 'name' => 'Mụn nước', 'specialty_id' => $daLieu?->specialty_id],

            // Cơ xương khớp
            ['code' => 'SYM_DAU_LUNG', 'name' => 'Đau lưng', 'specialty_id' => $coXuongKhop?->specialty_id],
            ['code' => 'SYM_MOI_KHOP_GOI', 'name' => 'Nhức mỏi khớp gối', 'specialty_id' => $coXuongKhop?->specialty_id],
            ['code' => 'SYM_SUNG_KHOP', 'name' => 'Sưng khớp', 'specialty_id' => $coXuongKhop?->specialty_id],
            ['code' => 'SYM_TE_BI_CHAN_TAY', 'name' => 'Tê bì chân tay', 'specialty_id' => $coXuongKhop?->specialty_id],
        ];

        foreach ($symptoms as $item) {
            Symptom::firstOrCreate(
                ['symptom_code' => $item['code']],
                [
                    'symptom_name' => $item['name'],
                    'specialty_id' => $item['specialty_id'],
                    'description' => 'Triệu chứng '.$item['name'],
                ]
            );
        }
    }
}
