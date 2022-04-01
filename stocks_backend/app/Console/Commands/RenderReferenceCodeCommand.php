<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class RenderReferenceCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:usersreferencecode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update reference_code for old users';

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
        $users = DB::table('users')
                ->get();
        for($i = 0; $i < count($users); $i++){
            $id = $users[$i]->id;
            do{
                $code1 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 10, 3); 
                $code2 = substr(str_shuffle("0123456789"),0,3); 
                $reference_code = strtoupper($code1.$code2);
            }while(DB::table('users')->where('personal_reference_code',"=",$reference_code)->first());
            DB::table('users')
            ->where('id', $id)
            ->update(['personal_reference_code' => $reference_code]);
        }  
    }
}
