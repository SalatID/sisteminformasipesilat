<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Encryption\DecryptException;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::all();
        $roles = Role::all();
        // dd($users);
        return view('pages.admin.user_management.user.list',compact('users','roles'));
    }
    public function create()
    {
        
    }
    public function store()
    {
        $credentials = request()->validate([
            'fullname'=>['required'],
            'role'=>['required'],
            'email' => ['required', 'email','unique:users,email'],
        ]);
        $users = User::create($credentials);
        if($user){
            $role = Role::where(['name' =>  $user->role])->first();
            $user->assignRole([$role->id]);
            $this->sendEmail($user);
            return redirect()->back()->with(["error"=>false,"message"=>"Registration Successful, Please check email for activation"]);
        }
        return redirect()->back()->with(["error"=>true,"message"=>"Registration Failed"]);
    }
    public function show()
    {
        
    }
    public function edit($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $users = User::find($decrypted);
            return response()->json($users);
        } catch (DecryptException $e) {
            return response()->json(["error"=>true,"message"=>"Decript Failed"]);
        }
    }
    public function update($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $params = array_filter(request()->all(),function($key){
                return in_array($key,["fullname","email","role"])!==false;
            },ARRAY_FILTER_USE_KEY);
            // $params['updated_user']=auth()->user()->id;
            $users = User::find($decrypted);
            $oldEmail = $users->email;
            if($oldEmail != request('email')){
                $params['email_verified_at'] = NULL;
            }
            // dd($params);
            $users->update($params);
            if ($users){
                if($oldEmail != request('email')){
                    $this->sendEmail($users);
                }
                return redirect()->route('users.index')->with(["error"=>false,"message"=>"Update Sucess"]);
            }
            return redirect()->back()->with(["error"=>true,"message"=>"Update Failed"]);
        } catch (DecryptException $e) {
            return redirect()->back()->with(["error"=>true,"message"=>"Decript Failed"]);
        }
    }
    public function destroy()
    {
        
    }
    public function emailValidation($token)
    {
        try {
            $decrypted = json_decode(Crypt::decrypt($token));
            if($decrypted->expired_at <= Carbon::now()){
                return redirect()->route('login')->withErrors([
                    'error'=>true,
                    'message' => 'Link Expired, Please Contact Administrator',
                ]);
            }
            if(User::where(['email'=>$decrypted->email])->exists()){
                $email = Crypt::encrypt($decrypted->email);
                $from = Crypt::encrypt($decrypted->from);
                switch ($decrypted->from) {
                    case 'mail.register':
                        $title = "Create Password";
                    break;
                    case 'mail.forgot_password':
                        $title = "Forgot Password";
                    break;
                    default:
                    $title = "Create Password";
                }
                return view('auth.new_password',compact('email','from','title'));
            }
            return redirect()->route('login')->withErrors([
                'error'=>true,
                'message' => 'Something Wrong, Please contact Administrator',
            ]);
        } catch (DecryptException $e) {
            return redirect()->route('login')->withErrors([
                'error'=>true,
                'message' => 'Invalid Login',
            ]);
        }
    }
    public function newPassword()
    {
        try {
            $credentials = request()->validate([
                'validator'=>['required'],
                "password"=> ['required',
                    'string',
                    'min:8'
                ],
                "retype_password"=>"same:password"
            ]);
            $decrypted =Crypt::decrypt(request('validator'));
            if(User::where(['email'=>$decrypted])->update([
                'email_verified_at'=>date('Y-m-d H:i:s'),
                'password'=>bcrypt(request('password'))
                ])){
               return redirect()->route('login')->withErrors([
                    'error'=>false,
                    'message' => (request('from')=='mail.register'?'Activation':'Change password').' successful, please login',
                ]);
            }
            return redirect()->route('login')->withErrors([
                'error'=>true,
                'message' => 'Something Wrong, Please contact Administrator',
            ]);
        } catch (DecryptException $e) {
            return redirect()->route('login')->withErrors([
                'error'=>true,
                'message' => 'Invalid Login',
            ]);
        }
    }
    public function sendEmail($user,$subject="Verifikasi Email",$view="mail.register")
    {
        $url = env('APP_URL').'/register/validate/'.Crypt::encrypt(json_encode(['email'=>$user->email,'expired_at'=>Carbon::now()->addMinutes(30),"from"=>$view]));
        $data = json_encode([
            "to"=>$user->email,
            "subject"=>$subject,
            "view"=>$view,
            "data"=>[
                "username"=>$user->fullname,
                "link"=>$url
            ]
        ]);
        // return $data;
        Queue::push(new \App\Jobs\SendEmailJob($data));
    }
    public function resendActivationLink($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $users = User::find($decrypted);
            if($users==null){
                return redirect()->back()->with(["error"=>false,"message"=>"Somethink wrong"]);
            }
            $this->sendEmail($users);
            return redirect()->back()->with(["error"=>false,"message"=>"The activation link has been re-sent to the email"]);
        } catch (DecryptException $e) {
            return response()->json(["error"=>true,"message"=>"Decript Failed"]);
        }
    }
    public function forgotPassword()
    {
        return view('auth.forgot_password');
    }
    public function procForgotPassword()
    {
        $email = request()->validate([
            'email'=>['required'],
        ]);
        $user = User::where(['email'=>$email])->first();
        if ($user==null ){
            return redirect()->back()->withErrors(["error"=>true,"message"=>"Account Not Found"]);
        }
        if ($user->email_verified_at==null){
            return redirect()->back()->withErrors(["error"=>true,"message"=>"you haven't activated yet, please check your email for activation or contact the admin to resend the activation link"]);
        }
        $this->sendEmail($user,"Forgot Password","mail.forgot_password");
        return redirect()->back()->withErrors(["error"=>false,"message"=>"The activation link has been re-sent to the email"]);
    }
}
