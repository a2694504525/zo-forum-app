<?php
function app_formula_restricted($raw,$forum=[]) {
 $u=current_user();$f=@unserialize($raw,['allowed_classes'=>false]);if(!is_array($f))return (bool)$raw;
 if(!empty($f['medal']))return true;
 if(($u['adminid']??0)==1||!empty($forum['ismoderator'])||in_array((string)($u['groupid']??7),explode("\t",$forum['spviewperm']??''),true))return false;
 if(!empty($f['users'])&&!in_array($u['username']??'',preg_split('/\r?\n/',trim($f['users'])),true))return true;
 return trim($f[1]??'')!=='';
}
function group_readaccess() { $u=current_user();$g=one('SELECT readaccess FROM '.table('common_usergroup_field').' WHERE groupid=?',[$u['groupid']??7]);return (int)($g['readaccess']??0); }
function visible_forums() {
 $u=current_user();if($u)native_core($u['uid']);$group=(int)($u['groupid']??7);$read=group_readaccess();$out=[];
 $access=[];if($u)foreach(rows('SELECT fid,allowview FROM '.table('forum_access').' WHERE uid=?',[$u['uid']]) as $a)$access[(int)$a['fid']]=(int)$a['allowview'];
 foreach(rows('SELECT f.fid,f.fup,f.type,f.name,f.threads,f.posts,f.todayposts,ff.viewperm,ff.password,ff.formulaperm,ff.price,ff.spviewperm FROM '.table('forum_forum').' f JOIN '.table('forum_forumfield').' ff ON ff.fid=f.fid WHERE f.status=1 ORDER BY f.displayorder,f.fid') as $f){$fid=(int)$f['fid'];if($f['password']||app_formula_restricted($f['formulaperm'],$f)||$f['price']||($access[$fid]??0)===-1)continue;if(($access[$fid]??0)!==1&&($f['viewperm']?($u?!forumperm($f['viewperm']):!in_array('7',explode("\t",$f['viewperm']),true)):!$read))continue;$f['fid']=$fid;$f['fup']=(int)$f['fup'];$out[$fid]=$f;}
 do{$removed=false;foreach($out as $id=>$f)if($f['fup']&&!isset($out[$f['fup']])){unset($out[$id]);$removed=true;}}while($removed);return $out;
}
function visible_thread_where() {
 $ids=[];foreach(visible_forums() as $f)if($f['type']!=='group')$ids[]=$f['fid'];$ids=implode(',',$ids)?:'0';$u=current_user();$uid=(int)($u['uid']??0);$read=group_readaccess();
 $pay=$uid?' OR t.authorid='.$uid.' OR EXISTS(SELECT 1 FROM '.table('common_credit_log').' cl WHERE cl.uid='.$uid.' AND cl.operation=\'BTC\' AND cl.relatedid=t.tid)':'';
 return 't.fid IN ('.$ids.') AND t.displayorder>=0 AND (t.readperm<='.$read.($uid?' OR t.authorid='.$uid:'').') AND (t.price=0'.$pay.') AND t.closed<=1';
}
function render_post($post,$thread) {
 $u=current_user();native_core((int)($u['uid']??0));global $_G;require_once libfile('function/forum');loadforum((int)$thread['fid'],(int)$thread['tid']);$_G['forum_thread']=$thread;$_G['tid']=(int)$thread['tid'];require_once libfile('function/discuzcode');
 require_once __DIR__.'/media_v101.php';app_qiniu_auth();return app_render_post_v101($post,$thread);
}
