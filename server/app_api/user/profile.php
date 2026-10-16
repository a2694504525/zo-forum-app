<?php
require dirname(__DIR__).'/bootstrap.php';method('GET');$uid=number('uid');$d=user_public($uid);$d['threads']=thread_list(' AND t.authorid=?',[$uid]);respond($d);
