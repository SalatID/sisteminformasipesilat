<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

/**
 * Feature test memakai database MySQL terpisah (`sip_testing` secara default)
 * dan hanya membuat tabel yang dibutuhkan fitur notifikasi terjadwal.
 * Database pengembangan (MySQL `sip`) tidak pernah disentuh.
 *
 * SQLite tidak dipakai karena ekstensi pdo_sqlite tidak tersedia di server
 * pengembangan/target.
 */
trait UsesScheduledNotificationDatabase
{
    protected function prepareScheduledNotificationDatabase(): void
    {
        $database = (string) env('DB_TEST_DATABASE', 'sip_testing');
        $base = (array) config('database.connections.mysql');

        $connection = array_merge($base, [
            'database' => $database,
            'prefix' => '',
            'strict' => true,
        ]);

        config([
            'database.connections.schedule_test' => $connection,
            'database.default' => 'schedule_test',

            // Cache file dipakai bersama antar kelas test; idempotensi webhook
            // (kunci sha1 message id) akan "menelan" pesan test berikutnya.
            'cache.default' => 'array',
            'queue.default' => 'sync',
        ]);

        $this->createTestDatabase($connection);
        DB::purge('schedule_test');

        Schema::dropIfExists('scheduled_notification_runs');
        Schema::dropIfExists('scheduled_notification_targets');
        Schema::dropIfExists('scheduled_notifications');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        // Tabel minimal Spatie Permission: sidebar memakai @canany sehingga
        // tanpa tabel ini halaman admin yang memakai layout akan error.
        $this->createPermissionTables();

        (require database_path('migrations/2026_11_20_000001_create_scheduled_notifications_table.php'))->up();
        (require database_path('migrations/2026_11_20_000003_create_scheduled_notification_targets_table.php'))->up();
        (require database_path('migrations/2026_11_20_000002_create_scheduled_notification_runs_table.php'))->up();
        (require database_path('migrations/2026_11_20_000004_add_targets_to_scheduled_notification_runs_table.php'))->up();

        // Database uji dipakai ulang antar-test: mulai dari keadaan bersih
        // agar kode run/provider message id dari test sebelumnya tidak
        // mengganggu resolusi.
        DB::table('scheduled_notification_runs')->delete();
        DB::table('scheduled_notification_targets')->delete();
        DB::table('scheduled_notifications')->delete();

        // Lock runner disimpan di cache file; lepaskan agar test berikutnya
        // tidak dianggap "locked" bila test sebelumnya selesai normal.
        foreach (['scheduled-notification:runner'] as $lockName) {
            Cache::lock($lockName, 300)->forceRelease();
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createPermissionTables(): void
    {
        $this->createPermissionTable();

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });
    }

    private function createPermissionTable(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createTestDatabase(array $config): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            (string) ($config['host'] ?? '127.0.0.1'),
            (string) ($config['port'] ?? '3306'),
        );

        $pdo = new PDO(
            $dsn,
            (string) ($config['username'] ?? 'root'),
            (string) ($config['password'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $database = str_replace('`', '', (string) $config['database']);

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $database,
        ));
    }
}
