if(window.jQuery){
    $(document).ready(function(){
    	FancyWebSocket_load_bind_();
    });
}
else{
    window.addEventListener('load', FancyWebSocket_load_bind_);
}
    
function FancyWebSocket_load_bind_(){
 HC_WS.after_connect=_after_connect;
 HC_WS.after_disconnect=_after_disconnect;
 HC_WS.run('wss://buldakoff.ru/_ws_/');
 }
function _after_connect(){
	HC_WS.follow_channel('1000004',follow_channel_,onmessage_);
	HC_WS.follow_channel('1000005',follow_channel_,onmessage_);
	HC_WS.follow_channel('1000006',follow_channel_,onmessage_);
	HC_WS.follow_channel('1000007',follow_channel_,onmessage_);
}
function _after_disconnect(){
	//console.log('after_disconnect');
}

//###############################################################################		

function onmessage_(data)
{
$('#ws_log').prepend('<div style=" padding: 4px; ">'+data.msg+'</div>');
console.log(data);
}
function follow_channel_(data)
{
console.log(data);
}

//###############################################################################




//###############################################################################
//#######################    WebSocket           ################################
//###############################################################################
var HC_WS = new function() 
{
  this.FancyWebSocketServer = true;
  this.reconec_c=0;
  this.reconecttimeout;

  this.msg = new Object();
  this.pingtimeout;
  this.reconec=true;
  this.users=new Array();
  this.server_datetime;
  this.callback_list=new Array();
  
  this.after_connect=false;
  this.after_disconnect=false;

  
  this.run = function(url){
		this.log('Connecting...');
		this.FancyWebSocketServer = new FancyWebSocket(url);
		//Let the user know we're connected
		this.FancyWebSocketServer.bind('open', function() {
			clearTimeout(HC_WS.reconecttimeout);HC_WS.reconec_c=0;
			HC_WS.log( "Connected" );
			if(typeof HC_WS.after_connect=== "function"){HC_WS.after_connect();}
			$('#channel_list').click();
		});
		//OH NOES! Disconnection occurred.
		this.FancyWebSocketServer.bind('close', function( data ) {
			clearTimeout(HC_WS.pingtimeout);
			delta=HC_WS.getRandomArbitary(500, 4000)+900;
			if(HC_WS.reconec)HC_WS.reconecttimeout=setTimeout(HC_WS.reconect, delta);
			if(typeof HC_WS.after_disconnect=== "function"){HC_WS.after_disconnect();}
			HC_WS.log( "Disconnected." );
		});
		//Log any messages sent from server
		this.FancyWebSocketServer.bind('message', function( payload ) {
			var data= JSON.parse(payload);
			HC_WS.parser(data);
		});

		this.FancyWebSocketServer.connect();
	  
	};
  

  this.send = function(data){
		data=JSON.stringify(data);
		this.FancyWebSocketServer.send( 'message', data );
	};
	this.reconect = function(){
		HC_WS.reconec_c++;
		//HC_WS.log(HC_WS.reconec_c);
		if(HC_WS.reconec_c<10){HC_WS.FancyWebSocketServer.connect();}
		else{HC_WS.reconec=false;	}
	}
	this.conect = function(){
		HC_WS.reconec=true;
		HC_WS.FancyWebSocketServer.connect();
	}
	this.disconect = function(){
		HC_WS.reconec=false;
		HC_WS.FancyWebSocketServer.disconnect();
	}

	this.parser = function(msg_)	
	{
		if(typeof this.callback_list[msg_.type] === "function"){this.callback_list[msg_.type](msg_);}
		return false;
	};

	//###############################################################################################
	this.prolongate_chanel=function(data,callback) 
	{
		this.msg['type']='prolongate_chanel';
		this.msg['data']=data;
		this.callback_list[this.msg['type']]=callback;
		this.send(this.msg);		
	}
	this.remove_chanel=function(data,callback) 
	{
		this.msg['type']='remove_chanel';
		this.msg['data']=data;
		this.callback_list[this.msg['type']]=callback;
		this.send(this.msg);		
	}
	this.create_chanel=function(data,callback) 
	{
		this.msg['type']='create_chanel';
		this.msg['data']=data;
		this.callback_list[this.msg['type']]=callback;
		this.send(this.msg);		
	}
	this.chanel_list=function(callback) 
	{
		this.msg['type']='chanel_list';
		this.callback_list[this.msg['type']]=callback;
		this.send(this.msg);		
	}
	this.follow_channel=function(id,callback,onmessage)
	{
		this.msg['type']='follow_channel';
		this.msg['id']=id;
		this.callback_list['channel_'+id]=callback;
		this.callback_list['message_to_'+id]=onmessage;
		this.send(this.msg);		
	}
	this.unfollow_channel=function(id,callback)
	{
		this.msg['type']='unfollow_channel';
		this.msg['id']=id;
		this.callback_list['channel_'+id]=callback;
		this.send(this.msg);		
	}
	this.send_message_to=function(id,text,callback)
	{
		this.msg['type']='send_message_to';
		this.msg['msg']=text;
		this.msg['id']=id;
		this.callback_list['message_to_'+id]=callback;
		this.send(this.msg);		
	}
	//###############################################################################################
	this.log = function(msg) 
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
	this.getRandomArbitary=function (min, max)
	{
	  return parseInt(Math.random() * (max - min) + min);
	}
	

};

//###############################################################################

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
		if(typeof(MozWebSocket)=='function' ){this.conn = new MozWebSocket(url);}
		else{this.conn = new WebSocket(url);}
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