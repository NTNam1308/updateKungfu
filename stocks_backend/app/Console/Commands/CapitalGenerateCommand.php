<?php

namespace App\Console\Commands;
use App\Http\Controllers\CapitalGenerateController;

use Illuminate\Console\Command;

class CapitalGenerateCommand extends Command
{
    protected $signature = 'capital:generate  {--type=}{--mack=}';

    protected $description = 'Generate Capital value';

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
        $capital = new CapitalGenerateController();
        $capital->capitalGenerate($type,$mack);
        return 0;
    }
}
