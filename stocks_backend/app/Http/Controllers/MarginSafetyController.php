<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use \Exception;

class MarginSafetyController extends Controller
{
    public  function FV($rate = 0, $nper = 0, $pmt = 0, $pv = 0, $type = 0)
    {
        if ($type != 0 && $type != 1) {
            return False;
        }
        if ($rate != 0.0) {
            return -$pv * pow(1 + $rate, $nper) - $pmt * (1 + $rate * $type) * (pow(1 + $rate, $nper) - 1) / $rate;
        } else {
            return -$pv - $pmt * $nper;
        }
    }
    protected function RATE($period, $old_val, $new_val)
    {
        if ($new_val == 0 || $old_val == 0 || $period == 0)
            return 0;
        else {
            $data_return = pow($new_val / $old_val, 1 / $period) - 1;
            if (is_nan($data_return)) {
                return 0;
            }
            return $data_return;
        }
    }
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

    //hàm check null và tính giá trị ttm
    protected function calculate_ttm_contain_check_null($d, $key, $i)
    {
        if (is_null($this->checkNullValueByYear($d, $key, $i, $i)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 1)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 2)) || is_null($this->checkNullValueByYear($d, $key, $i, $i + 3))) {
            return null;
        }
        return $this->checkNullValueByYear($d, $key, $i, $i + 1) + $this->checkNullValueByYear($d, $key, $i, $i + 2) + $this->checkNullValueByYear($d, $key, $i, $i) + $this->checkNullValueByYear($d, $key, $i, $i + 3);
    }

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

    protected function getClosePriceByTime($data_close_price, $time)
    {
        return isset($data_close_price[$time]) ? (float) $data_close_price[$time]->closeprice : 0;
    }

    public function calculate_growth_rate_future($res)
    {
        if (count($res) > 23) {
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", 0);
            $eps_ttm_before = $this->calculate_ttm_contain_check_null($res, "eps", 20);
            $bvps = $this->checkNullValueByYear($res, "bvps", 0, 0);
            $bvps_before = $this->checkNullValueByYear($res, "bvps", 0, 20);
            if (!is_null($eps_ttm) && !is_null($eps_ttm_before) && !is_null($bvps) && !is_null($bvps_before)) {
                $g_eps = $eps_ttm < 0 ? 0 : ($eps_ttm * $eps_ttm_before < 0 ? 1 : $this->RATE(5, $eps_ttm_before, $eps_ttm));
                $g_bvps = $bvps < 0 ? 0 : ($bvps * $bvps_before < 0 ? 1 : $this->RATE(5, $bvps_before, $bvps));
                if ($g_eps >= 0 && $g_bvps >= 0) {
                    return min($g_eps, $g_bvps);
                } else {
                    if ($g_eps > 0) {
                        return $g_eps;
                    } else if ($g_eps > 0) {
                        return $g_eps;
                    } else {
                        return 0;
                    }
                }
            }
        }
        if (count($res) > 15) {
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", 0);
            $eps_ttm_before = $this->calculate_ttm_contain_check_null($res, "eps", 12);
            $bvps = $this->checkNullValueByYear($res, "bvps", 0, 0);
            $bvps_before = $this->checkNullValueByYear($res, "bvps", 0, 12);
            if (!is_null($eps_ttm) && !is_null($eps_ttm_before) && !is_null($bvps) && !is_null($bvps_before)) {
                $g_eps = $eps_ttm < 0 ? 0 : ($eps_ttm * $eps_ttm_before < 0 ? 1 : $this->RATE(3, $eps_ttm_before, $eps_ttm));
                $g_bvps = $bvps < 0 ? 0 : ($bvps * $bvps_before < 0 ? 1 : $this->RATE(3, $bvps_before, $bvps));
                if ($g_eps >= 0 && $g_bvps >= 0) {
                    return min($g_eps, $g_bvps);
                } else {
                    if ($g_eps > 0) {
                        return $g_eps;
                    } else if ($g_eps > 0) {
                        return $g_eps;
                    } else {
                        return 0;
                    }
                }
            }
        }
        if (count($res) > 7) {
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", 0);
            $eps_ttm_before = $this->calculate_ttm_contain_check_null($res, "eps", 4);
            $bvps = $this->checkNullValueByYear($res, "bvps", 0, 0);
            $bvps_before = $this->checkNullValueByYear($res, "bvps", 0, 4);
            if (!is_null($eps_ttm) && !is_null($eps_ttm_before) && !is_null($bvps) && !is_null($bvps_before)) {
                $g_eps = $eps_ttm < 0 ? 0 : ($eps_ttm * $eps_ttm_before < 0 ? 1 : $this->RATE(1, $eps_ttm_before, $eps_ttm));
                $g_bvps = $bvps < 0 ? 0 : ($bvps * $bvps_before < 0 ? 1 : $this->RATE(1, $bvps_before, $bvps));
                if ($g_eps >= 0 && $g_bvps >= 0) {
                    return min($g_eps, $g_bvps);
                } else {
                    if ($g_eps > 0) {
                        return $g_eps;
                    } else if ($g_eps > 0) {
                        return $g_eps;
                    } else {
                        return 0;
                    }
                }
            }
        }
        return 0;
    }

    public function getDataNonbank(Request $req)
    {
        $mack = strtoupper($req->input("mack"));
        $thoigian = "quarter";
        $typeBank = "nonbank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $eps_ttm = 0;
        $ocf_ttm = 0;
        $capex_ttm = 0;
        $bvps = 0;
        $tang_truong_bvps = 0;
        $so_luong_co_phieu = 0;
        $ty_le_tang_truong_tuong_lai = 0;
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
            ->addSelect(DB::raw('(cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_tai_san_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_tai_san_dai_han_khac) as capex'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10)*1000 as eps'))
            ->addSelect(DB::raw('bs.von_dau_tu_cua_chu_so_huu'));

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
            ->take(24)
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        $list_pe = [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $price = $this->getClosePriceByTime($list_close_price, $res[$i]["is_thoigian"]);
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $pe = $eps_ttm != 0 ? $price / $eps_ttm : 0;
            array_push($list_pe, $pe);
        }
        $data_eps = DB::table("compare_nonbank")
            ->addSelect("mack")
            ->addSelect("eps")
            ->addSelect("von_hoa")
            ->addSelect("bvps")
            ->addSelect("tang_truong_bvps")
            ->where("mack", $mack)
            ->first();
        $eps_ttm = 0;
        try {
            $ocf_ttm = $this->calculate_ttm_contain_check_null($res, "ocf", 0);
            $capex_ttm = $this->calculate_ttm_contain_check_null($res, "capex", 0);

            $ty_le_tang_truong_tuong_lai =  $this->calculate_growth_rate_future($res) * 100;
        } catch (Exception $e) {
        }
        try {
            $eps_ttm = $data_eps->eps;
            $bvps = $data_eps->bvps;
            $tang_truong_bvps = $data_eps->tang_truong_bvps;
            $so_luong_co_phieu = $data_eps->von_hoa;
        } catch (Exception $e) {
        }
        if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 0))) {
            if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 4))) {
                if ($this->checkNullValueByYear($res, "bvps", 0, 4) != 0) {
                    $tang_truong_bvps = $res[0]["bvps"] < 0 ? -1 : ($res[0]["bvps"] - $res[4]["bvps"]) / abs($res[4]["bvps"]);
                }
            }
        }
        return [
            "mack" => $mack,
            "eps_ttm" => round($eps_ttm, 2),
            "ty_le_tang_truong_tuong_lai" => round($ty_le_tang_truong_tuong_lai, 2),
            "pe_cao_nhat" => max($list_pe),
            "pe_thap_nhat" => min($list_pe),
            "so_luong_co_phieu" => $so_luong_co_phieu,
            "ocf_ttm" => $ocf_ttm,
            "capex_ttm" => $capex_ttm,
            "bvps" => $bvps,
            "tang_truong_bvps" => $tang_truong_bvps * 100,
        ];
    }

    public function getDataBank(Request $req)
    {
        $mack = strtoupper($req->input("mack"));
        $thoigian = "quarter";
        $typeBank = "bank";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $eps_ttm = 0;
        $ocf_ttm = 0;
        $capex_ttm = 0;
        $bvps = 0;
        $tang_truong_bvps = 0;
        $so_luong_co_phieu = 0;
        $ty_le_tang_truong_tuong_lai = 0;
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
            ->addSelect(DB::raw('(cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd+cf.tien_chi_tu_thanh_ly_nhuong_ban_tscd) as capex'))
            ->addSelect(DB::raw('(bs.von_va_cac_quy+bs.loi_ich_cua_co_dong_thieu_so-bs.tai_san_co_dinh_vo_hinh)/(bs.von_dieu_le/10000) as bvps'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/(bs.von_dieu_le)*10000 as eps'));

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
            ->take(24)
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        $list_pe = [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $price = $this->getClosePriceByTime($list_close_price, $res[$i]["is_thoigian"]);
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $pe = $eps_ttm != 0 ? $price / $eps_ttm : 0;
            array_push($list_pe, $pe);
        }
        $data_eps = DB::table("compare_bank")
            ->addSelect("mack")
            ->addSelect("eps")
            ->addSelect("von_hoa")
            ->addSelect("gia_tri_so_sach as bvps")
            ->addSelect("tang_truong_bvps")
            ->where("mack", $mack)
            ->first();
        $eps_ttm = 0;
        try {
            $ocf_ttm = $this->calculate_ttm_contain_check_null($res, "ocf", 0);
            $capex_ttm = $this->calculate_ttm_contain_check_null($res, "capex", 0);

            $ty_le_tang_truong_tuong_lai =  $this->calculate_growth_rate_future($res) * 100;
        } catch (Exception $e) {
        }
        try {
            $eps_ttm = $data_eps->eps;
            $bvps = $data_eps->bvps;
            $tang_truong_bvps = $data_eps->tang_truong_bvps;
            $so_luong_co_phieu = $data_eps->von_hoa;
        } catch (Exception $e) {
        }
        if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 0))) {
            if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 4))) {
                if ($this->checkNullValueByYear($res, "bvps", 0, 4) != 0) {
                    $tang_truong_bvps = $res[0]["bvps"] < 0 ? -1 : ($res[0]["bvps"] - $res[4]["bvps"]) / abs($res[4]["bvps"]);
                }
            }
        }
        return [
            "mack" => $mack,
            "eps_ttm" => round($eps_ttm, 2),
            "ty_le_tang_truong_tuong_lai" => round($ty_le_tang_truong_tuong_lai, 2),
            "pe_cao_nhat" => max($list_pe),
            "pe_thap_nhat" => min($list_pe),
            "so_luong_co_phieu" => $so_luong_co_phieu,
            "ocf_ttm" => $ocf_ttm,
            "capex_ttm" => $capex_ttm,
            "bvps" => $bvps,
            "tang_truong_bvps" => $tang_truong_bvps * 100,
        ];
    }

    public function getDataStock(Request $req)
    {
        $mack = strtoupper($req->input("mack"));
        $thoigian = "quarter";
        $typeBank = "stock";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $eps_ttm = 0;
        $ocf_ttm = 0;
        $capex_ttm = 0;
        $bvps = 0;
        $tang_truong_bvps = 0;
        $so_luong_co_phieu = 0;
        $ty_le_tang_truong_tuong_lai = 0;
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
            ->addSelect(DB::raw('(cf.tien_chi_de_mua_sam_xay_dung_tscd_bdsdt+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_bdsdt) as capex'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu - bs.tai_san_co_dinh_vo_hinh) / (bs.von_gop_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('is.loi_nhuan_ke_toan_sau_thue_tndn/(bs.von_gop_cua_chu_so_huu/10000) as eps'));

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
            ->take(24)
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        $list_pe = [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $price = $this->getClosePriceByTime($list_close_price, $res[$i]["is_thoigian"]);
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $pe = $eps_ttm != 0 ? $price / $eps_ttm : 0;
            array_push($list_pe, $pe);
        }
        $data_eps = DB::table("compare_stock")
            ->addSelect("mack")
            ->addSelect("eps")
            ->addSelect("von_hoa")
            ->addSelect("bvps")
            ->addSelect("tang_truong_bvps")
            ->where("mack", $mack)
            ->first();
        $eps_ttm = 0;
        try {
            $ocf_ttm = $this->calculate_ttm_contain_check_null($res, "ocf", 0);
            $capex_ttm = $this->calculate_ttm_contain_check_null($res, "capex", 0);

            $ty_le_tang_truong_tuong_lai =  $this->calculate_growth_rate_future($res) * 100;
        } catch (Exception $e) {
        }
        try {
            $eps_ttm = $data_eps->eps;
            $bvps = $data_eps->bvps;
            $tang_truong_bvps = $data_eps->tang_truong_bvps;
            $so_luong_co_phieu = $data_eps->von_hoa;
        } catch (Exception $e) {
        }
        if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 0))) {
            if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 4))) {
                if ($this->checkNullValueByYear($res, "bvps", 0, 4) != 0) {
                    $tang_truong_bvps = $res[0]["bvps"] < 0 ? -1 : ($res[0]["bvps"] - $res[4]["bvps"]) / abs($res[4]["bvps"]);
                }
            }
        }
        return [
            "mack" => $mack,
            "eps_ttm" => round($eps_ttm, 2),
            "ty_le_tang_truong_tuong_lai" => round($ty_le_tang_truong_tuong_lai, 2),
            "pe_cao_nhat" => max($list_pe),
            "pe_thap_nhat" => min($list_pe),
            "so_luong_co_phieu" => $so_luong_co_phieu,
            "ocf_ttm" => $ocf_ttm,
            "capex_ttm" => $capex_ttm,
            "bvps" => $bvps,
            "tang_truong_bvps" => $tang_truong_bvps * 100,
        ];
    }

    public function getDataInsurance(Request $req)
    {
        $mack = strtoupper($req->input("mack"));
        $thoigian = "quarter";
        $typeBank = "insurance";
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $cf = 'cf_' . $thoigian . '_' . $typeBank;
        $eps_ttm = 0;
        $ocf_ttm = 0;
        $capex_ttm = 0;
        $bvps = 0;
        $tang_truong_bvps = 0;
        $so_luong_co_phieu = 0;
        $ty_le_tang_truong_tuong_lai = 0;
        $column_select = DB::table("temp_table")
            ->addSelect(DB::raw('is.thoigian as is_thoigian'))
            ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
            ->addSelect(DB::raw('cf.thoigian as cf_thoigian'))
            ->addSelect(DB::raw('cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
            ->addSelect(DB::raw('(cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac+cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac) as capex'))
            ->addSelect(DB::raw('(bs.von_chu_so_huu + bs.loi_ich_co_dong_thieu_so - bs.tai_san_co_dinh_vo_hinh)/(bs.von_dau_tu_cua_chu_so_huu/10000) as bvps'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps'));

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
            ->take(24)
            ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.cf_thoigian,3),substr(m.cf_thoigian, 1, 2))) DESC"))
            ->get();
        $res = json_decode(json_encode($res), true);
        $list_pe = [];
        $list_close_price = $this->getListClosePriceByMack(strtoupper($mack), array_column($res, "is_thoigian"));
        for ($i = 0; $i < count($res) - 4; $i++) {
            $price = $this->getClosePriceByTime($list_close_price, $res[$i]["is_thoigian"]);
            $eps_ttm = $this->calculate_ttm_contain_check_null($res, "eps", $i);
            $pe = $eps_ttm != 0 ? $price / $eps_ttm : 0;
            array_push($list_pe, $pe);
        }
        $data_eps = DB::table("compare_insurance")
            ->addSelect("mack")
            ->addSelect("eps")
            ->addSelect("von_hoa")
            ->addSelect("bvps")
            ->addSelect("tang_truong_bvps")
            ->where("mack", $mack)
            ->first();
        $eps_ttm = 0;
        try {
            $ocf_ttm = $this->calculate_ttm_contain_check_null($res, "ocf", 0);
            $capex_ttm = $this->calculate_ttm_contain_check_null($res, "capex", 0);

            $ty_le_tang_truong_tuong_lai =  $this->calculate_growth_rate_future($res) * 100;
        } catch (Exception $e) {
        }
        try {
            $eps_ttm = $data_eps->eps;
            $bvps = $data_eps->bvps;
            $tang_truong_bvps = $data_eps->tang_truong_bvps;
            $so_luong_co_phieu = $data_eps->von_hoa;
        } catch (Exception $e) {
        }
        if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 0))) {
            if (!is_null($this->checkNullValueByYear($res, "bvps", 0, 4))) {
                if ($this->checkNullValueByYear($res, "bvps", 0, 4) != 0) {
                    $tang_truong_bvps = $res[0]["bvps"] < 0 ? -1 : ($res[0]["bvps"] - $res[4]["bvps"]) / abs($res[4]["bvps"]);
                }
            }
        }
        return [
            "mack" => $mack,
            "eps_ttm" => round($eps_ttm, 2),
            "ty_le_tang_truong_tuong_lai" => round($ty_le_tang_truong_tuong_lai, 2),
            "pe_cao_nhat" => max($list_pe),
            "pe_thap_nhat" => min($list_pe),
            "so_luong_co_phieu" => $so_luong_co_phieu*100,
            "ocf_ttm" => $ocf_ttm,
            "capex_ttm" => $capex_ttm,
            "bvps" => $bvps,
            "tang_truong_bvps" => $tang_truong_bvps * 100,
        ];
    }

    public function getData(Request $req)
    {
        $mack = strtoupper($req->input('mack'));
        $type = DB::table('danh_sach_mack')
            ->select("nhom")
            ->where('mack', '=', $mack)
            ->first();
        $type = $type->nhom;
        switch ($type) {
            case "nonbank":
                return $this->getDataNonbank($req);
                break;
            case "bank":
                return $this->getDataBank($req);
                break;
            case "stock":
                return $this->getDataStock($req);
                break;
            case "insurance":
                return $this->getDataInsurance($req);
                break;
            default:
                return "";
                break;
        }
    }

    public function index()
    {
        $user = JWTAuth::user();
        $list_data = DB::table('save_valuation')
            ->addSelect('id')
            ->addSelect('label')
            ->addSelect('content')
            ->where('users_id', $user->id)
            ->get();
        return response()->json($list_data, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required',
        ]);
        $user = JWTAuth::user();
        $check = DB::table('save_valuation')
            ->where('label',trim($request->label))
            ->where('users_id',$user->id)
            ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already name",
            ], 422);
        }
        $new_item = [
            "users_id" => $user->id,
            "label" => trim($request->label),
            "content" => $request->content,
        ];
        $last_insert_id = DB::table('save_valuation')->insertGetId($new_item);
        $new_item = array_merge(['id' => $last_insert_id], $new_item);
        return response()->json($new_item, 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'label' => 'required',
        ]);
        $user = JWTAuth::user();
        $check = DB::table('save_valuation')
            ->where('label',trim($request->label))
            ->where('users_id',$user->id)
            ->where('id', '<>', $request->id)
            ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already name",
            ], 422);
        }
        $item_edit = [
            "label" => trim($request->label),
            "content" => $request->content
        ];
        DB::table('save_valuation')
            ->where('id', $request->id)
            ->where('users_id', $user->id)
            ->update($item_edit);
        return response()->json([
            "id" => $request->id,
            "users_id" => $user->id,
            "label" => trim($request->label),
            "content" => $request->content
       ], 200);
    }

    public function destroy($id)
    {
        $user = JWTAuth::user();
        DB::table('save_valuation')
            ->where('users_id', $user->id)
            ->where('id', $id)
            ->delete();
        return response(null, 204);
    }
}
