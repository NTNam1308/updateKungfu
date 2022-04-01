<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\CanslimController;
use App\Http\Controllers\FourmController;
use App\Http\Controllers\CompareGenerateController;
use App\Http\Controllers\CapitalGenerateController;

class GenerateController extends Controller
{
    public function generateCanslim(Request $request)
    {
        $mack = $request->input("mack");
        $type_stock = $request->input("type_stock");
        $quarter = $request->input('quarter');
        $canslim = new CanslimController();
        if ($quarter == "all") {
            $canslim->canslimGenerateAll($type_stock, $mack);
            return response()->json([
                'msg' => "Đã gen điểm CANSLIM của các mã " . implode(", ", $mack) . " thành công",
            ], 200);
        } else {
            $canslim->canslimGenerateNewQuarter($type_stock, $mack);
            if ($mack) {
                return response()->json([
                    'msg' => "Đã gen điểm CANSLIM của các mã " . implode(", ", $mack) . " thành công",
                ], 200);
            } else {
                return response()->json([
                    'msg' => "Đã gen điểm CANSLIM của các mã thuộc nhóm " . $type_stock . " thành công",
                ], 200);
            }
        }
    }

    public function generateFourm(Request $request)
    {
        $mack = $request->input("mack");
        $type_stock = $request->input("type_stock");
        $quarter = $request->input('quarter');
        $fourm = new FourmController();
        if ($quarter == "all") {
            $fourm->fourmGenerateAll($type_stock, $mack);
            return response()->json([
                'msg' => "Đã gen điểm 4M của các mã " . implode(", ", $mack) . " thành công",
            ], 200);
        } else {
            $fourm->fourmGenerateNewQuarter($type_stock, $mack);
            if ($mack) {
                return response()->json([
                    'msg' => "Đã gen điểm 4M của các mã " . implode(", ", $mack) . " thành công",
                ], 200);
            } else {
                return response()->json([
                    'msg' => "Đã gen điểm 4M của các mã thuộc nhóm " . $type_stock . " thành công",
                ], 200);
            }
        }
    }

    public function generateCompare(Request $request)
    {
        $mack = $request->input("mack");
        $type_stock = $request->input("type_stock");
        $quarter = $request->input('quarter');
        $compare = new CompareGenerateController();
        $capital = new CapitalGenerateController();

        $compare->compareGenerate($type_stock, $mack);
        $capital->capitalGenerate($type_stock, $mack);
        if ($mack) {
            return response()->json([
                'msg' => "Đã gen SO SÁNH của các mã " . implode(", ", $mack) . " thành công",
            ], 200);
        } else {
            return response()->json([
                'msg' => "Đã gen SO SÁNH của các mã thuộc nhóm " . $type_stock . " thành công",
            ], 200);
        }
    }

    public function generate(Request $request)
    {
        $user = JWTAuth::user();
        $check_admin = false;
        foreach ($user->roles as $row) {
            if ($row->name == "admin" || $row->name == "moderator") {
                $check_admin = true;
            }
        }
        if ($check_admin) {
            $type_gen = $request->input('type_gen');
            switch ($type_gen) {
                case "canslim":
                    return $this->generateCanslim($request);
                    break;
                case "4m":
                    return $this->generateFourm($request);
                    break;
                case "compare":
                    return $this->generateCompare($request);
                    break;
                default:
                    return "";
                    break;
            }
        }
        return response()->json([
            'error' => "Unauthorized",
        ], 401);
    }
}
