<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppVersion;
use Illuminate\Support\Facades\DB;


class AppVersionController extends Controller
{
    public function index()
    {
        return response()->json(AppVersion::orderBy('id', 'desc')->first()->version);
    }

    public function getAllVersion()
    {
        $appVersion = AppVersion::select("id","version")
                    ->orderby('id','desc')
                    ->get();
        return response()->json($appVersion);
    }

    public function store(Request $request)
    {
        $request->validate([
            'version' => 'required|unique:app_versions|max:20',
        ]);
        $appVersion = AppVersion::create([
            'version' => $request->version
        ]);
        return response()->json($appVersion);
    }

    public function update(Request $request){
        $request->validate([
            'version' => 'required|unique:app_versions|max:20',
        ]);
        $check = DB::table('app_versions')
                ->where('version', $request->version)
                ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already App Version",
            ], 422);
        }
        $item_update = ["version" => $request->version];
        $appVersion = DB::table('app_versions')
                    ->where('id', $request->id)
                    ->update($item_update);
        return response()->json([
            "id" =>  $request->id,
            "version" => $request->version
        ]);
    }

    public function destroy($id){
        DB::table('app_versions')
            ->where('id', $id)
            ->delete();
        return response(null,204);
    }
 
}
