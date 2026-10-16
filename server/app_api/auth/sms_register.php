<?php
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__).'/sms.php';method('POST');rate('sms_verify',20,600);$c=sms_config();$tel=sms_tel();$code=text_input('code',8);$register=basename(__FILE__)==='sms_register.php';if(!$register&&empty($c['tel_reglogin']))respond(null,403,'论坛未开放短信登录');consume_sms($tel,$register?0:4,$code,$c);
$bound=one('SELECT uid FROM '.table('comiis_sms_user').' WHERE tel=? ORDER BY dateline DESC LIMIT 1',[$tel]);$is_new=false;
if($bound&&$register)respond(null,422,'手机号已注册');if($bound)$uid=(int)$bound['uid'];else{
 $id=(string)random_int(1000000,9999999);$username=$register&&input('username')?text_input('username',45):str_replace('{id}',$id,$c['reg_name']??'手机用户{id}');$password=$register&&input('password')?text_input('password',256):'Zo!'.bin2hex(random_bytes(12));$email=$register&&input('email')?text_input('email',150):'mob'.str_replace('{id}',(string)random_int(100000,999999),$c['reg_email']??'{id}@mobile.invalid');
 $uid=create_member($username,$password,$email,(int)($c['reg_group']??0));exec_sql('INSERT INTO '.table('comiis_sms_user').' (uid,tel,regip,type,state,dateline,province,ua) VALUES (?,?,?,1,2,?,?,?)',[$uid,$tel,$_SERVER['REMOTE_ADDR'],time(),'APP',substr($_SERVER['HTTP_USER_AGENT']??'APP',0,250)]);exec_sql('UPDATE '.table('common_member_profile').' SET mobile=? WHERE uid=?',[$tel,$uid]);$is_new=true;
}
$m=one('SELECT groupid FROM '.table('common_member').' WHERE uid=?',[$uid]);if(!$m||in_array((int)$m['groupid'],[4,5,6,8],true))respond(null,403,'账号需审核或状态不允许登录');respond(issue_token($uid,$is_new));
