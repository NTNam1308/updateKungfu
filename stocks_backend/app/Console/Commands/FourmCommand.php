<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FourmController;

class FourmCommand extends Command
{
    protected $signature = 'fourm:generate {--type=}{--quarter=}{--mack=}';

    protected $description = 'Generate fourm point';

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
        $controller = new FourmController();
        if($quarter == "all"){
            $controller->fourmGenerateAll($type,$mack);
        }else{
            $controller->fourmGenerateNewQuarter($type,$mack);
        }
        return 0;
    }
}
