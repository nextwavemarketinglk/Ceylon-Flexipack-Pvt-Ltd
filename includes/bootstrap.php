<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode','1');
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax','path'=>'/']);
    session_start();
}
if(!function_exists('mb_strlen')) { function mb_strlen(string $value): int { return (int)(iconv_strlen($value,'UTF-8') ?: strlen($value)); } }
if(!function_exists('mb_substr')) { function mb_substr(string $value,int $start,?int $length=null): string { return (string)(iconv_substr($value,$start,$length,'UTF-8') ?: substr($value,$start,$length)); } }
const SITE_NAME = 'Ceylon Packaging';
const PHONE_PRIMARY = '+94 76 767 8937';
const PHONE_SECONDARY = '+94 77 227 8937';
const WA_NUMBER = '94767678937';
const PRODUCTS_FILE = __DIR__ . '/../data/products.json';
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function products_seed(): array { $j = file_get_contents(PRODUCTS_FILE); return json_decode($j ?: '[]',true) ?: []; }
function product_by_id(string $id): ?array {foreach(products_seed() as $p) if($p['id']===$id) return $p;return null;}
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf']; }
function csrf_check(?string $token): bool {return is_string($token)&&hash_equals(csrf_token(),$token);}
function db(): ?PDO {
    static $pdo=false;
    if($pdo!==false) return $pdo;
    try {
        $config=__DIR__.'/../config.php';
        if(is_file($config)) {
            $c=require $config;
            if(($c['driver']??'')==='mysql') {
                $pdo=new PDO('mysql:host='.$c['host'].';dbname='.$c['dbname'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);return $pdo;
            }
        }
        $sqlite=__DIR__.'/../storage/ceylon.sqlite';
        if(!is_file($sqlite)) return $pdo=null;
        $pdo=new PDO('sqlite:'.$sqlite,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $pdo->exec('PRAGMA foreign_keys = ON;');return $pdo;
    } catch(Throwable $err) {error_log($err->getMessage());return $pdo=null;}
}
function is_installed(): bool {try { $p=db();return $p && (bool)$p->query('SELECT COUNT(*) FROM admins')->fetchColumn(); }catch(Throwable $ex){return false;}}
function setting(string $key,string $default=''): string { try{$p=db();if(!$p)return $default;$q=$p->prepare('SELECT value FROM settings WHERE key_name = ?');$q->execute([$key]);$v=$q->fetchColumn();return $v===false?$default:(string)$v;}catch(Throwable $ex){return $default;}}
function phone_digits(string $s): string {return preg_replace('/\D+/','',$s)??'';}
function admin_required(): void { if(empty($_SESSION['admin_id'])) {header('Location: login.php');exit;} }
function site_products(): array {
    $defaults=products_seed();
    try { $p=db(); if(!$p)return $defaults;
        $rows=$p->query('SELECT slug AS id, name_en, name_si, category, description_en, description_si, image, featured FROM products WHERE active=1 ORDER BY sort_order ASC, id ASC')->fetchAll();
        return $rows ?: $defaults;
    }catch(Throwable $e){return $defaults;}
}
