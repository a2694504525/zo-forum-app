<?php
require dirname(__DIR__).'/bootstrap.php';method('GET');rate('challenge',60,600);global $pdo;$pdo->exec('CREATE TABLE IF NOT EXISTS '.table('app_challenge').' (id CHAR(32) PRIMARY KEY,answer_hash CHAR(64) NOT NULL,expires INT UNSIGNED NOT NULL) ENGINE=InnoDB');exec_sql('DELETE FROM '.table('app_challenge').' WHERE expires<?',[time()]);
$id=bin2hex(random_bytes(16));$a=random_int(1,9);$b=random_int(1,9);exec_sql('INSERT INTO '.table('app_challenge').' VALUES (?,?,?)',[$id,hash('sha256',$id.'|'.($a+$b)),time()+180]);respond(['challenge_id'=>$id,'question'=>$a.' + '.$b.' = ?']);
