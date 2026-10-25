<?php
declare(strict_types=1);
$root = dirname(__DIR__);
if (is_file($root.'/.env')) {
    foreach (file($root.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key,$value] = explode('=', $line, 2);
        if (preg_match('/^[A-Z_][A-Z0-9_]*$/', trim($key)) && getenv(trim($key)) === false) putenv(trim($key).'='.trim($value, " \t\n\r\0\x0B\"'"));
    }
}
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $root.'/storage/logs/php.log');
if (PHP_SAPI !== 'cli') {
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
}
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>filter_var(getenv('SESSION_SECURE') ?: 'false',FILTER_VALIDATE_BOOLEAN)]);
session_start();
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
    static $db;
    if (!extension_loaded('pdo_mysql')) throw new RuntimeException('MySQL driver unavailable');
    if (!$db) $db = new PDO('mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').';port='.(getenv('DB_PORT') ?: '3306').';dbname='.(getenv('DB_NAME') ?: 'escalate_classes').';charset=utf8mb4',getenv('DB_USER') ?: '',getenv('DB_PASSWORD') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    return $db;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('The form expired. Reload and try again.'); } }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function admin(): bool { return isset($_SESSION['admin_id']) && (time()-(int)($_SESSION['last_seen'] ?? 0))<1800; }
function require_admin(): void { if (!admin()) { unset($_SESSION['admin_id']); redirect('?page=admin'); } $_SESSION['last_seen']=time(); }
function setting(string $key, string $fallback=''): string { static $settings; if ($settings===null) { try{$settings=db()->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR);}catch(Throwable $e){$settings=[];} } return (string)($settings[$key] ?? $fallback); }
function content(string $key,string $fallback): string { static $items; if($items===null){try{$items=db()->query('SELECT content_key,content_value FROM site_content')->fetchAll(PDO::FETCH_KEY_PAIR);}catch(Throwable $e){$items=[];}}return (string)($items[$key]??$fallback); }
function all_classes(): array { try{return db()->query('SELECT * FROM classes WHERE is_active=1 ORDER BY display_order,id')->fetchAll();}catch(Throwable $e){return array_map(fn($i)=>['id'=>$i,'name'=>'Class '.$i,'description'=>'Build understanding through consistent practice.'],range(1,10));} }
function sessions(): array { try{return db()->query('SELECT * FROM academic_sessions WHERE is_active=1 ORDER BY created_at DESC,id DESC')->fetchAll();}catch(Throwable $e){return [];} }
set_exception_handler(function(Throwable $error) use ($root){error_log(get_class($error).': '.$error->getMessage());http_response_code(500);echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Temporarily unavailable</title><h1>Something went wrong.</h1><p>Please try again. Your request has not been confirmed.</p></html>';});
