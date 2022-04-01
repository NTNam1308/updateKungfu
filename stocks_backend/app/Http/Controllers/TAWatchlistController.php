<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class TAWatchlistController extends Controller
{
    public function getListCategory(Request $request)
    {
        $user = JWTAuth::user();
        $data_return = [];
        $check_admin = false;
        foreach ($user->roles as $row) {
            if ($row->name == "admin" || $row->name == "moderator" || $row->name == "coworker") {
                $check_admin = true;
            }
        }
        if ($check_admin) {
            $data_department_category = DB::connection('stocks_backend_pgsql')
            ->table('stock_list')
            ->addSelect("nganh")
            ->distinct()
            ->where("nganh","!=","")
            ->get();
            foreach ($data_department_category as $value) {
                array_push($data_return, [
                    "name" => "Ngành: " . $value->nganh,
                    "id" => "department_". $value->nganh,
                    "readonly" => true
                ]);
            }
            array_push($data_return, [
                "name" => "KUNGFU Clans",
                "id" => "stocksuggestion_1",
                "readonly" => true
            ]);
        }
        
        $data_tradinglog_category = DB::table('category')
            ->addSelect('name')
            ->addSelect('id')
            ->where('id_user', $user->id)
            ->orderBy('id', "asc")
            ->get();
        foreach ($data_tradinglog_category as $value) {
            array_push($data_return, [
                "name" => "Nhật ký - " . $value->name,
                "id" => "tradinglog_" . $value->id,
                "readonly" => true
            ]);
        }
        $data_watchlist_category = DB::table('my_watchlists')
            ->addSelect('name')
            ->addSelect('id')
            ->where('user_id', $user->id)
            ->orderBy('id', "asc")
            ->get();
        foreach ($data_watchlist_category as $value) {
            array_push($data_return, [
                "name" => $value->name,
                "id" => "watchlist_" . $value->id,
                "readonly" => false
            ]);
        }
        return $data_return;
    }

    public function getListMackByCategory(Request $request)
    {
        $user = JWTAuth::user();
        $category_id = $request->input("category_id");
        $category_id = explode("_", $category_id);
        if ($category_id[0] == "watchlist") {
            return DB::table('watchlists')
                ->addSelect('mack')
                ->addSelect('id')
                ->where('user_id', $user->id)
                ->where('my_watchlist_id', $category_id[1])
                ->orderBy('id', "asc")
                ->get();
        } else if ($category_id[0] == "tradinglog") {
            $list_data_sell = DB::table('trading_log')
                ->addSelect("mack")
                ->addSelect(DB::raw("SUM(khoi_luong) as khoi_luong_ban"))
                ->where('id_user', $user->id)
                ->where('loai_giao_dich', "ban")
                ->where("danh_muc", $category_id[1])
                ->groupBy(["mack"])
                ->get();
            $list_data_buy = DB::table('trading_log')
                ->addSelect("mack")
                ->addSelect(DB::raw("SUM(khoi_luong) as khoi_luong_mua"))
                ->where("id_user", $user->id)
                ->where("loai_giao_dich", "mua")
                ->where("danh_muc", $category_id[1])
                ->groupBy(["mack"])
                ->get();
            foreach ($list_data_sell as $key => $value) {
                $list_data_sell[$value->mack] = $value;
                unset($list_data_sell[$key]);
            }
            $data_return = [];
            foreach ($list_data_buy as $key => $value) {
                $khoi_luong_ton = $value->khoi_luong_mua;
                if (isset($list_data_sell[$value->mack])) {
                    $khoi_luong_ton = $value->khoi_luong_mua - $list_data_sell[$value->mack]->khoi_luong_ban;
                }
                if ($khoi_luong_ton == 0) {
                    continue;
                }
                $value->khoi_luong_ton = $khoi_luong_ton;
                array_push($data_return, [
                    "id" => $key,
                    "mack" => $value->mack,
                    // "khoi_luong_ton" => $khoi_luong_ton
                ]);
            }
            return $data_return;
        }else if($category_id[0] == "stocksuggestion"){
            return DB::table('stock_suggestions')
            ->addSelect("id")
            ->addSelect("mack")
            ->whereNull("ngay_dong_khuyen_nghi")
            ->get();
        }else if($category_id[0] == "department"){
            return DB::connection('stocks_backend_pgsql')
            ->table('stock_list')
            ->addSelect(DB::raw("row_number() OVER () as id"))
            ->addSelect("stockcode as mack")
            ->where("nganh",$category_id[1])
            ->get();
        }else {
            return [];
        }
    }
}
