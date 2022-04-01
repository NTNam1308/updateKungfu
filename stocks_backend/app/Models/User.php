<?php

namespace App\Models;

// Helper
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Custom
use App\Models\MyWatchlist;
use App\Models\Watchlist;
use App\Models\Stock;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;
    use HasRoles;
    use HasFactory;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }    

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'status','avatar', 'activate_date', 'promotion_months', 'clan', 'note',
         'limited', 'student', 'forever', 'type', 'fcm_token_web', 'fcm_token_mobile'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at'
    ];
    
    protected $guard_name = 'api';

    protected $attributes = [ 
        'menuroles' => 'user',
    ];
    public function myWatchlist() {
        return $this->hasMany(MyWatchlist::class, "user_id");
    }
    public function watchlist() {
        return $this->hasMany(Watchlist::class, "user_id");
    }

    public function attachStocks($list_stocks)
    {
        try {
            DB::beginTransaction();
            $listItemCreate = [];
            $listStockRestore = [];
            $currentTime = Carbon::now();
            foreach( $list_stocks as $stock ) {
                $checkStock = Stock::query()->where('stockcode', $stock)->exists();  // foreign key
                if( !$checkStock  ) { return; } // nếu mã không tồn tại return;

                $checkExistsIsDelete = UserStock::where('user_id', $this->id)->where('stockcode',  $stock)->where('is_delete',  1)->exists();
                $checkExists = UserStock::where('user_id', $this->id)->where('is_delete',  0)->where('stockcode', $stock)->exists();

                // Tạo mới nếu chưa tồn tại
                if (  !$checkExists &&  !$checkExistsIsDelete) {
                    array_push($listItemCreate, [
                        'user_id' => $this->id,
                        'stockcode' => $stock,
                        'is_delete'=> 0,
                        'created_at'=> $currentTime,
                        'updated_at'=> $currentTime,
                    ]);
                }
                if (  $checkExistsIsDelete ) { $listStockRestore[] = $stock;  } // push $stock to $listStockRestore
            }

            // Khôi phục stock đã tồn tại đặt is_delete = 0
            UserStock::where( 'user_id', '=', $this->id )
                ->where('is_delete', '=', 1)
                ->whereIn('stockcode', $listStockRestore)
                ->update([ 'is_delete' => 0 ]); // khôi phục

            UserStock::insert($listItemCreate); // Tạo mới nếu chưa tồn tại

            DB::commit();
            return;
        } catch(\Exception $e){
            DB::rollBack();
            echo "\n có lỗi xảy ra. \n";
            echo $e;
            return 0;
        }
    }

    public static function detachStocks($list_stocks)
    {
        try {
            DB::beginTransaction();
            $user = JWTAuth::user();
            foreach( $list_stocks as $item_stock) {
                $count_mack_in_watchlist = Watchlist::whereUserId($user->id)->where("mack", $item_stock )->count();

                if( $count_mack_in_watchlist == 1 ) { // if if only
                    // Update is_delete = 1
                    UserStock::where( [
                        'user_id' => $user->id, 
                        'stockcode'=> $item_stock
                    ])->update([
                        'is_delete' => 1,
                    ]);
                   
                }
            }
            DB::commit();
            return;
        } catch(\Exception $e){
            DB::rollBack();
            echo "\n có lỗi xảy ra. \n";
            echo $e;
            return 0;
        }
    }

    public function stocks()
    {
        return UserStock::where('user_id', $this->id )->get();
    }


}
