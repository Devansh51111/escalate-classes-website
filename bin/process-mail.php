<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/bootstrap.php';require dirname(__DIR__).'/app/features.php';
if(!in_array('--send',$argv,true)){fwrite(STDERR,"Mail is not sent without --send. Configure SMTP and inspect the queue before running.\n");exit(1);}
$rows=db()->query("SELECT * FROM notification_logs WHERE status IN ('pending','failed') AND attempt_count<3 ORDER BY id LIMIT 20")->fetchAll();
foreach($rows as$row){try{smtp_send($row['recipient'],$row['subject'],$row['body']);$status='sent';$error=null;$body='';}catch(Throwable $e){$status='failed';$error='SMTP transport failed. Check configuration without exposing credentials.';$body=$row['body'];}$q=db()->prepare('UPDATE notification_logs SET status=?,attempt_count=attempt_count+1,last_error_summary=?,body=? WHERE id=?');$q->execute([$status,$error,$body,$row['id']]);echo $row['id'].': '.$status."\n";}
