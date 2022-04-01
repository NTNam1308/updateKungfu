<?php

namespace App\Console\Commands;

// Helper
use Illuminate\Console\Command;
use App\Notifications\SendMaintenanceNotification;
use Kutia\Larafirebase\Facades\Larafirebase;
use Illuminate\Support\Facades\DB;
use Notification;

// Custom
use App\Models\User;

class NofifyMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:maintenance {--status=} {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notify maintenance';

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
        $status = $this->option('status');
        $from = $this->option('from');
        $to = $this->option('to');

      
        if($status != 'on' && $status != 'off' ) {
            echo "\n";
            $this->info("Vui lòng nhập status: --status=on hoặc --status=off");
            echo "\n";
            dd();
        }

        $title = '';
        $message = '';
        if($status == 'on') {
            if( empty($from ) || empty($to) ) {
                $this->info("Vui lòng nhập thời gian");
                $this->info("php artisan notify:mantenance --status=on --from=9 --to=21");
                $this->info("title is: từ 9giờ tới 10giờ");
                dd();
            }
            $title = 'Thông báo bảo trì';
            $message = 'Từ ' . $from . 'giờ tới ' . $to . 'giờ';
        }
        else if($status == 'off') {
            $title = 'Thông báo bảo trì thành công';
            $message = '';
        }
        
        $fcmTokensWeb = User::whereNotNull('fcm_token_web')->pluck('fcm_token_web')->toArray();
     
        $body = (object) [
            'message' => $message,
            'status' => $status,
            'from' => $from,
            'to' => $to
        ];
        Larafirebase::withTitle($title)->withBody($body)->sendMessage($fcmTokensWeb);
        $checkExists = DB::table('maintenance')->exists();
        if($checkExists == 1) {
            DB::table('maintenance')->update(['status' => $status]);
        } else {
            DB::table('maintenance')->insert(['status' => $status]);
        }
        
        echo "\nThành công \n";
        return 0;
    }
}
