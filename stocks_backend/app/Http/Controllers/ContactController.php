<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class ContactController extends Controller
{
    public function sendContact(ContactRequest $request){
            $body = "<p><strong>Họ tên:</strong> ".$request->name??''."</p>";
            $body .= "<p><strong>Điện thoại:</strong> ".$request->phone??''."</p>";
            $body .= "<p><strong>Email:</strong> ".$request->email??''."</p>";
            $body .= "<p><strong>Nội dung liên hệ:</strong></p>";         
            $body .= "<p>".$request->content??''."</p>";         

            Mail::send([], [], function ($message) use ($body)
            {
                $message->from('no-reply@thoitrangoutlet.com', 'KungfuStocksPro');
                $message->to('kungfustockspro@happy.live');
                $message->cc('contact@vietpointer.vn');
                $message->bcc('buianhtruong14@gmail.com');
                $message->subject("Liên Hệ - KungfuStocksPro");
                $message->setBody($body,'text/html');
            });

        return response()->json(['status' => 'success'], 200);
    }

    public function saveContactFeedBack(ContactRequest $request){
        // $email = $request->email;
        $date = Carbon::now()->format("Y-m-d H:i:s");
        // $user = DB::table('users')->where('email', '=', $email)->first();
        // if($user == null){
        //     $registered = 'Unregistered';
        // }else{
        //     $registered = $user->status;
        // }
        DB::table('customer_contacts')
            ->insert([
                'email'      => $request->email,
                'phone'      => $request->phone,
                'name'       => $request->name,
                'content'    => $request->content,
                'called'     => 'New',
                // 'registered' => $registered,
                'notes'      => null,
                'created_at' => $date,
        ]);
        return $this->sendContact($request);
    }

    public function index(){
        // $customer_contacts = DB::table('customer_contacts')
        //                         // ->orderByRaw("concat(called, 'Đã liên hệ','true') ASC")
        //                         // ->orderByRaw("concat(registered, 'false','true') ASC")
        //                         // ->orderBy('created_at', 'desc')
        //                         ->orderBy('id','desc')
        //                         ->get();



        $customer_contacts = DB::table('customer_contacts')
                            ->select('customer_contacts.id', 'customer_contacts.name', 'customer_contacts.email',
                            'customer_contacts.phone', 'customer_contacts.called', 'customer_contacts.content', 'customer_contacts.notes',
                            'customer_contacts.created_at', 'users.status as registered')
                            ->leftJoin('users', 'customer_contacts.email', '=', 'users.email')
                            ->orderBy('id','desc')
                            ->get();
        return response()->json(['data' => $customer_contacts], 200);        
    }

    // public function called(Request $request){
    //     $customer_contact = DB::table('customer_contacts')
    //                             ->where('id', '=', $request->id)
    //                             ->get();
    //     if($customer_contact == null){
    //         return response()->json(['message' => 'Liên hệ này không tồn tại'], 400);
    //     }else{
    //         DB::table('customer_contacts')
    //         ->where('id', $request->id)
    //         ->update(['called' => 'true']);
    //         return response()->json(['message' => 'Liên hệ thành công', 'status' => 'success'], 200);
    //     }
    // }

    public function edit($id){
        $customer_contact = DB::table('customer_contacts')
                            ->select('customer_contacts.id', 'customer_contacts.name', 'customer_contacts.email',
                            'customer_contacts.phone', 'customer_contacts.called', 'customer_contacts.content', 'customer_contacts.notes',
                            'customer_contacts.created_at', 'users.status as registered')
                            ->leftJoin('users', 'customer_contacts.email', '=', 'users.email')
                            ->where('customer_contacts.id', '=', $id)
                            ->first();
        return response()->json(['customer_contact' =>  $customer_contact], 200);
    }

    public function show($id){
        $customer_contact = DB::table('customer_contacts')
                            ->select('customer_contacts.id', 'customer_contacts.name', 'customer_contacts.email',
                            'customer_contacts.phone', 'customer_contacts.called', 'customer_contacts.content', 'customer_contacts.notes',
                            'customer_contacts.created_at', 'users.status as registered')
                            ->leftJoin('users', 'customer_contacts.email', '=', 'users.email')
                            ->where('customer_contacts.id', '=', $id)
                            ->first();
        return response()->json(['customer_contact' =>  $customer_contact], 200);
    }

    public function update(Request $request){
        $name = $request->called;
        $name = $request->notes;
        $customer_contact = DB:: table('customer_contacts')
                            ->where('id', '=', $request->id)
                            ->get();
        if($customer_contact == null){
            return response()->json(['message' => 'Liên hệ này không tồn tại'], 400);
        }else{
            DB::table('customer_contacts')
            ->where('id', $request->id)
            ->update([
                'called' => $request->called,
                'notes'  => $request->notes,
            ]);
            return response()->json(['status' => 'success'], 200);
        }
    }




}
