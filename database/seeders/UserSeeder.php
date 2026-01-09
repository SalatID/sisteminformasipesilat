<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roleName = 'super-admin';
         app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $user = User::create([
            'fullname' => 'Super Admin', 
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
            'email_verified_at' => date('Y-m-d H:i:s'),
            'role'=>$roleName
        ]);
        Role::create([
            'name'=>$roleName
        ]);
        $user = User::find(1);
        $role = Role::find(1);

        $permisions = [
            "user_list",
            "user_create",
            "user_edit",
            "user_delete",
            "role_list",
            "role_create",
            "role_edit",
            "role_delete",
            "permission_list",
            "permission_create",
            "permission_edit",
            "permission_delete",
            "product_list",
            "product_create",
            "product_edit",
            "product_delete",
            "category_list",
            "category_create",
            "category_edit",
            "category_delete",
            "history-payment_list",
            "history-payment_create",
            "history-payment_edit",
            "history-payment_delete",
            "app-content_list",
            "app-content_create",
            "app-content_edit",
            "app-content_delete",
            "app-stories_list",
            "app-stories_create",
            "app-stories_edit",
            "app-stories_delete",
            "app-notification_list",
            "app-notification_create",
            "app-notification_edit",
            "app-notification_delete",
            "app-banner_list",
            "app-banner_create",
            "app-banner_edit",
            "app-banner_delete",
            "web-content_list",
            "web-content_create",
            "web-content_edit",
            "web-content_delete",
            "web-banner_list",
            "web-banner_create",
            "web-banner_edit",
            "web-banner_delete",
        ];
        foreach($permisions as $value){
            Permission::create(["name"=>$value]);
        }
     
        $permissions = Permission::pluck('id','id')->all();
        
        $user->assignRole([$role->id]);

        $role->syncPermissions($permissions);
 
         
         $role->givePermissionTo(Permission::all());      
    }
}
