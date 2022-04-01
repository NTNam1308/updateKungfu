<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateMyWatchlistCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:mywatchlist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user_all = User::all();
        foreach($user_all as $user) {
            // tạo watchlist default cho tất cả user hiện tại

            $my_watchlist_default = DB::table("my_watchlists")
            ->where("user_id",  $user->id)
            ->where("name", "Kungfu Watchlist")
            ->value("id");

            if($user->id != $my_watchlist_default) {
                DB::table("my_watchlists")->insert([
                    "name" => "Kungfu Watchlist",
                    "user_id" => $user->id
                ]);
            }

            // cập nhật tất cả user đang có watchlist sang watchlist default
            $my_watchlist_default_reload = DB::table("my_watchlists")
            ->where("user_id",  $user->id)
            ->where("name", "Kungfu Watchlist")
            ->value("id");

            if(count($user->watchlist) > 0 && $user->watchlist()->value("my_watchlist_id") != $my_watchlist_default_reload) {
                foreach($user->watchlist as $user_watchlist) {
                    $update_to_my_watchlist = DB::table("watchlists")
                    ->where("id", $user_watchlist->id)->update([
                        "my_watchlist_id" => $user->myWatchlist()->value("id"),
                    ]);
                }
            }
        }
        echo "\n Thành công. \n";
        return 0;
    }
}
