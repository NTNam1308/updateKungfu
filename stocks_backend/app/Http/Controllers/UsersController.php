<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateInfoRequest;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserServicePlan;
use App\Models\MyWatchlist;
use App\Models\Notify;
use App\Models\Watchlist;
use Carbon\Carbon;
use Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class UsersController extends Controller
{

    protected const USER_ROLE = "user";
    protected const COWORKER_ROLE = "user,coworker";
    protected const MODERATOR_ROLE = "user,coworker,moderator";
    protected const ADMIN_ROLE = "user,coworker,moderator,admin";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // if (strpos(auth()->user()->menuroles, 'admin') !== false) {
        //     return response()->json( ['status' => 'admin'] );
        // }

        $not_like_string = "moderator";
        if (!empty(auth()->user()) && auth()->user()->hasRole('admin')) {
            $not_like_string = "N/A";
        }
        $you = auth()->user()->id;
        // type: [{value:-1 , label:"Tất Cả" },{value:0 , label:"Thường" }, {value:1, label:"Nội Bộ"}, {value:2, label:"Ngoại Giao"}, {value:3, label:"Test"}]
        if(isset($request->type)){
            if($request->type < 0){
                $users = DB::table('users')
                ->select('users.id', 'users.name', 'users.email','users.phone',
                 'users.menuroles as roles', 'users.status', 'user_service_plan.activate_date',
                'user_service_plan.period', 'user_service_plan.expiry_date', 'users.type', 'users.created_at  as registered', 'users.personal_reference_code')
                ->join('user_service_plan', 'users.id', '=', 'user_service_plan.user')
                ->where('menuroles', 'not like', "%".$not_like_string."%")
                ->get();

            }else{
                $users = DB::table('users')
                ->select('users.id', 'users.name', 'users.email','users.phone', 'users.menuroles as roles',
                 'users.status', 'user_service_plan.period', 'user_service_plan.expiry_date', 'user_service_plan.activate_date',
                  'users.type', 'users.created_at  as registered', 'users.personal_reference_code')
                ->join('user_service_plan', 'users.id', '=', 'user_service_plan.user')
                ->where('type',$request->type)
                ->where('menuroles', 'not like', "%".$not_like_string."%")
                ->get();
            }

        } else {
            $users = DB::table('users')
            ->select('users.id', 'users.name', 'users.email','users.phone', 'users.menuroles as roles',
             'users.status', 'user_service_plan.period', 'user_service_plan.activate_date',
              'user_service_plan.expiry_date', 'users.type', 'users.created_at  as registered', 'users.personal_reference_code')
            ->join('user_service_plan', 'users.id', '=', 'user_service_plan.user')
            ->where('menuroles', 'not like', "%".$not_like_string."%")
            ->get();
        }
        return response()->json( compact('users', 'you') );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = DB::table('users')
        ->select('users.avatar','users.id', 'users.name', 'users.email', 'users.menuroles as roles', 'users.status', 'users.email_verified_at as registered', 'users.personal_reference_code')
        ->where('users.id', '=', $id)
        ->first();
        $plan = DB::table('user_service_plan')
        ->join('service_plans', 'user_service_plan.service_plan', '=', 'service_plans.id')
        ->select('service_plans.name', 'user_service_plan.expiry_date')
        ->where('user_service_plan.user', '=', $user->id)
        ->first();
        $user->plan = isset($plan->name) ? $plan->name : '';
	$user->plan_name = isset($plan->name) ? $plan->name : '';
        $user->expiry_date = isset($plan->expiry_date) ? $plan->expiry_date : '';
        $user->avatar = url('/').$user->avatar;
        return response()->json( $user );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $url = url('/');
        $user = DB::table('users')
        ->select(DB::raw("CONCAT('$url' , avatar) as avatar") ,'users.id', 'users.name', 'users.email', 'users.phone', 'users.address',
         'users.menuroles as roles', 'users.status', 'users.clan', 'users.note', 'users.promotion_months',
         'users.limited', 'users.student', 'users.forever', 'users.type', 'users.personal_reference_code', 'users.reference_promotion_months')
        ->where('users.id', '=', $id)
        ->first();
        $plan = DB::table('user_service_plan')
        ->join('service_plans', 'user_service_plan.service_plan', '=', 'service_plans.id')
        ->select('service_plans.name', 'user_service_plan.expiry_date', 'user_service_plan.service_plan', 
        'user_service_plan.activate_date', 'user_service_plan.period')
        ->where('user_service_plan.user', '=', $user->id)
        ->first();
	    $user->plan = isset($plan->service_plan) ? $plan->service_plan : '';
	    $user->plan_name = isset($plan->name) ? $plan->name : '';
        $user->activate_date = isset($plan->activate_date) ? date('d M Y', strtotime($plan->activate_date)) : false;
        $user->expiry_date = isset($plan->expiry_date) ? date('d M Y', strtotime($plan->expiry_date)) : false;
        $user->period =  isset($plan->period) ? $plan->period : 1;
        return response()->json( $user );
    }

    protected function removeRoles($user, $roles) {
        if (!empty($roles)) {
            foreach (explode(",", $roles) as $role) {
                $user->removeRole($role);
            }
        }
    }

    protected function assignRoles($user, $roles) {
        if (!empty($roles)) {
            $user->assignRole(explode(",", $roles));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    
    public function update(UserRequest $request, $id)
    {
        $activating = false;
        $user = User::find($id);
        $old_roles = $user->menuroles;
        $activating = $user->status != $request->input('status') && $request->input('status') == 'Active' ? true : false;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $user->address = $request->input('address');
        
        // Only admin can edit those field
        if (auth()->user()->hasRole('admin')) {
            $user->limited = $request->input('limited');
            $user->forever = $request->input('forever');
            $user->type = $request->input('type');
        }
        
        // Only admin or moderator can edit those field
        if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('moderator')) {

            // --- userServicePlan --- 
            $userServicePlan = UserServicePlan::where('user', '=', $id)->first();
            if( $userServicePlan ) {

                $dateOld = Carbon::parse($userServicePlan->activate_date)->format('Y-m-d'); // activate_date old in database
                $dateRequest = Carbon::parse($request->activate_date)->format('Y-m-d');  // activate_date request
                // if empty set activate_date
                if(empty($userServicePlan->activate_date)) {
                    $userServicePlan->activate_date = $request->input('activate_date');
                }
                // if change activate_date
                if( !empty($userServicePlan->activate_date) && $dateRequest != $dateOld ) {
                    $userServicePlan->activate_date = $request->input('activate_date');
                }
                $userServicePlan->expiry_date = $request->input('expiry_date');
                $userServicePlan->service_plan = $request->input('service_plan');
                $userServicePlan->period = $request->input('period');
                $userServicePlan->save();
            
            } else {
                UserServicePlan::create([
                    'expiry_date' => $request->input('expiry_date'), 
                    'service_plan' => $request->input('service_plan'),
                    'period' => $request->input('period'),
                    'activate_date' => $request->input('activate_date'),
                    'user' => $user->id
                ]);
            }
 
            
            // --- user --- 
            $user->clan = $request->input('clan');
            $user->promotion_months = $request->input('promotion_months');
            $user->student = $request->input('student');
            $user->status = $request->input('status');

            $user->note = $request->input('note');
            if ($activating == true) {
                $user->personal_reference_code = $this->renderReferenceCode();
                $user->activater = $request->activater;
            }

        }
        
        
        // Set menuroles
        if ($request->input('role') != null && $request->input('role') != $old_roles
            && auth()->user()->hasRole('admin')
            && in_array($request->input('role'), array(self::USER_ROLE, self::COWORKER_ROLE, self::MODERATOR_ROLE, self::ADMIN_ROLE)) 
        ) {
            $user->menuroles = $request->input('role');
        } else if ($request->input('role') != null && $request->input('role') != $old_roles
            && auth()->user()->hasRole('moderator')
            && in_array($request->input('role'), array(self::USER_ROLE, self::COWORKER_ROLE)) 
        ) {
            $user->menuroles = $request->input('role');
        }
        
        $user->save();
        
        // remove old roles and assign new role
        if ($request->input('role') != null && $request->input('role') != $old_roles) {
            if (auth()->user()->hasRole('admin')
                && in_array($request->input('role'), array(self::USER_ROLE, self::COWORKER_ROLE, self::MODERATOR_ROLE, self::ADMIN_ROLE))
            ) {
                $this->removeRoles($user, $old_roles);
                $this->assignRoles($user, $request->input('role'));
            }
            if (auth()->user()->hasRole('moderator')
                && in_array($old_roles, array(self::USER_ROLE, self::COWORKER_ROLE))
                && in_array($request->input('role'), array(self::USER_ROLE, self::COWORKER_ROLE))
            ) {
                $this->removeRoles($user, $old_roles);
                $this->assignRoles($user, $request->input('role'));
            }
        }
        //Default watchlist
        $watchlist = MyWatchlist::where('user_id', '=', $id)->where('name', '=', 'Kungfu Watchlist')->first();
        if( !$watchlist ){
            MyWatchlist::create([
                "name" => "Kungfu Watchlist",
                "user_id" => $user->id
            ]);
        }
        //Default category
        $category_ls = Category::where('id_user', '=', $id)->where('name', '=', 'Lướt Sóng')->first();
        if( !$category_ls ){
            Category::create([
                'name' => 'Lướt Sóng',
                'id_user' => $user->id
            ]);
        }
        
        $category_dt = Category::where('id_user', '=', $id)->where('name', '=', 'Đầu Tư')->first();
        if( !$category_dt ){
            Category::create([
                'name' => 'Đầu Tư',
                'id_user' => $user->id
            ]);
        }

        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('moderator')) {
            return response()->json( ['status' => 'success'] );
        }
        
        $plan_info = DB::table('user_service_plan')
        ->join('service_plans', 'user_service_plan.service_plan', '=', 'service_plans.id')
        ->join('users','users.id', '=','user_service_plan.user')
        ->select('service_plans.name', 'user_service_plan.expiry_date', 'user_service_plan.service_plan', 
        'user_service_plan.activate_date', 'user_service_plan.period', 'users.promotion_months' )
        ->where('user_service_plan.user', '=', $id)
        ->first();

        if ($activating == true) {
            // Active reference
            $reference = DB::table('reference_code')
                        ->where("user_id", "=", $id)
                        ->first();
            if($reference !=  null){
                $this->activeReference($reference);
            }                               
            // Send email
            if($plan_info->promotion_months == null){
                $plan_info->promotion_months = "0";
            };
            $body = "<p>Chúc mừng hội viên ".$plan_info->name." KFSP, ".$user->name;
            $body .= "<br/>"; 
            $body .= "<p>Cảm ơn bạn đã tin tưởng và lựa chọn chúng tôi. Gói dịch vụ bạn đăng ký đã được kích hoạt thành công.</p>";
            $body .= "<br/>";  
            $body .= "<p><strong>THÔNG TIN HỘI VIÊN: </strong></p>";
            $body .= "<table style='border: 1px solid black;border-collapse: collapse;' >";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Tên đăng nhập (User ID) </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse; font-weight: bold;'>".$user->email."</td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'> Link đăng nhập</td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse; font-weight: bold;'> https://kungfustockspro.live/login </td>";
            $body .= "</tr>";
            $body .= "</table>"; 
            $body .= "<br/>";  
            $body .= "<p><strong>THÔNG TIN DỊCH VỤ: </strong></p>";  
            $body .= "<div>";  
            $body .= "<table >";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Tên gói </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse; font-weight: bold;'>".$plan_info->name." (".$plan_info->period." năm)</td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Ngày kích hoạt </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse; font-weight: bold;'>".$plan_info->activate_date."</td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Số tháng khuyến mại</td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse; font-weight: bold;'> ".$plan_info->promotion_months." </td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Ngày hết hạn </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse; font-weight: bold;'>".$plan_info->expiry_date."</td>";
            $body .= "</tr>";
            $body .= "</table>";
            $body .= "</div>";  

            $body .= "<p>Hy vọng bạn sẽ đạt được những thay đổi tích cực khi sử dụng dịch vụ <strong>".$plan_info->name."-".$plan_info->period." năm</strong> của KungFuStocksPro</p>"; 
            $body .= "<p>Nếu bạn cần hỗ trợ, vui lòng liên hệ hotline <strong style='color: #e60000'>(+84) 038 9843068</strong>  -  hoặc gửi thư về địa chỉ <strong style='color: #e60000'> kungfustockspro@happy.live </strong>.</p>"; 
            $body .= "<br/>";  
            $body .= "<p>KungFuStocksPro Team</p>"; 
            $body .= "<br/>"; 
            $body .= "-----";
            $body .= "<div>"; 
            $body .= "<img src='".getenv('FRONT_END_URL')."/img/logo-mail.png' style='width: 160px;height: auto;'/>"; 
            $body .= "</div>";  
            $body .= "<p>Phòng Dịch vụ khách hàng</p>"; 
            $body .= "<p>Email: kungfustockspro@happy.live</p>"; 
            $body .= "<p>SĐT:<span style='color: #e60000'>(+84) 038 9843068</span> </p>"; 
            $body .= "<p>Website: https://kungfustockspro.live/ </p>"; 
            $body .= "<p>Địa chỉ: Số 9 đường 9A, Khu Dân Cư Nam Phú, Phường Tân Thuận Đông, Quận 7, Thành phố Hồ Chí Minh</p>";
            Mail::send([], [], function ($message) use ($user, $body, $plan_info)
            {
                $message->from(getenv('MAIL_FROM_ADDRESS'), 'KungfuStocksPro');
                $message->to($user->email);
                $message->subject("[KungFuStocksPro] Bạn đã trở thành ".$plan_info->name."!");
                $message->setBody($body,'text/html');
            });

            $body = "<p>Xin chào Hội viên ".$user->name;
            $body .= "<p>Giờ đây bạn có thể tham gia nhóm Hội viên KFSP để cập nhật các thông tin hữu ích về phần mềm và kết nối với cộng đồng đầu tư của chúng tôi.</p>";
            $body .= "<br/>";  
            $body .= "<p style= 'form-weight: bold; color: #e60000;'><strong>Các bước đăng ký: </strong></p>";
            $body .= "<div style='padding-left: 30px'>";
            $body .= "<span> <strong>Bước 1: </strong> Tạo tài khoản trên ứng dụng Telegram (Nếu chưa có tài khoản)</span><br/>";
            $body .= "<span> <strong>Bước 2: </strong> Điền Form này: <a href='https://bit.ly/Investors-telegram'>https://bit.ly/Investors-telegram</a> </span><br/>";
            $body .= "<span> <strong>Bước 3:  </strong>Lưu danh bạ trên điện thoại và nhắn tin với KFSP Team <strong>(tìm +84389843068 hoặc @KFSPTEAM)</strong> trên ứng dụng Telegram để Team dễ tìm tài khoản của mình. Khi nhắn tin thì vui lòng ghi tên +SĐT hoặc email đã dùng để đăng ký KFSP </span><br/>";          
            $body .= "</div>";
            $body .= "<br>";


            $body .= "<p style= 'form-weight: bold; color: #e60000;'><strong>Lưu ý: </strong></p>";
            $body .= "<div style='padding-left: 30px'>";
            $body .= "<span>- Mỗi tài khoản đăng ký KFSP chỉ được duyệt 1 tài khoản Telegram duy nhất. </strong> </span><br/>";
            $body .= "<span>- Có 1 Nhóm Hội viên (Chat) và 1 Kênh (Không Chat)</span><br/>";
            $body .= "<span>- Vì vào sau nên sẽ có những thông tin Hội viên chưa được tiếp cận, hãy đọc lại tin nhắn ở Kênh Không chat để cập nhật các thông tin từ Admin (anh Thái Phạm, Nguyễn Thanh). </span><br/>";
            $body .= "<span>- Đăng nhập lỗi tài khoản, các trục trặc về kỹ thuật, liên hệ Mr. Hiệp (SĐT 0379939298/ Telegram @HIEPNGUYENVT) hoặc Mr. Thanh (SĐT 0911939468/Telegram  @NGUYENTHANHCLLHP). Khi nhắn tin vui lòng nêu rõ vấn đề gặp phải, cung cấp thông tin tài khoản để được kiểm tra nhanh chóng.</span><br/>";
            $body .= "</div>";
            $body .= "<br>";
            $body .= "<br/>"; 
            $body .= "<p>Nếu bạn cần hỗ trợ, vui lòng liên hệ hotline <span style='color: #e60000'>(+84) 038 9843068 </span>-  hoặc gửi thư về địa chỉ <strong style='color: #e60000'> kungfustockspro@happy.live </strong>.</p>"; 
           
            $body .= "<br/>";  
            $body .= "<p>KungFuStocksPro Team</p>"; 
            $body .= "<br/>"; 
            $body .= "-----";
            $body .= "<div>"; 
            $body .= "<img src='".getenv('FRONT_END_URL')."/img/logo-mail.png' style='width: 160px;height: auto;'/>"; 
            $body .= "</div>";  
            $body .= "<p>Phòng Dịch vụ khách hàng</p>"; 
            $body .= "<p>Email: kungfustockspro@happy.live</p>"; 
            $body .= "<p>SĐT:<span style='color: #e60000'>(+84) 038 9843068</span> </p>"; 
            $body .= "<p>Website: https://kungfustockspro.live/ </p>"; 
            $body .= "<p>Địa chỉ: Số 9 đường 9A, Khu Dân Cư Nam Phú, Phường Tân Thuận Đông, Quận 7, Thành phố Hồ Chí Minh</p>";
            Mail::send([], [], function ($message) use ($user, $body, $plan_info)
            {
                $message->from(getenv('MAIL_FROM_ADDRESS'), 'KungfuStocksPro');
                $message->to($user->email);
                $message->subject("[KungFuStocksPro] Hướng dẫn tham gia nhóm Hội viên KungFuStocksPro");
                $message->setBody($body,'text/html');
            });
        }

        //$request->session()->flash('message', 'Successfully updated user');
        return response()->json( ['status' => 'success'] );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function passwd(Request $request, $id)
    {
        if (auth()->user()->hasRole('admin') && auth()->user()->id != $id) {
            $request->validate([
                'new_password' => 'required|min:6|max:128'
            ]);
            $user = User::find($id);
            $user->password = bcrypt($request->new_password);
            $user->save();
            return response()->json( ['status' => 'success'] );
        } else if (auth()->user()->hasRole('moderator') && auth()->user()->id != $id) {
            $user = User::find($id);
            if (strpos($user->menuroles, 'admin') !== false || strpos($user->menuroles, 'moderator') !== false) {
                return response()->json(['status' => 'error'], 400);
            } else {
                $user->password = bcrypt($request->new_password);
                $user->save();
                return response()->json( ['status' => 'success'] );
            }
        } else {
            $request->validate([
                'new_password'       => 'required|min:6|max:128',
                'old_password'       => 'required|min:6|max:128'
            ]);
            $user = User::find($id);
            if(Hash::check($request->old_password, $user->password)) {
                $user->password = bcrypt($request->new_password);
                $user->save();
                return response()->json( ['status' => 'success'] );
            } else {
                return response()->json(['status' => 'error'], 400);
            }   
        }    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changeAvatar(Request $request, $id)
    {
        $validatedData = $request->validate([
            'file'          => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if($request->hasFile('file')){
            try {
                $storedPath = Storage::putFile('public', $request->file('file'));
                $user = User::find($id);
                $user->avatar = Storage::url($storedPath);
                $user->save();
                return response()->json( ['storedPath' => url('/').Storage::url($storedPath)] );
            } catch (Exception $e) {
                return response()->json(['status' => 'error', 'message' => 'Không thể lưu ảnh'], 400);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Ảnh không tồn tại'], 400);
        }        
    }

    public function updateInfo(UserUpdateInfoRequest $request) { 
        try {
            $user = JWTAuth::user();
            $image = $user->avatar;
            if($request->image){

                $imgdata = base64_decode($request->image);
                $mime_type = finfo_buffer(finfo_open(), $imgdata, FILEINFO_MIME_TYPE);
                $explode_type = explode("/", $mime_type);
                $type = $explode_type[1]; // jpeg, png, jpg, gif

                $image_64 = "data:image/".$type.";base64,".$request->image;

                // handle image base 64
                $replace = substr($image_64, 0, strpos($image_64, ',')+1); 
                $image = str_replace($replace, '', $image_64); 
                $image = str_replace(' ', '+', $image); 
                $imageName = Str::random(20).'.'.$type ;

                $storedPath = "public/".$imageName;
                $result_image = base64_decode($image);

                Storage::put($storedPath, $result_image); // save to storage
                $image = Storage::url($storedPath);    
            }
            
            User::where('id', $user->id)->update([
                'avatar' => $image,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
            $result = [
                'avatar' => url('/').$image,
                'phone' => $request->phone,
                'address' => $request->address,
            ];
            return response()->json( $result );

        } catch (Exception $e) {
            return response()->json(['status' => 'error'], 400);
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthenticated. User has no permission'], 401);
        }
        $user = User::find($id);
        if($user){
            DB::table('users')->where('id', $id)->delete();
            DB::table('user_service_plan')->where('user', $id)->delete();
        }
        return response()->json( ['status' => 'success'] );
    }

    public function checkToken(Request $request){
        $token = User::where('last_session',$request->token)->first();
        if($token){
            return response()->json( ['status' => 'success'], 200 );
        } else {
            return response()->json( ['error' => 'error'], 400 );
        }
    }
    // Update fcm token
    public function updateFcmToken(Request $request){

        try{
            if( $request->isApp == "true" ) { 
                $request->user()->update(['fcm_token_mobile'=>$request->fcm_token]); 
            } 
            else { 
                $request->user()->update(['fcm_token_web'=>$request->fcm_token]);
            }
            return response()->json([ 'success nha nha '=>true ]);
        }catch(\Exception $e){
            return response()->json([ 'error'=>false ],500);
        }
    }
    
    // Check maintenance
    public function checkMaintenance(){
        return response()->json(DB::table('maintenance')->get(['status']));
    }
    
     // Get notify
     public function getNotify() {
        $user = auth()->user();
        $allNotify = $user->notifications()->orderBy('group', 'DESC')->get();
        return response()->json($allNotify, 200);
    }

    // User watched
    public function userCallBackIsRead(Request $request){
        $listId = $request->id_notify;
        $user = auth()->user();
        $checkIdFinance = $user->notifications()->whereIn('id', $listId )->pluck('id')->toArray();
        
        // Set read_at
        foreach($checkIdFinance as $id_notify) {
            Notify::whereId($id_notify)->update(['read_at' => CarBon::now()]) ;
        }
        return response()->json(['status' => 'success'], 200);
    }

    // User Callback is_new
    public function userCallBackIsNew(Request $request){
        $user = auth()->user();
        $group = $request->group;
        $groupOfUser = $user->notifications()->whereIn('group', $group )->pluck('group')->toArray();
        
        // Set is_new = 1
        Notify::whereIn('group', $groupOfUser )->update(['is_new' => 1 ]) ;

        return response()->json(['status' => 'success'], 200);
    }

    public function activeReference($reference){
        $referencer = User::where('personal_reference_code',$reference->reference_code)->first();
        $new_user = User::where('id',$reference->user_id)->first();
        //check thời hạn để tăng discount lên
        $service_plan_referencer = DB::table('user_service_plan')->where('user', '=', $referencer->id)->first();
        $service_plan_new_user = DB::table('user_service_plan')->where('user', '=', $new_user->id)->first();
        $month_discount = $service_plan_new_user->period * getenv('TIME_DISCOUNT_FOR_REFERENCE');
        // ghi log
        
        DB::beginTransaction();
        try{
            DB::table('reference_code')
            ->where("user_id", "=", $new_user->id)
            ->update(['status' => 'Active', 'date_active' => date('Y-m-d'), 'referencer_id' => $referencer->id]);

            $new_expiry_date_referencer = date('Y-m-d H:i:s', strtotime($service_plan_referencer->expiry_date.' +'.$month_discount.' months'));
            $new_expiry_date_new_user = date('Y-m-d H:i:s', strtotime($service_plan_new_user->expiry_date.' +'.$month_discount.' months'));

            //update active_date
            DB::table('user_service_plan')
            ->where("user", "=", $referencer->id)
            ->update(['expiry_date' => "$new_expiry_date_referencer"]);

            DB::table('user_service_plan')
            ->where("user", "=", $new_user->id)
            ->update(['expiry_date' => "$new_expiry_date_new_user"]);

            //update reference_promotion_months
            $referencer_reference_promotion_months = $referencer->reference_promotion_months + $month_discount;
            $new_user_promotion_months = $new_user->reference_promotion_months + $month_discount;
            DB::table('users')
            ->where("id", "=", $new_user->id)
            ->update(['reference_promotion_months' => "$new_user_promotion_months"]);
            DB::table('users')
            ->where("id", "=", $referencer->id)
            ->update(['reference_promotion_months' => "$referencer_reference_promotion_months"]);
            
            // log 
            $log_data = ['new_user' => $new_user->email, 'referencer' => $referencer->email, 'discount' => $month_discount, 'date' => date("Y-m-d")]; 
            $log = json_encode($log_data);

            DB::table('user_logs')
            ->insert([
                'user_id' => $referencer->id,

                'log_name' => 'KFSP tri ân người giới thiệu',
                'log_type' => 'reference_success',
                'status' => 'New',
                'date_time' => date("Y-m-d"),
                'log' => $log,
            ]);

            DB::table('user_logs')
            ->insert([
                'user_id' => $new_user->id,
                'log_name' => 'KFSP quà từ mã giới thiệu',
                'log_type' => 'active_reference_success',
                'status' => 'New',
                'date_time' => date("Y-m-d"),
                'log' => $log,
            ]); 
            
            DB::commit();
        }catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
    public function renderReferenceCode(){
        do{
            $code1 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 10, 3); 
            $code2 = substr(str_shuffle("0123456789"),0,3); 
            $code = strtoupper($code1.$code2);
        }while(User::where('personal_reference_code',"=",$code)->first());
        return $code;
    }
}
