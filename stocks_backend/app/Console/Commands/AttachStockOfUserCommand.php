<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Watchlist;

class AttachStockOfUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attach:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert all mack unique of user to table user_stocks';

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
     * Chạy lại lệnh nếu dữ liệu chưa đồng bộ. 
     */
    public function handle()
    {
        $allUser = User::all();
        $totalUser = count($allUser);
        $this->info("Attach user_stocks");
        $this->info("Có {$totalUser} user");
        $progressBarUserStock = $this->output->createProgressBar($totalUser); // create pregress user_stocks
        foreach(  $allUser as $user ) { // loop all user
            $user->attachStocks(  Watchlist::whereUserId($user->id)->distinct()->pluck('mack')->toArray() ); // attach to table user_stocks
            $progressBarUserStock->advance(); // running progres user_stocks
        }
        echo "\n Thành công. \n";
        return 0;
    }
}
