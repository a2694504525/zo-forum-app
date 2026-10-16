<?php
function app_schema() {
 global $pdo;
 $pdo->exec('CREATE TABLE IF NOT EXISTS '.table('app_token').' (token_hash CHAR(64) PRIMARY KEY,uid MEDIUMINT UNSIGNED NOT NULL,expires INT UNSIGNED NOT NULL,created INT UNSIGNED NOT NULL,KEY uid(uid),KEY expires(expires)) ENGINE=InnoDB');
 $pdo->exec('CREATE TABLE IF NOT EXISTS '.table('app_rate').' (rate_key CHAR(64) PRIMARY KEY,window INT UNSIGNED NOT NULL,hits INT UNSIGNED NOT NULL) ENGINE=InnoDB');
 foreach(['favorite','like'] as $type) $pdo->exec('CREATE TABLE IF NOT EXISTS '.table('app_'.$type).' (uid MEDIUMINT UNSIGNED NOT NULL,tid MEDIUMINT UNSIGNED NOT NULL,created INT UNSIGNED NOT NULL,PRIMARY KEY(uid,tid),KEY tid(tid)) ENGINE=InnoDB');
}
function exec_sql($sql,$args=[]) { global $pdo; $s=$pdo->prepare($sql);$s->execute($args);return $s->rowCount(); }
function input($name,$default='') { global $app_input; return $app_input[$name]??$default; }
function text_input($name,$max=200,$required=true) { $s=input($name);if(!is_string($s)||strlen($s)>$max||($required&&trim($s)==='')) respond(null,422,'请检查'.$name.'参数');return trim($s); }
function id_input($name) { $v=input($name);if(!is_scalar($v)||!preg_match('/^[1-9]\d*$/D',(string)$v))respond(null,422,'ID 参数错误');return (int)$v; }
function method($allowed) { if(!in_array($_SERVER['REQUEST_METHOD'],(array)$allowed,true))respond(null,405,'请求方式不支持'); }
function rate($scope,$max=10,$seconds=600) {
 app_schema();$key=hash('sha256',$scope.'|'.($_SERVER['REMOTE_ADDR']??''));$now=time();
 exec_sql('INSERT INTO '.table('app_rate').' (rate_key,window,hits) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE hits=IF(window<=?,1,hits+1),window=IF(window<=?,VALUES(window),window)',[$key,$now,$now-$seconds,$now-$seconds]);
 $r=one('SELECT hits FROM '.table('app_rate').' WHERE rate_key=?',[$key]);if($r['hits']>$max)respond(null,429,'操作过于频繁，请稍后重试');
}
function current_user($required=false) {
 static $loaded=false,$user=null;
 if(!$loaded) { $loaded=true;$header=$_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'';
  if(preg_match('/^Bearer ([a-f0-9]{64})$/D',$header,$m)) {
   $found=one('SELECT t.uid FROM '.table('app_token').' t WHERE t.token_hash=? AND t.expires>?',[hash('sha256',$m[1]),time()]);
   if($found) $user=one('SELECT uid,username,groupid,adminid,credits,newpm,newprompt,email FROM '.table('common_member').' WHERE uid=?',[$found['uid']]);
   if($user&&in_array((int)$user['groupid'],[4,5,6,8],true))$user=null;
  }
 }
 if($required&&!$user)respond(null,401,'请登录后操作');return $user;
}
function user_public($uid,$private=false) {
 $m=one('SELECT m.uid,m.username,m.groupid,m.credits,m.regdate,m.newpm,m.newprompt,g.grouptitle,c.posts,c.threads,c.oltime,c.extcredits1,c.extcredits2 FROM '.table('common_member').' m LEFT JOIN '.table('common_usergroup').' g ON g.groupid=m.groupid LEFT JOIN '.table('common_member_count').' c ON c.uid=m.uid WHERE m.uid=?',[$uid]);
 if(!$m)respond(null,404,'用户不存在');$m['uid']=(int)$m['uid'];$m['avatar']='https://www.zozse.cn/uc_server/avatar.php?uid='.$m['uid'].'&size=middle';
 if(!$private)unset($m['newpm'],$m['newprompt']);else { $p=one('SELECT mobile,bio,gender,resideprovince,residecity FROM '.table('common_member_profile').' WHERE uid=?',[$uid]);$m['profile']=$p;$m['has_mobile']=!empty($p['mobile']); }
 return $m;
}
function issue_token($uid,$is_new=false) { app_schema();$token=bin2hex(random_bytes(32));exec_sql('INSERT INTO '.table('app_token').' (token_hash,uid,expires,created) VALUES (?,?,?,?)',[hash('sha256',$token),$uid,time()+2592000,time()]);$user=user_public($uid,true);$user['is_new']=$is_new;return ['token'=>$token,'expires_in'=>2592000,'user'=>$user]; }
function native_core($uid=0) {
 global $_G;
 static $loaded=false;
 if(!$loaded) {
  $loaded=true;$saved=[];foreach($GLOBALS as $k=>$v){if(!in_array($k,['GLOBALS','_GET','_POST','_COOKIE','_REQUEST','_FILES','_SERVER','_ENV','_G'],true))$saved[$k]=$v;}define('CURSCRIPT','app_api');require_once dirname(__DIR__).'/source/class/class_core.php';foreach($saved as $k=>$v)$GLOBALS[$k]=$v;global $_G;
  $app=C::app();$app->init_user=false;$app->init_session=false;$app->init_cron=false;$app->init_misc=false;$app->init_mobile=false;$app->init();
  set_exception_handler('app_exception');
  require_once libfile('function/member');loaducenter();loadcache('plugin');
 }
 $_G['adminid']=0;$_G['uid']=(int)$uid;$_G['member']=$uid?getuserbyuid($uid,1):[];$_G['adminid']=(int)($_G['member']['adminid']??0);$_G['username']=$_G['member']['username']??'';$_G['groupid']=$uid?(int)$_G['member']['groupid']:7;loadcache('usergroup_'.$_G['groupid']);$_G['group']=$_G['cache']['usergroup_'.$_G['groupid']]??[];
}
function lock_user($purpose,$uid) { global $pdo;$name='zoapp_'.substr(hash('sha256',$purpose.'|'.$uid),0,56);$s=$pdo->prepare('SELECT GET_LOCK(?,10)');$s->execute([$name]);if((int)$s->fetchColumn()!==1)respond(null,429,'操作繁忙，请稍后重试');register_shutdown_function(function()use($pdo,$name){$s=$pdo->prepare('SELECT RELEASE_LOCK(?)');$s->execute([$name]);}); }
