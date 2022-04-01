<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfitLossController extends Controller
{
    public function getAllItemTrading()
    {
        $user = JWTAuth::user();

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
        return $list_data_item;
    }
}
