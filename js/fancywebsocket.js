
var Server;
var msg = new Object();
var pingtimeout;
var reconec=true;
var reconec_c=0;
var reconecttimeout;
var users=new Array();
var server_datetime;
var callback_list=new Array();

function send( text ){text=JSON.stringify(text);Server.send( 'message', text );}


		$(document).ready(function() {
			log('Connecting...');
			var url=location.hostname;
			Server = new FancyWebSocket('wss://buldakoff.com/_ws_/');
			//Let the user know we're connected
			Server.bind('open', function() {
				//pingtimeout=setTimeout(ping, 1000);	
				clearTimeout(reconecttimeout);
				reconec_c=0;
				log( "Connected." );
				$('#channel_list').click();
			});
			//OH NOES! Disconnection occurred.
			Server.bind('close', function( data ) {
				clearTimeout(pingtimeout);
				var delta=getRandomArbitary(500, 4000)+9000;
				if(reconec)reconecttimeout=setTimeout(reconect, delta);
				log( "Disconnected." );
			});
			//Log any messages sent from server
			Server.bind('message', function( payload ) {
				var data= JSON.parse(payload);
				parser(data);
			});

			Server.connect();
		});
//###############################################################################
function prolongate_chanel(data,callback) 
{
	msg['type']='prolongate_chanel';
	msg['data']=data;
	callback_list[msg['type']]=callback;
	send(msg);		
}
function remove_chanel(data,callback) 
{
	msg['type']='remove_chanel';
	msg['data']=data;
	callback_list[msg['type']]=callback;
	send(msg);		
}
function create_chanel(data,callback) 
{
	msg['type']='create_chanel';
	msg['data']=data;
	callback_list[msg['type']]=callback;
	send(msg);		
}
function chanel_list(callback) 
{
	msg['type']='chanel_list';
	callback_list[msg['type']]=callback;
	send(msg);		
}
function follow_channel(id,callback,onmessage)
{
	msg['type']='follow_channel';
	msg['id']=id;
	callback_list['channel_'+id]=callback;
	callback_list['message_to_'+id]=onmessage;
	send(msg);		
}
function unfollow_channel(id,callback)
{
	msg['type']='unfollow_channel';
	msg['id']=id;
	callback_list['channel_'+id]=callback;
	send(msg);		
}
function send_message_to(id,text,callback)
{
	msg['type']='send_message_to';
	msg['msg']=text;
	msg['id']=id;
	callback_list['message_to_'+id]=callback;
	send(msg);		
}


//###############################################################################
function parser(msg) 
{
	//console.log(msg);
	if(typeof callback_list[msg.type] === "function"){callback_list[msg.type](msg);}
	return false;
}
	
//###############################################################################
function log( msg) 
{
var i=0;
	$('#syslog .log_i').each(function()
		{
			i++;
		 if(i>22)$(this).remove();
		});
	$('#syslog').prepend('<div class="log_i">'+msg+'</div>');
	console.log(msg);
}
//###############################################################################		
function ping()
{
	msg['type']='ping';
	msg['data']='ping ';
	send(msg);		
	$('#ping').css('background','#f00');
	var delta=getRandomArbitary(50, 800)+2500;	
	pingtimeout=setTimeout(ping, delta);
	//console.log('ping');
}
//###############################################################################
function reconect(){
	reconec_c++;
	log(reconec_c);
	if(reconec_c<10){Server.connect();}
	else{reconec=false;	}
}
function conect(){
	reconec=true;
	Server.connect();
}
function disconect(){
	reconec=false;
	Server.disconnect();
}
//###############################################################################
function getCookie(name) {
	  var matches = document.cookie.match(new RegExp(
	    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	  ));
	  return matches ? decodeURIComponent(matches[1]) : undefined;
	}
function getRandomArbitary(min, max)
{
  return parseInt(Math.random() * (max - min) + min);
}


var FancyWebSocket = function(url)
{
	var callbacks = {};
	var ws_url = url;
	var conn;
	var status=false;

	this.bind = function(event_name, callback){
		callbacks[event_name] = callbacks[event_name] || [];
		callbacks[event_name].push(callback);
		return this;// chainable
	};

	this.send = function(event_name, event_data){
		this.conn.send( event_data );
		return this;
	};

	this.connect = function() {
		if ( typeof(MozWebSocket) == 'function' )
			this.conn = new MozWebSocket(url);
		else
			this.conn = new WebSocket(url);
		this.status=true;

		// dispatch to the right handlers
		this.conn.onmessage = function(evt){
			dispatch('message', evt.data);
		};

		this.conn.onclose = function(){dispatch('close',null)}
		this.conn.onopen = function(){dispatch('open',null)}
	};

	this.disconnect = function() {
		this.conn.close();
		this.status=false;
	};

	var dispatch = function(event_name, message){
		var chain = callbacks[event_name];
		if(typeof chain == 'undefined') return; // no callbacks for this event
		for(var i = 0; i < chain.length; i++){
			chain[i]( message )
		}
	}
};