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

class ChartController extends Controller
{
    //hàm trừ đi số quý
    protected function dec_quarter($time, $count)
    {
        $quarter = (int) substr($time, 1, 2);
        $year = (int) substr($time, -4);
        $year -= floor($count / 4);
        if ($quarter <= $count % 4) {
            $quarter = 4 - ($count % 4 - $quarter);
            $year--;
        } else
            $quarter -= $count % 4;
        return "Q" . $quarter . " " . $year;
    }

    //hàm check null và lấy giá trị đúng theo quý và năm
    protected function checkNullValueByYear($d, $key, $i, $i_next)
    {
        if ($i > count($d) || $i_next > count($d))
            return null;
        $thoigian = is_null($d[$i]["is_thoigian"]) ? $d[$i]["bs_thoigian"] : $d[$i]["is_thoigian"];
        $thoigian_muonlay = $this->dec_quarter($thoigian, $i_next - $i);
        // return $thoigian_muonlay;
        for ($j = $i; $j <= $i_next; $j++) {
            $thoigian_tam = is_null($d[$j]["is_thoigian"]) ? $d[$j]["bs_thoigian"] : $d[$j]["is_thoigian"];
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

    public function doanhThuLoiNhuanTTMNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 7)
            ->get(['thoigian', 'doanh_thu_thuan', 'loi_nhuan_sau_thue_tndn', 'loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me']);
        $count_res = count($res);
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 7; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] = $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 1]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 2]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 3]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'];
            $loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me_ttm_before = $res[$i + 4]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 5]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 6]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 7]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'];
            $res[$i]['loi_nhuan_sau_thue_tndn'] = ($res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] / $loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me_ttm_before - 1) * 100;
            $res[$i]['doanh_thu_thuan'] = round($res[$i]['doanh_thu_thuan'] / 1000, 1);
            $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] = round($res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] / 1000, 1);
            $res[$i]['loi_nhuan_sau_thue_tndn'] = round($res[$i]['loi_nhuan_sau_thue_tndn'], 1);
        }
        $res = array_slice($res, 0, $count_res - 7);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Doanh thu lợi nhuận TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Doanh thu thuần TTM", "LNST TTM", "Tăng trưởng LNST so với quý cùng kỳ"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Doanh thu thuần TTM",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LNST TTM",
                    "type" => "bar",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tăng trưởng LNST so với quý cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[2],
                ],
            ],
        ];
    }

    public function doanhThuLoiNhuanQuyNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->get(['thoigian', 'doanh_thu_thuan', 'loi_nhuan_sau_thue_tndn', 'loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me']);
        $count_res = count($res);
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['loi_nhuan_sau_thue_tndn'] = $res[$i + 4]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] != 0 ? ($res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] < 0 ? -1 : ($res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] - $res[$i + 4]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me']) / abs($res[$i + 4]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'])) * 100 : 0;
            $res[$i]['tt_dtt'] = $res[$i + 4]['doanh_thu_thuan'] != 0 ? ($res[$i]['doanh_thu_thuan'] < 0 ? -1 : ($res[$i]['doanh_thu_thuan'] - $res[$i + 4]['doanh_thu_thuan']) / abs($res[$i + 4]['doanh_thu_thuan'])) * 100 : 0;
            $res[$i]['doanh_thu_thuan'] = round($res[$i]['doanh_thu_thuan'] / 1000, 1);
            $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] = round($res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] / 1000, 1);
            $res[$i]['loi_nhuan_sau_thue_tndn'] = round($res[$i]['loi_nhuan_sau_thue_tndn'], 1);
            $res[$i]['tt_dtt'] = round($res[$i]['tt_dtt'], 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        if (!$res) {
            return [];
        }
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Doanh thu lợi nhuận quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => [
                    "Tăng trưởng doanh thu thuần",
                    "Tăng trưởng LNST so với quý cùng kỳ",
                    "Doanh thu thuần", "LNST",
                ],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Doanh thu thuần",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LNST",
                    "type" => "bar",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tăng trưởng LNST so với quý cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tăng trưởng doanh thu thuần",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ],

            ],
        ];
    }

    public function doanhThuLoiNhuanTTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_stock.thoigian,3),substr(is_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 7)
            ->addSelect("thoigian")
            ->addSelect("cong_doanh_thu_hoat_dong")
            ->addSelect("loi_nhuan_ke_toan_sau_thue_tndn")
            ->addSelect(DB::raw("0 as tang_truong_lnst"))
            ->get();
        $count_res = count($res);
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 7; $i++) {
            $res[$i]['cong_doanh_thu_hoat_dong'] = $res[$i]['cong_doanh_thu_hoat_dong'] + $res[$i + 1]['cong_doanh_thu_hoat_dong'] + $res[$i + 2]['cong_doanh_thu_hoat_dong'] + $res[$i + 3]['cong_doanh_thu_hoat_dong'];
            $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] = $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] + $res[$i + 1]['loi_nhuan_ke_toan_sau_thue_tndn'] + $res[$i + 2]['loi_nhuan_ke_toan_sau_thue_tndn'] + $res[$i + 3]['loi_nhuan_ke_toan_sau_thue_tndn'];
            $loi_nhuan_ke_toan_sau_thue_tndn_before = $res[$i + 4]['loi_nhuan_ke_toan_sau_thue_tndn'] + $res[$i + 5]['loi_nhuan_ke_toan_sau_thue_tndn'] + $res[$i + 6]['loi_nhuan_ke_toan_sau_thue_tndn'] + $res[$i + 7]['loi_nhuan_ke_toan_sau_thue_tndn'];

            $res[$i]['tang_truong_lnst'] = round((($res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] / $loi_nhuan_ke_toan_sau_thue_tndn_before - 1) * 100), 1);
            $res[$i]['cong_doanh_thu_hoat_dong'] = round(($res[$i]['cong_doanh_thu_hoat_dong']) / 1000, 1);
            $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] = round(($res[$i]['loi_nhuan_ke_toan_sau_thue_tndn']) / 1000, 1);
        }
        $res = array_slice($res, 0, $count_res - 7);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Doanh thu lợi nhuận TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Cộng doanh thu hoạt động TTM", "LNST TTM", "Tăng trưởng LNST so với quý cùng kỳ"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Cộng doanh thu hoạt động TTM",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LNST TTM",
                    "type" => "bar",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tăng trưởng LNST so với quý cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[3],
                ],
            ],
        ];
    }

    public function doanhThuLoiNhuanQuyStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_stock.thoigian,3),substr(is_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect("thoigian")
            ->addSelect("cong_doanh_thu_hoat_dong")
            ->addSelect("loi_nhuan_ke_toan_sau_thue_tndn")
            ->addSelect(DB::raw("0 as tang_truong_lnst"))
            ->addSelect(DB::raw("0 as tang_truong_cdt"))
            ->get();
        $count_res = count($res);
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['tang_truong_lnst'] = round((($res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] / $res[$i + 4]['loi_nhuan_ke_toan_sau_thue_tndn'] - 1) * 100), 1);
            $res[$i]['tang_truong_cdt'] = round((($res[$i]['cong_doanh_thu_hoat_dong'] / $res[$i + 4]['cong_doanh_thu_hoat_dong'] - 1) * 100), 1);
            $res[$i]['cong_doanh_thu_hoat_dong'] = round(($res[$i]['cong_doanh_thu_hoat_dong']) / 1000, 1);
            $res[$i]['loi_nhuan_ke_toan_sau_thue_tndn'] = round(($res[$i]['loi_nhuan_ke_toan_sau_thue_tndn']) / 1000, 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Doanh thu lợi nhuận quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Cộng doanh thu hoạt động", "LNST", "Tăng trưởng LNST so với quý cùng kỳ", "Tăng trưởng doanh thu"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Cộng doanh thu hoạt động",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LNST",
                    "type" => "bar",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tăng trưởng LNST so với quý cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tăng trưởng doanh thu",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ]
            ],
        ];
    }

    public function doanhThuLoiNhuanQuyInsurance(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_insurance')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_insurance.thoigian,3),substr(is_quarter_insurance.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect("thoigian")
            ->addSelect(DB::raw("doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + doanh_thu_hoat_dong_tai_chinh as tong_doanh_thu"))
            ->addSelect("loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as lnst")
            ->addSelect(DB::raw("0 as tang_truong_tdt"))
            ->addSelect(DB::raw("0 as tang_truong_lnst"))
            ->get();
        $count_res = count($res);
        if ($count_res < 4)
            return;
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['tang_truong_tdt'] = $res[$i + 4]['tong_doanh_thu'] != 0 ? round((($res[$i]['tong_doanh_thu'] / $res[$i + 4]['tong_doanh_thu'] - 1) * 100), 1) : 0;
            $res[$i]['tang_truong_lnst'] = $res[$i + 4]['lnst'] != 0 ? round((($res[$i]['lnst'] / $res[$i + 4]['lnst'] - 1) * 100), 1) : 0;
            $res[$i]['tong_doanh_thu'] = round(($res[$i]['tong_doanh_thu']) / 1000, 1);
            $res[$i]['lnst'] = round(($res[$i]['lnst']) / 1000, 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Doanh thu lợi nhuận quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Tổng doanh thu", "LNST", "Tăng trưởng doanh thu thuần", "Tăng trưởng LNST"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Tổng doanh thu",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LNST",
                    "type" => "bar",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tăng trưởng doanh thu thuần",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tăng trưởng LNST",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ]
            ],
        ];
    }

    public function anToanTTMInsurance(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_insurance')
            ->join('bs_quarter_insurance', function ($join) {
                $join->on('bs_quarter_insurance.mack', '=', 'is_quarter_insurance.mack')
                    ->on('bs_quarter_insurance.thoigian', '=', 'is_quarter_insurance.thoigian');
            })
            ->where('is_quarter_insurance.mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_insurance.thoigian,3),substr(is_quarter_insurance.thoigian, 1, 2)) DESC')
            ->take($limit + 3)
            ->addSelect(DB::raw("is_quarter_insurance.thoigian"))
            ->addSelect(DB::raw("doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as doanh_thu_thuan"))
            ->addSelect(DB::raw("du_phong_nghiep_vu"))
            ->get();
        $count_res = count($res);
        if ($count_res < 3)
            return;
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 3; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['ti_le_dp_dtbh'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['du_phong_nghiep_vu'] / $res[$i]['doanh_thu_thuan']), 1) : 0;

            $res[$i]['du_phong_nghiep_vu'] = round(($res[$i]['du_phong_nghiep_vu']) / 1000, 1);
            $res[$i]['ti_le_dp_dtbh'] = round(($res[$i]['ti_le_dp_dtbh']), 1);
        }
        $res = array_slice($res, 0, $count_res - 3);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "An toàn - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Dự phòng nghiệp vụ bảo hiểm", "Tỷ lệ dự phòng/Doanh thu bảo hiểm"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Lần",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "7%"
            ],
            "series" => [
                [
                    "name" => "Dự phòng nghiệp vụ bảo hiểm",
                    "type" => "bar",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tỷ lệ dự phòng/Doanh thu bảo hiểm",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[3],
                ]
            ],
        ];
    }

    public function taiSanQuyNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_nonbank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_nonbank.thoigian,3),substr(bs_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect('thoigian')
            ->addSelect('tien_va_cac_khoan_tuong_duong_tien')
            ->addSelect('cac_khoan_dau_tu_tai_chinh_ngan_han')
            ->addSelect('tong_hang_ton_kho')
            ->addSelect('tong_tai_san_ngan_han_khac as tai_san_ngan_han_khac')
            ->addSelect(DB::raw('cac_khoan_phai_thu_dai_han + cac_khoan_phai_thu_ngan_han as cac_khoan_phai_thu'))
            ->addSelect('tai_san_co_dinh')
            ->addSelect('bat_dong_san_dau_tu')
            ->addSelect('tai_san_do_dang_dai_han')
            ->addSelect('cac_khoan_dau_tu_tai_chinh_dai_han')
            ->addSelect(DB::raw('tong_tai_san_dai_han_khac + loi_thue_thuong_mai as tai_san_dai_han_khac'))
            ->addSelect('tong_cong_tai_san as tts')

            ->get();
        // return $res;
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]['tt_tts'] = $res[$i + 4]['tts'] != 0 ? $res[$i]['tts'] / $res[$i + 4]['tts'] - 1 : 0;
            $res[$i]['tien_va_cac_khoan_tuong_duong_tien'] = round($res[$i]['tien_va_cac_khoan_tuong_duong_tien'] / 1000, 1);
            $res[$i]['cac_khoan_dau_tu_tai_chinh_ngan_han'] = round($res[$i]['cac_khoan_dau_tu_tai_chinh_ngan_han'] / 1000, 1);
            $res[$i]['tong_hang_ton_kho'] = round($res[$i]['tong_hang_ton_kho'] / 1000, 1);
            $res[$i]['tai_san_ngan_han_khac'] = round($res[$i]['tai_san_ngan_han_khac'] / 1000, 1);
            $res[$i]['cac_khoan_phai_thu'] = round($res[$i]['cac_khoan_phai_thu'] / 1000, 1);
            $res[$i]['tai_san_co_dinh'] = round($res[$i]['tai_san_co_dinh'] / 1000, 1);
            $res[$i]['bat_dong_san_dau_tu'] = round($res[$i]['bat_dong_san_dau_tu'] / 1000, 1);
            $res[$i]['tai_san_do_dang_dai_han'] = round($res[$i]['tai_san_do_dang_dai_han'] / 1000, 1);
            $res[$i]['cac_khoan_dau_tu_tai_chinh_dai_han'] = round($res[$i]['cac_khoan_dau_tu_tai_chinh_dai_han'] / 1000, 1);
            $res[$i]['tai_san_dai_han_khac'] = round($res[$i]['tai_san_dai_han_khac'] / 1000, 1);
            $res[$i]['tt_tts'] = round($res[$i]['tt_tts'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Tài sản quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>${params.seriesName}%'
            ],

            "legend" => [
                "data" => ["Tiền và các khoản tương đương tiền", "Đầu tư tài chính ngắn hạn", "Tổng hàng tồn kho", "Tài sản ngắn hạn khác", "Các khoản phải thu", "Tài sản cố định", "Bất động sản đầu tư", "Tài sản dở dang dài hạn", "Đầu tư tài chính dài hạn", "Tài sản dài hạn khác", "Tăng trưởng TTS"],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                        "margin" => 0
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Tiền và các khoản tương đương tiền",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Đầu tư tài chính ngắn hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tổng hàng tồn kho",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tài sản ngắn hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Các khoản phải thu",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Tài sản cố định",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Bất động sản đầu tư",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Tài sản dở dang dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Đầu tư tài chính dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[9],
                ],
                [
                    "name" => "Tài sản dài hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[10],
                ],
                [
                    "name" => "Tăng trưởng TTS",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ]
            ],
        ];
    }

    public function taiSanQuyStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_stock')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_stock.thoigian,3),substr(bs_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect('thoigian')
            ->addSelect('tien_va_cac_khoan_tuong_duong_tien')
            ->addSelect(DB::raw('cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl as fvtpl'))
            ->addSelect(DB::raw('cac_khoan_dau_tu_giu_den_ngay_dao_han_htm as htm'))
            ->addSelect(DB::raw('cac_tai_san_tai_chinh_san_sang_de_ban_afs as afs'))
            ->addSelect(DB::raw('cac_khoan_cho_vay'))
            ->addSelect(DB::raw('cac_khoan_phai_thu'))
            ->addSelect(DB::raw('tai_san_ngan_han - (tien_va_cac_khoan_tuong_duong_tien+cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl+cac_khoan_dau_tu_giu_den_ngay_dao_han_htm+cac_tai_san_tai_chinh_san_sang_de_ban_afs+cac_khoan_cho_vay+cac_khoan_phai_thu) as tsnhk'))
            ->addSelect(DB::raw('tai_san_co_dinh'))
            ->addSelect(DB::raw('tai_san_tai_chinh_dai_han'))
            ->addSelect(DB::raw('tai_san_dai_han - (tai_san_co_dinh+tai_san_tai_chinh_dai_han) as tsdhk'))
            ->addSelect(DB::raw('tong_cong_tai_san'))

            ->get();

        // return $res;
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]['tt_tts'] = $res[$i + 4]['tong_cong_tai_san'] != 0 ? $res[$i]['tong_cong_tai_san'] / $res[$i + 4]['tong_cong_tai_san'] - 1 : 0;
            $res[$i]['tien_va_cac_khoan_tuong_duong_tien'] = round($res[$i]['tien_va_cac_khoan_tuong_duong_tien'] / 1000, 1);
            $res[$i]['fvtpl'] = round($res[$i]['fvtpl'] / 1000, 1);
            $res[$i]['htm'] = round($res[$i]['htm'] / 1000, 1);
            $res[$i]['afs'] = round($res[$i]['afs'] / 1000, 1);
            $res[$i]['cac_khoan_cho_vay'] = round($res[$i]['cac_khoan_cho_vay'] / 1000, 1);
            $res[$i]['cac_khoan_phai_thu'] = round($res[$i]['cac_khoan_phai_thu'] / 1000, 1);
            $res[$i]['tsnhk'] = round($res[$i]['tsnhk'] / 1000, 1);
            $res[$i]['tai_san_co_dinh'] = round($res[$i]['tai_san_co_dinh'] / 1000, 1);
            $res[$i]['tai_san_tai_chinh_dai_han'] = round($res[$i]['tai_san_tai_chinh_dai_han'] / 1000, 1);
            $res[$i]['tsdhk'] = round($res[$i]['tsdhk'] / 1000, 1);
            $res[$i]['tt_tts'] = round($res[$i]['tt_tts'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Tài sản quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>${params.seriesName}%'
            ],

            "legend" => [
                "data" => [
                    "Tiền và Tương đương tiền",
                    "FVTPL",
                    "HTM",
                    "AFS",
                    "Dư nợ Margin",
                    "Các khoản phải thu",
                    "Tài sản ngắn hạn khác",
                    "Tài sản cố định",
                    "Tài sản tài chính dài hạn",
                    "Tài sản dài hạn khác",
                    "Tăng trưởng TTS"
                ],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Tiền và Tương đương tiền",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "FVTPL",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "HTM",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "AFS",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Dư nợ Margin",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Các khoản phải thu",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Tài sản ngắn hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Tài sản cố định",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Tài sản tài chính dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[9],
                ],
                [
                    "name" => "Tài sản dài hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[10],
                ],
                [
                    "name" => "Tăng trưởng TTS",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function taiSanQuyInsurance(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_insurance')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect('thoigian')
            ->addSelect('tien as tien_va_cac_khoan_tuong_duong_tien')
            ->addSelect('cac_khoan_dau_tu_tai_chinh_ngan_han')
            ->addSelect('tong_hang_ton_kho')
            ->addSelect('tong_tai_san_ngan_han_khac as tai_san_ngan_han_khac')
            ->addSelect(DB::raw('cac_khoan_phai_thu + cac_khoan_phai_thu_dai_han as cac_khoan_phai_thu'))
            ->addSelect('tai_san_co_dinh')
            ->addSelect('bat_dong_san_dau_tu')
            ->addSelect('chi_phi_xay_dung_co_ban_do_dang as tai_san_do_dang_dai_han')
            ->addSelect('cac_khoan_dau_tu_tai_chinh_dai_han')
            ->addSelect(DB::raw('cac_khoan_ky_quy_ky_cuoc_dai_han as tai_san_dai_han_khac'))
            ->addSelect('tong_cong_tai_san as tts')
            ->get();
        // return $res;
        $res = json_decode(json_encode($res), true);
        if (count($res) < 4)
            return;
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]['tt_tts'] = $res[$i + 4]['tts'] != 0 ? $res[$i]['tts'] / $res[$i + 4]['tts'] - 1 : 0;
            $res[$i]['tien_va_cac_khoan_tuong_duong_tien'] = round($res[$i]['tien_va_cac_khoan_tuong_duong_tien'] / 1000, 1);
            $res[$i]['cac_khoan_dau_tu_tai_chinh_ngan_han'] = round($res[$i]['cac_khoan_dau_tu_tai_chinh_ngan_han'] / 1000, 1);
            $res[$i]['tong_hang_ton_kho'] = round($res[$i]['tong_hang_ton_kho'] / 1000, 1);
            $res[$i]['tai_san_ngan_han_khac'] = round($res[$i]['tai_san_ngan_han_khac'] / 1000, 1);
            $res[$i]['cac_khoan_phai_thu'] = round($res[$i]['cac_khoan_phai_thu'] / 1000, 1);
            $res[$i]['tai_san_co_dinh'] = round($res[$i]['tai_san_co_dinh'] / 1000, 1);
            $res[$i]['bat_dong_san_dau_tu'] = round($res[$i]['bat_dong_san_dau_tu'] / 1000, 1);
            $res[$i]['tai_san_do_dang_dai_han'] = round($res[$i]['tai_san_do_dang_dai_han'] / 1000, 1);
            $res[$i]['cac_khoan_dau_tu_tai_chinh_dai_han'] = round($res[$i]['cac_khoan_dau_tu_tai_chinh_dai_han'] / 1000, 1);
            $res[$i]['tai_san_dai_han_khac'] = round($res[$i]['tai_san_dai_han_khac'] / 1000, 1);
            $res[$i]['tt_tts'] = round($res[$i]['tt_tts'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Tài sản quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis"
            ],

            "legend" => [
                "data" => ["Tiền và các khoản tương đương tiền", "Đầu tư tài chính ngắn hạn", "Tổng hàng tồn kho", "Tài sản ngắn hạn khác", "Các khoản phải thu", "Tài sản cố định", "Bất động sản đầu tư", "Tài sản dở dang dài hạn", "Đầu tư tài chính dài hạn", "Tài sản dài hạn khác", "Tăng trưởng TTS"],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                        "margin" => 0
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Tiền và các khoản tương đương tiền",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Đầu tư tài chính ngắn hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tổng hàng tồn kho",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tài sản ngắn hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Các khoản phải thu",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Tài sản cố định",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Bất động sản đầu tư",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Tài sản dở dang dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Đầu tư tài chính dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[9],
                ],
                [
                    "name" => "Tài sản dài hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[10],
                ],
                [
                    "name" => "Tăng trưởng TTS",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ]
            ],
        ];
    }

    public function nguonVonQuyNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_nonbank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_nonbank.thoigian,3),substr(bs_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('thoigian'))
            ->addSelect(DB::raw('vay_va_no_thue_tai_chinh_ngan_han'))
            ->addSelect(DB::raw('vay_va_no_thue_tai_chinh_dai_han'))
            ->addSelect(DB::raw('phai_tra_nguoi_ban_dai_han + phai_tra_nguoi_ban_ngan_han as phai_tra_nguoi_ban'))
            ->addSelect(DB::raw('nguoi_mua_tra_tien_truoc'))
            ->addSelect(DB::raw('no_phai_tra-(vay_va_no_thue_tai_chinh_ngan_han+vay_va_no_thue_tai_chinh_dai_han+phai_tra_nguoi_ban_dai_han + phai_tra_nguoi_ban_ngan_han+nguoi_mua_tra_tien_truoc) as vay_no_khac'))
            ->addSelect(DB::raw('nguon_von_chu_so_huu-(loi_nhuan_sau_thue_chua_phan_phoi+thang_du_gop_co_phan+von_dau_tu_cua_chu_so_huu) as vcsh_khac'))
            ->addSelect(DB::raw('loi_nhuan_sau_thue_chua_phan_phoi'))
            ->addSelect(DB::raw('thang_du_gop_co_phan'))
            ->addSelect(DB::raw('von_dau_tu_cua_chu_so_huu'))
            ->addSelect(DB::raw('tong_cong_tai_san as tts'))
            ->get();
        // return $res;
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]['tt_tts'] = $res[$i + 4]['tts'] != 0 ? $res[$i]['tts'] / $res[$i + 4]['tts'] - 1 : 0;
            $res[$i]['vay_va_no_thue_tai_chinh_ngan_han'] = round($res[$i]['vay_va_no_thue_tai_chinh_ngan_han'] / 1000, 1);
            $res[$i]['vay_va_no_thue_tai_chinh_dai_han'] = round($res[$i]['vay_va_no_thue_tai_chinh_dai_han'] / 1000, 1);
            $res[$i]['phai_tra_nguoi_ban'] = round($res[$i]['phai_tra_nguoi_ban'] / 1000, 1);
            $res[$i]['nguoi_mua_tra_tien_truoc'] = round($res[$i]['nguoi_mua_tra_tien_truoc'] / 1000, 1);
            $res[$i]['vay_no_khac'] = round($res[$i]['vay_no_khac'] / 1000, 1);
            $res[$i]['vcsh_khac'] = round($res[$i]['vcsh_khac'] / 1000, 1);

            $res[$i]['loi_nhuan_sau_thue_chua_phan_phoi'] = round($res[$i]['loi_nhuan_sau_thue_chua_phan_phoi'] / 1000, 1);
            $res[$i]['thang_du_gop_co_phan'] = round($res[$i]['thang_du_gop_co_phan'] / 1000, 1);
            $res[$i]['von_dau_tu_cua_chu_so_huu'] = round($res[$i]['von_dau_tu_cua_chu_so_huu'] / 1000, 1);
            $res[$i]['tt_tts'] = round($res[$i]['tt_tts'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Nguồn vốn quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>${params.seriesName}%'
            ],

            "legend" => [
                "data" => [
                    "Vay và nợ thuê tài chính ngắn hạn",
                    "Vay và nợ thuê tài chính dài hạn",
                    "Phải trả người bán",
                    "Người mua trả tiền trước",
                    "Vay nợ khác",
                    "Vốn chủ sở hữu khác",
                    "Lợi nhuận sau thuế chưa phân phối",
                    "Thặng dư vốn cổ phần",
                    "Vốn đầu tư của chủ sở hữu",
                    "Tăng trưởng TTS"
                ],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                        "margin" => 0
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Vay và nợ thuê tài chính ngắn hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Vay và nợ thuê tài chính dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Phải trả người bán",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Người mua trả tiền trước",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Vay nợ khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Vốn chủ sở hữu khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Lợi nhuận sau thuế chưa phân phối",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Thặng dư vốn cổ phần",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Vốn đầu tư của chủ sở hữu",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[9],
                ],
                [
                    "name" => "Tăng trưởng TTS",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
            ],
        ];
    }

    public function nguonVonQuyQuyStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_stock')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_stock.thoigian,3),substr(bs_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect('thoigian')
            ->addSelect(DB::raw('vay_va_no_thue_tai_san_tai_chinh_ngan_han'))
            ->addSelect(DB::raw('vay_va_no_thue_tai_san_tai_chinh_dai_han'))
            ->addSelect(DB::raw('no_phai_tra_ngan_han-vay_va_no_thue_tai_san_tai_chinh_ngan_han as no_ngan_han_khac'))
            ->addSelect(DB::raw('no_phai_tra_dai_han-vay_va_no_thue_tai_san_tai_chinh_dai_han as no_dai_han_khac'))
            ->addSelect(DB::raw('tong_von_chu_so_huu-(loi_nhuan_chua_phan_phoi+thang_du_von_co_phan+von_gop_cua_chu_so_huu) as von_chu_so_huu_khac'))
            ->addSelect(DB::raw('loi_nhuan_chua_phan_phoi'))
            ->addSelect(DB::raw('thang_du_von_co_phan'))
            ->addSelect(DB::raw('von_gop_cua_chu_so_huu'))
            ->addSelect(DB::raw('tong_cong_no_phai_tra_va_von_chu_so_huu as tts'))

            ->get();

        // return $res;
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]['tt_nv'] = $res[$i + 4]['tts'] != 0 ? $res[$i]['tts'] / $res[$i + 4]['tts'] - 1 : 0;
            $res[$i]['vay_va_no_thue_tai_san_tai_chinh_ngan_han'] = round($res[$i]['vay_va_no_thue_tai_san_tai_chinh_ngan_han'] / 1000, 1);
            $res[$i]['vay_va_no_thue_tai_san_tai_chinh_dai_han'] = round($res[$i]['vay_va_no_thue_tai_san_tai_chinh_dai_han'] / 1000, 1);
            $res[$i]['no_ngan_han_khac'] = round($res[$i]['no_ngan_han_khac'] / 1000, 1);
            $res[$i]['no_dai_han_khac'] = round($res[$i]['no_dai_han_khac'] / 1000, 1);
            $res[$i]['von_chu_so_huu_khac'] = round($res[$i]['von_chu_so_huu_khac'] / 1000, 1);
            $res[$i]['loi_nhuan_chua_phan_phoi'] = round($res[$i]['loi_nhuan_chua_phan_phoi'] / 1000, 1);
            $res[$i]['thang_du_von_co_phan'] = round($res[$i]['thang_du_von_co_phan'] / 1000, 1);
            $res[$i]['von_gop_cua_chu_so_huu'] = round($res[$i]['von_gop_cua_chu_so_huu'] / 1000, 1);
            $res[$i]['tt_nv'] = round($res[$i]['tt_nv'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Nguồn vốn quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>${params.seriesName}%'
            ],

            "legend" => [
                "data" => [
                    "Vay và nợ thuê tài chính ngắn hạn",
                    "Vay và nợ thuê tài chính dài hạn",
                    "Nợ ngắn hạn khác",
                    "Nợ dài hạn khác",
                    "VCSH khác",
                    "LNST chưa phân phối",
                    "Thặng dư vốn cổ phần",
                    "Vốn góp của chủ sở hữu",
                    "Tăng trưởng NV"
                ],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Vay và nợ thuê tài chính ngắn hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Vay và nợ thuê tài chính dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Nợ ngắn hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Nợ dài hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "VCSH khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[5],
                ],
                [
                    "name" => "LNST chưa phân phối",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Thặng dư vốn cổ phần",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Vốn góp của chủ sở hữu",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Tăng trưởng NV",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[10],
                ],
            ],
        ];
    }

    public function nguonVonQuyInsurance(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_insurance')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('thoigian'))
            ->addSelect(DB::raw('vay_va_no_ngan_han as vay_va_no_thue_tai_chinh_ngan_han'))
            ->addSelect(DB::raw('vay_dai_han as vay_va_no_thue_tai_chinh_dai_han'))
            ->addSelect(DB::raw('du_phong_nghiep_vu as du_phong_nghiep_vu'))
            ->addSelect(DB::raw('no_ngan_han-vay_va_no_ngan_han as no_ngan_han_khac'))
            ->addSelect(DB::raw('tong_no_dai_han-vay_dai_han as no_dai_han_khac'))
            ->addSelect(DB::raw('no_khac'))
            ->addSelect(DB::raw('nguon_von_chu_so_huu-loi_nhuan_sau_thue_chua_phan_phoi-thang_du_von_co_phan-von_dau_tu_cua_chu_so_huu+loi_ich_co_dong_thieu_so as vcsh_khac'))
            ->addSelect(DB::raw('loi_nhuan_sau_thue_chua_phan_phoi'))
            ->addSelect(DB::raw('thang_du_von_co_phan'))
            ->addSelect(DB::raw('von_dau_tu_cua_chu_so_huu'))
            ->addSelect(DB::raw('tong_cong_nguon_von as tcnv'))
            ->get();
        // return $res;
        $res = json_decode(json_encode($res), true);
        if (count($res) < 4)
            return;
        for ($i = 0; $i < count($res) - 4; $i++) {
            $res[$i]['tt_nv'] = $res[$i + 4]['tcnv'] != 0 ? $res[$i]['tcnv'] / $res[$i + 4]['tcnv'] - 1 : 0;
            $res[$i]['vay_va_no_thue_tai_chinh_ngan_han'] = round($res[$i]['vay_va_no_thue_tai_chinh_ngan_han'] / 1000, 1);
            $res[$i]['vay_va_no_thue_tai_chinh_dai_han'] = round($res[$i]['vay_va_no_thue_tai_chinh_dai_han'] / 1000, 1);
            $res[$i]['du_phong_nghiep_vu'] = round($res[$i]['du_phong_nghiep_vu'] / 1000, 1);
            $res[$i]['no_ngan_han_khac'] = round($res[$i]['no_ngan_han_khac'] / 1000, 1);
            $res[$i]['no_dai_han_khac'] = round($res[$i]['no_dai_han_khac'] / 1000, 1);
            $res[$i]['no_khac'] = round($res[$i]['no_khac'] / 1000, 1);
            $res[$i]['vcsh_khac'] = round($res[$i]['vcsh_khac'] / 1000, 1);
            $res[$i]['loi_nhuan_sau_thue_chua_phan_phoi'] = round($res[$i]['loi_nhuan_sau_thue_chua_phan_phoi'] / 1000, 1);
            $res[$i]['thang_du_von_co_phan'] = round($res[$i]['thang_du_von_co_phan'] / 1000, 1);
            $res[$i]['von_dau_tu_cua_chu_so_huu'] = round($res[$i]['von_dau_tu_cua_chu_so_huu'] / 1000, 1);
            $res[$i]['tt_nv'] = round($res[$i]['tt_nv'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Nguồn vốn quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis"
            ],
            "legend" => [
                "data" => [
                    "Vay và nợ thuê tài chính ngắn hạn",
                    "Vay và nợ thuê tài chính dài hạn",
                    "Dự phòng nghiệp vụ bảo hiểm",
                    "Nợ ngắn hạn khác",
                    "Nợ dài hạn khác",
                    "Nợ khác",
                    "Vốn chủ sở hữu khác",
                    "Lợi nhuận sau thuế chưa phân phối",
                    "Thặng dư vốn cổ phần",
                    "Vốn đầu tư của chủ sở hữu",
                    "Tăng trưởng NV"
                ],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                        "margin" => 0
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Vay và nợ thuê tài chính ngắn hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Vay và nợ thuê tài chính dài hạn",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Dự phòng nghiệp vụ bảo hiểm",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Nợ ngắn hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Nợ dài hạn khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Nợ khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Vốn chủ sở hữu khác",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Lợi nhuận sau thuế chưa phân phối",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Thặng dư vốn cổ phần",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[9],
                ],
                [
                    "name" => "Vốn đầu tư của chủ sở hữu",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[10],
                ],
                [
                    "name" => "Tăng trưởng NV",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function luuChuyenTienQuyNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('cf_quarter_nonbank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(cf_quarter_nonbank.thoigian,3),substr(cf_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->get(['thoigian', 'luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh', 'luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu', 'luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh', 'luu_chuyen_tien_thuan_trong_ky']);
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_trong_ky'] = round($res[$i]['luu_chuyen_tien_thuan_trong_ky'] / 1000, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Lưu chuyển tiền quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {c2} tỷ VNĐ'
            ],

            "legend" => [
                "data" => ["LCTT từ hoạt động kinh doanh", "LCTT từ hoạt động đầu tư", "LCTT từ hoạt động tài chính", "LCTT trong kỳ"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
            ],
            "series" => [
                [
                    "name" => "LCTT từ hoạt động kinh doanh",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LCTT từ hoạt động đầu tư",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "LCTT từ hoạt động tài chính",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "LCTT trong kỳ",
                    "type" => "line",
                    "data" => $arr[4],
                ],
            ],
        ];
    }

    public function luuChuyenTienQuyStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('cf_quarter_stock')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(cf_quarter_stock.thoigian,3),substr(cf_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->addSelect(DB::raw('cf_quarter_stock.thoigian'))
            ->addSelect(DB::raw('cf_quarter_stock.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'))
            ->addSelect(DB::raw('cf_quarter_stock.luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'))
            ->addSelect(DB::raw('cf_quarter_stock.luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'))
            ->addSelect(DB::raw('cf_quarter_stock.tang_giam_tien_thuan_trong_ky as luu_chuyen_tien_thuan_trong_ky'))

            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_trong_ky'] = round($res[$i]['luu_chuyen_tien_thuan_trong_ky'] / 1000, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Lưu chuyển tiền quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {c2} tỷ VNĐ'
            ],

            "legend" => [
                "data" => ["LCTT từ hoạt động kinh doanh", "LCTT từ hoạt động đầu tư", "LCTT từ hoạt động tài chính", "LCTT trong kỳ"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "LCTT từ hoạt động kinh doanh",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LCTT từ hoạt động đầu tư",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "LCTT từ hoạt động tài chính",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "LCTT trong kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ],
            ],
        ];
    }

    public function luuChuyenTienQuyInsurance(Request $req)
    {
        $is_type_direct_insurance = $req->input('is_type_direct_insurance');
        $res = [];
        $limit = 11;
        $res = DB::table('cf_quarter_insurance')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->take($limit)
            ->addSelect('thoigian')
            ->addSelect(DB::raw($is_type_direct_insurance ? 'luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh' : 'luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'))
            ->addSelect(DB::raw($is_type_direct_insurance ? 'luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu as luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu' : 'luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu as luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'))
            ->addSelect(DB::raw($is_type_direct_insurance ? 'luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh as luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh' : 'luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'))
            ->addSelect(DB::raw($is_type_direct_insurance ? 'luu_chuyen_tien_thuan_trong_ky as luu_chuyen_tien_thuan_trong_ky' : 'luu_chuyen_tien_thuan_trong_ky'))
            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_dau_tu'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'] = round($res[$i]['luu_chuyen_tien_thuan_tu_hoat_dong_tai_chinh'] / 1000, 1);
            $res[$i]['luu_chuyen_tien_thuan_trong_ky'] = round($res[$i]['luu_chuyen_tien_thuan_trong_ky'] / 1000, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Lưu chuyển tiền quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => ["LCTT từ hoạt động kinh doanh", "LCTT từ hoạt động đầu tư", "LCTT từ hoạt động tài chính", "LCTT trong kỳ"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ]
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "5%"
            ],
            "series" => [
                [
                    "name" => "LCTT từ hoạt động kinh doanh",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "LCTT từ hoạt động đầu tư",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "LCTT từ hoạt động tài chính",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "LCTT trong kỳ",
                    "type" => "line",
                    "data" => $arr[4],
                ],
            ],
        ];
    }

    public function khaNangThanhToanTTMNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->join('bs_quarter_nonbank', function ($join) {
                $join->on('bs_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('bs_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->join('cf_quarter_nonbank', function ($join) {
                $join->on('cf_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('cf_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->where("is_quarter_nonbank.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 3)
            ->addSelect(DB::raw('is_quarter_nonbank.thoigian'))
            ->addSelect(DB::raw('bs_quarter_nonbank.tai_san_luu_dong_va_dau_tu_ngan_han as tai_san_luu_dong_va_dau_tu_ngan_han'))
            ->addSelect(DB::raw('bs_quarter_nonbank.tong_hang_ton_kho as tong_hang_ton_kho'))
            ->addSelect(DB::raw('bs_quarter_nonbank.no_ngan_han as no_ngan_han'))
            ->addSelect(DB::raw('0 as thanh_toan_hien_hanh'))
            ->addSelect(DB::raw('0 as thanh_toan_nhanh'))
            ->addSelect(DB::raw('bs_quarter_nonbank.vay_va_no_thue_tai_chinh_dai_han as no_vay_dai_han'))
            ->addSelect(DB::raw('(cf_quarter_nonbank.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh + cf_quarter_nonbank.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_tai_san_dai_han_khac+cf_quarter_nonbank.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_tai_san_dai_han_khac) as fcf'))
            ->addSelect(DB::raw('cf_quarter_nonbank.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf'))
            ->addSelect(DB::raw('cf_quarter_nonbank.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_tai_san_dai_han_khac as tien_chi'))
            ->addSelect(DB::raw('cf_quarter_nonbank.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_tai_san_dai_han_khac as tien_thu'))


            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 3; $i++) {
            $res[$i]['ocf'] = $res[$i]['ocf'] + $res[$i + 1]['ocf'] + $res[$i + 2]['ocf'] + $res[$i + 3]['ocf'];
            $res[$i]['fcf'] = $res[$i]['ocf'] + ($res[$i]['tien_chi'] + $res[$i + 1]['tien_chi'] + $res[$i + 2]['tien_chi'] + $res[$i + 3]['tien_chi']) + ($res[$i]['tien_thu'] + $res[$i + 1]['tien_thu'] + $res[$i + 2]['tien_thu'] + $res[$i + 3]['tien_thu']);
            $res[$i]['thanh_toan_hien_hanh'] = $res[$i]['no_ngan_han'] != 0 ? round(($res[$i]['tai_san_luu_dong_va_dau_tu_ngan_han'] /  $res[$i]['no_ngan_han']), 1) : 0;
            $res[$i]['thanh_toan_nhanh'] = $res[$i]['no_ngan_han'] != 0 ? round((($res[$i]['tai_san_luu_dong_va_dau_tu_ngan_han'] - $res[$i]['tong_hang_ton_kho']) /  $res[$i]['no_ngan_han']), 1) : 0;
            $res[$i]['ocf'] = round($res[$i]['ocf'], 1);
            $res[$i]['fcf'] = round($res[$i]['fcf'], 1);
            $res[$i]['no_vay_dai_han'] = round($res[$i]['no_vay_dai_han'], 1);
        }
        $res = array_slice($res, 0, $count_res - 3);
        $arr = [];
        if (!$res) {
            return [];
        }
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Khả năng thanh toán - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],

            "legend" => [
                "data" => ["FCF", "OCF", "Thanh toán hiện hành", "Thanh toán nhanh", "Nợ vay dài hạn"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu đồng",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                    "left" => "2%"
                ],
                [
                    "type" => "value",
                    "name" => "Lần",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ]
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "FCF",
                    "type" => "bar",
                    "data" => $arr[7],
                ],
                [
                    "name" => "OCF",
                    "type" => "bar",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Thanh toán hiện hành",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ],
                [
                    "name" => "Thanh toán nhanh",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[5],
                ],
                [
                    "name" => "Nợ vay dài hạn",
                    "type" => "bar",
                    "data" => $arr[6],
                ]
            ],
        ];
    }

    public function khaNangThanhToanTTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_stock as bs')
            ->where("bs.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs.thoigian,3),substr(bs.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->addSelect(DB::raw('bs.thoigian'))
            ->addSelect(DB::raw('bs.tai_san_ngan_han/bs.no_phai_tra_ngan_han as ty_le_thanh_toan_nhanh'))
            ->addSelect(DB::raw('bs.no_phai_tra/bs.von_chu_so_huu as de'))

            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['ty_le_thanh_toan_nhanh'] = round($res[$i]['ty_le_thanh_toan_nhanh'] * 100, 1);
            $res[$i]['de'] = round($res[$i]['de'] * 100, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Khả năng thanh toán TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",

            ],
            "legend" => [
                "data" => [
                    "% Dự phòng TSCT",
                    "% Dự phòng KPT",
                    "Tỷ lệ Dự nợ Margin/VCSH",
                ],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ]
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Tỷ lệ thanh toán nhanh",
                    "type" => "line",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Tỷ lệ D/E",
                    "type" => "line",
                    "data" => $arr[2],
                ],
            ],
        ];
    }

    public function khaNangThanhToanTTMInsurance(Request $req)
    {
        $is_type_direct_insurance = $req->input('is_type_direct_insurance');
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_insurance as bs')
            ->join('cf_quarter_insurance as cf', function ($join) {
                $join->on('cf.mack', '=', 'bs.mack')
                    ->on('cf.thoigian', '=', 'bs.thoigian');
            })
            ->where("bs.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs.thoigian,3),substr(bs.thoigian, 1, 2)) DESC')
            ->take($limit + 3)
            ->addSelect(DB::raw('bs.thoigian'))
            ->addSelect(DB::raw('bs.tai_san_luu_dong_va_dau_tu_ngan_han as tai_san_ngan_han'))
            ->addSelect(DB::raw('bs.du_phong_nghiep_vu'))
            ->addSelect(DB::raw('bs.no_ngan_han'))
            ->addSelect(DB::raw('bs.vay_dai_han as no_vay_dai_han'))

            ->addSelect(DB::raw($is_type_direct_insurance ? "cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf" : "cf.luu_chuyen_tien_thuan_tu_hoat_dong_kinh_doanh as ocf"))
            ->addSelect(DB::raw($is_type_direct_insurance ? "cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac as tien_chi_mua_sam_tscd" : "cf.tien_chi_de_mua_sam_xay_dung_tscd_va_cac_ts_dai_han_khac as tien_chi_mua_sam_tscd"))
            ->addSelect(DB::raw($is_type_direct_insurance ? "cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as tien_thu_tu_thanh_ly_tscd" : "cf.tien_thu_tu_thanh_ly_nhuong_ban_tscd_va_cac_ts_dai_han_khac as tien_thu_tu_thanh_ly_tscd"))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        if ($count_res < 3)
            return;
        for ($i = 0; $i < $count_res - 3; $i++) {
            $res[$i]['thanh_toan_hien_hanh'] = $res[$i]['no_ngan_han'] != 0 ? round(($res[$i]['tai_san_ngan_han'] /  $res[$i]['no_ngan_han']), 1) : 0;
            $res[$i]['thanh_toan_du_phong'] = $res[$i]['du_phong_nghiep_vu'] != 0 ? round(($res[$i]['tai_san_ngan_han'] /  $res[$i]['du_phong_nghiep_vu']), 1) : 0;
            $res[$i]['no_vay_dai_han'] = round($res[$i]['no_vay_dai_han'] / 1000, 1);

            $tcms_tscd_ttm = $res[$i]['tien_chi_mua_sam_tscd'] + $res[$i + 1]['tien_chi_mua_sam_tscd'] + $res[$i + 2]['tien_chi_mua_sam_tscd'] + $res[$i + 3]['tien_chi_mua_sam_tscd'];
            $ttttl_tscd_ttm = $res[$i]['tien_thu_tu_thanh_ly_tscd'] + $res[$i + 1]['tien_thu_tu_thanh_ly_tscd'] + $res[$i + 2]['tien_thu_tu_thanh_ly_tscd'] + $res[$i + 3]['tien_thu_tu_thanh_ly_tscd'];
            $res[$i]['ocf'] = $res[$i]['ocf'] + $res[$i + 1]['ocf'] + $res[$i + 2]['ocf'] + $res[$i + 3]['ocf'];
            $res[$i]['fcf'] = $res[$i]['ocf'] + $tcms_tscd_ttm + $ttttl_tscd_ttm;


            $res[$i]['ocf'] = round($res[$i]['ocf'] / 1000, 1);
            $res[$i]['fcf'] = round($res[$i]['fcf'] / 1000, 1);
        }
        $res = array_slice($res, 0, $count_res - 3);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Khả năng thanh toán - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => ["FCF TTM", "OCF TTM", "Thanh toán hiện hành", "Thanh toán dự phòng", "Nợ vay dài hạn"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ đồng",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                    "left" => "2%"
                ],
                [
                    "type" => "value",
                    "name" => "Lần",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ]
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "8%"
            ],
            "series" => [
                [
                    "name" => "Nợ vay dài hạn",
                    "type" => "bar",
                    "data" => $arr[4],
                ],
                [
                    "name" => "FCF TTM",
                    "type" => "bar",
                    "data" => $arr[10],
                ],
                [
                    "name" => "OCF TTM",
                    "type" => "bar",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Thanh toán hiện hành",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[8],
                ],
                [
                    "name" => "Thanh toán dự phòng",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ]
            ],
        ];
    }

    public function bienLaiTTMNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->join('cf_quarter_nonbank', function ($join) {
                $join->on('cf_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('cf_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->where("is_quarter_nonbank.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 3)
            ->addSelect(DB::raw('is_quarter_nonbank.thoigian'))
            ->addSelect(DB::raw('is_quarter_nonbank.doanh_thu_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_nonbank.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'))
            ->addSelect(DB::raw('is_quarter_nonbank.loi_nhuan_gop as loi_nhuan_gop'))
            ->addSelect(DB::raw('is_quarter_nonbank.tong_loi_nhuan_ke_toan_truoc_thue as tong_loi_nhuan_ke_toan_truoc_thue'))
            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 3; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] = $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 1]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 2]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] + $res[$i + 3]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'];
            $res[$i]['loi_nhuan_gop'] = $res[$i]['loi_nhuan_gop'] + $res[$i + 1]['loi_nhuan_gop'] + $res[$i + 2]['loi_nhuan_gop'] + $res[$i + 3]['loi_nhuan_gop'];
            $res[$i]['tong_loi_nhuan_ke_toan_truoc_thue'] = $res[$i]['tong_loi_nhuan_ke_toan_truoc_thue'] + $res[$i + 1]['tong_loi_nhuan_ke_toan_truoc_thue'] + $res[$i + 2]['tong_loi_nhuan_ke_toan_truoc_thue'] + $res[$i + 3]['tong_loi_nhuan_ke_toan_truoc_thue'];

            $res[$i]['bien_loi_nhuan_rong'] = $res[$i]['doanh_thu_thuan'] != 0 ? $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] / $res[$i]['doanh_thu_thuan'] : 0;
            $res[$i]['bien_loi_nhuan_gop'] = $res[$i]['doanh_thu_thuan'] != 0 ? $res[$i]['loi_nhuan_gop'] / $res[$i]['doanh_thu_thuan'] : 0;
            $res[$i]['bien_loi_nhuan_truoc_thue'] = $res[$i]['doanh_thu_thuan'] != 0 ? $res[$i]['tong_loi_nhuan_ke_toan_truoc_thue'] / $res[$i]['doanh_thu_thuan'] : 0;

            $res[$i]['bien_loi_nhuan_rong'] = round($res[$i]['bien_loi_nhuan_rong'] * 100, 1);
            $res[$i]['bien_loi_nhuan_gop'] = round($res[$i]['bien_loi_nhuan_gop'] * 100, 1);
            $res[$i]['bien_loi_nhuan_truoc_thue'] = round($res[$i]['bien_loi_nhuan_truoc_thue'] * 100, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        $res = array_slice($res, 0, count($res) - 3);
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Biên lợi nhuận TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b> %'
            ],

            "legend" => [
                "data" => ["Biên lợi nhuận ròng TTM", "Biên lợi nhuận gộp TTM", "Biên lợi nhuận trước thuế TTM"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Biên lợi nhuận ròng TTM",
                    "type" => "line",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Biên lợi nhuận gộp TTM",
                    "type" => "line",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Biên lợi nhuận trước thuế TTM",
                    "type" => "line",
                    "data" => $arr[7],
                ],
            ],
        ];
    }

    public function bienLaiQuyNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->join('cf_quarter_nonbank', function ($join) {
                $join->on('cf_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('cf_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->where("is_quarter_nonbank.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 3)
            ->addSelect(DB::raw('is_quarter_nonbank.thoigian'))
            ->addSelect(DB::raw('is_quarter_nonbank.doanh_thu_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_nonbank.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'))
            ->addSelect(DB::raw('is_quarter_nonbank.loi_nhuan_gop as loi_nhuan_gop'))
            ->addSelect(DB::raw('is_quarter_nonbank.tong_loi_nhuan_ke_toan_truoc_thue as tong_loi_nhuan_ke_toan_truoc_thue'))
            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 3; $i++) {
            $res[$i]['bien_loi_nhuan_rong'] = $res[$i]['doanh_thu_thuan'] != 0 ? $res[$i]['loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me'] / $res[$i]['doanh_thu_thuan'] : 0;
            $res[$i]['bien_loi_nhuan_gop'] = $res[$i]['doanh_thu_thuan'] != 0 ? $res[$i]['loi_nhuan_gop'] / $res[$i]['doanh_thu_thuan'] : 0;
            $res[$i]['bien_loi_nhuan_truoc_thue'] = $res[$i]['doanh_thu_thuan'] != 0 ? $res[$i]['tong_loi_nhuan_ke_toan_truoc_thue'] / $res[$i]['doanh_thu_thuan'] : 0;

            $res[$i]['bien_loi_nhuan_rong'] = round($res[$i]['bien_loi_nhuan_rong'] * 100, 1);
            $res[$i]['bien_loi_nhuan_gop'] = round($res[$i]['bien_loi_nhuan_gop'] * 100, 1);
            $res[$i]['bien_loi_nhuan_truoc_thue'] = round($res[$i]['bien_loi_nhuan_truoc_thue'] * 100, 1);
        }
        $arr = [];
        $res = array_slice($res, 0, count($res) - 3);
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Biên lợi nhuận quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b> %'
            ],

            "legend" => [
                "data" => ["Biên lợi nhuận ròng", "Biên lợi nhuận gộp", "Biên lợi nhuận trước thuế"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Biên lợi nhuận ròng",
                    "type" => "line",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Biên lợi nhuận gộp",
                    "type" => "line",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Biên lợi nhuận trước thuế",
                    "type" => "line",
                    "data" => $arr[7],
                ],
            ],
        ];
    }

    public function bienLaiTTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock as is')
            ->join('cf_quarter_stock as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->take($limit + 3)
            ->addSelect(DB::raw('is.thoigian'))

            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan as doanh_thu_moi_gioi'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu as doanh_thu_cho_vay'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as doanh_thu_bao_lanh'))
            ->addSelect(DB::raw('is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs as doanh_thu_tu_doanh'))
            ->addSelect(DB::raw('is.chi_phi_moi_gioi_chung_khoan as chi_phi_moi_gioi'))
            ->addSelect(DB::raw('is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu as chi_phi_cho_vay'))
            ->addSelect(DB::raw('is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan as chi_phi_bao_lanh'))
            ->addSelect(DB::raw('(is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh) as chi_phi_tu_doanh'))

            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res) - 3; $i++) {
            $res[$i]['doanh_thu_moi_gioi'] = $res[$i]['doanh_thu_moi_gioi'] + $res[$i + 1]['doanh_thu_moi_gioi'] + $res[$i + 2]['doanh_thu_moi_gioi'] + $res[$i + 3]['doanh_thu_moi_gioi'];
            $res[$i]['doanh_thu_cho_vay'] = $res[$i]['doanh_thu_cho_vay'] + $res[$i + 1]['doanh_thu_cho_vay'] + $res[$i + 2]['doanh_thu_cho_vay'] + $res[$i + 3]['doanh_thu_cho_vay'];
            $res[$i]['doanh_thu_bao_lanh'] = $res[$i]['doanh_thu_bao_lanh'] + $res[$i + 1]['doanh_thu_bao_lanh'] + $res[$i + 2]['doanh_thu_bao_lanh'] + $res[$i + 3]['doanh_thu_bao_lanh'];
            $res[$i]['doanh_thu_tu_doanh'] = $res[$i]['doanh_thu_tu_doanh'] + $res[$i + 1]['doanh_thu_tu_doanh'] + $res[$i + 2]['doanh_thu_tu_doanh'] + $res[$i + 3]['doanh_thu_tu_doanh'];

            $res[$i]['chi_phi_moi_gioi'] = $res[$i]['chi_phi_moi_gioi'] + $res[$i + 1]['chi_phi_moi_gioi'] + $res[$i + 2]['chi_phi_moi_gioi'] + $res[$i + 3]['chi_phi_moi_gioi'];
            $res[$i]['chi_phi_cho_vay'] = $res[$i]['chi_phi_cho_vay'] + $res[$i + 1]['chi_phi_cho_vay'] + $res[$i + 2]['chi_phi_cho_vay'] + $res[$i + 3]['chi_phi_cho_vay'];
            $res[$i]['chi_phi_bao_lanh'] = $res[$i]['chi_phi_bao_lanh'] + $res[$i + 1]['chi_phi_bao_lanh'] + $res[$i + 2]['chi_phi_bao_lanh'] + $res[$i + 3]['chi_phi_bao_lanh'];
            $res[$i]['chi_phi_tu_doanh'] = $res[$i]['chi_phi_tu_doanh'] + $res[$i + 1]['chi_phi_tu_doanh'] + $res[$i + 2]['chi_phi_tu_doanh'] + $res[$i + 3]['chi_phi_tu_doanh'];

            $res[$i]['bien_moi_gioi'] = $res[$i]['doanh_thu_moi_gioi'] != 0 ? ($res[$i]['doanh_thu_moi_gioi'] - $res[$i]['chi_phi_moi_gioi']) / $res[$i]['doanh_thu_moi_gioi'] : 0;
            $res[$i]['bien_cho_vay'] = $res[$i]['doanh_thu_cho_vay'] != 0 ? ($res[$i]['doanh_thu_cho_vay'] - $res[$i]['chi_phi_cho_vay']) / $res[$i]['doanh_thu_cho_vay'] : 0;
            $res[$i]['bien_bao_lanh'] = $res[$i]['doanh_thu_bao_lanh'] != 0 ? ($res[$i]['doanh_thu_bao_lanh'] - $res[$i]['chi_phi_bao_lanh']) / $res[$i]['doanh_thu_bao_lanh'] : 0;
            $res[$i]['bien_tu_doanh'] = $res[$i]['doanh_thu_tu_doanh'] != 0 ? ($res[$i]['doanh_thu_tu_doanh'] - $res[$i]['chi_phi_tu_doanh']) / $res[$i]['doanh_thu_tu_doanh'] : 0;

            $res[$i]['doanh_thu_moi_gioi'] = round($res[$i]['doanh_thu_moi_gioi'] / 1000, 1);
            $res[$i]['doanh_thu_cho_vay'] = round($res[$i]['doanh_thu_cho_vay'] / 1000, 1);
            $res[$i]['doanh_thu_bao_lanh'] = round($res[$i]['doanh_thu_bao_lanh'] / 1000, 1);
            $res[$i]['doanh_thu_tu_doanh'] = round($res[$i]['doanh_thu_tu_doanh'] / 1000, 1);

            $res[$i]['bien_moi_gioi'] = round($res[$i]['bien_moi_gioi'] * 100, 1);
            $res[$i]['bien_cho_vay'] = round($res[$i]['bien_cho_vay'] * 100, 1);
            $res[$i]['bien_bao_lanh'] = round($res[$i]['bien_bao_lanh'] * 100, 1);
            $res[$i]['bien_tu_doanh'] = round($res[$i]['bien_tu_doanh'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res) - 3);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Biên lợi nhuận TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",

            ],
            // "toolbox" => [
            //     "show" => true,
            //     "feature" => [
            //         "dataView" => [
            //             "readOnly" => true,
            //             "title" => "Bảng dữ liệu",
            //             "lang" => ['Bảng dữ liệu', 'Đóng', 'Tải lại']
            //         ],
            //         "magicType" => [
            //             "type" => ['line', 'bar', 'stack'],
            //             "title" => [
            //                 "line" => "Biểu đồ đường",
            //                 "bar" => "Biểu đồ cột",
            //                 "stack" => "Biểu đồ chồng"
            //             ]
            //         ],
            //         "restore" => [
            //             "title" => "Khôi phục"
            //         ],
            //     ]
            // ],
            "legend" => [
                "data" => [
                    "Doanh thu môi giới (TTM)",
                    "Doanh thu cho vay (TTM)",
                    "Doanh thu bảo lãnh (TTM)",
                    "Doanh thu tự doanh (TTM)",
                    "Biên lợi nhuận từ hoạt động môi giới",
                    "Biên lợi nhuận từ hoạt động cho vay",
                    "Biên lợi nhuận từ hoạt động bảo lãnh",
                    "Biên lợi nhuận từ hoạt động tự doanh",
                ],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Doanh thu môi giới (TTM)",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Doanh thu cho vay (TTM)",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Doanh thu bảo lãnh (TTM)",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Doanh thu tự doanh (TTM)",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động môi giới",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động cho vay",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[10],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động bảo lãnh",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động tự doanh",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function bienLaiQuyStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock as is')
            ->where("is.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->addSelect(DB::raw('is.thoigian'))

            ->addSelect(DB::raw('is.doanh_thu_moi_gioi_chung_khoan as doanh_thu_moi_gioi'))
            ->addSelect(DB::raw('is.lai_tu_cac_khoan_cho_vay_va_phai_thu as doanh_thu_cho_vay'))
            ->addSelect(DB::raw('is.doanh_thu_bao_lanh_dai_ly_phat_hanh_chung_khoan as doanh_thu_bao_lanh'))
            ->addSelect(DB::raw('is.lai_tu_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lai_tu_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm + is.lai_tu_cac_tai_san_tai_chinh_san_sang_de_ban_afs as doanh_thu_tu_doanh'))
            ->addSelect(DB::raw('is.chi_phi_moi_gioi_chung_khoan as chi_phi_moi_gioi'))
            ->addSelect(DB::raw('is.chi_phi_lai_vay_lo_tu_cac_khoan_cho_vay_va_phai_thu as chi_phi_cho_vay'))
            ->addSelect(DB::raw('is.chi_phi_hoat_dong_bao_lanh_dai_ly_phat_hanh_chung_khoan as chi_phi_bao_lanh'))
            ->addSelect(DB::raw('(is.lo_cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + is.lo_cac_khoan_dau_tu_nam_giu_den_ngay_dao_han_htm_ + is.lo_ban_cac_tai_san_tai_chinh_san_sang_de_ban_afs+ is.chi_phi_hoat_dong_tu_doanh) as chi_phi_tu_doanh'))

            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['bien_moi_gioi'] = $res[$i]['doanh_thu_moi_gioi'] != 0 ? ($res[$i]['doanh_thu_moi_gioi'] - $res[$i]['chi_phi_moi_gioi']) / $res[$i]['doanh_thu_moi_gioi'] : 0;
            $res[$i]['bien_cho_vay'] = $res[$i]['doanh_thu_cho_vay'] != 0 ? ($res[$i]['doanh_thu_cho_vay'] - $res[$i]['chi_phi_cho_vay']) / $res[$i]['doanh_thu_cho_vay'] : 0;
            $res[$i]['bien_bao_lanh'] = $res[$i]['doanh_thu_bao_lanh'] != 0 ? ($res[$i]['doanh_thu_bao_lanh'] - $res[$i]['chi_phi_bao_lanh']) / $res[$i]['doanh_thu_bao_lanh'] : 0;
            $res[$i]['bien_tu_doanh'] = $res[$i]['doanh_thu_tu_doanh'] != 0 ? ($res[$i]['doanh_thu_tu_doanh'] - $res[$i]['chi_phi_tu_doanh']) / $res[$i]['doanh_thu_tu_doanh'] : 0;

            $res[$i]['doanh_thu_moi_gioi'] = round($res[$i]['doanh_thu_moi_gioi'] / 1000, 1);
            $res[$i]['doanh_thu_cho_vay'] = round($res[$i]['doanh_thu_cho_vay'] / 1000, 1);
            $res[$i]['doanh_thu_bao_lanh'] = round($res[$i]['doanh_thu_bao_lanh'] / 1000, 1);
            $res[$i]['doanh_thu_tu_doanh'] = round($res[$i]['doanh_thu_tu_doanh'] / 1000, 1);

            $res[$i]['bien_moi_gioi'] = round($res[$i]['bien_moi_gioi'] * 100, 1);
            $res[$i]['bien_cho_vay'] = round($res[$i]['bien_cho_vay'] * 100, 1);
            $res[$i]['bien_bao_lanh'] = round($res[$i]['bien_bao_lanh'] * 100, 1);
            $res[$i]['bien_tu_doanh'] = round($res[$i]['bien_tu_doanh'] * 100, 1);
        }
        $res = array_slice($res, 0, count($res));
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Biên lợi nhuận quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",

            ],
            // "toolbox" => [
            //     "show" => true,
            //     "feature" => [
            //         "dataView" => [
            //             "readOnly" => true,
            //             "title" => "Bảng dữ liệu",
            //             "lang" => ['Bảng dữ liệu', 'Đóng', 'Tải lại']
            //         ],
            //         "magicType" => [
            //             "type" => ['line', 'bar', 'stack'],
            //             "title" => [
            //                 "line" => "Biểu đồ đường",
            //                 "bar" => "Biểu đồ cột",
            //                 "stack" => "Biểu đồ chồng"
            //             ]
            //         ],
            //         "restore" => [
            //             "title" => "Khôi phục"
            //         ],
            //     ]
            // ],
            "legend" => [
                "data" => [
                    "Doanh thu môi giới",
                    "Doanh thu cho vay",
                    "Doanh thu bảo lãnh",
                    "Doanh thu tự doanh",
                    "Biên lợi nhuận từ hoạt động môi giới",
                    "Biên lợi nhuận từ hoạt động cho vay",
                    "Biên lợi nhuận từ hoạt động bảo lãnh",
                    "Biên lợi nhuận từ hoạt động tự doanh",
                ],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Tỷ VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Doanh thu môi giới",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Doanh thu cho vay",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Doanh thu bảo lãnh",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Doanh thu tự doanh",
                    "type" => "bar",
                    "stack" => "total",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động môi giới",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động cho vay",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[10],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động bảo lãnh",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
                [
                    "name" => "Biên lợi nhuận từ hoạt động tự doanh",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function bienLaiQuyInsurance(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_insurance as is')
            ->where("is.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->addSelect(DB::raw('is.thoigian'))

            ->addSelect(DB::raw('loi_nhuan_hoat_dong_tai_chinh/doanh_thu_hoat_dong_tai_chinh as bien_loi_nhuan_hd_tai_chinh'))
            ->addSelect(DB::raw('tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep/doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_truoc_thue'))
            ->addSelect(DB::raw('loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem as bien_loi_nhuan_rong'))

            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['bien_loi_nhuan_hd_tai_chinh'] = round($res[$i]['bien_loi_nhuan_hd_tai_chinh'] * 100, 1);
            $res[$i]['bien_loi_nhuan_truoc_thue'] = round($res[$i]['bien_loi_nhuan_truoc_thue'] * 100, 1);
            $res[$i]['bien_loi_nhuan_rong'] = round($res[$i]['bien_loi_nhuan_rong'] * 100, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Biên lợi nhuận quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",

            ],
            "legend" => [
                "data" => [
                    "Biên lợi nhuận HĐ tài chính",
                    "Biên lợi nhuận trước thuế",
                    "Biên lợi nhuận ròng",
                ],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%",
                "right" => "5%"
            ],
            "series" => [
                [
                    "name" => "Biên lợi nhuận HĐ tài chính",
                    "type" => "line",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Biên lợi nhuận trước thuế",
                    "type" => "line",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Biên lợi nhuận ròng",
                    "type" => "line",
                    "data" => $arr[3],
                ]
            ],
        ];
    }

    public function duPhongTTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_stock as bs')
            ->where("bs.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs.thoigian,3),substr(bs.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->addSelect(DB::raw('bs.thoigian'))
            ->addSelect(DB::raw('(bs.du_phong_suy_giam_gia_tri_cac_tai_san_tai_chinh/(bs.cac_tai_san_tai_chinh_ghi_nhan_thong_qua_lai_lo_fvtpl + bs.cac_khoan_dau_tu_giu_den_ngay_dao_han_htm + bs.cac_tai_san_tai_chinh_san_sang_de_ban_afs + bs.cac_khoan_cho_vay)) as du_phong_tsct'))
            ->addSelect(DB::raw('bs.du_phong_suy_giam_gia_tri_cac_khoan_phai_thu/bs.cac_khoan_phai_thu as du_phong_kpt'))
            ->addSelect(DB::raw('bs.cac_khoan_cho_vay/bs.von_chu_so_huu as margin_vcsh'))

            ->get();
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < count($res); $i++) {

            $res[$i]['du_phong_tsct'] = round(abs($res[$i]['du_phong_tsct']) * 100, 1);
            $res[$i]['du_phong_kpt'] = round(abs($res[$i]['du_phong_kpt']) * 100, 1);
            $res[$i]['margin_vcsh'] = round($res[$i]['margin_vcsh'] * 100, 1);
        }
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Dự phòng TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",

            ],
            "legend" => [
                "data" => [
                    "% Dự phòng TSCT",
                    "% Dự phòng KPT",
                    "Tỷ lệ Dự nợ Margin/VCSH",
                ],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "% Dự phòng TSCT",
                    "type" => "line",
                    "data" => $arr[1],
                ],
                [
                    "name" => "% Dự phòng KPT",
                    "type" => "line",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tỷ lệ Dự nợ Margin/VCSH",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[3],
                ]
            ],
        ];
    }

    public function hieuSuatHoatDongTTMNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->join('bs_quarter_nonbank', function ($join) {
                $join->on('bs_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('bs_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->where("is_quarter_nonbank.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is_quarter_nonbank.thoigian'))
            ->addSelect(DB::raw('is_quarter_nonbank.doanh_thu_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_nonbank.gia_von_ban_hang as gia_von_ban_hang'))
            ->addSelect(DB::raw('bs_quarter_nonbank.tong_hang_ton_kho as tong_hang_ton_kho'))
            ->addSelect(DB::raw('bs_quarter_nonbank.cac_khoan_phai_thu_ngan_han + bs_quarter_nonbank.cac_khoan_phai_thu_dai_han as phai_thu'))
            ->addSelect(DB::raw('bs_quarter_nonbank.phai_tra_nguoi_ban_ngan_han + bs_quarter_nonbank.phai_tra_nguoi_ban_dai_han as phai_tra'))
            ->addSelect(DB::raw('0 as vong_quay_hang_ton_kho'))
            ->addSelect(DB::raw('0 as so_ngay_ton_kho'))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
            ->addSelect(DB::raw('0 as so_ngay_phai_thu'))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
            ->addSelect(DB::raw('0 as so_ngay_phai_tra'))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['gia_von_ban_hang'] = $res[$i]['gia_von_ban_hang'] + $res[$i + 1]['gia_von_ban_hang'] + $res[$i + 2]['gia_von_ban_hang'] + $res[$i + 3]['gia_von_ban_hang'];
            // }
            // $res[0]['vong_quay_hang_ton_kho'] = round(($res[0]['gia_von_ban_hang'] * 2 / ($res[0]['tong_hang_ton_kho'] + $res[4]['tong_hang_ton_kho'])), 1);
            // $res[0]['vong_quay_khoan_phai_thu'] = round(($res[0]['doanh_thu_thuan'] * 2 / ($res[0]['phai_thu'] + $res[4]['phai_thu'])), 1);
            // $res[0]['vong_quay_khoan_phai_tra'] = round((($res[0]['gia_von_ban_hang'] + $res[0]['tong_hang_ton_kho'] - $res[4]['tong_hang_ton_kho']) * 2 / ($res[0]['phai_tra'] + $res[4]['phai_tra'])), 1);
            // $res[0]['so_ngay_ton_kho'] = round((365 / $res[0]['vong_quay_hang_ton_kho']), 1);
            // $res[0]['so_ngay_phai_thu'] = round((365 / $res[0]['vong_quay_khoan_phai_thu']), 1);
            // $res[0]['so_ngay_phai_tra'] = round((365 / $res[0]['vong_quay_khoan_phai_tra']), 1);

            // for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['vong_quay_hang_ton_kho'] = ($res[$i]['tong_hang_ton_kho'] + $res[$i + 4]['tong_hang_ton_kho']) != 0 ? ($res[$i]['gia_von_ban_hang'] * 2 / ($res[$i]['tong_hang_ton_kho'] + $res[$i + 4]['tong_hang_ton_kho'])) : 0;
            $res[$i]['vong_quay_khoan_phai_thu'] = ($res[$i]['phai_thu'] + $res[$i + 4]['phai_thu']) != 0 ? ($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['phai_thu'] + $res[$i + 4]['phai_thu'])) : 0;
            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['phai_tra'] + $res[$i + 4]['phai_tra']) != 0 ? (($res[$i]['gia_von_ban_hang'] + $res[$i]['tong_hang_ton_kho'] - $res[$i + 4]['tong_hang_ton_kho']) * 2 / ($res[$i]['phai_tra'] + $res[$i + 4]['phai_tra'])) : 0;
            $res[$i]['so_ngay_ton_kho'] = $res[$i]['vong_quay_hang_ton_kho'] != 0 ? round((365 / $res[$i]['vong_quay_hang_ton_kho']), 1) : 0;
            $res[$i]['so_ngay_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? round((365 / $res[$i]['vong_quay_khoan_phai_thu']), 1) : 0;
            $res[$i]['so_ngay_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? round((365 / $res[$i]['vong_quay_khoan_phai_tra']), 1) : 0;
            $res[$i]['vong_quay_hang_ton_kho'] = round($res[$i]['vong_quay_hang_ton_kho'], 1);
            $res[$i]['vong_quay_khoan_phai_thu'] = round($res[$i]['vong_quay_khoan_phai_thu'], 1);
            $res[$i]['vong_quay_khoan_phai_tra'] = round($res[$i]['vong_quay_khoan_phai_tra'], 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Hiệu suất hoạt động - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} %<br/>{a1}: {c1} lần<br/>{a2}: {c2}%<br/>{a3}: {c3} lần<br/>{a4}: {c4} %<br/>{a5}: {c5} lần'

            ],

            "legend" => [
                "data" => ["Vòng quay phải trả", "Số ngày phải trả", "Vòng quay phải thu", "Số ngày phải thu", "Vòng quay hàng tồn kho", "Số ngày tồn kho"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Lần",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Ngày",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Vòng quay hàng tồn kho",
                    "type" => "line",
                    "data" => $arr[6],
                ],
                [
                    "name" => "Số ngày tồn kho",
                    "type" => "bar",
                    "yAxisIndex" => 1,
                    "data" => $arr[7],
                ],
                [
                    "name" => "Vòng quay phải thu",
                    "type" => "line",
                    "data" => $arr[8],
                ],
                [
                    "name" => "Số ngày phải thu",
                    "type" => "bar",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ],
                [
                    "name" => "Vòng quay phải trả",
                    "type" => "line",
                    "data" => $arr[10],
                ],
                [
                    "name" => "Số ngày phải trả",
                    "type" => "bar",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
            ],
        ];
    }

    public function hieuSuatHoatDongTTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock')
            ->join('bs_quarter_stock', function ($join) {
                $join->on('bs_quarter_stock.mack', '=', 'is_quarter_stock.mack')
                    ->on('bs_quarter_stock.thoigian', '=', 'is_quarter_stock.thoigian');
            })
            ->where("is_quarter_stock.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_stock.thoigian,3),substr(is_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is_quarter_stock.thoigian'))
            ->addSelect(DB::raw('is_quarter_stock.cong_doanh_thu_hoat_dong as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_stock.cong_chi_phi_hoat_dong as chi_phi_hoat_dong'))
            ->addSelect(DB::raw('bs_quarter_stock.cac_khoan_phai_thu + bs_quarter_stock.thue_gia_tri_gia_tang_duoc_khau_tru + bs_quarter_stock.phai_thu_cac_dich_vu_ctck_cung_cap + bs_quarter_stock.phai_thu_noi_bo + bs_quarter_stock.phai_thu_ve_loi_giao_dich_chung_khoan + bs_quarter_stock.cac_khoan_phai_thu_khac + bs_quarter_stock.du_phong_suy_giam_gia_tri_cac_khoan_phai_thu as phai_thu'))
            ->addSelect(DB::raw('bs_quarter_stock.phai_tra_nguoi_ban_ngan_han + bs_quarter_stock.phai_tra_nguoi_ban_dai_han as phai_tra'))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_thu'))
            ->addSelect(DB::raw('0 as so_ngay_phai_thu'))
            ->addSelect(DB::raw('0 as vong_quay_khoan_phai_tra'))
            ->addSelect(DB::raw('0 as so_ngay_phai_tra'))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['chi_phi_hoat_dong'] = $res[$i]['chi_phi_hoat_dong'] + $res[$i + 1]['chi_phi_hoat_dong'] + $res[$i + 2]['chi_phi_hoat_dong'] + $res[$i + 3]['chi_phi_hoat_dong'];
        }
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['vong_quay_khoan_phai_thu'] = ($res[$i]['phai_thu'] + $res[$i + 4]['phai_thu']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['phai_thu'] + $res[$i + 4]['phai_thu'])), 1) : 0;
            $res[$i]['vong_quay_khoan_phai_tra'] = ($res[$i]['phai_tra'] + $res[$i + 4]['phai_tra']) != 0 ? round(($res[$i]['chi_phi_hoat_dong'] * 2 / ($res[$i]['phai_tra'] + $res[$i + 4]['phai_tra'])), 1) : 0;
            $res[$i]['so_ngay_phai_thu'] = $res[$i]['vong_quay_khoan_phai_thu'] != 0 ? round((365 / $res[$i]['vong_quay_khoan_phai_thu']), 1) : 0;
            $res[$i]['so_ngay_phai_tra'] = $res[$i]['vong_quay_khoan_phai_tra'] != 0 ? round((365 / $res[$i]['vong_quay_khoan_phai_tra']), 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Hiệu suất hoạt động - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} %<br/>{a1}: {c1} lần<br/>{a2}: {c2}%<br/>{a3}: {c3} lần<br/>{a4}: {c4} %<br/>{a5}: {c5} lần'

            ],

            "legend" => [
                "data" => ["Vòng quay các khoản phải trả", "Số ngày phải trả", "Vòng quay các khoản phải thu", "Số ngày phải thu"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Lần",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "Vòng quay các khoản phải thu",
                    "type" => "line",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Số ngày phải thu",
                    "type" => "bar",
                    "yAxisIndex" => 1,
                    "data" => $arr[6],
                ],
                [
                    "name" => "Vòng quay các khoản phải trả",
                    "type" => "line",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Số ngày phải trả",
                    "type" => "bar",
                    "yAxisIndex" => 1,
                    "data" => $arr[8],
                ],
            ],
        ];
    }

    public function phanRaROATTMNonbank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->join('bs_quarter_nonbank', function ($join) {
                $join->on('bs_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('bs_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->where("is_quarter_nonbank.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is_quarter_nonbank.thoigian'))
            ->addSelect(DB::raw('is_quarter_nonbank.doanh_thu_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_nonbank.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as lnst'))
            ->addSelect(DB::raw('bs_quarter_nonbank.tong_cong_nguon_von as tong_cong_nguon_von'))
            ->addSelect(DB::raw('0 as roa_ttm'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('0 as ty_suat_loi_nhuan'))
            ->get();
        // ->toSql();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['lnst'] = $res[$i]['lnst'] + $res[$i + 1]['lnst'] + $res[$i + 2]['lnst'] + $res[$i + 3]['lnst'];
            $res[$i]['roa_ttm'] = ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von']) != 0 ? round(($res[$i]['lnst'] * 2 / ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von'])) * 100, 1) : 0;
            $res[$i]['vong_quay_tai_san'] = ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von'])) * 100, 1) : 0;
            $res[$i]['ty_suat_loi_nhuan'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['lnst'] / $res[$i]['doanh_thu_thuan']) * 100, 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Phân rã ROA - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b> %'
            ],

            "legend" => [
                "data" => ["Vòng quay tài sản", "Tỷ suất lợi nhuận", "ROA TTM"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "ROA TTM",
                    "type" => "bar",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Vòng quay tài sản",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[5],
                ],
                [
                    "name" => "Tỷ suất lợi nhuận",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[6],
                ],
            ],
        ];
    }

    public function phanRaROATTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock')
            ->join('bs_quarter_stock', function ($join) {
                $join->on('bs_quarter_stock.mack', '=', 'is_quarter_stock.mack')
                    ->on('bs_quarter_stock.thoigian', '=', 'is_quarter_stock.thoigian');
            })
            ->where("is_quarter_stock.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_stock.thoigian,3),substr(is_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is_quarter_stock.thoigian'))
            ->addSelect(DB::raw('is_quarter_stock.cong_doanh_thu_hoat_dong as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_stock.loi_nhuan_ke_toan_sau_thue_tndn as lnst'))
            ->addSelect(DB::raw('bs_quarter_stock.tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('0 as roa_ttm'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('0 as ty_suat_loi_nhuan'))
            ->get();
        // ->toSql();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['lnst'] = $res[$i]['lnst'] + $res[$i + 1]['lnst'] + $res[$i + 2]['lnst'] + $res[$i + 3]['lnst'];
        }
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['roa_ttm'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san']) != 0 ? round(($res[$i]['lnst'] * 2 / ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san'])) * 100, 1) : 0;
            $res[$i]['vong_quay_tai_san'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san'])) * 100, 1) : 0;
            $res[$i]['ty_suat_loi_nhuan'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['lnst'] / $res[$i]['doanh_thu_thuan']) * 100, 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Phân rã ROA - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b>%'

            ],

            "legend" => [
                "data" => ["Vòng quay tài sản", "Tỷ suất lợi nhuận", "ROA TTM"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "ROA TTM",
                    "type" => "bar",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Vòng quay tài sản",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[5],
                ],
                [
                    "name" => "Tỷ suất lợi nhuận",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[6],
                ],
            ],
        ];
    }

    public function phanRaROATTMInsurance(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_insurance as is')
            ->join('bs_quarter_insurance as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as doanh_thu_thuan'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as lnst'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['lnst'] = $res[$i]['lnst'] + $res[$i + 1]['lnst'] + $res[$i + 2]['lnst'] + $res[$i + 3]['lnst'];

            $res[$i]['roa_ttm'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 1]['tong_cong_tai_san']) != 0 ? round(($res[$i]['lnst'] * 2 / ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san'])) * 100, 1) : 0;
            $res[$i]['vong_quay_tai_san'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 1]['tong_cong_tai_san']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san'])) * 100, 1) : 0;
            $res[$i]['ty_suat_loi_nhuan'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['lnst'] / $res[$i]['doanh_thu_thuan']) * 100, 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Phân rã ROA - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],

            "legend" => [
                "data" => ["Vòng quay tài sản", "Tỷ suất lợi nhuận", "ROA TTM"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "30%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "ROA TTM",
                    "type" => "bar",
                    "data" => $arr[4],
                ],
                [
                    "name" => "Vòng quay tài sản",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[5],
                ],
                [
                    "name" => "Tỷ suất lợi nhuận",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[6],
                ],
            ],
        ];
    }

    public function phanRaROETTMNonbank(Request $req)
    {
        $limit = 11;
        $res = DB::table('is_quarter_nonbank')
            ->join('bs_quarter_nonbank', function ($join) {
                $join->on('bs_quarter_nonbank.mack', '=', 'is_quarter_nonbank.mack')
                    ->on('bs_quarter_nonbank.thoigian', '=', 'is_quarter_nonbank.thoigian');
            })
            ->where("is_quarter_nonbank.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_nonbank.thoigian,3),substr(is_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is_quarter_nonbank.thoigian'))
            ->addSelect(DB::raw('is_quarter_nonbank.doanh_thu_thuan as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_nonbank.chi_phi_lai_vay + is_quarter_nonbank.tong_loi_nhuan_ke_toan_truoc_thue as ebit'))
            ->addSelect(DB::raw('is_quarter_nonbank.tong_loi_nhuan_ke_toan_truoc_thue as ebt'))
            ->addSelect(DB::raw('is_quarter_nonbank.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me as lnst'))
            ->addSelect(DB::raw('bs_quarter_nonbank.tong_cong_nguon_von as tong_cong_nguon_von'))
            ->addSelect(DB::raw('bs_quarter_nonbank.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('0 as roe_ttm'))
            ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('0 as bien_ebit'))
            ->addSelect(DB::raw('0 as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('0 as ganh_nang_thue'))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['ebit'] = $res[$i]['ebit'] + $res[$i + 1]['ebit'] + $res[$i + 2]['ebit'] + $res[$i + 3]['ebit'];
            $res[$i]['ebt'] = $res[$i]['ebt'] + $res[$i + 1]['ebt'] + $res[$i + 2]['ebt'] + $res[$i + 3]['ebt'];
            $res[$i]['lnst'] = $res[$i]['lnst'] + $res[$i + 1]['lnst'] + $res[$i + 2]['lnst'] + $res[$i + 3]['lnst'];

            $res[$i]['roe_ttm'] = ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu']) != 0 ? round(($res[$i]['lnst'] * 2 / ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu'])) * 100, 1) : 0;
            $res[$i]['don_bay_tai_chinh'] = ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu']) != 0 ? round((($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von']) / ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu'])) * 100, 1) : 0;
            $res[$i]['vong_quay_tai_san'] = ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von'])) * 100, 1) : 0;
            $res[$i]['bien_ebit'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['ebit'] / $res[$i]['doanh_thu_thuan']) * 100, 1) : 0;
            $res[$i]['ganh_nang_lai_suat'] = $res[$i]['ebit'] != 0 ? round(($res[$i]['ebt'] / $res[$i]['ebit']) * 100, 1) : 0;
            $res[$i]['ganh_nang_thue'] = $res[$i]['ebt'] != 0 ? round(($res[$i]['lnst'] / $res[$i]['ebt']) * 100, 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Phân rã ROE - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b>%<br/>{a3}: <b>{c3}</b> %<br/>{a4}: <b>{c4}</b> %<br/>{a5}: <b>{c5}</b>%'
            ],

            "legend" => [
                "data" => ["ROE TTM", "Đòn bẩy tài chính", "Vòng quay tài sản", "Biên EBIT", "Gánh nặng lãi suất", "Gánh nặng thuế"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "ROE TTM",
                    "type" => "bar",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Đòn bẩy tài chính",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[8],
                ],
                [
                    "name" => "Vòng quay tài sản",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ],
                [
                    "name" => "Biên EBIT",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[10],
                ],
                [
                    "name" => "Gánh nặng lãi suất",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
                [
                    "name" => "Gánh nặng thuế",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function phanRaROETTMStock(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_stock')
            ->join('bs_quarter_stock', function ($join) {
                $join->on('bs_quarter_stock.mack', '=', 'is_quarter_stock.mack')
                    ->on('bs_quarter_stock.thoigian', '=', 'is_quarter_stock.thoigian');
            })
            ->where("is_quarter_stock.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is_quarter_stock.thoigian,3),substr(is_quarter_stock.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is_quarter_stock.thoigian'))
            ->addSelect(DB::raw('is_quarter_stock.cong_doanh_thu_hoat_dong as doanh_thu_thuan'))
            ->addSelect(DB::raw('is_quarter_stock.tong_loi_nhuan_ke_toan_truoc_thue + is_quarter_stock.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is_quarter_stock.tong_loi_nhuan_ke_toan_truoc_thue as ebt'))
            ->addSelect(DB::raw('is_quarter_stock.loi_nhuan_ke_toan_sau_thue_tndn as lnst'))
            ->addSelect(DB::raw('bs_quarter_stock.tong_cong_tai_san as tong_cong_nguon_von'))
            ->addSelect(DB::raw('bs_quarter_stock.von_chu_so_huu as von_chu_so_huu'))
            ->addSelect(DB::raw('0 as roe_ttm'))
            ->addSelect(DB::raw('0 as don_bay_tai_chinh'))
            ->addSelect(DB::raw('0 as vong_quay_tai_san'))
            ->addSelect(DB::raw('0 as bien_ebit'))
            ->addSelect(DB::raw('0 as ganh_nang_lai_suat'))
            ->addSelect(DB::raw('0 as ganh_nang_thue'))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['ebit'] = $res[$i]['ebit'] + $res[$i + 1]['ebit'] + $res[$i + 2]['ebit'] + $res[$i + 3]['ebit'];
            $res[$i]['ebt'] = $res[$i]['ebt'] + $res[$i + 1]['ebt'] + $res[$i + 2]['ebt'] + $res[$i + 3]['ebt'];
            $res[$i]['lnst'] = $res[$i]['lnst'] + $res[$i + 1]['lnst'] + $res[$i + 2]['lnst'] + $res[$i + 3]['lnst'];
        }
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['roe_ttm'] = ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu']) != 0 ? round(($res[$i]['lnst'] * 2 / ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu'])) * 100, 1) : 0;
            $res[$i]['don_bay_tai_chinh'] = ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu']) != 0 ? round((($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von']) / ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu'])) * 100, 1) : 0;
            $res[$i]['vong_quay_tai_san'] = ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['tong_cong_nguon_von'] + $res[$i + 4]['tong_cong_nguon_von'])) * 100, 1) : 0;
            $res[$i]['bien_ebit'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['ebit'] / $res[$i]['doanh_thu_thuan']) * 100, 1) : 0;
            $res[$i]['ganh_nang_lai_suat'] = $res[$i]['ebit'] != 0 ? round(($res[$i]['ebt'] / $res[$i]['ebit']) * 100, 1) : 0;
            $res[$i]['ganh_nang_thue'] = $res[$i]['ebt'] != 0 ? round(($res[$i]['lnst'] / $res[$i]['ebt']) * 100, 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Phân rã ROE - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b>%<br/>{a3}: <b>{c3}</b> %<br/>{a4}: <b>{c4}</b> %<br/>{a5}: <b>{c5}</b>%'
            ],

            "legend" => [
                "data" => ["ROE TTM", "Đòn bẩy tài chính", "Vòng quay tài sản", "Biên EBIT", "Gánh nặng lãi suất", "Gánh nặng thuế"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "ROE TTM",
                    "type" => "bar",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Đòn bẩy tài chính",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[8],
                ],
                [
                    "name" => "Vòng quay tài sản",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ],
                [
                    "name" => "Biên EBIT",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[10],
                ],
                [
                    "name" => "Gánh nặng lãi suất",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
                [
                    "name" => "Gánh nặng thuế",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function phanRaROETTMInsurance(Request $req)
    {
        $is_type_direct_insurance = $req->input('is_type_direct_insurance');
        if ($is_type_direct_insurance)
            return [
                "title" => [
                    "text" => "Phân rã ROE - TTM",
                    "left" => "center",
                    "align" => "auto",
                    "top" => "2%",
                    "textStyle" => [
                        "fontFamily" => "Tahoma"
                    ]
                ],
                "tooltip" => [
                    "trigger" => "axis",
                    "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b>%<br/>{a3}: <b>{c3}</b> %<br/>{a4}: <b>{c4}</b> %<br/>{a5}: <b>{c5}</b>%'
                ],
                "series" => [],
            ];
        $limit = 11;
        $res = DB::table('is_quarter_insurance as is')
            ->join('bs_quarter_insurance as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->join('cf_quarter_insurance as cf', function ($join) {
                $join->on('cf.mack', '=', 'is.mack')
                    ->on('cf.thoigian', '=', 'is.thoigian');
            })
            ->where("is.mack", $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.doanh_thu_thuan_hoat_dong_kinh_doanh_bao_hiem + is.doanh_thu_hoat_dong_tai_chinh as doanh_thu_thuan'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me + cf.chi_phi_lai_vay as ebit'))
            ->addSelect(DB::raw('is.tong_loi_nhuan_truoc_thue_thu_nhap_doanh_nghiep as ebt'))
            ->addSelect(DB::raw('is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me as lnst'))
            ->addSelect(DB::raw('bs.tong_cong_tai_san'))
            ->addSelect(DB::raw('bs.von_chu_so_huu+bs.loi_ich_co_dong_thieu_so as von_chu_so_huu'))
            ->get();
        $res = json_decode(json_encode($res), true);
        $count_res = count($res);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['doanh_thu_thuan'] = $res[$i]['doanh_thu_thuan'] + $res[$i + 1]['doanh_thu_thuan'] + $res[$i + 2]['doanh_thu_thuan'] + $res[$i + 3]['doanh_thu_thuan'];
            $res[$i]['ebit'] = $res[$i]['ebit'] + $res[$i + 1]['ebit'] + $res[$i + 2]['ebit'] + $res[$i + 3]['ebit'];
            $res[$i]['ebt'] = $res[$i]['ebt'] + $res[$i + 1]['ebt'] + $res[$i + 2]['ebt'] + $res[$i + 3]['ebt'];
            $res[$i]['lnst'] = $res[$i]['lnst'] + $res[$i + 1]['lnst'] + $res[$i + 2]['lnst'] + $res[$i + 3]['lnst'];

            $res[$i]['roe_ttm'] = ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu']) != 0 ? round(($res[$i]['lnst'] * 2 / ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu'])) * 100, 1) : 0;
            $res[$i]['don_bay_tai_chinh'] = ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu']) != 0 ? round((($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san']) / ($res[$i]['von_chu_so_huu'] + $res[$i + 4]['von_chu_so_huu'])) * 100, 1) : 0;
            $res[$i]['vong_quay_tai_san'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san']) != 0 ? round(($res[$i]['doanh_thu_thuan'] * 2 / ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san'])) * 100, 1) : 0;
            $res[$i]['bien_ebit'] = $res[$i]['doanh_thu_thuan'] != 0 ? round(($res[$i]['ebit'] / $res[$i]['doanh_thu_thuan']) * 100, 1) : 0;
            $res[$i]['ganh_nang_lai_suat'] = $res[$i]['ebit'] != 0 ? round(($res[$i]['ebt'] / $res[$i]['ebit']) * 100, 1) : 0;
            $res[$i]['ganh_nang_thue'] = $res[$i]['ebt'] != 0 ? round(($res[$i]['lnst'] / $res[$i]['ebt']) * 100, 1) : 0;
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        if (!$res) {
            return [];
        }
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Phân rã ROE - TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => '<b>{b0}</b><br/>{a0}: <b>{c0}</b> %<br/>{a1}: <b>{c1}</b> %<br/>{a2}: <b>{c2}</b>%<br/>{a3}: <b>{c3}</b> %<br/>{a4}: <b>{c4}</b> %<br/>{a5}: <b>{c5}</b>%'
            ],

            "legend" => [
                "data" => ["ROE TTM", "Đòn bẩy tài chính", "Vòng quay tài sản", "Biên EBIT", "Gánh nặng lãi suất", "Gánh nặng thuế"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%"
            ],
            "series" => [
                [
                    "name" => "ROE TTM",
                    "type" => "bar",
                    "data" => $arr[7],
                ],
                [
                    "name" => "Đòn bẩy tài chính",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[8],
                ],
                [
                    "name" => "Vòng quay tài sản",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[9],
                ],
                [
                    "name" => "Biên EBIT",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[10],
                ],
                [
                    "name" => "Gánh nặng lãi suất",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[11],
                ],
                [
                    "name" => "Gánh nặng thuế",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[12],
                ],
            ],
        ];
    }

    public function fourmCanslimPointChart(Request $request)
    {
        $mack = $request->input('mack');
        $limit = 9;
        $type = DB::table('danh_sach_mack')
            ->select("nhom")
            ->where('mack', '=', $request->input('mack'))
            ->first();
        $type = $type->nhom;
        $fourm = DB::table('fourm')
            ->where('mack', '=', $mack)
            ->where('is_last', true)
            ->first();
        $fourm_point = $fourm ? round($fourm->tong_diem, 2) : "N/A";
        $canslim = DB::table('canslim')
            ->where('mack', '=', $mack)
            ->where('is_last', true)
            ->first();
        $canslim_point = $canslim ? round($canslim->tong_diem, 2) : "N/A";
        $indicator = [];
        switch ($type) {
            case "nonbank":
                $indicator = [
                    ["name" => "%G_Sales", "max" => 100],
                    ["name" => "ROIC", "max" => 100],
                    ["name" => "ROE", "max" => 100],
                    ["name" => "ROA", "max" => 100],
                    ["name" => "OCF/LNST", "max" => 100],
                    ["name" => "LNST/Sale", "max" => 100],
                    ["name" => "Sale/TTS", "max" => 100],
                    ["name" => "NDH", "max" => 100],
                    ["name" => "%G_OCF", "max" => 100],
                    ["name" => "%G_BVPS", "max" => 100],
                    ["name" => "%G_EPS", "max" => 100]
                ];
                break;
            case "bank":
                $indicator = [
                    ["name" => "CIR", "max" => 100],
                    ["name" => "ROAA", "max" => 100],
                    ["name" => "ROEA", "max" => 100],
                    ["name" => "NIM", "max" => 100],
                    ["name" => "Lãi đầu tư", "max" => 100],
                    ["name" => "Lãi thu phí", "max" => 100],
                    ["name" => "Lãi dự thu", "max" => 100],
                    ["name" => "Dự phòng", "max" => 100],
                    ["name" => "CAR", "max" => 100],
                    ["name" => "NPL", "max" => 100],
                    ["name" => "CASA", "max" => 100]
                ];
                break;
            case "stock":
                $indicator = [
                    ["name" => "%G_TTS", "max" => 100],
                    ["name" => "ROAA", "max" => 100],
                    ["name" => "ROEA", "max" => 100],
                    ["name" => "%DỰ PHÒNG", "max" => 100],
                    ["name" => "%Tự doanh", "max" => 100],
                    ["name" => "%IB", "max" => 100],
                    ["name" => "%MARGIN", "max" => 100],
                    ["name" => "%MG", "max" => 100],
                    ["name" => "CIR", "max" => 100],
                    ["name" => "%G_EPS", "max" => 100],
                    ["name" => "%G_BVPS", "max" => 100]
                ];
                break;
            case "insurance":
                $indicator = [
                    ["name" => "%G_OSales", "max" => 100],
                    ["name" => "DU PHONG", "max" => 100],
                    ["name" => "ROE", "max" => 100],
                    ["name" => "ROA", "max" => 100],
                    ["name" => "OCF/LNST", "max" => 100],
                    ["name" => "LNST/Sale", "max" => 100],
                    ["name" => "Sale/TTS", "max" => 100],
                    ["name" => "%G_OCF", "max" => 100],
                    ["name" => "%G_BVPS", "max" => 100],
                    ["name" => "%G_EPS", "max" => 100],
                    ["name" => "%G_FSales", "max" => 100],
                ];
                break;
            default:
                break;
        }
        $option_radar_fourm = [
            "backgroundColor" => [
                "type" => 'radial',
                "x" => 0.5,
                "y" => 0.5,
                "r" => 0.9,
                "colorStops" => [
                    [
                        "offset" => 0, "color" => '#000'
                    ],
                    [
                        "offset" => 1, "color" => '#BD1D1E'
                    ]
                ],
                "global" => false
            ],
            "tooltip" => [
                "show" => true
            ],
            "radar" => [
                "indicator" => $indicator,
                "center" => ["50%", "48%"],
                "splitNumber" => 9,
                "axisLine" => [
                    "show" => false
                ]
            ],
            "series" => [
                [
                    "name" => "",
                    "type" => "radar",
                    "data" => [
                        []
                    ]
                ],
            ],
        ];
        $option_radar_canslim = [
            "backgroundColor" => [
                "type" => 'radial',
                "x" => 0.5,
                "y" => 0.5,
                "r" => 0.9,
                "colorStops" => [
                    [
                        "offset" => 0, "color" => '#000'
                    ],
                    [
                        "offset" => 1, "color" => '#1E7934'
                    ]
                ],
                "global" => false
            ],
            "tooltip" => [
                "show" => true
            ],
            "radar" => [
                "indicator" => [
                    ["name" => "%G_Sales (C) ", "max" => 100],
                    ["name" => "%G_EPS (TTM-1)", "max" => 100],
                    ["name" => "%G_EPS (TTM)", "max" => 100],
                    ["name" => "%G_EPS (C-1)", "max" => 100],
                    ["name" => "%G_EPS (C) ", "max" => 100],
                    ["name" => "%G_Sales (TTM-1)", "max" => 100],
                    ["name" => "%G_Sales (TTM)", "max" => 100],
                    ["name" => "%G_Sales (C-1)", "max" => 100],
                    ["name" => "%ROE (TTM)", "max" => 100],
                    ["name" => "%ROE (TTM-1)", "max" => 100],
                ],
                "center" => ["50%", "48%"],
                "splitNumber" => 9,
                "axisLine" => [
                    "show" => false
                ]
            ],
            "series" => [
                [
                    "name" => "",
                    "type" => "radar",
                    "data" => [
                        []
                    ],
                    "lineStyle" => [
                        "color" => "#9E561C"
                    ],
                    "itemStyle" => [
                        "color" => "#fff"
                    ]
                ],
            ],
        ];
        if ($fourm) {
            $option_radar_fourm = [
                "backgroundColor" => [
                    "type" => 'radial',
                    "x" => 0.5,
                    "y" => 0.5,
                    "r" => 0.9,
                    "colorStops" => [
                        [
                            "offset" => 0, "color" => '#000'
                        ],
                        [
                            "offset" => 1, "color" => $fourm_point > 50 ? '#1E7934' : '#BD1D1E'
                        ]
                    ],
                    "global" => false
                ],
                "tooltip" => [
                    "show" => true
                ],
                "radar" => [
                    "indicator" => $indicator,
                    "center" => ["50%", "48%"],
                    "splitNumber" => 9,
                    "axisLine" => [
                        "show" => false
                    ]
                ],
                "series" => [
                    [
                        "name" => $fourm->mack,
                        "type" => "radar",
                        "data" => [
                            [
                                round($fourm->sale_tang_truong),
                                round($fourm->roic),
                                round($fourm->roe),
                                round($fourm->roa),
                                round($fourm->lcdtkd_tren_loi_nhuan),
                                round($fourm->loi_nhuan_tren_doanh_thu),
                                round($fourm->doanh_thu_tren_tai_san),
                                round($fourm->no_dai_han_nam_gan_nhat),
                                round($fourm->lcdtkd_tang_truong),
                                round($fourm->bvps_tang_truong),
                                round($fourm->eps_tang_truong),
                            ]
                        ],
                        "lineStyle" => [
                            "color" => "#ff1111"
                        ],
                        "itemStyle" => [
                            "color" => "#fff"
                        ]
                    ],
                ],
            ];
        }
        if ($canslim) {
            $option_radar_canslim = [
                "backgroundColor" => [
                    "type" => 'radial',
                    "x" => 0.5,
                    "y" => 0.5,
                    "r" => 0.9,
                    "colorStops" => [
                        [
                            "offset" => 0, "color" => '#000'
                        ],
                        [
                            "offset" => 1, "color" => $canslim_point > 50 ? '#1E7934' : '#BD1D1E'
                        ]
                    ],
                    "global" => false
                ],
                "tooltip" => [
                    "show" => true
                ],
                "radar" => [
                    "indicator" => [
                        ["name" => "%G_Sales (C) ", "max" => 100],
                        ["name" => "%ROE (TTM-1)", "max" => 100],
                        ["name" => "%ROE (TTM)", "max" => 100],
                        ["name" => "%G_EPS (TTM-1)", "max" => 100],
                        ["name" => "%G_EPS (TTM)", "max" => 100],
                        ["name" => "%G_EPS (C-1)", "max" => 100],
                        ["name" => "%G_EPS (C) ", "max" => 100],
                        ["name" => "%G_Sales (TTM-1)", "max" => 100],
                        ["name" => "%G_Sales (TTM)", "max" => 100],
                        ["name" => "%G_Sales (C-1)", "max" => 100],
                    ],
                    "center" => ["50%", "48%"],
                    "splitNumber" => 9,
                    "axisLine" => [
                        "show" => false
                    ]
                ],
                "series" => [
                    [
                        "name" => $canslim->mack,
                        "type" => "radar",
                        "data" => [
                            [
                                round($canslim->sale_quy_gan_nhat),
                                round($canslim->roe_trailing_12_thang_truoc_do),
                                round($canslim->roe_trailing_12_thang),
                                round($canslim->eps_trailing_12_thang_truoc_do),
                                round($canslim->eps_trailing_12_thang),
                                round($canslim->eps_quy_gan_nhat_truoc_do),
                                round($canslim->eps_quy_gan_nhat),
                                round($canslim->sale_trailing_12_thang_truoc_do),
                                round($canslim->sale_trailing_12_thang),
                                round($canslim->sale_quy_gan_nhat_truoc_do),
                            ]
                        ],
                        "lineStyle" => [
                            "color" => "#9E561C"
                        ],
                        "itemStyle" => [
                            "color" => "#fff"
                        ]
                    ],
                ],
            ];
        }
        $fourm_history = DB::table('fourm')
            ->where('mack', '=', $mack)
            ->addSelect('thoigian')
            ->addSelect('tong_diem')
            ->take($limit)
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->get();
        $arr_quarter_item = [];
        $arr_fourm_point_item = [];
        foreach ($fourm_history as $row) {
            array_unshift($arr_quarter_item, substr($row->thoigian, 0, 2) . "." . substr($row->thoigian, -2));
            array_unshift($arr_fourm_point_item, round($row->tong_diem, 2));
        }
        $fourm_option_history_chart = [
            // "title" => [
            //     "text" => "Điểm Canslim qua các quý",
            //     "left" => "center",
            //     "align" => "auto",
            //     "top" => "2%",
            //     "textStyle" => [
            //         "fontFamily" => "Tahoma"
            //     ]
            // ],
            "tooltip" => [
                "show" => false,
            ],
            "xAxis" => [
                "type" => 'category',
                "data" => $arr_quarter_item,
                "axisLabel" => [
                    "interval" => 0,
                    // "rotate" => 60
                ]
            ],
            "visualMap" => [
                [
                    "show" => false,
                    "seriesIndex" => 0,
                    "pieces" => [
                        ["gte" => 50, "color" => "#00aa00"],
                        ["lt" => 50, "gt" => 0, "color" => "#ee5442"],
                    ],
                ],
            ],
            "grid" => [
                "top" => "20%",
                "bottom" => "15%"
            ],
            "yAxis" => [
                "type" => "value",
                "max" => 100
            ],
            "series" => [
                [
                    "data" => $arr_fourm_point_item,
                    "type" => 'line',
                    "smooth" => true,
                    "label" => [
                        "show" => true,
                        "position" => 'top',
                        // "formatter"=> '{b}: {@score}'
                    ],
                    "markPoint" => [
                        "symbolSize" => [60, 50],
                        "data" => [
                            [
                                "value" => end($arr_fourm_point_item),
                                "yAxis" => end($arr_fourm_point_item),
                                "xAxis" => end($arr_quarter_item)
                            ]
                        ],
                        "label" => [
                            "formatter" => '{c}',
                            "color" => "#fff"
                        ],
                        "symbol" => "pin"
                    ],
                    "itemStyle" => [
                        "color" => $fourm_point > 50 ? '#2eb85c' : '#e55353'
                    ]
                ]
            ]
        ];
        $canslim_history = DB::table('canslim')
            ->where('mack', '=', $mack)
            ->addSelect('thoigian')
            ->addSelect('tong_diem')
            ->take($limit)
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->get();
        $arr_quarter_item = [];
        $arr_canslim_point_item = [];
        foreach ($canslim_history as $row) {
            array_unshift($arr_quarter_item, substr($row->thoigian, 0, 2) . "." . substr($row->thoigian, -2));
            array_unshift($arr_canslim_point_item, round($row->tong_diem, 2));
        }
        $canslim_option_history_chart = [
            // "title" => [
            //     "text" => "Điểm Canslim qua các quý",
            //     "left" => "center",
            //     "align" => "auto",
            //     "top" => "2%",
            //     "textStyle" => [
            //         "fontFamily" => "Tahoma"
            //     ]
            // ],
            "tooltip" => [
                "show" => false,
            ],
            "xAxis" => [
                "type" => 'category',
                "data" => $arr_quarter_item,
                "axisLabel" => [
                    "interval" => 0,
                    // "rotate" => 60
                ]
            ],
            "visualMap" => [
                [
                    "show" => false,
                    "seriesIndex" => 0,
                    "pieces" => [
                        ["gte" => 50, "color" => "#00aa00"],
                        ["lt" => 50, "gt" => 0, "color" => "#ee5442"],
                    ],
                ],
            ],
            "grid" => [
                "top" => "20%",
                "bottom" => "15%"
            ],
            "yAxis" => [
                "type" => "value",
                "max" => 100
            ],
            "series" => [
                [
                    "data" => $arr_canslim_point_item,
                    "type" => 'line',
                    "smooth" => true,
                    "label" => [
                        "show" => true,
                        "position" => 'top',
                        // "formatter"=> '{b}: {@score}'
                    ],
                    "markPoint" => [
                        "symbolSize" => [60, 50],
                        "data" => [
                            [
                                "value" => end($arr_canslim_point_item),
                                "yAxis" => end($arr_canslim_point_item),
                                "xAxis" => end($arr_quarter_item)
                            ],
                        ],
                        "label" => [
                            "formatter" => '{c}',
                            "color" => "#fff"
                        ],
                        "symbol" => "pin",
                    ],
                    "itemStyle" => [
                        "color" => $canslim_point > 50 ? '#2eb85c' : '#e55353'
                    ]
                ]
            ]
        ];
        $res =  [
            "fourm_point" => $fourm_point,
            "canslim_point" => $canslim_point,
            "fourm_option_chart" => $option_radar_fourm,
            "canslim_option_chart" => $option_radar_canslim,
            "fourm_option_history_chart" => $fourm_option_history_chart,
            "canslim_option_history_chart" => $canslim_option_history_chart
        ];
        return response()->json($res);
    }

    public function fourmCanslimPointChartByTime(Request $request)
    {
        $mack = $request->input('mack');
        $type = $request->input('type');
        $time = $request->input('time');
        if ($type == "4m") {
            $fourm = DB::table('fourm')
                ->where('mack', '=', $mack)
                ->where('thoigian', $time)
                ->first();
            $nhom = DB::table('danh_sach_mack')
                ->select("nhom")
                ->where('mack', '=', $request->input('mack'))
                ->first();
            $nhom = $nhom->nhom;
            $fourm_point = $fourm ? round($fourm->tong_diem, 2) : "N/A";
            $indicator = [];
            switch ($nhom) {
                case "nonbank":
                    $indicator = [
                        ["name" => "%G_Sales", "max" => 100],
                        ["name" => "ROIC", "max" => 100],
                        ["name" => "ROE", "max" => 100],
                        ["name" => "ROA", "max" => 100],
                        ["name" => "OCF/LNST", "max" => 100],
                        ["name" => "LNST/Sale", "max" => 100],
                        ["name" => "Sale/TTS", "max" => 100],
                        ["name" => "NDH", "max" => 100],
                        ["name" => "%G_OCF", "max" => 100],
                        ["name" => "%G_BVPS", "max" => 100],
                        ["name" => "%G_EPS", "max" => 100]
                    ];
                    break;
                case "bank":
                    $indicator = [
                        ["name" => "CIR", "max" => 100],
                        ["name" => "ROAA", "max" => 100],
                        ["name" => "ROEA", "max" => 100],
                        ["name" => "NIM", "max" => 100],
                        ["name" => "Lãi đầu tư", "max" => 100],
                        ["name" => "Lãi thu phí", "max" => 100],
                        ["name" => "Lãi dự thu", "max" => 100],
                        ["name" => "Dự phòng", "max" => 100],
                        ["name" => "CAR", "max" => 100],
                        ["name" => "NPL", "max" => 100],
                        ["name" => "CASA", "max" => 100]
                    ];
                    break;
                case "stock":
                    $indicator = [
                        ["name" => "%G_TTS", "max" => 100],
                        ["name" => "ROAA", "max" => 100],
                        ["name" => "ROEA", "max" => 100],
                        ["name" => "%DỰ PHÒNG", "max" => 100],
                        ["name" => "%Tự doanh", "max" => 100],
                        ["name" => "%IB", "max" => 100],
                        ["name" => "%MARGIN", "max" => 100],
                        ["name" => "%MG", "max" => 100],
                        ["name" => "CIR", "max" => 100],
                        ["name" => "%G_EPS", "max" => 100],
                        ["name" => "%G_BVPS", "max" => 100]
                    ];
                    break;
                case "insurance":
                    $indicator = [
                        ["name" => "%G_OSales", "max" => 100],
                        ["name" => "DU PHONG", "max" => 100],
                        ["name" => "ROE", "max" => 100],
                        ["name" => "ROA", "max" => 100],
                        ["name" => "OCF/LNST", "max" => 100],
                        ["name" => "LNST/Sale", "max" => 100],
                        ["name" => "Sale/TTS", "max" => 100],
                        ["name" => "%G_OCF", "max" => 100],
                        ["name" => "%G_BVPS", "max" => 100],
                        ["name" => "%G_EPS", "max" => 100],
                        ["name" => "%G_FSales", "max" => 100],
                    ];
                    break;
                default:
                    break;
            }
            $option_radar_fourm = [
                "backgroundColor" => [
                    "type" => 'radial',
                    "x" => 0.5,
                    "y" => 0.5,
                    "r" => 0.9,
                    "colorStops" => [
                        [
                            "offset" => 0, "color" => '#000'
                        ],
                        [
                            "offset" => 1, "color" => '#BD1D1E'
                        ]
                    ],
                    "global" => false
                ],
                "tooltip" => [
                    "show" => true
                ],
                "radar" => [
                    "indicator" => $indicator,
                    "center" => ["50%", "48%"],
                    "splitNumber" => 9,
                    "axisLine" => [
                        "show" => false
                    ]
                ],
                "series" => [
                    [
                        "name" => "",
                        "type" => "radar",
                        "data" => [
                            []
                        ]
                    ],
                ],
            ];
            if ($fourm) {
                $option_radar_fourm = [
                    "backgroundColor" => [
                        "type" => 'radial',
                        "x" => 0.5,
                        "y" => 0.5,
                        "r" => 0.9,
                        "colorStops" => [
                            [
                                "offset" => 0, "color" => '#000'
                            ],
                            [
                                "offset" => 1, "color" => $fourm_point > 50 ? '#1E7934' : '#BD1D1E'
                            ]
                        ],
                        "global" => false
                    ],
                    "tooltip" => [
                        "show" => true
                    ],
                    "radar" => [
                        "indicator" => $indicator,
                        "center" => ["50%", "48%"],
                        "splitNumber" => 9,
                        "axisLine" => [
                            "show" => false
                        ]
                    ],
                    "series" => [
                        [
                            "name" => $fourm->mack,
                            "type" => "radar",
                            "data" => [
                                [
                                    round($fourm->sale_tang_truong),
                                    round($fourm->roic),
                                    round($fourm->roe),
                                    round($fourm->roa),
                                    round($fourm->lcdtkd_tren_loi_nhuan),
                                    round($fourm->loi_nhuan_tren_doanh_thu),
                                    round($fourm->doanh_thu_tren_tai_san),
                                    round($fourm->no_dai_han_nam_gan_nhat),
                                    round($fourm->lcdtkd_tang_truong),
                                    round($fourm->bvps_tang_truong),
                                    round($fourm->eps_tang_truong),
                                ]
                            ],
                            "lineStyle" => [
                                "color" => "#ff1111"
                            ],
                            "itemStyle" => [
                                "color" => "#fff"
                            ]
                        ],
                    ],
                ];
            }
            $res =  [
                "point" => $fourm_point,
                "option_chart" => $option_radar_fourm
            ];
            return response()->json($res);
        } else if ($type == "canslim") {
            $canslim = DB::table('canslim')
                ->where('mack', '=', $mack)
                ->where('thoigian', $time)
                ->first();
            $canslim_point = $canslim ? round($canslim->tong_diem, 2) : "N/A";
            $option_radar_canslim = [
                "backgroundColor" => [
                    "type" => 'radial',
                    "x" => 0.5,
                    "y" => 0.5,
                    "r" => 0.9,
                    "colorStops" => [
                        [
                            "offset" => 0, "color" => '#000'
                        ],
                        [
                            "offset" => 1, "color" => '#1E7934'
                        ]
                    ],
                    "global" => false
                ],
                "tooltip" => [
                    "show" => true
                ],
                "radar" => [
                    "indicator" => [
                        ["name" => "%G_Sales (C) ", "max" => 100],
                        ["name" => "%G_EPS (TTM-1)", "max" => 100],
                        ["name" => "%G_EPS (TTM)", "max" => 100],
                        ["name" => "%G_EPS (C-1)", "max" => 100],
                        ["name" => "%G_EPS (C) ", "max" => 100],
                        ["name" => "%G_Sales (TTM-1)", "max" => 100],
                        ["name" => "%G_Sales (TTM)", "max" => 100],
                        ["name" => "%G_Sales (C-1)", "max" => 100],
                        ["name" => "%ROE (TTM)", "max" => 100],
                        ["name" => "%ROE (TTM-1)", "max" => 100],
                    ],
                    "center" => ["50%", "48%"],
                    "splitNumber" => 9,
                    "axisLine" => [
                        "show" => false
                    ]
                ],
                "series" => [
                    [
                        "name" => "",
                        "type" => "radar",
                        "data" => [
                            []
                        ],
                        "lineStyle" => [
                            "color" => "#9E561C"
                        ],
                        "itemStyle" => [
                            "color" => "#fff"
                        ]
                    ],
                ],
            ];
            if ($canslim) {
                $option_radar_canslim = [
                    "backgroundColor" => [
                        "type" => 'radial',
                        "x" => 0.5,
                        "y" => 0.5,
                        "r" => 0.9,
                        "colorStops" => [
                            [
                                "offset" => 0, "color" => '#000'
                            ],
                            [
                                "offset" => 1, "color" => $canslim_point > 50 ? '#1E7934' : '#BD1D1E'
                            ]
                        ],
                        "global" => false
                    ],
                    "tooltip" => [
                        "show" => true
                    ],
                    "radar" => [
                        "indicator" => [
                            ["name" => "%G_Sales (C) ", "max" => 100],
                            ["name" => "%ROE (TTM-1)", "max" => 100],
                            ["name" => "%ROE (TTM)", "max" => 100],
                            ["name" => "%G_EPS (TTM-1)", "max" => 100],
                            ["name" => "%G_EPS (TTM)", "max" => 100],
                            ["name" => "%G_EPS (C-1)", "max" => 100],
                            ["name" => "%G_EPS (C) ", "max" => 100],
                            ["name" => "%G_Sales (TTM-1)", "max" => 100],
                            ["name" => "%G_Sales (TTM)", "max" => 100],
                            ["name" => "%G_Sales (C-1)", "max" => 100],
                        ],
                        "center" => ["50%", "48%"],
                        "splitNumber" => 9,
                        "axisLine" => [
                            "show" => false
                        ]
                    ],
                    "series" => [
                        [
                            "name" => $canslim->mack,
                            "type" => "radar",
                            "data" => [
                                [
                                    round($canslim->sale_quy_gan_nhat),
                                    round($canslim->roe_trailing_12_thang_truoc_do),
                                    round($canslim->roe_trailing_12_thang),
                                    round($canslim->eps_trailing_12_thang_truoc_do),
                                    round($canslim->eps_trailing_12_thang),
                                    round($canslim->eps_quy_gan_nhat_truoc_do),
                                    round($canslim->eps_quy_gan_nhat),
                                    round($canslim->sale_trailing_12_thang_truoc_do),
                                    round($canslim->sale_trailing_12_thang),
                                    round($canslim->sale_quy_gan_nhat_truoc_do),
                                ]
                            ],
                            "lineStyle" => [
                                "color" => "#9E561C"
                            ],
                            "itemStyle" => [
                                "color" => "#fff"
                            ]
                        ],
                    ],
                ];
            }
            $res =  [
                "point" => $canslim_point,
                "option_chart" => $option_radar_canslim
            ];
            return response()->json($res);
        }
    }

    public function bds3RedLinesBar(Request $request)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_nonbank')
            ->where('mack', $request->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_nonbank.thoigian,3),substr(bs_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take($limit)
            ->get(['thoigian', 'no_phai_tra', 'no_ngan_han', 'nguoi_mua_tra_tien_truoc', 'tong_cong_tai_san', 'nguon_von_chu_so_huu', 'tien_va_cac_khoan_tuong_duong_tien', 'cac_khoan_dau_tu_tai_chinh_ngan_han']);
        $count_res = count($res);
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res; $i++) {
            $res[$i]['lan_ranh_do_no_tts'] = 0.7;
            $res[$i]['lan_ranh_do_no_vcsh'] = 1;
            $res[$i]['lan_ranh_do_thanh_toan'] = 1;
            $res[$i]['no_tts'] = round(($res[$i]['no_phai_tra'] - $res[$i]['nguoi_mua_tra_tien_truoc']) / $res[$i]['tong_cong_tai_san'], 2);
            $res[$i]['no_vcsh'] = round(($res[$i]['no_phai_tra'] - $res[$i]['nguoi_mua_tra_tien_truoc']) / $res[$i]['nguon_von_chu_so_huu'], 2);
            $res[$i]['thanh_toan'] = round(($res[$i]['tien_va_cac_khoan_tuong_duong_tien'] + $res[$i]['cac_khoan_dau_tu_tai_chinh_ngan_han']) / $res[$i]['no_ngan_han'], 2);
        }
        if (!$res) {
            return [];
        }
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "3 lằn ranh đỏ",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => [
                    "LẰN RANH ĐỎ NỢ/TTS",
                    "LẰN RANH ĐỎ NỢ/VCSH",
                    "LẰN RANH ĐỎ THANH TOÁN",
                    "NỢ/TTS",
                    "NỢ/VCSH",
                    "THANH TOÁN",
                ],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "interval" => 0.1,
                    "name" => "Phần trăm",
                    "splitLine" => [
                        "show" => false
                    ],
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "NỢ/TTS",
                    "type" => "bar",
                    "data" => $arr[11],
                ],
                [
                    "name" => "NỢ/VCSH",
                    "type" => "bar",
                    "data" => $arr[12],
                ],
                [
                    "name" => "LẰN RANH ĐỎ NỢ/TTS",
                    "type" => "line",
                    "symbol" => "none",
                    "lineStyle" => [
                        "color" => "#ff0000",
                        "type" => "dashed"
                    ],
                    "itemStyle" => [
                        "color" => "#ff0000"
                    ],
                    "data" => $arr[8],
                ],
                [
                    "name" => "LẰN RANH ĐỎ NỢ/VCSH",
                    "type" => "line",
                    "symbol" => "none",
                    "lineStyle" => [
                        "color" => "#ff0000",
                        "type" => "dashed"
                    ],
                    "itemStyle" => [
                        "color" => "#ff0000"
                    ],
                    "data" => $arr[9],
                ],
                [
                    "name" => "LẰN RANH ĐỎ THANH TOÁN",
                    "type" => "line",
                    "symbol" => "none",
                    "lineStyle" => [
                        "type" => "dashed"
                    ],
                    "data" => $arr[10],
                ],
                [
                    "name" => "THANH TOÁN",
                    "type" => "line",
                    "data" => $arr[13],
                ],
            ],
        ];
    }

    public function bds3RedLinesTriangle(Request $request)
    {
        $res = [];
        $res = DB::table('bs_quarter_nonbank')
            ->where('mack', $request->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_nonbank.thoigian,3),substr(bs_quarter_nonbank.thoigian, 1, 2)) DESC')
            ->take(1)
            ->get(['thoigian', 'no_phai_tra', 'no_ngan_han', 'nguoi_mua_tra_tien_truoc', 'tong_cong_tai_san', 'nguon_von_chu_so_huu', 'tien_va_cac_khoan_tuong_duong_tien', 'cac_khoan_dau_tu_tai_chinh_ngan_han']);
        $res = json_decode(json_encode($res), true);
        if (!$res) {
            return [];
        }
        $lan_ranh_do_no_tts = 0.7;
        $lan_ranh_do_no_vcsh = 1;
        $lan_ranh_do_thanh_toan = 1;
        $no_tts = round(($res[0]['no_phai_tra'] - $res[0]['nguoi_mua_tra_tien_truoc']) / $res[0]['tong_cong_tai_san'], 2);
        $no_vcsh = round(($res[0]['no_phai_tra'] - $res[0]['nguoi_mua_tra_tien_truoc']) / $res[0]['nguon_von_chu_so_huu'], 2);
        $thanh_toan = round(($res[0]['tien_va_cac_khoan_tuong_duong_tien'] + $res[0]['cac_khoan_dau_tu_tai_chinh_ngan_han']) / $res[0]['no_ngan_han'], 2);

        return [
            "title" => [
                "text" => "",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "radar" => [
                "indicator" => [
                    ["name" => 'Nợ/TTS', "max" => 1],
                    ["name" => 'THANH TOÁN', "max" => 1],
                    ["name" => 'NỢ/VCSH', "max" => 1],
                ]
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => 'Budget vs spending',
                    "type" => 'radar',
                    "data" => [
                        [
                            "value" => [$lan_ranh_do_no_tts, $lan_ranh_do_thanh_toan, $lan_ranh_do_no_vcsh],
                            "name" => 'LẰN RANH',
                            "lineStyle" => [
                                "color" => "#ff0000"
                            ],
                            "itemStyle" => [
                                "color" => "#ff0000"
                            ]
                        ],
                        [
                            "value" => [$no_tts, $thanh_toan, $no_vcsh],
                            'name' => 'pending'
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function calculate_point_bank($diem, $position_column)
    {
        $arrMap = [
            [1,    70.00,    11.00,    2.75,    8.20,    1.00,    55.00,    4.00,    4.00,    2.30,    4.00,    0.10],
            [2,    70.00,    11.00,    2.75,    8.20,    1.00,    55.00,    4.00,    4.00,    2.30,    4.00,    0.10],
            [3,    65.00,    14.00,    2.50,    8.80,    1.10,    50.00,    6.00,    6.00,    2.45,    6.00,    0.40],
            [4,    60.00,    17.00,    2.25,    9.40,    1.20,    45.00,    8.00,    8.00,    2.60,    8.00,    0.70],
            [5,    55.00,    20.00,    2.00,    10.00,    1.30,    40.00,    10.00,    10.00,    2.75,    10.00,    1.00],
            [6,    50.00,    23.00,    1.75,    10.60,    1.40,    35.00,    12.00,    12.00,    2.90,    12.00,    1.30],
            [7,    45.00,    26.00,    1.50,    11.20,    1.50,    30.00,    14.00,    14.00,    3.05,    14.00,    1.60],
            [8,    40.00,    29.00,    1.25,    11.80,    1.60,    25.00,    16.00,    16.00,    3.20,    16.00,    1.90],
            [9,    35.00,    32.00,    1.00,    12.40,    1.70,    20.00,    18.00,    18.00,    3.35,    18.00,    2.20],
            [10,    30.00,    35.00,    0.75,    13.00,    1.80,    15.00,    20.00,    20.00,    3.50,    20.00,    2.50]
        ];
        return $arrMap[1][$position_column] >= $diem ? $arrMap[0][0] : ($arrMap[2][$position_column] >= $diem ? $arrMap[1][0] : ($arrMap[3][$position_column] >= $diem ? $arrMap[2][0] : ($arrMap[4][$position_column] >= $diem ? $arrMap[3][0] : ($arrMap[5][$position_column] >= $diem ? $arrMap[4][0] : ($arrMap[6][$position_column] >= $diem ? $arrMap[5][0] : ($arrMap[7][$position_column] >= $diem ? $arrMap[6][0] : ($arrMap[8][$position_column] >= $diem ? $arrMap[7][0] : ($arrMap[9][$position_column] >= $diem ? $arrMap[8][0] : $arrMap[9][0]))))))));
    }

    protected function calculate_point_cir_bank($diem, $position_column)
    {
        $arrMap = [
            [1,    70.00,    11.00,    2.75,    8.20,    1.00,    55.00,    4.00,    4.00,    2.30,    4.00,    0.10],
            [2,    70.00,    11.00,    2.75,    8.20,    1.00,    55.00,    4.00,    4.00,    2.30,    4.00,    0.10],
            [3,    65.00,    14.00,    2.50,    8.80,    1.10,    50.00,    6.00,    6.00,    2.45,    6.00,    0.40],
            [4,    60.00,    17.00,    2.25,    9.40,    1.20,    45.00,    8.00,    8.00,    2.60,    8.00,    0.70],
            [5,    55.00,    20.00,    2.00,    10.00,    1.30,    40.00,    10.00,    10.00,    2.75,    10.00,    1.00],
            [6,    50.00,    23.00,    1.75,    10.60,    1.40,    35.00,    12.00,    12.00,    2.90,    12.00,    1.30],
            [7,    45.00,    26.00,    1.50,    11.20,    1.50,    30.00,    14.00,    14.00,    3.05,    14.00,    1.60],
            [8,    40.00,    29.00,    1.25,    11.80,    1.60,    25.00,    16.00,    16.00,    3.20,    16.00,    1.90],
            [9,    35.00,    32.00,    1.00,    12.40,    1.70,    20.00,    18.00,    18.00,    3.35,    18.00,    2.20],
            [10,    30.00,    35.00,    0.75,    13.00,    1.80,    15.00,    20.00,    20.00,    3.50,    20.00,    2.50]
        ];
        return $arrMap[1][$position_column] <= $diem ? $arrMap[0][0] : ($arrMap[2][$position_column] <= $diem ? $arrMap[1][0] : ($arrMap[3][$position_column] <= $diem ? $arrMap[2][0] : ($arrMap[4][$position_column] <= $diem ? $arrMap[3][0] : ($arrMap[5][$position_column] <= $diem ? $arrMap[4][0] : ($arrMap[6][$position_column] <= $diem ? $arrMap[5][0] : ($arrMap[7][$position_column] <= $diem ? $arrMap[6][0] : ($arrMap[8][$position_column] <= $diem ? $arrMap[7][0] : ($arrMap[9][$position_column] <= $diem ? $arrMap[8][0] : $arrMap[9][0]))))))));
    }

    public function bankChartScatter(Request $req)
    {
        $thoigian = 'quarter';
        $typeBank = 'bank';
        $quarter = $req->input('quarter');
        $year = $req->input('year');
        $mack = strtoupper($req->input('mack'));
        $typeData = $req->input('type_data');
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $tm = 'tm_' . $thoigian . '_' . $typeBank;
        if ($quarter == 0 && $year == 0) {
            $rs = DB::table($bs)
                ->select("thoigian")
                ->where('mack', '=', $mack)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->first();
            $newest_time = $rs->thoigian;
            $quarter = substr($newest_time, 1, 1);
            $year = substr($newest_time, 3);
        }

        $scatter = DB::table($is . ' as is')
            ->join($bs . ' as bs', function ($join) {
                $join->on('bs.mack', '=', 'is.mack')
                    ->on('bs.thoigian', '=', 'is.thoigian');
            })
            ->join($tm . ' as tm', function ($join) {
                $join->on('tm.mack', '=', 'is.mack')
                    ->on('tm.thoigian', '=', 'is.thoigian');
            })
            ->where("is.thoigian", 'Q' . $quarter . ' ' . $year)
            // ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->orderBy('eps')
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.mack'))
            ->addSelect(DB::raw('lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/(von_dieu_le)*10000 as eps'));
        switch ($typeData) {
            case 'huy_dong_von':
                $scatter = $scatter->addSelect(DB::raw('bs.tien_gui_khach_hang as huy_dong_von'));
                break;
            case 'tong_tai_san':
                $scatter = $scatter->addSelect(DB::raw('bs.tong_cong_tai_san as tong_cong_tai_san'));
                break;
            case 'du_no_cho_vay':
                $scatter = $scatter->addSelect(DB::raw('bs.tong_cho_vay_khach_hang as du_no_cho_vay'));
                break;
            case 'von_dieu_le':
                $scatter = $scatter->addSelect(DB::raw('bs.von_dieu_le as von_dieu_le'));
                break;
            case 'thu_nhap_theo_lai_thuan':
                $scatter = $scatter->addSelect(DB::raw('is.thu_nhap_lai_thuan'));
                break;
            case 'loi_nhuan_sau_thue':
                $scatter = $scatter->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'));
                break;
            case 'no_nhom_3':
                $scatter = $scatter->addSelect(DB::raw('tm.no_duoi_tieu_chuan'));
                break;
            case 'no_nhom_4':
                $scatter = $scatter->addSelect(DB::raw('tm.no_nghi_ngo'));
                break;
            case 'no_nhom_5':
                $scatter = $scatter->addSelect(DB::raw('tm.no_xau_co_kha_nang_mat_von'));
                break;
            case 'no_nhom_345':
                $scatter = $scatter->addSelect(DB::raw('tm.no_duoi_tieu_chuan + tm.no_nghi_ngo+tm.no_xau_co_kha_nang_mat_von as no_nhom_345'));
                break;
            default:
                return [];
                break;
        }
        $scatter = $scatter->get();
        $scatter = json_decode(json_encode($scatter), true);
        // dd($scatter);
        $arrSeriesScatterChart = [];
        foreach ($scatter as $key => $row) {
            $arr_key = array_keys($row);
            $arrSeriesScatterChartItem = [
                ($key + 1) * 2,
                round(($row[$arr_key[3]] / 1000), 1),
                round($row[$arr_key[3]]  / 1000, 1),
                $row['mack']
            ];
            if ($row['mack'] === $mack)
                array_unshift($arrSeriesScatterChart, $arrSeriesScatterChartItem);
            else
                array_push($arrSeriesScatterChart, $arrSeriesScatterChartItem);
        }
        return $arrSeriesScatterChart;
    }

    public function taiSanQuyBank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_bank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_bank.thoigian,3),substr(bs_quarter_bank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('thoigian'))
            ->addSelect(DB::raw('tong_cong_tai_san / 1000 as tong_cong_tai_san'))
            ->addSelect(DB::raw('(tong_cong_nguon_von-loi_ich_cua_co_dong_thieu_so-von_va_cac_quy) /1000 as no_phai_tra'))
            ->get();
        $count_res = count($res);
        if ($count_res < 4)
            return [
                "series" => []
            ];
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['tt_tts'] = $res[$i + 4]['tong_cong_tai_san'] != 0 ? round(($res[$i]['tong_cong_tai_san'] / $res[$i + 4]['tong_cong_tai_san'] - 1) * 100, 1) : 0;
            $res[$i]['tt_npt'] = $res[$i + 4]['no_phai_tra'] != 0 ? round(($res[$i]['no_phai_tra'] / $res[$i + 4]['no_phai_tra'] - 1) * 100, 1) : 0;
            $res[$i]['no_tts'] = $res[$i]['tong_cong_tai_san'] != 0 ? round($res[$i]['no_phai_tra'] / $res[$i]['tong_cong_tai_san'] * 100, 1) : 0;
            $res[$i]['tong_cong_tai_san'] = round($res[$i]['tong_cong_tai_san'], 1);
            $res[$i]['no_phai_tra'] = round($res[$i]['no_phai_tra'], 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Tài sản quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Tổng cộng tài sản", "Nợ phải trả", "Tăng trưởng TTS so với cùng kỳ", "Tăng trưởng NPT so với cùng kỳ", "Nợ/TTS"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Tổng cộng tài sản",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Nợ phải trả",
                    "type" => "bar",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tăng trưởng TTS so với cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tăng trưởng NPT so với cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ],
                [
                    "name" => "Nợ/TTS",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[5],
                ],
            ],
        ];
    }

    public function ldrQuyBank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_bank')
            ->where('mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_bank.thoigian,3),substr(bs_quarter_bank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('thoigian'))
            ->addSelect(DB::raw('tong_cho_vay_khach_hang / 1000 as cho_vay_khach_hang'))
            ->addSelect(DB::raw('tien_gui_khach_hang /1000 as tien_gui_khach_hang'))
            ->addSelect(DB::raw('(cac_khoan_no_chinh_phu_va_nhnn+tien_gui_va_cho_vay_cac_tctd_khac+tien_gui_khach_hang+phat_hanh_giay_to_co_gia) /1000 as tvhd'))
            ->get();
        $count_res = count($res);
        if ($count_res < 4)
            return [
                "series" => []
            ];
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $res[$i]['tt_cvkh'] = $res[$i + 4]['cho_vay_khach_hang'] != 0 ? round(($res[$i]['cho_vay_khach_hang'] / $res[$i + 4]['cho_vay_khach_hang'] - 1) * 100, 1) : 0;
            $res[$i]['tt_tgkh'] = $res[$i + 4]['tien_gui_khach_hang'] != 0 ? round(($res[$i]['tien_gui_khach_hang'] / $res[$i + 4]['tien_gui_khach_hang'] - 1) * 100, 1) : 0;
            $res[$i]['ldr'] = $res[$i]['tvhd'] != 0 ? round($res[$i]['cho_vay_khach_hang'] / $res[$i]['tvhd'] * 100, 1) : 0;
            $res[$i]['cho_vay_khach_hang'] = round($res[$i]['cho_vay_khach_hang'], 1);
            $res[$i]['tien_gui_khach_hang'] = round($res[$i]['tien_gui_khach_hang'], 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "LDR quý",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Cho vay khách hàng", "Tiền gửi khách hàng", "Tăng trưởng CVKH so với cùng kỳ", "Tăng trưởng TGKH so với cùng kỳ", "LDR"],
                "top" => "10%",
                "type" => "scroll"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Cho vay khách hàng",
                    "type" => "bar",
                    "data" => $arr[1],
                ],
                [
                    "name" => "Tiền gửi khách hàng",
                    "type" => "bar",
                    "data" => $arr[2],
                ],
                [
                    "name" => "Tăng trưởng CVKH so với cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ],
                [
                    "name" => "Tăng trưởng TGKH so với cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[5],
                ],
                [
                    "name" => "LDR",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[6],
                ],
            ],
        ];
    }

    public function hieuQuaTTMBank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('bs_quarter_bank')
            ->join('is_quarter_bank as is', function ($join) {
                $join->on('bs_quarter_bank.mack', '=', 'is.mack')
                    ->on('bs_quarter_bank.thoigian', '=', 'is.thoigian');
            })
            ->where('is.mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(bs_quarter_bank.thoigian,3),substr(bs_quarter_bank.thoigian, 1, 2)) DESC')
            ->take($limit + 4)
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.thu_nhap_lai_thuan as thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai as lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'))
            ->addSelect(DB::raw('tien_gui_tai_nhnn + tien_vang_gui_tai_cac_tctd_khac + cho_vay_khach_hang + chung_khoan_dau_tu as b_yoea'))
            ->addSelect(DB::raw('tong_cong_tai_san as tong_cong_tai_san'))
            ->addSelect(DB::raw('von_va_cac_quy as von_va_cac_quy'))
            ->addSelect(DB::raw('loi_ich_cua_co_dong_thieu_so as loi_ich_cua_co_dong_thieu_so'))

            ->get();
        $count_res = count($res);
        if ($count_res < 4)
            return [
                "series" => []
            ];
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 4; $i++) {
            $thu_nhap_lai_thuan_ttm = $res[$i]['thu_nhap_lai_thuan'] + $res[$i + 1]['thu_nhap_lai_thuan'] + $res[$i + 2]['thu_nhap_lai_thuan'] + $res[$i + 3]['thu_nhap_lai_thuan'];
            $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 1]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 2]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 3]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'];

            $res[$i]['nim_ttm'] =  ($res[$i]['b_yoea'] + $res[$i + 4]['b_yoea']) != 0 ? ($thu_nhap_lai_thuan_ttm * 2 / ($res[$i]['b_yoea'] + $res[$i + 4]['b_yoea'])) * 100 : 0;
            $res[$i]['roaa_ttm'] = ($res[$i]['tong_cong_tai_san'] + $res[$i + 1]['tong_cong_tai_san']) != 0 ? ($lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm * 2 / ($res[$i]['tong_cong_tai_san'] + $res[$i + 4]['tong_cong_tai_san'])) * 100 : 0;
            $res[$i]['roea_ttm'] = ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so'] + $res[$i + 1]['von_va_cac_quy'] + $res[$i + 1]['loi_ich_cua_co_dong_thieu_so']) != 0 ? ($lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm * 2 / ($res[$i]['von_va_cac_quy'] + $res[$i]['loi_ich_cua_co_dong_thieu_so'] + $res[$i + 4]['von_va_cac_quy'] + $res[$i + 4]['loi_ich_cua_co_dong_thieu_so'])) * 100 : 0;
            $res[$i]['nim_ttm'] = round($res[$i]['nim_ttm'], 1);
            $res[$i]['roaa_ttm'] = round($res[$i]['roaa_ttm'], 1);
            $res[$i]['roea_ttm'] = round($res[$i]['roea_ttm'], 1);
        }
        $res = array_slice($res, 0, $count_res - 4);
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Hiệu quả TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["ROEA TTM", "ROAA TTM", "NIM TTM"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "ROEA TTM",
                    "type" => "line",
                    "data" => $arr[9],
                ],
                [
                    "name" => "ROAA TTM",
                    "type" => "line",
                    "data" => $arr[8],
                ],
                [
                    "name" => "NIM TTM",
                    "type" => "line",
                    "data" => $arr[7],
                ],
            ],
        ];
    }

    public function thuNhapTTMBank(Request $req)
    {
        $res = [];
        $limit = 11;
        $res = DB::table('is_quarter_bank as is')
            ->where('is.mack', $req->input('mack'))
            ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
            ->take($limit + 7)
            ->addSelect(DB::raw('is.thoigian'))
            ->addSelect(DB::raw('is.thu_nhap_lai_thuan/1000 as thu_nhap_lai_thuan'))
            ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/1000 as lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'))
            ->get();
        $count_res = count($res);
        if ($count_res < 7)
            return [
                "series" => []
            ];
        $res = json_decode(json_encode($res), true);
        for ($i = 0; $i < $count_res - 7; $i++) {
            $thu_nhap_lai_thuan_ttm = $res[$i]['thu_nhap_lai_thuan'] + $res[$i + 1]['thu_nhap_lai_thuan'] + $res[$i + 2]['thu_nhap_lai_thuan'] + $res[$i + 3]['thu_nhap_lai_thuan'];
            $thu_nhap_lai_thuan_ttm_before = $res[$i + 4]['thu_nhap_lai_thuan'] + $res[$i + 5]['thu_nhap_lai_thuan'] + $res[$i + 6]['thu_nhap_lai_thuan'] + $res[$i + 7]['thu_nhap_lai_thuan'];
            $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm = $res[$i]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 1]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 2]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 3]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'];
            $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm_before = $res[$i + 4]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 5]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 6]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[$i + 7]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'];
            $res[$i]['tnln_ttm'] = $thu_nhap_lai_thuan_ttm;
            if ($thu_nhap_lai_thuan_ttm * $thu_nhap_lai_thuan_ttm_before < 0 && $thu_nhap_lai_thuan_ttm < 0)
                $res[$i]['tt_tnln'] = -1;
            else {
                if ($thu_nhap_lai_thuan_ttm * $thu_nhap_lai_thuan_ttm_before < 0 && $thu_nhap_lai_thuan_ttm_before < 0)
                    $res[$i]['tt_tnln'] = 1;
                else
                    $res[$i]['tt_tnln'] = ($thu_nhap_lai_thuan_ttm / $thu_nhap_lai_thuan_ttm_before - 1) * 100;
            }
            $res[$i]['lnst_ttm'] = $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm;
            if ($lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm * $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm_before < 0 && $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm < 0)
                $res[$i]['tt_lnst'] = -1;
            else {
                if ($lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm * $thu_nhap_lai_thuan_ttm_before < 0 && $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm_before < 0)
                    $res[$i]['tt_lnst'] = 1;
                else
                    $res[$i]['tt_lnst'] = ($lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm / $lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai_ttm_before - 1) * 100;
            }
            $res[$i]['tnln_ttm'] = round($res[$i]['tnln_ttm'], 1);
            $res[$i]['tt_tnln'] = round($res[$i]['tt_tnln'], 1);
            $res[$i]['lnst_ttm'] = round($res[$i]['lnst_ttm'], 1);
            $res[$i]['tt_lnst'] = round($res[$i]['tt_lnst'], 1);
        }
        $res = array_slice($res, 0, $count_res - 7);
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = array_reverse($arr[$i]);
        }
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr[0][$i] = substr($arr[0][$i], 0, 2) . "." . substr($arr[0][$i], -2);
        }
        return [
            "title" => [
                "text" => "Thu nhập TTM",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
                // "formatter" => '<b>{b0}</b><br/>{a0}: {c0} tỷ VNĐ<br/>{a1}: {c1} tỷ VNĐ<br/>{a2}: {{c2}-{100}} %'
            ],

            "legend" => [
                "data" => ["Thu nhập lãi ròng (thuần) TTM", "Tăng trưởng TNLR so với cùng kỳ", "Lợi nhuận sau thuế TTM", "Tăng trưởng LNST so với cùng kỳ"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => $arr[0],
                    "axisLabel" => [
                        "interval" => 0,
                        "rotate" => 60
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
                [
                    "type" => "value",
                    "name" => "Phần trăm",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Thu nhập lãi ròng (thuần) TTM",
                    "type" => "bar",
                    "data" => $arr[3],
                ],
                [
                    "name" => "Tăng trưởng TNLR so với cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[4],
                ],
                [
                    "name" => "Lợi nhuận sau thuế TTM",
                    "type" => "bar",
                    "data" => $arr[5],
                ],
                [
                    "name" => "Tăng trưởng LNST so với cùng kỳ",
                    "type" => "line",
                    "yAxisIndex" => 1,
                    "data" => $arr[6],
                ],
            ],
        ];
    }

    public function noXauNganhBank(Request $req)
    {
        $res = [];
        $res = DB::table('tm_quarter_bank')
            ->whereIn('thoigian', function ($query) {
                $query->fromSub(
                    DB::table('tm_quarter_bank')
                        ->addSelect(DB::raw('distinct thoigian'))
                        ->orderByRaw("CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
                        ->take(4),
                    'sub'
                )
                    ->addSelect(DB::raw('thoigian'));
            })
            ->addSelect("thoigian")
            ->addSelect(DB::raw("SUM(no_duoi_tieu_chuan) as no_nhom_3"))
            ->addSelect(DB::raw("SUM(no_nghi_ngo) as no_nhom_4"))
            ->addSelect(DB::raw("SUM(no_xau_co_kha_nang_mat_von) as no_nhom_5"))
            ->addSelect(DB::raw("SUM(no_duoi_tieu_chuan + no_nghi_ngo + no_xau_co_kha_nang_mat_von) as no_nhom_345"))
            ->orderByRaw("CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
            ->groupBy(["thoigian"])
            ->get();
        $res = json_decode(json_encode($res), true);
        return [
            "title" => [
                "text" => "Nợ xấu ngành",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],

            "legend" => [
                "data" => ["Nợ nhóm 3", "Nợ nhóm 4", "Nợ nhóm 5", "Tổng nợ xấu"],
                "top" => "10%"
            ],
            "xAxis" => [
                [
                    'data' => [$res[3]["thoigian"], $res[2]["thoigian"], $res[1]["thoigian"], $res[0]["thoigian"]],
                    "axisLabel" => [
                        "interval" => 0,
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "grid" => [
                "top" => "32%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "series" => [
                [
                    "name" => "Nợ nhóm 3",
                    "type" => "line",
                    "data" => [$res[3]["no_nhom_3"], $res[2]["no_nhom_3"], $res[1]["no_nhom_3"], $res[0]["no_nhom_3"]],
                ],
                [
                    "name" => "Nợ nhóm 4",
                    "type" => "line",
                    "data" => [$res[3]["no_nhom_4"], $res[2]["no_nhom_4"], $res[1]["no_nhom_4"], $res[0]["no_nhom_4"]],
                ],
                [
                    "name" => "Nợ nhóm 5",
                    "type" => "line",
                    "data" => [$res[3]["no_nhom_5"], $res[2]["no_nhom_5"], $res[1]["no_nhom_5"], $res[0]["no_nhom_5"]],
                ],
                [
                    "name" => "Tổng nợ xấu",
                    "type" => "line",
                    "data" => [$res[3]["no_nhom_345"], $res[2]["no_nhom_345"], $res[1]["no_nhom_345"], $res[0]["no_nhom_345"]],
                ],
            ],
        ];
    }

    public function bankChart(Request $req)
    {
        $limit =  1;
        $thoigian = 'quarter';
        $typeBank = 'bank';
        $quarter = $req->input('quarter');
        $year = $req->input('year');
        $arrMack = $req->input('mack');
        $is = 'is_' . $thoigian . '_' . $typeBank;
        $bs = 'bs_' . $thoigian . '_' . $typeBank;
        $tm = 'tm_' . $thoigian . '_' . $typeBank;
        if ($quarter == 0 && $year == 0) {
            $rs = DB::table($bs)
                ->select("thoigian")
                ->where('mack', '=', $arrMack[0])
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->first();
            $newest_time = $rs->thoigian;
            $quarter = substr($newest_time, 1, 1);
            $year = substr($newest_time, 3);
        }
        $arrResponse["quarter"] = (int) $quarter;
        $arrResponse["year"] = (int) $year;
        $arrTime = [];
        for ($i = 0; $i < 4; $i++) {
            array_push($arrTime, 'Q' . $quarter . ' ' . $year);
            if ($quarter == 1) {
                $quarter = 4;
                $year--;
            } else
                $quarter--;
        }
        $res = [];
        $arrLegendRadarChart = [];
        $arrSeriesRadarChart = [];
        $arrBarPoint = [];
        $arrSeriesBarChart = [];
        $arrSeriesNoNhom3Chart = [];
        $arrSeriesNoNhom4Chart = [];
        $arrSeriesNoNhom5Chart = [];
        $arrSeriesNoNhom345Chart = [];
        foreach ($arrMack as $key => $row) {
            $car = DB::table('car_year_bank')
                ->whereIn("thoigian", [$year, $year - 1, $year - 2])
                ->where("mack", $row)
                ->addSelect(DB::raw('car*100 as car'))
                ->orderBy("thoigian", "DESC")
                ->get();

            $column_select = DB::table("temp_table")
                ->addSelect(DB::raw('is.thoigian as is_thoigian'))
                ->addSelect(DB::raw('bs.thoigian as bs_thoigian'))
                ->addSelect(DB::raw('tm.thoigian as tm_thoigian'))
                ->addSelect(DB::raw('is.chi_phi_hoat_dong'))
                ->addSelect(DB::raw('is.loi_nhuan_tu_hdkd_truoc_chi_phi_du_phong_rui_ro_tin_dung-is.chi_phi_hoat_dong as doanh_thu_hoat_dong'))
                ->addSelect(DB::raw('is.lai_lo_thuan_tu_hoat_dong_dich_vu'))
                ->addSelect(DB::raw('(is.lai_lo_thuan_tu_hoat_dong_kinh_doanh_ngoai_hoi+is.lai_lo_thuan_tu_mua_ban_chung_khoan_kinh_doanh+is.lai_lo_thuan_tu_mua_ban_chung_khoan_dau_tu) as doanh_thu_tu_hoat_dong_dau_tu'))
                ->addSelect(DB::raw('is.thu_nhap_lai_thuan'))
                ->addSelect(DB::raw('is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'))

                ->addSelect(DB::raw('0 as cir'))
                ->addSelect(DB::raw('(tm.tien_gui_khong_ky_han/(bs.cho_vay_khach_hang))*100 as casa'))
                ->addSelect(DB::raw('((tm.no_duoi_tieu_chuan+tm.no_nghi_ngo+tm.no_xau_co_kha_nang_mat_von)/(bs.tong_cho_vay_khach_hang)*100) as npl'))
                ->addSelect(DB::raw('bs.du_phong_rui_ro_cvkh/(bs.tong_cho_vay_khach_hang/100) as du_phong'))
                ->addSelect(DB::raw('bs.cac_khoan_lai_phi_phai_thu as du_thu'))
                ->addSelect(DB::raw('0 as phi'))
                ->addSelect(DB::raw('0 as dau_tu'))
                ->addSelect(DB::raw('(bs.tien_gui_tai_nhnn+bs.tien_vang_gui_tai_cac_tctd_khac_va_cho_vay_cac_tctd_khac+bs.tong_cho_vay_khach_hang+bs.chung_khoan_dau_tu) as nim'))
                ->addSelect(DB::raw('(bs.von_va_cac_quy+bs.loi_ich_cua_co_dong_thieu_so) as roe'))
                ->addSelect(DB::raw('bs.tong_cong_tai_san as roa'))
                ->addSelect(DB::raw('tm.no_duoi_tieu_chuan as no_nhom_3'))
                ->addSelect(DB::raw('tm.no_nghi_ngo as no_nhom_4'))
                ->addSelect(DB::raw('tm.no_xau_co_kha_nang_mat_von as no_nhom_5'))
                ->addSelect(DB::raw('tm.no_duoi_tieu_chuan + tm.no_nghi_ngo + tm.no_xau_co_kha_nang_mat_von as no_nhom_345'))
                ->addSelect(DB::raw('bs.tien_gui_khach_hang as huy_dong_von'));

            $table_is = DB::table($is . ' as is')
                ->leftJoin($bs . ' as bs', function ($join) {
                    $join->on('bs.mack', '=', 'is.mack')
                        ->on('bs.thoigian', '=', 'is.thoigian');
                })
                ->leftJoin($tm . ' as tm', function ($join) {
                    $join->on('tm.mack', '=', 'is.mack')
                        ->on('tm.thoigian', '=', 'is.thoigian');
                })
                ->where("is.mack", $row)
                ->whereIn("is.thoigian", $arrTime);
            $table_bs = DB::table($bs . ' as bs')
                ->leftJoin($is . ' as is', function ($join) {
                    $join->on('is.mack', '=', 'bs.mack')
                        ->on('is.thoigian', '=', 'bs.thoigian');
                })
                ->leftJoin($tm . ' as tm', function ($join) {
                    $join->on('tm.mack', '=', 'bs.mack')
                        ->on('tm.thoigian', '=', 'bs.thoigian');
                })
                ->whereNull("is.mack")
                ->where("bs.mack", $row)
                ->whereIn("bs.thoigian", $arrTime);
            $table_tm = DB::table($tm . ' as tm')
                ->leftJoin($is . ' as is', function ($join) {
                    $join->on('is.mack', '=', 'tm.mack')
                        ->on('is.thoigian', '=', 'tm.thoigian');
                })
                ->leftJoin($bs . ' as bs', function ($join) {
                    $join->on('bs.mack', '=', 'tm.mack')
                        ->on('bs.thoigian', '=', 'tm.thoigian');
                })
                ->whereNull("is.mack")
                ->whereNull("bs.mack")
                ->where("tm.mack", $row)
                ->whereIn("tm.thoigian", $arrTime);
            $table_is->columns = $column_select->columns;
            $table_bs->columns = $column_select->columns;
            $table_tm->columns = $column_select->columns;
            $res = DB::query()->fromSub(
                $table_is
                    ->union(
                        $table_bs
                    )
                    ->union(
                        $table_tm
                    ),
                'm'
            )
                ->take($limit + 3)
                ->orderByRaw(DB::raw("COALESCE(CONCAT(SUBSTR(m.is_thoigian,3),substr(m.is_thoigian, 1, 2)),CONCAT(SUBSTR(m.bs_thoigian,3),substr(m.bs_thoigian, 1, 2)),CONCAT(SUBSTR(m.tm_thoigian,3),substr(m.tm_thoigian, 1, 2))) DESC"))
                ->get();
            $res = json_decode(json_encode($res), true);
            if (!$res)
                continue;
            if (count($res) < ($limit + 3))
                continue;
            $res[0]['chi_phi_hoat_dong'] = $res[0]['chi_phi_hoat_dong'] + $res[1]['chi_phi_hoat_dong'] + $res[2]['chi_phi_hoat_dong'] + $res[3]['chi_phi_hoat_dong'];
            $res[0]['doanh_thu_hoat_dong'] = $res[0]['doanh_thu_hoat_dong'] + $res[1]['doanh_thu_hoat_dong'] + $res[2]['doanh_thu_hoat_dong'] + $res[3]['doanh_thu_hoat_dong'];
            $res[0]['lai_lo_thuan_tu_hoat_dong_dich_vu'] = $res[0]['lai_lo_thuan_tu_hoat_dong_dich_vu'] + $res[1]['lai_lo_thuan_tu_hoat_dong_dich_vu'] + $res[2]['lai_lo_thuan_tu_hoat_dong_dich_vu'] + $res[3]['lai_lo_thuan_tu_hoat_dong_dich_vu'];
            $res[0]['doanh_thu_tu_hoat_dong_dau_tu'] = $res[0]['doanh_thu_tu_hoat_dong_dau_tu'] + $res[1]['doanh_thu_tu_hoat_dong_dau_tu'] + $res[2]['doanh_thu_tu_hoat_dong_dau_tu'] + $res[3]['doanh_thu_tu_hoat_dong_dau_tu'];
            $res[0]['thu_nhap_lai_thuan'] = $res[0]['thu_nhap_lai_thuan'] + $res[1]['thu_nhap_lai_thuan'] + $res[2]['thu_nhap_lai_thuan'] + $res[3]['thu_nhap_lai_thuan'];
            $res[0]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] = $res[0]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[1]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[2]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] + $res[3]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'];
            $res[0]['cir'] = round(abs($res[0]['chi_phi_hoat_dong'] / $res[0]['doanh_thu_hoat_dong']) * 100, 2);
            $res[0]['casa'] = round($res[0]['casa'], 2);
            $res[0]['npl'] = round($res[0]['npl'], 2);
            $res[0]['car'] = $car[0]->car == 0 ? ($car[1]->car != 0 ? $car[1]->car : $car[2]->car) : $car[0]->car;
            $res[0]['car'] = round($res[0]['car'], 2);
            $res[0]['du_phong'] = round(abs($res[0]['du_phong']), 2);
            $res[0]['du_thu'] = round(($res[0]['du_thu'] / $res[0]['doanh_thu_hoat_dong']) * 100, 2);
            $res[0]['phi'] = round(($res[0]['lai_lo_thuan_tu_hoat_dong_dich_vu'] / $res[0]['doanh_thu_hoat_dong']) * 100, 2);
            $res[0]['dau_tu'] = round(($res[0]['doanh_thu_tu_hoat_dong_dau_tu'] / $res[0]['doanh_thu_hoat_dong']) * 100, 2);
            $res[0]['nim'] = round(($res[0]['thu_nhap_lai_thuan'] / $res[0]['nim']) * 100, 2);
            $res[0]['roe'] = round(($res[0]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] / $res[0]['roe']) * 100, 2);
            $res[0]['roa'] = round(($res[0]['lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai'] / $res[0]['roa']) * 100, 2);
            $res[0]['no_nhom_3'] = round($res[0]['no_nhom_3'], 2);
            $res[0]['no_nhom_4'] = round($res[0]['no_nhom_4'], 2);
            $res[0]['no_nhom_5'] = round($res[0]['no_nhom_5'], 2);
            $res[0]['no_nhom_345'] = round($res[0]['no_nhom_345'], 2);
            // dd($res);
            array_push($arrLegendRadarChart, $row);
            $arrSeriesRadarChartItem = [
                "value" => [
                    $this->calculate_point_cir_bank($res[0]['cir'], 1) * 10,
                    $this->calculate_point_bank($res[0]['roa'], 11) * 10,
                    $this->calculate_point_bank($res[0]['roe'], 10) * 10,
                    $this->calculate_point_bank($res[0]['nim'], 9) * 10,
                    $this->calculate_point_bank($res[0]['dau_tu'], 8) * 10,
                    $this->calculate_point_bank($res[0]['phi'], 7) * 10,
                    $this->calculate_point_cir_bank($res[0]['du_thu'], 6) * 10,
                    $this->calculate_point_bank($res[0]['du_phong'], 5) * 10,
                    $this->calculate_point_bank($res[0]['car'], 4) * 10,
                    $this->calculate_point_cir_bank($res[0]['npl'], 3) * 10,
                    $this->calculate_point_bank($res[0]['casa'], 2) * 10,
                ],
                "name" => $row,
                "type" => "line",
            ];
            array_push($arrSeriesRadarChart, $arrSeriesRadarChartItem);
            $arrSeriesNoNhom3ChartItem = [
                "name" => $row,
                "type" => 'line',
                "data" => [$res[3]['no_nhom_3'], $res[2]['no_nhom_3'], $res[1]['no_nhom_3'], $res[0]['no_nhom_3']]
            ];
            array_push($arrSeriesNoNhom3Chart, $arrSeriesNoNhom3ChartItem);
            $arrSeriesNoNhom4ChartItem = [
                "name" => $row,
                "type" => 'line',
                "data" => [$res[3]['no_nhom_4'], $res[2]['no_nhom_4'], $res[1]['no_nhom_4'], $res[0]['no_nhom_4']]
            ];
            array_push($arrSeriesNoNhom4Chart, $arrSeriesNoNhom4ChartItem);
            $arrSeriesNoNhom5ChartItem = [
                "name" => $row,
                "type" => 'line',
                "data" => [$res[3]['no_nhom_5'], $res[2]['no_nhom_5'], $res[1]['no_nhom_5'], $res[0]['no_nhom_5']]
            ];
            array_push($arrSeriesNoNhom5Chart, $arrSeriesNoNhom5ChartItem);
            $arrSeriesNoNhom345ChartItem = [
                "name" => $row,
                "type" => 'line',
                "data" => [$res[3]['no_nhom_345'], $res[2]['no_nhom_345'], $res[1]['no_nhom_345'], $res[0]['no_nhom_345']]
            ];
            array_push($arrSeriesNoNhom345Chart, $arrSeriesNoNhom345ChartItem);
            $arrBarPointItem = [
                "type" => $row,
                "cir" => $this->calculate_point_cir_bank($res[0]['cir'], 1),
                "casa" => $this->calculate_point_bank($res[0]['casa'], 2),
                "npl" => $this->calculate_point_cir_bank($res[0]['npl'], 3),
                "car" => $this->calculate_point_bank($res[0]['car'], 4),
                "du_phong" => $this->calculate_point_bank($res[0]['du_phong'], 5),
                "du_thu" => $this->calculate_point_cir_bank($res[0]['du_thu'], 6),
                "phi" => $this->calculate_point_bank($res[0]['phi'], 7),
                "dau_tu" => $this->calculate_point_bank($res[0]['dau_tu'], 8),
                "nim" => $this->calculate_point_bank($res[0]['nim'], 9),
                "roe" => $this->calculate_point_bank($res[0]['roe'], 10),
                "roa" => $this->calculate_point_bank($res[0]['roa'], 11),
                "diem" => -1
            ];
            $arrBarPointItem['diem'] = $arrBarPointItem['cir'] * 0.05 + $arrBarPointItem['casa'] * 0.04 + $arrBarPointItem['npl'] * 0.15 + $arrBarPointItem['car'] * 0.1 + $arrBarPointItem['du_phong'] * 0.03 + $arrBarPointItem['du_thu'] * 0.03 + $arrBarPointItem['phi'] * 0.03 + $arrBarPointItem['dau_tu'] * 0.02 + $arrBarPointItem['nim'] * 0.2 + $arrBarPointItem['roe'] * 0.2 + $arrBarPointItem['roa'] * 0.15;
            $arrBarPointItem['diem'] = round($arrBarPointItem['diem'] * 10, 1);
            array_push($arrBarPoint, $arrBarPointItem);
            array_push($arrSeriesBarChart, $arrBarPointItem['diem']);
        }

        $option_radar = [
            "backgroundColor" => [
                "type" => 'radial',
                "x" => 0.5,
                "y" => 0.5,
                "r" => 0.9,
                "colorStops" => [
                    [
                        "offset" => 0, "color" => '#000'
                    ],
                    [
                        "offset" => 1, "color" => '#1E7934'
                    ]
                ],
                "global" => false
            ],
            "tooltip" => [
                "show" => true
            ],
            "legend" => [
                "data" => $arrLegendRadarChart,
                "type" => "scroll",
                "bottom" => "0",
                "textStyle" => [
                    "color" => "#fff"
                ]
            ],
            "radar" => [
                "indicator" => [
                    ["name" => "CIR", "max" => 100],
                    ["name" => "ROA", "max" => 100],
                    ["name" => "ROE", "max" => 100],
                    ["name" => "NIM", "max" => 100],
                    ["name" => "Lãi đầu tư", "max" => 100],
                    ["name" => "Lãi thu phí", "max" => 100],
                    ["name" => "Lãi dự thu", "max" => 100],
                    ["name" => "Dự phòng", "max" => 100],
                    ["name" => "CAR", "max" => 100],
                    ["name" => "NPL", "max" => 100],
                    ["name" => "CASA", "max" => 100],
                ],
                "center" => ["50%", "48%"],
                "splitNumber" => 9,
                "axisLine" => [
                    "show" => false
                ]
            ],
            "series" => [
                [
                    "name" => "radar",
                    "type" => "radar",
                    "data" => $arrSeriesRadarChart
                ],
            ],
        ];
        $option_bar = [
            "tooltip" => [
                "show" => true
            ],
            "xAxis" => [
                "type" => "category",
                "data" => $arrLegendRadarChart,
                "axisLine" => [
                    "show" => false,
                ],
                "axisTick" => [
                    "show" => false,
                ],
                "axisLabel" => [
                    "show" => true,
                    "fontSize" => 20,
                    "fontWeight" => "900",
                    "color" => "#ef1cc9",
                    "rotate" => 90,
                ],
            ],
            "yAxis" => [
                "type" => "value",
                "splitLine" => [
                    "show" => false,
                ],
            ],
            "grid" => [
                "top" => "10%",
                "bottom" => "20%",
            ],
            "series" => [
                [
                    "data" => $arrSeriesBarChart,
                    "type" => "bar",
                    "color" => "#ef1cc9",
                    "barMaxWidth" => "20",
                    'label' => [
                        "show" => true,
                        "position" => "top",
                        "fontWeight" => "900",
                        "fontSize" => 20,
                        "color" => "#ef1cc9",
                    ],
                ],
            ],
        ];
        $option_no_xau_tung_ngan_hang_nhom_3 = [
            "title" => [
                "text" => "Nợ xấu từng ngân hàng",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => $arrLegendRadarChart,
                "type" => "scroll",
                "top" => "10%"
            ],
            "grid" => [
                "top" => "25%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "xAxis" => [
                [
                    'data' => array_reverse($arrTime),
                    "axisLabel" => [
                        "interval" => 0,
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "series" => $arrSeriesNoNhom3Chart
        ];
        $option_no_xau_tung_ngan_hang_nhom_4 = [
            "title" => [
                "text" => "Nợ xấu từng ngân hàng",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => $arrLegendRadarChart,
                "type" => "scroll",
                "top" => "10%"
            ],
            "grid" => [
                "top" => "25%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "xAxis" => [
                [
                    'data' => array_reverse($arrTime),
                    "axisLabel" => [
                        "interval" => 0,
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "series" => $arrSeriesNoNhom4Chart
        ];
        $option_no_xau_tung_ngan_hang_nhom_5 = [
            "title" => [
                "text" => "Nợ xấu từng ngân hàng",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => $arrLegendRadarChart,
                "type" => "scroll",
                "top" => "10%"
            ],
            "grid" => [
                "top" => "25%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "xAxis" => [
                [
                    'data' => array_reverse($arrTime),
                    "axisLabel" => [
                        "interval" => 0,
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "series" => $arrSeriesNoNhom5Chart
        ];
        $option_no_xau_tung_ngan_hang_nhom_345 = [
            "title" => [
                "text" => "Nợ xấu từng ngân hàng",
                "left" => "center",
                "align" => "auto",
                "top" => "2%",
                "textStyle" => [
                    "fontFamily" => "Tahoma"
                ]
            ],
            "tooltip" => [
                "trigger" => "axis",
            ],
            "legend" => [
                "data" => $arrLegendRadarChart,
                "type" => "scroll",
                "top" => "10%"
            ],
            "grid" => [
                "top" => "25%",
                "bottom" => "15%",
                "left" => "13%",
                "right" => "13%"
            ],
            "xAxis" => [
                [
                    'data' => array_reverse($arrTime),
                    "axisLabel" => [
                        "interval" => 0,
                    ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "name" => "Triệu VNĐ",
                    "axisLabel" => [
                        "formatter" => "{value}",
                    ],
                ],
            ],
            "series" => $arrSeriesNoNhom345Chart
        ];
        $arrResponse["radar"] = $option_radar;
        $arrResponse["bar"] =  $option_bar;
        $arrResponse["tsq"] =  $this->taiSanQuyBank($req);
        $arrResponse["ldrq"] =  $this->ldrQuyBank($req);
        $arrResponse["hqttm"] =  $this->hieuQuaTTMBank($req);
        $arrResponse["tnttm"] =  $this->thuNhapTTMBank($req);
        $arrResponse["nxtnh_nhom_3"] = $option_no_xau_tung_ngan_hang_nhom_3;
        $arrResponse["nxtnh_nhom_4"] = $option_no_xau_tung_ngan_hang_nhom_4;
        $arrResponse["nxtnh_nhom_5"] = $option_no_xau_tung_ngan_hang_nhom_5;
        $arrResponse["nxtnh_nhom_345"] = $option_no_xau_tung_ngan_hang_nhom_345;
        $arrResponse["nxn_nhom_345"] = $this->noXauNganhBank($req);
        return response()->json($arrResponse);
    }

    public function getAllChartNonbank(Request $req)
    {
        $req->merge([
            'mack' => strtoupper($req->input('mack')),
        ]);
        $typeBank = DB::connection('pgsql')
            ->table('stock_list')
            ->addSelect("nhom")
            ->addSelect("nganh")
            ->where('stockcode', '=', $req->input('mack'))
            ->first();
        if (!$typeBank)
            return [];
        $department = $typeBank->nganh;
        $typeBank = $typeBank->nhom;
        $arr = [];
        $arr["typeMack"] = $typeBank;
        $arr["optionChart"] = [];
        if ($typeBank === "nonbank") {
            // array_push($arr["optionChart"], $this->doanhThuLoiNhuanTTMNonbank($req));
            if ($this->doanhThuLoiNhuanQuyNonbank($req)) {
                array_push($arr["optionChart"], $this->doanhThuLoiNhuanQuyNonbank($req));
            }
            if ($this->bienLaiQuyNonbank($req)) {
                array_push($arr["optionChart"], $this->bienLaiQuyNonbank($req));
            }
            if ($this->hieuSuatHoatDongTTMNonbank($req)) {
                array_push($arr["optionChart"], $this->hieuSuatHoatDongTTMNonbank($req));
            }
            if ($this->khaNangThanhToanTTMNonbank($req)) {
                array_push($arr["optionChart"], $this->khaNangThanhToanTTMNonbank($req));
            }
            if ($this->luuChuyenTienQuyNonbank($req)) {
                array_push($arr["optionChart"], $this->luuChuyenTienQuyNonbank($req));
            }
            if ($this->phanRaROATTMNonbank($req)) {
                array_push($arr["optionChart"], $this->phanRaROATTMNonbank($req));
            }
            if ($this->phanRaROETTMNonbank($req)) {
                array_push($arr["optionChart"], $this->phanRaROETTMNonbank($req));
            }
            if ($this->taiSanQuyNonbank($req)) {
                array_push($arr["optionChart"], $this->taiSanQuyNonbank($req));
            }
            if ($this->nguonVonQuyNonbank($req)) {
                array_push($arr["optionChart"], $this->nguonVonQuyNonbank($req));
            }
            if (strtoupper($department) == "BẤT ĐỘNG SẢN") {
                if ($this->bds3RedLinesBar($req)) {
                    array_push($arr["optionChart"], $this->bds3RedLinesBar($req));
                }
                // if ($this->bds3RedLinesTriangle($req)) {
                // array_push($arr["optionChart"], $this->bds3RedLinesTriangle($req));
                // }
            }
        } else if ($typeBank === "stock") {
            // array_push($arr["optionChart"], $this->doanhThuLoiNhuanTTMStock($req));
            array_push($arr["optionChart"], $this->doanhThuLoiNhuanQuyStock($req));
            array_push($arr["optionChart"], $this->bienLaiQuyStock($req));
            array_push($arr["optionChart"], $this->duPhongTTMStock($req));
            array_push($arr["optionChart"], $this->khaNangThanhToanTTMStock($req));
            array_push($arr["optionChart"], $this->luuChuyenTienQuyStock($req));
            // array_push($arr["optionChart"], $this->hieuSuatHoatDongTTMStock($req));
            array_push($arr["optionChart"], $this->phanRaROATTMStock($req));
            array_push($arr["optionChart"], $this->phanRaROETTMStock($req));
            array_push($arr["optionChart"], $this->taiSanQuyStock($req));
            array_push($arr["optionChart"], $this->nguonVonQuyQuyStock($req));
        } else if ($typeBank === "insurance") {
            $type_direct_insurance = DB::table('insurance_type')
                ->where('mack', $req->input('mack'))
                ->first();
            $type_direct_insurance = $type_direct_insurance->type;
            $is_type_direct_insurance = $type_direct_insurance == "TT";
            $req->merge([
                'is_type_direct_insurance' => $is_type_direct_insurance,
            ]);
            array_push($arr["optionChart"], $this->doanhThuLoiNhuanQuyInsurance($req));
            array_push($arr["optionChart"], $this->bienLaiQuyInsurance($req));
            array_push($arr["optionChart"], $this->anToanTTMInsurance($req));
            array_push($arr["optionChart"], $this->khaNangThanhToanTTMInsurance($req));
            array_push($arr["optionChart"], $this->luuChuyenTienQuyInsurance($req));
            array_push($arr["optionChart"], $this->phanRaROATTMInsurance($req));
            if (!$is_type_direct_insurance)
                array_push($arr["optionChart"], $this->phanRaROETTMInsurance($req));
            array_push($arr["optionChart"], $this->taiSanQuyInsurance($req));
            array_push($arr["optionChart"], $this->nguonVonQuyInsurance($req));
        }
        return $arr;
    }
}
