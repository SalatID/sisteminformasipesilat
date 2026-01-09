<?php

namespace App\Http\Controllers;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->role = new Role();
    }
    public function index()
    {
        $roles = Role::with(['permissions'])->get();
        // dd($roles);
        return view('pages.admin.user_management.role.list',compact('roles'));
    }
    public function create(){
        
    }
    public function store(){
        $params = array_filter(request()->all(),function($key){
            return in_array($key,["name"])!==false;
        },ARRAY_FILTER_USE_KEY);
        // $params['updated_user']=auth()->user()->id;
        $role = Role::create($params);
        if ($role){
            return redirect()->route('roles.index')->with(["error"=>false,"message"=>"Insert Sucess"]);
        }
        return redirect()->back()->with(["error"=>true,"message"=>"Insert Failed"]);
    }
    public function show(){
        
    }
    public function edit($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $role = Role::find($decrypted);
            $permissions = Permission::all()->pluck('name','id');
            return view('pages.admin.user_management.role.edit', compact('permissions', 'role'));
        } catch (DecryptException $e) {
            return response()->json(["error"=>true,"message"=>"Decript Failed"]);
        }
        $role = Role::find($id);
        $permissions = Permission::all()->pluck('name','id');
        return view('pages.admin.user_management.role.edit', compact('permissions', 'role'));
    }
    public function update($id){
        try{
            $decrypted = Crypt::decryptString($id);
            $params = array_filter(request()->all(),function($key){
                return in_array($key,["name"])!==false;
            },ARRAY_FILTER_USE_KEY);
            // $params['updated_user']=auth()->user()->id;
            $role = Role::find($decrypted);
            $role->update($params);
            $role->syncPermissions(request()->input('permissions', []));
            if ($role){
                return redirect()->route('roles.index')->with(["error"=>false,"message"=>"Update Sucess"]);
            }
            return redirect()->back()->with(["error"=>true,"message"=>"Update Failed"]);
        } catch (DecryptException $e) {
            return redirect()->back()->with(["error"=>true,"message"=>"Decript Failed"]);
        }
    }
    public function destroy($id){
         try{
            $decrypted = Crypt::decryptString($id);
            $permission = Role::find($decrypted);
            $permission->delete();
            if ($permission){
                return response()->json(["error"=>false,"message"=>"Delete Sucess"]);
            }
            return response()->json(["error"=>true,"message"=>"Delete Failed"]);
        } catch (DecryptException $e) {
            return response()->json(["error"=>true,"message"=>"Decript Failed"]);
        }
    }
}
