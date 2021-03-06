<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MobileStockDataRawController extends Controller
{
    public function getDataTableIS($mack, $type_time, $page, $item_per_page, $type_stock)
    {
        $table_data = DB::table("is_" . $type_time . "_" . $type_stock . " as is")
            ->leftJoin("bs_" . $type_time . "_" . $type_stock . " as bs", function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->offset($page * $item_per_page)
            ->take($item_per_page)
            ->where("is.mack", $mack);
        $column_select = null;
        $data_mapping = [];
        switch ($type_stock) {
            case "nonbank":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('is.thoigian'))
                    ->addSelect(DB::raw('doanh_thu_thuan'))
                    ->addSelect(DB::raw('gia_von_ban_hang'))
                    ->addSelect(DB::raw('loi_nhuan_gop'))
                    ->addSelect(DB::raw('doanh_thu_hoat_dong_tai_chinh'))
                    ->addSelect(DB::raw('chi_phi_tai_chinh'))
                    ->addSelect(DB::raw('chi_phi_ban_hang'))
                    ->addSelect(DB::raw('chi_phi_quan_ly_doanh_nghiep'))
                    ->addSelect(DB::raw('loi_nhuan_tu_hoat_dong_kinh_doanh'))
                    ->addSelect(DB::raw('loi_nhuan_khac'))
                    ->addSelect(DB::raw('loi_nhuan_trong_cong_ty_lien_ket'))
                    ->addSelect(DB::raw('tong_loi_nhuan_ke_toan_truoc_thue'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_tndn'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "doanh_thu_thuan" => "Doanh thu thu???n",
                    "gia_von_ban_hang" => "Gi?? v???n h??ng b??n",
                    "loi_nhuan_gop" => "L???i nhu???n g???p",
                    "doanh_thu_hoat_dong_tai_chinh" => "Doanh thu ho???t ?????ng t??i ch??nh",
                    "chi_phi_tai_chinh" => "Chi ph?? t??i ch??nh",
                    "chi_phi_ban_hang" => "Chi ph?? b??n h??ng",
                    "chi_phi_quan_ly_doanh_nghiep" => "Chi ph?? qu???n l?? doanh nghi???p",
                    "loi_nhuan_tu_hoat_dong_kinh_doanh" => "L???i nhu???n thu???n t??? ho???t ?????ng kinh doanh",
                    "loi_nhuan_khac" => "L???i nhu???n kh??c",
                    "loi_nhuan_trong_cong_ty_lien_ket" => "Ph???n l???i nhu???n ho???c l??? trong c??ng ty li??n k???t li??n doanh",
                    "tong_loi_nhuan_ke_toan_truoc_thue" => "T???ng l???i nhu???n k??? to??n tr?????c thu???",
                    "loi_nhuan_sau_thue_tndn" => "L???i nhu???n sau thu??? thu nh???p doanh nghi???p",
                    "loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me" => "L???i nhu???n sau thu??? c???a c??? ????ng c???a c??ng ty m???",
                    "eps" => "EPS",
                ];
                break;
            case "bank":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('is.thoigian'))
                    ->addSelect(DB::raw('thu_nhap_lai_thuan'))
                    ->addSelect(DB::raw('lai_lo_thuan_tu_hoat_dong_dich_vu'))
                    ->addSelect(DB::raw('lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi'))
                    ->addSelect(DB::raw('lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh'))
                    ->addSelect(DB::raw('lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu'))
                    ->addSelect(DB::raw('lai_lo_thuan_tu_hoat_dong_khac'))
                    ->addSelect(DB::raw('thu_nhap_tu_hoat_dong_gop_von_mua_co_phan'))
                    ->addSelect(DB::raw('chi_phi_hoat_dong'))
                    ->addSelect(DB::raw('loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung'))
                    ->addSelect(DB::raw('chi_phi_du_phong_rui_ro_tin_dung'))
                    ->addSelect(DB::raw('tong_loi_nhuan_truoc_thue'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_thu_nhap_doanh_nghiep'))
                    ->addSelect(DB::raw('loi_ich_cua_co_dong_thieu_so_va_co_tuc_uu_dai'))
                    ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/(bs.von_dieu_le)*10000 as eps'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "thu_nhap_lai_thuan" => "Thu nh???p l??i thu???n",
                    "lai_lo_thuan_tu_hoat_dong_dich_vu" => "L???i nhu???n thu???n ho???t ?????ng d???ch v???",
                    "lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi" => "L???i nhu???n thu???n ho???t ?????ng kinh doanh ngo???i h???i, v??ng",
                    "lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh" => "L???i nhu???n thu???n mua b??n ch???ng kho??n kinh doanh",
                    "lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu" => "L???i nhu???n thu???n mua b??n ch???ng kho??n ?????u t??",
                    "lai_lo_thuan_tu_hoat_dong_khac" => "L???i nhu???n thu???n ho???t ?????ng kh??c",
                    "thu_nhap_tu_hoat_dong_gop_von_mua_co_phan" => "Thu nh???p t??? g??p v???n, mua c??? phi???u",
                    "chi_phi_hoat_dong" => "Chi ph?? ho???t ?????ng",
                    "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung" => "L???i nhu???n thu???n ho???t ?????ng kinh doanh tr?????c chi ph?? d??? ph??ng",
                    "chi_phi_du_phong_rui_ro_tin_dung" => "Chi ph?? d??? ph??ng r???i ro t??n d???ng",
                    "tong_loi_nhuan_truoc_thue" => "L???i nhu???n tr?????c thu???",
                    "loi_nhuan_sau_thue_thu_nhap_doanh_nghiep" => "L???i nhu???n sau thu???",
                    "loi_ich_cua_co_dong_thieu_so_va_co_tuc_uu_dai" => "L???i nhu???n sau thu??? c???a c??? ????ng ng??n h??ng m???",
                    "eps" => "EPS",
                ];
                break;
            case "stock":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('is.thoigian'))
                    ->addSelect(DB::raw('cong_doanh_thu_hoat_dong'))
                    ->addSelect(DB::raw('cong_chi_phi_hoat_dong'))
                    ->addSelect(DB::raw('cong_doanh_thu_hoat_dong - cong_chi_phi_hoat_dong as loi_nhuan_gop_hdkd'))
                    ->addSelect(DB::raw('chi_phi_quan_ly_cong_ty_chung_khoan'))
                    ->addSelect(DB::raw('ket_qua_hoat_dong'))
                    ->addSelect(DB::raw('cong_ket_qua_hoat_dong_khac'))
                    ->addSelect(DB::raw('tong_loi_nhuan_ke_toan_truoc_thue'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_phan_bo_cho_chu_so_huu'))
                    ->addSelect(DB::raw('loi_nhuan_ke_toan_sau_thue_tndn'))
                    ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn/(bs.von_gop_cua_chu_so_huu/10000) as eps'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "cong_doanh_thu_hoat_dong" => "C???ng doanh thu ho???t ?????ng",
                    "cong_chi_phi_hoat_dong" => "C???ng chi ph?? ho???t ?????ng",
                    "loi_nhuan_gop_hdkd" => "L???i nhu???n g???p H??KD",
                    "chi_phi_quan_ly_cong_ty_chung_khoan" => "Chi ph?? qu???n l?? c??ng ty ch???ng kho??n",
                    "ket_qua_hoat_dong" => "K???t qu??? ho???t ?????ng",
                    "cong_ket_qua_hoat_dong_khac" => "C???ng k???t qu??? ho???t ?????ng kh??c",
                    "tong_loi_nhuan_ke_toan_truoc_thue" => "L???i nhu???n tr?????c thu???",
                    "loi_nhuan_sau_thue_phan_bo_cho_chu_so_huu" => "L???i nhu???n sau thu???",
                    "loi_nhuan_ke_toan_sau_thue_tndn" => "L???i nhu???n sau thu??? c???a c??? ????ng c??ng ty m???",
                    "eps" => "EPS",
                ];
                break;
            case "insurance":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('is.thoigian'))
                    ->addSelect(DB::raw('doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem'))
                    ->addSelect(DB::raw('tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                    ->addSelect(DB::raw('loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_'))
                    ->addSelect(DB::raw('loi_nhuan_thuan_hoat_dong_kinh_doanh_bao_hiem'))
                    ->addSelect(DB::raw('loi_nhuan_hoat_dong_tai_chinh'))
                    ->addSelect(DB::raw('tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_thu_nhap_doanh_nghiep'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_cua_co_dong_cong_ty_me'))
                    ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem" => "Doanh thu thu???n ho???t ?????ng kinh doanh b???o hi???m",
                    "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem" => "T???ng chi tr???c ti???p ho???t ?????ng kinh doanh b???o hi???m",
                    "loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_" => "L???i nhu???n g???p ho???t ?????ng kinh doanh b???o hi???m",
                    "loi_nhuan_thuan_hoat_dong_kinh_doanh_bao_hiem" => "L???i nhu???n thu???n ho???t ?????ng kinh doanh b???o hi???m",
                    "loi_nhuan_hoat_dong_tai_chinh" => "L???i nhu???n ho???t ?????ng t??i ch??nh",
                    "tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep" => "T???ng l???i nhu???n tr?????c thu??? thu nh???p doanh nghi???p",
                    "loi_nhuan_sau_thue_thu_nhap_doanh_nghiep" => "L???i nhu???n sau thu??? thu nh???p doanh nghi???p",
                    "loi_nhuan_sau_thue_cua_co_dong_cong_ty_me" => "L???i nhu???n sau thu??? c???a c??? ????ng c??ng ty m???",
                    "eps" => "EPS",
                ];
                break;
            default:
                # code...
                break;
        }
        $table_data->columns = $column_select->columns;
        if ($type_time == "quarter") {
            $table_data = $table_data->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC');
        } else {
            $table_data = $table_data->orderBy("is.thoigian", "desc");
        }
        return [
            "data_table" => $table_data->get(),
            "data_mapping" => $data_mapping
        ];
    }

    public function getDataTableBS($mack, $type_time, $page, $item_per_page, $type_stock)
    {
        $table_data = DB::table("bs_" . $type_time . "_" . $type_stock)
            ->offset($page * $item_per_page)
            ->take($item_per_page)
            ->where("mack", $mack);
        $column_select = null;
        $data_mapping = [];
        switch ($type_stock) {
            case "nonbank":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('tai_san_luu_dong_va_dau_tu_ngan_han'))
                    ->addSelect(DB::raw('tien_va_cac_khoan_tuong_duong_tien'))
                    ->addSelect(DB::raw('cac_khoan_dau_tu_tai_chinh_ngan_han'))
                    ->addSelect(DB::raw('cac_khoan_phai_thu_ngan_han'))
                    ->addSelect(DB::raw('tong_hang_ton_kho'))
                    ->addSelect(DB::raw('tong_tai_san_ngan_han_khac'))
                    ->addSelect(DB::raw('tai_san_co_dinh_va_dau_tu_dai_han'))
                    ->addSelect(DB::raw('tai_san_co_dinh'))
                    ->addSelect(DB::raw('bat_dong_san_dau_tu'))
                    ->addSelect(DB::raw('cac_khoan_dau_tu_tai_chinh_dai_han'))
                    ->addSelect(DB::raw('tong_cong_tai_san'))
                    ->addSelect(DB::raw('no_phai_tra'))
                    ->addSelect(DB::raw('no_ngan_han'))
                    ->addSelect(DB::raw('no_dai_han'))
                    ->addSelect(DB::raw('nguon_von_chu_so_huu'))
                    ->addSelect(DB::raw('von_dau_tu_cua_chu_so_huu'))
                    ->addSelect(DB::raw('thang_du_gop_co_phan'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_chua_phan_phoi'))
                    ->addSelect(DB::raw('loi_ich_cua_co_dong_khong_kiem_soat'))
                    ->addSelect(DB::raw('tong_cong_nguon_von'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "tai_san_luu_dong_va_dau_tu_ngan_han" => "T??i s???n l??u ?????ng v?? ?????u t?? ng???n h???n",
                    "tien_va_cac_khoan_tuong_duong_tien" => "Ti???n v?? c??c kho???n t????ng ??????ng ti???n",
                    "cac_khoan_dau_tu_tai_chinh_ngan_han" => "C??c kho???n ?????u t?? t??i ch??nh ng???n h???n",
                    "cac_khoan_phai_thu_ngan_han" => "C??c kho???n ph???i thu ng???n h???n",
                    "tong_hang_ton_kho" => "T???ng h??ng t???n kho",
                    "tong_tai_san_ngan_han_khac" => "T??i s???n ng???n h???n kh??c",
                    "tai_san_co_dinh_va_dau_tu_dai_han" => "T??i s???n c??? ?????nh v?? ?????u t?? d??i h???n",
                    "tai_san_co_dinh" => "T??i s???n c??? ?????nh",
                    "bat_dong_san_dau_tu" => "B???t ?????ng s???n ?????u t??",
                    "cac_khoan_dau_tu_tai_chinh_dai_han" => "C??c kho???n ?????u t?? t??i ch??nh d??i h???n",
                    "tong_cong_tai_san" => "T???ng c???ng t??i s???n",
                    "no_phai_tra" => "N??? ph???i tr???",
                    "no_ngan_han" => "N??? ng???n h???n",
                    "no_dai_han" => "N??? d??i h???n",
                    "nguon_von_chu_so_huu" => "Ngu???n v???n ch??? s??? h???u",
                    "von_dau_tu_cua_chu_so_huu" => "V???n ?????u t?? c???a ch??? s??? h???u",
                    "thang_du_gop_co_phan" => "Th???ng d?? v???n c??? ph???n",
                    "loi_nhuan_sau_thue_chua_phan_phoi" => "L???i nhu???n sau thu??? ch??a ph??n ph???i",
                    "loi_ich_cua_co_dong_khong_kiem_soat" => "L???i ??ch c???a c??? ????ng kh??ng ki???m so??t",
                    "tong_cong_nguon_von" => "T???ng c???ng ngu???n v???n",
                ];
                break;
            case "bank":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('tien_mat_chung_tu_co_gia_tri_ngoai_te_kim_loai_quy_da_quy'))
                    ->addSelect(DB::raw('tien_gui_tai_nhnn'))
                    ->addSelect(DB::raw('tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac'))
                    ->addSelect(DB::raw('chung_khoan_kinh_doanh'))
                    ->addSelect(DB::raw('cac_cong_cu_tai_chinh_phai_sinh_va_cac_tai_san_tai_chinh_khac'))
                    ->addSelect(DB::raw('cho_vay_khach_hang'))
                    ->addSelect(DB::raw('chung_khoan_dau_tu'))
                    ->addSelect(DB::raw('gop_von_dau_tu_dai_han'))
                    ->addSelect(DB::raw('tai_san_co_dinh'))
                    ->addSelect(DB::raw('bat_dong_san_dau_tu'))
                    ->addSelect(DB::raw('tai_san_co_khac'))
                    ->addSelect(DB::raw('tong_cong_tai_san'))
                    ->addSelect(DB::raw('cac_khoan_no_chinh_phu_va_nhnn'))
                    ->addSelect(DB::raw('tien_gui_va_cho_vay_cac_tctd_khac'))
                    ->addSelect(DB::raw('von_tai_tro_uy_thac_dau_tu_ma_ngan_hang_chiu_rui_ro'))
                    ->addSelect(DB::raw('phat_hanh_giay_to_co_gia'))
                    ->addSelect(DB::raw('von_va_cac_quy'))
                    ->addSelect(DB::raw('loi_ich_cua_co_dong_thieu_so'))
                    ->addSelect(DB::raw('tong_cong_nguon_von'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "tien_mat_chung_tu_co_gia_tri_ngoai_te_kim_loai_quy_da_quy" => "Ti???n m???t, v??ng b???c, ???? qu??",
                    "tien_gui_tai_nhnn" => "Ti???n g???i t???i NHNN",
                    "tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac" => "Ti???n, v??ng g???i v?? cho vay t???i c??c TCTD kh??c",
                    "chung_khoan_kinh_doanh" => "Ch???ng kho??n KD",
                    "cac_cong_cu_tai_chinh_phai_sinh_va_cac_tai_san_tai_chinh_khac" => "C??ng c??? TC ph??i sinh v?? TSTC kh??c",
                    "cho_vay_khach_hang" => "Cho vay v?? cho thu?? TC kh??ch h??ng",
                    "chung_khoan_dau_tu" => "Ch???ng kho??n ?????u t??",
                    "gop_von_dau_tu_dai_han" => "G??p v???n, ?????u t?? d??i h???n",
                    "tai_san_co_dinh" => "T??i s???n c??? ?????nh",
                    "bat_dong_san_dau_tu" => "B???t ?????ng s???n ?????u t??",
                    "tai_san_co_khac" => "TS c?? kh??c",
                    "tong_cong_tai_san" => "T???ng TS",
                    "cac_khoan_no_chinh_phu_va_nhnn" => "N??? Ch??nh ph??? & NHNN",
                    "tien_gui_va_cho_vay_cac_tctd_khac" => "Ti???n g???i vay c??c TCTD kh??c",
                    "von_tai_tro_uy_thac_dau_tu_ma_ngan_hang_chiu_rui_ro" => "V???n t??i tr???, ???y th??c ?????u t??, cho vay m?? TCTD ch???u r???i ro",
                    "phat_hanh_giay_to_co_gia" => "Ph??t h??nh gi???y t??? c?? gi??",
                    "von_va_cac_quy" => "V???n v?? c??c qu???",
                    "loi_ich_cua_co_dong_thieu_so" => "L???i ??ch C??TS",
                    "tong_cong_nguon_von" => "T???ng NPT & VCSH",
                ];
                break;
            case "stock":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('tai_san_ngan_han'))
                    ->addSelect(DB::raw('tien_va_cac_khoan_tuong_duong_tien'))
                    ->addSelect(DB::raw('cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl'))
                    ->addSelect(DB::raw('cac_khoan_dau_tu_giu_den_ngay_dao_han_htm'))
                    ->addSelect(DB::raw('cac_tai_san_tai_chinh_san_sang_de_ban_afs'))
                    ->addSelect(DB::raw('cac_khoan_phai_thu'))
                    ->addSelect(DB::raw('tai_san_dai_han'))
                    ->addSelect(DB::raw('cac_khoan_dau_tu'))
                    ->addSelect(DB::raw('tai_san_co_dinh'))
                    ->addSelect(DB::raw('bat_dong_san_dau_tu'))
                    ->addSelect(DB::raw('tong_cong_tai_san'))
                    ->addSelect(DB::raw('no_phai_tra'))
                    ->addSelect(DB::raw('no_phai_tra_ngan_han'))
                    ->addSelect(DB::raw('no_phai_tra_dai_han'))
                    ->addSelect(DB::raw('tong_von_chu_so_huu'))
                    ->addSelect(DB::raw('von_gop_cua_chu_so_huu'))
                    ->addSelect(DB::raw('thang_du_von_co_phan'))
                    ->addSelect(DB::raw('loi_nhuan_chua_phan_phoi'))
                    ->addSelect(DB::raw('loi_ich_cua_co_dong_khong_nam_quyen_kiem_soat'))
                    ->addSelect(DB::raw('tong_cong_no_phai_tra_va_von_chu_so_huu'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    'tai_san_ngan_han' => "T??i s???n ng???n h???n",
                    'tien_va_cac_khoan_tuong_duong_tien' => "Ti???n v?? c??c kho???n t????ng ??????ng ti???n",
                    'cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl' => "C??c t??i s???n t??i ch??nh ghi nh???n th??ng qua l??i l??? (FVTPL)",
                    'cac_khoan_dau_tu_giu_den_ngay_dao_han_htm' => "C??c kho???n ?????u t??  gi??? ?????n ng??y ????o h???n (HTM)",
                    'cac_tai_san_tai_chinh_san_sang_de_ban_afs' => "C??c t??i s???n t??i ch??nh s???n s??ng ????? b??n (AFS)",
                    'cac_khoan_phai_thu' => "C??c kho???n ph???i thu",
                    'tai_san_dai_han' => "T??i s???n d??i h???n",
                    'cac_khoan_dau_tu' => "C??c kho???n ?????u t??",
                    'tai_san_co_dinh' => "T??i s???n c??? ?????nh",
                    'bat_dong_san_dau_tu' => "B???t ?????ng s???n ?????u t??",
                    'tong_cong_tai_san' => "T???ng c???ng t??i s???n",
                    'no_phai_tra' => "N??? ph???i tr???",
                    'no_phai_tra_ngan_han' => "N??? ph???i tr??? ng???n h???n",
                    'no_phai_tra_dai_han' => "N??? ph???i tr??? d??i h???n",
                    'tong_von_chu_so_huu' => "V???n ch??? s??? h???u",
                    'von_gop_cua_chu_so_huu' => "V???n g??p c???a ch??? s??? h???u",
                    'thang_du_von_co_phan' => "Th???ng d?? v???n c??? ph???n",
                    'loi_nhuan_chua_phan_phoi' => "L???i nhu???n ch??a ph??n ph???i",
                    'loi_ich_cua_co_dong_khong_nam_quyen_kiem_soat' => "L???i ??ch c???a c??? ????ng kh??ng n???m quy???n ki???m so??t",
                    'tong_cong_no_phai_tra_va_von_chu_so_huu' => "T???ng ngu???n v???n",
                ];
                break;
            case "insurance":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('tai_san_luu_dong_va_dau_tu_ngan_han'))
                    ->addSelect(DB::raw('tien'))
                    ->addSelect(DB::raw('cac_khoan_dau_tu_tai_chinh_ngan_han'))
                    ->addSelect(DB::raw('cac_khoan_phai_thu'))
                    ->addSelect(DB::raw('hang_ton_kho'))
                    ->addSelect(DB::raw('tai_san_ngan_han_khac'))
                    ->addSelect(DB::raw('tai_san_co_dinh_va_dau_tu_dai_han'))
                    ->addSelect(DB::raw('tai_san_co_dinh'))
                    ->addSelect(DB::raw('bat_dong_san_dau_tu'))
                    ->addSelect(DB::raw('cac_khoan_dau_tu_tai_chinh_dai_han'))
                    ->addSelect(DB::raw('cac_khoan_ky_quy_ky_cuoc_dai_han'))
                    ->addSelect(DB::raw('tong_cong_tai_san'))
                    ->addSelect(DB::raw('no_phai_tra'))
                    ->addSelect(DB::raw('no_ngan_han'))
                    ->addSelect(DB::raw('du_phong_nghiep_vu'))
                    ->addSelect(DB::raw('no_dai_han'))
                    ->addSelect(DB::raw('nguon_von_chu_so_huu+loi_ich_co_dong_thieu_so as von_chu_so_huu'))
                    ->addSelect(DB::raw('von_dau_tu_cua_chu_so_huu'))
                    ->addSelect(DB::raw('thang_du_von_co_phan'))
                    ->addSelect(DB::raw('loi_nhuan_sau_thue_chua_phan_phoi'))
                    ->addSelect(DB::raw('loi_ich_co_dong_thieu_so'))
                    ->addSelect(DB::raw('tong_cong_nguon_von'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    'tai_san_luu_dong_va_dau_tu_ngan_han' => "T??i s???n l??u ?????ng v?? ?????u t?? ng???n h???n",
                    'tien' => "Ti???n",
                    'cac_khoan_dau_tu_tai_chinh_ngan_han' => "C??c kho???n ?????u t?? t??i ch??nh ng???n h???n",
                    'cac_khoan_phai_thu' => "C??c kho???n ph???i thu",
                    'hang_ton_kho' => "H??ng t???n kho",
                    'tai_san_ngan_han_khac' => "T??i s???n ng???n h???n kh??c",
                    'tai_san_co_dinh_va_dau_tu_dai_han' => "T??i s???n c??? ?????nh v?? ?????u t?? d??i h???n",
                    'tai_san_co_dinh' => "T??i s???n c??? ?????nh",
                    'bat_dong_san_dau_tu' => "B???t ?????ng s???n ?????u t??",
                    'cac_khoan_dau_tu_tai_chinh_dai_han' => "C??c kho???n ?????u t?? t??i ch??nh d??i h???n",
                    'cac_khoan_ky_quy_ky_cuoc_dai_han' => "C??c kho???n k?? qu???, k?? c?????c d??i h???n",
                    'tong_cong_tai_san' => "T???ng c???ng t??i s???n",
                    'no_phai_tra' => "N??? ph???i tr???",
                    'no_ngan_han' => "N??? ng???n h???n",
                    'du_phong_nghiep_vu' => "D??? ph??ng nghi???p v???",
                    'no_dai_han' => "N??? d??i h???n",
                    'nguon_von_chu_so_huu+loi_ich_co_dong_thieu_so as von_chu_so_huu' => "V???n ch??? s??? h???u",
                    'von_dau_tu_cua_chu_so_huu' => "V???n ?????u t?? c???a ch??? s??? h???u",
                    'thang_du_von_co_phan' => "Th???ng d?? v???n c??? ph???n",
                    'loi_nhuan_sau_thue_chua_phan_phoi' => "L???i nhu???n sau thu??? ch??a ph??n ph???i",
                    'loi_ich_co_dong_thieu_so' => "L???i ???ch C??TS",
                    'tong_cong_nguon_von' => "T???ng c???ng ngu???n v???n",
                ];
                break;
            default:
                break;
        }
        $table_data->columns = $column_select->columns;
        if ($type_time == "quarter") {
            $table_data = $table_data->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC');
        } else {
            $table_data = $table_data->orderBy("thoigian", "desc");
        }
        return [
            "data_table" => $table_data->get(),
            "data_mapping" => $data_mapping
        ];
    }

    public function getDataTableCF($mack, $type_time, $page, $item_per_page, $type_stock)
    {
        $table_data = DB::table("cf_" . $type_time . "_" . $type_stock)
            ->offset($page * $item_per_page)
            ->take($item_per_page)
            ->where("mack", $mack);
        $column_select = null;
        $data_mapping = [];
        switch ($type_stock) {
            case "nonbank":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_trong_ky'))
                    ->addSelect(DB::raw('tien_va_tuong_duong_tien_dau_ky'))
                    ->addSelect(DB::raw('tien_va_tuong_duong_tien_cuoi_ky'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng ?????u t??",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng t??i ch??nh",
                    "luu_chuyen_tien_thuan_trong_ky" => "L??u chuy???n ti???n thu???n trong k???",
                    "tien_va_tuong_duong_tien_dau_ky" => "Ti???n v?? t????ng ??????ng ti???n ?????u k???",
                    "tien_va_tuong_duong_tien_cuoi_ky" => "Ti???n v?? t????ng ??????ng ti???n cu???i k???",
                ];
                break;
            case "bank":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'))
                    ->addSelect(DB::raw('luu_chuyen_tien_tu_hoat_dong_tai_chinh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_trong_ky'))
                    ->addSelect(DB::raw('tien_va_tuong_duong_tien_dau_ky'))
                    ->addSelect(DB::raw('tien_va_tuong_duong_tien_cuoi_ky'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng ?????u t??",
                    "luu_chuyen_tien_tu_hoat_dong_tai_chinh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng t??i ch??nh",
                    "luu_chuyen_tien_thuan_trong_ky" => "L??u chuy???n ti???n thu???n trong k???",
                    "tien_va_tuong_duong_tien_dau_ky" => "Ti???n v?? t????ng ??????ng ti???n ?????u k???",
                    "tien_va_tuong_duong_tien_cuoi_ky" => "Ti???n v?? t????ng ??????ng ti???n cu???i k???",
                ];
                break;
            case "stock":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'))
                    ->addSelect(DB::raw('tang_giam_tien_thuan_trong_ky'))
                    ->addSelect(DB::raw('tien_va_cac_khoan_tuong_duong_tien_dau_ky'))
                    ->addSelect(DB::raw('tien_va_cac_khoan_tuong_duong_tien_cuoi_ky'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng ?????u t??",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng t??i ch??nh",
                    "tang_giam_tien_thuan_trong_ky" => "L??u chuy???n ti???n thu???n trong k???",
                    "tien_va_cac_khoan_tuong_duong_tien_dau_ky" => "Ti???n v?? t????ng ??????ng ti???n ?????u k???",
                    "tien_va_cac_khoan_tuong_duong_tien_cuoi_ky" => "Ti???n v?? t????ng ??????ng ti???n cu???i k???",
                ];
                break;
            case "insurance":
                $column_select = DB::table("temp_table")
                    ->addSelect(DB::raw('thoigian'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'))
                    ->addSelect(DB::raw('luu_chuyen_tien_thuan_trong_ky'))
                    ->addSelect(DB::raw('tien_va_tuong_duong_tien_dau_ky'))
                    ->addSelect(DB::raw('tien_va_tuong_duong_tien_cuoi_ky'));
                $data_mapping = [
                    "thoigian" => "Th???i gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng ?????u t??",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh" => "L??u chuy???n ti???n thu???n t??? ho???t ?????ng t??i ch??nh",
                    "luu_chuyen_tien_thuan_trong_ky" => "L??u chuy???n ti???n thu???n trong k???",
                    "tien_va_tuong_duong_tien_dau_ky" => "Ti???n v?? t????ng ??????ng ti???n ?????u k???",
                    "tien_va_tuong_duong_tien_cuoi_ky" => "Ti???n v?? t????ng ??????ng ti???n cu???i k???",
                ];
                break;
            default:
                break;
        }
        $table_data->columns = $column_select->columns;
        if ($type_time == "quarter") {
            $table_data = $table_data->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC');
        } else {
            $table_data = $table_data->orderBy("thoigian", "desc");
        }
        return [
            "data_table" => $table_data->get(),
            "data_mapping" => $data_mapping
        ];
    }

    public function getDataTableRaw(Request $request)
    {
        $res = [];
        $mack = strtoupper($request->input('mack'));
        $type_time = $request->input('type_time');
        $type_table = $request->input('type_table');
        $page = $request->input('page') ? $request->input('page') : 1;
        $item_per_page = $request->input('item_per_page') ? $request->input('item_per_page') : 100;
        $page -= 1;
        $order = $request->input('order') ? $request->input('order') : "asc";
        $is_get_data_mapping = $request->input('is_get_data_mapping') ? $request->input('is_get_data_mapping') : false;
        $type_stock = DB::table('danh_sach_mack')
            ->select("nhom")
            ->where('mack', '=', $mack)
            ->first();
        $type_stock = $type_stock->nhom;
        $data = [];
        switch ($type_table) {
            case "is":
                $data = $this->getDataTableIS($mack, $type_time, $page, $item_per_page, $type_stock);
                break;
            case "bs":
                $data = $this->getDataTableBS($mack, $type_time, $page, $item_per_page, $type_stock);
                break;
            case "cf":
                $data = $this->getDataTableCF($mack, $type_time, $page, $item_per_page, $type_stock);
                break;
            default:
                break;
        }
        $res = $data["data_table"];
        $res = json_decode(json_encode($res), true);
        if (!$res)
            return response()->json($res);
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            $arr[array_keys($res[0])[$i]] = array_column($res, array_keys($res[0])[$i]);
        }
        if ($order == "asc") {
            foreach ($arr as $key => $value) {
                $arr[$key] = array_reverse($arr[$key]);
            }
        }
        $arr["mack"] = $mack;
        $arr["type_stock"] = $type_stock;
        if ($is_get_data_mapping) {
            $arr["data_mapping"] = $data["data_mapping"];
        }
        return response()->json($arr);
    }
}
