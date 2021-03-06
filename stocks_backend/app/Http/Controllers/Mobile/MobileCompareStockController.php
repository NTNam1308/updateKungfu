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
            "mack" => ["title" => "M?? CK", "unit" => ""],
            "thoigian" => ["title" => "Th???i gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Gi?? th??? tr?????ng", "unit" => "VND"],
            "von_hoa" => ["title" => "V???n h??a", "unit" => "VND"],
            "eps" => ["title" => "EPS", "unit" => "VND"],
            "bvps" => ["title" => "BVPS", "unit" => "VND"],
            "pe" => ["title" => "PE", "unit" => "L???N"],
            "peg" => ["title" => "PEG", "unit" => "L???N"],
            "pb" => ["title" => "PB", "unit" => "L???N"],
            "dtt_ttm" => ["title" => "Doanh thu thu???n", "unit" => "VND"],
            "lng_ttm" => ["title" => "L???i nhu???n g???p", "unit" => "VND"],
            "lnr_ttm" => ["title" => "L???i nhu???n r??ng", "unit" => "VND"],
            "blng_ttm" => ["title" => "Bi??n l???i nhu???n g???p", "unit" => "%"],
            "blnr_ttm" => ["title" => "Bi??n l???i nhu???n r??ng", "unit" => "%"],
            "roa_ttm" => ["title" => "ROA", "unit" => "%"],
            "roe_ttm" => ["title" => "ROE", "unit" => "%"],
            "roic_ttm" => ["title" => "ROIC", "unit" => "%"],
            "vqts_ttm" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "ocflnt_ttm" => ["title" => "OCF/L???i nhu???n thu???n", "unit" => "L???N"],
            "fcf_ttm" => ["title" => "D??ng ti???n t??? do (FCF)", "unit" => "VND"],
            "ti_le_thanh_toan_hien_hanh" => ["title" => "T??? l??? thanh to??n hi???n h??nh", "unit" => "L???N"],
            "ti_le_thanh_toan_nhanh" => ["title" => "T??? l??? thanh to??n nhanh", "unit" => "L???N"],
            "de" => ["title" => "T??? l??? D/E", "unit" => "L???N"],
            "so_ngay_binh_quan_vong_quay_hang_ton_kho" => ["title" => "S??? ng??y b??nh qu??n v??ng quay HTK", "unit" => "NG??Y"],
            "so_ngay_binh_quan_vong_quay_khoan_phai_thu" => ["title" => "S??? ng??y b??nh qu??n v??ng quay KPThu", "unit" => "NG??Y"],
            "so_ngay_binh_quan_vong_quay_khoan_phai_tra" => ["title" => "S??? ng??y b??nh qu??n v??ng quay KPTr???", "unit" => "NG??Y"],
            "roa" => ["title" => "ROA", "unit" => "%"],
            "vqts" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "tsln" => ["title" => "T??? su???t l???i nhu???n", "unit" => "%"],
            "roe" => ["title" => "ROE", "unit" => "%"],
            "dbtc" => ["title" => "????n b???y t??i ch??nh", "unit" => "L???N"],
            "vqts2" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "bien_ebit" => ["title" => "Bi??n EBIT", "unit" => "%"],
            "gnls" => ["title" => "G??nh n???ng l??i su???t", "unit" => "%"],
            "gnt" => ["title" => "G??nh n???ng thu???", "unit" => "%"],
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
            "mack" => ["title" => "M?? CK", "unit" => ""],
            "thoigian" => ["title" => "Th???i gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Gi?? th??? tr?????ng", "unit" => "VND"],
            "von_hoa" => ["title" => "V???n h??a", "unit" => "VND"],
            'eps' => ["title" => "EPS", "unit" => "VND"],
            'gia_tri_so_sach' => ["title" => "Gi?? tr??? s??? s??ch", "unit" => "VND"],
            'pe' => ["title" => "PE", "unit" => "L???N"],
            'peg' => ["title" => "PEG", "unit" => "L???N"],
            'pb' => ["title" => "PB", "unit" => "L???N"],
            'tong_doanh_thu_hoat_dong_ttm' => ["title" => "T???ng Doanh thu ho???t ?????ng", "unit" => "VND"],
            'chi_phi_hoat_dong_ttm' => ["title" => "Chi ph?? ho???t ?????ng", "unit" => "VND"],
            'loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung_ttm' => ["title" => "L???i nhu???n t??? H??KD tr?????c chi ph?? d??? ph??ng r???i ro t??n d???ng", "unit" => "VND"],
            'lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm' => ["title" => "L???i nhu???n sau thu???", "unit" => "VND"],
            'du_no_cho_vay_tts_co' => ["title" => "D?? n??? cho vay/T???ng t??i s???n C??", "unit" => "L???N"],
            'vcsh_tvhd' => ["title" => "V???n ch??? s??? h???u/T???ng v???n huy ?????ng", "unit" => "L???N"],
            'vcsh_tts_co' => ["title" => "V???n ch??? s??? h???u/T???ng t??i s???n C??", "unit" => "L???N"],
            'roea' => ["title" => "ROEA", "unit" => "%"],
            'roaa_ttm' => ["title" => "ROAA", "unit" => "%"],
            'yea_ttm' => ["title" => "YOEA", "unit" => "%"],
            'cof_ttm' => ["title" => "COF", "unit" => "%"],
            'nim_ttm' => ["title" => "NIM", "unit" => "%"],
            'cir_ttm' => ["title" => "CIR", "unit" => "%"],
            'casa_ttm' => ["title" => "T??? l??? CASA", "unit" => "%"],
            'ty_le_bao_no_xau_ttm' => ["title" => "T??? l??? bao n??? x???u", "unit" => "%"],
            'ty_le_no_xau_npl_ttm' => ["title" => "T??? l??? n??? x???u - NPL", "unit" => "%"],
            'lai_du_thu_ttm' => ["title" => "L??i d??? thu", "unit" => "%"],
            'car_y' => ["title" => "H??? s??? CAR", "unit" => "%"],
            'ldr_ttm' => ["title" => "LDR", "unit" => "%"],
            'dprrtd_tdn' => ["title" => "D??? ph??ng r???i ro t??n d???ng/T???ng d?? n???", "unit" => "%"],
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
            "mack" => ["title" => "M?? CK", "unit" => ""],
            "thoigian" => ["title" => "Th???i gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Gi?? th??? tr?????ng", "unit" => "VND"],
            "von_hoa" => ["title" => "V???n h??a", "unit" => "VND"],
            "gia_tri_doanh_nghiep" => ["title" => "Gi?? tr??? doanh nghi???p", "unit" => "VND"],
            "eps" => ["title" => "EPS", "unit" => "VND"],
            "bvps" => ["title" => "BVPS", "unit" => "VND"],
            "pe" => ["title" => "PE", "unit" => "L???N"],
            "peg" => ["title" => "PEG", "unit" => "L???N"],
            "pb" => ["title" => "PB", "unit" => "L???N"],
            "cong_doanh_thu_hoat_dong_ttm" => ["title" => "C???ng doanh thu ho???t ?????ng", "unit" => "VND"],
            "doanh_thu_moi_gioi_ttm" => ["title" => "Doanh thu m??i gi???i", "unit" => "VND"],
            "doanh_thu_cho_vay_ttm" => ["title" => "Doanh thu cho vay", "unit" => "VND"],
            "doanh_thu_bao_lanh_ttm" => ["title" => "Doanh thu b???o l??nh", "unit" => "VND"],
            "doanh_thu_tu_doanh_ttm" => ["title" => "Doanh thu t??? doanh", "unit" => "VND"],
            "loi_nhuan_moi_gioi_ttm" => ["title" => "L???i nhu???n m??i gi???i", "unit" => "VND"],
            "loi_nhuan_cho_vay_ttm" => ["title" => "L???i nhu???n cho vay", "unit" => "VND"],
            "loi_nhuan_bao_lanh_ttm" => ["title" => "L???i nhu???n b???o l??nh", "unit" => "VND"],
            "loi_nhuan_tu_doanh_ttm" => ["title" => "L???i nhu???n t??? doanh", "unit" => "VND"],
            "loi_nhuan_gop_ttm" => ["title" => "L???i nhu???n g???p", "unit" => "VND"],
            "loi_nhuan_ke_toan_sau_thue_tndn_ttm" => ["title" => "L???i nhu???n sau thu???", "unit" => "VND"],
            "bien_loi_nhuan_gop" => ["title" => "Bi???n l???i nhu???n g???p", "unit" => "%"],
            "bien_loi_nhuan_sau_thue" => ["title" => "Bi??n l???i nhu???n sau thu???", "unit" => "%"],
            "roaa" => ["title" => "ROAA", "unit" => "%"],
            "roea" => ["title" => "ROEA", "unit" => "%"],
            "ti_le_du_phong_suy_giam_gia_tri_tstc" => ["title" => "T??? l??? d??? ph??ng suy gi???m gi?? tr??? t??i s???n t??i ch??nh", "unit" => "%"],
            "ti_le_du_phong_suy_giam_gia_tri_khoan_phai_thu" => ["title" => "T??? l??? d??? ph??ng suy gi???m gi?? tr??? kho???n ph???i thu", "unit" => "%"],
            "ti_le_du_no_margin_vcsh" => ["title" => "T??? l??? D?? n??? margin/VCSH", "unit" => "%"],
            "de" => ["title" => "T??? l??? D/E", "unit" => "L???N"],
            "roa" => ["title" => "ROA", "unit" => "%"],
            "vong_quay_tai_san" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "ti_suat_loi_nhuan" => ["title" => "T??? su???t l???i nhu???n", "unit" => "%"],
            "roe" => ["title" => "ROE", "unit" => "%"],
            "don_bay_tai_chinh" => ["title" => "????n b???y t??i ch??nh", "unit" => "L???N"],
            "vong_quay_tai_san2" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "bien_ebit2" => ["title" => "Bi??n EBIT", "unit" => "%"],
            "ganh_nang_lai_suat" => ["title" => "G??nh n???ng l??i su???t", "unit" => "%"],
            "ganh_nang_thue" => ["title" => "G??nh n???ng thu???", "unit" => "%"],
            "bpt_tnv" => ["title" => "N??? ph???i tr???/T???ng ngu???n v???n", "unit" => "L???N"],
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
            "mack" => ["title" => "M?? CK", "unit" => ""],
            "thoigian" => ["title" => "Th???i gian", "unit" => ""],
            "gia_thi_truong" => ["title" => "Gi?? th??? tr?????ng", "unit" => "VND"],
            "von_hoa" => ["title" => "V???n h??a", "unit" => "VND"],
            "eps" => ["title" => "EPS", "unit" => "VND"],
            "bvps" => ["title" => "BVPS", "unit" => "VND"],
            "pe" => ["title" => "PE", "unit" => "L???N"],
            "pb" => ["title" => "PB", "unit" => "L???N"],
            "doanh_thu_thuan_ttm" => ["title" => "Doanh thu thu???n t??? H??KD B???o hi???m", "unit" => "VND"],
            "doanh_thu_hoat_dong_tai_chinh_ttm" => ["title" => "Doanh thu t??? ho???t ?????ng t??i ch??nh", "unit" => "VND"],
            "tong_doanh_thu_hoat_dong_ttm" => ["title" => "T???ng doanh thu ho???t ?????ng", "unit" => "VND"],
            "loi_nhuan_tu_hoat_dong_bao_hiem_ttm" => ["title" => "L???i nhu???n ho???t ?????ng b???o hi???m", "unit" => "VND"],
            "loi_nhuan_hoat_dong_tai_chinh_ttm" => ["title" => "L???i nhu???n ho???t ?????ng t??i ch??nh", "unit" => "VND"],
            "lntt_ttm" => ["title" => "L???i nhu???n r??ng", "unit" => "VND"],
            "bien_loi_nhuan_gop" => ["title" => "Bi??n l???i nhu???n g???p", "unit" => "%"],
            "bien_loi_nhuan_hoat_dong_tai_chinh" => ["title" => "Bi??n l???i nhu???n ho???t ?????ng t??i ch??nh", "unit" => "%"],
            "bien_loi_nhuan_rong" => ["title" => "Bi??n l???i nhu???n r??ng", "unit" => "%"],
            "roa_ttm" => ["title" => "ROA", "unit" => "%"],
            "roe_ttm" => ["title" => "ROE", "unit" => "%"],
            "roic_ttm" => ["title" => "ROIC", "unit" => "%"],
            "vong_quay_tai_san" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "ocf" => ["title" => "D??ng ti???n t??? H??KD ch??nh - OCF", "unit" => "VND"],
            "ocf_loi_nhuan_sau_thue" => ["title" => "OCF/LNST", "unit" => "L???N"],
            "fcf" => ["title" => "D??ng ti???n t??? do - FCF", "unit" => "VND"],
            "ty_le_thanh_toan_hien_hanh" => ["title" => "T??? l??? thanh to??n hi???n h??nh", "unit" => "L???N"],
            "ty_le_thanh_toan_du_phong" => ["title" => "T??? l??? thanh to??n d??? ph??ng", "unit" => "L???N"],
            "ty_le_de" => ["title" => "T??? l??? D/E", "unit" => "L???N"],
            "du_phong_nghiep_vu_bao_hiem" => ["title" => "D??? ph??ng nghi???p v??? b???o hi???m", "unit" => "VND"],
            "ty_le_dp_dtbh_ttm" => ["title" => "T??? l??? d??? ph??ng/doanh thu b???o hi???m", "unit" => "L???N"],
            "roa2" => ["title" => "ROA", "unit" => "%"],
            "vong_quay_tai_san2" => ["title" => "V??ng quay t??i s???n", "unit" => "VND"],
            "tsln" => ["title" => "T??? su???t l???i nhu???n", "unit" => "%"],
            "roe2" => ["title" => "ROE", "unit" => "%"],
            "dbtc" => ["title" => "????n b???y t??i ch??nh", "unit" => "L???N"],
            "vqts2" => ["title" => "V??ng quay t??i s???n", "unit" => "L???N"],
            "bien_ebit2" => ["title" => "Bi??n EBIT", "unit" => "%"],
            "gnls" => ["title" => "G??nh n???ng l??i su???t", "unit" => "%"],
            "gnt" => ["title" => "G??nh n???ng thu???", "unit" => "%"],
            "tai_san_ngan_han_tong_so_tai_san" => ["title" => "T??i s???n ng???n h???n/T???ng s??? t??i s???n", "unit" => "L???N"],
            "tai_san_dai_han_tong_so_tai_san" => ["title" => "T??i s???n d??i h???n/T???ng s??? t??i s???n", "unit" => "L???N"],
            "no_phai_tra_tong_nguon_von" => ["title" => "N??? ph???i tr???/T???ng ngu???n v???n", "unit" => "L???N"],
            "nguon_von_chu_so_huu_tong_nguon_von" => ["title" => "Ngu???n v???n ch??? s??? h???u/T???ng ngu???n v???n", "unit" => "L???N"],
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
