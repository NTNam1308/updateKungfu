<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryController extends Controller
{
    public function index()
    {
        $user = JWTAuth::user();
        $list_data_category = DB::table('category')
            ->addSelect('name')
            ->addSelect('id')
            ->where('id_user', $user->id)
            ->get();
        return response()->json( $list_data_category, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $user = JWTAuth::user();
        $check = DB::table('category')
                ->where('name',trim($request->name))
                ->where('id_user',$user->id)
                ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already name",
            ], 422);
        }
        $new_category = [
            "id_user" => $user->id,
            "name" => trim($request->name)
        ];
        $last_insert_id = DB::table('category')->insertGetId($new_category);
        $new_category = array_merge(['id' => $last_insert_id], $new_category);
        return response()->json($new_category, 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $user = JWTAuth::user();
        $check = DB::table('category')
                ->where('name',trim($request->name))
                ->where('id_user',$user->id)
                ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already name",
            ], 422);
        }
        $item_edit_category = [
            "id_user" => $user->id,
            "name" => trim($request->name)
        ];
        DB::table('category')
            ->where('id', $request->id)
            ->where('id_user', $user->id)
            ->update($item_edit_category);
        return response()->json([
            "id" => $request->id,
            "id_user" => $user->id,
            "name" => trim($request->name)
       ], 200);
    }

    public function destroy($id)
    {
        $user = JWTAuth::user();
        DB::table('category')
            ->where('id_user', $user->id)
            ->where('id', $id)
            ->delete();
        DB::table('trading_log')
            ->where('id_user', $user->id)
            ->where('danh_muc', $id)
            ->delete();
        return response(null, 204);
    }
}
