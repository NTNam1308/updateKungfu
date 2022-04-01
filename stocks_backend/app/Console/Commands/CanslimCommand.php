<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CanslimController;

class CanslimCommand extends Command
{
    protected $signature = 'canslim:generate  {--type=}{--quarter=}{--mack=}';

    protected $description = 'Generate canslim point';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $type = $this->option('type');
        $mack = $this->option('mack');
        if($mack){
            $mack = explode(",",$mack);
        }
        $quarter = $this->option('quarter');
        $canslim = new  CanslimController();
        if($quarter == "all"){
            $canslim->canslimGenerateAll($type,$mack);
        }else{
            $canslim->canslimGenerateNewQuarter($type,$mack);
        }
        return 0;
    }
}
