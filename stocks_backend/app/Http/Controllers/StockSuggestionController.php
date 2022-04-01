<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class StockSuggestionController extends Controller
{
    public function index()
    {
        $user = JWTAuth::user();
        $check = false;
        if($user->clan == 1){
            $check = true;
        }
        foreach($user->roles as $row){
            if($row->name == "admin"){
                $check = true;
            }
        }
        if(!$check){
            return response()->json([
                "message" => "Unauthorizon"
            ], 401);
        }
        $list_data_item = DB::table('stock_suggestions')
            ->get();
        return response()->json($list_data_item, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mack' => 'required',
            'gia_khuyen_nghi' => 'required|numeric|gt:0',
            'ngay_khuyen_nghi' => 'required',
            'gia_muc_tieu' => 'required|numeric|gt:0',
            'gia_cat_lo' => 'required|numeric|gt:0'
        ]);
        $new_stock_suggestion = [
            "mack" => $request->mack,
            "gia_khuyen_nghi" => $request->gia_khuyen_nghi,
            "ngay_khuyen_nghi" => $request->ngay_khuyen_nghi,
            "gia_muc_tieu" => $request->gia_muc_tieu,
            "gia_cat_lo" => $request->gia_cat_lo
        ];
        $last_insert_id = DB::table('stock_suggestions')->insertGetId($new_stock_suggestion);
        $new_stock_suggestion = array_merge(['id' => $last_insert_id], $new_stock_suggestion);
        return response()->json($new_stock_suggestion, 201);
    }
    // 'ngay_dong_khuyen_nghi' => 'required',
    //         'gia_dong_khuyen_nghi' => 'required|numeric|gt:0'
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|gt:0',
            'mack' => 'required',
            'gia_khuyen_nghi' => 'required|numeric|gt:0',
            'ngay_khuyen_nghi' => 'required',
            'gia_muc_tieu' => 'required|numeric|gt:0',
            'gia_cat_lo' => 'required|numeric|gt:0',
            'gia_dong_khuyen_nghi' => 'numeric|gt:0'
        ]);
        $item_edit_stock_suggestion = [
            "mack" => $request->mack,
            "gia_khuyen_nghi" => $request->gia_khuyen_nghi,
            "ngay_khuyen_nghi" => $request->ngay_khuyen_nghi,
            "gia_muc_tieu" => $request->gia_muc_tieu,
            "gia_cat_lo" => $request->gia_cat_lo,
            "ngay_dong_khuyen_nghi" => $request->ngay_dong_khuyen_nghi,
            "gia_dong_khuyen_nghi" => $request->gia_dong_khuyen_nghi
        ];
        DB::table('stock_suggestions')
            ->where('id', $request->id)
            ->update($item_edit_stock_suggestion);
        return response()->json([
            "id" => $request->id,
            "mack" => $request->mack,
            "gia_khuyen_nghi" => $request->gia_khuyen_nghi,
            "ngay_khuyen_nghi" => $request->ngay_khuyen_nghi,
            "gia_muc_tieu" => $request->gia_muc_tieu,
            "gia_cat_lo" => $request->gia_cat_lo,
            "ngay_dong_khuyen_nghi" => $request->ngay_dong_khuyen_nghi,
            "gia_dong_khuyen_nghi" => $request->gia_dong_khuyen_nghi
        ], 200);
    }

    public function closeSuggestion(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|gt:0',
            'gia_dong_khuyen_nghi' => 'required|numeric|gt:0'
        ]);
        $item_edit_stock_suggestion = [
            "ngay_dong_khuyen_nghi" => date('Y-m-d H:i:s'),
            "gia_dong_khuyen_nghi" => $request->gia_dong_khuyen_nghi
        ];
        $id_updated = DB::table('stock_suggestions')
            ->where('id', $request->id)
            ->update($item_edit_stock_suggestion);
        $data_updated = DB::table('stock_suggestions')
            ->where('id', $request->id)
            ->first();
        return response()->json([$data_updated], 200);
    }

    public function destroy($id)
    {
        DB::table('stock_suggestions')
            ->where('id', $id)
            ->delete();
        return response(null, 204);
    }
}
