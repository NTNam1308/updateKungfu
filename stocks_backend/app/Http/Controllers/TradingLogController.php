<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class TradingLogController extends Controller
{
    public function getCurrentPrice(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $last_date =  DB::connection('pgsql')
            ->table('hoticker')
            ->addSelect(DB::raw('MAX(tradingdate) as tradingdate'))
            ->first();
        $last_date = $last_date->tradingdate;
        $last_date = substr($last_date, 0, 10);
        $price = DB::connection('pgsql')
            ->table('trstockrealtime')
            ->where('stockcode', '=', $mack)
            ->where('tradingtime', '>=', $last_date)
            ->addSelect('lastprice')
            ->first();
        return $price->lastprice / 1000;
    }

    public function index()
    {
        $user = JWTAuth::user();
        $list_data_item = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->orderBy('danh_muc')
            ->orderBy('mack')
            ->orderBy('ngay_giao_dich')
            ->orderBy('id')
            ->get();
        if (count($list_data_item) == 0) {
            return response()->json([
                "list_data_item" => [],
                "list_data_mack" => [],
            ], 200);
        }
        $mack = $list_data_item[0]->mack;
        $danh_muc = $list_data_item[0]->danh_muc;
        $tong_khoi_luong_mua = 0;
        $tong_khoi_luong_ban = 0;
        $tong_gia_tri_dau_tu = 0;
        $khoi_luong_ton = 0;
        $gia_trung_binh = 0;
        foreach ($list_data_item as $row) {
            if ($danh_muc != $row->danh_muc) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $danh_muc = $row->danh_muc;
            }
            if ($mack != $row->mack) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $mack = $row->mack;
            }
            if ($row->loai_giao_dich == "mua") {
                $tong_khoi_luong_mua += $row->khoi_luong;
                $khoi_luong_ton += $row->khoi_luong;
                $tong_gia_tri_dau_tu += $row->khoi_luong * $row->gia_thuc_hien * (1 + $row->phi / 100);
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
            }
            if ($row->loai_giao_dich == "ban") {
                $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
                $khoi_luong_ton -= $row->khoi_luong;
                $tong_khoi_luong_ban += $row->khoi_luong;
                $tong_gia_tri_dau_tu = $gia_trung_binh * $khoi_luong_ton;
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                // $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
            }
        }
        $list_data_mack = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->addSelect(DB::raw("distinct mack"))
            ->get();
        return response()->json([
            "list_data_item" => $list_data_item,
            "list_data_mack" => $list_data_mack,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mack' => 'required',
            'ngay_giao_dich' => 'required',
            'loai_giao_dich' => 'required',
            'khoi_luong' => 'required|numeric|gt:0',
            'gia_thuc_hien' => 'required|numeric|min:0',
            'phi' => 'required|numeric|min:0',
            'danh_muc' => 'required|exists:category,id'
        ]);
        $user = JWTAuth::user();
        // extract($request->all(), EXTR_PREFIX_SAME, "wddx");
        $lai_lo_da_thuc_hien = 0;
        if ($request->loai_giao_dich == "ban") {
            $data_trading_log = DB::table('trading_log')
                ->where('id_user', $user->id)
                ->where("mack", $request->mack)
                ->where("danh_muc", $request->danh_muc)
                ->orderBy("ngay_giao_dich", "asc")
                ->orderBy("id", "asc")
                ->get();
            $tong_khoi_luong_mua = 0;
            $tong_khoi_luong_ban = 0;
            $tong_gia_tri_dau_tu = 0;
            $khoi_luong_ton = 0;
            $gia_trung_binh = 0;
            foreach ($data_trading_log as $item) {
                if (strtotime($item->ngay_giao_dich) <= strtotime($request->ngay_giao_dich . " 00:00:00.0")) {
                    if ($item->loai_giao_dich == "mua") {
                        $tong_khoi_luong_mua += $item->khoi_luong;
                        $khoi_luong_ton += $item->khoi_luong;
                        $tong_gia_tri_dau_tu += $item->khoi_luong * $item->gia_thuc_hien * (1 + $item->phi / 100);
                        $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                    }
                    if ($item->loai_giao_dich == "ban") {
                        $khoi_luong_ton -= $item->khoi_luong;
                        $tong_khoi_luong_ban += $item->khoi_luong;
                        $tong_gia_tri_dau_tu = $gia_trung_binh * $khoi_luong_ton;
                        $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                    }
                }
            }
            $tong_khoi_luong_hien_co = $tong_khoi_luong_mua - $tong_khoi_luong_ban;
            if ($tong_khoi_luong_hien_co < $request->khoi_luong || $request->khoi_luong <= 0) {
                return response()->json([
                    'error' => "No transaction yet",
                ], 422);
            }
            // $lai_lo_da_thuc_hien = ($request->gia_thuc_hien) * ($request->khoi_luong) * 1000 * (1 - $request->phi / 100) - ($request->khoi_luong) * $gia_trung_binh * 1000;
            // $lai_lo_da_thuc_hien = ($request->gia_thuc_hien * (1 - $request->phi / 100) - $gia_trung_binh) * $request->khoi_luong;
            $lai_lo_da_thuc_hien = 0;
        }
        $new_trading_log = [
            "id_user" => $user->id,
            "mack" => $request->mack,
            "ngay_giao_dich" => $request->ngay_giao_dich,
            "loai_giao_dich" => $request->loai_giao_dich,
            "khoi_luong" => $request->khoi_luong,
            "gia_thuc_hien" => $request->gia_thuc_hien,
            "phi" => $request->phi,
            "danh_muc" => $request->danh_muc,
            "lai_lo_da_thuc_hien" => 0,
            "chu_thich" => $request->chu_thich
        ];
        DB::table('trading_log')->insert($new_trading_log);
        $list_data_item = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->orderBy('danh_muc')
            ->orderBy('mack')
            ->orderBy('ngay_giao_dich')
            ->orderBy('id')
            ->get();
        $mack = $list_data_item[0]->mack;
        $danh_muc = $list_data_item[0]->danh_muc;
        $tong_khoi_luong_mua = 0;
        $tong_khoi_luong_ban = 0;
        $tong_gia_tri_dau_tu = 0;
        $khoi_luong_ton = 0;
        $gia_trung_binh = 0;
        foreach ($list_data_item as $row) {
            if ($danh_muc != $row->danh_muc) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $danh_muc = $row->danh_muc;
            }
            if ($mack != $row->mack) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $mack = $row->mack;
            }
            if ($row->loai_giao_dich == "mua") {
                $tong_khoi_luong_mua += $row->khoi_luong;
                $khoi_luong_ton += $row->khoi_luong;
                $tong_gia_tri_dau_tu += $row->khoi_luong * $row->gia_thuc_hien * (1 + $row->phi / 100);
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
            }
            if ($row->loai_giao_dich == "ban") {
                $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
                $khoi_luong_ton -= $row->khoi_luong;
                $tong_khoi_luong_ban += $row->khoi_luong;
                $tong_gia_tri_dau_tu = $gia_trung_binh * $khoi_luong_ton;
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                // $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
            }
        }
        $list_data_mack = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->addSelect(DB::raw("distinct mack"))
            ->get();
        return response()->json([
            "list_data_item" => $list_data_item,
            "list_data_mack" => $list_data_mack,
        ], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'mack' => 'required',
            'ngay_giao_dich' => 'required',
            'loai_giao_dich' => 'required',
            'khoi_luong' => 'required|numeric|gt:0',
            'gia_thuc_hien' => 'required|numeric|min:0',
            'phi' => 'required|numeric|min:0',
            'danh_muc' => 'required|exists:category,id'
        ]);
        $user = JWTAuth::user();
        // extract($request->all(), EXTR_PREFIX_SAME, "wddx");
        $lai_lo_da_thuc_hien = 0;
        if ($request->loai_giao_dich == "ban") {
            $data_trading_log = DB::table('trading_log')
                ->where('id_user', $user->id)
                ->where("mack", $request->mack)
                ->whereNotIn("id", [$request->id])
                ->where("danh_muc", $request->danh_muc)
                ->get();
            $tong_khoi_luong_mua = 0;
            $tong_khoi_luong_ban = 0;
            $tong_gia_tri_dau_tu = 0;
            $khoi_luong_ton = 0;
            $gia_trung_binh = 0;
            foreach ($data_trading_log as $item) {
                if (strtotime($item->ngay_giao_dich) <= strtotime($request->ngay_giao_dich . " 00:00:00.0")) {
                    if ($item->loai_giao_dich == "mua") {
                        $tong_khoi_luong_mua += $item->khoi_luong;
                        $khoi_luong_ton += $item->khoi_luong;
                        $tong_gia_tri_dau_tu += $item->khoi_luong * $item->gia_thuc_hien * (1 + $item->phi / 100);
                        $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                    }
                    if ($item->loai_giao_dich == "ban") {
                        $khoi_luong_ton -= $item->khoi_luong;
                        $tong_khoi_luong_ban += $item->khoi_luong;
                        $tong_gia_tri_dau_tu = $gia_trung_binh * $khoi_luong_ton;
                        $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                    }
                }
            }
            $tong_khoi_luong_hien_co = $tong_khoi_luong_mua - $tong_khoi_luong_ban;
            if ($tong_khoi_luong_hien_co < $request->khoi_luong || $request->khoi_luong <= 0) {
                return response()->json([
                    'error' => "No transaction yet",
                ], 422);
            }
            // $lai_lo_da_thuc_hien = ($request->gia_thuc_hien) * ($request->khoi_luong) * 1000 * (1 - $request->phi / 100) - ($request->khoi_luong) * $gia_trung_binh * 1000;
            // $lai_lo_da_thuc_hien = ($request->gia_thuc_hien * (1 - $request->phi / 100) - $gia_trung_binh) * $request->khoi_luong;
            $lai_lo_da_thuc_hien = 0;
        }
        // extract($request->all(), EXTR_PREFIX_SAME, "wddx");
        $item_edit_trading_log = [
            "id_user" => $user->id,
            "mack" => $request->mack,
            "ngay_giao_dich" => $request->ngay_giao_dich,
            "loai_giao_dich" => $request->loai_giao_dich,
            "khoi_luong" => $request->khoi_luong,
            "gia_thuc_hien" => $request->gia_thuc_hien,
            "phi" => $request->phi,
            "danh_muc" => $request->danh_muc,
            "lai_lo_da_thuc_hien" => 0,
            "chu_thich" => $request->chu_thich
        ];
        DB::table('trading_log')
            ->where('id', $request->id)
            ->where('id_user', $user->id)
            ->update($item_edit_trading_log);
        $list_data_item = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->orderBy('danh_muc')
            ->orderBy('mack')
            ->orderBy('ngay_giao_dich')
            ->orderBy('id')
            ->get();
        $mack = $list_data_item[0]->mack;
        $danh_muc = $list_data_item[0]->danh_muc;
        $tong_khoi_luong_mua = 0;
        $tong_khoi_luong_ban = 0;
        $tong_gia_tri_dau_tu = 0;
        $khoi_luong_ton = 0;
        $gia_trung_binh = 0;
        foreach ($list_data_item as $row) {
            if ($danh_muc != $row->danh_muc) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $danh_muc = $row->danh_muc;
            }
            if ($mack != $row->mack) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $mack = $row->mack;
            }
            if ($row->loai_giao_dich == "mua") {
                $tong_khoi_luong_mua += $row->khoi_luong;
                $khoi_luong_ton += $row->khoi_luong;
                $tong_gia_tri_dau_tu += $row->khoi_luong * $row->gia_thuc_hien * (1 + $row->phi / 100);
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
            }
            if ($row->loai_giao_dich == "ban") {
                $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
                $khoi_luong_ton -= $row->khoi_luong;
                $tong_khoi_luong_ban += $row->khoi_luong;
                $tong_gia_tri_dau_tu = $gia_trung_binh * $khoi_luong_ton;
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                // $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
            }
        }
        $list_data_mack = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->addSelect(DB::raw("distinct mack"))
            ->get();
        return response()->json([
            "list_data_item" => $list_data_item,
            "list_data_mack" => $list_data_mack,
        ], 200);
    }

    public function destroy($id)
    {
        $user = JWTAuth::user();
        DB::table('trading_log')
            ->where('id_user', $user->id)
            ->where('id', $id)
            ->delete();
        $list_data_item = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->orderBy('danh_muc')
            ->orderBy('mack')
            ->orderBy('ngay_giao_dich')
            ->orderBy('id')
            ->get();
        if (count($list_data_item) == 0) {
            return response()->json([
                "list_data_item" => [],
                "list_data_mack" => [],
            ], 200);
        }
        $mack = $list_data_item[0]->mack;
        $danh_muc = $list_data_item[0]->danh_muc;
        $tong_khoi_luong_mua = 0;
        $tong_khoi_luong_ban = 0;
        $tong_gia_tri_dau_tu = 0;
        $khoi_luong_ton = 0;
        $gia_trung_binh = 0;
        foreach ($list_data_item as $row) {
            if ($danh_muc != $row->danh_muc) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $danh_muc = $row->danh_muc;
            }
            if ($mack != $row->mack) {
                $tong_khoi_luong_mua = 0;
                $tong_khoi_luong_ban = 0;
                $tong_gia_tri_dau_tu = 0;
                $khoi_luong_ton = 0;
                $gia_trung_binh = 0;
                $mack = $row->mack;
            }
            if ($row->loai_giao_dich == "mua") {
                $tong_khoi_luong_mua += $row->khoi_luong;
                $khoi_luong_ton += $row->khoi_luong;
                $tong_gia_tri_dau_tu += $row->khoi_luong * $row->gia_thuc_hien * (1 + $row->phi / 100);
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
            }
            if ($row->loai_giao_dich == "ban") {
                $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
                $khoi_luong_ton -= $row->khoi_luong;
                $tong_khoi_luong_ban += $row->khoi_luong;
                $tong_gia_tri_dau_tu = $gia_trung_binh * $khoi_luong_ton;
                $gia_trung_binh = $khoi_luong_ton != 0 ? $tong_gia_tri_dau_tu / $khoi_luong_ton : 0;
                // $row->lai_lo_da_thuc_hien = ($row->gia_thuc_hien * (1 - $row->phi / 100) - $gia_trung_binh) * $row->khoi_luong;
            }
        }
        $list_data_mack = DB::table('trading_log')
            ->where('id_user', $user->id)
            ->addSelect(DB::raw("distinct mack"))
            ->get();
        return response()->json([
            "list_data_item" => $list_data_item,
            "list_data_mack" => $list_data_mack,
        ], 200);
    }
}
