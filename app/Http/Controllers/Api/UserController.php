<?php

namespace App\Http\Controllers\Api;

use App\ApiResult\ApiResult;
use App\Http\Controllers\Controller;
use App\SupportFunction\SupportFunction;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $userLogin = $request->user();

            $isAdmin = User::join('user_types', 'users.type_id', '=', 'user_types.id')
                ->where([
                    ['user_types.rule', '=', 'ADMIN'],
                    ['users.id', '=', $userLogin->id]
                ])
                ->select('users.*')
                ->first();
            if ($isAdmin) {
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
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'type_id' => $request->type_id,
                ]);
                $token = Hash::make(Str::random(32));
                $user->email_verify_token = $token;
                $user->save();
                // We need Feature Send Email here
                $to_name = "Ét o ét Coffee";
                $to_email = $user->email;
                $data = array(
                    "fullName" => $user->name,
                    "url_verify" => SupportFunction::get_url_sever() . '/api/user/active-account?email_verify_token=' . $token,
                );

                Mail::send('emails.confirm', $data, function ($message) use ($to_name, $to_email) {
                    $message->to($to_email)->subject('Verify Email'); //send this mail with subject
                    $message->from($to_email, $to_name); //send from this mail
                });
                $this->apiResult->setData("Register success. Please confirm your email");
            } else {
                $this->apiResult->setError("You are not ADMIN type");
            }
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
                $date = SupportFunction::getDatetimeVietNamNow();
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
    
    // Login
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'email' => 'required|max:60|min:6|email',
                'password' => 'required|max:60|min:6',
            ]);
            if ($validator->fails()) {
                return response($this->result->setError('Some field is not true !!'));
            }
            $validated = ['email' => $request->email, 'password' => $request->password];
            if (auth()->attempt($validated)) {
                $user = auth()->user();
                if ($user->is_confirm == 0) {
                    $this->apiResult->setError("This account isn't confirm email");
                } else if ($user->is_active == 0) {
                    $this->apiResult->setError("This account has been block");
                } else {
                    $user = User::find($user->id);
                    $userLocation = "user_" . $user->id;
                    // Create Token
                    $token = $user->createToken($userLocation)->accessToken;
                    $data = [
                        "user" => $user,
                        "token" => $token,
                    ];
                    $this->apiResult->setData($data);
                }
            } else {
                $this->apiResult->setError('Wrong at email or password');
            }
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when register",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }

    // Logout
    public function logout(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            // Clear all token
            DB::table('oauth_access_tokens')
            ->where('user_id', $user_id)
                ->delete();
            $this->apiResult->setData('Logout successful');
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when register",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }
}
