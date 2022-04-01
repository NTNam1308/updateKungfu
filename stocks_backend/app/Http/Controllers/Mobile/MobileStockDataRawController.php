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
                    "thoigian" => "Thời gian",
                    "doanh_thu_thuan" => "Doanh thu thuần",
                    "gia_von_ban_hang" => "Giá vốn hàng bán",
                    "loi_nhuan_gop" => "Lợi nhuận gộp",
                    "doanh_thu_hoat_dong_tai_chinh" => "Doanh thu hoạt động tài chính",
                    "chi_phi_tai_chinh" => "Chi phí tài chính",
                    "chi_phi_ban_hang" => "Chi phí bán hàng",
                    "chi_phi_quan_ly_doanh_nghiep" => "Chi phí quản lý doanh nghiệp",
                    "loi_nhuan_tu_hoat_dong_kinh_doanh" => "Lợi nhuận thuần từ hoạt động kinh doanh",
                    "loi_nhuan_khac" => "Lợi nhuận khác",
                    "loi_nhuan_trong_cong_ty_lien_ket" => "Phần lợi nhuận hoặc lỗ trong công ty liên kết liên doanh",
                    "tong_loi_nhuan_ke_toan_truoc_thue" => "Tổng lợi nhuận kế toán trước thuế",
                    "loi_nhuan_sau_thue_tndn" => "Lợi nhuận sau thuế thu nhập doanh nghiệp",
                    "loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me" => "Lợi nhuận sau thuế của cổ đông của công ty mẹ",
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
                    "thoigian" => "Thời gian",
                    "thu_nhap_lai_thuan" => "Thu nhập lãi thuần",
                    "lai_lo_thuan_tu_hoat_dong_dich_vu" => "Lợi nhuận thuần hoạt động dịch vụ",
                    "lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi" => "Lợi nhuận thuần hoạt động kinh doanh ngoại hối, vàng",
                    "lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh" => "Lợi nhuận thuần mua bán chứng khoán kinh doanh",
                    "lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu" => "Lợi nhuận thuần mua bán chứng khoán đầu tư",
                    "lai_lo_thuan_tu_hoat_dong_khac" => "Lợi nhuận thuần hoạt động khác",
                    "thu_nhap_tu_hoat_dong_gop_von_mua_co_phan" => "Thu nhập từ góp vốn, mua cổ phiếu",
                    "chi_phi_hoat_dong" => "Chi phí hoạt động",
                    "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung" => "Lợi nhuận thuần hoạt động kinh doanh trước chi phí dự phòng",
                    "chi_phi_du_phong_rui_ro_tin_dung" => "Chi phí dự phòng rủi ro tín dụng",
                    "tong_loi_nhuan_truoc_thue" => "Lợi nhuận trước thuế",
                    "loi_nhuan_sau_thue_thu_nhap_doanh_nghiep" => "Lợi nhuận sau thuế",
                    "loi_ich_cua_co_dong_thieu_so_va_co_tuc_uu_dai" => "Lợi nhuận sau thuế của cổ đông ngân hàng mẹ",
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
                    "thoigian" => "Thời gian",
                    "cong_doanh_thu_hoat_dong" => "Cộng doanh thu hoạt động",
                    "cong_chi_phi_hoat_dong" => "Cộng chi phí hoạt động",
                    "loi_nhuan_gop_hdkd" => "Lợi nhuận gộp HĐKD",
                    "chi_phi_quan_ly_cong_ty_chung_khoan" => "Chi phí quản lý công ty chứng khoán",
                    "ket_qua_hoat_dong" => "Kết quả hoạt động",
                    "cong_ket_qua_hoat_dong_khac" => "Cộng kết quả hoạt động khác",
                    "tong_loi_nhuan_ke_toan_truoc_thue" => "Lợi nhuận trước thuế",
                    "loi_nhuan_sau_thue_phan_bo_cho_chu_so_huu" => "Lợi nhuận sau thuế",
                    "loi_nhuan_ke_toan_sau_thue_tndn" => "Lợi nhuận sau thuế của cổ đông công ty mẹ",
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
                    "thoigian" => "Thời gian",
                    "doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem" => "Doanh thu thuần hoạt động kinh doanh bảo hiểm",
                    "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem" => "Tổng chi trực tiếp hoạt động kinh doanh bảo hiểm",
                    "loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_" => "Lợi nhuận gộp hoạt động kinh doanh bảo hiểm",
                    "loi_nhuan_thuan_hoat_dong_kinh_doanh_bao_hiem" => "Lợi nhuận thuần hoạt động kinh doanh bảo hiểm",
                    "loi_nhuan_hoat_dong_tai_chinh" => "Lợi nhuận hoạt động tài chính",
                    "tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep" => "Tổng lợi nhuận trước thuế thu nhập doanh nghiệp",
                    "loi_nhuan_sau_thue_thu_nhap_doanh_nghiep" => "Lợi nhuận sau thuế thu nhập doanh nghiệp",
                    "loi_nhuan_sau_thue_cua_co_dong_cong_ty_me" => "Lợi nhuận sau thuế của cổ đông công ty mẹ",
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
                    "thoigian" => "Thời gian",
                    "tai_san_luu_dong_va_dau_tu_ngan_han" => "Tài sản lưu động và đầu tư ngắn hạn",
                    "tien_va_cac_khoan_tuong_duong_tien" => "Tiền và các khoản tương đương tiền",
                    "cac_khoan_dau_tu_tai_chinh_ngan_han" => "Các khoản đầu tư tài chính ngắn hạn",
                    "cac_khoan_phai_thu_ngan_han" => "Các khoản phải thu ngắn hạn",
                    "tong_hang_ton_kho" => "Tổng hàng tồn kho",
                    "tong_tai_san_ngan_han_khac" => "Tài sản ngắn hạn khác",
                    "tai_san_co_dinh_va_dau_tu_dai_han" => "Tài sản cố định và đầu tư dài hạn",
                    "tai_san_co_dinh" => "Tài sản cố định",
                    "bat_dong_san_dau_tu" => "Bất động sản đầu tư",
                    "cac_khoan_dau_tu_tai_chinh_dai_han" => "Các khoản đầu tư tài chính dài hạn",
                    "tong_cong_tai_san" => "Tổng cộng tài sản",
                    "no_phai_tra" => "Nợ phải trả",
                    "no_ngan_han" => "Nợ ngắn hạn",
                    "no_dai_han" => "Nợ dài hạn",
                    "nguon_von_chu_so_huu" => "Nguồn vốn chủ sở hữu",
                    "von_dau_tu_cua_chu_so_huu" => "Vốn đầu tư của chủ sở hữu",
                    "thang_du_gop_co_phan" => "Thặng dư vốn cổ phần",
                    "loi_nhuan_sau_thue_chua_phan_phoi" => "Lợi nhuận sau thuế chưa phân phối",
                    "loi_ich_cua_co_dong_khong_kiem_soat" => "Lợi ích của cổ đông không kiểm soát",
                    "tong_cong_nguon_von" => "Tổng cộng nguồn vốn",
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
                    "thoigian" => "Thời gian",
                    "tien_mat_chung_tu_co_gia_tri_ngoai_te_kim_loai_quy_da_quy" => "Tiền mặt, vàng bạc, đá quý",
                    "tien_gui_tai_nhnn" => "Tiền gửi tại NHNN",
                    "tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac" => "Tiền, vàng gửi và cho vay tại các TCTD khác",
                    "chung_khoan_kinh_doanh" => "Chứng khoán KD",
                    "cac_cong_cu_tai_chinh_phai_sinh_va_cac_tai_san_tai_chinh_khac" => "Công cụ TC phái sinh và TSTC khác",
                    "cho_vay_khach_hang" => "Cho vay và cho thuê TC khách hàng",
                    "chung_khoan_dau_tu" => "Chứng khoán đầu tư",
                    "gop_von_dau_tu_dai_han" => "Góp vốn, đầu tư dài hạn",
                    "tai_san_co_dinh" => "Tài sản cố định",
                    "bat_dong_san_dau_tu" => "Bất động sản đầu tư",
                    "tai_san_co_khac" => "TS có khác",
                    "tong_cong_tai_san" => "Tổng TS",
                    "cac_khoan_no_chinh_phu_va_nhnn" => "Nợ Chính phủ & NHNN",
                    "tien_gui_va_cho_vay_cac_tctd_khac" => "Tiền gửi vay các TCTD khác",
                    "von_tai_tro_uy_thac_dau_tu_ma_ngan_hang_chiu_rui_ro" => "Vốn tài trợ, ủy thác đầu tư, cho vay mà TCTD chịu rủi ro",
                    "phat_hanh_giay_to_co_gia" => "Phát hành giấy tờ có giá",
                    "von_va_cac_quy" => "Vốn và các quỹ",
                    "loi_ich_cua_co_dong_thieu_so" => "Lợi ích CĐTS",
                    "tong_cong_nguon_von" => "Tổng NPT & VCSH",
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
                    "thoigian" => "Thời gian",
                    'tai_san_ngan_han' => "Tài sản ngắn hạn",
                    'tien_va_cac_khoan_tuong_duong_tien' => "Tiền và các khoản tương đương tiền",
                    'cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl' => "Các tài sản tài chính ghi nhận thông qua lãi lỗ (FVTPL)",
                    'cac_khoan_dau_tu_giu_den_ngay_dao_han_htm' => "Các khoản đầu tư  giữ đến ngày đáo hạn (HTM)",
                    'cac_tai_san_tai_chinh_san_sang_de_ban_afs' => "Các tài sản tài chính sẵn sàng để bán (AFS)",
                    'cac_khoan_phai_thu' => "Các khoản phải thu",
                    'tai_san_dai_han' => "Tài sản dài hạn",
                    'cac_khoan_dau_tu' => "Các khoản đầu tư",
                    'tai_san_co_dinh' => "Tài sản cố định",
                    'bat_dong_san_dau_tu' => "Bất động sản đầu tư",
                    'tong_cong_tai_san' => "Tổng cộng tài sản",
                    'no_phai_tra' => "Nợ phải trả",
                    'no_phai_tra_ngan_han' => "Nợ phải trả ngắn hạn",
                    'no_phai_tra_dai_han' => "Nợ phải trả dài hạn",
                    'tong_von_chu_so_huu' => "Vốn chủ sở hữu",
                    'von_gop_cua_chu_so_huu' => "Vốn góp của chủ sở hữu",
                    'thang_du_von_co_phan' => "Thặng dư vốn cổ phần",
                    'loi_nhuan_chua_phan_phoi' => "Lợi nhuận chưa phân phối",
                    'loi_ich_cua_co_dong_khong_nam_quyen_kiem_soat' => "Lợi ích của cổ đông không nắm quyền kiểm soát",
                    'tong_cong_no_phai_tra_va_von_chu_so_huu' => "Tổng nguồn vốn",
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
                    "thoigian" => "Thời gian",
                    'tai_san_luu_dong_va_dau_tu_ngan_han' => "Tài sản lưu động và đầu tư ngắn hạn",
                    'tien' => "Tiền",
                    'cac_khoan_dau_tu_tai_chinh_ngan_han' => "Các khoản đầu tư tài chính ngắn hạn",
                    'cac_khoan_phai_thu' => "Các khoản phải thu",
                    'hang_ton_kho' => "Hàng tồn kho",
                    'tai_san_ngan_han_khac' => "Tài sản ngắn hạn khác",
                    'tai_san_co_dinh_va_dau_tu_dai_han' => "Tài sản cố định và đầu tư dài hạn",
                    'tai_san_co_dinh' => "Tài sản cố định",
                    'bat_dong_san_dau_tu' => "Bất động sản đầu tư",
                    'cac_khoan_dau_tu_tai_chinh_dai_han' => "Các khoản đầu tư tài chính dài hạn",
                    'cac_khoan_ky_quy_ky_cuoc_dai_han' => "Các khoản ký quỹ, ký cược dài hạn",
                    'tong_cong_tai_san' => "Tổng cộng tài sản",
                    'no_phai_tra' => "Nợ phải trả",
                    'no_ngan_han' => "Nợ ngắn hạn",
                    'du_phong_nghiep_vu' => "Dự phòng nghiệp vụ",
                    'no_dai_han' => "Nợ dài hạn",
                    'nguon_von_chu_so_huu+loi_ich_co_dong_thieu_so as von_chu_so_huu' => "Vốn chủ sở hữu",
                    'von_dau_tu_cua_chu_so_huu' => "Vốn đầu tư của chủ sở hữu",
                    'thang_du_von_co_phan' => "Thặng dư vốn cổ phần",
                    'loi_nhuan_sau_thue_chua_phan_phoi' => "Lợi nhuận sau thuế chưa phân phối",
                    'loi_ich_co_dong_thieu_so' => "Lợi ịch CĐTS",
                    'tong_cong_nguon_von' => "Tổng cộng nguồn vốn",
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
                    "thoigian" => "Thời gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "Lưu chuyển tiền thuần từ hoạt động kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "Lưu chuyển tiền thuần từ hoạt động đầu tư",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh" => "Lưu chuyển tiền thuần từ hoạt động tài chính",
                    "luu_chuyen_tien_thuan_trong_ky" => "Lưu chuyển tiền thuần trong kỳ",
                    "tien_va_tuong_duong_tien_dau_ky" => "Tiền và tương đương tiền đầu kỳ",
                    "tien_va_tuong_duong_tien_cuoi_ky" => "Tiền và tương đương tiền cuối kỳ",
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
                    "thoigian" => "Thời gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "Lưu chuyển tiền thuần từ hoạt động kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "Lưu chuyển tiền thuần từ hoạt động đầu tư",
                    "luu_chuyen_tien_tu_hoat_dong_tai_chinh" => "Lưu chuyển tiền thuần từ hoạt động tài chính",
                    "luu_chuyen_tien_thuan_trong_ky" => "Lưu chuyển tiền thuần trong kỳ",
                    "tien_va_tuong_duong_tien_dau_ky" => "Tiền và tương đương tiền đầu kỳ",
                    "tien_va_tuong_duong_tien_cuoi_ky" => "Tiền và tương đương tiền cuối kỳ",
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
                    "thoigian" => "Thời gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "Lưu chuyển tiền thuần từ hoạt động kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "Lưu chuyển tiền thuần từ hoạt động đầu tư",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh" => "Lưu chuyển tiền thuần từ hoạt động tài chính",
                    "tang_giam_tien_thuan_trong_ky" => "Lưu chuyển tiền thuần trong kỳ",
                    "tien_va_cac_khoan_tuong_duong_tien_dau_ky" => "Tiền và tương đương tiền đầu kỳ",
                    "tien_va_cac_khoan_tuong_duong_tien_cuoi_ky" => "Tiền và tương đương tiền cuối kỳ",
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
                    "thoigian" => "Thời gian",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh" => "Lưu chuyển tiền thuần từ hoạt động kinh doanh",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu" => "Lưu chuyển tiền thuần từ hoạt động đầu tư",
                    "luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh" => "Lưu chuyển tiền thuần từ hoạt động tài chính",
                    "luu_chuyen_tien_thuan_trong_ky" => "Lưu chuyển tiền thuần trong kỳ",
                    "tien_va_tuong_duong_tien_dau_ky" => "Tiền và tương đương tiền đầu kỳ",
                    "tien_va_tuong_duong_tien_cuoi_ky" => "Tiền và tương đương tiền cuối kỳ",
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
