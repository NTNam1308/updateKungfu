<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Top400StockController;

class Top400StockCommand extends Command
{
    protected $signature = 'top400stock:generate';

    protected $description = 'Generate data Top 400 stock';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $top400Stock = new Top400StockController();
        $top400Stock->generateDataToDB();
        return 0;
    }
}
