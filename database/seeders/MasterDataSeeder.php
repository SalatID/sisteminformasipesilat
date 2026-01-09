<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Ts;
use App\Models\Coach;
use App\Models\Unit;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Insert or update TS
        $tsData = [
            ['name' => 'Pratama Taruna', 'alias' => 'PT', 'multiplier' => 0.0],
            ['name' => 'Pratama Madya', 'alias' => 'PM', 'multiplier' => 0.0],
            ['name' => 'Pratama Utama', 'alias' => 'PU', 'multiplier' => 0.0],
            ['name' => 'Satria Taruna', 'alias' => 'ST', 'multiplier' => 1.0],
            ['name' => 'Satria Madya', 'alias' => 'SM', 'multiplier' => 2.0],
            ['name' => 'Satria Utama', 'alias' => 'SU', 'multiplier' => 3.0],
            ['name' => 'Pendekar Muda Taruna', 'alias' => 'PMT', 'multiplier' => 4.0],
            ['name' => 'Pendekar Muda Madya', 'alias' => 'PMM', 'multiplier' => 4.0],
            ['name' => 'Pendekar Muda Utama', 'alias' => 'PMU', 'multiplier' => 4.0],
            ['name' => 'Dewan Guru', 'alias' => 'DG', 'multiplier' => 4.0],
        ];

        foreach ($tsData as $data) {
            Ts::updateOrCreate(
                ['name' => $data['name']],
                [
                    'multiplier' => $data['multiplier'],
                    'alias' => $data['alias'],
                    'created_by' => Str::uuid(),
                    'updated_by' => Str::uuid(),
                ]
            );
        }

        // 2. Insert or update Coaches
        $st = Ts::where('name', 'Satria Taruna')->first();
        $sm = Ts::where('name', 'Satria Madya')->first();
        $su = Ts::where('name', 'Satria Utama')->first();
        $pmt = Ts::where('name', 'Pendekar Muda Taruna')->first();
        $pmm = Ts::where('name', 'Pendekar Muda Madya')->first();

        $coachData = [
            ['name' => 'Indra Madya Permana', 'ts_id' => $pmm->id],
            ['name' => 'Agam Ikhwan', 'ts_id' => $pmm->id],
            ['name' => 'Agus Sutrisno', 'ts_id' => $pmm->id],
            ['name' => 'Yonpi Apriadi', 'ts_id' => $pmm->id],
            ['name' => 'Ashabul Kahfi', 'ts_id' => $pmt->id],
            ['name' => 'Apriyanto', 'ts_id' => $pmt->id],
            ['name' => 'Eko Purwanto', 'ts_id' => $pmt->id],
            ['name' => 'Juminta Ibrahim', 'ts_id' => $pmt->id],
            ['name' => 'Ramli Alamsyah', 'ts_id' => $pmt->id],
            ['name' => 'Nining Eka Wati', 'ts_id' => $pmt->id],
            ['name' => 'Zaenal Arifin', 'ts_id' => $pmt->id],
            ['name' => 'Catur Wulandari', 'ts_id' => $pmt->id],
            ['name' => 'M. Arief P', 'ts_id' => $su->id],
            ['name' => 'Mursalat Asyidiq', 'ts_id' => $su->id],
            ['name' => 'Ahmad Ramdani', 'ts_id' => $su->id],
            ['name' => 'Hari Darmawan', 'ts_id' => $su->id],
            ['name' => 'M. Ahyar Ilyas', 'ts_id' => $su->id],
            ['name' => 'Revika Zulviani', 'ts_id' => $su->id],
            ['name' => 'Ummul Ubaedillah', 'ts_id' => $su->id],
            ['name' => 'Wahyu', 'ts_id' => $su->id],
            ['name' => 'Indah Setya', 'ts_id' => $su->id],
            ['name' => 'Ibnu Fauzan', 'ts_id' => $sm->id],
            ['name' => 'Regita Fitra', 'ts_id' => $st->id],
            ['name' => 'Asa Luth', 'ts_id' => $st->id],
            ['name' => 'Aster Wardana', 'ts_id' => $st->id],
            ['name' => 'Aqila Khairunnisa', 'ts_id' => $st->id],
            ['name' => 'Hasna Salsabila', 'ts_id' => $st->id],
            ['name' => 'Yohanes Dimas', 'ts_id' => $st->id],
            ['name' => 'Najilah Azka', 'ts_id' => $st->id],
            ['name' => 'Nurul Octaviyani Safitri', 'ts_id' => $st->id],
            ['name' => 'Sunan Shaquille Alvarick', 'ts_id' => $st->id],
            ['name' => 'Jason Van Persie', 'ts_id' => $st->id],
            ['name' => 'Jane Happy Karina', 'ts_id' => $st->id],
            ['name' => 'Rafli Rizky Prasetio', 'ts_id' => $st->id],
            ['name' => 'Muhammad Raihan', 'ts_id' => $st->id],
            ['name' => 'Sultan Alif Alhafidz', 'ts_id' => $st->id],
            ['name' => 'Vero Rayyan Nawaqinzye', 'ts_id' => $st->id],
            ['name' => 'Vanessa Juavanka', 'ts_id' => $st->id],
            ['name' => 'Nailah Althafunnisa Gunawan', 'ts_id' => $st->id],
            ['name' => 'Abdul Rojak', 'ts_id' => $st->id],
            ['name' => 'Milan Fajar Alamsyah', 'ts_id' => $st->id],
            ['name' => 'Elidhya Frasiska Ramadhani', 'ts_id' => $st->id],
            ['name' => 'Muhammad Almer Farras Alramvi', 'ts_id' => $st->id],
            ['name' => 'Nuraini', 'ts_id' => $st->id],
        ];

        foreach ($coachData as $data) {
            Coach::updateOrCreate(
                ['name' => $data['name'], 'ts_id' => $data['ts_id']],
                [
                    'coach_exam_date' => now(),
                    'coach_exam_at' => 'PPS SMI KOWIL JAKBAR',
                    'created_by' => Str::uuid(),
                    'updated_by' => Str::uuid(),
                ]
            );
        }

        // 3. Insert or update Units
        $unitData = [
            [
                'name' => 'SMP Bangun Nusantara',
                'pj_name' => 'Eko Purwanto',
                'school_pic_name' => 'Sahrul Sari',
                'school_pic_occupation' => 'Wakil Kepala Sekolah Guru',
                'school_pic_number' => '081806675258',
                'training_day' => 'Saturday',
                'training_hours_start' => '15:30:00',
                'training_hours_end' => '17:30:00',
            ],
            [
                'name' => 'SMK Bangun Nusantara',
                'pj_name' => 'Eko Purwanto',
                'school_pic_name' => 'Zulyansyah',
                'school_pic_occupation' => 'Guru',
                'school_pic_number' => '0895332364226',
                'training_day' => 'Sunday',
                'training_hours_start' => '15:30:00',
                'training_hours_end' => '17:30:00',
            ],
            [
                'name' => 'SMK TI YPML',
                'pj_name' => 'Mursalat Ayidiq',
                'school_pic_name' => 'Syuhada',
                'school_pic_occupation' => 'Pembina Ekskul',
                'school_pic_number' => '89531321647',
                'training_day' => 'Thursday',
                'training_hours_start' => '15:30:00',
                'training_hours_end' => '17:00:00',
            ],
            [
                'name' => 'SMP/SMA/SMK Cengkareng 1',
                'pj_name' => 'Catur Wulandari',
                'school_pic_name' => 'Tri',
                'school_pic_occupation' => 'Pembina OSIS',
                'school_pic_number' => '087888743331',
                'training_day' => 'Tuesday,Friday',
                'training_hours_start' => '15:30:00',
                'training_hours_end' => '17:30:00',
            ],
            [
                'name' => 'SMP/SMA/SMK Cengkareng 2',
                'pj_name' => 'M. Ahyar Ilyas',
                'school_pic_name' => 'Joko',
                'school_pic_occupation' => 'Bendahara',
                'school_pic_number' => '085213239930',
                'training_day' => 'Monday,Wednesday',
                'training_hours_start' => '15:00:00',
                'training_hours_end' => '17:00:00',
            ],
            [
                'name' => 'SDN Kalideres 05',
                'pj_name' => 'Revika Zulviani',
                'school_pic_name' => 'Fikri',
                'school_pic_occupation' => 'Guru',
                'school_pic_number' => '0895365139029',
                'training_day' => 'Tuesday',
                'training_hours_start' => '13:30:00',
                'training_hours_end' => '15:00:00',
            ],
            [
                'name' => 'SDN Kalideres 02 PT',
                'pj_name' => 'Revika Zulviani',
                'school_pic_name' => 'Lina',
                'school_pic_occupation' => 'Guru',
                'school_pic_number' => '081311766112',
                'training_day' => 'Monday',
                'training_hours_start' => '16:00:00',
                'training_hours_end' => '17:00:00',
            ],
            [
                'name' => 'SMP Negeri 225 Jakarta',
                'pj_name' => 'M. Arief P',
                'school_pic_name' => 'Dede Sumarna,M.Pd',
                'school_pic_occupation' => 'Pembina Ekskul',
                'school_pic_number' => '082216526926',
                'training_day' => 'Wednesday',
                'training_hours_start' => '14:30:00',
                'training_hours_end' => '16:30:00',
            ],
            [
                'name' => 'SMP IT AL MAKA',
                'pj_name' => 'M. Arief P',
                'school_pic_name' => 'Athia',
                'school_pic_occupation' => 'Wakil Kesiswaan',
                'school_pic_number' => '087788504767',
                'training_day' => 'Monday',
                'training_hours_start' => '14:10:00',
                'training_hours_end' => '15:30:00',
            ],
            [
                'name' => 'SMA IT AL MAKA',
                'pj_name' => 'M. Arief P',
                'school_pic_name' => 'Firdaus Oktawijaya',
                'school_pic_occupation' => 'Wakil Kesiswaan',
                'school_pic_number' => '085930377998',
                'training_day' => 'Thursday',
                'training_hours_start' => '14:30:00',
                'training_hours_end' => '16:00:00',
            ],
            [
                'name' => 'SMP KEBUDAYAAN',
                'pj_name' => 'M. Arief P',
                'school_pic_name' => 'Sebrina Rizkita Samsuri, S.Pd',
                'school_pic_occupation' => 'Pembina Ekskul',
                'school_pic_number' => '089509055957',
                'training_day' => 'Saturday',
                'training_hours_start' => '07:00:00',
                'training_hours_end' => '09:00:00',
            ],
            [
                'name' => 'RPTRA KALIDERES',
                'pj_name' => 'Juminta Ibrahim',
                'school_pic_name' => 'Saefullah',
                'school_pic_occupation' => 'Pengelola RPTRA Kalideres / PIC Silat',
                'school_pic_number' => '085157867735',
                'training_day' => 'Saturday',
                'training_hours_start' => '15:30:00',
                'training_hours_end' => '17:00:00',
            ],
             [
                'name' => 'KALIDERES',
                'pj_name' => 'Indra Madya Permana',
                'school_pic_name' => '',
                'school_pic_occupation' => '',
                'school_pic_number' => '',
                'training_day' => 'Sunday',
                'training_hours_start' => '10:00:00',
                'training_hours_end' => '14:00:00',
            ],
            [
                'name' => 'SDN Pegadungan 012 Pagi',
                'pj_name' => 'Hari Darmawan',
                'school_pic_name' => 'Latifah',
                'school_pic_occupation' => 'Guru',
                'school_pic_number' => '082116515725',
                'training_day' => 'Wednesday',
                'training_hours_start' => '14:30:00',
                'training_hours_end' => '15:30:00',
            ],
            [
                'name' => 'SD KARAWACI 08 ',
                'pj_name' => 'Apriyanto',
                'school_pic_name' => 'ENDAH',
                'school_pic_occupation' => 'Pembina Ekskul',
                'school_pic_number' => '081906428115',
                'training_day' => 'Tuesday',
                'training_hours_start' => '15:30:00',
                'training_hours_end' => '17:30:00',
            ],
            [
                'name' => 'SDN Pegadungan 08 Petang',
                'pj_name' => 'Ummul Ubaedillah',
                'school_pic_name' => 'Icih',
                'school_pic_occupation' => 'Bendahara',
                'school_pic_number' => '085817331413',
                'training_day' => 'Saturday',
                'training_hours_start' => '07:00:00',
                'training_hours_end' => '09:00:00',
            ],
            [
                'name' => 'MI NURUL YAKIN',
                'pj_name' => 'Apriyanto',
                'school_pic_name' => 'RIZAL',
                'school_pic_occupation' => 'Kepala Sekolah',
                'school_pic_number' => '085695452575',
                'training_day' => 'Saturday',
                'training_hours_start' => '07:00:00',
                'training_hours_end' => '09:00:00',
            ],
            [
                'name' => 'SDN Pegadungan 01 Pagi',
                'pj_name' => 'Zaenal',
                'school_pic_name' => 'Sample PIC',
                'school_pic_occupation' => 'Teacher',
                'school_pic_number' => '08123456789',
                'training_day' => 'Tuesday',
                'training_hours_start' => '13:00:00',
                'training_hours_end' => '15:00:00',
            ],
            [
                'name' => 'Kuttab At Taqwa',
                'pj_name' => 'Indah Setya Wati',
                'school_pic_name' => 'Ust. Sugandi',
                'school_pic_occupation' => 'Kepala Kuttab',
                'school_pic_number' => '08123456789',
                'training_day' => 'Monday',
                'training_hours_start' => '07:00:00',
                'training_hours_end' => '09:00:00',
            ],
        ];

        foreach ($unitData as $data) {
            $pj = Coach::where('name', $data['pj_name'])->first();
            if ($pj) {
                Unit::updateOrCreate(
                    ['name' => $data['name']],
                    [
                        'training_day' => $data['training_day'],
                        'pj_id' => $pj->id,
                        'training_hours_start' => $data['training_hours_start'],
                        'training_hours_end' => $data['training_hours_end'],
                        'paid_fee_type' => 'pertemuan',
                        'paid_periode' => 'perbulan',
                        'school_pic_name' => $data['school_pic_name'],
                        'school_pic_number' => $data['school_pic_number'],
                        'school_level' => 'High School', // Default, can adjust
                        'school_pic_occupation' => $data['school_pic_occupation'],
                        'joined_date' => now()->subDays(rand(1, 365)),
                        'created_by' => Str::uuid(),
                        'updated_by' => Str::uuid(),
                    ]
                );
            }
        }
    }
}