<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class SdbUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data yang ada untuk menghindari duplikasi saat re-seed
        DB::table('sdb_units')->delete();

        $units = [];
        $now = now();

        // Layout Tipe B (12 Kolom x 6 Baris)
        $tipeBLayout = [
            ["001", "002", "003", "031", "032", "033", "061", "062", "063", "091", "092", "093"],
            ["004", "005", "006", "034", "035", "036", "064", "065", "066", "094", "095", "096"],
            ["007", "008", "009", "037", "038", "039", "067", "068", "069", "097", "098", "099"],
            ["010", "011", "012", "040", "041", "042", "070", "071", "072", "100", "101", "102"],
            ["013", "014", "015", "043", "044", "045", "073", "074", "075", "103", "104", "105"],
            ["016", "017", "018", "046", "047", "048", "076", "077", "078", "106", "107", "108"]
        ];

        // Layout Tipe C (12 Kolom x 4 Baris)
        $tipeCLayout = [
            ["019", "020", "021", "049", "050", "051", "079", "080", "081", "109", "110", "111"],
            ["022", "023", "024", "052", "053", "054", "082", "083", "084", "112", "113", "114"],
            ["025", "026", "027", "055", "056", "057", "085", "086", "087", "115", "116", "117"],
            ["028", "029", "030", "058", "059", "060", "088", "089", "090", "118", "119", "120"]
        ];

        foreach (array_merge(...$tipeBLayout) as $nomor) {
            $units[] = ['nomor_sdb' => $nomor, 'tipe' => 'B', 'created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_merge(...$tipeCLayout) as $nomor) {
            $units[] = ['nomor_sdb' => $nomor, 'tipe' => 'C', 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('sdb_units')->insert($units);
    }
}
