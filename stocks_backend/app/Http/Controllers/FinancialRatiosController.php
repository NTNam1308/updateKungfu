<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use \Exception;
use Mail;
use Datetime;

// set_error_handler(function () {
//     throw new Exception('Ach!');
// });

class FinancialRatiosController extends Controller
{
    //hàm trừ đi số quý và số năm
    protected function dec_quarter($time, $count)
    {
        $quarter = (int) substr($time, 1, 2);
        $year = (int) substr($time, -4);
        if (is_null($time))
            return null;
        if (str_contains($time, "Q")) {
            $year -= floor($count / 4);
            if ($quarter <= $count % 4) {
                $quarter = 4 - ($count % 4 - $quarter);
                $year--;
            } else
                $quarter -= $count % 4;
            return "Q" . $quarter . " " . $year;
        } else {
            $year -= $count;
            return $year;
        }
    }

    //hàm check null và lấy giá trị đúng theo quý và năm
    protected function checkNullValueByYear($d, $key, $i, $i_next)
    {
        if ($i > count($d) || $i_next > count($d))
            return null;
        $thoigian = is_null($d[$i]["is_thoigian"]) ? (is_null($d[$i]["bs_thoigian"]) ? $d[$i]["cf_thoigian"] : $d[$i]["bs_thoigian"]) : $d[$i]["is_thoigian"];
        $thoigian_muonlay = $this->dec_quarter($thoigian, $i_next - $i);
        // return $thoigian_muonlay;
        for ($j = $i; $j <= $i_next; $j++) {
            $thoigian_tam = is_null($d[$j]["is_thoigian"]) ? (is_null($d[$j]["bs_thoigian"]) ? $d[$j]["cf_thoigian"] : $d[$j]["bs_thoigian"]) : $d[$j]["is_thoigian"];
            if ($thoigian_muonlay == $thoigian_tam) {
                if (is_null($d[$j][$key]))
                    return null;
                return $d[$j][$key];
            }
        }
        return null;
    }
    //hàm check null và tính giá trị quý hiện tại + quý trước
    protected function calculate_before_quarter_contain_check_null($d, $key, $i)
    {
        if (is_null($this->checkNullValueByYear($d, $key, $i, $i)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 1)))
            return null;
        return $this->checkNullValueByYear($d, $key, $i, $i) + $this->checkNullValueByYear($d, $key, $i, $i + 1);
    }
    //hàm check null và tính giá trị quý hiện tại + quý cùng kỳ năm ngoái
    protected function calculate_before_4_quarter_contain_check_null($d, $key, $i)
    {
        if (is_null($this->checkNullValueByYear($d, $key, $i, $i)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 4)))
            return null;
        return $this->checkNullValueByYear($d, $key, $i, $i) + $this->checkNullValueByYear($d, $key, $i, $i + 4);
    }
    //hàm check null và tính giá trị ttm
    protected function calculate_ttm_contain_check_null($d, $key, $i)
    {
        if (is_null($this->checkNullValueByYear($d, $key, $i, $i)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 1)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 2)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 3))) {
            return null;
        }
        return $this->checkNullValueByYear($d, $key, $i, $i + 1) + $this->checkNullValueByYear($d, $key, $i, $i + 2) + $this->checkNullValueByYear($d, $key, $i, $i) + $this->checkNullValueByYear($d, $key, $i, $i + 3);
    }
    //hàm lấy ngày cuối cùng của quý 
    protected function getLastDayInQuarter($time, $separation)
    {
        $quarter = (int) substr($time, 1, 2);
        $year = (int) substr($time, -4);
        $month = $quarter * 3;
        $day = 0;
        if ($month == 3 || $month == 12) {
            $day = 31;
        } else {
            $day = 30;
        }
        return $year . $separation . $month . $separation . $day;
    }

    //hàm lấy tất cả close price theo các ngày cuối cùng của quý
    protected function getListClosePriceByMack($mack, $list_time)
    {
        $list_sub_query = [];
        foreach ($list_time as $item) {
            if (!is_null($item))
                array_push($list_sub_query, DB::raw("(SELECT tradingdate FROM stock_eod WHERE stockcode='" . $mack . "' and tradingdate<='" . $this->getLastDayInQuarter($item, "-") . "' ORDER BY tradingdate DESC LIMIT 1)"));
        }
        $res = DB::connection('pgsql')
            ->table('stock_eod')
            ->addSelect(DB::raw('closeprice'))
            ->addSelect('tradingdate')
            ->where('stockcode', $mack)
            ->whereIn('tradingdate', $list_sub_query)
            ->orderBy('tradingdate', 'DESC')
            ->get();
        $return_data = [];
        foreach ($list_time as $item) {
            if (!is_null($item)) {
                foreach ($res as $key => $res_time) {
                    $year_res = (int) substr($res_time->tradingdate, 0, 4);
                    $month_res = (int) substr($res_time->tradingdate, 5, 2);

                    $year_input = (int) substr($item, -4);
                    $month_input = (int) substr($this->getLastDayInQuarter($item, "-"), 5, 2);
                    if ($year_res == $year_input && $month_res == $month_input) {
                        $return_data[$item] = $res[$key];
                        unset($res[$key]);
                        continue;
                    }
                }
            }
        }
        return $return_data;
    }

    //hàm lấy close price theo ngày
    protected function getClosePriceByTime($data_close_price, $time)
    {
        return isset($data_close_price[$time]) ? (float) $data_close_price[$time]->closeprice : 0;
    }

    protected function test(Request $req)
    {
        return $this->getListClosePriceByMack($req->input('mack'), ['Q2 2021', 'Q1 2021']);
    }

    protected function search_car_by_year($d, $year)
    {
        foreach ($d as $value) {
            if ($value["thoigian"] == $year) {
                return $value["car"];
            }
        }
        return 0;
    }

    public function getFinancialRatiosNonBank(Request $req)
    {
        $type = $req->input('thoigian');
        if ($type == "quarter")
            return $this->calculateFinancialRatiosNonBankQuarter($req);
        else if ($type == "ttm")
            return $this->calculateFinancialRatiosNonBankTTM($req);
        else
            return $this->calculateFinancialRatiosNonBankYear($req);
    }

    public function calculateFinancialRatiosNonBankQuarter(Request $req)
    {
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "nonbank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $page -= 1;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('bs.tong_hang_ton_kho as tong_hang_ton_kho'))
            ->addSelect(DB::raw('is.gia_von_ban_hang as gia_von_ban_hang'))
            ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han as cac_khoan_phai_thu_dai_han'))
            ->addSelect(DB::raw('bs.cac_khoan_phai_thu_ngan_han as cac_khoan_phai_thu_ngan_han'))
            ->addSelect(DB::raw('bs.phai_tra_nguoi_ban_ngan_han as phai_tra_nguoi_ban_ngan_han'))
            ->addSelect(DB::raw('bs.phai_tra_nguoi_ban_dai_han as phai_tra_nguoi_ban_dai_han'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('0 as gia_thi_truong'))
            ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu / 10000 as von_hoa'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_chinh_ngan_han+bs.vay_va_no_thue_tai_chinh_dai_han-bs.tien_va_cac_khoan_tuong_duong_tien as gia_tri_doanh_nghiep'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10)*1000 as eps'))
            ->addSelect(DB::raw('0 as tang_truong_eps'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('0 as tang_truong_bvps'))
            ->addSelect(DB::raw('0 as pe'))
            ->addSelect(DB::raw('0 as pb'))
            ->addSelect(DB::raw('0 as evebit'))
            ->addSelect(DB::raw('0 as evebitda'))
            ->addSelect(DB::raw('is.doanh_thu_thuan as doanh_thu'))
            ->addSelect(DB::raw('0 as tang_truong_doanh_thu_thuan'))
            ->addSelect(DB::raw('is.loi_nhuan_gop as loi_nhuan_gop'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay + cf.khau_hao_tscd as ebitda'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as ebt'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as loi_nhuan_rong'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('is.loi_nhuan_gop / is.doanh_thu_thuan as bien_loi_nhuan_gop'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay) / is.doanh_thu_thuan as bien_ebit'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay + cf.khau_hao_tscd) / is.doanh_thu_thuan as bien_ebitda'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / is.doanh_thu_thuan as bien_ebt'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me / is.doanh_thu_thuan as bien_loi_nhuan_rong'))
            ->addSelect(DB::raw('0 as roe'))
            ->addSelect(DB::raw('0 as roic'))
            ->addSelect(DB::raw('0 as roa'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as dong_tien_tu_hoat_dong_kd_chinh'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh/loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as ocf_lnst'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh + cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_tai_san_dai_han_khac + cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_tai_san_dai_han_khac as free_cash_flow'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_chinh_dai_han as no_dai_han'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ti_le_thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('( bs.tai_san_luu_dong_va_dau_tu_ngan_han - bs.tong_hang_ton_kho ) / bs.no_ngan_han as ti_le_thanh_toan_nhanh'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.nguon_von_chu_so_huu as tong_no_von_chu_so_huu'))
            ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_hang_ton_kho"))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_khoan_phai_thu"))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_khoan_phai_tra"))
            ->addSelect(DB::raw('0 as roa2'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
            ->addSelect(DB::raw('( is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me) / is.doanh_thu_thuan as ti_suat_loi_nhuan'))
            ->addSelect(DB::raw('0 as roe2'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san / bs.von_chu_so_huu as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san3'))
            ->addSelect(DB::raw('( is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay ) / is.doanh_thu_thuan  as bien_ebit2'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / ( is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay ) as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me / is.tong_loi_nhuan_ke_toan_truoc_thue as ganh_nang_thue'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.tong_cong_tai_san as tai_san_ngan_han_tong_so_tai_san'))
            ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han / bs.tong_cong_tai_san as tai_san_dai_han_tong_so_tai_san'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.tong_cong_nguon_von as no_phai_tra_tong_nguon_von'))
            ->addSelect(DB::raw('bs.nguon_von_chu_so_huu / bs.tong_cong_nguon_von as nguon_von_chu_so_huu_tong_nguon_von'))
            ->addSelect(DB::raw('(bs.no_phai_tra - bs.nguoi_mua_tra_tien_truoc) / bs.tong_cong_nguon_von as ty_le_no_tts'))
            ->addSelect(DB::raw('(bs.no_phai_tra - bs.nguoi_mua_tra_tien_truoc) / bs.nguon_von_chu_so_huu as ty_le_no_vcsh'))
            ->addSelect(DB::raw('(bs.tien_va_cac_khoan_tuong_duong_tien + bs.cac_khoan_dau_tu_tai_chinh_ngan_han) / bs.no_ngan_han as ty_le_thanh_toan'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? $res[$i]["cf_thoigian"] : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * $res[$i]["von_hoa"];
            $res[$i]["gia_tri_doanh_nghiep"] = $res[$i]["von_hoa"] + $res[$i]["gia_tri_doanh_nghiep"];
            if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "eps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $res[$i]["eps"] < 0 ? -1 : ($res[$i]["eps"] - $this->checkNullValueByYear($res, "eps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "eps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_doanh_thu_thuan"] = $res[$i]["doanh_thu"] < 0 ? -1 : ($res[$i]["doanh_thu"] - $this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["loi_nhuan_rong"] < 0 ? -1 : ($res[$i]["loi_nhuan_rong"] - $this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4));
                    }
                }
            }
            // checkNullValueByYear
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                $res[$i]['pe'] = $res[$i]["gia_thi_truong"] / $this->calculate_ttm_contain_check_null($res, "eps", $i);
            }
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            if (!is_null($this->calculate_ttm_contain_check_null($res, "ebit", $i))) {
                $res[$i]['evebit'] = $res[$i]["gia_tri_doanh_nghiep"] / $this->calculate_ttm_contain_check_null($res, "ebit", $i);
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "ebitda", $i))) {
                $res[$i]['evebitda'] = $res[$i]["gia_tri_doanh_nghiep"] / $this->calculate_ttm_contain_check_null($res, "ebitda", $i);
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                $res[$i]['roe2'] = $res[$i]['roe'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i);
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                $res[$i]['vong_quay_tai_san3'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['vong_quay_tai_san'] = $res[$i]['doanh_thu'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "no_dai_han", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "no_dai_han", $i)) != 0) {
                            $res[$i]['roic'] = ($res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "no_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i)));
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                $res[$i]['roa2'] = $res[$i]['roa'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
            }
            if (!is_null($this->checkNullValueByYear($res, "gia_von_ban_hang", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i) != 0) {
                $res[$i]['vong_quay_hang_ton_kho'] = ($res[$i]['gia_von_ban_hang'] * 2 / ($res[$i + 1]['tong_hang_ton_kho'] + $res[$i]['tong_hang_ton_kho']));
                $res[$i]['so_ngay_binh_quan_vong_quay_hang_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? 365 / $res[$i]['vong_quay_hang_ton_kho'] : 0;
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "gia_von_ban_hang", $i, $i)) && !is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i)) && !is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['gia_von_ban_hang'] + $res[$i]['tong_hang_ton_kho'] - $res[$i + 1]['tong_hang_ton_kho']) * 2 / ($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_tra'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i)) && $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i);
            }
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        for ($i = 11; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function calculateFinancialRatiosNonBankTTM(Request $req)
    {
        $thoigian = "quarter";
        $mack = strtoupper($req->input('mack'));
        $typeBank = "nonbank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $page -= 1;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('bs.tong_hang_ton_kho as tong_hang_ton_kho'))
            ->addSelect(DB::raw('is.gia_von_ban_hang as gia_von_ban_hang'))
            ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han as cac_khoan_phai_thu_dai_han'))
            ->addSelect(DB::raw('bs.cac_khoan_phai_thu_ngan_han as cac_khoan_phai_thu_ngan_han'))
            ->addSelect(DB::raw('bs.phai_tra_nguoi_ban_ngan_han as phai_tra_nguoi_ban_ngan_han'))
            ->addSelect(DB::raw('bs.phai_tra_nguoi_ban_dai_han as phai_tra_nguoi_ban_dai_han'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('0 as gia_thi_truong'))
            ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu / 10000 as von_hoa'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_chinh_ngan_han+bs.vay_va_no_thue_tai_chinh_dai_han-bs.tien_va_cac_khoan_tuong_duong_tien as gia_tri_doanh_nghiep'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10)*1000 as eps'))
            ->addSelect(DB::raw('0 as tang_truong_eps'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('0 as tang_truong_bvps'))
            ->addSelect(DB::raw('0 as pe'))
            ->addSelect(DB::raw('0 as pb'))
            ->addSelect(DB::raw('0 as evebit'))
            ->addSelect(DB::raw('0 as evebitda'))
            ->addSelect(DB::raw('is.doanh_thu_thuan as doanh_thu'))
            ->addSelect(DB::raw('0 as tang_truong_doanh_thu_thuan'))
            ->addSelect(DB::raw('is.loi_nhuan_gop as loi_nhuan_gop'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay + cf.khau_hao_tscd as ebitda'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as ebt'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as loi_nhuan_rong'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as bien_loi_nhuan_gop'))
            ->addSelect(DB::raw('0 as bien_ebit'))
            ->addSelect(DB::raw('0 as bien_ebitda'))
            ->addSelect(DB::raw('0 as bien_ebt'))
            ->addSelect(DB::raw('0 as bien_loi_nhuan_rong'))
            ->addSelect(DB::raw('0 as roe'))
            ->addSelect(DB::raw('0 as roic'))
            ->addSelect(DB::raw('0 as roa'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as dong_tien_tu_hoat_dong_kd_chinh'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh/loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as ocf_lnst'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh + cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_tai_san_dai_han_khac + cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_tai_san_dai_han_khac as free_cash_flow'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_chinh_dai_han as no_dai_han'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ti_le_thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('( bs.tai_san_luu_dong_va_dau_tu_ngan_han - bs.tong_hang_ton_kho ) / bs.no_ngan_han as ti_le_thanh_toan_nhanh'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.nguon_von_chu_so_huu as tong_no_von_chu_so_huu'))
            ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_hang_ton_kho"))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_khoan_phai_thu"))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_khoan_phai_tra"))
            ->addSelect(DB::raw('0 as roa2'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
            ->addSelect(DB::raw('0 as ti_suat_loi_nhuan'))
            ->addSelect(DB::raw('0 as roe2'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san / bs.von_chu_so_huu as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san3'))
            ->addSelect(DB::raw('0  as bien_ebit2'))
            ->addSelect(DB::raw('0 as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('0 as ganh_nang_thue'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.tong_cong_tai_san as tai_san_ngan_han_tong_so_tai_san'))
            ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han / bs.tong_cong_tai_san as tai_san_dai_han_tong_so_tai_san'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.tong_cong_nguon_von as no_phai_tra_tong_nguon_von'))
            ->addSelect(DB::raw('bs.nguon_von_chu_so_huu / bs.tong_cong_nguon_von as nguon_von_chu_so_huu_tong_nguon_von'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 8)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 8; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? $res[$i]["cf_thoigian"] : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * $res[$i]["von_hoa"];
            $res[$i]["gia_tri_doanh_nghiep"] = $res[$i]["von_hoa"] + $res[$i]["gia_tri_doanh_nghiep"];
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "eps", $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "eps", $i) - $this->calculate_ttm_contain_check_null($res, "eps", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "eps", $i + 4));
                    }
                }
            }
            $res[$i]["eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4) != 0) {
                        $res[$i]["tang_truong_doanh_thu_thuan"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i) - $this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4));
                    }
                }
            }
            $res[$i]["doanh_thu"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu", $i);
            $res[$i]["loi_nhuan_gop"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_gop", $i);
            $res[$i]["ebit"] = $this->calculate_ttm_contain_check_null($res, "ebit", $i);
            $res[$i]["ebitda"] = $this->calculate_ttm_contain_check_null($res, "ebitda", $i);
            $res[$i]["ebt"] = $this->calculate_ttm_contain_check_null($res, "ebt", $i);

            if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i) - $this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4));
                    }
                }
            }
            $res[$i]["loi_nhuan_rong"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i);
            // checkNullValueByYear
            if ($res[$i]["loi_nhuan_rong"] != 0) {
                $res[$i]['pe'] = $res[$i]["gia_thi_truong"] / $res[$i]["eps"];
            }
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            if ($res[$i]["ebit"] != 0) {
                $res[$i]['evebit'] = $res[$i]["gia_tri_doanh_nghiep"] / $res[$i]["ebit"];
            }
            if ($res[$i]["ebitda"] != 0) {
                $res[$i]['evebitda'] = $res[$i]["gia_tri_doanh_nghiep"] / $res[$i]["ebitda"];
            }
            if ($res[$i]["doanh_thu"] != 0) {
                $res[$i]['bien_loi_nhuan_gop'] = $res[$i]["loi_nhuan_gop"] / $res[$i]["doanh_thu"];
                $res[$i]['bien_ebit2'] = $res[$i]['bien_ebit'] = $res[$i]["ebit"] / $res[$i]["doanh_thu"];
                $res[$i]['bien_ebitda'] = $res[$i]["ebitda"] / $res[$i]["doanh_thu"];
                $res[$i]['bien_ebt'] = $res[$i]["ebt"] / $res[$i]["doanh_thu"];
                $res[$i]['bien_loi_nhuan_rong'] = $res[$i]["loi_nhuan_rong"] / $res[$i]["doanh_thu"];
            }

            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                $res[$i]['roe2'] = $res[$i]['roe'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i);
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                $res[$i]['vong_quay_tai_san3'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['vong_quay_tai_san'] = $res[$i]['doanh_thu'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
            }
            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "no_dai_han", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                        if (($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "no_dai_han", $i)) != 0) {
                            $res[$i]['roic'] = ($res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "no_dai_han", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i)));
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                $res[$i]['roa2'] = $res[$i]['roa'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
            }
            $res[$i]["dong_tien_tu_hoat_dong_kd_chinh"] = $this->calculate_ttm_contain_check_null($res, "dong_tien_tu_hoat_dong_kd_chinh", $i);
            $res[$i]["free_cash_flow"] = $this->calculate_ttm_contain_check_null($res, "free_cash_flow", $i);
            if ($res[$i]["loi_nhuan_rong"] != 0) {
                $res[$i]["ocf_lnst"] = $res[$i]["dong_tien_tu_hoat_dong_kd_chinh"] / $res[$i]["loi_nhuan_rong"];
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "gia_von_ban_hang", $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "tong_hang_ton_kho", $i) != 0) {
                $res[$i]['vong_quay_hang_ton_kho'] = ($this->calculate_ttm_contain_check_null($res, "gia_von_ban_hang", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_hang_ton_kho", $i));
                $res[$i]['so_ngay_binh_quan_vong_quay_hang_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? 365 / $res[$i]['vong_quay_hang_ton_kho'] : 0;
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                        if (($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu'] * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "gia_von_ban_hang", $i)) && !is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i)) && !is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 4))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i))) {
                        if (($this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_tra'] = ($this->calculate_ttm_contain_check_null($res, "gia_von_ban_hang", $i) + $res[$i]['tong_hang_ton_kho'] - $res[$i + 4]['tong_hang_ton_kho']) * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_tra'] : 0;
                        }
                    }
                }
            }
            if ($res[$i]["doanh_thu"] != 0) {
                $res[$i]['ti_suat_loi_nhuan'] = $res[$i]["loi_nhuan_rong"] / $res[$i]["doanh_thu"];
            }
            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i);
            }
            if ($res[$i]["ebit"] != 0) {
                $res[$i]['ganh_nang_lai_suat'] = $res[$i]["ebt"] / $res[$i]["ebit"];
            }
            if ($res[$i]["ebt"] != 0) {
                $res[$i]['ganh_nang_thue'] = $res[$i]["loi_nhuan_rong"] / $res[$i]["ebt"];
            }
        }
        $res = array_slice($res, 0, count($res) - 8);
        $arr = [];
        for ($i = 11; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function calculateFinancialRatiosNonBankYear(Request $req)
    {
        $limit = 100;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "nonbank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('bs.tong_hang_ton_kho as tong_hang_ton_kho'))
            ->addSelect(DB::raw('is.gia_von_ban_hang as gia_von_ban_hang'))
            ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han as cac_khoan_phai_thu_dai_han'))
            ->addSelect(DB::raw('bs.cac_khoan_phai_thu_ngan_han as cac_khoan_phai_thu_ngan_han'))
            ->addSelect(DB::raw('bs.phai_tra_nguoi_ban_ngan_han as phai_tra_nguoi_ban_ngan_han'))
            ->addSelect(DB::raw('bs.phai_tra_nguoi_ban_dai_han as phai_tra_nguoi_ban_dai_han'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            // ->addSelect(DB::raw('0 as gia_thi_truong'))
            // ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu / 10000 as von_hoa'))
            // ->addSelect(DB::raw('bs.vay_va_no_thue_tai_chinh_ngan_han+bs.vay_va_no_thue_tai_chinh_dai_han-bs.tien_va_cac_khoan_tuong_duong_tien as gia_tri_doanh_nghiep'))
            // ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10)*1000 as eps'))
            // ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            // ->addSelect(DB::raw('0 as pe'))
            // ->addSelect(DB::raw('0 as pb'))
            // ->addSelect(DB::raw('0 as evebit'))
            // ->addSelect(DB::raw('0 as evebitda'))
            ->addSelect(DB::raw('is.doanh_thu_thuan as doanh_thu'))
            ->addSelect(DB::raw('0 as tang_truong_doanh_thu_thuan'))
            ->addSelect(DB::raw('is.loi_nhuan_gop as loi_nhuan_gop'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay + cf.khau_hao_tscd as ebitda'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as ebt'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as loi_nhuan_rong'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('is.loi_nhuan_gop / is.doanh_thu_thuan as bien_loi_nhuan_gop'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay) / is.doanh_thu_thuan as bien_ebit'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay + cf.khau_hao_tscd) / is.doanh_thu_thuan as bien_ebitda'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / is.doanh_thu_thuan as bien_ebt'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me / is.doanh_thu_thuan as bien_loi_nhuan_rong'))
            ->addSelect(DB::raw('0 as roe'))
            ->addSelect(DB::raw('0 as roic'))
            ->addSelect(DB::raw('0 as roa'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as dong_tien_tu_hoat_dong_kd_chinh'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh/loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as ocf_lnst'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh + cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_tai_san_dai_han_khac + cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_tai_san_dai_han_khac as free_cash_flow'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_chinh_dai_han as no_dai_han'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ti_le_thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('( bs.tai_san_luu_dong_va_dau_tu_ngan_han - bs.tong_hang_ton_kho ) / bs.no_ngan_han as ti_le_thanh_toan_nhanh'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.nguon_von_chu_so_huu as tong_no_von_chu_so_huu'))
            ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_hang_ton_kho"))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_khoan_phai_thu"))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
            ->addSelect(DB::raw("0 as so_ngay_binh_quan_vong_quay_khoan_phai_tra"))
            ->addSelect(DB::raw('0 as roa2'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
            ->addSelect(DB::raw('( is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me) / is.doanh_thu_thuan as ti_suat_loi_nhuan'))
            ->addSelect(DB::raw('0 as roe2'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san / bs.von_chu_so_huu as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san3'))
            ->addSelect(DB::raw('( is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay ) / is.doanh_thu_thuan  as bien_ebit2'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / ( is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay ) as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me / is.tong_loi_nhuan_ke_toan_truoc_thue as ganh_nang_thue'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.tong_cong_tai_san as tai_san_ngan_han_tong_so_tai_san'))
            ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han / bs.tong_cong_tai_san as tai_san_dai_han_tong_so_tai_san'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.tong_cong_nguon_von as no_phai_tra_tong_nguon_von'))
            ->addSelect(DB::raw('bs.nguon_von_chu_so_huu / bs.tong_cong_nguon_von as nguon_von_chu_so_huu_tong_nguon_von'))
            ->addSelect(DB::raw('(bs.no_phai_tra - bs.nguoi_mua_tra_tien_truoc) / bs.tong_cong_nguon_von as ty_le_no_tts'))
            ->addSelect(DB::raw('(bs.no_phai_tra - bs.nguoi_mua_tra_tien_truoc) / bs.nguon_von_chu_so_huu as ty_le_no_vcsh'))
            ->addSelect(DB::raw('(bs.tien_va_cac_khoan_tuong_duong_tien + bs.cac_khoan_dau_tu_tai_chinh_ngan_han) / bs.no_ngan_han as ty_le_thanh_toan'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);;
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->orderByRaw(DB::raw("COALESCE(m.is_thoigian,3,m.bs_thoigian,3,m.cf_thoigian,3) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        for ($i = 0; $i < count($res) - 1; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? $res[$i]["cf_thoigian"] : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1))) {
                    if ($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1) != 0) {
                        $res[$i]["tang_truong_doanh_thu_thuan"] = $res[$i]["doanh_thu"] < 0 ? -1 : ($res[$i]["doanh_thu"] - $this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1)) / abs($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 1))) {
                    if ($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 1) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["loi_nhuan_rong"] < 0 ? -1 : ($res[$i]["loi_nhuan_rong"] - $this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 1)) / abs($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 1));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                $res[$i]['roe2'] = $res[$i]['roe'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i);
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                $res[$i]['vong_quay_tai_san3'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['vong_quay_tai_san'] = $res[$i]['doanh_thu'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "no_dai_han", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "no_dai_han", $i)) != 0) {
                            $res[$i]['roic'] = ($res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "no_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i)));
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                $res[$i]['roa2'] = $res[$i]['roa'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
            }
            if (!is_null($this->checkNullValueByYear($res, "gia_von_ban_hang", $i, $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i) != 0) {
                $res[$i]['vong_quay_hang_ton_kho'] = ($res[$i]['gia_von_ban_hang'] * 2 / ($res[$i + 1]['tong_hang_ton_kho'] + $res[$i]['tong_hang_ton_kho']));
                $res[$i]['so_ngay_binh_quan_vong_quay_hang_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? 365 / $res[$i]['vong_quay_hang_ton_kho'] : 0;
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_ngan_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "gia_von_ban_hang", $i, $i)) && !is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i)) && !is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['gia_von_ban_hang'] + $res[$i]['tong_hang_ton_kho'] - $res[$i + 1]['tong_hang_ton_kho']) * 2 / ($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_ngan_han", $i) + $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban_dai_han", $i));
                            $res[$i]['so_ngay_binh_quan_vong_quay_khoan_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_tra'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i)) && $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / $this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i);
            }
        }
        $res = array_slice($res, 0, count($res) - 1);
        $arr = [];
        for ($i = 11; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function getFinancialRatiosBank(Request $req)
    {
        $type = $req->input('thoigian');
        if ($type == "quarter")
            return $this->calculateFinancialRatiosBankQuarter($req);
        else if ($type == "ttm")
            return $this->calculateFinancialRatiosBankTTM($req);
        else
            return $this->calculateFinancialRatiosBankYear($req);
    }

    public function calculateFinancialRatiosBankQuarter(Request $req)
    {
        $order = $req->input('order') ? $req->input('order') : "asc";
        $limit = 100;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $page -= 1;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "bank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $tm = 'tm_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('tm.thoigian as tm_thoigian'))
            ->addSelect(DB::raw('bs.von_va_cac_quy as von_va_cac_quy'))
            ->addSelect(DB::raw('bs.loi_ich_cua_co_dong_thieu_so as loi_ich_cua_co_dong_thieu_so'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu as thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu'))
            ->addSelect(DB::raw('is.chi_phi_lai_va_cac_chi_phi_tuong_tu as chi_phi_lai_va_cac_chi_phi_tuong_tu'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue as tong_loi_nhuan_truoc_thue'))
            ->addSelect(DB::raw('bs.von_dieu_le as von_dieu_le'))
            ->addSelect(DB::raw('bs.cho_vay_khach_hang as cho_vay_khach_hang'))
            ->addSelect(DB::raw('bs.tien_gui_khach_hang as tien_gui_khach_hang'))
            ->addSelect(DB::raw('is.chi_phi_du_phong_rui_ro_tin_dung as chi_phi_du_phong_rui_ro_tin_dung'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_khac as lai_lo_thuan_tu_hoat_dong_khac'))
            ->addSelect(DB::raw('bs.du_phong_rui_ro_cvkh as du_phong_rui_ro_cvkh'))
            ->addSelect(DB::raw('tm.no_duoi_tieu_chuan + tm.no_nghi_ngo+tm.no_xau_co_kha_nang_mat_von as no_nhom_345'))
            ->addSelect(DB::raw('bs.tien_gui_tai_nhnn + bs.tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac +bs.tong_cho_vay_khach_hang + bs.chung_khoan_dau_tu as b_yoea'))
            ->addSelect(DB::raw('bs.cac_khoan_no_chinh_phu_va_nhnn + bs.tien_gui_va_cho_vay_cac_tctd_khac + bs.tien_gui_khach_hang + bs.phat_hanh_giay_to_co_gia as b_cof'))
            ->addSelect(DB::raw('bs.cac_khoan_lai_phi_phai_thu as cac_khoan_lai_phi_phai_thu'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('0 as gia_thi_truong'))
            ->addSelect(DB::raw('0 as von_hoa'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/(bs.von_dieu_le)*10000 as eps'))
            ->addSelect(DB::raw('0 as tang_truong_eps'))
            ->addSelect(DB::raw('(bs.von_va_cac_quy+bs.loi_ich_cua_co_dong_thieu_so-bs.tai_san_co_dinh_vo_hinh)/(bs.von_dieu_le/10000) as bvps'))
            ->addSelect(DB::raw('0 as tang_truong_bvps'))
            ->addSelect(DB::raw('0 as pe'))
            ->addSelect(DB::raw('0 as pb'))
            ->addSelect(DB::raw('is.thu_nhap_lai_thuan as thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('0 as tang_truong_thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_dich_vu as lai_lo_thuan_tu_hoat_dong_dich_vu'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi + is.lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh + is.lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu as lai_lo_thuan_tu_hoat_dong_dau_tu'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung - is.chi_phi_hoat_dong  as tong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.chi_phi_hoat_dong as chi_phi_hoat_dong'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung as loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai as lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as roea'))
            ->addSelect(DB::raw('0 as roaa'))
            ->addSelect(DB::raw('0 as yoea'))
            ->addSelect(DB::raw('0 as cof'))
            ->addSelect(DB::raw('0 as nim'))
            ->addSelect(DB::raw('0 as cir'))
            ->addSelect(DB::raw('(tm.tien_gui_khong_ky_han/bs.cho_vay_khach_hang) as casa'))
            ->addSelect(DB::raw('0 as du_no_cho_vay_tts_co'))
            ->addSelect(DB::raw('0 as vcsh_tvhd'))
            ->addSelect(DB::raw('0 as vcsh_tts_co'))
            ->addSelect(DB::raw('0 as ty_le_bao_no_xau_ttm'))
            ->addSelect(DB::raw('0 as ty_le_no_xau_npl_ttm'))
            ->addSelect(DB::raw('0 as lai_du_thu_ttm'))
            ->addSelect(DB::raw('0 as car_y'))
            ->addSelect(DB::raw('0 as dlr'))
            ->addSelect(DB::raw('0 as ty_le_du_phong'))
            ->addSelect(DB::raw('0 as tscl_tts_co'));

        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'is.mack')
                    ->on('tm.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'bs.mack')
                    ->on('tm.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'cf.mack')
                    ->on('tm.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_tm = DB::table($tm . ' as tm')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'tm.mack')
                    ->on('is.thoigian', '=', 'tm.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'tm.mack')
                    ->on('bs.thoigian', '=', 'tm.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'tm.mack')
                    ->on('cf.thoigian', '=', 'tm.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->whereNull("cf.mack")
            ->where("tm.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $table_tm->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                )
                ->union(
                    $table_tm
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2)),CONCAT(SUBSTR(m.tm_thoigian,3),substr(m.tm_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $d_car = DB::table('car_year_bank')
            ->addSelect("car")
            ->addSelect("thoigian")
            ->where("mack", $mack)
            ->get();
        $d_car = json_decode(json_encode($d_car), true);
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? (is_null($res[$i]["cf_thoigian"]) ? $res[$i]["tm_thoigian"] : $res[$i]["cf_thoigian"]) : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * ($res[$i]["von_dieu_le"] / 10000);
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                $res[$i]['pe'] = $res[$i]["gia_thi_truong"] / $this->calculate_ttm_contain_check_null($res, "eps", $i);
            }
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            $year = (int) substr($res[$i]["thoigian"], -4);
            $res[$i]["car_y"] = $this->search_car_by_year($d_car, $year);
            if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "eps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $res[$i]["eps"] < 0 ? -1 : ($res[$i]["eps"] - $this->checkNullValueByYear($res, "eps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "eps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_thu_nhap_lai_thuan"] = $res[$i]["thu_nhap_lai_thuan"] < 0 ? -1 : ($res[$i]["thu_nhap_lai_thuan"] - $this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai"] < 0 ? -1 : ($res[$i]["lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai"] - $this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_va_cac_quy", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "von_va_cac_quy", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i)) != 0) {
                            $res[$i]['roea'] = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "von_va_cac_quy", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i));
                        }
                    }
                }
            }
            if ($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                    $res[$i]['roaa'] = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i) != 0) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu", $i))) {
                    $res[$i]['yoea'] = $this->calculate_ttm_contain_check_null($res, "thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i);
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "b_cof", $i) != 0) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_lai_va_cac_chi_phi_tuong_tu", $i))) {
                    $res[$i]['cof'] = abs($this->calculate_ttm_contain_check_null($res, "chi_phi_lai_va_cac_chi_phi_tuong_tu", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "b_cof", $i));
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i) != 0) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i))) {
                    $res[$i]['nim'] = $this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i);
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i))) {
                    if (($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)) != 0) {
                        $res[$i]['cir'] = abs($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i) / ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_rui_ro_cvkh", $i, $i))) {
                if ($this->checkNullValueByYear($res, "no_nhom_345", $i, $i) != 0) {
                    $res[$i]['ty_le_bao_no_xau_ttm'] = abs($res[$i]['du_phong_rui_ro_cvkh'] / $res[$i]['no_nhom_345']);
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "no_nhom_345", $i, $i))) {
                if ($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i) != 0) {
                    $res[$i]['ty_le_no_xau_npl_ttm'] = $res[$i]['no_nhom_345'] / $res[$i]['cho_vay_khach_hang'];
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i))) {
                    if (($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)) != 0) {
                        if ($this->checkNullValueByYear($res, "cac_khoan_lai_phi_phai_thu", $i, $i) != 0) {
                            $res[$i]['lai_du_thu_ttm'] = $res[$i]['cac_khoan_lai_phi_phai_thu'] / ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i));
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i))) {
                if ($this->checkNullValueByYear($res, "b_cof", $i, $i) != 0) {
                    $res[$i]['dlr'] = $res[$i]['cho_vay_khach_hang'] / $res[$i]['b_cof'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i))) {
                if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                    $res[$i]['du_no_cho_vay_tts_co'] = $res[$i]['cho_vay_khach_hang'] / $res[$i]['tong_cong_tai_san'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "von_va_cac_quy", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_ich_cua_co_dong_thieu_so", $i, $i))) {
                    if ($this->checkNullValueByYear($res, "b_cof", $i, $i) != 0) {
                        $res[$i]['vcsh_tvhd'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so']) / $res[$i]['b_cof'];
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "von_va_cac_quy", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_ich_cua_co_dong_thieu_so", $i, $i))) {
                    if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                        $res[$i]['vcsh_tts_co'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so']) / $res[$i]['tong_cong_tai_san'];
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_rui_ro_cvkh", $i, $i))) {
                if ($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i) != 0) {
                    $res[$i]['ty_le_du_phong'] = $res[$i]['du_phong_rui_ro_cvkh'] / $res[$i]['cho_vay_khach_hang'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "b_yoea", $i, $i))) {
                if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                    $res[$i]['tscl_tts_co'] = $res[$i]['b_yoea'] / $res[$i]['tong_cong_tai_san'];
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        for ($i = 20; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function calculateFinancialRatiosBankTTM(Request $req)
    {
        $order = $req->input('order') ? $req->input('order') : "asc";
        $limit = 100;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $page -= 1;
        $thoigian = "quarter";
        $mack = strtoupper($req->input('mack'));
        $typeBank = "bank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $tm = 'tm_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('tm.thoigian as tm_thoigian'))
            ->addSelect(DB::raw('bs.von_va_cac_quy as von_va_cac_quy'))
            ->addSelect(DB::raw('bs.loi_ich_cua_co_dong_thieu_so as loi_ich_cua_co_dong_thieu_so'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu as thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu'))
            ->addSelect(DB::raw('is.chi_phi_lai_va_cac_chi_phi_tuong_tu as chi_phi_lai_va_cac_chi_phi_tuong_tu'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue as tong_loi_nhuan_truoc_thue'))
            ->addSelect(DB::raw('bs.von_dieu_le as von_dieu_le'))
            ->addSelect(DB::raw('bs.cho_vay_khach_hang as cho_vay_khach_hang'))
            ->addSelect(DB::raw('bs.tien_gui_khach_hang as tien_gui_khach_hang'))
            ->addSelect(DB::raw('is.chi_phi_du_phong_rui_ro_tin_dung as chi_phi_du_phong_rui_ro_tin_dung'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_khac as lai_lo_thuan_tu_hoat_dong_khac'))
            ->addSelect(DB::raw('bs.du_phong_rui_ro_cvkh as du_phong_rui_ro_cvkh'))
            ->addSelect(DB::raw('tm.no_duoi_tieu_chuan + tm.no_nghi_ngo+tm.no_xau_co_kha_nang_mat_von as no_nhom_345'))
            ->addSelect(DB::raw('bs.tien_gui_tai_nhnn + bs.tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac +bs.tong_cho_vay_khach_hang + bs.chung_khoan_dau_tu as b_yoea'))
            ->addSelect(DB::raw('bs.cac_khoan_no_chinh_phu_va_nhnn + bs.tien_gui_va_cho_vay_cac_tctd_khac + bs.tien_gui_khach_hang + bs.phat_hanh_giay_to_co_gia as b_cof'))
            ->addSelect(DB::raw('bs.cac_khoan_lai_phi_phai_thu as cac_khoan_lai_phi_phai_thu'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('0 as gia_thi_truong'))
            ->addSelect(DB::raw('0 as von_hoa'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/(bs.von_dieu_le)*10000 as eps'))
            ->addSelect(DB::raw('0 as tang_truong_eps'))
            ->addSelect(DB::raw('(bs.von_va_cac_quy+bs.loi_ich_cua_co_dong_thieu_so-bs.tai_san_co_dinh_vo_hinh)/(bs.von_dieu_le/10000) as bvps'))
            ->addSelect(DB::raw('0 as tang_truong_bvps'))
            ->addSelect(DB::raw('0 as pe'))
            ->addSelect(DB::raw('0 as pb'))
            ->addSelect(DB::raw('is.thu_nhap_lai_thuan as thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('0 as tang_truong_thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_dich_vu as lai_lo_thuan_tu_hoat_dong_dich_vu'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi + is.lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh + is.lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu as lai_lo_thuan_tu_hoat_dong_dau_tu'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung - is.chi_phi_hoat_dong  as tong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.chi_phi_hoat_dong as chi_phi_hoat_dong'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung as loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai as lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as roea'))
            ->addSelect(DB::raw('0 as roaa'))
            ->addSelect(DB::raw('0 as yoea'))
            ->addSelect(DB::raw('0 as cof'))
            ->addSelect(DB::raw('0 as nim'))
            ->addSelect(DB::raw('0 as cir'))
            ->addSelect(DB::raw('(tm.tien_gui_khong_ky_han/bs.cho_vay_khach_hang) as casa'))
            ->addSelect(DB::raw('0 as du_no_cho_vay_tts_co'))
            ->addSelect(DB::raw('0 as vcsh_tvhd'))
            ->addSelect(DB::raw('0 as vcsh_tts_co'))
            ->addSelect(DB::raw('0 as ty_le_bao_no_xau_ttm'))
            ->addSelect(DB::raw('0 as ty_le_no_xau_npl_ttm'))
            ->addSelect(DB::raw('0 as lai_du_thu_ttm'))
            ->addSelect(DB::raw('0 as car_y'))
            ->addSelect(DB::raw('0 as dlr'))
            ->addSelect(DB::raw('0 as ty_le_du_phong'))
            ->addSelect(DB::raw('0 as tscl_tts_co'));

        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'is.mack')
                    ->on('tm.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'bs.mack')
                    ->on('tm.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'cf.mack')
                    ->on('tm.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_tm = DB::table($tm . ' as tm')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'tm.mack')
                    ->on('is.thoigian', '=', 'tm.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'tm.mack')
                    ->on('bs.thoigian', '=', 'tm.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'tm.mack')
                    ->on('cf.thoigian', '=', 'tm.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->whereNull("cf.mack")
            ->where("tm.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $table_tm->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                )
                ->union(
                    $table_tm
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 8)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2)),CONCAT(SUBSTR(m.tm_thoigian,3),substr(m.tm_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $d_car = DB::table('car_year_bank')
            ->addSelect("car")
            ->addSelect("thoigian")
            ->where("mack", $mack)
            ->get();
        $d_car = json_decode(json_encode($d_car), true);
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 8; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? (is_null($res[$i]["cf_thoigian"]) ? $res[$i]["tm_thoigian"] : $res[$i]["cf_thoigian"]) : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * ($res[$i]["von_dieu_le"] / 10000);
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "eps", $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "eps", $i) - $this->calculate_ttm_contain_check_null($res, "eps", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "eps", $i + 4));
                    }
                }
            }
            $res[$i]["eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $res[$i]['pe'] = $res[$i]["eps"] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]["eps"] : 0;
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            $year = (int) substr($res[$i]["thoigian"], -4);
            $res[$i]["car_y"] = $this->search_car_by_year($d_car, $year);

            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i + 4) != 0) {
                        $res[$i]["tang_truong_thu_nhap_lai_thuan"] = $this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i) - $this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i + 4));
                    }
                }
            }
            $res[$i]["thu_nhap_lai_thuan"] = $this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i);
            if (!is_null($this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i) - $this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i + 4));
                    }
                }
            }
            $res[$i]["lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai"] = $this->calculate_ttm_contain_check_null($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i);
            $res[$i]["lai_lo_thuan_tu_hoat_dong_dich_vu"] = $this->calculate_ttm_contain_check_null($res, "lai_lo_thuan_tu_hoat_dong_dich_vu", $i);
            $res[$i]["lai_lo_thuan_tu_hoat_dong_dau_tu"] = $this->calculate_ttm_contain_check_null($res, "lai_lo_thuan_tu_hoat_dong_dau_tu", $i);
            $res[$i]["tong_doanh_thu_hoat_dong"] = $this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i);
            $res[$i]["chi_phi_hoat_dong"] = $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i);
            $res[$i]["loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i);

            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "von_va_cac_quy", $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                        if (($this->calculate_before_4_quarter_contain_check_null($res, "von_va_cac_quy", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i)) != 0) {
                            $res[$i]['roea'] = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "von_va_cac_quy", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i));
                        }
                    }
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                    $res[$i]['roaa'] = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i) != 0) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu", $i))) {
                    $res[$i]['yoea'] = $this->calculate_ttm_contain_check_null($res, "thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i);
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "b_cof", $i) != 0) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_lai_va_cac_chi_phi_tuong_tu", $i))) {
                    $res[$i]['cof'] = abs($this->calculate_ttm_contain_check_null($res, "chi_phi_lai_va_cac_chi_phi_tuong_tu", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "b_cof", $i));
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i) != 0) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i))) {
                    $res[$i]['nim'] = $this->calculate_ttm_contain_check_null($res, "thu_nhap_lai_thuan", $i) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "b_yoea", $i);
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i))) {
                    if (($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)) != 0) {
                        $res[$i]['cir'] = abs($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i) / ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_rui_ro_cvkh", $i, $i))) {
                if ($this->checkNullValueByYear($res, "no_nhom_345", $i, $i) != 0) {
                    $res[$i]['ty_le_bao_no_xau_ttm'] = abs($res[$i]['du_phong_rui_ro_cvkh'] / $res[$i]['no_nhom_345']);
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "no_nhom_345", $i, $i))) {
                if ($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i) != 0) {
                    $res[$i]['ty_le_no_xau_npl_ttm'] = $res[$i]['no_nhom_345'] / $res[$i]['cho_vay_khach_hang'];
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i))) {
                    if (($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)) != 0) {
                        if ($this->checkNullValueByYear($res, "cac_khoan_lai_phi_phai_thu", $i, $i) != 0) {
                            $res[$i]['lai_du_thu_ttm'] = $res[$i]['cac_khoan_lai_phi_phai_thu'] / ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i));
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i))) {
                if ($this->checkNullValueByYear($res, "b_cof", $i, $i) != 0) {
                    $res[$i]['dlr'] = $res[$i]['cho_vay_khach_hang'] / $res[$i]['b_cof'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i))) {
                if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                    $res[$i]['du_no_cho_vay_tts_co'] = $res[$i]['cho_vay_khach_hang'] / $res[$i]['tong_cong_tai_san'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "von_va_cac_quy", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_ich_cua_co_dong_thieu_so", $i, $i))) {
                    if ($this->checkNullValueByYear($res, "b_cof", $i, $i) != 0) {
                        $res[$i]['vcsh_tvhd'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so']) / $res[$i]['b_cof'];
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "von_va_cac_quy", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_ich_cua_co_dong_thieu_so", $i, $i))) {
                    if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                        $res[$i]['vcsh_tts_co'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so']) / $res[$i]['tong_cong_tai_san'];
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_rui_ro_cvkh", $i, $i))) {
                if ($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i) != 0) {
                    $res[$i]['ty_le_du_phong'] = $res[$i]['du_phong_rui_ro_cvkh'] / $res[$i]['cho_vay_khach_hang'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "b_yoea", $i, $i))) {
                if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                    $res[$i]['tscl_tts_co'] = $res[$i]['b_yoea'] / $res[$i]['tong_cong_tai_san'];
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 8);
        $arr = [];
        for ($i = 20; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function calculateFinancialRatiosBankYear(Request $req)
    {
        $limit = 100;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "bank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $tm = 'tm_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('tm.thoigian as tm_thoigian'))
            ->addSelect(DB::raw('bs.von_va_cac_quy as von_va_cac_quy'))
            ->addSelect(DB::raw('bs.loi_ich_cua_co_dong_thieu_so as loi_ich_cua_co_dong_thieu_so'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('is.thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu as thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu'))
            ->addSelect(DB::raw('is.chi_phi_lai_va_cac_chi_phi_tuong_tu as chi_phi_lai_va_cac_chi_phi_tuong_tu'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue as tong_loi_nhuan_truoc_thue'))
            ->addSelect(DB::raw('bs.von_dieu_le as von_dieu_le'))
            ->addSelect(DB::raw('bs.cho_vay_khach_hang as cho_vay_khach_hang'))
            ->addSelect(DB::raw('bs.tien_gui_khach_hang as tien_gui_khach_hang'))
            ->addSelect(DB::raw('is.chi_phi_du_phong_rui_ro_tin_dung as chi_phi_du_phong_rui_ro_tin_dung'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_khac as lai_lo_thuan_tu_hoat_dong_khac'))
            ->addSelect(DB::raw('bs.du_phong_rui_ro_cvkh as du_phong_rui_ro_cvkh'))
            ->addSelect(DB::raw('tm.no_duoi_tieu_chuan + tm.no_nghi_ngo+tm.no_xau_co_kha_nang_mat_von as no_nhom_345'))
            ->addSelect(DB::raw('bs.tien_gui_tai_nhnn + bs.tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac +bs.tong_cho_vay_khach_hang + bs.chung_khoan_dau_tu as b_yoea'))
            ->addSelect(DB::raw('bs.cac_khoan_no_chinh_phu_va_nhnn + bs.tien_gui_va_cho_vay_cac_tctd_khac + bs.tien_gui_khach_hang + bs.phat_hanh_giay_to_co_gia as b_cof'))
            ->addSelect(DB::raw('bs.cac_khoan_lai_phi_phai_thu as cac_khoan_lai_phi_phai_thu'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('is.thu_nhap_lai_thuan as thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('0 as tang_truong_thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_dich_vu as lai_lo_thuan_tu_hoat_dong_dich_vu'))
            ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi + is.lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh + is.lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu as lai_lo_thuan_tu_hoat_dong_dau_tu'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung - is.chi_phi_hoat_dong  as tong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.chi_phi_hoat_dong as chi_phi_hoat_dong'))
            ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung as loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai as lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as roea'))
            ->addSelect(DB::raw('0 as roaa'))
            ->addSelect(DB::raw('0 as yoea'))
            ->addSelect(DB::raw('0 as cof'))
            ->addSelect(DB::raw('0 as nim'))
            ->addSelect(DB::raw('0 as cir'))
            ->addSelect(DB::raw('(tm.tien_gui_khong_ky_han/bs.cho_vay_khach_hang) as casa'))
            ->addSelect(DB::raw('0 as du_no_cho_vay_tts_co'))
            ->addSelect(DB::raw('0 as vcsh_tvhd'))
            ->addSelect(DB::raw('0 as vcsh_tts_co'))
            ->addSelect(DB::raw('0 as ty_le_bao_no_xau_ttm'))
            ->addSelect(DB::raw('0 as ty_le_no_xau_npl_ttm'))
            ->addSelect(DB::raw('0 as lai_du_thu_ttm'))
            ->addSelect(DB::raw('0 as car_y'))
            ->addSelect(DB::raw('0 as dlr'))
            ->addSelect(DB::raw('0 as ty_le_du_phong'))
            ->addSelect(DB::raw('0 as tscl_tts_co'));

        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'is.mack')
                    ->on('tm.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'bs.mack')
                    ->on('tm.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'cf.mack')
                    ->on('tm.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_tm = DB::table($tm . ' as tm')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'tm.mack')
                    ->on('is.thoigian', '=', 'tm.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'tm.mack')
                    ->on('bs.thoigian', '=', 'tm.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'tm.mack')
                    ->on('cf.thoigian', '=', 'tm.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->whereNull("cf.mack")
            ->where("tm.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $table_tm->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                )
                ->union(
                    $table_tm
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->orderByRaw(DB::raw("COALESCE(m.is_thoigian,m.bs_thoigian,m.cf_thoigian,m.tm_thoigian) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $d_car = DB::table('car_year_bank')
            ->addSelect("car")
            ->addSelect("thoigian")
            ->where("mack", $mack)
            ->get();
        $d_car = json_decode(json_encode($d_car), true);
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? (is_null($res[$i]["cf_thoigian"]) ? $res[$i]["tm_thoigian"] : $res[$i]["cf_thoigian"]) : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $year = (int) substr($res[$i]["thoigian"], -4);
            $res[$i]["car_y"] = $this->search_car_by_year($d_car, $year);
            if (!is_null($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 1))) {
                    if ($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 1) != 0) {
                        $res[$i]["tang_truong_thu_nhap_lai_thuan"] = $res[$i]["thu_nhap_lai_thuan"] < 0 ? -1 : ($res[$i]["thu_nhap_lai_thuan"] - $this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 1)) / abs($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i + 1));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 1))) {
                    if ($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 1) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai"] < 0 ? -1 : ($res[$i]["lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai"] - $this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 1)) / abs($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i + 1));
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_va_cac_quy", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "von_va_cac_quy", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i)) != 0) {
                            $res[$i]['roea'] = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "von_va_cac_quy", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_cua_co_dong_thieu_so", $i));
                        }
                    }
                }
            }
            if ($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai", $i, $i))) {
                    $res[$i]['roaa'] = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if (($this->checkNullValueByYear($res, "b_yoea", $i, $i) + $this->checkNullValueByYear($res, "b_yoea", $i, $i + 1)) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu", $i, $i))) {
                    $res[$i]['yoea'] = $this->checkNullValueByYear($res, "thu_nhap_tu_lai_va_cac_khoan_thu_nhap_tuong_tu", $i, $i) * 2 / ($this->checkNullValueByYear($res, "b_yoea", $i, $i) + $this->checkNullValueByYear($res, "b_yoea", $i, $i + 1));
                }
            }
            if (($this->checkNullValueByYear($res, "b_cof", $i, $i) + $this->checkNullValueByYear($res, "b_cof", $i, $i + 1)) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "chi_phi_lai_va_cac_chi_phi_tuong_tu", $i, $i))) {
                    $res[$i]['cof'] = abs($this->checkNullValueByYear($res, "chi_phi_lai_va_cac_chi_phi_tuong_tu", $i, $i) * 2 / ($this->checkNullValueByYear($res, "b_cof", $i, $i) + $this->checkNullValueByYear($res, "b_cof", $i, $i + 1)));
                }
            }
            if (($this->checkNullValueByYear($res, "b_yoea", $i, $i) + $this->checkNullValueByYear($res, "b_yoea", $i, $i + 1)) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i))) {
                    $res[$i]['nim'] = $this->checkNullValueByYear($res, "thu_nhap_lai_thuan", $i, $i) * 2 / ($this->checkNullValueByYear($res, "b_yoea", $i, $i) + $this->checkNullValueByYear($res, "b_yoea", $i, $i + 1));
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "chi_phi_hoat_dong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i, $i))) {
                    if (($this->checkNullValueByYear($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i, $i) - $this->checkNullValueByYear($res, "chi_phi_hoat_dong", $i, $i)) != 0) {
                        $res[$i]['cir'] = abs($this->checkNullValueByYear($res, "chi_phi_hoat_dong", $i, $i) / ($this->checkNullValueByYear($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i, $i) - $this->checkNullValueByYear($res, "chi_phi_hoat_dong", $i, $i)));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_rui_ro_cvkh", $i, $i))) {
                if ($this->checkNullValueByYear($res, "no_nhom_345", $i, $i) != 0) {
                    $res[$i]['ty_le_bao_no_xau_ttm'] = abs($res[$i]['du_phong_rui_ro_cvkh'] / $res[$i]['no_nhom_345']);
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "no_nhom_345", $i, $i))) {
                if ($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i) != 0) {
                    $res[$i]['ty_le_no_xau_npl_ttm'] = $res[$i]['no_nhom_345'] / $res[$i]['cho_vay_khach_hang'];
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i))) {
                    if (($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i)) != 0) {
                        if ($this->checkNullValueByYear($res, "cac_khoan_lai_phi_phai_thu", $i, $i) != 0) {
                            $res[$i]['lai_du_thu_ttm'] = $res[$i]['cac_khoan_lai_phi_phai_thu'] / ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung", $i) - $this->calculate_ttm_contain_check_null($res, "chi_phi_hoat_dong", $i));
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i))) {
                if ($this->checkNullValueByYear($res, "b_cof", $i, $i) != 0) {
                    $res[$i]['dlr'] = $res[$i]['cho_vay_khach_hang'] / $res[$i]['b_cof'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i))) {
                if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                    $res[$i]['du_no_cho_vay_tts_co'] = $res[$i]['cho_vay_khach_hang'] / $res[$i]['tong_cong_tai_san'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "von_va_cac_quy", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_ich_cua_co_dong_thieu_so", $i, $i))) {
                    if ($this->checkNullValueByYear($res, "b_cof", $i, $i) != 0) {
                        $res[$i]['vcsh_tvhd'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so']) / $res[$i]['b_cof'];
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "von_va_cac_quy", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_ich_cua_co_dong_thieu_so", $i, $i))) {
                    if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                        $res[$i]['vcsh_tts_co'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so']) / $res[$i]['tong_cong_tai_san'];
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_rui_ro_cvkh", $i, $i))) {
                if ($this->checkNullValueByYear($res, "cho_vay_khach_hang", $i, $i) != 0) {
                    $res[$i]['ty_le_du_phong'] = $res[$i]['du_phong_rui_ro_cvkh'] / $res[$i]['cho_vay_khach_hang'];
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "b_yoea", $i, $i))) {
                if ($this->checkNullValueByYear($res, "tong_cong_tai_san", $i, $i) != 0) {
                    $res[$i]['tscl_tts_co'] = $res[$i]['b_yoea'] / $res[$i]['tong_cong_tai_san'];
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        for ($i = 20; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function getFinancialRatiosStock(Request $req)
    {
        $type = $req->input('thoigian');
        if ($type == "quarter")
            return $this->calculateFinancialRatiosStockQuarter($req);
        else if ($type == "ttm")
            return $this->calculateFinancialRatiosStockTTM($req);
        else
            return $this->calculateFinancialRatiosStockYear($req);
    }

    public function calculateFinancialRatiosStockQuarter(Request $req)
    {
        $limit = 100;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "stock";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('0 as gia_thi_truong'))
            ->addSelect(DB::raw('bs.von_gop_cua_chu_so_huu / 10000 as von_hoa'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_san_tai_chinh_dai_han + bs.vay_va_no_thue_tai_san_tai_chinh_ngan_han - bs.tien_va_cac_khoan_tuong_duong_tien as gia_tri_doanh_nghiep'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn/(bs.von_gop_cua_chu_so_huu/10000) as eps'))
            ->addSelect(DB::raw('0 as tang_truong_eps'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh) / (bs.von_gop_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('0 as tang_truong_bvps'))
            ->addSelect(DB::raw('0 as pe'))
            ->addSelect(DB::raw('0 as pb'))
            ->addSelect(DB::raw('0 as evebit'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong as doanh_thu'))
            ->addSelect(DB::raw('0 as tang_truong_cong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan as doanh_thu_moi_gioi_chung_khoan'))
            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan - is.chi_phi_moi_gioi_chung_khoan as loi_nhuan_tu_hoat_dong_moi_gioi'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu as lai_tu_cac_khoan_cho_vay_va_phai_thu'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu - is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu as loi_nhuan_tu_hoat_dong_cho_vay'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan - is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan as loi_nhuan_tu_hoat_dong_bao_lanh'))
            ->addSelect(DB::raw('is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs as doanh_thu_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('(is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) - (is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh) as loi_nhuan_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong - is.cong_chi_phi_hoat_dong as cong_loi_nhuan_gop_hoat_dong'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as tong_loi_nhuan_ke_toan_truoc_thue'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn as loi_nhuan_ke_toan_sau_thue_tndn'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('(is.doanh_thu_moi_gioi_chung_khoan - is.chi_phi_moi_gioi_chung_khoan)/is.doanh_thu_moi_gioi_chung_khoan as bien_loi_nhuan_tu_hoat_dong_moi_gioi'))
            ->addSelect(DB::raw('(is.lai_tu_cac_khoan_cho_vay_va_phai_thu - is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu)/lai_tu_cac_khoan_cho_vay_va_phai_thu as bien_loi_nhuan_tu_hoat_dong_cho_vay'))
            ->addSelect(DB::raw('(is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan - is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan)/is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as bien_loi_nhuan_tu_hoat_dong_bao_lanh'))
            ->addSelect(DB::raw('((is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) - (is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh))/(is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) as bien_loi_nhuan_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('(is.cong_doanh_thu_hoat_dong - is.cong_chi_phi_hoat_dong)/(is.cong_doanh_thu_hoat_dong) as bien_loi_nhuan_gop'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay)/cong_doanh_thu_hoat_dong as bien_ebit'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue)/cong_doanh_thu_hoat_dong as bien_ebt'))
            ->addSelect(DB::raw('(is.loi_nhuan_ke_toan_sau_thue_tndn)/cong_doanh_thu_hoat_dong as bien_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as roaa'))
            ->addSelect(DB::raw('0 as roea'))
            ->addSelect(DB::raw('abs(bs.du_phong_suy_giam_gia_tri_cac_tai_san_tai_chinh/(bs.cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + bs.cac_khoan_dau_tu_giu_den_ngay_dao_han_htm + bs.cac_khoan_cho_vay + bs.cac_tai_san_tai_chinh_san_sang_de_ban_afs)) as ti_le_du_phong_suy_giam_gia_tri_tstc'))
            ->addSelect(DB::raw('abs(bs.du_phong_suy_giam_gia_tri_cac_khoan_phai_thu/bs.cac_khoan_phai_thu) as ti_le_du_phong_suy_giam_gia_tri_khoan_phai_thu'))
            ->addSelect(DB::raw('bs.cac_khoan_cho_vay/bs.von_chu_so_huu as ti_le_du_no_margin_vcsh'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han/bs.no_phai_tra_ngan_han as ti_le_thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_von_chu_so_huu as de'))
            ->addSelect(DB::raw('0 as roaa2'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('(is.loi_nhuan_ke_toan_sau_thue_tndn)/cong_doanh_thu_hoat_dong as ti_suat_loi_nhuan'))
            ->addSelect(DB::raw('0 as roea2'))
            ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay)/cong_doanh_thu_hoat_dong as bien_ebit2'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / (is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay) as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn / (is.tong_loi_nhuan_ke_toan_truoc_thue) as ganh_nang_thue'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han / bs.tong_cong_tai_san as tsnh_tts'))
            ->addSelect(DB::raw('bs.tai_san_dai_han / bs.tong_cong_tai_san as tsdh_tts'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.tong_cong_no_phai_tra_va_von_chu_so_huu as bpt_tnv'))
            ->addSelect(DB::raw('bs.tong_von_chu_so_huu / bs.tong_cong_no_phai_tra_va_von_chu_so_huu as tvcsh_tnv'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? $res[$i]["cf_thoigian"] : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * $res[$i]["von_hoa"];
            $res[$i]["gia_tri_doanh_nghiep"] = $res[$i]["von_hoa"] + $res[$i]["gia_tri_doanh_nghiep"];
            if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "eps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $res[$i]["eps"] < 0 ? -1 : ($res[$i]["eps"] - $this->checkNullValueByYear($res, "eps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "eps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_cong_doanh_thu_hoat_dong"] = $res[$i]["doanh_thu"] < 0 ? -1 : ($res[$i]["doanh_thu"] - $this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] < 0 ? -1 : ($res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] - $res[$i + 4]["loi_nhuan_ke_toan_sau_thue_tndn"]) / abs($res[$i + 4]["loi_nhuan_ke_toan_sau_thue_tndn"]);
                    }
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                $res[$i]['pe'] = $res[$i]["gia_thi_truong"] / $this->calculate_ttm_contain_check_null($res, "eps", $i);
            }
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            if (!is_null($this->calculate_ttm_contain_check_null($res, "ebit", $i))) {
                $res[$i]['evebit'] = $res[$i]["gia_tri_doanh_nghiep"] / $this->calculate_ttm_contain_check_null($res, "ebit", $i);
            }
            if ($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                    $res[$i]['roaa'] = $res[$i]['roaa2'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                    $res[$i]['vong_quay_tai_san'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['doanh_thu'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if ($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                    $res[$i]['roea'] = $res[$i]['roea2'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] * 2 / ($res[$i]['von_chu_so_huu'] + $res[$i + 1]['von_chu_so_huu']);
                }
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                    $res[$i]['don_bay_tai_chinh'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 1]['tong_cong_tai_san']) / ($res[$i]['von_chu_so_huu'] + $res[$i + 1]['von_chu_so_huu']);
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 4);
        // dd($res);
        $arr = [];
        for ($i = 5; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function calculateFinancialRatiosStockTTM(Request $req)
    {
        $limit = 100;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $thoigian = "quarter";
        $mack = strtoupper($req->input('mack'));
        $typeBank = "stock";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('0 as gia_thi_truong'))
            ->addSelect(DB::raw('bs.von_gop_cua_chu_so_huu / 10000 as von_hoa'))
            ->addSelect(DB::raw('bs.vay_va_no_thue_tai_san_tai_chinh_dai_han + bs.vay_va_no_thue_tai_san_tai_chinh_ngan_han - bs.tien_va_cac_khoan_tuong_duong_tien as gia_tri_doanh_nghiep'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn/(bs.von_gop_cua_chu_so_huu/10000) as eps'))
            ->addSelect(DB::raw('0 as tang_truong_eps'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh) / (bs.von_gop_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('0 as tang_truong_bvps'))
            ->addSelect(DB::raw('0 as pe'))
            ->addSelect(DB::raw('0 as pb'))
            ->addSelect(DB::raw('0 as evebit'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong as doanh_thu'))
            ->addSelect(DB::raw('0 as tang_truong_cong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan as doanh_thu_moi_gioi_chung_khoan'))
            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan - is.chi_phi_moi_gioi_chung_khoan as loi_nhuan_tu_hoat_dong_moi_gioi'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu as lai_tu_cac_khoan_cho_vay_va_phai_thu'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu - is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu as loi_nhuan_tu_hoat_dong_cho_vay'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan - is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan as loi_nhuan_tu_hoat_dong_bao_lanh'))
            ->addSelect(DB::raw('is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs as doanh_thu_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('(is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) - (is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh) as loi_nhuan_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong - is.cong_chi_phi_hoat_dong as cong_loi_nhuan_gop_hoat_dong'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as tong_loi_nhuan_ke_toan_truoc_thue'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn as loi_nhuan_ke_toan_sau_thue_tndn'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('(is.doanh_thu_moi_gioi_chung_khoan - is.chi_phi_moi_gioi_chung_khoan)/is.doanh_thu_moi_gioi_chung_khoan as bien_loi_nhuan_tu_hoat_dong_moi_gioi'))
            ->addSelect(DB::raw('(is.lai_tu_cac_khoan_cho_vay_va_phai_thu - is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu)/lai_tu_cac_khoan_cho_vay_va_phai_thu as bien_loi_nhuan_tu_hoat_dong_cho_vay'))
            ->addSelect(DB::raw('(is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan - is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan)/is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as bien_loi_nhuan_tu_hoat_dong_bao_lanh'))
            ->addSelect(DB::raw('((is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) - (is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh))/(is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) as bien_loi_nhuan_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('(is.cong_doanh_thu_hoat_dong - is.cong_chi_phi_hoat_dong)/(is.cong_doanh_thu_hoat_dong) as bien_loi_nhuan_gop'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay)/cong_doanh_thu_hoat_dong as bien_ebit'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue)/cong_doanh_thu_hoat_dong as bien_ebt'))
            ->addSelect(DB::raw('(is.loi_nhuan_ke_toan_sau_thue_tndn)/cong_doanh_thu_hoat_dong as bien_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as roaa'))
            ->addSelect(DB::raw('0 as roea'))
            ->addSelect(DB::raw('abs(bs.du_phong_suy_giam_gia_tri_cac_tai_san_tai_chinh/(bs.cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + bs.cac_khoan_dau_tu_giu_den_ngay_dao_han_htm + bs.cac_khoan_cho_vay + bs.cac_tai_san_tai_chinh_san_sang_de_ban_afs)) as ti_le_du_phong_suy_giam_gia_tri_tstc'))
            ->addSelect(DB::raw('abs(bs.du_phong_suy_giam_gia_tri_cac_khoan_phai_thu/bs.cac_khoan_phai_thu) as ti_le_du_phong_suy_giam_gia_tri_khoan_phai_thu'))
            ->addSelect(DB::raw('bs.cac_khoan_cho_vay/bs.von_chu_so_huu as ti_le_du_no_margin_vcsh'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han/bs.no_phai_tra_ngan_han as ti_le_thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_von_chu_so_huu as de'))
            ->addSelect(DB::raw('0 as roaa2'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('(is.loi_nhuan_ke_toan_sau_thue_tndn)/cong_doanh_thu_hoat_dong as ti_suat_loi_nhuan'))
            ->addSelect(DB::raw('0 as roea2'))
            ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay)/cong_doanh_thu_hoat_dong as bien_ebit2'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / (is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay) as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn / (is.tong_loi_nhuan_ke_toan_truoc_thue) as ganh_nang_thue'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han / bs.tong_cong_tai_san as tsnh_tts'))
            ->addSelect(DB::raw('bs.tai_san_dai_han / bs.tong_cong_tai_san as tsdh_tts'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.tong_cong_no_phai_tra_va_von_chu_so_huu as bpt_tnv'))
            ->addSelect(DB::raw('bs.tong_von_chu_so_huu / bs.tong_cong_no_phai_tra_va_von_chu_so_huu as tvcsh_tnv'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 8)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 8; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? $res[$i]["cf_thoigian"] : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * $res[$i]["von_hoa"];
            $res[$i]["gia_tri_doanh_nghiep"] = $res[$i]["von_hoa"] + $res[$i]["gia_tri_doanh_nghiep"];
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "eps", $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "eps", $i) - $this->calculate_ttm_contain_check_null($res, "eps", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "eps", $i + 4));
                    }
                }
            }
            $res[$i]["eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $res[$i]['pe'] = $res[$i]["eps"] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]["eps"] : 0;
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4) != 0) {
                        $res[$i]["tang_truong_cong_doanh_thu_hoat_dong"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i) - $this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "doanh_thu", $i + 4));
                    }
                }
            }
            $res[$i]["doanh_thu"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu", $i);
            if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i) - $this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i + 4));
                    }
                }
            }
            $res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i);
            $res[$i]["doanh_thu_moi_gioi_chung_khoan"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu_moi_gioi_chung_khoan", $i);
            $res[$i]["loi_nhuan_tu_hoat_dong_moi_gioi"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hoat_dong_moi_gioi", $i);
            $res[$i]["lai_tu_cac_khoan_cho_vay_va_phai_thu"] = $this->calculate_ttm_contain_check_null($res, "lai_tu_cac_khoan_cho_vay_va_phai_thu", $i);
            $res[$i]["loi_nhuan_tu_hoat_dong_cho_vay"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hoat_dong_cho_vay", $i);
            $res[$i]["doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan", $i);
            $res[$i]["loi_nhuan_tu_hoat_dong_bao_lanh"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hoat_dong_bao_lanh", $i);
            $res[$i]["doanh_thu_tu_hoat_dong_tu_doanh"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu_tu_hoat_dong_tu_doanh", $i);
            $res[$i]["loi_nhuan_tu_hoat_dong_tu_doanh"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hoat_dong_tu_doanh", $i);
            $res[$i]["cong_loi_nhuan_gop_hoat_dong"] = $this->calculate_ttm_contain_check_null($res, "cong_loi_nhuan_gop_hoat_dong", $i);
            $res[$i]["ebit"] = $this->calculate_ttm_contain_check_null($res, "ebit", $i);
            $res[$i]["tong_loi_nhuan_ke_toan_truoc_thue"] = $this->calculate_ttm_contain_check_null($res, "tong_loi_nhuan_ke_toan_truoc_thue", $i);

            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            $res[$i]['evebit'] = $res[$i]["ebit"] != 0 ? $res[$i]["gia_tri_doanh_nghiep"] / $res[$i]["ebit"] : 0;
            $res[$i]['bien_loi_nhuan_tu_hoat_dong_moi_gioi'] = $res[$i]["doanh_thu_moi_gioi_chung_khoan"] != 0 ? $res[$i]["loi_nhuan_tu_hoat_dong_moi_gioi"] / $res[$i]["doanh_thu_moi_gioi_chung_khoan"] : 0;
            $res[$i]['bien_loi_nhuan_tu_hoat_dong_cho_vay'] = $res[$i]["lai_tu_cac_khoan_cho_vay_va_phai_thu"] != 0 ? $res[$i]["loi_nhuan_tu_hoat_dong_cho_vay"] / $res[$i]["lai_tu_cac_khoan_cho_vay_va_phai_thu"] : 0;
            $res[$i]['bien_loi_nhuan_tu_hoat_dong_bao_lanh'] = $res[$i]["doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan"] != 0 ? $res[$i]["loi_nhuan_tu_hoat_dong_bao_lanh"] / $res[$i]["doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan"] : 0;
            $res[$i]['bien_loi_nhuan_tu_hoat_dong_tu_doanh'] = $res[$i]["doanh_thu_tu_hoat_dong_tu_doanh"] != 0 ? $res[$i]["loi_nhuan_tu_hoat_dong_tu_doanh"] / $res[$i]["doanh_thu_tu_hoat_dong_tu_doanh"] : 0;
            $res[$i]['bien_loi_nhuan_gop'] = $res[$i]["doanh_thu"] != 0 ? $res[$i]["cong_loi_nhuan_gop_hoat_dong"] / $res[$i]["doanh_thu"] : 0;
            $res[$i]['bien_ebit2'] = $res[$i]['bien_ebit'] = $res[$i]["doanh_thu"] != 0 ? $res[$i]["ebit"] / $res[$i]["doanh_thu"] : 0;
            $res[$i]['bien_ebt'] = $res[$i]["doanh_thu"] != 0 ? $res[$i]["tong_loi_nhuan_ke_toan_truoc_thue"] / $res[$i]["doanh_thu"] : 0;
            $res[$i]['ti_suat_loi_nhuan'] = $res[$i]['bien_loi_nhuan_sau_thue'] = $res[$i]["doanh_thu"] != 0 ? $res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] / $res[$i]["doanh_thu"] : 0;
            $res[$i]['ganh_nang_lai_suat'] = $res[$i]["ebit"] != 0 ? $res[$i]["tong_loi_nhuan_ke_toan_truoc_thue"] / $res[$i]["ebit"] : 0;
            $res[$i]['ganh_nang_thue'] = $res[$i]["tong_loi_nhuan_ke_toan_truoc_thue"] != 0 ? $res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] / $res[$i]["tong_loi_nhuan_ke_toan_truoc_thue"] : 0;

            if ($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                    $res[$i]['roaa'] = $res[$i]['roaa2'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                    $res[$i]['vong_quay_tai_san'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['doanh_thu'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if ($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                    $res[$i]['roea'] = $res[$i]['roea2'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i);
                }
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                    $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / $this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i);
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 8);
        // dd($res);
        $arr = [];
        for ($i = 5; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function calculateFinancialRatiosStockYear(Request $req)
    {
        $limit = $req->input('thoigian') === "quarter" ? 100 : 10;
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "stock";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong as doanh_thu'))
            ->addSelect(DB::raw('0 as tang_truong_cong_doanh_thu_hoat_dong'))
            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan as doanh_thu_moi_gioi_chung_khoan'))
            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan - is.chi_phi_moi_gioi_chung_khoan as loi_nhuan_tu_hoat_dong_moi_gioi'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu as lai_tu_cac_khoan_cho_vay_va_phai_thu'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu - is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu as loi_nhuan_tu_hoat_dong_cho_vay'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan - is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan as loi_nhuan_tu_hoat_dong_bao_lanh'))
            ->addSelect(DB::raw('is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs as doanh_thu_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('(is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) - (is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh) as loi_nhuan_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('is.cong_doanh_thu_hoat_dong - is.cong_chi_phi_hoat_dong as cong_loi_nhuan_gop_hoat_dong'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue as tong_loi_nhuan_ke_toan_truoc_thue'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn as loi_nhuan_ke_toan_sau_thue_tndn'))
            ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('(is.doanh_thu_moi_gioi_chung_khoan - is.chi_phi_moi_gioi_chung_khoan)/is.doanh_thu_moi_gioi_chung_khoan as bien_loi_nhuan_tu_hoat_dong_moi_gioi'))
            ->addSelect(DB::raw('(is.lai_tu_cac_khoan_cho_vay_va_phai_thu - is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu)/lai_tu_cac_khoan_cho_vay_va_phai_thu as bien_loi_nhuan_tu_hoat_dong_cho_vay'))
            ->addSelect(DB::raw('(is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan - is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan)/is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as bien_loi_nhuan_tu_hoat_dong_bao_lanh'))
            ->addSelect(DB::raw('((is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) - (is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh))/(is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs) as bien_loi_nhuan_tu_hoat_dong_tu_doanh'))
            ->addSelect(DB::raw('(is.cong_doanh_thu_hoat_dong - is.cong_chi_phi_hoat_dong)/(is.cong_doanh_thu_hoat_dong) as bien_loi_nhuan_gop'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay)/cong_doanh_thu_hoat_dong as bien_ebit'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue)/cong_doanh_thu_hoat_dong as bien_ebt'))
            ->addSelect(DB::raw('(is.loi_nhuan_ke_toan_sau_thue_tndn)/cong_doanh_thu_hoat_dong as bien_loi_nhuan_sau_thue'))
            ->addSelect(DB::raw('0 as roaa'))
            ->addSelect(DB::raw('0 as roea'))
            ->addSelect(DB::raw('abs(bs.du_phong_suy_giam_gia_tri_cac_tai_san_tai_chinh/(bs.cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + bs.cac_khoan_dau_tu_giu_den_ngay_dao_han_htm + bs.cac_khoan_cho_vay + bs.cac_tai_san_tai_chinh_san_sang_de_ban_afs)) as ti_le_du_phong_suy_giam_gia_tri_tstc'))
            ->addSelect(DB::raw('abs(bs.du_phong_suy_giam_gia_tri_cac_khoan_phai_thu/bs.cac_khoan_phai_thu) as ti_le_du_phong_suy_giam_gia_tri_khoan_phai_thu'))
            ->addSelect(DB::raw('bs.cac_khoan_cho_vay/bs.von_chu_so_huu as ti_le_du_no_margin_vcsh'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han/bs.no_phai_tra_ngan_han as ti_le_thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_von_chu_so_huu as de'))
            ->addSelect(DB::raw('0 as roaa2'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('(is.loi_nhuan_ke_toan_sau_thue_tndn)/cong_doanh_thu_hoat_dong as ti_suat_loi_nhuan'))
            ->addSelect(DB::raw('0 as roea2'))
            ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
            ->addSelect(DB::raw('(is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay)/cong_doanh_thu_hoat_dong as bien_ebit2'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_ke_toan_truoc_thue / (is.tong_loi_nhuan_ke_toan_truoc_thue + is.chi_phi_lai_vay) as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn / (is.tong_loi_nhuan_ke_toan_truoc_thue) as ganh_nang_thue'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han / bs.tong_cong_tai_san as tsnh_tts'))
            ->addSelect(DB::raw('bs.tai_san_dai_han / bs.tong_cong_tai_san as tsdh_tts'))
            ->addSelect(DB::raw('bs.no_phai_tra / bs.tong_cong_no_phai_tra_va_von_chu_so_huu as bpt_tnv'))
            ->addSelect(DB::raw('bs.tong_von_chu_so_huu / bs.tong_cong_no_phai_tra_va_von_chu_so_huu as tvcsh_tnv'));
        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        for ($i = 0; $i < count($res) - 1; $i++) {
            $res[$i]["thoigian"] = is_null($res[$i]["is_thoigian"]) ? (is_null($res[$i]["bs_thoigian"]) ? $res[$i]["cf_thoigian"] : $res[$i]["bs_thoigian"]) : $res[$i]["is_thoigian"];
            $res[$i]["mack"] = strtoupper($mack);
            if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1))) {
                    if ($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1) != 0) {
                        $res[$i]["tang_truong_cong_doanh_thu_hoat_dong"] = $res[$i]["doanh_thu"] < 0 ? -1 : ($res[$i]["doanh_thu"] - $this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1)) / abs($this->checkNullValueByYear($res, "doanh_thu", $i, $i + 1));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i + 1))) {
                    if ($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i + 1) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] < 0 ? -1 : ($res[$i]["loi_nhuan_ke_toan_sau_thue_tndn"] - $this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i + 1)) / abs($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i + 1));
                    }
                }
            }
            if ($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                    $res[$i]['roaa'] = $res[$i]['roaa2'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu", $i, $i))) {
                    $res[$i]['vong_quay_tai_san'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['doanh_thu'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if ($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_ke_toan_sau_thue_tndn", $i, $i))) {
                    $res[$i]['roea'] = $res[$i]['roea2'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] * 2 / ($res[$i]['von_chu_so_huu'] + $res[$i + 1]['von_chu_so_huu']);
                }
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                    $res[$i]['don_bay_tai_chinh'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 1]['tong_cong_tai_san']) / ($res[$i]['von_chu_so_huu'] + $res[$i + 1]['von_chu_so_huu']);
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 1);
        // dd($res);
        $arr = [];
        for ($i = 5; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return $arr;
    }

    public function getFinancialRatiosInsurance(Request $req)
    {
        $type = $req->input('thoigian');
        if ($type == "quarter")
            return $this->calculateFinancialRatiosInsuranceQuarter($req);
        if ($type == "ttm")
            return $this->calculateFinancialRatiosInsuranceTTM($req);
        else
            return $this->calculateFinancialRatiosInsuranceYear($req);
    }

    protected function calculateFinancialRatiosInsuranceQuarter(Request $req)
    {
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $type_direct_insurance = DB::table('insurance_type')
            ->where('mack', $req->input('mack'))
            ->first();
        $type_direct_insurance = $type_direct_insurance->type;
        $is_type_direct_insurance = $type_direct_insurance == "TT";
        $limit = $req->input('thoigian') === "quarter" ? 100 : 10;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "insurance";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        if ($is_type_direct_insurance) {
            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
                ->addSelect(DB::raw('bs.von_chu_so_huu'))
                ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so'))
                ->addSelect(DB::raw('bs.vay_dai_han'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san'))
                ->addSelect(DB::raw('is.tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                ->addSelect(DB::raw('bs.tong_hang_ton_kho'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu'))
                ->addSelect(DB::raw('bs.phai_thu_cua_khach_hang'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han'))
                ->addSelect(DB::raw('bs.phai_tra_nguoi_ban'))
                ->addSelect(DB::raw('is.thoigian'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('0 as gia_thi_truong'))
                ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu/10000 as von_hoa'))
                ->addSelect(DB::raw('bs.vay_va_no_ngan_han+bs.vay_dai_han-bs.tien as gia_tri_doanh_nghiep'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'))
                ->addSelect(DB::raw('0 as tang_truong_eps'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu + bs.loi_ich_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
                ->addSelect(DB::raw('0 as tang_truong_bvps'))
                ->addSelect(DB::raw('0 as pe'))
                ->addSelect(DB::raw('0 as pb'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan_tu_hdkd_bh'))
                ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('0 as tang_truong_tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_tu_hoat_dong_bao_hiem'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_gop'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh/is.doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_truoc_thue'))
                ->addSelect(DB::raw('(is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as roa'))
                ->addSelect(DB::raw('0 as roic'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh / is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as ocf_loi_nhuan_thuan'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh+cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as fcf'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ty_le_thanh_toan_hien_hanh'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.du_phong_nghiep_vu as ty_le_thanh_toan_du_phong'))
                ->addSelect(DB::raw('bs.no_phai_tra / (bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so) as ty_le_de'))
                ->addSelect(DB::raw('bs.du_phong_nghiep_vu as du_phong_nghiep_vu_bao_hiem'))
                ->addSelect(DB::raw('0 as ty_le_dp_dtbh_ttm'))
                ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as roa2'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as ty_suat_loi_nhuan'))
                ->addSelect(DB::raw('0 as roe2'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han/bs.tong_cong_tai_san as tsnh_tsts'))
                ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han/bs.tong_cong_tai_san as tsdh_tsts'))
                ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_cong_nguon_von as npt_tnv'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so)/bs.tong_cong_nguon_von as nvcsh_tnv'));
        } else {
            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
                ->addSelect(DB::raw('bs.von_chu_so_huu'))
                ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so'))
                ->addSelect(DB::raw('bs.vay_dai_han'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san'))
                ->addSelect(DB::raw('is.tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                ->addSelect(DB::raw('bs.tong_hang_ton_kho'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu'))
                ->addSelect(DB::raw('bs.phai_thu_cua_khach_hang'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han'))
                ->addSelect(DB::raw('bs.phai_tra_nguoi_ban'))
                ->addSelect(DB::raw('is.thoigian'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('0 as gia_thi_truong'))
                ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu/10000 as von_hoa'))
                ->addSelect(DB::raw('bs.vay_va_no_ngan_han+bs.vay_dai_han-bs.tien as gia_tri_doanh_nghiep'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'))
                ->addSelect(DB::raw('0 as tang_truong_eps'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu + bs.loi_ich_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
                ->addSelect(DB::raw('0 as tang_truong_bvps'))
                ->addSelect(DB::raw('0 as pe'))
                ->addSelect(DB::raw('0 as pb'))
                ->addSelect(DB::raw('0 as evebit'))
                ->addSelect(DB::raw('0 as evebitda'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan_tu_hdkd_bh'))
                ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('0 as tang_truong_tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_tu_hoat_dong_bao_hiem'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay as ebit'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay + cf.khau_hao_tai_san_co_dinh as ebitda'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_gop'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh/is.doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_ebit'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay + cf.khau_hao_tai_san_co_dinh)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_ebitda'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_truoc_thue'))
                ->addSelect(DB::raw('(is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as roa'))
                ->addSelect(DB::raw('0 as roe'))
                ->addSelect(DB::raw('0 as roic'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh / is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as ocf_loi_nhuan_thuan'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh+cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as fcf'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ty_le_thanh_toan_hien_hanh'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.du_phong_nghiep_vu as ty_le_thanh_toan_du_phong'))
                ->addSelect(DB::raw('bs.no_phai_tra / (bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so) as ty_le_de'))
                ->addSelect(DB::raw('bs.du_phong_nghiep_vu as du_phong_nghiep_vu_bao_hiem'))
                ->addSelect(DB::raw('0 as ty_le_dp_dtbh_ttm'))
                ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as roa2'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as ty_suat_loi_nhuan'))
                ->addSelect(DB::raw('0 as roe2'))
                ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san3'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem) as bien_ebit2'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep/(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay) as ganh_nang_lai_suat'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ganh_nang_thue'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han/bs.tong_cong_tai_san as tsnh_tsts'))
                ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han/bs.tong_cong_tai_san as tsdh_tsts'))
                ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_cong_nguon_von as npt_tnv'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so)/bs.tong_cong_nguon_von as nvcsh_tnv'));
        }

        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * $res[$i]["von_hoa"];
            $res[$i]["gia_tri_doanh_nghiep"] = $res[$i]["von_hoa"] + $res[$i]["gia_tri_doanh_nghiep"];
            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                try {
                    $res[$i]['pe'] = $res[$i]["gia_thi_truong"] / $this->calculate_ttm_contain_check_null($res, "eps", $i);
                } catch (Exception $e) {
                }
            }
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;
            if (!$is_type_direct_insurance) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "ebit", $i))) {
                    try {
                        $res[$i]['evebit'] = $res[$i]["gia_tri_doanh_nghiep"] / $this->calculate_ttm_contain_check_null($res, "ebit", $i);
                    } catch (Exception $e) {
                    }
                }
                if (!is_null($this->calculate_ttm_contain_check_null($res, "ebitda", $i))) {
                    try {
                        $res[$i]['evebitda'] = $res[$i]["gia_tri_doanh_nghiep"] / $this->calculate_ttm_contain_check_null($res, "ebitda", $i);
                    } catch (Exception $e) {
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "eps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "eps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $res[$i]["eps"] < 0 ? -1 : ($res[$i]["eps"] - $this->checkNullValueByYear($res, "eps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "eps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_tong_doanh_thu_hoat_dong"] = $res[$i]["tong_doanh_thu_hoat_dong"] < 0 ? -1 : ($res[$i]["tong_doanh_thu_hoat_dong"] - $res[$i + 4]["tong_doanh_thu_hoat_dong"]) / abs($res[$i + 4]["tong_doanh_thu_hoat_dong"]);
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["loi_nhuan_rong"] < 0 ? -1 : ($res[$i]["loi_nhuan_rong"] - $res[$i + 4]["loi_nhuan_rong"]) / abs($res[$i + 4]["loi_nhuan_rong"]);
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i)) != 0) {
                            $res[$i]['roe2'] = $res[$i]['roa'] = $res[$i]['loi_nhuan_rong'] * 2 / ($res[$i + 1]['von_chu_so_huu'] + $res[$i]['von_chu_so_huu'] + $res[$i]['loi_ich_co_dong_thieu_so'] + $res[$i + 1]['loi_ich_co_dong_thieu_so']);
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i))) {
                        if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                            if (($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i) + $this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i)) != 0) {
                                $res[$i]['roe'] = $res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i) + $this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i));
                            }
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                    $res[$i]['roa2'] = $res[$i]['roic'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i))) {
                    $res[$i]['vong_quay_tai_san'] = $res[$i]['tong_doanh_thu_hoat_dong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu_thuan_tu_hdkd_bh", $i, $i))) {
                    $res[$i]['vong_quay_tai_san2'] = $res[$i]['vong_quay_tai_san3'] =  $res[$i]['doanh_thu_thuan_tu_hdkd_bh'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_nghiep_vu_bao_hiem", $i, $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "doanh_thu_thuan_tu_hdkd_bh", $i))) {
                    $res[$i]['ty_le_dp_dtbh_ttm'] = $res[$i]['du_phong_nghiep_vu_bao_hiem'] / $this->calculate_ttm_contain_check_null($res, "doanh_thu_thuan_tu_hdkd_bh", $i);
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem", $i, $i))) {
                    $res[$i]['vong_quay_hang_ton_kho'] = $res[$i]['tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i);
                    $res[$i]['snbq_vong_quay_hang_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? 365 / $res[$i]['vong_quay_hang_ton_kho'] : 0;
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "doanh_thu_thuan_tu_hdkd_bh", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu_thuan_tu_hdkd_bh'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i));
                            $res[$i]['snbq_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i)) && $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem", $i, $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i))) {
                        if (!is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1))) {
                            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'] + $res[$i]['tong_hang_ton_kho'] - $this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1)) * 2 / $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i);
                            $res[$i]['snbq_vong_quay_khoan_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_tra'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                        $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / ($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i));
                    }
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        for ($i = 13; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank . '_' . $type_direct_insurance);
        return $arr;
    }

    protected function calculateFinancialRatiosInsuranceTTM(Request $req)
    {
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $type_direct_insurance = DB::table('insurance_type')
            ->where('mack', $req->input('mack'))
            ->first();
        $type_direct_insurance = $type_direct_insurance->type;
        $is_type_direct_insurance = $type_direct_insurance == "TT";
        $limit = $req->input('thoigian') === "quarter" ? 100 : 10;
        $thoigian = "quarter";
        $mack = strtoupper($req->input('mack'));
        $typeBank = "insurance";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        if ($is_type_direct_insurance) {
            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
                ->addSelect(DB::raw('bs.von_chu_so_huu'))
                ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so'))
                ->addSelect(DB::raw('bs.vay_dai_han'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san'))
                ->addSelect(DB::raw('is.tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                ->addSelect(DB::raw('bs.tong_hang_ton_kho'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu'))
                ->addSelect(DB::raw('bs.phai_thu_cua_khach_hang'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han'))
                ->addSelect(DB::raw('bs.phai_tra_nguoi_ban'))
                ->addSelect(DB::raw('is.thoigian'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('0 as gia_thi_truong'))
                ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu/10000 as von_hoa'))
                ->addSelect(DB::raw('bs.vay_va_no_ngan_han+bs.vay_dai_han-bs.tien as gia_tri_doanh_nghiep'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'))
                ->addSelect(DB::raw('0 as tang_truong_eps'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu + bs.loi_ich_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
                ->addSelect(DB::raw('0 as tang_truong_bvps'))
                ->addSelect(DB::raw('0 as pe'))
                ->addSelect(DB::raw('0 as pb'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan_tu_hdkd_bh'))
                ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('0 as tang_truong_tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_tu_hoat_dong_bao_hiem'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_gop'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh/is.doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_truoc_thue'))
                ->addSelect(DB::raw('(is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as roa'))
                ->addSelect(DB::raw('0 as roic'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh / is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as ocf_loi_nhuan_thuan'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh+cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as fcf'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ty_le_thanh_toan_hien_hanh'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.du_phong_nghiep_vu as ty_le_thanh_toan_du_phong'))
                ->addSelect(DB::raw('bs.no_phai_tra / (bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so) as ty_le_de'))
                ->addSelect(DB::raw('bs.du_phong_nghiep_vu as du_phong_nghiep_vu_bao_hiem'))
                ->addSelect(DB::raw('0 as ty_le_dp_dtbh_ttm'))
                ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as roa2'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as ty_suat_loi_nhuan'))
                ->addSelect(DB::raw('0 as roe2'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han/bs.tong_cong_tai_san as tsnh_tsts'))
                ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han/bs.tong_cong_tai_san as tsdh_tsts'))
                ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_cong_nguon_von as npt_tnv'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so)/bs.tong_cong_nguon_von as nvcsh_tnv'));
        } else {
            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
                ->addSelect(DB::raw('bs.von_chu_so_huu'))
                ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so'))
                ->addSelect(DB::raw('bs.vay_dai_han'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san'))
                ->addSelect(DB::raw('is.tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                ->addSelect(DB::raw('bs.tong_hang_ton_kho'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu'))
                ->addSelect(DB::raw('bs.phai_thu_cua_khach_hang'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han'))
                ->addSelect(DB::raw('bs.phai_tra_nguoi_ban'))
                ->addSelect(DB::raw('is.thoigian'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('0 as gia_thi_truong'))
                ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu/10000 as von_hoa'))
                ->addSelect(DB::raw('bs.vay_va_no_ngan_han+bs.vay_dai_han-bs.tien as gia_tri_doanh_nghiep'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'))
                ->addSelect(DB::raw('0 as tang_truong_eps'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu + bs.loi_ich_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
                ->addSelect(DB::raw('0 as tang_truong_bvps'))
                ->addSelect(DB::raw('0 as pe'))
                ->addSelect(DB::raw('0 as pb'))
                ->addSelect(DB::raw('0 as evebit'))
                ->addSelect(DB::raw('0 as evebitda'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan_tu_hdkd_bh'))
                ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('0 as tang_truong_tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_tu_hoat_dong_bao_hiem'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay as ebit'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay + cf.khau_hao_tai_san_co_dinh as ebitda'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_gop'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh/is.doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_ebit'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay + cf.khau_hao_tai_san_co_dinh)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_ebitda'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_truoc_thue'))
                ->addSelect(DB::raw('(is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as roa'))
                ->addSelect(DB::raw('0 as roe'))
                ->addSelect(DB::raw('0 as roic'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh / is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as ocf_loi_nhuan_thuan'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh+cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as fcf'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ty_le_thanh_toan_hien_hanh'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.du_phong_nghiep_vu as ty_le_thanh_toan_du_phong'))
                ->addSelect(DB::raw('bs.no_phai_tra / (bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so) as ty_le_de'))
                ->addSelect(DB::raw('bs.du_phong_nghiep_vu as du_phong_nghiep_vu_bao_hiem'))
                ->addSelect(DB::raw('0 as ty_le_dp_dtbh_ttm'))
                ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as roa2'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as ty_suat_loi_nhuan'))
                ->addSelect(DB::raw('0 as roe2'))
                ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san3'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem) as bien_ebit2'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep/(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay) as ganh_nang_lai_suat'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ganh_nang_thue'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han/bs.tong_cong_tai_san as tsnh_tsts'))
                ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han/bs.tong_cong_tai_san as tsdh_tsts'))
                ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_cong_nguon_von as npt_tnv'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so)/bs.tong_cong_nguon_von as nvcsh_tnv'));
        }

        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 8)
            ->whereRaw("SUBSTR(m.is_thoigian,3) > 2009")
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 8; $i++) {
            $res[$i]["gia_thi_truong"] = $this->getClosePriceByTime($list_close_price, $res[$i]["thoigian"]);
            $res[$i]["von_hoa"] = $res[$i]["gia_thi_truong"] * $res[$i]["von_hoa"];
            $res[$i]["gia_tri_doanh_nghiep"] = $res[$i]["von_hoa"] + $res[$i]["gia_tri_doanh_nghiep"];
            $res[$i]['pb'] = $res[$i]['bvps'] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]['bvps'] : 0;

            if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "eps", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "eps", $i + 4) != 0) {
                        $res[$i]["tang_truong_eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "eps", $i) - $this->calculate_ttm_contain_check_null($res, "eps", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "eps", $i + 4));
                    }
                }
            }
            $res[$i]["eps"] = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $res[$i]['pe'] = $res[$i]["eps"] != 0 ? $res[$i]["gia_thi_truong"] / $res[$i]["eps"] : 0;
            if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "bvps", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "bvps", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_bvps"] = $res[$i]["bvps"] < 0 ? -1 : ($res[$i]["bvps"] - $this->checkNullValueByYear($res, "bvps", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "bvps", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i + 4) != 0) {
                        $res[$i]["tang_truong_tong_doanh_thu_hoat_dong"] = $this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i) - $this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i + 4));
                    }
                }
            }
            $res[$i]["tong_doanh_thu_hoat_dong"] = $this->calculate_ttm_contain_check_null($res, "tong_doanh_thu_hoat_dong", $i);
            if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4))) {
                    if ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i) < 0 ? -1 : ($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i) - $this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4)) / abs($this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i + 4));
                    }
                }
            }
            $res[$i]["loi_nhuan_rong"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_rong", $i);
            $res[$i]["doanh_thu_thuan_tu_hdkd_bh"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu_thuan_tu_hdkd_bh", $i);
            $res[$i]["doanh_thu_hoat_dong_tai_chinh"] = $this->calculate_ttm_contain_check_null($res, "doanh_thu_hoat_dong_tai_chinh", $i);
            $res[$i]["loi_nhuan_tu_hoat_dong_bao_hiem"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_tu_hoat_dong_bao_hiem", $i);
            $res[$i]["loi_nhuan_hoat_dong_tai_chinh"] = $this->calculate_ttm_contain_check_null($res, "loi_nhuan_hoat_dong_tai_chinh", $i);
            $res[$i]["ebt"] = $this->calculate_ttm_contain_check_null($res, "ebt", $i);

            $res[$i]['bien_loi_nhuan_gop'] = $res[$i]["doanh_thu_thuan_tu_hdkd_bh"] != 0 ? $res[$i]["loi_nhuan_tu_hoat_dong_bao_hiem"] / $res[$i]["doanh_thu_thuan_tu_hdkd_bh"] : 0;
            $res[$i]['bien_loi_nhuan_hoat_dong_tai_chinh'] = $res[$i]["doanh_thu_hoat_dong_tai_chinh"] != 0 ? $res[$i]["loi_nhuan_hoat_dong_tai_chinh"] / $res[$i]["doanh_thu_hoat_dong_tai_chinh"] : 0;
            $res[$i]['bien_loi_nhuan_truoc_thue'] = $res[$i]["tong_doanh_thu_hoat_dong"] != 0 ? $res[$i]["ebt"] / $res[$i]["tong_doanh_thu_hoat_dong"] : 0;
            $res[$i]["ty_suat_loi_nhuan"] = $res[$i]['bien_loi_nhuan_rong'] = $res[$i]["tong_doanh_thu_hoat_dong"] != 0 ? $res[$i]["loi_nhuan_rong"] / $res[$i]["tong_doanh_thu_hoat_dong"] : 0;

            $res[$i]["ocf"] = $this->calculate_ttm_contain_check_null($res, "ocf", $i);
            $res[$i]["ocf_loi_nhuan_thuan"] = $this->calculate_ttm_contain_check_null($res, "ocf_loi_nhuan_thuan", $i);
            $res[$i]["fcf"] = $this->calculate_ttm_contain_check_null($res, "fcf", $i);
            $res[$i]['ty_le_dp_dtbh_ttm'] = $res[$i]["doanh_thu_thuan_tu_hdkd_bh"] != 0 ? $res[$i]['du_phong_nghiep_vu_bao_hiem'] / $res[$i]["doanh_thu_thuan_tu_hdkd_bh"] : 0;

            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                        if (($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i)) != 0) {
                            $res[$i]['roe2'] = $res[$i]['roa'] = $res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i));
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "vay_dai_han", $i))) {
                        if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                            if (($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i) + $this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i)) != 0) {
                                $res[$i]['roe'] = $res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "vay_dai_han", $i));
                            }
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                    $res[$i]['roa2'] = $res[$i]['roic'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }

            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_hang_ton_kho", $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "tong_hang_ton_kho", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem", $i, $i))) {
                    $res[$i]['vong_quay_hang_ton_kho'] = $res[$i]['tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_hang_ton_kho", $i);
                    $res[$i]['snbq_vong_quay_hang_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? 365 / $res[$i]['vong_quay_hang_ton_kho'] : 0;
                }
            }
            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "doanh_thu_thuan_tu_hdkd_bh", $i, $i))) {
                        if (($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu_thuan_tu_hdkd_bh'] * 2 / ($this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i));
                            $res[$i]['snbq_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i)) && $this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem", $i, $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i))) {
                        if (!is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1))) {
                            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'] + $res[$i]['tong_hang_ton_kho'] - $this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1)) * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i);
                            $res[$i]['snbq_vong_quay_khoan_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_tra'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i))) {
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                    if ($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                        $res[$i]['vong_quay_tai_san'] = $res[$i]['vong_quay_tai_san2'] = $res[$i]['tong_doanh_thu_hoat_dong'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                    }
                }
            }
            if (!$is_type_direct_insurance) {
                $res[$i]["ebit"] = $this->calculate_ttm_contain_check_null($res, "ebit", $i);
                $res[$i]["ebitda"] = $this->calculate_ttm_contain_check_null($res, "ebitda", $i);
                if (!is_null($this->calculate_ttm_contain_check_null($res, "ebit", $i))) {
                    $res[$i]['evebit'] = $res[$i]["ebit"] != 0 ? $res[$i]["gia_tri_doanh_nghiep"] / $res[$i]["ebit"] : 0;
                    $res[$i]['evebitda'] = $res[$i]["ebitda"] != 0 ? $res[$i]["gia_tri_doanh_nghiep"] / $res[$i]["ebitda"] : 0;
                }
                $res[$i]['bien_ebit2'] =  $res[$i]['bien_ebit'] = $res[$i]["tong_doanh_thu_hoat_dong"] != 0 ? $res[$i]["ebit"] / $res[$i]["tong_doanh_thu_hoat_dong"] : 0;
                $res[$i]['bien_ebitda'] = $res[$i]["tong_doanh_thu_hoat_dong"] != 0 ? $res[$i]["ebitda"] / $res[$i]["tong_doanh_thu_hoat_dong"] : 0;
                if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i))) {
                    if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                        if ($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) != 0) {
                            $res[$i]['vong_quay_tai_san3'] = $res[$i]['tong_doanh_thu_hoat_dong'] * 2 / $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                        }
                    }
                }
                if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                    if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                        if (!is_null($this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                            $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_4_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / ($this->calculate_before_4_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_4_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i));
                        }
                    }
                }
                $res[$i]['ganh_nang_thue'] = $res[$i]["ebt"] != 0 ? $res[$i]["loi_nhuan_rong"] / $res[$i]["ebt"] : 0;
                $res[$i]['ganh_nang_lai_suat'] = $res[$i]["ebit"] != 0 ? $res[$i]["ebt"] / $res[$i]["ebit"] : 0;
            }
        }
        $res = array_slice($res, 0, count($res) - 8);
        $arr = [];
        for ($i = 13; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank . '_' . $type_direct_insurance);
        return $arr;
    }

    protected function calculateFinancialRatiosInsuranceYear(Request $req)
    {
        $page = $req->input('page') ? $req->input('page') : 1;
        $item_per_page = $req->input('item_per_page') ? $req->input('item_per_page') : 100;
        $order = $req->input('order') ? $req->input('order') : "asc";
        $page -= 1;
        $type_direct_insurance = DB::table('insurance_type')
            ->where('mack', $req->input('mack'))
            ->first();
        $type_direct_insurance = $type_direct_insurance->type;
        $is_type_direct_insurance = $type_direct_insurance == "TT";
        $limit = $req->input('thoigian') === "quarter" ? 100 : 10;
        $thoigian = $req->input('thoigian');
        $mack = strtoupper($req->input('mack'));
        $typeBank = "insurance";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $res = [];
        if ($is_type_direct_insurance) {
            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
                ->addSelect(DB::raw('bs.von_chu_so_huu'))
                ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so'))
                ->addSelect(DB::raw('bs.vay_dai_han'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san'))
                ->addSelect(DB::raw('is.tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                ->addSelect(DB::raw('bs.tong_hang_ton_kho'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu'))
                ->addSelect(DB::raw('bs.phai_thu_cua_khach_hang'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han'))
                ->addSelect(DB::raw('bs.phai_tra_nguoi_ban'))
                ->addSelect(DB::raw('is.thoigian'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan_tu_hdkd_bh'))
                ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('0 as tang_truong_tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_tu_hoat_dong_bao_hiem'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as loi_nhuan_rong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_gop'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh/is.doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_truoc_thue'))
                ->addSelect(DB::raw('(is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
                ->addSelect(DB::raw('0 as roa'))
                ->addSelect(DB::raw('0 as roic'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh / is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as ocf_loi_nhuan_thuan'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh+cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as fcf'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ty_le_thanh_toan_hien_hanh'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.du_phong_nghiep_vu as ty_le_thanh_toan_du_phong'))
                ->addSelect(DB::raw('bs.no_phai_tra / (bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so) as ty_le_de'))
                ->addSelect(DB::raw('bs.du_phong_nghiep_vu as du_phong_nghiep_vu_bao_hiem'))
                ->addSelect(DB::raw('0 as ty_le_dp_dtbh_ttm'))
                ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as roa2'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as ty_suat_loi_nhuan'))
                ->addSelect(DB::raw('0 as roe2'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han/bs.tong_cong_tai_san as tsnh_tsts'))
                ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han/bs.tong_cong_tai_san as tsdh_tsts'))
                ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_cong_nguon_von as npt_tnv'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so)/bs.tong_cong_nguon_von as nvcsh_tnv'));
        } else {
            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
                ->addSelect(DB::raw('bs.von_chu_so_huu'))
                ->addSelect(DB::raw('bs.loi_ich_co_dong_thieu_so'))
                ->addSelect(DB::raw('bs.vay_dai_han'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san'))
                ->addSelect(DB::raw('is.tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'))
                ->addSelect(DB::raw('bs.tong_hang_ton_kho'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu'))
                ->addSelect(DB::raw('bs.phai_thu_cua_khach_hang'))
                ->addSelect(DB::raw('bs.cac_khoan_phai_thu_dai_han'))
                ->addSelect(DB::raw('bs.phai_tra_nguoi_ban'))
                ->addSelect(DB::raw('is.thoigian'))
                ->addSelect(DB::raw('is.mack'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan_tu_hdkd_bh'))
                ->addSelect(DB::raw('is.doanh_thu_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('0 as tang_truong_tong_doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_ as loi_nhuan_tu_hoat_dong_bao_hiem'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay as ebit'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay + cf.khau_hao_tai_san_co_dinh as ebitda'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as tang_truong_loi_nhuan_sau_thue'))
                ->addSelect(DB::raw('is.loi_nhuan_gop_hoat_dong_kinh_doanh_bao_hiem_/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_gop'))
                ->addSelect(DB::raw('is.loi_nhuan_hoat_dong_tai_chinh/is.doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hoat_dong_tai_chinh'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_ebit'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay + cf.khau_hao_tai_san_co_dinh)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_ebitda'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_truoc_thue'))
                ->addSelect(DB::raw('(is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh) as bien_loi_nhuan_rong'))
                ->addSelect(DB::raw('0 as roa'))
                ->addSelect(DB::raw('0 as roe'))
                ->addSelect(DB::raw('0 as roic'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh / is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as ocf_loi_nhuan_thuan'))
                ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh+cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as fcf'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.no_ngan_han as ty_le_thanh_toan_hien_hanh'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han / bs.du_phong_nghiep_vu as ty_le_thanh_toan_du_phong'))
                ->addSelect(DB::raw('bs.no_phai_tra / (bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so) as ty_le_de'))
                ->addSelect(DB::raw('bs.du_phong_nghiep_vu as du_phong_nghiep_vu_bao_hiem'))
                ->addSelect(DB::raw('0 as ty_le_dp_dtbh_ttm'))
                ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_hang_ton_kho'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_thu'))
                ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as snbq_vong_quay_khoan_phai_tra'))
                ->addSelect(DB::raw('0 as roa2'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san2'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as ty_suat_loi_nhuan'))
                ->addSelect(DB::raw('0 as roe2'))
                ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
                ->addSelect(DB::raw('0 as vong_quay_tai_san3'))
                ->addSelect(DB::raw('(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay)/(is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem) as bien_ebit2'))
                ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep/(is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep + cf.chi_phi_lai_vay) as ganh_nang_lai_suat'))
                ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ganh_nang_thue'))
                ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han/bs.tong_cong_tai_san as tsnh_tsts'))
                ->addSelect(DB::raw('bs.tai_san_co_dinh_va_dau_tu_dai_han/bs.tong_cong_tai_san as tsdh_tsts'))
                ->addSelect(DB::raw('bs.no_phai_tra/bs.tong_cong_nguon_von as npt_tnv'))
                ->addSelect(DB::raw('(bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so)/bs.tong_cong_nguon_von as nvcsh_tnv'));
        }

        $table_is = DB::table($is . ' as is')
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $mack);
        $table_bs = DB::table($bs . ' as bs')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'bs.mack')
                    ->on('is.thoigian', '=', 'bs.thoigian');
            })
            ->leftJoin($cf . ' as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->whereNull("is.mack")
            ->where("bs.mack", $mack);
        $table_cf = DB::table($cf . ' as cf')
            ->leftJoin($is . ' as is', function ($join) {
                $join->on('is.mack', '=', 'cf.mack')
                    ->on('is.thoigian', '=', 'cf.thoigian');
            })
            ->leftJoin($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'cf.mack')
                    ->on('bs.thoigian', '=', 'cf.thoigian');
            })
            ->whereNull("is.mack")
            ->whereNull("bs.mack")
            ->where("cf.mack", $mack);
        $table_is->columns = $column_select->columns;
        $table_bs->columns = $column_select->columns;
        $table_cf->columns = $column_select->columns;
        $res = DB::query()->fromSub(
            $table_is
                ->union(
                    $table_bs
                )
                ->union(
                    $table_cf
                ),
            'm'
        )
            ->offset($page * $item_per_page)
            ->take($item_per_page + 4)
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        if (count($res) == 0)
            return [];
        for ($i = 0; $i < count($res) - 4; $i++) {
            if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_tong_doanh_thu_hoat_dong"] = $res[$i]["tong_doanh_thu_hoat_dong"] < 0 ? -1 : ($res[$i]["tong_doanh_thu_hoat_dong"] - $this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4))) {
                    if ($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4) != 0) {
                        $res[$i]["tang_truong_loi_nhuan_sau_thue"] = $res[$i]["loi_nhuan_rong"] < 0 ? -1 : ($res[$i]["loi_nhuan_rong"] - $this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4)) / abs($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i + 4));
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i)) != 0) {
                            $res[$i]['roe2'] = $res[$i]['roa'] = $res[$i]['loi_nhuan_rong'] * 2 / ($res[$i + 1]['von_chu_so_huu'] + $res[$i]['von_chu_so_huu'] + $res[$i]['loi_ich_co_dong_thieu_so'] + $res[$i + 1]['loi_ich_co_dong_thieu_so']);
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i))) {
                        if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                            if (($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i) + $this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i)) != 0) {
                                $res[$i]['roe'] = $res[$i]['loi_nhuan_rong'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i) + $this->calculate_before_quarter_contain_check_null($res, "vay_dai_han", $i));
                            }
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                if (!is_null($this->checkNullValueByYear($res, "loi_nhuan_rong", $i, $i))) {
                    $res[$i]['roa2'] = $res[$i]['roic'] = $res[$i]['loi_nhuan_rong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "tong_doanh_thu_hoat_dong", $i, $i))) {
                    $res[$i]['vong_quay_tai_san'] = $res[$i]['tong_doanh_thu_hoat_dong'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
                if (!is_null($this->checkNullValueByYear($res, "doanh_thu_thuan_tu_hdkd_bh", $i, $i))) {
                    $res[$i]['vong_quay_tai_san2'] = $res[$i]['vong_quay_tai_san3'] =  $res[$i]['doanh_thu_thuan_tu_hdkd_bh'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i);
                }
            }
            if (!is_null($this->checkNullValueByYear($res, "du_phong_nghiep_vu_bao_hiem", $i, $i))) {
                if (!is_null($this->calculate_ttm_contain_check_null($res, "doanh_thu_thuan_tu_hdkd_bh", $i))) {
                    $res[$i]['ty_le_dp_dtbh_ttm'] = $res[$i]['du_phong_nghiep_vu_bao_hiem'] / $this->calculate_ttm_contain_check_null($res, "doanh_thu_thuan_tu_hdkd_bh", $i);
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i)) && $this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem", $i, $i))) {
                    $res[$i]['vong_quay_hang_ton_kho'] = $res[$i]['tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'] * 2 / $this->calculate_before_quarter_contain_check_null($res, "tong_hang_ton_kho", $i);
                    $res[$i]['snbq_vong_quay_hang_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? 365 / $res[$i]['vong_quay_hang_ton_kho'] : 0;
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "doanh_thu_thuan_tu_hdkd_bh", $i, $i))) {
                        if (($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i)) != 0) {
                            $res[$i]['vong_quay_khoan_phai_thu'] = $res[$i]['doanh_thu_thuan_tu_hdkd_bh'] * 2 / ($this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu", $i) + $this->calculate_before_quarter_contain_check_null($res, "cac_khoan_phai_thu_dai_han", $i));
                            $res[$i]['snbq_vong_quay_khoan_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_thu'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i)) && $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i) != 0) {
                if (!is_null($this->checkNullValueByYear($res, "tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem", $i, $i))) {
                    if (!is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i))) {
                        if (!is_null($this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1))) {
                            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['tong_chi_truc_tiep_hoat_dong_kinh_doanh_bao_hiem'] + $res[$i]['tong_hang_ton_kho'] - $this->checkNullValueByYear($res, "tong_hang_ton_kho", $i, $i + 1)) * 2 / $this->calculate_before_quarter_contain_check_null($res, "phai_tra_nguoi_ban", $i);
                            $res[$i]['snbq_vong_quay_khoan_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? 365 / $res[$i]['vong_quay_khoan_phai_tra'] : 0;
                        }
                    }
                }
            }
            if (!is_null($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i))) {
                if (!is_null($this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i))) {
                    if (!is_null($this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i))) {
                        $res[$i]['don_bay_tai_chinh'] = $this->calculate_before_quarter_contain_check_null($res, "tong_cong_tai_san", $i) / ($this->calculate_before_quarter_contain_check_null($res, "von_chu_so_huu", $i) + $this->calculate_before_quarter_contain_check_null($res, "loi_ich_co_dong_thieu_so", $i));
                    }
                }
            }
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        for ($i = 13; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank . '_' . $type_direct_insurance);
        return $arr;
    }

    public function getData(Request $req)
    {
        $mack = strtoupper($req->input('mack'));
        $typeBank = DB::table('danh_sach_mack')
            ->select("nhom")
            ->where('mack', '=', $mack)
            ->first();
        $typeBank = $typeBank->nhom;
        switch ($typeBank) {
            case "nonbank":
                return $this->getFinancialRatiosNonBank($req);
                break;
            case "stock":
                return $this->getFinancialRatiosStock($req);
                break;
            case "bank":
                return $this->getFinancialRatiosBank($req);
                break;
            case "insurance":
                return $this->getFinancialRatiosInsurance($req);
                break;
            default:
                return [];
                break;
        }
    }

    protected static function rotateTable($arr)
    {
        $arr_return = [];
        for ($i = 0; $i < count($arr[0]); $i++) {
            array_push($arr_return, array_column($arr, array_keys($arr[0])[$i]));
        }
        return $arr_return;
    }
}
