<?php
require dirname(__DIR__).'/bootstrap.php';method('GET');
$out=[]; foreach(public_forums() as $f) { unset($f['viewperm'],$f['password'],$f['formulaperm'],$f['price']); $f['name']=html_entity_decode(strip_tags($f['name']),ENT_QUOTES,'UTF-8'); $out[]=$f; } respond($out);
