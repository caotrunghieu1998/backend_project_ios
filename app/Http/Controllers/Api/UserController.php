<?php

namespace App\Http\Controllers\Api;

use App\ApiResult\ApiResult;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $apiResult;

    // Construct
    function __construct()
    {
        $this->apiResult = ApiResult::getInstance();
    }
    // Register
    public function register(Request $request)
    {
        try {
            // Check field
            $validator = Validator::make($request->all(), [
                'name'              => 'required|min:2|max:100',
                'phone'             => 'required|min:10|max:20',
                'email'             => 'required|email|min:6',
                'password'          => 'required|min:6|max:60',
                'confirm_password'  => 'required|min:6|max:60',
                'type_id'           => 'required|min:1'
            ]);

            if ($validator->fails()) {
                $this->apiResult->setError("Some field is not true");
                return response($this->apiResult->toResponse());
            }

            // Check Password
            if ($request->password != $request->confirm_password) {
                $this->apiResult->setError("Password and confirm password is not same");
                return response($this->apiResult->toResponse());
            }
            // Check Email Exist
            $checkEmail = User::where('email', $request->email)->first();
            if ($checkEmail) {
                $this->apiResult->setError("This email has been used");
                return response($this->apiResult->toResponse());
            }
            $today = getdate();
            // Register User
            $user = User::create([
                'type_id' => $request->type_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
            $token = Hash::make(Str::random(32));
            $user->email_verify_token = $token;
            $user->save();
            // We need Feature Send Email here
            $to_name = "Ét o ét Coffee";
            $to_email = $user->email;
            $data = array(
                "fullName" => $user->name,
                "url_verify" => $this->get_url_sever() . '/api/user/active-account?email_verify_token=' . $token,
            );

            Mail::send('emails.confirm', $data, function ($message) use ($to_name, $to_email) {
                $message->to($to_email)->subject('Verify Email'); //send this mail with subject
                $message->from($to_email, $to_name); //send from this mail
            });


            $this->apiResult->setData("Register success. Please confirm your email");
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when register",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }

    /** ACTIVE ACCOUNT */
    public function activeAccount(Request $request)
    {
        // Get value in request
        $token = $request->email_verify_token;
        $isSuccess = false;
        if ($token) {
            // Get Member
            $user = User::where('email_verify_token', $token)->first();
            if ($user) {
                $isSuccess = true;
                // Get date
                $date = $this->getDatetimeVietNamNow();
                // Set value
                $user->is_confirm = true;
                $user->email_verify_token = null;
                $user->confirm_at = $date;
                $user->save();
            }
            $email = $user ? $user->email : null;
            $fullName = $user ? $user->name : null;
            $data = [
                "isSuccess" => $isSuccess,
                "email" => $email,
                "fullName" => $fullName,
            ];
        } else {
            $data = [
                "isSuccess" => false,
                "email" => null,
                "fullName" => null,
            ];
        }

        return view('emails.activeAccount', $data);
    }

    // FUNCTION SUPPORT
    // Get datetime Viet Nam Now
    private function getDatetimeVietNamNow()
    {
        // Get date
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        return date('Y/m/d H:i:s', time());
    }

    //Get URL Sever
    public function get_url_sever()
    {
        $server_name = $_SERVER['SERVER_NAME'];

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $port = ":$_SERVER[SERVER_PORT]";
        } else {
            $port = '';
        }

        if (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }
        return $scheme . '://' . $server_name . $port;
    }
}
