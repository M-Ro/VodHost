<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\UserVerification;
use App\Mail\UserVerificationMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $verifyUser = UserVerification::create([
            'user_id' => $user->id,
            'token' => str_random(40)
        ]);

        Mail::to($user->email)->send(new UserVerificationMail($user));

        return $user;
    }

    /**
     * Verify a user account with the provided activation token.
     *
     * @param  string $token
     * @return \Illuminate\Http\Response
     */
    public function verify($token)
    {
        $userVerification = UserVerification::where('token', $token)->first();

        if (isset($userVerification)) {
            $user = $userVerification->user;

            if (!$user->verified) {
                $userVerification->user->verified = 1;
                $userVerification->user->save();

                $status = "Account verified. You may now login.";
            } else {
                $status = "Account already verified. You may login.";
            }
        } else {
            return redirect('/login')->with('warning', "Sorry your account cannot be identified.");
        }

        return redirect('/login')->with('status', $status);
    }

    /**
     * Overrides the registered method from RegistersUsers
     *
     * @param  string $token
     * @return \Illuminate\Http\Response
     */
    protected function registered(Request $request, $user)
    {
        $this->guard()->logout();

        return redirect('/login')->with('status', 'We sent you an activation code. Check your email and click on the link to verify.');
    }
}
