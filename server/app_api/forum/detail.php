<?php
require dirname(__DIR__).'/bootstrap.php';method('GET');
$fid=number('fid'); $fs=public_forums(); if(!isset($fs[$fid])||$fs[$fid]['type']==='group') respond(null,404,'版块不存在或不可公开访问');
$data=thread_list(' AND t.fid=?',[$fid]); $data['forum']=['fid'=>$fid,'name'=>strip_tags($fs[$fid]['name'])]; respond($data);
