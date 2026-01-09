<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\Rules\Numeric as Numeric;
use App\Rules\Lowercase as Lowercase;
use App\Rules\Uppercase as Uppercase;
use App\Rules\SpecialCharacter as SpecialCharacter;
use Illuminate\Support\Facades\Http;
use Storage;
use App\Models\Apps\City;
use App\Models\Apps\Suburb;
use App\Models\Apps\Area;
use App\Models\Apps\User;


class WebController extends Controller
{
    public function index()
    {
        $agent = new Agent();
        if ($agent->isMobile() || $agent->isTablet()){
            $view ='web-mobile';
        }else {
            $view = 'web';
        }
        return view($view);
    }
    public function registerSeller()
    {
        return view('pages.guest.registerSeller');
    }

    public function registerSellerValidate()
    {
        return view('pages.guest.registerSellerValidate');
    }

    public function procRegisterSeller()
    {
        $credentials = request()->validate([
            'email' => ['required', 'email'],
            'phoneNumber' => ['required', 'numeric'],
            "password"=> ['required',
                'string',
                'min:8',
                new Lowercase , new Numeric, new Uppercase , new SpecialCharacter
            ],
            "retype_password"=>"same:password",
            'profileImg' => ['required','image'],
            'name' => ['required'],
            'description' => ['required'],
            'instagram' => ['required'],
            'storeFoto' => ['required','image'],
            'cityId' => ['required'],
            'provinceId' => ['required'],
            'cityId' => ['required'],
            'suburbId' => ['required'],
            'areaId' => ['required'],
            'alamat' => ['required'],
            'postalCode' => ['required'],
        ]);
        if(!session()->has('dd_token')){
            $response = Http::post(env('API_DD_URL').':7171/oauth/token', [
                'grant_type' => env('GRANT_TYPE'),
                'client_id' => env('CLIENT_ID'),
                'client_secret' => env('CLIENT_SECRET'),
                'scope' => env('SCOPE'),
            ]);
            session()->put('dd_token',$response->object());
        }
        $params = $this->generateReqParams(request());
        $token = session()->get('dd_token');
        $save = Http::withHeaders([
            'Authorization' => $token->token_type.' '.$token->access_token,
            'Content-Type' => 'application/json'
        ])->post(env('API_DD_URL').':7171/api/registration/signup-v2', $params);
        if($save->object()==null) return redirect()->back()->with(['error'=>true,'message'=>'Registration Failed, code '.$save->getStatusCode()]);
        if($save->object()->Success){
            return redirect()->route('register-seller-validate');
        }
        return redirect()->back()->with(['error'=>true,'message'=>'Registration Failed '.$save->object()->Message]);
    }
    public function generateReqParams($req)
    {
        $explode = explode(' ',$req->name);
        $firstName = $explode[0];
        unset($explode[0]);
        $lastName = implode(' ',$explode);

        $exCountry = explode(':',$req->countryId);
        $countryId = $exCountry[0];
        $countryName = $exCountry[1]??'';

        $exProvince = explode(':',$req->provinceId);
        $provinceId = $exProvince[0];
        $provinceName = $exProvince[1]??'';

        $exCity = explode(':',$req->cityId);
        $cityId = $exCity[0];
        $cityName = $exCity[1]??'';

        $exSuburb = explode(':',$req->suburbId);
        $suburbId = $exSuburb[0];
        $suburbName = $exSuburb[1]??'';

        $exArea = explode(':',$req->areaId);
        $areaId = $exArea[0];
        $areaName = $exArea[1]??'';

        $filename = 'registration-seller-profile-image-'.(implode('-',explode(' ',$req->name))).'-'.time().'.'.$req->file('profileImg')->extension();
        if(!Storage::disk((env('UPLOAD_PATH','public_uploads')))->putFileAs('/images', $req->file('profileImg'),$filename)) {
            DB::rollBack();
            return redirect()->back()->with(["error"=>true,"message"=>"Upload Image Failed"]);
        }
        $profileImage = env('DEALDULU_ASSET','').'images'.'/'.$filename;

        $filename = 'registration-seller-store-image-'.(implode('-',explode(' ',$req->name))).'-'.time().'.'.$req->file('storeFoto')->extension();
        if(!Storage::disk((env('UPLOAD_PATH','public_uploads')))->putFileAs('/images', $req->file('storeFoto'),$filename)) {
            DB::rollBack();
            return redirect()->back()->with(["error"=>true,"message"=>"Upload Image Failed"]);
        }
        $storeImage = env('DEALDULU_ASSET','').'images'.'/'.$filename;

        $params = [
            'username' => $req->email,
            'password' => $req->password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'accountType' => 'APP',
            'imei' => (string)time(),
            'email' => $req->email,
            'fcm_token' => '',
            'userRight' => 0,
            'phoneNumber' => $req->phoneNumber,
            'merchantInput' => [
                'name' => $req->name,
                'alamat' => $req->alamat,
                'description' => $req->description,
                'socialMedia' => [
                    'instagram' => $req->instagram,
                ],
                'profileImg' => $profileImage, //upload dulu
                'countryId' => (int)$countryId,
                'countryName' => $countryName,
                'provinceId' => (int)$provinceId,
                'provinceName' => $provinceName,
                'cityId' => (int)$cityId,
                'cityName' => $cityName,
                'suburbId' => (int)$suburbId,
                'suburbName' => $suburbName,
                'areaId' => (int)$areaId,
                'areaName' => $areaName,
                'postalCode' => (int)$req->postalCode,
                'lat' => '-6.323232',
                'long' => '+6.123123',
                'isVerified' => false,
                'isOfficial' => false,
                'isActive' => false,
                'additionalData' => [
                    'store_image' => $storeImage,
                ],
            ],
        ];
        return $params;
    }
    public function getCity()
    {
        $id = explode(':',request('provinceId'))[0];
        return response()->json(City::where('location_province_id',$id)->get());
    }
    public function getSuburb()
    {
        $id = explode(':',request('cityId'))[0];
        return response()->json(Suburb::where('location_city_id',$id)->get());
    }
    public function getArea()
    {
        $id = explode(':',request('suburbId'))[0];
        return response()->json(Area::where('location_suburb_id',$id)->get());
    }
    public function appForgotPassword()
    {
        $token = request('token')??'';
        $parse = $this->parseJwt($token);
        if($parse->error){
            return view('pages.guest.message',(array)$parse);
        }
        return view('pages.guest.appForgotPassword');
    }
    public function procAppForgotPassword()
    {
        $credentials = request()->validate([
            "password"=> ['required',
                'string',
                'min:8',
                new Lowercase , new Numeric, new Uppercase , new SpecialCharacter
            ],
            "retype_password"=>"same:password",
        ]);
        $token = request('token')??'';
        $parse = $this->parseJwt($token);
        if($parse->error){
            return view('pages.guest.message',(array)$parse);
        }
        // $userId = $parse->user_id??'84';
        if($userId == null){
            return view('pages.guest.message',[
                "error"=>true,
                "message"=>"Token Invalid"
            ]);
        }
        $usr = User::find($userId);
        if($usr==null){
            return view('pages.guest.message',[
                "error"=>true,
                "message"=>"Something Wrong, User Not Found"
            ]);
        }
        $upd = User::where('id',$userId)->update([
            'password'=>bcrypt(request('password'))
        ]);
        if($upd){
            return view('pages.guest.message',[
                "error"=>false,
                "message"=>"Update Password Berhasil, Silahkan Login Kembali"
            ]);
        }
        return view('pages.guest.message',[
            "error"=>false,
            "message"=>"Update Password Gagal"
        ]);
    }
}
