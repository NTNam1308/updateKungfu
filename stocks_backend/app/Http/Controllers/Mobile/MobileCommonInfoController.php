<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MobileCommonInfoController extends Controller
{
    public function getCommonValueNonbank($mack)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $type = "nonbank";
        $is = 'is_' . $thoigian . '_' . $type;
        $bs = 'bs_' . $thoigian . '_' . $type;
        $price = null;
        try {
            $price = DB::connection('pgsql')
                ->table('stock_live')
                ->where('stockcode', '=', $mack)
                ->addSelect('lastprice')
                ->first();
            $price = $price->lastprice;
        } catch (Exception $e) {
            $price = 0;
        }
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu as von_dau_tu_cua_chu_so_huu'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('bs.no_dai_han as no_dai_han'))
            ->addSelect(DB::raw('bs.no_phai_tra as no_phai_tra'))
            ->addSelect(DB::raw('bs.no_ngan_han as no_ngan_han'))
            ->addSelect(DB::raw('is.doanh_thu_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('is.loi_nhuan_gop as loi_nhuan_gop'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as loi_nhuan_truoc_thue'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as loi_nhuan_rong'))
            ->addSelect(DB::raw('is.loi_nhuan_khac as loi_nhuan_khac'))
            ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh-is.chi_phi_tai_chinh as loi_nhuan_tu_hoat_dong_kinh_doanh'))
            ->addSelect(DB::raw('bs.tong_hang_ton_kho as tong_hang_ton_kho'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                ),
            'm'
        )
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2))) DESC"))
            ->take($limit + 3)
            ->get();
        $ret = [
            "von_dieu_le" => 0,
            "so_luong_co_phieu" => 0,
            "von_hoa_thi_truong" => 0,
            "eps" => 0,
            "bvps" => 0,
            "pb" => 0,
            "pe" => 0,
            "roa" => 0,
            "roe" =>  0,
            "roic" => 0,
            "tong_no_quy_gan_nhat" => 0,
            "tong_no_ngan_han_quy_gan_nhat" => 0,
            "tong_no_dai_han_quy_gan_nhat" => 0,
            "tong_tai_san_quy_gan_nhat" => 0,
            "tong_hang_ton_kho_quy_gan_nhat" => 0,
            "von_chu_so_huu_quy_gan_nhat" => 0,
            "doanh_thu_thuan_ttm" => 0,
            "loi_nhuan_gop_ttm" => 0,
            "loi_nhuan_khac_ttm" => 0,
            "loi_nhuan_truoc_thue_ttm" => 0,
            "loi_nhuan_sau_thue_ttm" => 0,
            "loi_nhuan_tu_hdtc_ttm" => 0
        ];
        if (count($res) == 0) {
            return $ret;
        }
        $res = json_decode(json_encode($res), true);
        if (!$res)
            return $ret;
        $data_compare = DB::table("compare_nonbank")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('roa_ttm'))
            ->addSelect(DB::raw('roe_ttm'))
            ->addSelect(DB::raw('roic_ttm'))
            ->addSelect(DB::raw('dtt_ttm'))
            ->addSelect(DB::raw('lng_ttm'))
            ->first();
        try {
            $res[0]['eps'] =  $data_compare->eps ? $data_compare->eps : 0;
            $res[0]['roa'] =  $data_compare->roa_ttm ? $data_compare->roa_ttm : 0;
            $res[0]['roe'] =  $data_compare->roa_ttm ? $data_compare->roe_ttm : 0;
            $res[0]['roic'] =  $data_compare->roa_ttm ? $data_compare->roic_ttm : 0;
        } catch (Exception $e) {
            $res[0]['eps'] =  0;
            $res[0]['roa'] =  0;
            $res[0]['roe'] =  0;
            $res[0]['roic'] = 0;
        }
        // $g_eps = $eps_ttm < 0 ? 0 : ($eps_ttm * $eps_previos_3_year_ttm < 0 ? 100 : $this::RATE(3, 0, - ($eps_previos_3_year_ttm), $eps_ttm));
        $res[0]['von_dieu_le'] = $res[0]['von_dau_tu_cua_chu_so_huu'];
        $res[0]['so_luong_co_phieu'] = $res[0]['von_dau_tu_cua_chu_so_huu'] * 100;
        $res[0]['von_hoa_thi_truong'] = $price * ($res[0]['von_dau_tu_cua_chu_so_huu'] / 10000);
        $res[0]['pb'] = $res[0]['bvps'] != 0 ? $price / $res[0]['bvps'] : 0;
        $res[0]['pe'] = $res[0]['eps'] != 0 ? $price / $res[0]['eps'] : 0;
        $doanh_thu_thuan_ttm =  0;
        $loi_nhuan_gop_ttm = 0;
        $loi_nhuan_khac_ttm = 0;
        $loi_nhuan_tu_hoat_dong_kinh_doanh_ttm = 0;
        $loi_nhuan_truoc_thue_ttm = 0;
        $loi_nhuan_rong_ttm = 0;
        try {
            $doanh_thu_thuan_ttm = $res[0]['doanh_thu_thuan'] + $res[1]['doanh_thu_thuan'] + $res[2]['doanh_thu_thuan'] + $res[3]['doanh_thu_thuan'];
            $loi_nhuan_gop_ttm = $res[0]['loi_nhuan_gop'] + $res[1]['loi_nhuan_gop'] + $res[2]['loi_nhuan_gop'] + $res[3]['loi_nhuan_gop'];
            $loi_nhuan_khac_ttm = $res[0]['loi_nhuan_khac'] + $res[1]['loi_nhuan_khac'] + $res[2]['loi_nhuan_khac'] + $res[3]['loi_nhuan_khac'];
            $loi_nhuan_tu_hoat_dong_kinh_doanh_ttm = $res[0]['loi_nhuan_tu_hoat_dong_kinh_doanh'] + $res[1]['loi_nhuan_tu_hoat_dong_kinh_doanh'] + $res[2]['loi_nhuan_tu_hoat_dong_kinh_doanh'] + $res[3]['loi_nhuan_tu_hoat_dong_kinh_doanh'];
            $loi_nhuan_truoc_thue_ttm = $res[0]['loi_nhuan_truoc_thue'] + $res[1]['loi_nhuan_truoc_thue'] + $res[2]['loi_nhuan_truoc_thue'] + $res[3]['loi_nhuan_truoc_thue'];
            $loi_nhuan_rong_ttm = $res[0]['loi_nhuan_rong'] + $res[1]['loi_nhuan_rong'] + $res[2]['loi_nhuan_rong'] + $res[3]['loi_nhuan_rong'];
        } catch (Exception $e) {
        }

        $res[0]['ps'] = $doanh_thu_thuan_ttm != 0 ? $price / $doanh_thu_thuan_ttm : 0;
        // $res[0]['peg'] = $g_eps != 0 ? $res[0]["pe"] / ($g_eps) /100 : 0;
        return [
            "von_dieu_le" => round($res[0]['von_dieu_le'], 1),
            // "gia_thi_truong" => round($price, 1),
            "so_luong_co_phieu" => round($res[0]['so_luong_co_phieu'], 1),
            "von_hoa_thi_truong" => round($res[0]['von_hoa_thi_truong'], 1),
            "eps" => round($res[0]['eps'], 1),
            "bvps" => round($res[0]['bvps'], 1),
            "pb" => round($res[0]['pb'], 1),
            "pe" => round($res[0]['pe'], 1),
            "roa" => round($res[0]['roa'], 1),
            "roe" =>  round($res[0]['roe'], 1),
            "roic" => round($res[0]['roic'], 1),
            "tong_no_quy_gan_nhat" => round($res[0]['no_phai_tra'], 1),
            "tong_no_ngan_han_quy_gan_nhat" => round($res[0]['no_ngan_han'], 1),
            "tong_no_dai_han_quy_gan_nhat" => round($res[0]['no_dai_han'], 1),
            "tong_tai_san_quy_gan_nhat" => round($res[0]['tong_cong_tai_san'], 1),
            "tong_hang_ton_kho_quy_gan_nhat" => round($res[0]['tong_hang_ton_kho'], 1),
            "von_chu_so_huu_quy_gan_nhat" => round($res[0]['von_chu_so_huu'], 1),
            "doanh_thu_thuan_ttm" => round($doanh_thu_thuan_ttm, 1),
            "loi_nhuan_gop_ttm" => round($loi_nhuan_gop_ttm, 1),
            "loi_nhuan_khac_ttm" => round($loi_nhuan_khac_ttm, 1),
            "loi_nhuan_truoc_thue_ttm" => round($loi_nhuan_truoc_thue_ttm, 1),
            "loi_nhuan_sau_thue_ttm" => round($loi_nhuan_rong_ttm, 1),
            "loi_nhuan_tu_hdtc_ttm" => round($loi_nhuan_tu_hoat_dong_kinh_doanh_ttm, 1)
        ];
    }

    public function getCommonValueBank($mack)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $type = "bank";
        $is = 'is_' . $thoigian . '_' . $type;
        $bs = 'bs_' . $thoigian . '_' . $type;
        $price = null;
        try {
            $price = DB::connection('pgsql')
                ->table('stock_live')
                ->where('stockcode', '=', $mack)
                ->addSelect('lastprice')
                ->first();
            $price = $price->lastprice;
        } catch (Exception $e) {
            $price = 0;
        }
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('(bs.von_va_cac_quy + bs.loi_ich_cua_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dieu_le/10000) as bvps'))
            ->addSelect(DB::raw('bs.von_dieu_le as von_dieu_le'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai as lnst'))
            ->addSelect(DB::raw('is.thu_nhap_lai_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('bs.von_va_cac_quy as von_chu_so_huu'))
            ->addSelect(DB::raw('bs.tien_gui_tai_nhnn + bs.tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac + bs.tong_cho_vay_khach_hang +bs.chung_khoan_dau_tu as tai_san_co_lai'))
            ->addSelect(DB::raw('bs.tong_cong_nguon_von-bs.loi_ich_cua_co_dong_thieu_so-bs.von_va_cac_quy as no_phai_tra'))
            ->addSelect(DB::raw('bs.tien_gui_khach_hang as tien_gui_khach_hang'))
            ->addSelect(DB::raw('bs.tong_cho_vay_khach_hang as tong_cho_vay_khach_hang'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung as loi_nhuan_tu_hdkd'))
            ->addSelect(DB::raw('is.chi_phi_hoat_dong as chi_phi_hoat_dong'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue as loi_nhuan_truoc_thue'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                ),
            'm'
        )
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2))) DESC"))
            ->take($limit + 3)
            ->get();
        $ret = [
            "von_dieu_le" => 0,
            "so_luong_co_phieu" => 0,
            "von_hoa_thi_truong" => 0,
            "eps" => 0,
            "bvps" => 0,
            "pb" => 0,
            "pe" => 0,
            "roaa" => 0,
            "roea" =>  0,
            "nim" => 0,
            "tong_no_quy_gan_nhat" => 0,
            "tong_tien_gui_khach_hang_quy_gan_nhat" => 0,
            "tong_du_no_cho_vay_quy_gan_nhat" => 0,
            "tong_tai_san_quy_gan_nhat" => 0,
            "tong_tai_san_co_sinh_lai_quy_gan_nhat" => 0,
            "vcsh_quy_gan_nhat" => 0,
            "doanh_thu_hoat_dong_ttm" => 0,
            "loi_nhuan_hoat_dong_ttm" => 0,
            "loi_nhuan_truoc_thue_ttm" => 0,
            "loi_nhuan_sau_thue_ttm" => 0,
        ];
        if (count($res) == 0) {
            return $ret;
        }
        $res = json_decode(json_encode($res), true);
        if (!$res)
            return $ret;
        $data_compare = DB::table("compare_bank")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('roaa_ttm'))
            ->addSelect(DB::raw('roea'))
            ->addSelect(DB::raw('nim_ttm'))
            ->first();
        try {
            $res[0]['eps'] =  $data_compare->eps ? $data_compare->eps : 0;
            $res[0]['roaa'] = $data_compare->roaa_ttm ? $data_compare->roaa_ttm : 0;
            $res[0]['roea'] = $data_compare->roea ? $data_compare->roea : 0;
            $res[0]['nim'] = $data_compare->nim_ttm ? $data_compare->nim_ttm : 0;
        } catch (Exception $e) {
            $res[0]['eps'] =  0;
            $res[0]['roaa'] = 0;
            $res[0]['roea'] =  0;
            $res[0]['nim'] =  0;
        }
        $doanh_thu_thuan_ttm = 0;
        $lnst_ttm = 0;
        $tai_san_co_lai_ttm = 0;
        $loi_nhuan_tu_hoat_dong_ttm = 0;
        $chi_phi_hoat_dong_ttm = 0;
        $loi_nhuan_truoc_thue_ttm = 0;
        try {
            $doanh_thu_thuan_ttm = $res[0]['doanh_thu_thuan'] + $res[1]['doanh_thu_thuan'] + $res[2]['doanh_thu_thuan'] + $res[3]['doanh_thu_thuan'];
            $lnst_ttm = $res[0]['lnst'] + $res[1]['lnst'] + $res[2]['lnst'] + $res[3]['lnst'];
            $tai_san_co_lai_ttm = $res[0]['tai_san_co_lai'] + $res[1]['tai_san_co_lai'] + $res[2]['tai_san_co_lai'] + $res[3]['tai_san_co_lai'];
            $loi_nhuan_tu_hoat_dong_ttm = $res[0]["loi_nhuan_tu_hdkd"] + $res[1]["loi_nhuan_tu_hdkd"] + $res[2]["loi_nhuan_tu_hdkd"] + $res[3]["loi_nhuan_tu_hdkd"];
            $chi_phi_hoat_dong_ttm = $res[0]["chi_phi_hoat_dong"] + $res[1]["chi_phi_hoat_dong"] + $res[2]["chi_phi_hoat_dong"] + $res[3]["chi_phi_hoat_dong"];
            $loi_nhuan_truoc_thue_ttm = $res[0]["loi_nhuan_truoc_thue"] + $res[1]["loi_nhuan_truoc_thue"] + $res[2]["loi_nhuan_truoc_thue"] + $res[3]["loi_nhuan_truoc_thue"];
        } catch (Exception $e) {
        }

        $res[0]['so_luong_co_phieu'] = $res[0]['von_dieu_le'] * 100;
        $res[0]['von_hoa_thi_truong'] = $price * ($res[0]['von_dieu_le'] / 10000);
        $res[0]['pb'] = $res[0]['bvps'] != 0 ? $price / $res[0]['bvps'] : 0;
        $res[0]['pe'] = $res[0]['eps'] != 0 ? $price / $res[0]['eps'] : 0;
        $res[0]['ps'] = $doanh_thu_thuan_ttm != 0 ? $price / $doanh_thu_thuan_ttm : 0;
        // $res[0]['peg'] = $g_eps != 0 ? $res[0]["pe"] / ($g_eps) /100 : 0;

        $doanh_thu_hoat_dong_ttm = $loi_nhuan_tu_hoat_dong_ttm - $chi_phi_hoat_dong_ttm;
        return [
            "von_dieu_le" => round($res[0]['von_dieu_le'], 1),
            "so_luong_co_phieu" => round($res[0]['so_luong_co_phieu'], 1),
            "von_hoa_thi_truong" => round($res[0]['von_hoa_thi_truong'], 1),
            "eps" => round($res[0]['eps'], 1),
            "bvps" => round($res[0]['bvps'], 1),
            "pb" => round($res[0]['pb'], 1),
            "pe" => round($res[0]['pe'], 1),
            "roaa" => round($res[0]['roaa'], 1),
            "roea" =>  round($res[0]['roea'], 1),
            "nim" => round($res[0]['nim'], 1),
            "tong_no_quy_gan_nhat" => round($res[0]['no_phai_tra'], 1),
            "tong_tien_gui_khach_hang_quy_gan_nhat" => round($res[0]['tien_gui_khach_hang'], 1),
            "tong_du_no_cho_vay_quy_gan_nhat" => round($res[0]['tong_cho_vay_khach_hang'], 1),
            "tong_tai_san_quy_gan_nhat" => round($res[0]['tong_cong_tai_san'], 1),
            "tong_tai_san_co_sinh_lai_quy_gan_nhat" => round($res[0]['tai_san_co_lai'], 1),
            "vcsh_quy_gan_nhat" => round($res[0]['von_chu_so_huu'], 1),
            "doanh_thu_hoat_dong_ttm" => round($doanh_thu_hoat_dong_ttm, 1),
            "loi_nhuan_hoat_dong_ttm" => round($loi_nhuan_tu_hoat_dong_ttm, 1),
            "loi_nhuan_truoc_thue_ttm" => round($loi_nhuan_truoc_thue_ttm, 1),
            "loi_nhuan_sau_thue_ttm" => round($lnst_ttm, 1),
        ];
    }

    public function getCommonValueStock($mack)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $type = "stock";
        $is = 'is_' . $thoigian . '_' . $type;
        $bs = 'bs_' . $thoigian . '_' . $type;
        $price = null;
        try {
            $price = DB::connection('pgsql')
                ->table('stock_live')
                ->where('stockcode', '=', $mack)
                ->addSelect('lastprice')
                ->first();
            $price = $price->lastprice;
        } catch (Exception $e) {
            $price = 0;
        }
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh) / (bs.von_gop_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('bs.von_gop_cua_chu_so_huu as von_dieu_le'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn as lnst'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_san_tai_chinh_dai_han as no_vay_tai_chinh_dai_han'))
            ->addSelect(DB::raw('bs.no_phai_tra as no_phai_tra'))
            ->addSelect(DB::raw('bs.no_phai_tra_ngan_han as no_phai_tra_ngan_han'))
            ->addSelect(DB::raw('bs.no_phai_tra_dai_han as no_phai_tra_dai_han'))
            ->addSelect(DB::raw('bs.tai_san_tai_chinh as tai_san_tai_chinh'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong as cong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.cong_chi_phi_hoat_dong as cong_chi_phi_hoat_dong'))
            ->addSelect(DB::raw('is.cong_ket_qua_hoat_dong_khac as cong_ket_qua_hoat_dong_khac'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as tong_loi_nhuan_ke_toan_truoc_thue'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong_tai_chinh as cong_doanh_thu_hoat_dong_tai_chinh'))
            ->addSelect(DB::raw('is.cong_chi_phi_tai_chinh as cong_chi_phi_tai_chinh'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                ),
            'm'
        )
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2))) DESC"))
            ->take($limit + 3)
            ->get();
        $ret = [
            "von_dieu_le" => 0,
            "so_luong_co_phieu" => 0,
            "von_hoa_thi_truong" => 0,
            "eps" => 0,
            "bvps" => 0,
            "pb" => 0,
            "pe" => 0,
            "ps" => 0,
            "roaa" => 0,
            "roea" =>  0,
            "roic" => 0,
            "tong_no_quy_gan_nhat" => 0,
            "tong_no_ngan_han_quy_gan_nhat" => 0,
            "tong_no_dai_han_quy_gan_nhat" => 0,
            "tong_tai_san_quy_gan_nhat" => 0,
            "tong_tai_san_tai_chinh_quy_gan_nhat" => 0,
            "von_chu_so_huu_quy_gan_nhat" => 0,
            "cong_doanh_thu_hoat_dong_ttm" => 0,
            "loi_nhuan_gop_ttm" => 0,
            "loi_nhuan_khac_ttm" => 0,
            "loi_nhuan_truoc_thue_ttm" => 0,
            "loi_nhuan_sau_thue_ttm" => 0,
            "loi_nhuan_tu_hdtc_ttm" => 0
        ];
        if (count($res) == 0)
            return $ret;
        $res = json_decode(json_encode($res), true);
        if (!$res) {
            return $ret;
        }
        $data_compare = DB::table("compare_stock")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('roaa'))
            ->addSelect(DB::raw('roea'))
            ->first();
        try {
            $res[0]['eps'] =  $data_compare->eps ? $data_compare->eps : 0;
            $res[0]['roaa'] = $data_compare->roaa ? $data_compare->roaa : 0;
            $res[0]['roea'] = $data_compare->roea ? $data_compare->roea : 0;
            $res[0]['roic'] = $data_compare->roea ? $data_compare->roea : 0;
        } catch (Exception $e) {
            $res[0]['eps'] =  0;
            $res[0]['roaa'] =  0;
            $res[0]['roea'] =  0;
            $res[0]['roic'] = 0;
        }
        $lnst_ttm = 0;
        $cong_doanh_thu_hoat_dong_ttm = 0;
        $cong_doanh_thu_hoat_dong_tai_chinh_ttm = 0;
        $cong_chi_phi_hoat_dong_ttm =  0;
        $loi_nhuan_khac_ttm = 0;
        $loi_nhuan_truoc_thue_ttm =  0;
        $cong_chi_phi_tai_chinh_ttm = 0;
        try {
            $lnst_ttm = $res[0]['lnst'] + $res[1]['lnst'] + $res[2]['lnst'] + $res[3]['lnst'];
            $cong_doanh_thu_hoat_dong_ttm = $res[0]['cong_doanh_thu_hoat_dong'] + $res[1]['cong_doanh_thu_hoat_dong'] + $res[2]['cong_doanh_thu_hoat_dong'] + $res[3]['cong_doanh_thu_hoat_dong'];
            $cong_doanh_thu_hoat_dong_tai_chinh_ttm = $res[0]['cong_doanh_thu_hoat_dong_tai_chinh'] + $res[1]['cong_doanh_thu_hoat_dong_tai_chinh'] + $res[2]['cong_doanh_thu_hoat_dong_tai_chinh'] + $res[3]['cong_doanh_thu_hoat_dong_tai_chinh'];

            $cong_chi_phi_hoat_dong_ttm = $res[0]['cong_chi_phi_hoat_dong'] + $res[1]['cong_chi_phi_hoat_dong'] + $res[2]['cong_chi_phi_hoat_dong'] + $res[3]['cong_chi_phi_hoat_dong'];
            $loi_nhuan_khac_ttm = $res[0]['cong_ket_qua_hoat_dong_khac'] + $res[1]['cong_ket_qua_hoat_dong_khac'] + $res[2]['cong_ket_qua_hoat_dong_khac'] + $res[3]['cong_ket_qua_hoat_dong_khac'];
            $loi_nhuan_truoc_thue_ttm = $res[0]['tong_loi_nhuan_ke_toan_truoc_thue'] + $res[1]['tong_loi_nhuan_ke_toan_truoc_thue'] + $res[2]['tong_loi_nhuan_ke_toan_truoc_thue'] + $res[3]['tong_loi_nhuan_ke_toan_truoc_thue'];
            $cong_chi_phi_tai_chinh_ttm = $res[0]['cong_chi_phi_tai_chinh'] + $res[1]['cong_chi_phi_tai_chinh'] + $res[2]['cong_chi_phi_tai_chinh'] + $res[3]['cong_chi_phi_tai_chinh'];
        } catch (Exception $e) {
        }

        $res[0]['so_luong_co_phieu'] = $res[0]['von_dieu_le'] * 100;
        $res[0]['von_hoa_thi_truong'] = $price * ($res[0]['von_dieu_le'] / 10000);
        $res[0]['pb'] = $res[0]['bvps'] != 0 ? $price / $res[0]['bvps'] : 0;
        $res[0]['pe'] = $res[0]['eps'] != 0 ? $price / $res[0]['eps'] : 0;
        $res[0]['ps'] = $cong_doanh_thu_hoat_dong_ttm != 0 ? $price / $cong_doanh_thu_hoat_dong_ttm : 0;
        // $res[0]['peg'] = $g_eps != 0 ? $res[0]["pe"] / ($g_eps) /100 : 0;

        $loi_nhuan_gop_ttm = $cong_doanh_thu_hoat_dong_ttm - $cong_chi_phi_hoat_dong_ttm;
        $loi_nhuan_tu_hoat_dong_tai_chinh_ttm = $cong_doanh_thu_hoat_dong_tai_chinh_ttm - $cong_chi_phi_tai_chinh_ttm;
        return [
            "von_dieu_le" => round($res[0]['von_dieu_le'], 1),
            "so_luong_co_phieu" => round($res[0]['so_luong_co_phieu'], 1),
            "von_hoa_thi_truong" => round($res[0]['von_hoa_thi_truong'], 1),
            "eps" => round($res[0]['eps'], 1),
            "bvps" => round($res[0]['bvps'], 1),
            "pb" => round($res[0]['pb'], 1),
            "pe" => round($res[0]['pe'], 1),
            "ps" => round($res[0]['ps'], 1),
            "roaa" => round($res[0]['roaa'], 1),
            "roea" =>  round($res[0]['roea'], 1),
            "roic" => round($res[0]['roic'], 1),
            "tong_no_quy_gan_nhat" => round($res[0]['no_phai_tra'], 1),
            "tong_no_ngan_han_quy_gan_nhat" => round($res[0]['no_phai_tra_ngan_han'], 1),
            "tong_no_dai_han_quy_gan_nhat" => round($res[0]['no_phai_tra_dai_han'], 1),
            "tong_tai_san_quy_gan_nhat" => round($res[0]['tong_cong_tai_san'], 1),
            "tong_tai_san_tai_chinh_quy_gan_nhat" => round($res[0]['tai_san_tai_chinh'], 1),
            "von_chu_so_huu_quy_gan_nhat" => round($res[0]['von_chu_so_huu'], 1),
            "cong_doanh_thu_hoat_dong_ttm" => round($cong_doanh_thu_hoat_dong_ttm, 1),
            "loi_nhuan_gop_ttm" => round($loi_nhuan_gop_ttm, 1),
            "loi_nhuan_khac_ttm" => round($loi_nhuan_khac_ttm, 1),
            "loi_nhuan_truoc_thue_ttm" => round($loi_nhuan_truoc_thue_ttm, 1),
            "loi_nhuan_sau_thue_ttm" => round($lnst_ttm, 1),
            "loi_nhuan_tu_hdtc_ttm" => round($loi_nhuan_tu_hoat_dong_tai_chinh_ttm, 1)
        ];
    }

    public function getCommonValueInsurance($mack)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $type = "insurance";
        $is = 'is_' . $thoigian . '_' . $type;
        $bs = 'bs_' . $thoigian . '_' . $type;
        $price = null;
        try {
            $price = DB::connection('pgsql')
                ->table('stock_live')
                ->where('stockcode', '=', $mack)
                ->addSelect('lastprice')
                ->first();
            $price = $price->lastprice;
        } catch (Exception $e) {
            $price = 0;
        }
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu + bs.loi_ich_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu as von_dieu_le'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as lnst'))
            ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan'))
            ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so as loi_ich_co_dong_thieu_so'))
            ->addSelect(DB::raw('bs.vay_dai_han'))
            ->addSelect(DB::raw('bs.no_phai_tra as no_phai_tra'))
            ->addSelect(DB::raw('bs.no_ngan_han as no_ngan_han'))
            ->addSelect(DB::raw('bs.no_dai_han'))
            ->addSelect(DB::raw('bs.du_phong_nghiep_vu'))
            ->addSelect(DB::raw('bs.cac_khoan_dau_tu_tai_chinh_ngan_han'))
            ->addSelect(DB::raw('bs.cac_khoan_dau_tu_tai_chinh_dai_han'))
            ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_gop'))
            ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh as loi_nhuan_hoat_dong_tai_chinh'))
            ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_khac as loi_nhuan_hoat_dong_khac'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as loi_nhuan_truoc_thue'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                ),
            'm'
        )
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2))) DESC"))
            ->take($limit + 3)
            ->get();
        $ret =  [
            "von_dieu_le" => 0,
            "so_luong_co_phieu" => 0,
            "von_hoa_thi_truong" => 0,
            "eps" => 0,
            "bvps" => 0,
            "pb" => 0,
            "pe" => 0,
            "ps" => 0,
            "roaa" => 0,
            "roea" =>  0,
            "roic" => 0,
            "tong_no_quy_gan_nhat" => 0,
            "tong_no_ngan_han_quy_gan_nhat" => 0,
            "tong_no_dai_han_quy_gan_nhat" => 0,
            "du_phong_nghiep_vu_quy_gan_nhat" => 0,
            "von_chu_so_huu_quy_gan_nhat" => 0,
            "tong_tai_san_quy_gan_nhat" => 0,
            "dau_tu_tai_chinh_ngan_han" => 0,
            "dau_tu_tai_chinh_dai_han" => 0,
            "doanh_thu_thuan_ttm" => 0,
            "loi_nhuan_gop_ttm" => 0,
            "loi_nhuan_hoat_dong_tai_chinh_ttm" => 0,
            "loi_nhuan_khac_ttm" => 0,
            "loi_nhuan_truoc_thue_ttm" => 0,
            "loi_nhuan_sau_thue_ttm" => 0,
        ];
        if (count($res) == 0)
            return $ret;
        $res = json_decode(json_encode($res), true);
        if (!$res) {
            return $ret;
        }
        $data_compare = DB::table("compare_insurance")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('roa_ttm'))
            ->addSelect(DB::raw('roe_ttm'))
            ->addSelect(DB::raw('roic_ttm'))
            ->first();
        try {
            $res[0]['eps'] =  $data_compare->eps ? $data_compare->eps : 0;
            $res[0]['roaa'] = $data_compare->roa_ttm ? $data_compare->roa_ttm : 0;
            $res[0]['roea'] = $data_compare->roe_ttm ? $data_compare->roe_ttm : 0;
            $res[0]['roic'] = $data_compare->roic_ttm ? $data_compare->roic_ttm : 0;
        } catch (Exception $e) {
            $res[0]['eps'] =  0;
            $res[0]['roaa'] =  0;
            $res[0]['roea'] =  0;
            $res[0]['roic'] = 0;
        }
        $lnst_ttm = 0;
        $doanh_thu_thuan_ttm = 0;
        $loi_nhuan_gop_ttm = 0;
        $loi_nhuan_hoat_dong_tai_chinh_ttm = 0;
        $loi_nhuan_hoat_dong_khac_ttm = 0;
        $loi_nhuan_truoc_thue_ttm = 0;
        try {
            $lnst_ttm = $res[0]['lnst'] + $res[1]['lnst'] + $res[2]['lnst'] + $res[3]['lnst'];
            $doanh_thu_thuan_ttm = $res[0]['doanh_thu_thuan'] + $res[1]['doanh_thu_thuan'] + $res[2]['doanh_thu_thuan'] + $res[3]['doanh_thu_thuan'];
            $loi_nhuan_gop_ttm = $res[0]['loi_nhuan_gop'] + $res[1]['loi_nhuan_gop'] + $res[2]['loi_nhuan_gop'] + $res[3]['loi_nhuan_gop'];
            $loi_nhuan_hoat_dong_tai_chinh_ttm = $res[0]['loi_nhuan_hoat_dong_tai_chinh'] + $res[1]['loi_nhuan_hoat_dong_tai_chinh'] + $res[2]['loi_nhuan_hoat_dong_tai_chinh'] + $res[3]['loi_nhuan_hoat_dong_tai_chinh'];
            $loi_nhuan_hoat_dong_khac_ttm = $res[0]['loi_nhuan_hoat_dong_khac'] + $res[1]['loi_nhuan_hoat_dong_khac'] + $res[2]['loi_nhuan_hoat_dong_khac'] + $res[3]['loi_nhuan_hoat_dong_khac'];
            $loi_nhuan_truoc_thue_ttm = $res[0]['loi_nhuan_truoc_thue'] + $res[1]['loi_nhuan_truoc_thue'] + $res[2]['loi_nhuan_truoc_thue'] + $res[3]['loi_nhuan_truoc_thue'];
        } catch (Exception $e) {
        }

        $res[0]['so_luong_co_phieu'] = $res[0]['von_dieu_le'] * 100;
        $res[0]['von_hoa_thi_truong'] = $price * ($res[0]['von_dieu_le'] / 10000);
        $res[0]['pb'] = $res[0]['bvps'] != 0 ? $price / $res[0]['bvps'] : 0;
        $res[0]['pe'] = $res[0]['eps'] != 0 ? $price / $res[0]['eps'] : 0;
        $res[0]['ps'] = $doanh_thu_thuan_ttm != 0 ? $price / $doanh_thu_thuan_ttm : 0;
        // $res[0]['peg'] = $g_eps != 0 ? $res[0]["pe"] / ($g_eps) /100 : 0;

        return [
            "von_dieu_le" => round($res[0]['von_dieu_le'], 1),
            "so_luong_co_phieu" => round($res[0]['so_luong_co_phieu'], 1),
            "von_hoa_thi_truong" => round($res[0]['von_hoa_thi_truong'], 1),
            "eps" => round($res[0]['eps'], 1),
            "bvps" => round($res[0]['bvps'], 1),
            "pb" => round($res[0]['pb'], 1),
            "pe" => round($res[0]['pe'], 1),
            "ps" => round($res[0]['ps'], 1),
            "roaa" => round($res[0]['roaa'], 1),
            "roea" =>  round($res[0]['roea'], 1),
            "roic" => round($res[0]['roic'], 1),
            "tong_no_quy_gan_nhat" => round($res[0]['no_phai_tra'], 1),
            "tong_no_ngan_han_quy_gan_nhat" => round($res[0]['no_ngan_han'], 1),
            "tong_no_dai_han_quy_gan_nhat" => round($res[0]['no_dai_han'], 1),
            "du_phong_nghiep_vu_quy_gan_nhat" => round($res[0]['du_phong_nghiep_vu'], 1),
            "von_chu_so_huu_quy_gan_nhat" => round($res[0]['von_chu_so_huu'], 1),
            "tong_tai_san_quy_gan_nhat" => round($res[0]['tong_cong_tai_san'], 1),
            "dau_tu_tai_chinh_ngan_han" => round($res[0]['cac_khoan_dau_tu_tai_chinh_ngan_han'], 1),
            "dau_tu_tai_chinh_dai_han" => round($res[0]['cac_khoan_dau_tu_tai_chinh_dai_han'], 1),
            "doanh_thu_thuan_ttm" => round($doanh_thu_thuan_ttm, 1),
            "loi_nhuan_gop_ttm" => round($loi_nhuan_gop_ttm, 1),
            "loi_nhuan_hoat_dong_tai_chinh_ttm" => round($loi_nhuan_hoat_dong_tai_chinh_ttm, 1),
            "loi_nhuan_khac_ttm" => round($loi_nhuan_hoat_dong_khac_ttm, 1),
            "loi_nhuan_truoc_thue_ttm" => round($loi_nhuan_truoc_thue_ttm, 1),
            "loi_nhuan_sau_thue_ttm" => round($lnst_ttm, 1),
        ];
    }

    protected function RATE($period, $old_val, $new_val)
    {
        return (pow($new_val / $old_val, 1 / $period) - 1);
    }

    public function getCommonInfo(Request $req)
    {
        $req->merge([
            'mack' => strtoupper($req->input('mack')),
        ]);
        $cong_ty_co_dong = DB::table('cong_ty_co_dong')
            ->where('mack', '=', $req->input('mack'))
            ->get();
        $cong_ty_giao_dich = DB::table('cong_ty_giao_dich')
            ->where('mack', '=', $req->input('mack'))
            ->get();
        $cong_ty_lanh_dao = DB::table('cong_ty_lanh_dao')
            ->where('mack', '=', $req->input('mack'))
            ->get();
        $cong_ty_tong_quan = DB::table('cong_ty_tong_quan')
            ->where('mack', '=', $req->input('mack'))
            ->addSelect("mack")
            ->addSelect("ten_cong_ty")
            ->addSelect("san_niem_yet")
            ->addSelect("dia_chi_tru_so")
            ->addSelect("so_luong_nhan_su")
            ->addSelect("ma_nganh_icb")
            ->addSelect("ten_nganh_icb")
            ->addSelect("cap_nganh_icb")
            ->addSelect("so_huu_nha_nuoc")
            ->addSelect("so_huu_nuoc_ngoai")
            ->addSelect("so_huu_khac")
            ->addSelect("lich_su")
            ->addSelect("nganh_nghe")
            ->first();
        $typeBank = DB::table('danh_sach_mack')
            ->select("nhom")
            ->where('mack', '=', $req->input('mack'))
            ->first();
        $typeBank = $typeBank->nhom;
        $cong_ty_tong_quan = json_decode(json_encode($cong_ty_tong_quan), true);
        $data_mapping_nonbank = [
            "mack" => "Mã CK",
            "ten_cong_ty" => "Tên công ty",
            "san_niem_yet" => "Sàn niêm yết",
            "dia_chi_tru_so" => "Địa chỉ trụ sở",
            "so_luong_nhan_su" => "Số lượng nhân sự",
            "ma_nganh_icb" => "Mã ngành ICB",
            "ten_nganh_icb" => "Tên ngành ICB",
            "cap_nganh_icb" => "Cấp ngành ICB",
            "von_dieu_le" => "Vốn điều lệ",
            "so_luong_co_phieu" => "Số lượng cổ phiếu",
            "von_hoa_thi_truong" => "Vốn hóa thị trường",
            "eps" => "EPS",
            "bvps" => "BVPS",
            "pb" => "P/B",
            "pe" => "P/E",
            "roa" => "ROA",
            "roe" => "ROE",
            "roic" => "ROIC",
            "tong_no_quy_gan_nhat" => "Tổng nợ quý gần nhất",
            "tong_no_ngan_han_quy_gan_nhat" => "Tổng nợ ngắn hạn quý gần nhất",
            "tong_no_dai_han_quy_gan_nhat" => "Tổng nợ dài hạn quý gần nhất",
            "tong_tai_san_quy_gan_nhat" => "Tổng tài sản quý gần nhất",
            "tong_hang_ton_kho_quy_gan_nhat" => "Tổng hàng tồn kho quý gần nhất",
            "von_chu_so_huu_quy_gan_nhat" => "Vốn chủ sở hữu quý gần nhất",
            "doanh_thu_thuan_ttm" => "Doanh thu thuần TTM",
            "loi_nhuan_gop_ttm" => "Lợi nhuận gộp TTM",
            "loi_nhuan_khac_ttm" => "Lợi nhuận khác TTM",
            "loi_nhuan_truoc_thue_ttm" => "Lợi nhuận trước thuế TTM",
            "loi_nhuan_sau_thue_ttm" => "Lợi nhuận sau thuế TTM",
            "loi_nhuan_tu_hdtc_ttm" => "Lợi nhuận từ HDTC TTM"
        ];
        $data_mapping_bank = [
            "mack" => "Mã CK",
            "ten_cong_ty" => "Tên công ty",
            "san_niem_yet" => "Sàn niêm yết",
            "dia_chi_tru_so" => "Địa chỉ trụ sở",
            "so_luong_nhan_su" => "Số lượng nhân sự",
            "ma_nganh_icb" => "Mã ngành ICB",
            "ten_nganh_icb" => "Tên ngành ICB",
            "cap_nganh_icb" => "Cấp ngành ICB",
            "von_dieu_le" => "Vốn điều lệ",
            "so_luong_co_phieu" => "Số lượng cổ phiếu",
            "von_hoa_thi_truong" => "Vốn hóa thị trường",
            "eps" => "EPS",
            "bvps" => "BVPS",
            "pb" => "P/B",
            "pe" => "P/E",
            "roaa" => "ROAA",
            "roea" =>  "ROEA",
            "nim" => "NIM",
            "tong_no_quy_gan_nhat" => "Tổng nợ quý gần nhất",
            "tong_tien_gui_khach_hang_quy_gan_nhat" => "Tổng tiền gửi khách hàng quý gần nhất",
            "tong_du_no_cho_vay_quy_gan_nhat" => "Tổng dư nợ cho vay quý gần nhất",
            "tong_tai_san_quy_gan_nhat" => "Tổng tài sản quý gần nhất",
            "tong_tai_san_co_sinh_lai_quy_gan_nhat" => "Tổng tài sản có sinh lãi quý gần nhất",
            "vcsh_quy_gan_nhat" => "Vốn chủ sở hữu quý gần nhất",
            "doanh_thu_hoat_dong_ttm" => "Doanh thu hoạt động TTM",
            "loi_nhuan_hoat_dong_ttm" => "Lợi nhuận hoạt động TTM",
            "loi_nhuan_truoc_thue_ttm" => "Lợi nhuận trước thuế TTM",
            "loi_nhuan_sau_thue_ttm" => "Lợi nhuận sau thuế TTM",
        ];
        $data_label = [];
        switch ($typeBank) {
            case "nonbank":
                $cong_ty_tong_quan = array_merge($cong_ty_tong_quan, $this->getCommonValueNonbank($req->input('mack')));
                $data_label = $data_mapping_nonbank;
                break;
            case "bank":
                $cong_ty_tong_quan = array_merge($cong_ty_tong_quan, $this->getCommonValueBank($req->input('mack')));
                $data_label = $data_mapping_bank;
                break;
            case "stock":
                $cong_ty_tong_quan = array_merge($cong_ty_tong_quan, $this->getCommonValueStock($req->input('mack')));
                break;
            case "insurance":
                $cong_ty_tong_quan = array_merge($cong_ty_tong_quan, $this->getCommonValueInsurance($req->input('mack')));
                break;
            default:
                break;
        }
        // dd($cong_ty_tong_quan);
        // $this->getCommonValueNonbank($req->input('mack'));
        $res = [];
        $res["cong_ty_co_dong"] = $cong_ty_co_dong;
        $res["cong_ty_giao_dich"] = $cong_ty_giao_dich;
        $res["cong_ty_lanh_dao"] = $cong_ty_lanh_dao;
        $res["cong_ty_tong_quan"] = [
            "value" => $cong_ty_tong_quan,
            "label" => $data_label,
        ];
        $res["type_bank"] = $typeBank;
        return response()->json($res);
    }
}
