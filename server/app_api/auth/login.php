<?php
require dirname(__DIR__).'/bootstrap.php';method('POST');rate('login',15,600);
$username=text_input('username',90);$password=text_input('password',256);native_core();
$r=uc_user_login(addslashes($username),$password,0,1,(int)input('questionid',0),text_input('answer',120,false),$_SERVER['REMOTE_ADDR']);
if(!is_array($r)||(int)$r[0]<1)respond(null,422,'账号、密码或安全问答不正确');
$m=one('SELECT uid,groupid FROM '.table('common_member').' WHERE uid=?',[(int)$r[0]]);if(!$m)respond(null,403,'账号尚未激活论坛账户，请先在网站登录');if(in_array((int)$m['groupid'],[4,5,6,8],true))respond(null,403,'当前账户状态不允许登录');respond(issue_token((int)$m['uid']));
