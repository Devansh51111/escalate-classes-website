<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
if(count($argv)!==3){fwrite(STDERR,"Usage: php bin/create-admin.php USERNAME EMAIL\nPassword is read from stdin, never command arguments.\n");exit(1);}
if(!filter_var($argv[2],FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Invalid email\n");exit(1);}
if((int)db()->query("SELECT COUNT(*) FROM admins")->fetchColumn()>0){fwrite(STDERR,"An administrator already exists. Use password recovery.\n");exit(1);}
fwrite(STDERR,"Enter a unique password of at least 12 characters (input may be visible): ");$password=rtrim((string)fgets(STDIN),"\r\n");if(strlen($password)<12){fwrite(STDERR,"Password too short\n");exit(1);}
$q=db()->prepare('INSERT INTO admins(username,email,password_hash) VALUES(?,?,?)');$q->execute([$argv[1],$argv[2],password_hash($password,PASSWORD_DEFAULT)]);fwrite(STDOUT,"Administrator created.\n");
