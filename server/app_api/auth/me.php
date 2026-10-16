<?php
require dirname(__DIR__).'/bootstrap.php';method('GET');$u=current_user(true);respond(user_public($u['uid'],true));
