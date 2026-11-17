# Uji webhook WAHA di lokal (tanpa WAHA sungguhan).
#
# Contoh:
#   .\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload-nomor-12.json
#   .\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload-unauthorized.json
#   .\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload.json
#
# Secret & HMAC key dibaca dari .env bila tidak diberikan lewat parameter.
# Skrip ini TIDAK menyalakan server; jalankan dulu:
#   php artisan serve --port=8000

param(
    [Parameter(Mandatory = $true)][string]$Payload,
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Secret,
    [string]$HmacKey,
    # Acak payload.id supaya bisa diuji berkali-kali (tanpa kena idempotensi 10 menit).
    [switch]$Fresh
)

$envPath = Join-Path $PSScriptRoot '..\..\.env'

function Read-EnvValue([string]$key) {
    if (-not (Test-Path $envPath)) { return '' }
    $line = Get-Content $envPath | Where-Object { $_ -match "^$key=" } | Select-Object -First 1
    if (-not $line) { return '' }
    return ($line -replace "^$key=", '').Trim().Trim('"')
}

if (-not $Secret) { $Secret = Read-EnvValue 'WAHA_WEBHOOK_SECRET' }
if (-not $HmacKey) { $HmacKey = Read-EnvValue 'WAHA_WEBHOOK_HMAC_KEY' }

if (-not (Test-Path $Payload)) {
    Write-Error "Payload tidak ditemukan: $Payload"
    exit 1
}

if (-not $Secret) {
    Write-Error 'WAHA_WEBHOOK_SECRET kosong. Isi .env atau lewatkan -Secret.'
    exit 1
}

$url = ($BaseUrl.TrimEnd('/')) + '/api/whatsapp/webhook/' + $Secret

$tempFile = $null

if ($Fresh) {
    # -Encoding UTF8 wajib: PowerShell 5.1 membaca berkas tanpa BOM sebagai ANSI,
    # yang merusak karakter – dan — pada teks pengingat.
    $json = Get-Content -Raw -Encoding UTF8 $Payload | ConvertFrom-Json
    $json.payload.id = 'local_' + [guid]::NewGuid().ToString('N')

    $tempFile = [IO.Path]::GetTempFileName()
    [IO.File]::WriteAllText($tempFile, ($json | ConvertTo-Json -Depth 20 -Compress), (New-Object Text.UTF8Encoding($false)))
    $Payload = $tempFile
}

$bytes = [IO.File]::ReadAllBytes((Resolve-Path $Payload))

$headers = @('-H', 'Content-Type: application/json')
$headers += @('-H', 'Accept: application/json')

if ($HmacKey) {
    $hmac = [Security.Cryptography.HMACSHA512]::new([Text.Encoding]::UTF8.GetBytes($HmacKey))
    $signature = -join ($hmac.ComputeHash($bytes) | ForEach-Object { $_.ToString('x2') })
    $headers += @('-H', "X-Webhook-Hmac: $signature")
}

Write-Host "POST $url" -ForegroundColor Cyan
Write-Host ("Payload      : " + (Split-Path $Payload -Leaf))
Write-Host ("HMAC         : " + $(if ($HmacKey) { 'ya' } else { 'tidak' }))
Write-Host ''

curl.exe -s -o - -w "`nHTTP %{http_code}`n" -X POST $url @headers --data-binary "@$Payload"

if ($tempFile -and (Test-Path $tempFile)) {
    Remove-Item -Force $tempFile
}
