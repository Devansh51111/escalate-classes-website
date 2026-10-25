<?php
declare(strict_types=1);
function enquiry_filter(): array {
 $where='e.archived_at IS NULL';$args=[];
 foreach(['status'=>'status','class'=>'class_id','session'=>'academic_session_id'] as $key=>$col){if(($_GET[$key]??'')!==''){$where.=' AND e.'.$col.'=?';$args[]=substr((string)$_GET[$key],0,80);}}
 $search=trim(substr((string)($_GET['q']??''),0,120));if($search!==''){$where.=' AND (e.parent_name LIKE ? OR e.student_name LIKE ? OR e.parent_phone LIKE ?)';array_push($args,...array_fill(0,3,'%'.$search.'%'));}
 if(($_GET['due']??'')==='1')$where.=' AND e.next_follow_up_at IS NOT NULL AND e.next_follow_up_at<=NOW()';
 return [$where,$args];
}
function enqueue_email(string $kind,string $to,string $subject,string $body,?int $enquiry=null):void {
 if(!filter_var($to,FILTER_VALIDATE_EMAIL))return;
 $q=db()->prepare('INSERT INTO notification_logs(enquiry_id,notification_type,recipient,subject,body,status) VALUES(?,?,?,?,?,?)');$q->execute([$enquiry,$kind,$to,$subject,$body,'pending']);
}
function smtp_send(string $to,string $subject,string $body):void {
 $host=(string)getenv('SMTP_HOST');$from=(string)getenv('SMTP_FROM');$port=(int)(getenv('SMTP_PORT')?:587);$mode=getenv('SMTP_ENCRYPTION')?:'tls';
 if($mode==='none'&&!in_array($host,['127.0.0.1','localhost','::1'],true))throw new RuntimeException('Unencrypted SMTP is only allowed for local tests');
 if(!$host||!filter_var($from,FILTER_VALIDATE_EMAIL)||!filter_var($to,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$host.$from.$to.$subject))throw new RuntimeException('Mail configuration invalid');
 $s=stream_socket_client(($mode==='ssl'?'ssl://':'tcp://').$host.':'.$port,$eno,$err,15,STREAM_CLIENT_CONNECT,stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]));if(!$s)throw new RuntimeException('SMTP connection failed');stream_set_timeout($s,15);
 $read=static function(array $codes)use($s):void{$last='';while(($line=fgets($s,1024))!==false){$last=$line;if(strlen($line)>=4&&$line[3]===' ')break;}if(!in_array((int)substr($last,0,3),$codes,true))throw new RuntimeException('SMTP response rejected');};
 $cmd=static function(string $line,array $codes)use($s,$read):void{if(fwrite($s,$line."\r\n")===false)throw new RuntimeException('SMTP write failed');$read($codes);};
 try{$read([220]);$cmd('EHLO localhost',[250]);if($mode==='tls'){$cmd('STARTTLS',[220]);if(!stream_socket_enable_crypto($s,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('TLS failed');$cmd('EHLO localhost',[250]);}if(getenv('SMTP_USER')){$cmd('AUTH LOGIN',[334]);$cmd(base64_encode((string)getenv('SMTP_USER')),[334]);$cmd(base64_encode((string)getenv('SMTP_PASSWORD')),[235]);}$cmd('MAIL FROM:<'.$from.'>',[250]);$cmd('RCPT TO:<'.$to.'>',[250,251]);$cmd('DATA',[354]);$body=str_replace(["\r\n","\r"],"\n",$body);$body=preg_replace('/^\./m','..',$body);$data='From: '.$from."\r\nTo: ".$to."\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nDate: ".date(DATE_RFC2822)."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".str_replace("\n","\r\n",$body);$cmd($data."\r\n.",[250]);$cmd('QUIT',[221]);}finally{fclose($s);}
}
function announcements():array{try{return db()->query('SELECT * FROM announcements WHERE is_published=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY id DESC')->fetchAll();}catch(Throwable $e){return [];}}
function managed_image(string $slot,string $fallback):array{try{$q=db()->prepare('SELECT m.stored_filename,m.alt_text FROM media m JOIN image_assignments a ON a.media_id=m.id WHERE a.slot=?');$q->execute([$slot]);$m=$q->fetch();if($m)return ['url'=>'uploads/'.$m['stored_filename'],'alt'=>$m['alt_text']];}catch(Throwable $e){}return ['url'=>$fallback,'alt'=>$slot==='logo'?'Escalate Classes book logo':($slot==='about'?'Escalate Classes exterior and institute sign':'Illustrative study desk')];}
function sql_backup():string {
 $tables=['admins','login_attempts','password_reset_tokens','academic_sessions','classes','admission_enquiries','enquiry_notes','site_settings','site_content','announcements','media','image_assignments','notification_logs'];$sql="-- Escalate Classes local backup. Contains confidential records.\nSET FOREIGN_KEY_CHECKS=0;\n";
 db()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');db()->beginTransaction();
 foreach($tables as $table){$sql.='DROP TABLE IF EXISTS `'.$table."`;\n";$schema=db()->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM);$sql.=$schema[1].";\n";foreach(db()->query('SELECT * FROM `'.$table.'`') as $row){$vals=array_map(fn($v)=>$v===null?'NULL':db()->quote((string)$v),array_values($row));$sql.='INSERT INTO `'.$table.'` VALUES ('.implode(',',$vals).");\n";}}
 db()->commit();return $sql."SET FOREIGN_KEY_CHECKS=1;\n";
}
function whatsapp_url(?string $className=null):string{
 $phone=preg_replace('/[\s()+-]/','',setting('whatsapp'));
 if(!preg_match('/^[1-9][0-9]{9,14}$/',$phone))return '';
 $message='Hello Escalate Classes, I would like to enquire about '.($className?'admissions for '.$className:'admissions').'. Please share the admission process and relevant details.';
 return 'https://wa.me/'.$phone.'?'.http_build_query(['text'=>$message],'','&',PHP_QUERY_RFC3986);
}

function content_defaults():array{return [
'about_approach_heading'=>'Strong foundations. A thoughtful approach.','about_history'=>'Teaching since 2016. More classroom photographs will be added after review.','faculty_heading'=>'Our Faculty','faculty_name'=>'Gunjan Ramchandani','faculty_role'=>'Faculty','classes_heading'=>'Find your starting point.','class_enquiry_label'=>'Enquire','contact_button'=>'View admission enquiry ↗','hero_caption'=>'Room to learn. Space to grow.','hero_eyebrow'=>'Teaching since 2016','hero_heading'=>'Small steps. Stronger futures.','hero_description'=>'A thoughtful space to learn, ask questions and grow. Discover learning support for Classes 1 to 10 at Escalate Classes.','admissions_button'=>'Explore admissions ↗','class_button'=>'Find your class →','about_heading'=>'Learning with purpose.','about_description'=>'Escalate Classes supports students in Classes 1–10 in Indore. We believe in making room for understanding, questions and consistent effort.','contact_heading'=>'Good questions. Open doors.','approach_heading'=>'Understanding first. Confidence follows.','principle_1_title'=>'Build the basics','principle_1_body'=>'Make space for clear concepts and steady practice.','principle_2_title'=>'Stay curious','principle_2_body'=>'Questions are part of learning.','principle_3_title'=>'Keep moving forward','principle_3_body'=>'Turn everyday effort into meaningful progress.','class_section_heading'=>'A place for your next chapter.','about_preview_heading'=>'Teaching since 2016.','about_preview_body'=>'A thoughtful place for learning, questions and consistent effort.','about_button'=>'About Escalate Classes ↗','cta_heading'=>'The next step starts with a question.','cta_button'=>'Enquire about admissions ↗','admissions_heading'=>'A new chapter starts here.','enquiry_heading'=>'Tell us about your learner.','privacy_notice'=>'Enquiry information is used to respond to admission requests and manage follow-ups. An enquiry is not an admission confirmation.','contact_prompt'=>'Let us talk about learning.','footer_tagline'=>'Thoughtful learning. Stronger foundations.'];}
