<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Controllers\CompareGenerateController;

class CompareGenerateCommand extends Command
{
    protected $signature = 'compare:generate  {--type=}{--mack=}';

    protected $description = 'Generate compare value';

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
        $compare = new  CompareGenerateController();
        $compare -> compareGenerate($type,$mack);
        return 0;
    }
}
