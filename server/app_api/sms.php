<?php
function sms_config() { native_core((int)(current_user()['uid']??0));global $_G;$c=$_G['cache']['plugin']['comiis_sms']??[];if(!$c)respond(null,403,'论坛未启用短信服务');return $c; }
function sms_tel() { $tel=text_input('tel',20);if(!preg_match('/^(\+)?(86)?0?1\d{10}$/D',$tel))respond(null,422,'手机号格式不正确');return preg_replace('/^(?:\+?86)?0?(1\d{10})$/','$1',$tel); }
function challenge_check() {
 $id=text_input('challenge_id',64);$answer=text_input('verify_code',20);$r=one('SELECT * FROM '.table('app_challenge').' WHERE id=? AND expires>?',[$id,time()]);if(!$r)respond(null,422,'验证码已过期，请刷新');
 if(!hash_equals($r['answer_hash'],hash('sha256',$id.'|'.strtolower($answer)))){exec_sql('DELETE FROM '.table('app_challenge').' WHERE id=?',[$id]);respond(null,422,'图形验证码不正确，请刷新');}
 if(exec_sql('DELETE FROM '.table('app_challenge').' WHERE id=?',[$id])!==1)respond(null,422,'验证码已使用');
}
function password_rules($password) { global $_G;if(strlen($password)<max(8,(int)($_G['setting']['pwlength']??0)))respond(null,422,'密码长度不足');foreach((array)($_G['setting']['strongpw']??[]) as $rule){$patterns=[1=>'/\d/',2=>'/[a-z]/',3=>'/[A-Z]/',4=>'/[^a-zA-Z0-9]/'];if(isset($patterns[$rule])&&!preg_match($patterns[$rule],$password))respond(null,422,'密码不符合论坛复杂度规则');} }
function create_member($username,$password,$email,$groupid=0) {
 native_core();global $_G;
 if(empty($_G['setting']['regstatus']))respond(null,403,'论坛已关闭注册');
 $ip=$_SERVER['REMOTE_ADDR'];rate('register',3,3600);password_rules($password);
 if($_G['setting']['regctrl']??0){$r=one('SELECT COUNT(*) n FROM '.table('common_regip').' WHERE ip=? AND dateline>?',[$ip,time()-(int)$_G['setting']['regctrl']*3600]);if($r['n'])respond(null,429,'本 IP 暂不可再次注册');}
 $uid=uc_user_register(addslashes($username),$password,$email,'','',$ip);if($uid<=0)respond(null,422,[-1=>'用户名不符合规则',-2=>'用户名受保护',-3=>'用户名已存在',-4=>'邮箱格式错误',-5=>'邮箱域名不允许',-6=>'邮箱已使用'][$uid]??'注册失败');
 $groupid=$groupid?:((int)($_G['setting']['regverify']??0)>0?8:(int)$_G['setting']['newusergroupid']);
 C::t('common_member')->insert_user($uid,$username,md5($password),$email,$ip,$groupid,['credits'=>explode(',',$_G['setting']['initcredits']),'emailstatus'=>0]);
 if((int)($_G['setting']['regverify']??0)===2)C::t('common_member_validate')->insert(['uid'=>$uid,'submitdate'=>time(),'moddate'=>0,'admin'=>'','submittimes'=>1,'status'=>0,'message'=>'APP 注册','remark'=>''],false,true);
 if(!empty($_G['setting']['regctrl']))C::t('common_regip')->insert(['ip'=>$ip,'count'=>-1,'dateline'=>time()]);
 return (int)$uid;
}
function consume_sms($tel,$type,$code,$c) {
 lock_user('sms',hash('sha256',$tel));$r=one('SELECT * FROM '.table('comiis_sms_temp').' WHERE tel=? AND type=? AND state=1 AND dateline>? ORDER BY id DESC LIMIT 1',[$tel,$type,time()-(int)($c['code_outtime']??600)]);
 if(!$r||(int)$r['count']>=max(1,(int)($c['code_errnum']??5)))respond(null,422,'验证码已过期或尝试次数过多');
 if(!preg_match('/^\d{'.max(4,min(8,(int)($c['code_len']??6))).'}$/D',$code)||!hash_equals((string)$r['code'],$code)){exec_sql('UPDATE '.table('comiis_sms_temp').' SET count=count+1 WHERE id=?',[$r['id']]);respond(null,422,'验证码不正确');}
 if(exec_sql('UPDATE '.table('comiis_sms_temp').' SET state=0 WHERE id=? AND state=1',[$r['id']])!==1)respond(null,422,'验证码已使用');
}
function sms_provider($tel,$type,$code,$c) {
 $keys=[0=>'reg_template',1=>'lostpw_template',2=>'bd_template',3=>'unre_template',4=>'login_template'];$template=$c[$keys[$type]]??'';
 if(!$template||empty($c['appkey'])||empty($c['appsecret'])||empty($c['name']))respond(null,403,'短信服务配置不完整，请联系管理员');
 $root=dirname(__DIR__).'/source/plugin/comiis_sms/sdk/';$service=(int)$c['sms_service'];
 if($service===2){require_once $root.'tx/SmsTools.php';require_once $root.'tx/SmsSender.php';$s=new SmsSingleSender($c['appkey'],$c['appsecret']);$r=json_decode($s->sendWithParam(86,preg_replace('/^(?:\+?86)?0?(1\d{10})$/','$1',$tel),$template,[$code],$c['name'],'',''),true);return isset($r['result'])&&(int)$r['result']===0;}
 if($service===3){$content='【'.$c['name'].'】'.str_replace('{code}',$code,$template);$url='https://api.smsbao.com/sms?'.http_build_query(['u'=>$c['appkey'],'p'=>md5($c['appsecret']),'m'=>$tel,'c'=>$content]);$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true]);$r=curl_exec($ch);curl_close($ch);return trim((string)$r)==='0';}
 if($service===1){require_once $root.'TopSdk.php';$client=new TopClient();$client->appkey=$c['appkey'];$client->secretKey=$c['appsecret'];$client->format='json';$req=new AlibabaAliqinFcSmsNumSendRequest();$req->setSmsType('normal');$req->setSmsFreeSignName($c['name']);$req->setSmsParam(json_encode(['code'=>$code]));$req->setRecNum($tel);$req->setSmsTemplateCode($template);$r=$client->execute($req);return !isset($r->sub_code)&&!isset($r->error_response);}
 foreach(['Core/Regions/ProductDomain.php','Core/Http/HttpResponse.php','Core/Auth/ISigner.php','Core/Profile/IClientProfile.php','Core/AcsRequest.php','Core/IAcsClient.php','Core/RpcAcsRequest.php','Core/Config.php','Core/Profile/DefaultProfile.php','Core/DefaultAcsClient.php','Api/Sms/Request/V20170525/SendSmsRequest.php'] as $file) require_once $root.'lib/'.$file;
 Config::load();$profile=DefaultProfile::getProfile('cn-hangzhou',$c['appkey'],$c['appsecret']);DefaultProfile::addEndpoint('cn-hangzhou','cn-hangzhou','Dysmsapi','dysmsapi.aliyuncs.com');$client=new DefaultAcsClient($profile);$req=new SendSmsRequest();$req->setPhoneNumbers($tel);$req->setSignName($c['name']);$req->setTemplateCode($template);$req->setTemplateParam(json_encode(['code'=>$code]));$r=$client->getAcsResponse($req);return ($r->Code??'')==='OK';
}

function sms_province($tel,$c) {
 $allowed=array_filter(array_map('trim',explode("\n",$c['sms_province']??'')));if(!$allowed)return 'APP';
 $ch=curl_init('https://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel='.rawurlencode($tel));curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true]);$raw=curl_exec($ch);curl_close($ch);$raw=iconv('GB18030','UTF-8//IGNORE',(string)$raw);preg_match_all("/(\\w+):'([^']+)/",$raw,$m);$data=$m[1]?array_combine($m[1],$m[2]):[];if(($data['telString']??'')!==$tel)respond(null,422,'暂时无法验证手机号归属地，请稍后重试');if(!in_array($data['province']??'',$allowed,true))respond(null,403,'该手机号归属地不符合论坛短信规则');return $data['carrier']??$data['province'];
}
