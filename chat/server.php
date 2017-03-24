<?php
// prevent the server from timing out
set_time_limit(0);

// ищем демона в списке процесов  ps aux | grep -v grep | grep demon.php
// запуск демона >>   php /home/adm/hc/chat/server.php start или останов stop или restart
$cmd = end($argv);
$pachw=__DIR__.'/';
// останов демона

$demonstatus=isDaemonActive($pachw.'websocketserver.pid');
if (($cmd == 'stop' or $cmd == 'restart') and $demonstatus) 
{
    $pid = file_get_contents($pachw.'websocketserver.pid') or die("pid file not found!\n");
    posix_kill($pid, SIGTERM);
   if($cmd !== 'restart' ){exit;}
}
if ($cmd !== 'start' and  $cmd !== 'restart') die('use (start | stop)!');
if ($cmd == 'start' and $demonstatus) die("demon is  run\n");
$parent = null;
$childs = array();
for ($i=0; $i<5; $i++) {
    $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP); //создаём связанные сокеты

    $pid = pcntl_fork(); //создаём форк

    if ($pid == -1) {
        die("error: pcntl_fork");
    } elseif ($pid) { //родительский процесс
        fclose($pair[0]); //закрываем один из сокетов в родителе
        $childs[] = $pair[1]; //второй будем использовать для связи с потомком
        file_put_contents($pachw.'websocketserver.pid', $pid);
        exit(0);
        
    } else { //дочерний процесс
        fclose($pair[1]); //закрываем второй из сокетов в потомке
        $parent = $pair[0]; //первый будем использовать для связи с родителем
        break; //выходим из цикла, чтобы дочерние процессы создавались только из родителя
    }
}

// child
// отключаешься от терминала
if (posix_setsid() === -1) die('Could not detach from terminal');

// регистрируешь callback сигнала остановки демона
declare(ticks = 1);
pcntl_signal(SIGTERM, 'dieMyDaemon');
consolemsg("daemon run\n");
    
// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';
$pach=dirname(__DIR__).'/';
$db = new SQLite3(__DIR__.'/ws_chanels.db');
$db->exec('CREATE TABLE IF NOT EXISTS chanel (id INT, name CHAR(250),type INT,key CHAR(64),date_cr DATETIME,dat_end DATETIME);');
//$db->exec('CREATE TABLE IF NOT EXISTS chanel_follow (id_chanel INT, id_follow INT,date_cr DATETIME);');
//$db->exec("delete from chanel ");


// start the server
$Server = new PHPWebSocket ();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
channel_list_fill();
$Server->wsStartServer('0.0.0.0', 8080);

function channel_list_fill($clientID='')
{
    global $Server,$pid,$db;
    $Server->chanel_list=array();
    $results = $db->query('SELECT * FROM chanel');
    $re=0;
    while ($row = $results->fetchArray()) {$re++;
        $follow='no';if($clientID!='' and isset($Server->chanel_follow_list[$row['id']][$clientID])){$follow='yes';}
        $Server->chanel_list[$row['id']]=array('name'=>$row['name'],'type'=>$row['type'],'dat_end'=>$row['dat_end'],'follow'=>$follow);
    }
    if($re==0)
    {
        $db->exec("INSERT INTO chanel VALUES('1000001','channel 1','0','',datetime('now'),datetime('now','+7 days'));");
        $db->exec("INSERT INTO chanel VALUES('1000002','channel 2','0','',datetime('now'),datetime('now','+7 days'));");
        $db->exec("INSERT INTO chanel VALUES('1000003','channel 3','0','',datetime('now'),datetime('now','+7 days'));");
        $db->exec("INSERT INTO chanel VALUES('1000004','create','0','',datetime('now'),datetime('now','+7 days'));");
        $db->exec("INSERT INTO chanel VALUES('1000005','login','0','',datetime('now'),datetime('now','+7 days'));");
        $db->exec("INSERT INTO chanel VALUES('1000006','open','0','',datetime('now'),datetime('now','+7 days'));");
        $db->exec("INSERT INTO chanel VALUES('1000007','click','0','',datetime('now'),datetime('now','+7 days'));");
        channel_list_fill($clientID);
    }
}
// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) 
{
	global $Server,$pid,$db;
	// check if message length is 0
	if ($messageLength == 0){
		$Server->wsClose($clientID);
		return;
	}
	if ( sizeof($Server->wsClients) == 1 )//The speaker is the only person in the room. Don't let them feel lonely.
	{
		//$Server->wsSend($clientID, "There isn't anyone else in the room, but I'll still listen to you. --Your Trusty Server");
	}
    if($binary){
        //$Server->wsSend($clientID,'video',$binary);
    }
   if($message!='' and $message!='undefined')
   {
	    $start = microtime(true);
	    $ip = long2ip($Server->wsClients[$clientID][6]);
	    try{
	    $msg=json_decode($message);
	    }
	    catch (Exception $e)
	    {
	        $echo['type']='';
	        $echo['status']='error';
	        $echo['code']='001';
	        $echo['dat']=date('Y.m.d H:i:s',time());
	        $Server->wsSend($clientID,json_encode($echo));
	         
	    }
	    $message='';if(isset($msg->data)){$message=strip_tags($msg->data);}
     
	if($msg->type=='create_chanel')
	{
	    $msg->data = preg_replace ("/[^a-zA-Z0-9_-\s]/","",$msg->data);
	    if($msg->type!==0 and $msg->type!==1){$msg->type=0;} // 0 public  1 privat  в планах сделать доступ только по RSA подписи
	    
	    $chanel_id=uniqid();
	    $echo['type']='create_chanel';
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['dat_end']=date('Y.m.d H:i:s',time()+360*24*7);
	    $echo['name']=$msg->data;
	    $echo['type']=$msg->type;
	    //$Server->chanel_list[$chanel_id]=$msg->data;
	    $key='';
	    if($msg->data!='')
	    {
	    $db->exec("INSERT INTO chanel VALUES('".$chanel_id."','".$msg->data."','".$msg->type."','$key',datetime('now'),datetime('now','+7 days'));");
	    }
	    else
	    {
	        $echo['status']='error';
	    }
	    channel_list_fill($clientID);	    
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='prolongate_chanel')
	{
	    $db->exec("update chanel set dat_end=datetime('now','+7 days') where id='".$msg->data."';");
	    $echo['type']='prolongate_chanel';
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    channel_list_fill($clientID);
	    $echo['data']=$Server->chanel_list;
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='remove_chanel')
	{
	    $db->exec("delete from chanel  where id='".$msg->data."';");
	    $echo['type']='remove_chanel';
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    channel_list_fill($clientID);
	    $echo['data']=$Server->chanel_list;
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='chanel_list')
	{
	    $echo['type']='chanel_list';
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    channel_list_fill($clientID);
	    $echo['data']=$Server->chanel_list;
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='follow_channel')
	{
	    if(isset($Server->chanel_list[$msg->id]))
	    {
	    $echo['type']='channel_'.$msg->id;
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['msg']='';
	    if(!isset($Server->chanel_follow_list[$msg->id][$clientID])) {  
	       $Server->chanel_follow_list[$msg->id][$clientID]=$clientID;
	       }
	    else{
	           $echo['msg']='double sign';
	       }
	    $echo['data']=$Server->chanel_follow_list[$msg->id];
	    }
	    else
	    {
	        $echo['type']='channel_'.$msg->id;
	        $echo['status']='error';
	        $echo['dat']=date('Y.m.d H:i:s',time());
	        $echo['msg']='access denied';
	    }
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='unfollow_channel')
	{
	    $echo['type']='channel_'.$msg->id;
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['msg']='';
	        unset($Server->chanel_follow_list[$msg->id][$clientID]);
	    $echo['data']=$Server->chanel_follow_list[$msg->id];
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='send_message_to')
	{
	    $echo['type']='message_to_'.$msg->id;
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['channel']['name']=$Server->chanel_list[$msg->id]['name'];
	    $echo['channel']['id']=$msg->id;
	    $echo['msg']=$msg->msg;
	    $count=0;
	    if(isset($Server->chanel_follow_list[$msg->id]))
	    {
	       foreach ($Server->chanel_follow_list[$msg->id] as $k=>$v){
	        $Server->wsSend($v,json_encode($echo));
	        $count++;
	       }
	    }
	    $echo=array();
	    $echo['status']='ok';
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['channel']['name']=$Server->chanel_list[$msg->id]['name'];
	    $echo['channel']['id']=$msg->id;
	    $echo['count']=$count;
	    $Server->wsSend($clientID,json_encode($echo));
	}
	elseif($msg->type=='ping')
	{
	    $echo['type']='pong';
	    $echo['data']=$message;
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['server']=print_r($_SERVER,true);
	    $Server->wsSend($clientID,json_encode($echo));
	}
	else
	{
	    $echo['type']='msg';
	    //$echo['data']=$message;
	    $echo['dat']=date('Y.m.d H:i:s',time());
	    $echo['server']=print_r($_SERVER,true);
	    $Server->wsSend($clientID,json_encode($echo));
	}
  }
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	$rr=memory_get_usage(true)/1024/1024;
//	echo "\n+ ".$clientID." = ".$rr." Mb\n";
	//echo "\n\n".$_SERVER['REMOTEHOST']."\n";
	$Server->log( "\n $ip ($clientID) has connected.\n" );
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) 
{
    global $Server,$pid;
    $Server->log( "\n ".$clientID." has left.\n" );
    foreach ($Server->chanel_follow_list as $k=>$v){
        unset($Server->chanel_follow_list[$k][$clientID]);
    }
}


function isDaemonActive($pidfile) {
    if( is_file($pidfile) ) {
        $pid = file_get_contents($pidfile);
        //получаем статус процесса
        $status = getDaemonStatus($pid);
        if($status['run']) {
            //демон уже запущен
            consolemsg("daemon already running info=".$status['info']);
            return true;
        } else {
            //pid-файл есть, но процесса нет
            consolemsg("there is no process with PID = ".$pid.", last termination was abnormal...");
            consolemsg("try to unlink PID file...");
            if(!unlink($pidfile)) {
                consolemsg("ERROR");
                //не могу уничтожить pid-файл. ошибка
                exit(-1);
            }
            consolemsg("OK");
        }
    }
    return false;
}

function getDaemonStatus($pid) {
    $result = array ('run'=>false);
    $output = null;
    exec("ps -aux -p ".$pid, $output);
    if(count($output)>1){//Если в результате выполнения больше одной строки то процесс есть! т.к. первая строка это заголовок, а вторая уже процесс
        $result['run'] = true;
        $result['info'] = $output[1];//строка с информацией о процессе
    }
    return $result;
}


function consolemsg($msg)
{
    echo $msg."\n";
}

function dieMyDaemon() {
    global $pachw;
    echo "server is dead\n";
    unlink($pachw.'websocketserver.pid');
    exit;
}

?>