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
                $this->apiResult->setError("Some field is not true !!");
                return response($this->apiResult->toResponse());
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
                "System error when login",
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
                "System error when logout",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }
    // Get profile
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            $this->apiResult->setData($user);
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when get profile",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }

    // Get List User
    public function getListUser(Request $request)
    {
        try {
            $user = $request->user();
            $isAdmin = User::join('user_types', 'users.type_id', '=', 'user_types.id')
                ->where([
                    ['user_types.rule', '=', 'ADMIN'],
                    ['users.id', '=', $user->id]
                ])
                ->select('users.*')
                ->first();
            if ($isAdmin) {
                // Register User
                $listUser = User::join('user_types', 'users.type_id', '=', 'user_types.id')
                    ->where([
                        ['user_types.rule', '!=', 'ADMIN']
                    ])
                    ->select('users.*', 'user_types.rule')
                    ->orderBy('user_types.id')
                    ->get();
                $this->apiResult->setData($listUser);
            } else {
                $this->apiResult->setError("You are not ADMIN type");
            }
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when get List User",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }

    // Change active Status
    public function changeActiveStatus(Request $request)
    {
        try {
            $user = $request->user();
            $isAdmin = User::join('user_types', 'users.type_id', '=', 'user_types.id')
                ->where([
                    ['user_types.rule', '=', 'ADMIN'],
                    ['users.id', '=', $user->id]
                ])
                ->select('users.*')
                ->first();
            if ($isAdmin) {
                // Check Staff
                $staff = User::join('user_types', 'users.type_id', '=', 'user_types.id')
                    ->where([
                        ['users.id', '=', $request->user_id]
                    ])
                    ->select('users.*', 'user_types.rule')
                    ->first();
                if (!$staff) {
                    $this->apiResult->setError("Cannot find the Staff");
                } else if ($staff->rule == 'ADMIN') {
                    $this->apiResult->setError("Cannot deactive for ADMIN user");
                } else {
                    $userStatus = $staff->is_active == 1 ? false : true;
                    $staff->update(['is_active' => $userStatus]);
                    $message = $userStatus == false ?
                        "Deactive staff \"" . $staff->name . "\" success" :
                        "Active staff \"" . $staff->name . "\" success";
                    $this->apiResult->setData($message);
                }
            } else {
                $this->apiResult->setError("You are not ADMIN type");
            }
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when change active Status",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }

    /** SEND CODE RESET PASSWORD TO MAIL*/
    public function sentCodeResetPasswordToMail(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'email' => 'required|email|min:6',
            ]);

            if ($validator->fails()) {
                $this->apiResult->setError("Please send mail name");
                return response($this->apiResult->toResponse());
            }
            // Check Email Exist
            $user = User::where('email', $request->email)->first();
            if ($user) {
                // Add Code reset password
                $codeReset = Str::random(8);
                $user->code_reset_password = $codeReset;
                $user->save();
                // Sent mail
                $to_name = "Ét o ét coffee";
                $to_email = $user->email;
                $data = array(
                    "fullName" => $user->name,
                    "code" => $codeReset,
                );
                Mail::send('emails.sendCodeResetPassword', $data, function ($message) use ($to_name, $to_email) {
                    $message->to($to_email)->subject('Forget Password'); //send this mail with subject
                    $message->from($to_email, $to_name); //send from this mail
                });
                $this->apiResult->setData("Sent Code to mail success");
            } else {
                $this->apiResult->setError("This Email is not exist");
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when send code reset password",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }

    /** Set code reset password null */
    public function setCodeResetPasswordNull(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'email' => 'required|email|min:6',
            ]);

            if ($validator->fails()) {
                $this->apiResult->setError("Please send the mail name");
                return response($this->apiResult->toResponse());
            }
            // Check Email Exist
            $user = User::where('email', $request->email)->first();
            if ($user) {
                // Set Null Code Reset Password
                $user->code_reset_password = null;
                $user->save();
                $this->apiResult->setData("Set Null Code Reset Password success");
            } else {
                $this->apiResult->setError("This Email is not exist");
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when Set Null Code Reset Password",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }

    /** RESET PASSWORD */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|min:8',
                'email' => 'required|email|min:6',
                'new_password' => 'required|min:6|max:60',
                'confirm_password' => 'required|min:6|max:60',
            ]);

            if ($validator->fails()) {
                $this->apiResult->setError("Some field is not true");
                return response($this->apiResult->toResponse());
            }
            // Password and re password
            if ($request->new_password != $request->confirm_password) {
                $this->apiResult->setError("Password and confirm password is not same");
                return response($this->apiResult->toResponse());
            }
            // Find Member
            $user = User::where([
                ['email', '=', $request->email],
                ['code_reset_password', '=', $request->code]
            ])->first();
            if ($user) {
                $user->code_reset_password = null;
                $user->password = bcrypt($request->new_password);
                $user->save();
                $this->apiResult->setData("Update password success");
            } else {
                $this->apiResult->setError("Wrong at your code !!");
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when reset password",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }

     // Change password
    public function changeUserPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'old_password' => 'required|max:60|min:6',
                'new_password' => 'required|max:60|min:6',
                'confirm_new_password' => 'required|max:60|min:6',
            ]);

            if ($validator->fails()) {
                $this->apiResult->setError("Some Field is not true");
            } else if ($request->new_password != $request->confirm_new_password) {
                $this->apiResult->setError("Password and confirm password is not same");
            } else {
                $user = $request->user();
                if (!password_verify($request->old_password, $user->password)) {
                    $this->apiResult->setError("Wrong at old password");
                } else {
                    // Update password
                    $user->password = bcrypt($request->new_password);
                    $user->save();
                    $this->logout($request);
                    $this->apiResult->setData("Update Password Success,Please Login again");
                }
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when change user password",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }

    // Change password
    public function changeUserName(Request $request)
    {
        try {
            $validator = Validator::make($request->input(), [
                'name'  => 'required|min:2|max:100',
            ]);

            if ($validator->fails()) {
                $this->apiResult->setError("Field name is not exist.");
            } else {
                $user = $request->user();

                // Update password
                $user->name = $request->name;
                $user->save();
                $this->apiResult->setData("Update user name success.");
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when change user name",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }
}
