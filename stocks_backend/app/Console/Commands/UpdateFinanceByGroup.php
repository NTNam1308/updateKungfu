<?php

namespace App\Console\Commands;

// Helper
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Custom
use App\Http\Controllers\StocksController;
use App\Notifications\CreateNotification;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserStock;

class UpdateFinanceByGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:finance-by  {--q=}{--y=}{--g=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            $stockControler = new StocksController();
            $allStocks = Stock::query()->whereRaw('LENGTH(stockcode) < 4')->pluck('stockcode')->toArray();
            $quarter = $this->option('q');
            $year = $this->option('y');
            $stringTime = "Q" . $quarter . " " . $year; 

            $this->info("Kiểm tra tất cả mã stock, lấy mã stock có báo cáo tài chính quý mới.");
            $listStockHasQuarterNew = [];
            $count_stocks = 0;
            // Kiểm tra tất cả mã stock, lấy mã stock có báo cáo tài chính quý mới.
            foreach( $allStocks as $item_stock ) {
                if( $stockControler->checkQuarterByMack($item_stock) == 1 ) {
                    $count_stocks ++;
                    echo $count_stocks . ",";
                    $listStockHasQuarterNew[] = $item_stock;
                }
            }
            echo "\n";
            $this->info("Tổng ". count($allStocks) . " mã stocks có: " . $count_stocks . 
            " đã có báo cáo tài chính quý " . $quarter . " năm ". $year);
            echo "\n";
            
            $listStockYetUpdate = [];
            $listUserIdIsYetUpdate = [];

            foreach( $listStockHasQuarterNew as $item_stock ) {
                $notHas = UserStock::where('stockcode', $item_stock)
                    ->where( 'is_delete', 0 )
                    ->where(function ($query) use ( $stringTime ) {
                        $query->where('finance_updated_at', '<>' , $stringTime )->orWhereNull('finance_updated_at', null);
                    })
                    ->pluck('user_id')->toArray();
                if( count( $notHas ) > 0 ) {
                    $listStockYetUpdate[] = $item_stock;
                    $listUserIdIsYetUpdate = array_merge($listUserIdIsYetUpdate, $notHas ); 
                }
            }

            // Tạo thông báo vào DB
            $this->createNotifyFinance( $listStockYetUpdate, array_unique( $listUserIdIsYetUpdate ) );
            echo "\nThành công. \n";
            return 0;
            
        } catch(\Exception $e){
            echo "\n có lỗi xảy ra. \n";
            echo $e;
            return 0;
        }
    }

    public function createNotifyFinance( $listStockYetUpdate, $listUserIdIsYetUpdate ) {
        try {
            DB::beginTransaction();

            $quarter = $this->option('q');
            $year = $this->option('y');
            $group = $this->option('g');
            $stringTime = "Q" . $quarter . " " . $year; 
            
            $listStock = Stock::query()->whereIn( 'stockcode' , $listStockYetUpdate )->where( 'nhom', $group )->orderBy('stockcode', 'ASC')->pluck('stockcode')->toArray();
            $totalStock = count($listStock);
            if( count( $listStock ) == 0 ) {  $this->info("Có {$totalStock} mã stock"); return 0; }
            $companyNames = Stock::query()->whereIn('stockcode', $listStock )->orderBy('stockcode', 'ASC')->pluck('ten')->toArray();
            $messageNews = (object) [];
            $messageUser = (object) [];
            
            // Lấy group cuối cùng của thông báo của user
            $groupOfUser = new \stdClass();
            foreach( $listUserIdIsYetUpdate as $user_id ) {
                $group = User::find($user_id)->notifications()->orderBy('group', 'DESC')->pluck('group')->first();
                if (!empty($group) ) {  $groupOfUser->$user_id = $group; } // thêm vào objec $groupOfUser
            }
            
            $this->info("Tạo thông báo cho : {$totalStock} mã stock");
            $progressBarNotfify = $this->output->createProgressBar($totalStock); // create pregress notify
            // Tạo tất cả thông báo cho $listStock
            for($index_stock = 0; $index_stock < count($listStock); $index_stock ++ ) {
                // Tạo dữ liệu
                $result = (object) [
                    'finance' => (object) [
                        'stockcode' => $listStock[$index_stock],
                        'company_name' => $companyNames[$index_stock],
                        'quarter' => $quarter,
                        'year' => $year,
                    ],
                    'news' => $messageNews,
                    'user' => $messageUser,
                ];

                $created_at = Carbon::now();
                $type_notify = 'finance';
                $group_first = 1;

                $userIdsOfStock = UserStock::where('stockcode', $listStock[$index_stock] )
                    ->where( 'is_delete', 0 )
                    ->where(function ($query) use ( $stringTime ) {
                        $query->where('finance_updated_at', '<>' , $stringTime )->orWhereNull('finance_updated_at', null);
                    })
                    ->pluck('user_id')->toArray();

                // Tạo thông báo
                foreach( $userIdsOfStock as $userId ) {
                    $toArrayCheckEmpty = (array)$groupOfUser; // cho object vào mảng để kiểm tra rỗng
                    if ( count( $toArrayCheckEmpty ) > 0 ) {
                        if( !empty( $groupOfUser->{(string)$userId } ) ) { // nếu có dữ liệu tạo thông báo, group cộng 1
                            User::Find($userId)->notify( new CreateNotification( json_encode($result),  $groupOfUser->{(string)$userId } + 1, $created_at, $type_notify ) );
                        }
                    } else { // tạo thông báo, group đầu tiên bẳng 1
                        User::Find($userId)->notify( new CreateNotification( json_encode($result), $group_first, $created_at, $type_notify ) );
                    }
                }
                $progressBarNotfify->advance(); // running progres notify
            }
      
            echo "\n";
            $this->info("Cập nhật cột finance_updated_at trong bảng user_stocks.");
            $progressBarUserStock = $this->output->createProgressBar(count($listStock)); // create pregress user_stocks

            foreach( $listStock as $item_stock ) {
                $userIdsOfStock = UserStock::where( 'stockcode', $item_stock)->pluck('user_id')->toArray();
                if( count($userIdsOfStock) > 0 ) {
                    UserStock::where('stockcode', $item_stock )->whereIn( 'user_id', $userIdsOfStock )
                        ->update([ 
                            'finance_updated_at' => $stringTime, 
                        ]);
                    $progressBarUserStock->advance(); // running progres user_stocks
                }
            }

            DB::commit();
            return 0;

        } catch(\Exception $e){
            DB::rollBack();
            echo "\n có lỗi xảy ra. \n";
            echo $e;
            return 0;
        }

    }
}
