<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use \Exception;
use Mail;
use Datetime;
use Tymon\JWTAuth\Facades\JWTAuth;

// set_error_handler(function () {
//     throw new Exception('Ach!');
// });

class CanslimFourmController extends Controller
{
    public function canslimFourmAll(Request $req)
    {
        $table_4m = DB::table('fourm')
            ->join('danh_sach_mack', 'fourm.mack', '=', 'danh_sach_mack.mack')
            ->join('capital', 'fourm.mack', '=', 'capital.mack')
            ->addSelect(DB::Raw('danh_sach_mack.mack'))
            ->addSelect(DB::Raw('fourm.thoigian'))
            ->addSelect(DB::Raw('fourm.tong_diem as diem_4m'))
            ->addSelect(DB::Raw('0 as diem_canslim'))
            ->addSelect(DB::Raw('danh_sach_mack.san'))
            ->addSelect(DB::Raw('capital.von_dieu_le as slcp'))
            ->where('fourm.is_last', true);
        $table_canslim = DB::table('canslim')
            ->join('danh_sach_mack', 'canslim.mack', '=', 'danh_sach_mack.mack')
            ->join('capital', 'canslim.mack', '=', 'capital.mack')
            ->addSelect(DB::Raw('danh_sach_mack.mack'))
            ->addSelect(DB::Raw('canslim.thoigian'))
            ->addSelect(DB::Raw('0 as diem_4m'))
            ->addSelect(DB::Raw('canslim.tong_diem as diem_canslim'))
            ->addSelect(DB::Raw('danh_sach_mack.san'))
            ->addSelect(DB::Raw('capital.von_dieu_le as slcp'))
            ->where('canslim.is_last', true);
        $data_return = DB::query()->fromSub(
            $table_4m
                ->unionAll(
                    $table_canslim
                ),
            'm'
        )
            ->addSelect(DB::Raw('mack'))
            ->addSelect(DB::Raw('SUM(diem_4m) AS diem_4m'))
            ->addSelect(DB::Raw('SUM(diem_canslim) AS diem_canslim'))
            ->addSelect(DB::Raw('san'))
            ->addSelect(DB::Raw('slcp * 100 as slcp'))
            ->groupBy(['mack', 'san', 'slcp'])
            ->get();
        // return $data_return;
        // $data_return = $res;
        // $data_return = DB::table('canslim')
        //     ->leftJoin('fourm', 'fourm.mack', '=', 'canslim.mack')
        //     ->join('danh_sach_mack', 'fourm.mack', '=', 'danh_sach_mack.mack')
        //     ->addSelect(DB::raw('canslim.mack as mack'))
        //     ->addSelect(DB::raw('IFNULL( `canslim`.`tong_diem` , 0 ) as diem_canslim'))
        //     ->addSelect(DB::Raw('IFNULL( `fourm`.`tong_diem` , 0 ) as diem_4m'))
        //     ->addSelect(DB::Raw('danh_sach_mack.nganh'))
        //     ->addSelect(DB::Raw('danh_sach_mack.nganh_hep'))
        //     ->addSelect(DB::Raw('danh_sach_mack.san'))
        //     ->where('fourm.is_last', true)
        //     ->where('canslim.is_last', true)
        //     ->orderBy('fourm.mack')
        //     ->get();
        // foreach ($data_return as $row) {
        //     if ($row->nganh == "")
        //         $row->nganh = $row->nganh_hep;
        // }

        $data_nganh = DB::connection('stocks_backend_pgsql')
            ->table('stock_list')
            ->addSelect("stockcode")
            ->addSelect("nganh")
            ->get();
            
        $data_avg50 = DB::connection('pgsql')
            ->table('stock_eod')
            ->where('tradingdate', function ($query) {
                $query->select(DB::raw('MAX(tradingdate)'))
                    ->from('index_eod')
                    ->where('stockcode', 'VNINDEX');
            })
            ->where('avgvol50', '<>', 0)
            ->addSelect('stockcode')
            ->addSelect('avgvol50 as avg')
            ->get();
        $data_avg50 = json_decode(json_encode($data_avg50), true);
        $data_nganh = json_decode(json_encode($data_nganh), true);
        foreach ($data_avg50 as $key => $value) {
            $data_avg50[$value['stockcode']] = $value;
            unset($data_avg50[$key]);
        }
        foreach ($data_nganh as $key => $value) {
            $data_nganh[$value['stockcode']] = $value;
            unset($data_nganh[$key]);
        }
        foreach ($data_return as $row) {
            $row->diem_4m = (float) $row->diem_4m;
            $row->diem_canslim = (float) $row->diem_canslim;
            if (isset($data_avg50[$row->mack])) {
                $row->avg50 = $data_avg50[$row->mack]['avg'];
            } else {
                $row->avg50 = 0;
            }
            if (isset($data_nganh[$row->mack])) {
                $row->nganh = $data_nganh[$row->mack]['nganh'];
            } else {
                $row->nganh = "";
            }
        }
        return $data_return;
    }
    public function canslimFourmByMack(Request $req)
    {
        $table_4m = DB::table('fourm')
            ->join('danh_sach_mack', 'fourm.mack', '=', 'danh_sach_mack.mack')
            ->join('capital', 'fourm.mack', '=', 'capital.mack')
            ->addSelect(DB::Raw('danh_sach_mack.mack'))
            ->addSelect(DB::Raw('fourm.thoigian'))
            ->addSelect(DB::Raw('fourm.tong_diem as diem_4m'))
            ->addSelect(DB::Raw('0 as diem_canslim'))
            ->addSelect(DB::Raw('danh_sach_mack.nganh'))
            ->addSelect(DB::Raw('danh_sach_mack.nganh_hep'))
            ->addSelect(DB::Raw('danh_sach_mack.san'))
            ->addSelect(DB::Raw('capital.von_dieu_le as slcp'))
            ->whereIn('fourm.mack', $req->mack)
            ->whereIn('danh_sach_mack.mack', $req->mack)
            ->where('fourm.is_last', true);
        $table_canslim = DB::table('canslim')
            ->join('danh_sach_mack', 'canslim.mack', '=', 'danh_sach_mack.mack')
            ->join('capital', 'canslim.mack', '=', 'capital.mack')
            ->addSelect(DB::Raw('danh_sach_mack.mack'))
            ->addSelect(DB::Raw('canslim.thoigian'))
            ->addSelect(DB::Raw('0 as diem_4m'))
            ->addSelect(DB::Raw('canslim.tong_diem as diem_canslim'))
            ->addSelect(DB::Raw('danh_sach_mack.nganh'))
            ->addSelect(DB::Raw('danh_sach_mack.nganh_hep'))
            ->addSelect(DB::Raw('danh_sach_mack.san'))
            ->addSelect(DB::Raw('capital.von_dieu_le as slcp'))
            ->whereIn('canslim.mack', $req->mack)
            ->whereIn('danh_sach_mack.mack', $req->mack)
            ->where('canslim.is_last', true);
        $data_return = DB::query()->fromSub(
            $table_4m
                ->unionAll(
                    $table_canslim
                ),
            'm'
        )
            ->addSelect(DB::Raw('mack'))
            ->addSelect(DB::Raw('SUM(diem_4m) AS diem_4m'))
            ->addSelect(DB::Raw('SUM(diem_canslim) AS diem_canslim'))
            ->addSelect(DB::Raw('nganh'))
            ->addSelect(DB::Raw('nganh_hep'))
            ->addSelect(DB::Raw('san'))
            ->addSelect(DB::Raw('slcp * 100 as slcp'))
            ->groupBy(['mack', 'nganh', 'nganh_hep', 'san', 'slcp'])
            ->get();
        $data_avg50 = DB::connection('pgsql')
            ->table('stock_eod')
            ->where('tradingdate', function ($query) {
                $query->select(DB::raw('MAX(tradingdate)'))
                    ->from('index_eod')
                    ->where('stockcode', 'VNINDEX');
            })
            ->where('avgvol50', '<>', 0)
            ->addSelect('stockcode')
            ->addSelect('avgvol50 as avg')
            ->get();
        $data_avg50 = json_decode(json_encode($data_avg50), true);
        foreach ($data_avg50 as $key => $value) {
            $data_avg50[$value['stockcode']] = $value;
            unset($data_avg50[$key]);
        }
        foreach ($data_return as $row) {
            $row->diem_4m = (float) $row->diem_4m;
            $row->diem_canslim = (float) $row->diem_canslim;
            if (isset($data_avg50[$row->mack])) {
                $row->avg50 = $data_avg50[$row->mack]['avg'];
            } else {
                $row->avg50 = 0;
            }
        }
        return $data_return;
    }

    public function countStock(Request $req)
    {
        $data_return = DB::table("capital")
            ->addSelect("mack")
            ->addSelect(DB::Raw('capital.von_dieu_le*100 as slcp'))
            ->get();
        foreach ($data_return as $key => $value) {
            $data_return[$value->mack] = $value->slcp;
            unset($data_return[$key]);
        }
        return $data_return;
    }
    public function countStockByMack(Request $req)
    {
        $data_return = DB::table("capital")
            ->addSelect("mack")
            ->whereIn('mack', $req->mack)
            ->addSelect(DB::Raw('capital.von_dieu_le*100 as slcp'))
            ->get();
        foreach ($data_return as $key => $value) {
            $data_return[$value->mack] = $value->slcp;
            unset($data_return[$key]);
        }
        return $data_return;
    }
    public function getDataCompareAll()
    {
        $data_nonbank = DB::table("compare_nonbank")->get();
        $data_bank = DB::table("compare_bank")->get();
        $data_stock = DB::table("compare_stock")->get();
        $data_insurance = DB::table("compare_insurance")->get();
        
        $data_return = [
            "nonbank" => $this->archiveArray($data_nonbank),
            "bank" => $this->archiveArray($data_bank),
            "stock" => $this->archiveArray($data_stock),
            "insurance" => $this->archiveArray($data_insurance),
        ];
        return $data_return;
    }
    protected static function archiveArray($arr)
    {
        $arr = json_decode(json_encode($arr), true);
        $arr_return = [];
        for ($i = 0; $i < count($arr[0]); $i++) {
            $arr_return[array_keys($arr[0])[$i]] = array_column($arr, array_keys($arr[0])[$i]);
        }
        return $arr_return;
    }
    public function index()
    {
        $user = JWTAuth::user();
        $list_data = DB::table('save_filter_group')
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
        $new_item = [
            "users_id" => $user->id,
            "label" => trim($request->label),
            "content" => $request->content,
        ];
        $last_insert_id = DB::table('save_filter_group')->insertGetId($new_item);
        $new_item = array_merge(['id' => $last_insert_id], $new_item);
        return response()->json($new_item, 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'label' => 'required',
        ]);
        $user = JWTAuth::user();
        $item_edit = [
            "label" => trim($request->label),
            "content" => $request->content
        ];
        DB::table('save_filter_group')
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
        DB::table('save_filter_group')
            ->where('users_id', $user->id)
            ->where('id', $id)
            ->delete();
        return response(null, 204);
    }
}
