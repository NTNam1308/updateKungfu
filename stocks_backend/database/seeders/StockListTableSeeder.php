<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockListTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('stocks_backend_pgsql')->table('stock_list')->truncate();

        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "AAA",
            "nhom" => "nonbank",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần  AAA",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "BBB",
            "nhom" => "bank",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần  BBB",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "CCC",
            "nhom" => "bank",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần CCC",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "DDD",
            "nhom" => "nonbank",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần  DDD",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "EEE",
            "nhom" => "nonbank",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần EEE",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "FFF",
            "nhom" => "stock",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần FFF",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "GGG",
            "nhom" => "stock",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần GGG",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "HHH",
            "nhom" => "insurance",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần HHH",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "III",
            "nhom" => "insurance",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần III",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "KKK",
            "nhom" => "insurance",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần KKK",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "LLL",
            "nhom" => "insurance",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần LLL",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "MMM",
            "nhom" => "insurance",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần MMM",
        ]);
        DB::connection('stocks_backend_pgsql')->table('stock_list')->insert([
            "stockcode" => "NNN",
            "nhom" => "nonbank",
            "nganh" => "SẢN XUẤT",
            "nganh_hep" => "SẢN XUẤT",
            "san" => "UPCOM",
            "nhomtop" => "",
            "san_ta" => "UPCOM",
            "bang_ta" => "upcomticker",
            "loai" => "stock",
            "ten" => "Công ty cổ phần NNN",
        ]);
    }
}
