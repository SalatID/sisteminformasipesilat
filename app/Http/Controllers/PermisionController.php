<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Crypt;

class PermisionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy("name")->get();
        // dd($users);
        return view('pages.admin.user_management.permission.list',compact('permissions'));
    }
    public function create()
    {
        
    }
    public function store()
    {
        $default = ["create","list","edit","delete"];
        $role = Role::where('name','super-admin')->first();
        if(request('all')=='on'){
            foreach($default as $def){
                $params = array_filter(request()->all(),function($key){
                    return in_array($key,["name"])!==false;
                },ARRAY_FILTER_USE_KEY);
                $params['name']=strtolower($params['name'].'_'.$def);
                if (!Permission::where($params)->exists()){
                    $permission = Permission::create($params);
                    $role->syncPermissions($permission);
                }
            }
        } else {
            $params = array_filter(request()->all(),function($key){
                return in_array($key,["name"])!==false;
            },ARRAY_FILTER_USE_KEY);
            // $params['updated_user']=auth()->user()->id;
            $params['name']=strtolower($params['name']);
            if (!Permission::where($params)->exists()){
                $permission = Permission::create($params);
                $role->syncPermissions($permission);
            }
        }
        $role->givePermissionTo(Permission::all());      
        if(!isset($permission)){
            return redirect()->back()->with(["error"=>true,"message"=>"Insert Failed, Permission Exist"]);
        }
        if ($permission){
            return redirect()->route('permissions.index')->with(["error"=>false,"message"=>"Insert Sucess"]);
        }
        return redirect()->back()->with(["error"=>true,"message"=>"Insert Failed"]);
    }
    public function show()
    {
        
    }
    public function edit($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $permission = Permission::find($decrypted);
            return response()->json($permission);
        } catch (DecryptException $e) {
            return response()->json(["error"=>true,"message"=>"Decript Failed"]);
        }
    }
    public function update($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $params = array_filter(request()->all(),function($key){
                return in_array($key,["name"])!==false;
            },ARRAY_FILTER_USE_KEY);
            // $params['updated_user']=auth()->user()->id;
            $permission = Permission::find($decrypted);
            $permission->update($params);
            if ($permission){
                return redirect()->route('permissions.index')->with(["error"=>false,"message"=>"Update Sucess"]);
            }
            return redirect()->back()->with(["error"=>true,"message"=>"Update Failed"]);
        } catch (DecryptException $e) {
            return redirect()->back()->with(["error"=>true,"message"=>"Decript Failed"]);
        }
        
    }
    public function destroy($id)
    {
        try{
            $decrypted = Crypt::decryptString($id);
            $permission = Permission::find($decrypted);
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
