<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Redirect;
use Session;
use Validator;

class AuthController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth',["except"=>"index"]);
    }

    public function index(Request $request)
    {
        $request->session()->forget('verified');
        return view('login');
    }

    public function registration()
    {
        return view('signup');
    }

    public function postLogin(Request $request)
    {
        request()->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {

            return redirect()->intended('account/dashboard');

        }

        return Redirect::to("login")->withSuccess('Oppes! You have entered invalid credentials');
    }

    public function postRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required',
            'email'    => 'required|email|unique:customer_tbl',
            'phoneNum' => 'required|unique:customer_tbl',
            'password' => 'required|min:3',
        ]);

        if ($validator->fails()) {
            return Redirect::to("signup")->withErrors($validator)->withInput();
        }

        if (User::where(['email' => $request->email])->first()) {
            return Redirect::to("signup")->withSuccess('Email already exist');
        }

        $email_token = sha1(microtime() . $request->email);
        $request->merge([
            'id'          => Str::orderedUuid(),
            'fullName'    => $request->fullName,
            'email'       => $request->email,
            'phoneNum'    => $request->phoneNum,
            'password'    => Hash::make($request->password),
            'email_token' => $email_token,
            'token_expiration' => date('Y-m-d H:i:s', strtotime('+ 4hours')),
        ]);

        if ($responseData = User::create($request->all())) {

            session(['signupEmail' => $request->email]);
                 //Sending Email
                // $details = [
               //     'email' => $request->email,
              //     'token' => config('user-defined.main-site-url')."verify-email/".$email_token,
             //     ];
            // event(new SendEmail($request->email, $details));

            return Redirect::to("signup-success");
        }
    }

    public function successPage()
    {
        return view('signupSuccess');
    }

    public function VerificationPage()
    {
       if(!empty(session("verified"))){

        return view('VerificationPage');
        
       }else{

         return Redirect::to("login");

       }
    }



    public function VerifyUser(Request $request)
    {
        if ($searchedToken       = User::where(['email_token' => $request->VerifyCode])->first()) {
            $tokenExpirationTime = date('YmdHis', strtotime($searchedToken->token_expiration));
            $currentTime = date('YmdHis');
            if ($currentTime > $tokenExpirationTime) {

                return Redirect::to("login")->withErrors("Verification link has expire");
                
            }else{

                $searchedToken->token_validated = 1;
                $searchedToken->save();
                
                session(['verified'=>'YES']);
                return Redirect::to("VerificationPage");
            }
        } else {
            return ApiResponse::returnErrorMessage($message = "Invalid Token");
        }
    }

    
   
  

    public function create(array $data)
    {
        return User::create([
            'id' => Str::orderedUuid(),
            'fullName' => $data['fullName'],
            'email' => $data['email'],
            'phoneNum' => $data['phoneNum'],
            'password' => Hash::make($data['password']),
            'email_token' => $data['email_token'],
            'token_expiration' => date('Y-m-d H:i:s', strtotime('+ 4hours')),
        ]);
    }

    public function editUser($id)
    {
        $data = [];
        $data['list'] = User::where('Status', '<>', "DELETED")->get();
        $data['showroom'] = DB::table("showroom")->get();

        $data['info'] = User::where(['id' => $id])->first();

        return view('registration', $data);
    }

    public function updateRegistration(Request $request)
    {
        $data = [
            'fullName' => $request->fullName,
            'email' => $request->email,
            'phoneNum' => $request->phoneNum,
        ];

        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $id = $request->user_id;
        $affected = DB::table('employee')->where('id', $id)->update($data);

        if ($affected) {
            return Redirect::to("registration")->withSuccess('User Update Successfully');
        } else {
            return Redirect::to("registration");
        }
    }

    public function deleteRegistration($id)
    {

        $affected = DB::table('employee')->where('id', $id)->update(['Status' => 'DELETED']);

        if ($affected) {
            return Redirect::to("registration");
        }
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();
        return Redirect('login');
    }

    public function SetShowRoomSession($ShowRoomID)
    {

        session(['SessionShowRoomID' => $ShowRoomID]);
        return Redirect::back()->withErrors(['msg', 'The Message']);

    }

}
