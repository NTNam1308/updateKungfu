<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Auth;



class UserLogController extends Controller
{
    public function getReferenceLog(){
        $id = auth()->user()->id;
        $data = DB::table('user_logs')
                    ->select('user_id', 'log_name', 'log', 'log_type', 'status')
                    ->where('user_id', "=", $id)
                    ->where(function($query) {
                        $query->where('log_type', "=", 'reference_success')   
                            ->orWhere('log_type', "=", 'active_reference_success');
                    })
                    ->orderby('id','desc')
                    ->get();
        return response()->json( ['status' => 'success', 'log' => $data], 200);
    }

    public function alertRefernceLog(){
        $id = auth()->user()->id;
        DB::table('user_logs')
        ->where('user_id', "=", $id)
        ->where('status', "=", 'New')
        ->where(function($query) {
            $query->where('log_type', "=", 'reference_success')   
                ->orWhere('log_type', "=", 'active_reference_success');
        })->update(['status' => 'Alert']);
        return response()->json(['status' => 'success']);    
    }

    public function getAllReferenceLog($id){
        $user = User::find($id);
        if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('moderator')) {
            $data = DB::table('user_logs')
                    ->select('log')
                    ->where('log_type', '=', 'reference_success')
                    // ->where(function($query) {
                    //     $query->where('log_type', "=", 'reference_success')   
                    //         ->orWhere('log_type', "=", 'active_reference_success');
                    // })
                    ->orderby('id','desc')
                    ->get();
            $status = 'success';
            $code = 200;
        }else{
            $data = "";
            $status = 'error';
            $code = 401;
        }
        return response()->json(['status' => $status, 'data' => $data], $code);
    }
}
