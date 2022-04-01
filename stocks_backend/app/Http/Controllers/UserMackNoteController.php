<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserMackNote;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserMackNoteController extends Controller
{
    public function index(Request $request)
    {
        $user = JWTAuth::user();
        $result  = UserMackNote::where('user_id', $user->id)->where('mack', $request->mack)->get();
        return response()->json($result->makeHidden(['user_id']));
    }

    public function store(Request $request)
    {
        $user = JWTAuth::user();

        $data = UserMackNote::firstOrCreate([
            "user_id" => $user->id, // check exists
            "mack" => $request->mack, // check exists
        ], [
            "note" => $request->note // no check
        ]);

        return response()->json($data->makeHidden(['user_id']));
    }

    public function update(Request $request, $id)
    {
        $user = JWTAuth::user();
        UserMackNote::find($id)->where('user_id', $user->id)->update([
            "note" => $request->note
        ]);

        $data = UserMackNote::find($id);
        return response()->json($data->makeHidden(['user_id']));
    }
}
