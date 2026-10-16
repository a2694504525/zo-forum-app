<?php
require dirname(__DIR__).'/bootstrap.php';method('POST');$u=current_user(true);$tid=id_input('tid');$pid=id_input('pid');lock_user('post',$u['uid']);
$t=one('SELECT * FROM '.table('forum_thread').' WHERE tid=?',[$tid]);if(!$t)respond(null,404,'帖子不存在');$pt=table('forum_post'.((int)$t['posttableid']?'_'.(int)$t['posttableid']:''));$p=one('SELECT * FROM '.$pt.' WHERE tid=? AND pid=?',[$tid,$pid]);
if(!$p||(int)$p['authorid']!==(int)$u['uid'])respond(null,403,'只能删除自己发布的内容');native_core($u['uid']);global $_G;require_once libfile('function/forum');loadforum(null,$tid);
if(empty($_G['group']['alloweditpost'])&&empty($_G['adminid']))respond(null,403,'当前账号不能编辑帖子');$limit=(int)($_G['group']['edittimelimit']??0);if($limit&&empty($_G['adminid'])&&time()-(int)$p['dateline']>$limit*60)respond(null,403,'已超过论坛允许的编辑时间');
if($p['first']&&(int)$t['replies']>0)respond(null,403,'主题已有回复，请先删除自己的回复或联系管理员');
require_once dirname(__DIR__).'/media_v101.php';app_qiniu_auth();$model=C::m('forum_post',$tid,$pid);$model->attach_before_method('deletepost',['class'=>'extend_thread_image','method'=>'before_deletepost']);$model->showmessage=function($key,$url='',$values=[]){throw new RuntimeException(strip_tags(lang('message',$key,$values)));};
try{$model->deletepost(['special'=>(int)$t['special'],'isanonymous'=>(int)$p['anonymous'],'handlereplycredit'=>0]);respond(['removed'=>true]);}catch(Throwable $e){respond(null,422,$e->getMessage());}
