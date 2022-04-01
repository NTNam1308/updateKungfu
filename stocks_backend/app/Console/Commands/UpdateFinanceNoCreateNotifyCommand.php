<?php

namespace App\Console\Commands;

// Helper
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Custom
use App\Http\Controllers\StocksController;
use App\Models\Stock;
use App\Models\UserStock;

class UpdateFinanceNoCreateNotifyCommand extends Command
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
    protected $signature = 'update:finance-no-notify {--q=}{--y=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update finance no create notify';

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
            foreach( $allStocks as $item_stock ) {
                    $count_stocks ++;
                    echo $count_stocks . ",";
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

            // Cập nhật user_stocks
            $this->updateUserStocks( $listStockYetUpdate, array_unique( $listUserIdIsYetUpdate ) );
            echo "\nThành công. \n";
            return 0;
            
        } catch(\Exception $e){
            echo "\n có lỗi xảy ra. \n";
            echo $e;
            return 0;
        }
    }

    public function updateUserStocks( $listStockYetUpdate, $listUserIdIsYetUpdate ) {
        try {
            DB::beginTransaction();
            $stringTimeResult = $this->stringTime;
            
            $listStock = Stock::query()->whereIn( 'stockcode' , $listStockYetUpdate )->orderBy('stockcode', 'ASC')->pluck('stockcode')->toArray();
            if( count( $listStock ) == 0 ) { return 0; }
           
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
