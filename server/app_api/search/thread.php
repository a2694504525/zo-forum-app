<?php
require dirname(__DIR__).'/bootstrap.php';method('GET');$type=$_GET['type']??'thread';$q=$_GET['keyword']??'';if(!is_string($q)||strlen($q)>240||trim($q)==='')respond(null,422,'请输入搜索关键词');$like='%'.str_replace(['!','%','_'],['!!','!%','!_'],trim($q)).'%';
if($type==='thread')respond(thread_list(" AND t.subject LIKE ? ESCAPE '!'",[$like]));
if($type==='author'){$p=paging(one("SELECT COUNT(*) n FROM ".table('common_member')." WHERE username LIKE ? ESCAPE '!'",[$like])['n']);$offset=($p['page']-1)*20;respond(['list'=>rows("SELECT uid,username,credits FROM ".table('common_member')." WHERE username LIKE ? ESCAPE '!' LIMIT ".$offset.',20',[$like]),'page'=>$p]);}
if($type==='forum'){$list=[];foreach(public_forums() as $f)if(mb_stripos(strip_tags($f['name']),$q)!==false)$list[]=['fid'=>$f['fid'],'name'=>strip_tags($f['name'])];respond(['list'=>$list,'page'=>paging(count($list))]);}respond(null,422,'搜索类型无效');
