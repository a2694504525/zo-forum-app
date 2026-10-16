<?php
// Resolve original attachment tables and CDN addresses after forum/post access checks.
function app_qiniu_auth() {
 global $_G;static $auth=null;if($auth)return $auth;
 $c=$_G['cache']['plugin']['addon_storage_qiniuoss']??[];
 if(empty($c['access_key_id'])||empty($c['access_key_secret']))return null;
 $root=dirname(__DIR__).'/source/plugin/addon_storage_qiniuoss/source/Qiniu/';
 require_once $root.'AutoLoad.php';return $auth=new Qiniu\Auth($c['access_key_id'],$c['access_key_secret']);
}
function app_attachment_url($a) {
 global $_G;$file=$a['attachment']??'';if(!$file||strpos($file,'..')!==false||preg_match('/[\x00-\x20\\\\]/',$file))return '';
 if(!empty($a['remote'])){
  $base=$_G['setting']['ftp'][!empty($a['isimage'])?'attachurl':'attachurl2']??'';
  if(!$base)$base=$_G['setting']['ftp']['attachurl']??'';
 }else $base=$_G['setting']['attachurl']??'data/attachment/';
 $url=safe_url(rtrim($base,'/').'/forum/'.$file);if(!$url)return '';
 if(empty($a['isimage'])){$url.='?attname='.rawurlencode($a['filename']??basename($file));if(!empty($a['remote'])&&($auth=app_qiniu_auth()))$url=$auth->privateDownloadUrl($url,600);}
 return $url;
}
function app_post_attachments($p,$t) {
 global $_G;$u=current_user();$out=[];$indices=rows('SELECT aid,tableid FROM '.table('forum_attachment').' WHERE tid=? AND pid=?',[$t['tid'],$p['pid']]);
 foreach($indices as $index){$id=(int)$index['tableid'];if($id<0||$id>9)continue;$a=one('SELECT * FROM '.table('forum_attachment_'.$id).' WHERE aid=? AND tid=? AND pid=?',[$index['aid'],$t['tid'],$p['pid']]);if(!$a)continue;
  $owner=$u&&(int)$u['uid']===(int)$a['uid'];$moderator=!empty($_G['forum']['ismoderator']);
  if(!$owner&&!$moderator){$permission=!empty($a['isimage'])?'allowgetimage':'allowgetattach';if(empty($_G['group'][$permission])||(!empty($_G['forum']['getattachperm'])&&!forumperm($_G['forum']['getattachperm']))||(int)($a['readperm']??0)>group_readaccess())continue;
   if((int)($a['price']??0)>0&&(!$u||!one('SELECT uid FROM '.table('forum_attachmentlog').' WHERE aid=? AND uid=?',[$a['aid'],$u['uid']])))continue;
  }
  $url=app_attachment_url($a);if($url)$out[(int)$a['aid']]=['aid'=>(int)$a['aid'],'url'=>$url,'filename'=>$a['filename'],'remote'=>(int)$a['remote'],'isimage'=>(bool)$a['isimage'],'size'=>(int)$a['filesize']];
 }return $out;
}
function app_clean_html_v101($html) {
 $doc=new DOMDocument();libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="UTF-8"><div id="zo-safe">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();$root=$doc->getElementById('zo-safe');if(!$root)return '';
 $tags=['p','br','b','strong','em','i','u','s','blockquote','pre','code','ul','ol','li','table','thead','tbody','tr','td','th','a','img','span','div','h1','h2','h3','hr','font','video','audio','source'];
 $walk=function($n)use(&$walk,$tags){foreach(iterator_to_array($n->childNodes) as $c){if($c instanceof DOMElement){$tag=strtolower($c->tagName);if(!in_array($tag,$tags,true)){if(in_array($tag,['script','iframe','object','embed','style','form','input','svg','math','textarea'],true))$n->removeChild($c);else{$walk($c);while($c->firstChild)$n->insertBefore($c->firstChild,$c);$n->removeChild($c);}continue;}
   foreach(iterator_to_array($c->attributes) as $attr){$key=strtolower($attr->name);$v=$attr->value;$c->removeAttribute($key);
    if(($key==='href'&&$tag==='a')||($key==='src'&&in_array($tag,['img','video','audio','source'],true))||($key==='poster'&&$tag==='video')){if($url=safe_url($v))$c->setAttribute($key,$url);}
    elseif(in_array($key,['alt','title'],true))$c->setAttribute($key,$v);
    elseif($key==='color'&&preg_match('/^(#[a-f0-9]{3,8}|[a-z]{3,20})$/i',$v))$c->setAttribute('color',$v);
    elseif($key==='size'&&preg_match('/^[1-7]$/D',$v))$c->setAttribute('size',$v);
    elseif(in_array($key,['colspan','rowspan'],true)&&ctype_digit($v)&&(int)$v<=20)$c->setAttribute($key,$v);
    elseif($key==='style'){ $styles=[];foreach(explode(';',$v) as $rule){$kv=explode(':',$rule,2);if(count($kv)!==2)continue;[$k,$value]=array_map('trim',$kv);$k=strtolower($k);if((in_array($k,['color','background-color'],true)&&preg_match('/^(#[a-f0-9]{3,8}|[a-z]{3,20}|rgba?\([\d.,%\s]+\))$/i',$value))||($k==='font-size'&&preg_match('/^(?:[1-9]|[12]\d|3[0-2])(?:px|pt)$/D',$value))||($k==='text-align'&&in_array($value,['left','center','right'],true))||($k==='font-weight'&&in_array($value,['bold','normal','700'],true))||($k==='font-style'&&$value==='italic')||($k==='text-decoration'&&in_array($value,['underline','line-through'],true)))$styles[]=$k.':'.$value;}if($styles)$c->setAttribute('style',implode(';',$styles));}
   }if(in_array($tag,['video','audio'],true)){$c->setAttribute('controls','controls');$c->setAttribute('preload','none');}if($tag==='img')$c->setAttribute('style','max-width:100%;height:auto');$walk($c);
  }elseif(!($c instanceof DOMText))$n->removeChild($c);}};$walk($root);$out='';foreach($root->childNodes as $c)$out.=$doc->saveHTML($c);return $out;
}
function app_render_post_v101($p,$t) {
 global $_G;require_once __DIR__.'/media_v101.php';$attachments=app_post_attachments($p,$t);$message=$p['message'];
 $message=preg_replace_callback('~\[attach(?:img)?\](\d+)\[/attach(?:img)?\]~i',function($m)use($attachments){$a=$attachments[(int)$m[1]]??null;if(!$a)return '[附件需满足阅读或下载权限]';return $a['isimage']?'[img]'.$a['url'].'[/img]':'[url='.$a['url'].']'.$a['filename'].'[/url]';},$message);
 // Native parser retains Discuz hide/password access. Media tokens are replaced AFTER it resolves hidden content.
 $media=[];$message=preg_replace_callback('~\[code\](.*?)\[/code\]~is',function($m)use(&$media){$key='ZO_CODE_'.bin2hex(random_bytes(12));$media[$key]='<pre><code>'.htmlspecialchars($m[1],ENT_QUOTES,'UTF-8').'</code></pre>';return $key;},$message);
 $message=preg_replace_callback('~\[(media|audio)(?:=[^\]]*)?\]([^\[]+)\[/\1\]~i',function($m)use(&$media){$url=safe_url(trim($m[2]));if(!$url||!preg_match('/\.(mp4|webm|mp3|m4a|ogg|wav)(?:\?|$)/i',$url))return $url?'[url='.$url.']播放媒体[/url]':'';$tag=preg_match('/\.(mp4|webm)(?:\?|$)/i',$url)?'video':'audio';$key='ZO_MEDIA_'.count($media).'_'.bin2hex(random_bytes(8));$media[$key]='<'.$tag.' src="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'" controls></'.$tag.'>';return $key;},$message);
 $html=discuzcode($message,(bool)$p['smileyoff'],(bool)$p['bbcodeoff'],(int)$p['htmlon'],1,1,1,(int)$_G['forum']['allowhtml'],0,0,(int)$p['authorid'],0,(int)$p['pid'],0,(int)$p['dateline'],(int)$p['first']);$html=strtr($html,$media);
 // Some templates hide original image src in file/zoomfile attributes; prefer the verified image URL.
 $html=preg_replace_callback('~<img\b[^>]*>~i',function($m)use($attachments){$s=$m[0];foreach(['zoomfile','file'] as $attr)if(preg_match('~\b'.$attr.'=["\']([^"\']+)["\']~i',$s,$v)&&safe_url($v[1]))return '<img src="'.htmlspecialchars(safe_url($v[1]),ENT_QUOTES,'UTF-8').'">';return $s;},$html);
 $html=app_clean_html_v101($html);$visible=[];foreach($attachments as $a)if(strpos(html_entity_decode($html,ENT_QUOTES,'UTF-8'),$a['url'])!==false)$visible[]=$a;
 $GLOBALS['app_render_attachments']=$visible;return $html;
}
function app_cover_v101($t) {
 $u=current_user();native_core((int)($u['uid']??0));global $_G;require_once libfile('function/forum');loadforum((int)$t['fid'],(int)$t['tid']);require_once __DIR__.'/media_v101.php';app_qiniu_auth();
 if(empty($_G['group']['allowgetimage']))return '';
 $thread=one('SELECT posttableid FROM '.table('forum_thread').' WHERE tid=?',[$t['tid']]);$pt=table('forum_post'.((int)$thread['posttableid']?'_'.(int)$thread['posttableid']:''));$post=one('SELECT * FROM '.$pt.' WHERE tid=? AND first=1 AND invisible=0',[$t['tid']]);if(!$post||preg_match('~\[(hide|password)\b~i',$post['message']))return '';
 $attachments=app_post_attachments($post,$t);foreach($attachments as $a)if($a['isimage'])return $a['url'];return '';
}
