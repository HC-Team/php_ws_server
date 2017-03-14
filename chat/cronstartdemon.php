<?php
$output='';
exec("/usr/local/bin/php  /home/adm/php_ws_server/chat/server.php start > /dev/null 2>/dev/null &");
die();