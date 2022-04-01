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

class UpdateFinanceCommand extends Command
{
    public $stringTime = "";
    public $quarter = 0;
    public $year = 0;
    public $requestResult = "";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:finance {--q=}{--y=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update finance';

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
     * Chạy lại lệnh nếu dữ liệu chưa đồng bộ. 
     */
    public function handle()
    {
        try {
            // Validate
            $currentSubOneQuaterGetQuarter = Carbon::now()->subQuarters(1)->quarter;
            $currentSubOneQuaterGetYear = Carbon::now()->subQuarters(1)->year;

            if( empty($this->option('q')) || empty($this->option('y')) ) { // nếu không có --q=, --y=
                $request = $this->ask(
                    "--q=quý --y=năm \n" .
                    "Nhập yes chạy mặc định là: php artisan update:finance --q={$currentSubOneQuaterGetQuarter} --y={$currentSubOneQuaterGetYear}\n".
                    "Nhập no thoát command nhập tùy chọn: php artisan update:finance --q=number --y=number"
                );
                $this->requestResult = $request;
                if($request != "yes") {  echo "Exit"; return; } // exit command
            } else { // nếu có --q=, --y=
                $this->quarter = $this->option('q');
                $this->year = $this->option('y');
                $this->stringTime = "Q" . $this->option('q') . " " . $this->option('y');
            }

            if( $this->requestResult == "yes") { // nếu chọn chạy mặc định
                $this->quarter = $currentSubOneQuaterGetQuarter;
                $this->year = $currentSubOneQuaterGetYear;
                $this->stringTime =   "Q" . $currentSubOneQuaterGetQuarter . " " . $currentSubOneQuaterGetYear;
            }
            
            $stringTimeResult = $this->stringTime;
         
            // Handle
            $stockControler = new StocksController();
            $allStocks = Stock::query()->whereRaw('LENGTH(stockcode) < 4')->pluck('stockcode')->toArray();
            
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
            " đã có báo cáo tài chính quý " . $this->quarter . " năm ". $this->year);
            echo "\n";
            
            $listStockYetUpdate = [];
            $listUserIdIsYetUpdate = [];
            
            foreach( $listStockHasQuarterNew as $item_stock ) {
                $notHas = UserStock::where('stockcode', $item_stock) // tìm finance_updated_at khác với $stringTimeResult
                    ->where( 'is_delete', 0 )
                    ->where(function ($query) use ( $stringTimeResult ) {
                        $query->where('finance_updated_at', '<>' , $stringTimeResult )->orWhereNull('finance_updated_at', null);
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
            $this->error('có lỗi xảy ra.!');
            echo $e;
            return 0;
        }
    }

    public function createNotifyFinance( $listStockYetUpdate, $listUserIdIsYetUpdate ) {
        try {
            DB::beginTransaction();
            $stringTimeResult = $this->stringTime;
            
            $listStock = Stock::query()->whereIn( 'stockcode' , $listStockYetUpdate )->orderBy('stockcode', 'ASC')->pluck('stockcode')->toArray();
            $totalStock = count($listStock);
            if( count( $listStock ) == 0 ) {  $this->info("Có {$totalStock} mã stock"); return 0; }
            $companyNames = Stock::query()->whereIn('stockcode', $listStockYetUpdate )->orderBy('stockcode', 'ASC')->pluck('ten')->toArray();
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
                        'quarter' => $this->quarter,
                        'year' => $this->year,
                    ],
                    'news' => $messageNews,
                    'user' => $messageUser,
                ];

                $created_at = Carbon::now();
                $type_notify = 'finance';
                $group_first = 1;

                $userIdsOfStock = UserStock::where('stockcode', $listStock[$index_stock] )
                    ->where( 'is_delete', 0 )
                    ->where(function ($query) use ( $stringTimeResult ) {
                        $query->where('finance_updated_at', '<>' , $stringTimeResult )->orWhereNull('finance_updated_at', null);
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

            // Cập nhật user_stocks
            foreach( $listStockYetUpdate as $item_stock ) {
                UserStock::where('stockcode', $item_stock )->whereIn( 'user_id', $listUserIdIsYetUpdate )
                    ->update([ 
                        'finance_updated_at' => $stringTimeResult, 
                    ]);
                $progressBarUserStock->advance(); // running progres user_stocks
            }
            echo "\nCác mã stock: \n";
            echo implode (" ",$listStock);
            
            DB::commit();
            return 0;

        } catch(\Exception $e){
            DB::rollBack();
            $this->error('có lỗi xảy ra.!');
            echo $e;
            return 0;
        }

    }
    
    
}
