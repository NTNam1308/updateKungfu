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

class CompareController extends Controller
{
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
        $department = $req->input('department');
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
                ->where('danh_sach_mack.nganh', '=', $department)
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
            $arr['danh_sach_ma'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (double) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_nonbank")
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item["von_hoa"] = $price * $item['von_hoa'];
            $item['gia_tri_doanh_nghiep'] = ($item['von_hoa'] + $item['gia_tri_doanh_nghiep']);
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['pb'] = $item['pb'] != 0 ? $price / $item['pb'] : 0;
            $item['evebit'] =  $item['evebit']  != 0 ? $item['gia_tri_doanh_nghiep'] / $item['evebit']  : 0;
            $item['evebitda'] = $item['evebitda'] != 0 ? $item['gia_tri_doanh_nghiep'] / $item['evebitda'] : 0;
            $item['peg'] = $item['peg'] !=0 ? ($item['pe'] / $item['peg']) : 0;
            $item["von_hoa"] = $item['von_hoa'] / 1000;
            $item['gia_tri_doanh_nghiep'] = $item['gia_tri_doanh_nghiep'] / 1000;
            array_push($list_items, $item);
        }

        $arr["list_items"] = $this::rotateTable($list_items);
        return $arr;
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
            $arr['danh_sach_ma'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (double) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_bank")
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['peg'] = $item['peg'] !=0 ? ($item['pe'] / $item['peg']) : 0;
            $item['gia_tri_so_sach'] = $item['von_hoa'] != 0 ? ($item['gia_tri_so_sach'] / $item['von_hoa']) : 0;
            $item['pb'] = $item['gia_tri_so_sach'] != 0 ? $price / $item['gia_tri_so_sach'] : 0;
            $item["von_hoa"] = $price * $item['von_hoa'] / 1000;
            array_push($list_items, $item);
        }

        $arr["list_items"] = $this::rotateTable($list_items);
        return $arr;
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
        $department = $req->input('department');
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
            $arr['danh_sach_ma'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (double) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_stock")
                ->where("mack", $mack)
                ->first();
            $item = json_decode(json_encode($item), true);
            if(!$item){
                continue;
            }
            $price = isset($list_price[$mack]) ? $list_price[$mack] : 0;
            $item["gia_thi_truong"] = $price;
            $item["von_hoa"] = $price * $item['von_hoa'];
            $item['gia_tri_doanh_nghiep'] = ($item['von_hoa'] + $item['gia_tri_doanh_nghiep']);
            $item['pe'] = $item['pe'] != 0 ? $price / $item['pe'] : 0;
            $item['pb'] = $item['pb'] != 0 ? $price / $item['pb'] : 0;
            $item['ev_ebit'] =  $item['ev_ebit']  != 0 ? $item['gia_tri_doanh_nghiep'] / $item['ev_ebit']  : 0;
            $item['peg'] = $item['peg'] !=0 ? ($item['pe'] / $item['peg']) : 0;
            $item["von_hoa"] = $item['von_hoa'] / 1000;
            $item['gia_tri_doanh_nghiep'] = $item['gia_tri_doanh_nghiep'] / 1000;
            array_push($list_items, $item);
        }

        $arr["list_items"] = $this::rotateTable($list_items);
        return $arr;
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
        $department = $req->input('department');
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
            $arr['danh_sach_ma'] = $arr_info_mack;
        }
        $list_price = DB::connection('pgsql')
            ->table('stock_live')
            ->whereIn('stockcode', $arrMack)
            ->addSelect('stockcode')
            ->addSelect('lastprice')
            ->get();
        foreach ($list_price as $key => $value) {
            $list_price[$value->stockcode] = (double) $value->lastprice;
            unset($list_price[$key]);
        }
        foreach ($arrMack as $mack) {
            $item = DB::table("compare_insurance")
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
            $item['evebit'] =  $item['evebit']  != 0 ? $item['gia_tri_doanh_nghiep'] / ($item['evebit'] / 1000)  : 0;
            $item['evebitda'] = $item['evebitda'] != 0 ? $item['gia_tri_doanh_nghiep'] / ($item['evebitda'] / 1000) : 0;
            $item['peg'] = $item['peg'] != 0 ? ($item['pe'] / $item['peg'] / 10000)  : 0;
            array_push($list_items, $item);
        }

        $arr["list_items"] = $this::rotateTable($list_items);
        return $arr;
    }

    public function getData(Request $req)
    {
        switch ($req->input('type')) {
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
            array_push($arr_return, array_column($arr, array_keys($arr[0])[$i]));
        }
        return $arr_return;
    }
}
