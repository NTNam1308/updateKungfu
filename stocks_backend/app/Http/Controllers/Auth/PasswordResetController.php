<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Str;
use Mail;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => "Không tồn tại tài khoản với email bạn cung cấp"
            ], 404);
        }

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60)
             ]
        );

        if ($user && $passwordReset) {
            $this->sentResetEmail($request->email, $passwordReset->token);
        }

        return response()->json([
            'message' => 'Email xác nhận thay đổi mật khẩu đã được gửi. Vui lòng kiểm tra email của bạn để xác nhận thay đổi mật khẩu!'
        ]);
    }

     /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|min:6',
            'token' => 'required|string'
        ]);

        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Mã đặt lại mật khẩu không hợp lệ'
            ], 404);
        }

        if (Carbon::parse($passwordReset->updated_at)->addMinutes(1440)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'Mã đặt lại mật khẩu đã hết hạn. Vui lòng thử lại yêu cầu thay đổi mật khẩu mới'
            ], 404);
        }
            
        $user = User::where('email', $passwordReset->email)->first();

        if (!$user) {
            return response()->json([
                'message' => "Email không tồn tại"
            ], 404);
        }

        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $this->sentResetSuccessEmail($request->email);
        return response()->json([
            'message' => "Đặt lại mật khẩu thành công"
        ], 200);
    }

    protected function sentResetEmail($email, $token) {
        $body = "<img src='".getenv('FRONT_END_URL')."/img/logo-mail.png' style='width: 160px;height: auto;'/>"; 
        $body .= "<p><strong>ĐẶT LẠI MẬT KHẨU</strong></p>";
        $body .= "<p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản Kungfu Stocks Pro của bạn - ".$email."</p>";
        $body .= "<p>Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này và mật khẩu của bạn sẽ không bị thay đổi. Liên kết dưới đây sẽ được duy trì trong 24 tiếng.</p>";
        $body .= "<a href='".getenv('FRONT_END_URL')."/reset-password?email=".$email."&token=".$token."' style='appearance: button;
        background-color: #71308A;
        border: solid transparent;
        border-radius: 20px;
        border-width: 0 0 4px;
        box-sizing: border-box;
        color: #FFFFFF;
        cursor: pointer;
        display: inline-block;
        font-family: din-round,sans-serif;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .8px;
        line-height: 20px;
        margin: 0;
        outline: none;
        overflow: visible;
        padding: 13px 16px;
        text-align: center;
        text-transform: uppercase;
        touch-action: manipulation;
        transform: translateZ(0);
        transition: filter .2s;
        user-select: none;
        -webkit-user-select: none;
        vertical-align: middle;
        white-space: nowrap;
        width: 20;
        text-decoration: none;' >ĐẶT LẠI MẬT KHẨU</a>";
        $body .= "<br/><br/>";  
        $body .= "<p>KungFuStocksPro Team</p>"; 
        $body .= "<br/>"; 
        $body .= "-----";
        $body .= "<div>"; 
        $body .= "<img src='".getenv('FRONT_END_URL')."/img/logo-mail.png' style='width: 160px;height: auto;'/>"; 
        $body .= "</div>";  
        $body .= "<p>Phòng Dịch vụ khách hàng</p>"; 
        $body .= "<p>Email: kungfustockspro@happy.live</p>";
        $body .= "<p>SĐT: <span style='color: #e60000'>(+84) 038 9843068</span></p>"; 
        $body .= "<p>Website: https://kungfustockspro.live/ </p>"; 
        $body .= "<p>Địa chỉ: Số 9 đường 9A, Khu Dân Cư Nam Phú, Phường Tân Thuận Đông, Quận 7, Thành phố Hồ Chí Minh</p>";
        Mail::send([], [], function ($message) use ($email, $body)
        {
            $message->from(getenv('MAIL_FROM_ADDRESS'), 'KungfuStocksPro');
            $message->to($email);
            $message->subject("[KungFuStocksPro] Yêu cầu đặt lại mật khẩu!");
            $message->setBody($body,'text/html');
        });
    }

    protected function sentResetSuccessEmail($email) {
        $body = "<img src='".getenv('FRONT_END_URL')."/img/logo-mail.png' style='width: 160px;height: auto;'/>"; 
        $body .= "<p><strong>BẠN ĐÃ ĐẶT LẠI MẬT KHẨU THÀNH CÔNG</strong></p>";
        $body .= "Yêu cầu đặt lại mật khẩu của tài khoản ".$email." đã được thực hiện.";
        $body .= "<p>Nếu bạn không thực hiện yêu cầu này, hãy liên hệ ngay với hotline <b style='color: #e60000' >(+84) 038 9843068</b> để được hỗ trợ kịp thời.</p>";
        $body .= "<br/>";  
        $body .= "<p>KungFuStocksPro Team</p>"; 
        $body .= "<br/>"; 
        $body .= "-----";
        $body .= "<div>"; 
        $body .= "<img src='".getenv('FRONT_END_URL')."/img/logo-mail.png' style='width: 160px;height: auto;'/>"; 
        $body .= "</div>";  
        $body .= "<p>Phòng Dịch vụ khách hàng</p>"; 
        $body .= "<p>Email: kungfustockspro@happy.live</p>"; 
        $body .= "<p>SĐT: <span style='color: #e60000'>(+84) 038 9843068</span></p>"; 
        $body .= "<p>Website: https://kungfustockspro.live/ </p>"; 
        $body .= "<p>Địa chỉ: Số 9 đường 9A, Khu Dân Cư Nam Phú, Phường Tân Thuận Đông, Quận 7, Thành phố Hồ Chí Minh</p>";
        Mail::send([], [], function ($message) use ($email, $body)
        {
            $message->from(getenv('MAIL_FROM_ADDRESS'), 'KungfuStocksPro');
            $message->to($email);
            $message->subject("[KungFuStocksPro] Đặt lại mật khẩu thành công!");
            $message->setBody($body,'text/html');
        });
    }
}