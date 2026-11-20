<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Membuat permission modul "notif-jadwal" agar menu langsung bisa dipakai
 * setelah migrate. Permission juga bisa dibuat manual lewat halaman
 * Permission yang sudah ada; migration ini hanya memastikan super-admin
 * tidak terkunci.
 */
return new class extends Migration
{
    private const MODULE = 'notif-jadwal';

    private const SUFFIXES = ['list', 'create', 'edit', 'delete'];

    public function up(): void
    {
        $now = now();
        $guard = 'web';

        foreach (self::SUFFIXES as $suffix) {
            $name = self::MODULE.'_'.$suffix;

            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => $guard,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleId = DB::table('roles')
            ->where('name', 'super-admin')
            ->where('guard_name', $guard)
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_map(fn (string $suffix) => self::MODULE.'_'.$suffix, self::SUFFIXES))
            ->where('guard_name', $guard)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $names = array_map(fn (string $suffix) => self::MODULE.'_'.$suffix, self::SUFFIXES);

        $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
