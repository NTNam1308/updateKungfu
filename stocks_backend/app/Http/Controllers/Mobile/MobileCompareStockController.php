<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// set_error_handler(function () {
//     throw new Exception('Ach!');
// });

class MobileCompareStockController extends Controller
{
    public function compareNonbank(Request $req)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $typeBank = "nonbank";
        $arrMack = $req->input('mack');
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $minMack = 5;
        $arr_info_mack = [];
        $arr = [];
        $list_items = [];
        $arrMack[0] = strtoupper($arrMack[0]);
        if (count($arrMack) == 1) {
            $res_quarter = DB::table($is)
                ->addSelect('thoigian')
                ->distinct()
                ->take(4)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $arr_quarter = [];
            foreach ($res_quarter as $row) {
                array_push($arr_quarter, $row->thoigian);
            }
            $res = DB::table($is . ' as is')
                ->join('cong_ty_tong_quan', 'cong_ty_tong_quan.mack', '=', 'is.mack')
                ->join('danh_sach_mack', 'danh_sach_mack.mack', '=', 'cong_ty_tong_quan.mack')
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('cong_ty_tong_quan.ten_cong_ty'))
                ->addSelect(DB::raw('sum(is.doanh_thu_thuan) as ttm'))
                ->whereNotIn('is.mack', $arrMack)
                ->whereIn('is.thoigian', $arr_quarter)
                ->orderByRaw('ttm desc')
                ->groupByRaw('is.mack,cong_ty_tong_quan.ten_cong_ty')
                ->take($minMack - count($arrMack))
                ->get();
            foreach ($res as $value) {
                array_push($arrMack, $value->mack);
                array_push($arr_info_mack, [
                    "mack" => $value->mack,
                    "ten_cong_ty" => $value->ten_cong_ty
                ]);
            }
            // $arr['list_info_mack'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (float) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_nonbank")
                ->addSelect(DB::raw("mack"))
                ->addSelect(DB::raw("thoigian"))
                ->addSelect(DB::raw("gia_thi_truong"))
                ->addSelect(DB::raw("von_hoa"))
                ->addSelect(DB::raw("eps"))
                ->addSelect(DB::raw("bvps"))
                ->addSelect(DB::raw("pe"))
                ->addSelect(DB::raw("peg"))
                ->addSelect(DB::raw("pb"))
                ->addSelect(DB::raw("dtt_ttm"))
                ->addSelect(DB::raw("lng_ttm"))
                ->addSelect(DB::raw("lnr_ttm"))
                ->addSelect(DB::raw("blng_ttm"))
                ->addSelect(DB::raw("blnr_ttm"))
                ->addSelect(DB::raw("roa_ttm"))
                ->addSelect(DB::raw("roe_ttm"))
                ->addSelect(DB::raw("roic_ttm"))
                ->addSelect(DB::raw("vqts_ttm"))
                ->addSelect(DB::raw("ocflnt_ttm"))
                ->addSelect(DB::raw("fcf_ttm"))
                ->addSelect(DB::raw("ti_le_thanh_toan_hien_hanh"))
                ->addSelect(DB::raw("ti_le_thanh_toan_nhanh"))
                ->addSelect(DB::raw("de"))
                ->addSelect(DB::raw("so_ngay_binh_quan_vong_quay_hang_ton_kho"))
                ->addSelect(DB::raw("so_ngay_binh_quan_vong_quay_khoan_phai_thu"))
                ->addSelect(DB::raw("so_ngay_binh_quan_vong_quay_khoan_phai_tra"))
                ->addSelect(DB::raw("roa"))
                ->addSelect(DB::raw("vqts"))
                ->addSelect(DB::raw("tsln"))
                ->addSelect(DB::raw("roe"))
                ->addSelect(DB::raw("dbtc"))
                ->addSelect(DB::raw("vqts2"))
                ->addSelect(DB::raw("bien_ebit"))
                ->addSelect(DB::raw("gnls"))
                ->addSelect(DB::raw("gnt"))
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item["von_hoa"] = $price * $item['von_hoa'];
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['pb'] = $item['pb'] != 0 ? $price / $item['pb'] : 0;
            $item['peg'] = $item['peg'] != 0 ? ($item['pe'] / $item['peg']) : 0;
            $item["von_hoa"] = $item['von_hoa'] / 1000;
            $item["dtt_ttm"] = $item['dtt_ttm'] / 1000;
            $item["lng_ttm"] = $item['lng_ttm'] / 1000;
            $item["lnr_ttm"] = $item['lnr_ttm'] / 1000;
            $item["fcf_ttm"] = $item['fcf_ttm'] / 1000;
            array_push($list_items, $item);
        }
        $data_mapping = [
            "mack" => ["title" => "Mã CK", "unit" => ""],
            "thoigian" => ["title" => "Thời gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Giá thị trường", "unit" => "VND"],
            "von_hoa" => ["title" => "Vốn hóa", "unit" => "VND"],
            "eps" => ["title" => "EPS", "unit" => "VND"],
            "bvps" => ["title" => "BVPS", "unit" => "VND"],
            "pe" => ["title" => "PE", "unit" => "LẦN"],
            "peg" => ["title" => "PEG", "unit" => "LẦN"],
            "pb" => ["title" => "PB", "unit" => "LẦN"],
            "dtt_ttm" => ["title" => "Doanh thu thuần", "unit" => "VND"],
            "lng_ttm" => ["title" => "Lợi nhuận gộp", "unit" => "VND"],
            "lnr_ttm" => ["title" => "Lợi nhuận ròng", "unit" => "VND"],
            "blng_ttm" => ["title" => "Biên lợi nhuận gộp", "unit" => "%"],
            "blnr_ttm" => ["title" => "Biên lợi nhuận ròng", "unit" => "%"],
            "roa_ttm" => ["title" => "ROA", "unit" => "%"],
            "roe_ttm" => ["title" => "ROE", "unit" => "%"],
            "roic_ttm" => ["title" => "ROIC", "unit" => "%"],
            "vqts_ttm" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "ocflnt_ttm" => ["title" => "OCF/Lợi nhuận thuần", "unit" => "LẦN"],
            "fcf_ttm" => ["title" => "Dòng tiền tự do (FCF)", "unit" => "VND"],
            "ti_le_thanh_toan_hien_hanh" => ["title" => "Tỉ lệ thanh toán hiện hành", "unit" => "LẦN"],
            "ti_le_thanh_toan_nhanh" => ["title" => "Tỉ lệ thanh toán nhanh", "unit" => "LẦN"],
            "de" => ["title" => "Tỷ lệ D/E", "unit" => "LẦN"],
            "so_ngay_binh_quan_vong_quay_hang_ton_kho" => ["title" => "Số ngày bình quân vòng quay HTK", "unit" => "NGÀY"],
            "so_ngay_binh_quan_vong_quay_khoan_phai_thu" => ["title" => "Số ngày bình quân vòng quay KPThu", "unit" => "NGÀY"],
            "so_ngay_binh_quan_vong_quay_khoan_phai_tra" => ["title" => "Số ngày bình quân vòng quay KPTrả", "unit" => "NGÀY"],
            "roa" => ["title" => "ROA", "unit" => "%"],
            "vqts" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "tsln" => ["title" => "Tỉ suất lợi nhuận", "unit" => "%"],
            "roe" => ["title" => "ROE", "unit" => "%"],
            "dbtc" => ["title" => "Đòn bẩy tài chính", "unit" => "LẦN"],
            "vqts2" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "bien_ebit" => ["title" => "Biên EBIT", "unit" => "%"],
            "gnls" => ["title" => "Gánh nặng lãi suất", "unit" => "%"],
            "gnt" => ["title" => "Gánh nặng thuế", "unit" => "%"],
        ];
        $arr["list_items"] = $this::rotateTable($list_items);
        return [
            "data_compare" => $arr,
            "data_mapping" => $data_mapping
        ];
    }

    public function compareBank(Request $req)
    {
        $thoigian = 'quarter';
        $typeBank = "bank";
        $arrMack = $req->input('mack');
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $tm = 'tm_' . $thoigian . '_' . $typeBank;
        $minMack = 5;
        $arr_info_mack = [];
        $arr = [];
        $list_items = [];
        $arrMack[0] = strtoupper($arrMack[0]);
        if (count($arrMack) == 1) {
            $res_quarter = DB::table($is)
                ->addSelect('thoigian')
                ->distinct()
                ->take(4)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $arr_quarter = [];
            foreach ($res_quarter as $row) {
                array_push($arr_quarter, $row->thoigian);
            }
            $res = DB::table($is . ' as is')
                ->join('cong_ty_tong_quan', 'cong_ty_tong_quan.mack', '=', 'is.mack')
                ->join('danh_sach_mack', 'danh_sach_mack.mack', '=', 'cong_ty_tong_quan.mack')
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('cong_ty_tong_quan.ten_cong_ty'))
                ->addSelect(DB::raw('sum(is.thu_nhap_lai_thuan) as ttm'))
                ->whereNotIn('is.mack', $arrMack)
                ->whereIn('is.thoigian', $arr_quarter)
                ->orderByRaw('ttm desc')
                ->groupByRaw('is.mack,cong_ty_tong_quan.ten_cong_ty')
                ->take($minMack - count($arrMack))
                ->get();
            foreach ($res as $value) {
                array_push($arrMack, $value->mack);
                array_push($arr_info_mack, [
                    "mack" => $value->mack,
                    "ten_cong_ty" => $value->ten_cong_ty
                ]);
            }
            // $arr['list_mack_info'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (float) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_bank")
                ->addSelect(DB::raw('mack'))
                ->addSelect(DB::raw('thoigian'))
                ->addSelect(DB::raw('gia_thi_truong'))
                ->addSelect(DB::raw('von_hoa'))
                ->addSelect(DB::raw('eps'))
                ->addSelect(DB::raw('gia_tri_so_sach'))
                ->addSelect(DB::raw('pe'))
                ->addSelect(DB::raw('peg'))
                ->addSelect(DB::raw('pb'))
                ->addSelect(DB::raw('tong_doanh_thu_hoat_dong_ttm'))
                ->addSelect(DB::raw('chi_phi_hoat_dong_ttm'))
                ->addSelect(DB::raw('loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung_ttm'))
                ->addSelect(DB::raw('lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm'))
                ->addSelect(DB::raw('du_no_cho_vay_tts_co'))
                ->addSelect(DB::raw('vcsh_tvhd'))
                ->addSelect(DB::raw('vcsh_tts_co'))
                ->addSelect(DB::raw('roea'))
                ->addSelect(DB::raw('roaa_ttm'))
                ->addSelect(DB::raw('yea_ttm'))
                ->addSelect(DB::raw('cof_ttm'))
                ->addSelect(DB::raw('nim_ttm'))
                ->addSelect(DB::raw('cir_ttm'))
                ->addSelect(DB::raw('casa_ttm'))
                ->addSelect(DB::raw('ty_le_bao_no_xau_ttm'))
                ->addSelect(DB::raw('ty_le_no_xau_npl_ttm'))
                ->addSelect(DB::raw('lai_du_thu_ttm'))
                ->addSelect(DB::raw('car_y'))
                ->addSelect(DB::raw('ldr_ttm'))
                ->addSelect(DB::raw('dprrtd_tdn'))
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['peg'] = $item['peg'] != 0 ? ($item['pe'] / $item['peg']) : 0;
            $item['gia_tri_so_sach'] = $item['von_hoa'] != 0 ? ($item['gia_tri_so_sach'] / $item['von_hoa']) : 0;
            $item['pb'] = $item['gia_tri_so_sach'] != 0 ? $price / $item['gia_tri_so_sach'] : 0;
            $item["von_hoa"] = $price * $item['von_hoa'];
            $item["von_hoa"] = $item['von_hoa'] / 1000;
            $item["tong_doanh_thu_hoat_dong_ttm"] = $item['tong_doanh_thu_hoat_dong_ttm'] / 1000;
            $item["chi_phi_hoat_dong_ttm"] = $item['chi_phi_hoat_dong_ttm'] / 1000;
            $item["loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung_ttm"] = $item['loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung_ttm'] / 1000;
            $item["lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm"] = $item['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm'] / 1000;
            
            array_push($list_items, $item);
        }
        $data_mapping = [
            "mack" => ["title" => "Mã CK", "unit" => ""],
            "thoigian" => ["title" => "Thời gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Giá thị trường", "unit" => "VND"],
            "von_hoa" => ["title" => "Vốn hóa", "unit" => "VND"],
            'eps' => ["title" => "EPS", "unit" => "VND"],
            'gia_tri_so_sach' => ["title" => "Giá trị sổ sách", "unit" => "VND"],
            'pe' => ["title" => "PE", "unit" => "LẦN"],
            'peg' => ["title" => "PEG", "unit" => "LẦN"],
            'pb' => ["title" => "PB", "unit" => "LẦN"],
            'tong_doanh_thu_hoat_dong_ttm' => ["title" => "Tổng Doanh thu hoạt động", "unit" => "VND"],
            'chi_phi_hoat_dong_ttm' => ["title" => "Chi phí hoạt động", "unit" => "VND"],
            'loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung_ttm' => ["title" => "Lợi nhuận từ HĐKD trước chi phí dự phòng rủi ro tín dụng", "unit" => "VND"],
            'lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm' => ["title" => "Lợi nhuận sau thuế", "unit" => "VND"],
            'du_no_cho_vay_tts_co' => ["title" => "Dư nợ cho vay/Tổng tài sản Có", "unit" => "LẦN"],
            'vcsh_tvhd' => ["title" => "Vốn chủ sở hữu/Tổng vốn huy động", "unit" => "LẦN"],
            'vcsh_tts_co' => ["title" => "Vốn chủ sở hữu/Tổng tài sản Có", "unit" => "LẦN"],
            'roea' => ["title" => "ROEA", "unit" => "%"],
            'roaa_ttm' => ["title" => "ROAA", "unit" => "%"],
            'yea_ttm' => ["title" => "YOEA", "unit" => "%"],
            'cof_ttm' => ["title" => "COF", "unit" => "%"],
            'nim_ttm' => ["title" => "NIM", "unit" => "%"],
            'cir_ttm' => ["title" => "CIR", "unit" => "%"],
            'casa_ttm' => ["title" => "Tỷ lệ CASA", "unit" => "%"],
            'ty_le_bao_no_xau_ttm' => ["title" => "Tỷ lệ bao nợ xấu", "unit" => "%"],
            'ty_le_no_xau_npl_ttm' => ["title" => "Tỷ lệ nợ xấu - NPL", "unit" => "%"],
            'lai_du_thu_ttm' => ["title" => "Lãi dự thu", "unit" => "%"],
            'car_y' => ["title" => "Hệ số CAR", "unit" => "%"],
            'ldr_ttm' => ["title" => "LDR", "unit" => "%"],
            'dprrtd_tdn' => ["title" => "Dự phòng rủi ro tín dụng/Tổng dư nợ", "unit" => "%"],
        ];
        $arr["list_items"] = $this::rotateTable($list_items);
        return [
            "data_compare" => $arr,
            "data_mapping" => $data_mapping
        ];
    }

    public function compareStock(Request $req)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $typeBank = "stock";
        $arrMack = $req->input('mack');
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $minMack = 5;
        $arr_info_mack = [];
        $arr = [];
        $list_items = [];
        $arrMack[0] = strtoupper($arrMack[0]);
        if (count($arrMack) == 1) {
            $res_quarter = DB::table($is)
                ->addSelect('thoigian')
                ->distinct()
                ->take(4)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $arr_quarter = [];
            foreach ($res_quarter as $row) {
                array_push($arr_quarter, $row->thoigian);
            }
            $res = DB::table($is . ' as is')
                ->join('cong_ty_tong_quan', 'cong_ty_tong_quan.mack', '=', 'is.mack')
                ->join('danh_sach_mack', 'danh_sach_mack.mack', '=', 'cong_ty_tong_quan.mack')
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('cong_ty_tong_quan.ten_cong_ty'))
                ->addSelect(DB::raw('sum(is.cong_doanh_thu_hoat_dong) as ttm'))
                ->whereNotIn('is.mack', $arrMack)
                ->whereIn('is.thoigian', $arr_quarter)
                ->orderByRaw('ttm desc')
                ->groupByRaw('is.mack,cong_ty_tong_quan.ten_cong_ty')
                ->take($minMack - count($arrMack))
                ->get();
            foreach ($res as $value) {
                array_push($arrMack, $value->mack);
                array_push($arr_info_mack, [
                    "mack" => $value->mack,
                    "ten_cong_ty" => $value->ten_cong_ty
                ]);
            }
            // $arr['danh_sach_ma'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (float) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_stock")
                ->addSelect(DB::raw("mack"))
                ->addSelect(DB::raw("thoigian"))
                ->addSelect(DB::raw("gia_thi_truong"))
                ->addSelect(DB::raw("von_hoa"))
                ->addSelect(DB::raw("gia_tri_doanh_nghiep"))
                ->addSelect(DB::raw("eps"))
                ->addSelect(DB::raw("bvps"))
                ->addSelect(DB::raw("pe"))
                ->addSelect(DB::raw("peg"))
                ->addSelect(DB::raw("pb"))
                ->addSelect(DB::raw("cong_doanh_thu_hoat_dong_ttm"))
                ->addSelect(DB::raw("doanh_thu_moi_gioi_ttm"))
                ->addSelect(DB::raw("doanh_thu_cho_vay_ttm"))
                ->addSelect(DB::raw("doanh_thu_bao_lanh_ttm"))
                ->addSelect(DB::raw("doanh_thu_tu_doanh_ttm"))
                ->addSelect(DB::raw("loi_nhuan_moi_gioi_ttm"))
                ->addSelect(DB::raw("loi_nhuan_cho_vay_ttm"))
                ->addSelect(DB::raw("loi_nhuan_bao_lanh_ttm"))
                ->addSelect(DB::raw("loi_nhuan_tu_doanh_ttm"))
                ->addSelect(DB::raw("loi_nhuan_gop_ttm"))
                ->addSelect(DB::raw("loi_nhuan_ke_toan_sau_thue_tndn_ttm"))
                ->addSelect(DB::raw("bien_loi_nhuan_gop"))
                ->addSelect(DB::raw("bien_loi_nhuan_sau_thue"))
                ->addSelect(DB::raw("roaa"))
                ->addSelect(DB::raw("roea"))
                ->addSelect(DB::raw("ti_le_du_phong_suy_giam_gia_tri_tstc"))
                ->addSelect(DB::raw("ti_le_du_phong_suy_giam_gia_tri_khoan_phai_thu"))
                ->addSelect(DB::raw("ti_le_du_no_margin_vcsh"))
                ->addSelect(DB::raw("de"))
                ->addSelect(DB::raw("roa"))
                ->addSelect(DB::raw("vong_quay_tai_san"))
                ->addSelect(DB::raw("ti_suat_loi_nhuan"))
                ->addSelect(DB::raw("roe"))
                ->addSelect(DB::raw("don_bay_tai_chinh"))
                ->addSelect(DB::raw("vong_quay_tai_san2"))
                ->addSelect(DB::raw("bien_ebit2"))
                ->addSelect(DB::raw("ganh_nang_lai_suat"))
                ->addSelect(DB::raw("ganh_nang_thue"))
                ->addSelect(DB::raw("bpt_tnv"))
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item["von_hoa"] = $price * $item['von_hoa'];
            $item['gia_tri_doanh_nghiep'] = $item['von_hoa'] + $item['gia_tri_doanh_nghiep'];
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['pb'] = $item['pb'] != 0 ? $price / $item['pb'] : 0;
            // $item['ev_ebit'] =  $item['ev_ebit']  != 0 ? $item['gia_tri_doanh_nghiep'] / $item['ev_ebit']  : 0;
            $item['peg'] = $item['peg'] !=0 ? ($item['pe'] / $item['peg']) : 0;
            array_push($list_items, $item);
        }
        $data_mapping = [
            "mack" => ["title" => "Mã CK", "unit" => ""],
            "thoigian" => ["title" => "Thời gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Giá thị trường", "unit" => "VND"],
            "von_hoa" => ["title" => "Vốn hóa", "unit" => "VND"],
            "gia_tri_doanh_nghiep" => ["title" => "Giá trị doanh nghiệp", "unit" => "VND"],
            "eps" => ["title" => "EPS", "unit" => "VND"],
            "bvps" => ["title" => "BVPS", "unit" => "VND"],
            "pe" => ["title" => "PE", "unit" => "LẦN"],
            "peg" => ["title" => "PEG", "unit" => "LẦN"],
            "pb" => ["title" => "PB", "unit" => "LẦN"],
            "cong_doanh_thu_hoat_dong_ttm" => ["title" => "Cộng doanh thu hoạt động", "unit" => "VND"],
            "doanh_thu_moi_gioi_ttm" => ["title" => "Doanh thu môi giới", "unit" => "VND"],
            "doanh_thu_cho_vay_ttm" => ["title" => "Doanh thu cho vay", "unit" => "VND"],
            "doanh_thu_bao_lanh_ttm" => ["title" => "Doanh thu bảo lãnh", "unit" => "VND"],
            "doanh_thu_tu_doanh_ttm" => ["title" => "Doanh thu tự doanh", "unit" => "VND"],
            "loi_nhuan_moi_gioi_ttm" => ["title" => "Lợi nhuận môi giới", "unit" => "VND"],
            "loi_nhuan_cho_vay_ttm" => ["title" => "Lợi nhuận cho vay", "unit" => "VND"],
            "loi_nhuan_bao_lanh_ttm" => ["title" => "Lợi nhuận bảo lãnh", "unit" => "VND"],
            "loi_nhuan_tu_doanh_ttm" => ["title" => "Lợi nhuận tự doanh", "unit" => "VND"],
            "loi_nhuan_gop_ttm" => ["title" => "Lợi nhuận gộp", "unit" => "VND"],
            "loi_nhuan_ke_toan_sau_thue_tndn_ttm" => ["title" => "Lợi nhuận sau thuế", "unit" => "VND"],
            "bien_loi_nhuan_gop" => ["title" => "Biện lợi nhuận gộp", "unit" => "%"],
            "bien_loi_nhuan_sau_thue" => ["title" => "Biên lợi nhuận sau thuế", "unit" => "%"],
            "roaa" => ["title" => "ROAA", "unit" => "%"],
            "roea" => ["title" => "ROEA", "unit" => "%"],
            "ti_le_du_phong_suy_giam_gia_tri_tstc" => ["title" => "Tỷ lệ dự phòng suy giảm giá trị tài sản tài chính", "unit" => "%"],
            "ti_le_du_phong_suy_giam_gia_tri_khoan_phai_thu" => ["title" => "Tỷ lệ dự phòng suy giảm giá trị khoản phải thu", "unit" => "%"],
            "ti_le_du_no_margin_vcsh" => ["title" => "Tỷ lệ Dư nợ margin/VCSH", "unit" => "%"],
            "de" => ["title" => "Tỷ lệ D/E", "unit" => "LẦN"],
            "roa" => ["title" => "ROA", "unit" => "%"],
            "vong_quay_tai_san" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "ti_suat_loi_nhuan" => ["title" => "Tỉ suất lợi nhuận", "unit" => "%"],
            "roe" => ["title" => "ROE", "unit" => "%"],
            "don_bay_tai_chinh" => ["title" => "Đòn bẩy tài chính", "unit" => "LẦN"],
            "vong_quay_tai_san2" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "bien_ebit2" => ["title" => "Biên EBIT", "unit" => "%"],
            "ganh_nang_lai_suat" => ["title" => "Gánh nặng lãi suất", "unit" => "%"],
            "ganh_nang_thue" => ["title" => "Gánh nặng thuế", "unit" => "%"],
            "bpt_tnv" => ["title" => "Nợ phải trả/Tổng nguồn vốn", "unit" => "LẦN"],
        ];
        $arr["list_items"] = $this::rotateTable($list_items);
        return [
            "data_compare" => $arr,
            "data_mapping" => $data_mapping
        ];
    }

    public function compareInsurance(Request $req)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $typeBank = "insurance";
        $arrMack = $req->input('mack');
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $minMack = 5;
        $arr_info_mack = [];
        $arr = [];
        $list_items = [];
        $arrMack[0] = strtoupper($arrMack[0]);
        // $type_direct_insurance = DB::table('insurance_type')
        //     ->where('mack', $arrMack[0])
        //     ->first();
        // $type_direct_insurance = $type_direct_insurance->type;
        if (count($arrMack) == 1) {
            $res_quarter = DB::table($is)
                ->addSelect('thoigian')
                ->distinct()
                ->take(4)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $arr_quarter = [];
            foreach ($res_quarter as $row) {
                array_push($arr_quarter, $row->thoigian);
            }
            $res = DB::table($is . ' as is')
                ->join('cong_ty_tong_quan', 'cong_ty_tong_quan.mack', '=', 'is.mack')
                ->join('danh_sach_mack', 'danh_sach_mack.mack', '=', 'is.mack')
                // ->join('insurance_type', 'danh_sach_mack.mack', '=', 'is.mack')
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('cong_ty_tong_quan.ten_cong_ty'))
                ->addSelect(DB::raw('sum(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem) as ttm'))
                // ->where('insurance_type.type', '=', $type_direct_insurance)
                ->whereNotIn('is.mack', $arrMack)
                ->whereIn('is.thoigian', $arr_quarter)
                ->orderByRaw('ttm desc')
                ->groupByRaw('is.mack,cong_ty_tong_quan.ten_cong_ty')
                ->take($minMack - count($arrMack))
                ->get();
            foreach ($res as $value) {
                array_push($arrMack, $value->mack);
                array_push($arr_info_mack, [
                    "mack" => $value->mack,
                    "ten_cong_ty" => $value->ten_cong_ty
                ]);
            }
            // $arr['danh_sach_ma'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (float) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_insurance")
                ->addSelect(DB::raw("mack"))
                ->addSelect(DB::raw("thoigian"))
                ->addSelect(DB::raw("gia_thi_truong"))
                ->addSelect(DB::raw("von_hoa"))
                ->addSelect(DB::raw("eps"))
                ->addSelect(DB::raw("bvps"))
                ->addSelect(DB::raw("pe"))
                ->addSelect(DB::raw("pb"))
                ->addSelect(DB::raw("doanh_thu_thuan_ttm"))
                ->addSelect(DB::raw("doanh_thu_hoat_dong_tai_chinh_ttm"))
                ->addSelect(DB::raw("tong_doanh_thu_hoat_dong_ttm"))
                ->addSelect(DB::raw("loi_nhuan_tu_hoat_dong_bao_hiem_ttm"))
                ->addSelect(DB::raw("loi_nhuan_hoat_dong_tai_chinh_ttm"))
                ->addSelect(DB::raw("lntt_ttm"))
                ->addSelect(DB::raw("bien_loi_nhuan_gop"))
                ->addSelect(DB::raw("bien_loi_nhuan_hoat_dong_tai_chinh"))
                ->addSelect(DB::raw("bien_loi_nhuan_rong"))
                ->addSelect(DB::raw("roa_ttm"))
                ->addSelect(DB::raw("roe_ttm"))
                ->addSelect(DB::raw("roic_ttm"))
                ->addSelect(DB::raw("vong_quay_tai_san"))
                ->addSelect(DB::raw("ocf"))
                ->addSelect(DB::raw("ocf_loi_nhuan_sau_thue"))
                ->addSelect(DB::raw("fcf"))
                ->addSelect(DB::raw("ty_le_thanh_toan_hien_hanh"))
                ->addSelect(DB::raw("ty_le_thanh_toan_du_phong"))
                ->addSelect(DB::raw("ty_le_de"))
                ->addSelect(DB::raw("du_phong_nghiep_vu_bao_hiem"))
                ->addSelect(DB::raw("ty_le_dp_dtbh_ttm"))
                ->addSelect(DB::raw("roa2"))
                ->addSelect(DB::raw("vong_quay_tai_san2"))
                ->addSelect(DB::raw("tsln"))
                ->addSelect(DB::raw("roe2"))
                ->addSelect(DB::raw("dbtc"))
                ->addSelect(DB::raw("vqts2"))
                ->addSelect(DB::raw("bien_ebit2"))
                ->addSelect(DB::raw("gnls"))
                ->addSelect(DB::raw("gnt"))
                ->addSelect(DB::raw("tai_san_ngan_han_tong_so_tai_san"))
                ->addSelect(DB::raw("tai_san_dai_han_tong_so_tai_san"))
                ->addSelect(DB::raw("no_phai_tra_tong_nguon_von"))
                ->addSelect(DB::raw("nguon_von_chu_so_huu_tong_nguon_von"))
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item["von_hoa"] = $price * $item['von_hoa'];
            // $item['gia_tri_doanh_nghiep'] = $item['von_hoa'] + $item['gia_tri_doanh_nghiep'];
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['pb'] = $item['pb'] != 0 ? $price / $item['pb'] : 0;
            // $item['evebit'] =  $item['evebit']  != 0 ? $item['gia_tri_doanh_nghiep'] / ($item['evebit'] / 1000)  : 0;
            // $item['evebitda'] = $item['evebitda'] != 0 ? $item['gia_tri_doanh_nghiep'] / ($item['evebitda'] / 1000) : 0;
            // $item['peg'] = $item['peg'] != 0 ? ($item['pe'] / $item['peg'] / 10000)  : 0;
            array_push($list_items, $item);
        }
        $data_mapping = [
            "mack" => ["title" => "Mã CK", "unit" => ""],
            "thoigian" => ["title" => "Thời gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Giá thị trường", "unit" => "VND"],
            "von_hoa" => ["title" => "Vốn hóa", "unit" => "VND"],
            "eps" => ["title" => "EPS", "unit" => "VND"],
            "bvps" => ["title" => "BVPS", "unit" => "VND"],
            "pe" => ["title" => "PE", "unit" => "LẦN"],
            "pb" => ["title" => "PB", "unit" => "LẦN"],
            "doanh_thu_thuan_ttm" => ["title" => "Doanh thu thuần từ HĐKD Bảo hiểm", "unit" => "VND"],
            "doanh_thu_hoat_dong_tai_chinh_ttm" => ["title" => "Doanh thu từ hoạt động tài chính", "unit" => "VND"],
            "tong_doanh_thu_hoat_dong_ttm" => ["title" => "Tổng doanh thu hoạt động", "unit" => "VND"],
            "loi_nhuan_tu_hoat_dong_bao_hiem_ttm" => ["title" => "Lợi nhuận hoạt động bảo hiểm", "unit" => "VND"],
            "loi_nhuan_hoat_dong_tai_chinh_ttm" => ["title" => "Lợi nhuận hoạt động tài chính", "unit" => "VND"],
            "lntt_ttm" => ["title" => "Lợi nhuận ròng", "unit" => "VND"],
            "bien_loi_nhuan_gop" => ["title" => "Biên lợi nhuận gộp", "unit" => "%"],
            "bien_loi_nhuan_hoat_dong_tai_chinh" => ["title" => "Biên lợi nhuận hoạt động tài chính", "unit" => "%"],
            "bien_loi_nhuan_rong" => ["title" => "Biên lợi nhuận ròng", "unit" => "%"],
            "roa_ttm" => ["title" => "ROA", "unit" => "%"],
            "roe_ttm" => ["title" => "ROE", "unit" => "%"],
            "roic_ttm" => ["title" => "ROIC", "unit" => "%"],
            "vong_quay_tai_san" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "ocf" => ["title" => "Dòng tiền từ HĐKD chính - OCF", "unit" => "VND"],
            "ocf_loi_nhuan_sau_thue" => ["title" => "OCF/LNST", "unit" => "LẦN"],
            "fcf" => ["title" => "Dòng tiền tự do - FCF", "unit" => "VND"],
            "ty_le_thanh_toan_hien_hanh" => ["title" => "Tỉ lệ thanh toán hiện hành", "unit" => "LẦN"],
            "ty_le_thanh_toan_du_phong" => ["title" => "Tỉ lệ thanh toán dự phòng", "unit" => "LẦN"],
            "ty_le_de" => ["title" => "Tỷ lệ D/E", "unit" => "LẦN"],
            "du_phong_nghiep_vu_bao_hiem" => ["title" => "Dự phòng nghiệp vụ bảo hiểm", "unit" => "VND"],
            "ty_le_dp_dtbh_ttm" => ["title" => "Tỷ lệ dự phòng/doanh thu bảo hiểm", "unit" => "LẦN"],
            "roa2" => ["title" => "ROA", "unit" => "%"],
            "vong_quay_tai_san2" => ["title" => "Vòng quay tài sản", "unit" => "VND"],
            "tsln" => ["title" => "Tỉ suất lợi nhuận", "unit" => "%"],
            "roe2" => ["title" => "ROE", "unit" => "%"],
            "dbtc" => ["title" => "Đòn bẩy tài chính", "unit" => "LẦN"],
            "vqts2" => ["title" => "Vòng quay tài sản", "unit" => "LẦN"],
            "bien_ebit2" => ["title" => "Biên EBIT", "unit" => "%"],
            "gnls" => ["title" => "Gánh nặng lãi suất", "unit" => "%"],
            "gnt" => ["title" => "Gánh nặng thuế", "unit" => "%"],
            "tai_san_ngan_han_tong_so_tai_san" => ["title" => "Tài sản ngắn hạn/Tổng số tài sản", "unit" => "LẦN"],
            "tai_san_dai_han_tong_so_tai_san" => ["title" => "Tài sản dài hạn/Tổng số tài sản", "unit" => "LẦN"],
            "no_phai_tra_tong_nguon_von" => ["title" => "Nợ phải trả/Tổng nguồn vốn", "unit" => "LẦN"],
            "nguon_von_chu_so_huu_tong_nguon_von" => ["title" => "Nguồn vốn chủ sở hữu/Tổng nguồn vốn", "unit" => "LẦN"],
        ];
        $arr["list_items"] = $this::rotateTable($list_items);
        return [
            "data_compare" => $arr,
            "data_mapping" => $data_mapping
        ];
    }

    public function getData(Request $req)
    {
        $arrMack = $req->input('mack');
        $arrMack[0] = strtoupper($arrMack[0]);
        $nhom = DB::table("danh_sach_mack")
                ->addSelect("nhom")
                ->where("mack",$arrMack[0])
                ->first();
        $nhom = $nhom->nhom;
        switch ($nhom) {
            case "nonbank":
                return $this->compareNonbank($req);
                break;
            case "bank":
                return $this->compareBank($req);
                break;
            case "stock":
                return $this->compareStock($req);
                break;
            case "insurance":
                return $this->compareInsurance($req);
                break;
            default:
                return "";
                break;
        }
    }

    protected static function rotateTable($arr)
    {
        $arr_return = [];
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr_return[array_keys($arr[0])[$i]] = array_column($arr, array_keys($arr[0])[$i]);
        }
        return $arr_return;
    }
}
