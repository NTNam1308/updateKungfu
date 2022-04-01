<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MyWatchlistTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('my_watchlists')->insert([
            "id" => 1,
            "name" => "Đầu tư",
            "user_id" => 1
        ]);
        DB::table('my_watchlists')->insert([
            "id" => 2,
            "name" => "Lướt sóng",
            "user_id" => 1
        ]);

        DB::table('my_watchlists')->insert([
            "id" => 3,
            "name" => "Đầu tư",
            "user_id" => 2
        ]);
        DB::table('my_watchlists')->insert([
            "id" => 4,
            "name" => "Lướt sóng",
            "user_id" => 2
        ]);
    }
}
