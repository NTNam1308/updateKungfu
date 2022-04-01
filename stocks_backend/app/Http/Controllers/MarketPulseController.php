<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketPulseController extends Controller
{
    public function getContent()
    {
        return response()->json(DB::table('market_pulse')->first());
    }

    public function updateContent(Request $request)
    {
        $content = json_encode($request->input("content"));
        // DB::table('market_pulse')->insert(["content" => $content]);
        DB::table('market_pulse')
            ->update(["content" => $content]);
        return response()->json(["message" => "success"], 200);
    }
}
