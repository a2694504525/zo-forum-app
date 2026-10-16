<?php
require dirname(__DIR__).'/bootstrap.php';method('POST');$u=current_user(true);$header=$_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'';exec_sql('DELETE FROM '.table('app_token').' WHERE token_hash=?',[hash('sha256',substr($header,7))]);respond(['logged_out'=>true]);
