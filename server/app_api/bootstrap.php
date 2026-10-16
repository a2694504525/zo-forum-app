<?php
// Independent public read API. Never bootstrap a browser session or trust client roles.
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function respond($data, $code=0, $msg='ok') { header('Content-Type: application/json; charset=utf-8'); http_response_code(200); echo json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function app_exception($e){ error_log('[ZO APP] '.get_class($e).': '.$e->getMessage());respond(null,500,'服务暂时不可用，请稍后重试'); }
set_exception_handler('app_exception');
$app_input=$_GET; if($_SERVER['REQUEST_METHOD']==='POST'){$app_input=$_POST;if(stripos($_SERVER['CONTENT_TYPE']??'','application/json')!==false){$body=file_get_contents('php://input');if(strlen($body)>200000)respond(null,422,'请求过大');$app_input=json_decode($body,true);if(!is_array($app_input))respond(null,422,'JSON 格式错误');}} 
define('IN_DISCUZ', true);
require dirname(__DIR__).'/config/config_global.php';
$db = $_config['db'][1];
$prefix = $db['tablepre'];
if (!preg_match('/^[a-zA-Z0-9_]+$/D',$prefix)) throw new RuntimeException('Invalid prefix');
$host=$db['dbhost']; $port=3306;
if (preg_match('/^([^:]+):(\d+)$/',$host,$m)) { $host=$m[1]; $port=(int)$m[2]; }
$pdo=new PDO('mysql:host='.$host.';port='.$port.';dbname='.$db['dbname'].';charset='.($db['dbcharset']==='utf8mb4'?'utf8mb4':'utf8'),$db['dbuser'],$db['dbpw'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
unset($db,$_config);
function table($name) { global $prefix; return '`'.$prefix.$name.'`'; }
function rows($sql,$args=[]) { global $pdo; $s=$pdo->prepare($sql); $s->execute($args); return $s->fetchAll(); }
function one($sql,$args=[]) { $r=rows($sql,$args); return $r ? $r[0] : null; }
function number($name,$default=0) { $v=$_GET[$name]??$default; if(!is_scalar($v)||!preg_match('/^\d+$/D',(string)$v)) respond(null,422,'参数格式错误'); return (int)$v; }
function paging($total) { $p=max(1,min(1000,number('page',1))); return ['page'=>$p,'perpage'=>20,'total'=>(int)$total,'total_page'=>(int)ceil($total/20)]; }
function legacy_public_forums() {
 $out=[];
 foreach(rows('SELECT f.fid,f.fup,f.type,f.name,f.threads,f.posts,f.todayposts,ff.viewperm,ff.password,ff.formulaperm,ff.price FROM '.table('forum_forum').' f JOIN '.table('forum_forumfield').' ff ON ff.fid=f.fid WHERE f.status=1 AND f.type IN (\'group\',\'forum\',\'sub\') ORDER BY f.displayorder,f.fid') as $f) {
  // Conservative guest access: custom formulas/passwords/paid forums are excluded.
  if($f['viewperm']||$f['password']||$f['formulaperm']||$f['price']) continue;
  $f['fid']=(int)$f['fid']; $f['fup']=(int)$f['fup']; $out[$f['fid']]=$f;
 }
 do { $removed=false; foreach($out as $id=>$f) if($f['fup'] && !isset($out[$f['fup']])) { unset($out[$id]); $removed=true; } } while($removed);
 return $out;
}
function public_ids() { $fs=public_forums(); $ids=[]; foreach($fs as $f) if($f['type']!=='group') $ids[]=$f['fid']; return implode(',',$ids)?:'0'; }
function thread_where() { return visible_thread_where(); }
function card($t) { if(empty($t['author'])) { $t['author']='匿名'; $t['authorid']=0; } $t['tid']=(int)$t['tid']; $t['fid']=(int)$t['fid']; $t['subject']=html_entity_decode($t['subject'],ENT_QUOTES,'UTF-8'); $t['avatar']='https://www.zozse.cn/uc_server/avatar.php?uid='.(int)$t['authorid'].'&size=middle';require_once __DIR__.'/media_v101.php';$t['cover']=app_cover_v101($t);return $t; }
function thread_list($extra='', $args=[]) {
 $sort=$_GET['sort']??'latest';if(!is_string($sort)||!in_array($sort,['latest','hot','digest'],true))respond(null,422,'排序参数无效');$where=thread_where().$extra.($sort==='digest'?' AND t.digest>0':($sort==='hot'?' AND t.lastpost>='.strtotime('today'):''));$order=$sort==='hot'?'t.views DESC,t.dateline DESC':'t.dateline DESC';
 $count=one('SELECT COUNT(*) n FROM '.table('forum_thread').' t WHERE '.$where,$args);
 $page=paging($count['n']); $offset=($page['page']-1)*20;
 $rs=rows('SELECT t.tid,t.fid,t.subject,t.author,t.authorid,t.dateline,t.views,t.replies,t.digest FROM '.table('forum_thread').' t WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$offset.',20',$args);
 return ['list'=>array_map('card',$rs),'page'=>$page];
}
function safe_url($url) {
 $url=html_entity_decode(trim($url),ENT_QUOTES,'UTF-8');
 if(preg_match('/[\x00-\x20\\\\]/',$url))return '';if(preg_match('~^https?://~i',$url)) return $url;
 if(preg_match('~^www\.[a-z0-9.-]+(?:/|$)~i',$url))return 'https://'.$url;if(substr($url,0,2)==='//') return 'https:'.$url;
 if(preg_match('~^(?:data/|static/|/[^/]|(?:forum|home|misc|plugin|space)\.php(?:\?|$))~',$url)) return 'https://www.zozse.cn/'.ltrim($url,'/');
 return '';
}
function clean_html($html) {
 if(!class_exists('DOMDocument')) return '<p>'.htmlspecialchars(strip_tags($html),ENT_QUOTES,'UTF-8').'</p>';
 $doc=new DOMDocument(); libxml_use_internal_errors(true); $doc->loadHTML('<?xml encoding="UTF-8"><div id="zo-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD); libxml_clear_errors();
 $allowed=['p','br','b','strong','em','i','u','s','blockquote','pre','code','ul','ol','li','table','thead','tbody','tr','td','th','a','img','span','div','h1','h2','h3','hr','font'];
 $walk=function($node) use(&$walk,$allowed) {
  foreach(iterator_to_array($node->childNodes) as $c) {
   if($c instanceof DOMElement) {
    $tag=strtolower($c->tagName);
    if(!in_array($tag,$allowed,true)) { if(in_array($tag,['script','iframe','object','embed','style','form','input','svg','math'],true)){$node->removeChild($c);}else{$walk($c);while($c->firstChild)$node->insertBefore($c->firstChild,$c);$node->removeChild($c);} continue; }
    foreach(iterator_to_array($c->attributes) as $attr) { $name=$attr->name; $val=$attr->value; $c->removeAttribute($name); if(($tag==='a'&&$name==='href')||($tag==='img'&&$name==='src')) { $u=safe_url($val); if($u) $c->setAttribute($name,$u); } elseif($name==='style'){ $styles=[];foreach(explode(';',$val) as $rule){$parts=explode(':',$rule,2);if(count($parts)!==2)continue;$k=strtolower(trim($parts[0]));$v=trim($parts[1]);if((in_array($k,['color','background-color'],true)&&preg_match('/^(#[a-f0-9]{3,8}|[a-z]{3,20})$/i',$v))||($k==='font-size'&&preg_match('/^(?:1[0-9]|2[0-9]|3[0-2])px$/D',$v))||($k==='text-align'&&in_array($v,['left','center','right'],true)))$styles[]=$k.':'.$v;}if($styles)$c->setAttribute('style',implode(';',$styles)); } elseif($name==='color' && preg_match('/^(#[a-f0-9]{3,8}|[a-z]{3,20})$/i',$val)) $c->setAttribute($name,$val); elseif($name==='size' && preg_match('/^[1-7]$/D',$val)) $c->setAttribute($name,$val); elseif(in_array($name,['alt','title'],true)) $c->setAttribute($name,$val); }
    $walk($c);
   } elseif(!($c instanceof DOMText)) $node->removeChild($c);
  }
 };
 $root=$doc->getElementById('zo-root'); if(!$root) return ''; $walk($root); $out=''; foreach($root->childNodes as $c) $out.=$doc->saveHTML($c); return $out;
}
function content_html($message,$htmlon=false) {
 // Restricted blocks are never rendered by this guest-only API.
 $message=preg_replace('~\[(hide|password)(?:=[^\]]*)?\].*?\[/\1\]~is','[受限内容，请在论坛登录后查看]',$message);
 if(preg_match('~\[(?:hide|password)\b~i',$message)) return '<p>此楼层包含受限内容，请在论坛查看。</p>';
 $h=$htmlon ? $message : nl2br(htmlspecialchars($message,ENT_QUOTES,'UTF-8'));
 foreach(['b'=>'strong','i'=>'em','u'=>'u','quote'=>'blockquote','code'=>'pre','list'=>'ul'] as $bb=>$tag) $h=preg_replace('~\['.$bb.'\](.*?)\[/'.$bb.'\]~is','<'.$tag.'>$1</'.$tag.'>',$h);
 $h=preg_replace('~\[url=(.*?)\](.*?)\[/url\]~is','<a href="$1">$2</a>',$h);
 $h=preg_replace('~\[url\](.*?)\[/url\]~is','<a href="$1">$1</a>',$h);
 $h=preg_replace('~\[img(?:=[^\]]*)?\](.*?)\[/img\]~is','<img src="$1">',$h);
 $h=preg_replace('~\[(?:color|size|font|align|backcolor)(?:=[^\]]*)?\]|\[/(?:color|size|font|align|backcolor)\]~i','',$h);
 $h=preg_replace('~\[list=1\](.*?)\[/list\]~is','<ol>$1</ol>',$h);
 $h=str_replace('[*]','<li>',$h);
 $h=preg_replace('~\[attach(?:img)?\]\d+\[/attach(?:img)?\]~i','[附件请在论坛查看]',$h);
 return clean_html($h);
}

require_once __DIR__.'/account.php';

require_once __DIR__.'/visibility.php';
function public_forums(){return visible_forums();}
