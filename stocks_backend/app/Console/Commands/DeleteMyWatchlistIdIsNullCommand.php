<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteMyWatchlistIdIsNullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watchlist:delete-error';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete my_watchlist_id is null in table watchlists';

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
        try {
            DB::table('watchlists')->whereNull('my_watchlist_id')->delete();
            echo "\nThành công. \n";
            return 0;
        } catch(\Exception $e){
            $this->error('có lỗi xảy ra.!');
            echo $e;
            return 0;
        }
    }
}
