<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\Stock;
use \Exception;
use Mail;
use Datetime;
use Carbon\Carbon;

// set_error_handler(function () {
//     throw new Exception('Ach!');
// });

class StocksController extends Controller
{

    public function index(Request $request) //TODO: CTP separate list function for each type
    {
        //CTP
        $res = [];
        $mack = strtoupper($request->input('mack'));
        $thoigian = $request->input('thoigian');
        $type = $request->input('type');
        $page = $request->input('page') ? $request->input('page') : 1;
        $item_per_page = $request->input('item_per_page') ? $request->input('item_per_page') : 100;
        $page -= 1;
        $order = $request->input('order') ? $request->input('order') : "asc";
        $typeBank = DB::table('danh_sach_mack')
            ->select("nhom")
            ->where('mack', '=', $mack)
            ->first();
        $typeBank = $typeBank->nhom;
        if ($mack == NULL or $thoigian == NULL or $type == NULL) {
            return $res;
        }

        info('INDEX FUNCTION CALLED' . $type);

        $table =  $type . "_" . $thoigian . "_" . $typeBank;
        info('Start query ' . $table);
        if ($type == "is") {
            $table_bs = "bs_" . $thoigian . "_" . $typeBank;
            $column_eps = "";
            switch ($typeBank) {
                case 'nonbank':
                    $column_eps = 'is.loi_nhuan_sau_thue_cua_co_dong_cua_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10)*1000 as eps';
                    break;
                case 'bank':
                    $column_eps = 'is.lnst_sau_khi_dieu_chinh_loi_ich_cua_cdts_va_co_tuc_uu_dai/(bs.von_dieu_le)*10000 as eps';
                    break;
                case 'stock':
                    $column_eps = 'is.loi_nhuan_ke_toan_sau_thue_tndn/(bs.von_gop_cua_chu_so_huu/10000) as eps';
                    break;
                case 'insurance':
                    $column_eps = 'is.loi_nhuan_sau_thue_cua_co_dong_cong_ty_me/(bs.von_dau_tu_cua_chu_so_huu/10000) as eps';
                    break;
                default:
                    break;
            }
            $res = DB::table($table . " as is")
                ->leftJoin($table_bs . ' as bs', function ($join) {
                    $join->on('bs.mack', '=', 'is.mack')
                        ->on('bs.thoigian', '=', 'is.thoigian');
                })
                ->addSelect("is.*")
                ->addSelect(DB::raw($column_eps))
                ->where('is.mack', '=', $mack)
                ->orderByRaw('CONCAT(substr(is.thoigian,3),substr(is.thoigian, 1, 2)) DESC')
                ->offset($page * $item_per_page)
                ->take($item_per_page)
                ->get();
        } else {
            $res = DB::table($table)
                ->select("*")
                ->where('mack', '=', $mack)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->offset($page * $item_per_page)
                ->take($item_per_page)
                ->get();
        }
        info($res);
        $res = json_decode(json_encode($res), true);
        if (!$res)
            return response()->json($res);
        // return $res;
        $arr = [];
        for ($i = 0; $i < count($res[0]); $i++) {
            array_push($arr, array_column($res, array_keys($res[0])[$i]));
        }
        if ($order == "asc") {
            for ($i = 0; $i < count($arr); $i++) {
                $arr[$i] = array_reverse($arr[$i]);
            }
        }
        array_unshift($arr, $typeBank);
        return response()->json($arr);
    }

    public function checkQuarterAll(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $thoigian = $request->input('thoigian');
        $type = ["bs", "is", "cf"];

        $cdkt = $this->checkQuarter($mack, $thoigian, $type[0]); // cân đối kế toán
        $bckqkd = $this->checkQuarter($mack, $thoigian, $type[1]); // báo cáo kết quả kinh doanh
        $dt = $this->checkQuarter($mack, $thoigian, $type[2]); // dòng tiền

        $array = [$cdkt, $bckqkd, $dt];
        foreach ($array as $data) {
            if ($data == 1) {
                return 1;
            } // if has 1 return true;
        }
        return 0;
    } 
    
    public function checkQuarterByMack( $mack ) 
    {  
        $thoigian = 'quarter';
        $type = ["bs", "is", "cf"];

        $cdkt = $this->checkQuarter($mack, $thoigian, $type[0] ); // cân đối kế toán
        $bckqkd = $this->checkQuarter($mack, $thoigian, $type[1] ); // báo cáo kết quả kinh doanh
        $dt = $this->checkQuarter($mack, $thoigian, $type[2] ); // dòng tiền

        $array = [$cdkt, $bckqkd, $dt];
        foreach($array as $data) { 
            if($data == 1) { return 1; } // if has 1 return true;
        }
        return 0;

    }
    public function checkQuarter($mack, $thoigian, $type ) {
        if (strlen($mack) > 3) { return 0; }

        $typeBank =  Stock::query()->select("nhom")->where('stockcode', '=', $mack)->first();
        $arrObject = (array)$typeBank; // move oject to arary, use check
        if( !$arrObject ) { return 0; }   
        $typeBank = $typeBank->nhom;

        $table =  $type . "_" . $thoigian . "_" . $typeBank;
        $res = DB::table($table)
            ->select("thoigian")
            ->where('mack', '=', $mack)
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->first();

        $currentSubQuarter = Carbon::now()->subQuarters(1)->quarter; // sub quarter, get quarter
        $currentYearSubQuarter = Carbon::now()->subQuarters(1)->year; // sub quarter, get year
        $check = "Q" . $currentSubQuarter . " " . $currentYearSubQuarter;

        $checkQuarter = 0;
        $quarterTime = "";
        $arr = (array)$res; // add object go to array
        
        if ( count($arr) > 0 ) {
            $quarterTime = $arr['thoigian']; // first quarter result
        }
        
        if ($check == $quarterTime) { // if has new quarter, return 1
            $checkQuarter = 1;
        } 
        return $checkQuarter;
    }
    public function filterCompanyGroup(Request $request)
    {

        $type = $request->type; // default is bs
        $thoigian = "quarter";
        $companyGroup = $request->company_group; // stock, nonbank, bank, insurance
        $table =  $type . "_" . $thoigian . "_" . $companyGroup;

        $quarter = $request->quarter; // Q1, Q2, Q3, Q4
        $year = $request->year;
        $time_result = $quarter . " " . $year;

        if ($request->company_group == "all") {

            $tableStock =  $type . "_" . $thoigian . "_" . "stock";
            $tableNonbank =  $type . "_" . $thoigian . "_" . "nonbank";
            $tableBank =  $type . "_" . $thoigian . "_" . "bank";
            $tableInsurance =  $type . "_" . $thoigian . "_" . "insurance";
            // stock
            $resTableStock = DB::table($tableStock)
                ->select("thoigian", "mack")
                ->addSelect(DB::raw("'stock' as nhom"))
                ->where('thoigian', '=', $time_result)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $resTableStock = json_decode(json_encode($resTableStock), true);

            // nonbank
            $resTableNonbank = DB::table($tableNonbank)
                ->select("thoigian", "mack")
                ->addSelect(DB::raw("'nonbank' as nhom"))
                ->where('thoigian', '=', $time_result)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $resTableNonbank = json_decode(json_encode($resTableNonbank), true);

            // bank
            $resTableBank = DB::table($tableBank)
                ->select("thoigian", "mack")
                ->addSelect(DB::raw("'bank' as nhom"))
                ->where('thoigian', '=', $time_result)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $resTableBank = json_decode(json_encode($resTableBank), true);

            // insurance
            $resTableInsurance = DB::table($tableInsurance)
                ->select("thoigian", "mack")
                ->addSelect(DB::raw("'insurance' as nhom"))
                ->where('thoigian', '=', $time_result)
                ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
                ->get();
            $resTableInsurance = json_decode(json_encode($resTableInsurance), true);

            $result = array_merge($resTableStock, $resTableNonbank, $resTableBank, $resTableInsurance);
            return response()->json($result);
        }

        $res = DB::table($table)
            ->select("thoigian", "mack")
            ->addSelect(DB::raw("'$request->company_group' as nhom"))
            ->where('thoigian', '=', $time_result)
            ->orderByRaw('CONCAT(substr(thoigian,3),substr(thoigian, 1, 2)) DESC')
            ->get();
        return response()->json($res);
    }
    public function searchInfo(Request $req)
    {
        $limit = $req->input('limit') ? $req->input('limit') : "5";
        $type = $req->input('type');
        $mack = strtoupper($req->input('mack'));
        $department = $req->input('department');
        if ($type) {
            $res = DB::table('danh_sach_mack')
                ->where('danh_sach_mack.mack', 'like', '%' . $mack . '%')
                ->leftJoin('cong_ty_tong_quan', 'danh_sach_mack.mack', '=', 'cong_ty_tong_quan.mack')
                ->where('danh_sach_mack.nhom', '=', $type)
                ->take($limit)
                ->addSelect('danh_sach_mack.mack')
                ->addSelect('danh_sach_mack.nhom')
                ->addSelect('danh_sach_mack.nganh')
                ->addSelect('cong_ty_tong_quan.ten_cong_ty');
            if ($department) {
                $res = $res->where('danh_sach_mack.nganh', '=', $department);
            }
            $res = $res->get();
        } else {
            $res = DB::table('danh_sach_mack')
                ->where('danh_sach_mack.mack', 'like', '%' . $mack . '%')
                ->leftJoin('cong_ty_tong_quan', 'danh_sach_mack.mack', '=', 'cong_ty_tong_quan.mack')
                ->take($limit)
                ->addSelect('danh_sach_mack.mack')
                ->addSelect('danh_sach_mack.nhom')
                ->addSelect('danh_sach_mack.nganh')
                ->addSelect('cong_ty_tong_quan.ten_cong_ty')
                ->get();
        }
        return response()->json($res);
    }

    public function getAllMack()
    {
        $data_return = DB::table('danh_sach_mack')
            ->leftJoin('cong_ty_tong_quan', 'danh_sach_mack.mack', '=', 'cong_ty_tong_quan.mack')
            ->addSelect('danh_sach_mack.mack')
            ->addSelect('danh_sach_mack.nhom')
            ->addSelect('danh_sach_mack.nganh')
            ->addSelect('danh_sach_mack.san')
            ->addSelect('danh_sach_mack.nganh_hep')
            ->addSelect('cong_ty_tong_quan.ten_cong_ty')
            ->where(DB::raw('LENGTH(danh_sach_mack.mack)'), '3')
            ->get();
        foreach ($data_return as $row) {
            if ($row->nganh == "")
                $row->nganh = $row->nganh_hep;
        }
        return $data_return;
    }

    public function getDataOrderBoard(Request $req)
    {
        $mack = strtoupper($req->input("mack"));
        $nhom = DB::table("danh_sach_mack")
            ->addSelect("nhom")
            ->where("mack", $mack)
            ->first();
        $nhom = $nhom->nhom;
        $data_tong_quan = DB::table('cong_ty_tong_quan')
            ->where('mack', '=', $req->input('mack'))
            ->addSelect("ten_cong_ty")
            ->addSelect("san_niem_yet")
            ->addSelect("dia_chi_tru_so")
            ->addSelect("so_luong_nhan_su")
            ->addSelect("ma_nganh_icb")
            ->addSelect("ten_nganh_icb")
            ->addSelect("cap_nganh_icb")
            ->first();
        $data_tong_quan = json_decode(json_encode($data_tong_quan), true);
        $data_capital = DB::table("capital")
            ->addSelect("mack")
            ->addSelect("thoigian")
            ->addSelect(DB::Raw('von_dieu_le / 10000 as von_dieu_le'))
            ->where("mack", $mack)
            ->first();
        $data_mapping_nonbank = [
            "dia_chi_tru_so" => [
                "prefix" => "Địa chỉ trụ sở",
                "subfix" => "",
            ],
            "so_luong_nhan_su" => [
                "prefix" => "Số lượng nhân sự",
                "subfix" => "",
            ],
            "ma_nganh_icb" => [
                "prefix" => "Mã ngành ICB",
                "subfix" => "",
            ],
            "ten_nganh_icb" => [
                "prefix" => "Tên ngành ICB",
                "subfix" => "",
            ],
            "cap_nganh_icb" => [
                "prefix" => "Cấp ngành ICB",
                "subfix" => "",
            ],
            "von_dieu_le" => [
                "prefix" => "Vốn điều lệ",
                "subfix" => "tỷ VNĐ",
            ],
            "roa_ttm" => [
                "prefix" => "ROA",
                "subfix" => "%",
            ],
            "roe_ttm" => [
                "prefix" => "ROE",
                "subfix" => "%",
            ],
            "roic_ttm" => [
                "prefix" => "ROIC",
                "subfix" => "%",
            ],
        ];
        $data_mapping_bank = [
            "dia_chi_tru_so" => [
                "prefix" => "Địa chỉ trụ sở",
                "subfix" => "",
            ],
            "so_luong_nhan_su" => [
                "prefix" => "Số lượng nhân sự",
                "subfix" => "",
            ],
            "ma_nganh_icb" => [
                "prefix" => "Mã ngành ICB",
                "subfix" => "",
            ],
            "ten_nganh_icb" => [
                "prefix" => "Tên ngành ICB",
                "subfix" => "",
            ],
            "cap_nganh_icb" => [
                "prefix" => "Cấp ngành ICB",
                "subfix" => "",
            ],
            "von_dieu_le" => [
                "prefix" => "Vốn điều lệ",
                "subfix" => "tỷ VNĐ",
            ],
            "roaa_ttm" => [
                "prefix" => "ROAA",
                "subfix" => "%",
            ],
            "roea" =>  [
                "prefix" => "ROEA",
                "subfix" => "%",
            ],
            "nim_ttm" => [
                "prefix" => "NIM",
                "subfix" => "%",
            ],
        ];
        $data_mapping_stock = [
            "dia_chi_tru_so" => [
                "prefix" => "Địa chỉ trụ sở",
                "subfix" => "",
            ],
            "so_luong_nhan_su" => [
                "prefix" => "Số lượng nhân sự",
                "subfix" => "",
            ],
            "ma_nganh_icb" => [
                "prefix" => "Mã ngành ICB",
                "subfix" => "",
            ],
            "ten_nganh_icb" => [
                "prefix" => "Tên ngành ICB",
                "subfix" => "",
            ],
            "cap_nganh_icb" => [
                "prefix" => "Cấp ngành ICB",
                "subfix" => "",
            ],
            "von_dieu_le" => [
                "prefix" => "Vốn điều lệ",
                "subfix" => "tỷ VNĐ",
            ],
            "roaa" => [
                "prefix" => "ROAA",
                "subfix" => "%",
            ],
            "roea" =>  [
                "prefix" => "ROEA",
                "subfix" => "%",
            ],
        ];
        $data_mapping_insurance = [
            "dia_chi_tru_so" => [
                "prefix" => "Địa chỉ trụ sở",
                "subfix" => "",
            ],
            "so_luong_nhan_su" => [
                "prefix" => "Số lượng nhân sự",
                "subfix" => "",
            ],
            "ma_nganh_icb" => [
                "prefix" => "Mã ngành ICB",
                "subfix" => "",
            ],
            "ten_nganh_icb" => [
                "prefix" => "Tên ngành ICB",
                "subfix" => "",
            ],
            "cap_nganh_icb" => [
                "prefix" => "Cấp ngành ICB",
                "subfix" => "",
            ],
            "von_dieu_le" => [
                "prefix" => "Vốn điều lệ",
                "subfix" => "tỷ VNĐ",
            ],
            "roa_ttm" => [
                "prefix" => "ROAA",
                "subfix" => "%",
            ],
            "roe_ttm" => [
                "prefix" => "ROEA",
                "subfix" => "%",
            ],
            "roic_ttm" => [
                "prefix" => "ROIC",
                "subfix" => "%",
            ],
        ];
        try {
            $data_tong_quan["von_dieu_le"] = $data_tong_quan["von_hoa"] = $data_capital->von_dieu_le;
        } catch (Exception $e) {
            $data_tong_quan["von_dieu_le"] = $data_tong_quan["von_hoa"] = 0;
        }
        $data_tong_quan["von_dieu_le"] = $data_tong_quan["von_dieu_le"] * 10;
        switch ($nhom) {
            case "nonbank":
                $data_tong_quan = array_merge($data_tong_quan, $this->getDataCommonNonbank($req->input('mack')));
                $data_label = $data_mapping_nonbank;
                break;
            case "bank":
                $data_tong_quan = array_merge($data_tong_quan, $this->getDataCommonBank($req->input('mack')));
                $data_label = $data_mapping_bank;
                break;
            case "stock":
                $data_tong_quan = array_merge($data_tong_quan, $this->getDataCommonStock($req->input('mack')));
                $data_label = $data_mapping_stock;
                break;
            case "insurance":
                $data_tong_quan = array_merge($data_tong_quan, $this->getDataCommonInsurance($req->input('mack')));
                $data_label = $data_mapping_insurance;
                break;
            default:
                return "";
                break;
        }
        return [
            "mack" => $mack,
            "value" => $data_tong_quan,
            "label" => $data_label,
        ];
    }

    public function getDataCommonNonbank($mack)
    {
        $mack = strtoupper($mack);
        $res = DB::table("compare_nonbank")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('bvps'))
            ->addSelect(DB::raw('roa_ttm'))
            ->addSelect(DB::raw('roe_ttm'))
            ->addSelect(DB::raw('roic_ttm'))
            ->first();
        $res = json_decode(json_encode($res), true);
        if($res){
            return [
                "eps" => round($res['eps'], 1),
                "bvps" => round($res['bvps'], 1),
                "roa_ttm" => round($res['roa_ttm'], 1),
                "roe_ttm" =>  round($res['roe_ttm'], 1),
                "roic_ttm" => round($res['roic_ttm'], 1),
            ];
        }else{
            return [
                "eps" => 0,
                "bvps" => 0,
                "roa_ttm" => 0,
                "roe_ttm" => 0,
                "roic_ttm" => 0,
            ];
        }
    }

    public function getDataCommonBank($mack)
    {
        $mack = strtoupper($mack);
        $res = DB::table("compare_bank")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('gia_tri_so_sach as bvps'))
            ->addSelect(DB::raw('roaa_ttm'))
            ->addSelect(DB::raw('roea'))
            ->addSelect(DB::raw('nim_ttm'))
            ->first();
        $res = json_decode(json_encode($res), true);
        if($res){
            return [
                "eps" => round($res['eps'], 1),
                "bvps" => round($res['bvps'], 1),
                "roaa_ttm" => round($res['roaa_ttm'], 1),
                "roea" =>  round($res['roea'], 1),
                "nim_ttm" => round($res['nim_ttm'], 1),
            ];
        }else{
            return [
                "eps" => 0,
                "bvps" => 0,
                "roaa_ttm" => 0,
                "roea" => 0,
                "nim_ttm" => 0,
            ];
        }
    }

    public function getDataCommonStock($mack)
    {
        $mack = strtoupper($mack);
        $res = DB::table("compare_stock")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('bvps'))
            ->addSelect(DB::raw('roaa'))
            ->addSelect(DB::raw('roea'))
            ->first();
        $res = json_decode(json_encode($res), true);
        if($res){
            return [
                "eps" => round($res['eps'], 1),
                "bvps" => round($res['bvps'], 1),
                "roaa" => round($res['roaa'], 1),
                "roea" =>  round($res['roea'], 1),
            ];
        }else{
            return [
                "eps" => 0,
                "bvps" => 0,
                "roa" => 0,
                "roaa" => 0,
                "roea" => 0,
            ];
        }
    }

    public function getDataCommonInsurance($mack)
    {
        $mack = strtoupper($mack);
        $res = DB::table("compare_insurance")
            ->where("mack", $mack)
            ->addSelect(DB::raw('eps'))
            ->addSelect(DB::raw('bvps'))
            ->addSelect(DB::raw('roa_ttm'))
            ->addSelect(DB::raw('roe_ttm'))
            ->addSelect(DB::raw('roic_ttm'))
            ->first();
        $res = json_decode(json_encode($res), true);
        if($res){
            return [
                "eps" => round($res['eps'], 1),
                "bvps" => round($res['bvps'], 1),
                "roa_ttm" => round($res['roa_ttm'], 1),
                "roe_ttm" =>  round($res['roe_ttm'], 1),
                "roic_ttm" => round($res['roic_ttm'], 1),
            ];
        }else{
            return [
                "eps" => 0,
                "bvps" => 0,
                "roa_ttm" => 0,
                "roe_ttm" => 0,
                "roic_ttm" => 0,
            ];
        }
    }
}
