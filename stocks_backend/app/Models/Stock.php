<?php

namespace App\Models;
use App\Models\UserStock;
use Illuminate\Support\Facades\DB;

class Stock
{
    private $stock_list = NULL;
    
    public static function query() {
        return DB::connection('stocks_backend_pgsql')->table('stock_list'); 
    }   
}
