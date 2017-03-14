<?php
$output='';
exec("/usr/local/bin/php  /home/adm/php_ws_server/chat/server.php restart > /dev/null 2>/dev/null &");
die();