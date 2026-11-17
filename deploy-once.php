<?php

/**
 * Skrip deploy sekali jalan untuk shared hosting tanpa akses terminal.
 *
 * CARA PAKAI:
 *   1. Upload/copy file ini ke root repositori di server (satu folder dengan artisan).
 *   2. Buat Cron Job di cPanel (sekali saja) dengan perintah:
 *      /usr/local/bin/php /home/smip6738/repositories/sisteminformasipesilat/deploy-once.php >> /home/smip6738/repositories/sisteminformasipesilat/storage/logs/cron/deploy.log 2>&1
 *   3. Buka /home/smip6738/repositories/sisteminformasipesilat/storage/logs/cron/deploy.log
 *   4. HAPUS file ini (dan cron job-nya) setelah selesai.
 *
 * Keamanan:
 *   - Perintah berasal dari daftar tetap (whitelist), bukan input bebas.
 *   - Tidak bisa dipanggil lewat browser: hanya berjalan dari CLI (php_sapi_name).
 */

// Tolak akses via web.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Hanya boleh dijalankan dari CLI.');
}

$root = __DIR__;
chdir($root);

// Semua perintah memakai pola yang sama seperti cron job manual Anda:
// /usr/local/bin/php <path>/artisan <perintah> >> <log> 2>&1
$php = PHP_BINARY;
$artisan = $root . '/artisan';
$cronLog = $root . '/storage/logs/cron';

if (!is_dir($cronLog)) {
    @mkdir($cronLog, 0775, true);
}

// Perintah yang dijalankan berurutan. Kunci = label, nilai = argumen artisan.
$steps = [
    'Versi PHP'            => null,
    'Versi Laravel'        => '--version',
    'Hapus cache lama'     => 'config:clear',
    'Migrasi database'     => 'migrate --force',
    'Cache config'         => 'config:cache',
    'Cache route'          => 'route:cache',
    'Cache view'           => 'view:cache',
    'Daftar jadwal'        => 'schedule:list',
    'Uji kirim (dry-run)'  => 'attendance:notify --type=all --dry-run --force',
];

/**
 * Jalankan artisan persis seperti di cron job, lalu kembalikan
 * [exit code, isi log].
 *
 * @return array{0: int, 1: string}
 */
function runArtisan(string $php, string $artisan, string $args, string $logDir, string $slug): array
{
    $logFile = $logDir . '/' . $slug . '.log';

    // Output ditimpa tiap kali deploy (bukan append) agar log tidak menumpuk.
    $cmd = sprintf(
        '%s %s %s > %s 2>&1',
        escapeshellarg($php),
        escapeshellarg($artisan),
        $args,
        escapeshellarg($logFile)
    );

    $code = 0;
    exec($cmd, $ignored, $code);

    return [$code, is_file($logFile) ? (string) file_get_contents($logFile) : ''];
}

$start = date('Y-m-d H:i:s');

echo "=== DEPLOY SIP ===\n";
echo "Waktu : {$start}\n";
echo "Root  : {$root}\n";
echo 'PHP   : ' . $php . ' (' . PHP_VERSION . ")\n\n";

// Folder yang wajib ada & writable (tidak ikut git).
$dirs = [
    'storage/logs/cron',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'bootstrap/cache',
    'public/storage/members',
    'public/members/documents',
    'public/contribution_receipts',
];

echo "=== FOLDER ===\n";
$notWritable = [];
foreach ($dirs as $dir) {
    $path = $root . '/' . $dir;

    if (!is_dir($path)) {
        $ok = @mkdir($path, 0775, true);
        echo ($ok ? '[DIBUAT      ] ' : '[GAGAL BUAT  ] ') . $dir . "\n";
        if (!$ok) {
            $notWritable[] = $dir;
        }
        continue;
    }

    $writable = is_writable($path);
    echo ($writable ? '[OK          ] ' : '[NOT WRITABLE] ') . $dir . "\n";
    if (!$writable) {
        $notWritable[] = $dir;
    }
}
echo "\n";

// vendor/ wajib ada; tanpa ini semua perintah gagal.
if (!is_dir($root . '/vendor')) {
    echo "!!! vendor/ TIDAK ADA — upload folder vendor/ dari komputer lokal (zip, lalu extract).\n\n";
}

$failed = [];

foreach ($steps as $label => $args) {
    $slug = 'deploy-' . trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($label)), '-');

    echo "=== {$label} ===\n";

    if ($args === null) {
        echo '[OK] ' . PHP_VERSION . "\n\n";
        continue;
    }

    [$code, $output] = runArtisan($php, $artisan, $args, $cronLog, $slug);

    echo $output === '' ? "(tidak ada output)\n" : $output . "\n";
    echo '[' . ($code === 0 ? 'OK' : 'EXIT ' . $code) . "] log: storage/logs/cron/{$slug}.log\n\n";

    if ($code !== 0) {
        $failed[] = $label;
    }
}

echo "=== RINGKASAN ===\n";

if ($failed === [] && $notWritable === []) {
    echo "[SEMUA BERHASIL]\n";
} else {
    foreach ($failed as $label) {
        echo "[GAGAL] {$label}\n";
    }
    foreach ($notWritable as $dir) {
        echo "[FOLDER BERMASALAH] {$dir}\n";
    }
}

echo "\n=== SELESAI " . date('Y-m-d H:i:s') . " ===\n";
echo "Langkah berikutnya:\n";
echo "  1. Hapus file deploy-once.php dari server.\n";
echo "  2. Hapus cron job yang memanggil deploy-once.php.\n";
echo "  3. Pasang cron scheduler: * * * * * /usr/local/bin/php {$artisan} schedule:run >> {$cronLog}/schedule.log 2>&1\n";
