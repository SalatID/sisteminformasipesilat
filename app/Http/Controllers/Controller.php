<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;


class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public static function GetUnitList(){
        $user = auth()->user();
        $userRoles = $user->getRoleNames();
        $units = \App\Models\Unit::when(!$userRoles->intersect(['super-admin', 'pengurus-komwil', 'korps-pelatih'])->isNotEmpty() && $userRoles->contains('pj-unit'), function ($query) use ($user) {
                return $query->where('pj_id', $user->coach_id);
            })
            ->orderBy('name', 'asc')
            ->get();
        return $units;
    }

    public static function createJwt($payloadData)
    {
        /*
         Fungsi yang digunakan untuk generate JWT Token, dari credential menjadi JWT Token
         */
        $privateKey = file_get_contents(dirname(__FILE__, 4).env('JWKS_JWT_PRIVATE_PATH'));

        if (false === $privateKey) {
            return false;
        }

        $json = file_get_contents(dirname(__FILE__, 4).env('JWKS_PATH'));
        
        if (false === $json) {
            return false;
        }
        
        $jwks = json_decode($json, true);
        // Generate a token
        $jwt = JWT::encode($payloadData, $privateKey, 'RS256', $jwks['keys'][0]['kid']);

        return $jwt;
    }

    public static function parseJwt($jwt)
    {
        try{
        /*
            Fungsi yang digunakan untuk merubah JWT Token menjadi Credential
            */
            $supportedAlgorithm = [
                'ES384','ES256', 'HS256', 'HS384', 'HS512', 'RS256', 'RS384','RS512'
            ];
            $json = file_get_contents(dirname(__FILE__, 4).env('JWKS_JWT_PUBLIC_PATH'));

            if (false === $json) {
                return false;
            }

            $jwks = self::getPublicKeyFromCertificate($json);
            $decode = JWT::decode($jwt, new Key($jwks, 'RS256'));
            return json_decode(json_encode([
                'error'=>false,
                'message'=>"Decode Success",
                'data'=>$decode
            ]));
        } catch (\Firebase\JWT\ExpiredException $e) {
            return json_decode(json_encode([
                'error'=>true,
                'message'=>"Token Expired"
            ]));
            
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return json_decode(json_encode([
                'error'=>true,
                'message'=>"Invalid Signature"
            ]));
            
        } catch (\UnexpectedValueException $e) {
            // Tangani error jika terjadi kesalahan lainnya
            return json_decode(json_encode([
                'error'=>true,
                'message'=>$e->getMessage()
            ]));
        }

    }
    public static function getPublicKeyFromCertificate($certificatePath) {
        // $certificate = file_get_contents($certificatePath);
        
        // Mendapatkan kunci publik dari file certificate.pem
        $publicKey = openssl_pkey_get_public($certificatePath);
        
        // Konversi kunci publik ke format yang dapat digunakan oleh library JWT
        $keyDetails = openssl_pkey_get_details($publicKey);
        $publicKeyString = $keyDetails['key'];
        
        // Mengembalikan kunci publik dalam format yang tepat
        return $publicKeyString;
    }

    public static function list_menu()
    {
        $menu = [
            [
                "icon"=>"fas fa-users",
                "name"=>"Pesilat",
                "src"=>"pesilat.index",
                "permision"=>["pesilat_list"],
                "children"=>[],
            ],
            [
                "id"=>"2",
                "icon"=>"fas fa-users",
                "name"=>"User Management",
                "src"=>"#",
                "permision"=>["user_list","role_list","permision_list"],
                "children"=>[
                    [
                        "icon"=>"fas fa-user",
                        "name"=>"User List",
                        "permision"=>["user_list"],
                        "src"=>"users.index"
                    ],
                    [
                        "icon"=>"fas fa-lock",
                        "name"=>"Role",
                        "permision"=>["role_list"],
                        "src"=>"roles.index"
                    ],
                    [
                        "icon"=>"fas fa-user-lock",
                        "name"=>"Permission",
                        "permision"=>["permission_list"],
                        "src"=>"permissions.index"
                    ],
                ],
            ],
            [
                "id"=>"3",
                "icon"=>"fas fa-calendar-check",
                "name"=>"Manajemen Absensi",
                "src"=>"#",
                "permision"=>["attendance-coach_list","report-attendance-unit_list","report-attendance-percentage_list","receipt-contribution-unit_list"],
                "children"=>[
                    [
                        "icon"=>"fas fa-user",
                        "name"=>"Absensi Pelatih",
                        "permision"=>["attendance-coach_list"],
                        "src"=>"attendance.coach.index"
                    ],
                    [
                        "icon"=>"fas fa-table",
                        "name"=>"Rekap Kehadiran Unit",
                        "permision"=>["report-attendance-unit_list"],
                        "src"=>"report.unit.attendance.index"
                    ],
                    [
                        "icon"=>"fas fa-chart-line",
                        "name"=>"Presentase Kehadiran",
                        "permision"=>["report-attendance-percentage_list"],
                        "src"=>"report.attendance.percentage.index"
                    ],
                    [
                        "icon"=>"fas fa-dollar-sign",
                        "name"=>"Kontribusi Pelatih",
                        "permision"=>["report-contribution-percoach_list"],
                        "src"=>"report.contribution.percoach"
                    ],
                    [
                        "icon"=>"fas fa-money-bill",
                        "name"=>"Tanda Terima Kontribusi",
                        "permision"=>["receipt-contribution-unit_list"],
                        "src"=>"receipt.contribution.history"
                    ],
                ],
            ],
        ];
        return $menu;
    }

    public static function label_color($var){
        switch ($var) {
            case 'edit':
                $color = "primary";
                break;
            case 'delete':
                $color = "danger";
                break;
            case 'create':
                $color = "success";
                break;
            case 'show' || 'list':
                $color = "info";
                break;
            
            default:
                $color = "success";
                break;
            }
            return $color;
    }
}
