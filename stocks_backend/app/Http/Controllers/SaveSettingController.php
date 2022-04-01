<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaveSettingController extends Controller
{
    public function getALL()
    {
        $user = JWTAuth::user();
        $list_data = DB::table('save_settings')
            ->addSelect('key')
            ->addSelect('value')
            ->where('users_id', $user->id)
            ->get();
        return response()->json($list_data, 200);
    }

    public function backupOldData(Request $request)
    {
        $list_item = $request->list_item;
        if(count($list_item) == 0){
            return response()->json(['error_code' => 'no_data'], 422);
        }
        $user = JWTAuth::user();
        for ($i=0; $i < count($list_item); $i++) { 
            $list_item[$i]['users_id'] = $user->id;
        }
        // dd($list_item[4]["value"]);
        // dd($list_item);
        DB::table('save_settings')->insert($list_item);
        return response()->json($list_item, 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required',
        ]);
        $user = JWTAuth::user();
        $check_exists = DB::table('save_settings')
            ->where('users_id', $user->id)
            ->where('key',trim($request->key))
            ->get();
        if (count($check_exists) > 0) {
            $item_edit = [
                "key" => trim($request->key),
                "value" => trim($request->value)
            ];
            DB::table('save_settings')
                ->where('users_id', $user->id)
                ->where('key',trim($request->key))
                ->update($item_edit);
            return response()->json([
                "key" => trim($request->key),
                "value" => trim($request->value)
            ], 200);
        } else {
            $request->validate([
                'key' => 'required',
            ]);
            $user = JWTAuth::user();
            $new_item = [
                "users_id" => $user->id,
                "key" => trim($request->key),
                "value" => trim($request->value),
            ];
            $last_insert_id = DB::table('save_settings')->insertGetId($new_item);
            $new_item = array_merge(['id' => $last_insert_id], $new_item);
            return response()->json($new_item, 201);
        }
    }

    public function destroy(Request $request)
    {
        $user = JWTAuth::user();
        DB::table('save_settings')
            ->where('users_id', $user->id)
            ->where('key', $request->key)
            ->delete();
        return response(null, 204);
    }
}
