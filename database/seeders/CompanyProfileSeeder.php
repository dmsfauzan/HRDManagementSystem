<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        CompanyProfile::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'PT Contoh Sejahtera',
                'tagline' => 'Membangun tim hebat untuk masa depan',
                'about' => 'Kami adalah perusahaan yang berfokus pada inovasi dan pengembangan talenta. Bergabunglah dengan tim kami yang dinamis dan tumbuh bersama.',
                'website' => 'https://contoh.co.id',
                'email' => 'karier@contoh.co.id',
                'phone' => '021-1234567',
                'address' => 'Jl. Contoh No. 1',
                'city' => 'Jakarta',
                'is_active' => true,
            ]
        );
    }
}
