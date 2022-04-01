<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MyWatchlist;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Models\Watchlist;

class MyWatchlistController extends Controller
{
    public function index(MyWatchlist $my_watchlist)
    {
        $user = JWTAuth::user();
        $list_data_category = DB::table('my_watchlists')
            ->addSelect('name')
            ->addSelect('id')
            ->where('user_id', $user->id)
            ->orderBy('id', "asc")
            ->get();
        return response()->json( $list_data_category, 200);
      
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $user = JWTAuth::user();
        $check = DB::table('my_watchlists')
                ->where('name',$request->name)
                ->where('user_id',$user->id)
                ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already name",
            ], 422);
        }
        $new_my_watchlist = [
            "user_id" => $user->id,
            "name" => $request->name
        ];
        $nameMyWachlist = ["name" => $request->name];
        
        $last_insert_id = DB::table('my_watchlists')->insertGetId($new_my_watchlist);
        $merge_id_to_my_watchlist = array_merge(['id' => $last_insert_id], $nameMyWachlist);
        return response()->json($merge_id_to_my_watchlist,201);
    }

 
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $user = JWTAuth::user();
        $check = DB::table('my_watchlists')
                ->where('name',$request->name)
                ->where('user_id',$user->id)
                ->get();
        if(count($check) > 0){
            return response()->json([
                'error' => "Already name",
            ], 422);
        }
        $item_edit_my_watchlist = [
            "user_id" => $user->id,
            "name" => $request->name
        ];
        DB::table('my_watchlists')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update($item_edit_my_watchlist);
        return response()->json([
            "id" =>  $id,
            "name" => $request->name
       ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = JWTAuth::user();

        $arr_mack_in_my_watchlist = Watchlist::whereUserId($user->id)->where('my_watchlist_id', $id)->pluck('mack')->toArray();
        $user->detachStocks( $arr_mack_in_my_watchlist );
        
        DB::table('watchlists')
        ->where('user_id', $user->id)
        ->where('my_watchlist_id', $id)
        ->delete();
        DB::table('my_watchlists')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->delete();
        return response(null, 204);
    }
}
