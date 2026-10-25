<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$ok=version_compare(PHP_VERSION,'8.1','>=');echo 'PHP 8.1+: '.($ok?'OK':'MISSING').PHP_EOL;foreach(['pdo_mysql','mbstring','session'] as $ext){$pass=extension_loaded($ext);$ok=$ok&&$pass;echo $ext.': '.($pass?'OK':'MISSING').PHP_EOL;}
if(!is_file(dirname(__DIR__).'/.env')){echo '.env: MISSING'.PHP_EOL;$ok=false;}
if($ok){require dirname(__DIR__).'/app/bootstrap.php';try{db()->query('SELECT 1');echo 'Database: OK'.PHP_EOL;}catch(Throwable $e){echo 'Database: unavailable; check private configuration'.PHP_EOL;$ok=false;}}
exit($ok?0:1);
