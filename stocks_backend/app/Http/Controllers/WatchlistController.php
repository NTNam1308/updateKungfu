<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Models\MyWatchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class WatchlistController extends Controller
{
    public function index(Request $request)
    {
        $list_data_item = Watchlist::where('user_id', $request->user_id)->orderBy('mack')
            ->get();
        return response()->json($list_data_item, 200);
    }
    public function macksByMyWatchlist($id)
    {
        $list_data_item = MyWatchlist::find($id)->macks()->orderBy('id', "asc")
            ->get();
        $list_data_item->makeHidden(['user_id']);
        return response()->json($list_data_item, 200);
    }

    public function indexDelete(Request $request)
    {
        $list_data_item = DB::table('watchlists')->where('user_id', $request->user_id)->orderBy('mack')->get();
        return response()->json($list_data_item, 200);
    }

    public function store(Request $request)
    {
        $user = JWTAuth::user();
        $request->validate([
            'mack' => 'required',
            'my_watchlist_id' => 'required',
        ]);

        // check my watchist of user
        $check_my_watchist_of_user = $user->myWatchlist()->whereId($request->my_watchlist_id)->pluck('id')->toArray();
        if( count($check_my_watchist_of_user) == 0 ) {
            return response()->json(['error_code' => 'my_watch_list_does_not_exist'], 422);
        }
        Watchlist::where('my_watchlist_id', $request->my_watchlist_id)->get();
        
        
        // check unique stock
        $macks_by_my_watchlist = MyWatchlist::find($request->my_watchlist_id)->macks()->pluck('mack')->toArray();
        if ( in_array( $request->mack, $macks_by_my_watchlist) ) {
            return response()->json(['error_code' => 'watch_list_mack_exist'], 422);
        } else {
            $last_insert_id = Watchlist::create([
                "mack" => $request->mack,
                "my_watchlist_id" => $request->my_watchlist_id,
                "user_id" => $user->id
            ]);
            
            // attach stock
            $user->attachStocks( [$request->mack] );
            
            unset($last_insert_id->user_id);
            return response()->json($last_insert_id, 201);
        }
    }

    public function addManyRow(Request $request)
    {
        $user = JWTAuth::user();
        $request->validate([
            'mack' => 'required',
            'my_watchlist_id' => 'required',
        ]);
        $list_mack = $request->mack;

        if(count($list_mack) > 50){
            return response()->json(['error_code' => 'too_many_items'], 422);
        }
        $user = JWTAuth::user();
        $list_mack_exists = DB::table('watchlists')
        ->addSelect('mack')
        ->distinct()
        ->where("my_watchlist_id", $request->my_watchlist_id)
        ->pluck('mack')
        ->toArray();

        $list_mack_not_exists = [];
        foreach ($list_mack as $mack) {
            if (!in_array($mack, $list_mack_exists)) {
                array_push($list_mack_not_exists, $mack);
            }
        }

        if(count($list_mack_not_exists) == 0){
            return response()->json(['error_code' => 'watch_list_mack_exist'], 422);
        }
        $list_items_insert_db = [];
        foreach ($list_mack_not_exists as $mack) {
            array_push($list_items_insert_db, [
                "mack" => $mack,
                "my_watchlist_id" => $request->my_watchlist_id,
                "user_id" => $user->id
            ]);
        }
        DB::table('watchlists')->insert($list_items_insert_db);
        
        $user->attachStocks( $list_mack_not_exists );
        return "success";
    }

    public function destroy($id)
    {
        $user = JWTAuth::user();
        $mack_name = Watchlist::whereUserId($user->id)->whereId($id)->pluck('mack')->toArray();
        $user->detachStocks( $mack_name );

        Watchlist::where('id', $id)->where("user_id", $user->id)->forceDelete();
        return response(null, 204);
    }

    public function updateIndex(Request $request){
       
        $user = JWTAuth::user();
        Watchlist::whereId($request->current_id)->where('user_id', $user->id)->update([
            "index" =>  $request->index_change_top,
        ]);
        Watchlist::whereId($request->id_index_change_top)->where('user_id', $user->id)->update([
            "index" =>  $request->index_row,
        ]);
        return response()->json(['status' => 'success'] ,200);
    }
    
}
