<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserServicePlan;
use App\Models\ServicePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UserRequest;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mail;
use Session;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'plans', 'verify', 'resetpassword']]);
    }

    /**
     * Register new user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->password = bcrypt($request->password);
        $user->status = 'New';
        $user->avatar = '';
        $reference_code = $request->reference_code;
        $user->save();
        if($reference_code != null){
            DB::table('reference_code')
            ->insert([
                'user_id' => $user->id,
                'reference_code' => $reference_code,
                'status' => 'pending',
                'date_active' => "0001:01:01",
            ]);
        }

        // Update service plan
        UserServicePlan::create([
            'user' => $user->id,
            'service_plan' => $request['service_plan'],
            'period' => $request['period'],
        ]);

        $autocreate =  $request['autocreate'];

        // Send verification email
        if ($user) {
            // Send email
            $verification_key = md5($user->email . $user->id);
            $body = "<p>Xin chào " . $user->name . "</p>";
            $body .= "<p>Cảm ơn bạn đăng ký sử dụng KungFuStocksPro.</p>";
            $body .= "<br/>";
            if (strcmp($autocreate, "yes") == 0) {
                $body .= "<p>Tài khoản của bạn được tạo với thông tin đăng nhập như sau. Bạn sẽ nhận được thông báo khi tài khoản được kích hoạt.</p>";
                $body .= "<b>Đăng nhập: </b>" . $user->email;
                $body .= "<br/>";
                $body .= "<b>Mật khẩu: </b>" . $request->password;
                $body .= "<br/>";
                $body .= "<b>Link đăng nhập: </b><a href='https://kungfustockspro.live/login'>https://kungfustockspro.live/login</a>";
            } else {
                $body .= "<p>Vui lòng click vào nút phía dưới để <span style='color: #e60000'>xác thực</span> email tài khoản .</p>";
                $body .= "<a href='" . getenv('FRONT_END_URL') . "/verify?email=" . $user->email . "&key=" . $verification_key . "'>KÍCH HOẠT</a>";
            }
            $body .= "<br/>";
            $body .= "<p>Nếu bạn cần hỗ trợ, vui lòng liên hệ hotline <strong style='color: #e60000'>(+84) 038 9843068 </strong> hoặc gửi thư về địa chỉ <strong style='color: #e60000'> kungfustockspro@happy.live </strong>.</p>";
            $body .= "<br/>";
            $body .= "<p>KungFuStocksPro Team</p>";
            $body .= "<br/>";
            $body .= "-----";
            $body .= "<div>";
            $body .= "<img src='" . getenv('FRONT_END_URL') . "/img/logo-mail.png' style='width: 160px;height: auto;'/>";
            $body .= "</div>";
            $body .= "<p>Phòng Dịch vụ khách hàng</p>";
            $body .= "<p>Email: kungfustockspro@happy.live</p>";
            $body .= "<p>SĐT: <span style='color: #e60000'>(+84) 038 9843068</span> </p>";
            $body .= "<p>Website: https://kungfustockspro.live/ </p>";
            $body .= "<p>Địa chỉ: Số 9 đường 9A, Khu Dân Cư Nam Phú, Phường Tân Thuận Đông, Quận 7, Thành phố Hồ Chí Minh</p>";

            Mail::send([], [], function ($message) use ($user, $body) {
                $message->from(getenv('MAIL_FROM_ADDRESS'), 'KungfuStocksPro');
                $message->to($user->email);
                $message->subject("[KungFuStocksPro] Tạo tài khoản thành công");
                $message->setBody($body, 'text/html');
            });
        }

        return response()->json(['status' => 'success'], 200);
        
    }

    /**
     * Verify user email.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email'     => 'required',
            'key'     => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validate->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->where('status', 'New')
            ->first();

        //Default category
        $my_watchlist  = DB::table("my_watchlists")->insert([
            "name" => "Kungfu Watchlist",
            "user_id" => $user->id
        ]);
        Category::create([
            'name' => 'Đầu Tư',
            'id_user' => $user->id
        ]);
        Category::create([
            'name' => 'Lướt Sóng',
            'id_user' => $user->id
        ]);


        if ($user->email  == $request->email) {
            $user->status = "Pending";
            $user->save();

            //send mail
            // $plan = UserServicePlan::where('user', '=', $user->id)->first();


            $plan = DB::table('user_service_plan')
            ->join('service_plans', 'user_service_plan.service_plan', '=', 'service_plans.id')
            ->join('users','users.id', '=','user_service_plan.user')
            ->select('service_plans.name', 'user_service_plan.service_plan', 'user_service_plan.period', 'user_service_plan.user', 'user_service_plan.expiry_date', 'user_service_plan.activate_date' )
            ->where('user_service_plan.user', '=', $user->id)
            ->first();

            if ($plan->period == 1) {
                $price = "6.800.000 VNĐ";
            } else {
                $price = "10.800.000 VNĐ";
            }
            $body  = "<p>Chào mừng bạn đến với KungFuStocksPro,</p>";
            $body .= "<br/>";
            $body .= "<p><strong>THÔNG TIN DỊCH VỤ: </strong></p>";  
            $body .= "<div>";  
            $body .= "<table >";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Tên gói </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>".$plan->name." (".$plan->period." năm)</td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Phương thức thanh toán </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Chuyển khoản ngân hàng</td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>Số tiền: </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'> ".$price." </td>";
            $body .= "</tr>";
            $body .= "<tr style='border: 1px solid black;border-collapse: collapse;'>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>ID tài khoản </td>";
            $body .= "<td style='border: 1px solid black;border-collapse: collapse;'>".$user->id."</td>";
            $body .= "</tr>";
            $body .= "</table>";
            $body .= "</div>"; 
            $body .= "<br/>";
            $body .= "<p>Để bước kích hoạt tài khoản thành công, bạn vui lòng hoàn tất thủ tục thanh toán theo hướng dẫn sau:</p>";
            $body .= "<br/>";
            $body .= "<p style= 'font-style: italic;form-weight: bold; color: #e60000;'>Lưu ý: </p>";
            $body .= "<div style='font-style: italic; padding-left: 30px'>";
            $body .= "<p>- Tài khoản của bạn sẽ được kích hoạt trong vòng 24h từ thời điểm thanh toán.</p>";
            $body .= "<p>- Vui lòng thực hiện đúng hướng dẫn về nội dung chuyển khoản để thủ tục kích hoạt được thực hiện nhanh chóng nhất.</p>";
            $body .= "<p>- Trong trường hợp thông tin trong nội dung chuyển khoản có sai sót, thời gian kích hoạt có thể kéo dài hơn 24h.</p>";
            $body .= "<p>- KFSP không có chính sách hoàn trả phí đối với thành viên đã mua và sử dụng phần mềm.</p>";
            $body .= "</div>";
            $body .= "<br/>";
            $body .= "<p>Phương thức thanh toán: <strong> Chuyển khoản qua ngân hàng </strong></p>";
            $body .= "<br/>";
            $body .= "<p>Quý khách vui lòng chuyển khoản vào tài khoản bên dưới với nội dung:</p>";
            $body .= "<p style='color: #004d8d'>".$user->name." ".$user->phone." ".$user->id."</p>";
            $body .= "<p>Thông tin ngân hàng: </p>";
            $body .= "<div style='text-indent: 3em; margin-top: 0;'>";
            $body .= "<p>Tên tài khoản: <strong> PHAM LE THAI</strong></p>";
            $body .= "<p>Số tài khoản: <strong>  288003368</strong></p>";
            $body .= "<p>Ngân hàng: <strong>  NH TMCP Quốc Tế Việt Nam (VIB)</strong></p>";
            $body .= "<p>Chi nhánh: <strong>  Phú Mỹ Hưng, Quận 7, HCM</strong></p>";
            $body .= "</div>";
            $body .= "<br/>";
            $body .= "<p>Nếu bạn cần hỗ trợ, vui lòng liên hệ hotline <strong style='color: #e60000;'>(+84) 038 9843068 </strong>-  hoặc gửi thư về địa chỉ <strong style='color: #e60000'> kungfustockspro@happy.live </strong>.</p>";
            $body .= "<br/>";
            $body .= "<p>KungFuStocksPro Team</p>";
            $body .= "<br/>";
            $body .= "-----";
            $body .= "<div>";
            $body .= "<img src='" . getenv('FRONT_END_URL') . "/img/logo-mail.png' style='width: 160px;height: auto;'/>";
            $body .= "</div>";
            $body .= "<p>Phòng Dịch vụ khách hàng</p>";
            $body .= "<p>Email: kungfustockspro@happy.live</p>";;
            $body .= "<p>SĐT: <span style='color: #e60000'>(+84) 038 9843068</span> </p>";
            $body .= "<p>Website: https://kungfustockspro.live/ </p>";
            $body .= "<p>Địa chỉ: Số 9 đường 9A, Khu Dân Cư Nam Phú, Phường Tân Thuận Đông, Quận 7, Thành phố Hồ Chí Minh</p>";
            Mail::send([], [], function ($message) use ($user, $body) {
                $message->from(getenv('MAIL_FROM_ADDRESS'), 'KungfuStocksPro');
                $message->to($user->email);
                $message->subject("[KungFuStocksPro] Hướng dẫn thanh toán");
                $message->setBody($body, 'text/html');
            });


            return response()->json(['status' => 'success'], 200);
        }

        return response()->json(['status' => 'error'], 400);
    }

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function plans()
    {
        $service_plans = ServicePlan::all('id', 'name');

        return $service_plans;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(UserRequest $request)
    {
        $credentials = [
            'email' => trim($request->email),
            'password' => $request->password,
        ];
        $persist_login = $request->input('persist_login');

        $users = User::where('email', $request->email)
            ->select('status', 'menuroles')
            ->first();

        if (!isset($users)) {
            return response()->json(['message' => 'Tên đăng nhập hoặc mật khẩu không đúng'], 400);
        }

        if ($users->status == 'New') {
            return response()->json(['message' => 'Tài khoản chưa kích hoạt.'], 400);
        }

        if ($users->status == 'Pending') {
            return response()->json(['message' => 'Tài khoản chờ thanh toán. Mời xem lại email để xem hướng dẫn thanh toán.'], 400);
        }

        if ($users->status == 'Inactive') {
            return response()->json(['message' => 'Bạn vui lòng gia hạn tài khoản để tiếp tục sử dụng.'], 400);
        }

        if ($users->status == 'Banned') {
            return response()->json(['message' => 'Tài khoản đã bị khóa.'], 400);
        }

        if ($persist_login == "true") {
            // Đơn vị phút: 20160 phút = 2 tuần
            auth()->setTTL(env('JWT_REFRESH_TTL', 20160));
        }

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (strpos($users->menuroles, 'admin')) {
            return $this->respondWithToken($token, $request->email, $request->isApp);
        }

        $plan = $this->getUserExpiry($request->email);
        if ($plan != null) {
            if (date("Y-m-d H:m:s") > $plan->expiry_date) {
                return response()->json(['message' => 'Tài khoản đã hết hạn'], 400);
            }
            if (date("Y-m-d H:m:s") < $plan->activate_date) {
                return response()->json(['message' => 'Tài khoản chưa đến ngày sử dụng'], 400);
            }
        }

        return $this->respondWithToken($token, $request->email, $request->isApp);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $email, $isApp)
    {
        $user = User::where('email', '=', $email)
            ->where('status', '=', 'Active')
            ->addSelect('menuroles')
            ->addSelect('name')
            ->addSelect('clan')
            ->addSelect('id')
            ->addSelect('avatar')
            ->addSelect('last_session')
            ->first();

        if( $isApp == "true" ) {
            $user->access_token_mobile = $token;
        } else {
            $user->last_session = $token;
        }
        $user->save();
        if ($user->clan == 1 || strpos($user->menuroles, 'admin')) {
            $user->menuroles = $user->menuroles . ',clan';
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'roles' => $user->menuroles,
            'user_name' => $user->name,
            'user_id' => $user->id,
            'avatar' => !empty($user->avatar) ? url('/') . $user->avatar : url('/images/avatar_default.png')
        ]);
    }

    protected function getUserExpiry($email)
    {
        $users = DB::table('users')
            ->join('user_service_plan', 'user_service_plan.user', '=', 'users.id')
            ->select('user_service_plan.expiry_date', 'users.forever', 'user_service_plan.activate_date')
            ->where('email', '=', $email)
            ->first();
        if ($users->forever == 1 && isset($users->expiry_date)) {
            return $users;
        } else {
            return null;
        }
    }
    
}
