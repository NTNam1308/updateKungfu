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

class TOP400StockController extends Controller
{
    const TOP_400_STOCK = [];

    //hàm trừ đi số quý và số năm
    protected function calc_liquid_point($delta)
    {
        if ($delta >= 100) {
            return 100;
        } else if ($delta >= 80) {
            return 90;
        } else if ($delta >= 60) {
            return 80;
        } else if ($delta >= 40) {
            return 70;
        } else if ($delta >= 20) {
            return 60;
        } else if ($delta >= 0) {
            return 50;
        } else if ($delta >= -20) {
            return 40;
        } else if ($delta >= -40) {
            return 30;
        } else if ($delta >= -60) {
            return 20;
        } else if ($delta >= -80) {
            return 10;
        } else {
            return 0;
        }
    }

    //hàm trừ đi số quý và số năm
    protected function calc_rank_point($point_4m, $point_canslim, $point_liquid)
    {
        return $point_4m * 0.3 + $point_canslim * 0.55 + $point_liquid * 0.15;
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

    protected static function rotateTable($arr)
    {
        $arr_return = [];
        for ($i = 0; $i < count($arr[0]); $i++) {
            array_push($arr_return, array_column($arr, array_keys($arr[0])[$i]));
        }
        return $arr_return;
    }

    public function getDataChart(Request $req)
    {
        $list_mack = $req->input("mack");
        if(!$list_mack){
            $list_mack = DB::table('rank_top_stocks')
            ->addSelect("mack")
            ->orderBy("xep_hang")
            ->where('thoigian', function ($query){
                $query->fromSub(
                    DB::table('rank_top_stocks')
                        ->addSelect(DB::raw('thoigian'))
                        ->orderByRaw("mack,CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
                        ->take(1),
                    'sub'
                )
                    ->addSelect(DB::raw('thoigian'));
            })
            ->take(6)
            ->pluck("mack");
        }
        $COUNT_QUARTER = 8;
        $data = DB::table('rank_top_stocks')
            ->addSelect("mack")
            ->addSelect("thoigian")
            ->addSelect("diem_xep_hang")
            ->whereIn('thoigian', function ($query) use ($COUNT_QUARTER) {
                $query->fromSub(
                    DB::table('rank_top_stocks')
                        ->addSelect(DB::raw('thoigian'))
                        ->orderByRaw("mack,CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
                        ->take($COUNT_QUARTER + 1),
                    'sub'
                )
                    ->addSelect(DB::raw('thoigian'));
            })
            ->whereIn("mack", $list_mack)
            ->orderByRaw("mack,CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
            ->get();
        $data = json_decode(json_encode($data), true);
        $data_calc = [];
        for ($i = 0; $i < count($data); $i += $COUNT_QUARTER + 1) {
            $item = [
                "mack" => $data[$i]["mack"],
            ];
            $arr_time = [];
            $arr_rank_point = [];
            $arr_delta = [];
            for ($j = 0; $j < $COUNT_QUARTER; $j++) {
                array_push($arr_time, $data[$i + $j]["thoigian"]);
                array_push($arr_rank_point, round($data[$i + $j]["diem_xep_hang"], 2));
                array_push($arr_delta, round($data[$i + $j]["diem_xep_hang"] - $data[$i + $j + 1]["diem_xep_hang"], 2));
            }
            $item["thoigian"] = array_reverse($arr_time);
            $item["diem_xep_hang"] = array_reverse($arr_rank_point);
            $item["delta"] = array_reverse($arr_delta);
            $data_calc[$item["mack"]] = $item;
        }
        $data_return = [];
        foreach ($list_mack as $mack) {
            array_push($data_return,$data_calc[$mack]);
        }
        return $data_return;
    }

    public function getDataIndex()
    {
        $COUNT_QUARTER = 3;
        $data = DB::table('rank_top_stocks')
            ->addSelect("mack")
            ->addSelect("thoigian")
            ->addSelect("diem_xep_hang")
            ->addSelect("xep_hang")
            ->whereIn('thoigian', function ($query) use ($COUNT_QUARTER) {
                $query->fromSub(
                    DB::table('rank_top_stocks')
                        ->addSelect(DB::raw('thoigian'))
                        ->orderByRaw("mack,CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
                        ->take($COUNT_QUARTER),
                    'sub'
                )
                    ->addSelect(DB::raw('thoigian'));
            })
            ->orderByRaw("mack,CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC")
            ->get();
        $data = json_decode(json_encode($data), true);
        $data_return = [];
        for ($i = 0; $i < count($data); $i += $COUNT_QUARTER) {
            $item = [
                "mack" => $data[$i]["mack"],
                "thoigian" => $data[$i]["thoigian"],
            ];
            for ($j = 0; $j < $COUNT_QUARTER; $j++) {
                $item["diem_" . $j] = round($data[$i + $j]["diem_xep_hang"], 2);
                $item["rank_" . $j] = round($data[$i + $j]["xep_hang"], 2);
                if ($j > 0) {
                    $diem_xep_hang = $data[$i + $j]["diem_xep_hang"];
                    $diem_xep_hang_quy_sau = $data[$i + $j - 1]["diem_xep_hang"];
                    $xep_hang = $data[$i + $j]["xep_hang"];
                    $xep_hang_quy_sau = $data[$i + $j - 1]["xep_hang"];

                    $item["deltadiem_" . $j] = round(($diem_xep_hang_quy_sau - $diem_xep_hang), 2);
                    $item["deltarank_" . $j] = -round($xep_hang_quy_sau - $xep_hang, 2);
                }
            }
            array_push($data_return, $item);
        }
        usort($data_return, function ($a, $b) {
            return $a['rank_0'] > $b['rank_0'] ? 1 : -1;
        });
        return $data_return;
    }

    //hàm gen data và lưu vào DB
    public function generateDataToDB()
    {
        $COUNT_QUARTER = 8;
        $list_time = [];
        $data_top_stocks = DB::table('top_stocks')
            ->where('thoigian', function ($query) {
                $query->select(DB::raw('thoigian'))
                    ->from('top_stocks')
                    ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                    ->take(1);
            })
            ->get();
        error_log("Start query get data");
        $data_top_stocks = json_decode(json_encode($data_top_stocks), true);
        $list_top_stocks = array_column($data_top_stocks, "mack");
        $newest_quarter = $data_top_stocks[0]["thoigian"];
        for ($i = 0; $i < $COUNT_QUARTER + 1; $i++) {
            array_push($list_time, $this->dec_quarter($newest_quarter, $i));
        }
        DB::table('rank_top_stocks')->delete();
        error_log("calculating data");
        foreach ($list_time as $quarter) {
            if ($quarter == $newest_quarter) {
                $DATA = $this->calc_data_rank_quarter($list_top_stocks, $quarter, "newest");
            } else {
                $DATA = $this->calc_data_rank_quarter($list_top_stocks, $quarter, "history");
            }
            
            DB::table('rank_top_stocks')->insert($DATA);
        }
        error_log("generating data to DB");
        error_log("DONE");
        return "success";
    }

    //hàm tính xếp hạng của top stock
    public function calc_data_rank_quarter($list_top_stocks, $quarter, $type)
    {
        $data_return = [];
        if ($type == "history") {
            $data_totalval = $this->getListTotalvalHistory($list_top_stocks, $quarter);
        } else if ($type == "newest") {
            $data_totalval = $this->getListTotalvalNewest($list_top_stocks, $quarter);
        }
        $data_4mcanslim = $this->getList4mCanslim($list_top_stocks, $quarter);
        $data_totalval = json_decode(json_encode($data_totalval), true);
        $data_4mcanslim = json_decode(json_encode($data_4mcanslim), true);
        foreach ($data_totalval as $key => $value) {
            $data_totalval[$value['stockcode']] = $value;
            unset($data_totalval[$key]);
        }
        foreach ($data_4mcanslim as $key => $value) {
            $data_4mcanslim[$value['mack']] = $value;
            unset($data_4mcanslim[$key]);
        }
        foreach ($list_top_stocks as $mack) {
            $point_4m = isset($data_4mcanslim[$mack]) ? $data_4mcanslim[$mack]["diem_4m"] : 0;
            $point_canslim = isset($data_4mcanslim[$mack]) ? $data_4mcanslim[$mack]["diem_canslim"] : 0;

            $tong_gtgd = isset($data_totalval[$mack]) ? $data_totalval[$mack]["tong_gtgd"] : 0;
            $gtgd_chu_ky_truoc = isset($data_totalval[$mack]) ? $data_totalval[$mack]["gtgd_chu_ky_truoc"] : 0;
            $delta = $gtgd_chu_ky_truoc != 0 ? ($tong_gtgd - $gtgd_chu_ky_truoc) / $gtgd_chu_ky_truoc : 0;
            $delta = $delta * 100;
            $point_liquid = $this->calc_liquid_point($delta);

            $rank_point = $this->calc_rank_point($point_4m, $point_canslim, $point_liquid);
            array_push($data_return, [
                "mack" => $mack,
                "thoigian" => $quarter,
                "tong_gtgd" => (float) $tong_gtgd,
                "gtgd_chu_ky_truoc" => (float) $gtgd_chu_ky_truoc,
                "delta" => (float) $delta,
                "diem_4m" => $point_4m,
                "diem_canslim" => $point_canslim,
                "diem_thanh_khoan" => $point_liquid,
                "diem_xep_hang" => $rank_point
            ]);
        }
        usort($data_return, function ($a, $b) {
            return $a['diem_xep_hang'] < $b['diem_xep_hang'] ? 1 : -1;
        });
        for ($i = 0; $i < count($data_return); $i++) {
            $data_return[$i]["xep_hang"] = $i + 1;
        }
        return $data_return;
    }

    //hàm lấy điểm 4m canslim theo quý
    protected function getList4mCanslim($listmack, $quarter)
    {
        return DB::table('fourm')
            ->join("canslim", function ($join) {
                $join->on("fourm.mack", "=", "canslim.mack")
                    ->on('fourm.thoigian', '=', 'canslim.thoigian');
            })
            ->addSelect(DB::Raw('fourm.mack'))
            ->addSelect(DB::Raw('fourm.thoigian'))
            ->addSelect(DB::Raw('fourm.tong_diem as diem_4m'))
            ->addSelect(DB::Raw('canslim.tong_diem as diem_canslim'))
            ->where('fourm.thoigian', $quarter)
            ->whereIn('fourm.mack', $listmack)
            ->orderBy("fourm.mack")
            ->get();
    }

    //hàm lấy totalval theo 40 ngày mới nhất
    protected function getListTotalvalNewest($listmack, $quarter)
    {
        $data_totalval = DB::connection('pgsql')->query()->fromSub(
            DB::connection('pgsql')
                ->table('stock_eod')
                ->addSelect(DB::raw('stockcode'))
                ->addSelect(DB::raw('totalval1'))
                ->where('stockcode', $listmack[0])
                ->orderBy('stockcode', 'DESC')
                ->orderBy('tradingdate', 'DESC')
                ->take(20),
            'sub'
        )
            ->addSelect(DB::raw('stockcode'))
            ->addSelect(DB::raw('SUM(totalval1) as tong_gtgd'))
            ->addSelect(DB::raw('0 as gtgd_chu_ky_truoc'))
            ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
            ->groupBy(["stockcode"]);
        $data_totalval = $data_totalval->unionAll(
            DB::connection('pgsql')->query()->fromSub(
                DB::connection('pgsql')
                    ->table('stock_eod')
                    ->addSelect(DB::raw('stockcode'))
                    ->addSelect(DB::raw('totalval1'))
                    ->where('stockcode', $listmack[0])
                    ->orderBy('stockcode', 'DESC')
                    ->orderBy('tradingdate', 'DESC')
                    ->take(20)
                    ->offset(20),
                'sub'
            )
                ->addSelect(DB::raw('stockcode'))
                ->addSelect(DB::raw('0 as tong_gtgd'))
                ->addSelect(DB::raw('SUM(totalval1) as gtgd_chu_ky_truoc'))
                ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
                ->groupBy(["stockcode"])
        );
        for ($i = 1; $i < count($listmack); $i++) {
            $data_totalval = $data_totalval->unionAll(
                DB::connection('pgsql')->query()->fromSub(
                    DB::connection('pgsql')
                        ->table('stock_eod')
                        ->addSelect(DB::raw('stockcode'))
                        ->addSelect(DB::raw('totalval1'))
                        ->where('stockcode', $listmack[$i])
                        ->orderBy('stockcode', 'DESC')
                        ->orderBy('tradingdate', 'DESC')
                        ->take(20),
                    'sub'
                )
                    ->addSelect(DB::raw('stockcode'))
                    ->addSelect(DB::raw('SUM(totalval1) as tong_gtgd'))
                    ->addSelect(DB::raw('0 as gtgd_chu_ky_truoc'))
                    ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
                    ->groupBy(["stockcode"])
            );
            $data_totalval = $data_totalval->unionAll(
                DB::connection('pgsql')->query()->fromSub(
                    DB::connection('pgsql')
                        ->table('stock_eod')
                        ->addSelect(DB::raw('stockcode'))
                        ->addSelect(DB::raw('totalval1'))
                        ->where('stockcode', $listmack[$i])
                        ->orderBy('stockcode', 'DESC')
                        ->orderBy('tradingdate', 'DESC')
                        ->take(20)
                        ->offset(20),
                    'sub'
                )
                    ->addSelect(DB::raw('stockcode'))
                    ->addSelect(DB::raw('0 as tong_gtgd'))
                    ->addSelect(DB::raw('SUM(totalval1) as gtgd_chu_ky_truoc'))
                    ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
                    ->groupBy(["stockcode"])
            );
        }
        $data_totalval = DB::connection('pgsql')->query()->fromSub(
            $data_totalval,
            'sub'
        )
            ->addSelect(DB::raw('stockcode'))
            ->addSelect(DB::raw("quarter"))
            ->addSelect(DB::raw('SUM(tong_gtgd) as tong_gtgd'))
            ->addSelect(DB::raw('SUM(gtgd_chu_ky_truoc) as gtgd_chu_ky_truoc'))
            ->orderBy("stockcode")
            ->groupBy(["stockcode", "quarter"]);
        return $data_totalval->get();
    }

    //hàm lấy totalval theo 40 ngày cuối cùng của quý trong quá khứ
    protected function getListTotalvalHistory($listmack, $quarter)
    {
        $data_totalval = DB::connection('pgsql')->query()->fromSub(
            DB::connection('pgsql')
                ->table('stock_eod')
                ->addSelect(DB::raw('stockcode'))
                ->addSelect(DB::raw('totalval1'))
                ->where('stockcode', $listmack[0])
                ->where('tradingdate', "<=", $this->getLastDayInQuarter($quarter, "-"))
                ->orderBy('stockcode', 'DESC')
                ->orderBy('tradingdate', 'DESC')
                ->take(20),
            'sub'
        )
            ->addSelect(DB::raw('stockcode'))
            ->addSelect(DB::raw('SUM(totalval1) as tong_gtgd'))
            ->addSelect(DB::raw('0 as gtgd_chu_ky_truoc'))
            ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
            ->groupBy(["stockcode"]);
        $data_totalval = $data_totalval->unionAll(
            DB::connection('pgsql')->query()->fromSub(
                DB::connection('pgsql')
                    ->table('stock_eod')
                    ->addSelect(DB::raw('stockcode'))
                    ->addSelect(DB::raw('totalval1'))
                    ->where('stockcode', $listmack[0])
                    ->where('tradingdate', "<=", $this->getLastDayInQuarter($quarter, "-"))
                    ->orderBy('stockcode', 'DESC')
                    ->orderBy('tradingdate', 'DESC')
                    ->take(20)
                    ->offset(20),
                'sub'
            )
                ->addSelect(DB::raw('stockcode'))
                ->addSelect(DB::raw('0 as tong_gtgd'))
                ->addSelect(DB::raw('SUM(totalval1) as gtgd_chu_ky_truoc'))
                ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
                ->groupBy(["stockcode"])
        );
        for ($i = 1; $i < count($listmack); $i++) {
            $data_totalval = $data_totalval->unionAll(
                DB::connection('pgsql')->query()->fromSub(
                    DB::connection('pgsql')
                        ->table('stock_eod')
                        ->addSelect(DB::raw('stockcode'))
                        ->addSelect(DB::raw('totalval1'))
                        ->where('stockcode', $listmack[$i])
                        ->where('tradingdate', "<=", $this->getLastDayInQuarter($quarter, "-"))
                        ->orderBy('stockcode', 'DESC')
                        ->orderBy('tradingdate', 'DESC')
                        ->take(20),
                    'sub'
                )
                    ->addSelect(DB::raw('stockcode'))
                    ->addSelect(DB::raw('SUM(totalval1) as tong_gtgd'))
                    ->addSelect(DB::raw('0 as gtgd_chu_ky_truoc'))
                    ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
                    ->groupBy(["stockcode"])
            );
            $data_totalval = $data_totalval->unionAll(
                DB::connection('pgsql')->query()->fromSub(
                    DB::connection('pgsql')
                        ->table('stock_eod')
                        ->addSelect(DB::raw('stockcode'))
                        ->addSelect(DB::raw('totalval1'))
                        ->where('stockcode', $listmack[$i])
                        ->where('tradingdate', "<=", $this->getLastDayInQuarter($quarter, "-"))
                        ->orderBy('stockcode', 'DESC')
                        ->orderBy('tradingdate', 'DESC')
                        ->take(20)
                        ->offset(20),
                    'sub'
                )
                    ->addSelect(DB::raw('stockcode'))
                    ->addSelect(DB::raw('0 as tong_gtgd'))
                    ->addSelect(DB::raw('SUM(totalval1) as gtgd_chu_ky_truoc'))
                    ->addSelect(DB::raw("'" . $quarter . "' as quarter"))
                    ->groupBy(["stockcode"])
            );
        }
        $data_totalval = DB::connection('pgsql')->query()->fromSub(
            $data_totalval,
            'sub'
        )
            ->addSelect(DB::raw('stockcode'))
            ->addSelect(DB::raw("quarter"))
            ->addSelect(DB::raw('SUM(tong_gtgd) as tong_gtgd'))
            ->addSelect(DB::raw('SUM(gtgd_chu_ky_truoc) as gtgd_chu_ky_truoc'))
            ->orderBy("stockcode")
            ->groupBy(["stockcode", "quarter"]);
        return $data_totalval->get();
    }
}
