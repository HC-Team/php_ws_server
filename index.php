<script defer type="text/javascript" src="/js/vendor/jquery-3.js"></script>
<script defer type="text/javascript" src="/js/fancywebsocket.js"></script>

<h1>JavaScript client </h1>

<div id="data" style="border: 1px solid #ddd; margin: 80px; min-height: 100px;padding: 20px;"></div>
<div id="chanel_list" style="border: 1px solid #ddd; margin: 80px; padding: 20px;"></div>

<div style="border: 1px solid #ddd; margin: 80px; padding: 20px;">
    <input type="text" placeholder="msg_text" id="msg_text" style="width: 60%;"> 
    <select id="ch_lists"  style="width: 30%;">
	    <option value=""></option>
    </select>
    <a href="" onclick="var text=$(\'#msg_text\').val();var id=$(\'#ch_lists option:selected\').val(); if(text==\'\' || id==\'\'){return false;}send_message_to(id,text,onmessage_);return false;">send msg</a>
</div>

<div style="border: 1px solid #ddd; margin: 80px; padding: 20px;">
    <input type="text" placeholder="channal name" id="channal_name"> <a href="" onclick="var name=$(\'#channal_name\').val(); if(name==\'\'){return false;}create_chanel(name+\'_channel\',create_chanel_);return false;">create chanel</a>
    <a href="" id="channel_list" onclick="chanel_list(chanel_list_);return false;">chanel list</a>
</div>

<div id="syslog" style="border: 1px solid #ddd; margin: 80px; padding: 20px;"></div>

<script>
function chanel_list_(data)
{
//	console.log(data);
	$('#chanel_list').html('');
	$("#ch_lists").empty();
	$("#ch_lists").append( $('<option value=""></option>'));
	for (v in data.data)
		{
		var tt='';
		tt+=' <a href="" onclick="follow_channel('+v+',follow_channel_,onmessage_);return false;">follow</a> ';
		tt+=' <a href="" onclick="unfollow_channel('+v+',follow_channel_);return false;">unfollow</a> ';
		tt+=' <a href="" onclick="send_message_to('+v+',\'echo message\',onmessage_);return false;">ping</a> ';
		tt+=' <a href="" onclick="prolongate_chanel(\''+v+'\',chanel_list_);return false;">prolongate</a> ';
		tt+=' <a href="" onclick="if(confirm(\'delete channel ?\')){remove_chanel(\''+v+'\',chanel_list_);}return false;">remove</a> ';
		$('#chanel_list').append('<div><b>'+data.data[v]['name']+'</b> '+data.data[v]['dat_end']+'  '+data.data[v]['follow']+' '+tt+'</div>');
		$("#ch_lists").append( $('<option value="'+v+'">'+data.data[v]['name']+' (id '+v+')</option>'));
		}
	
}
function onmessage_(data)
{
	console.log(data);
	$('#data').html('<div>'+data.channel.name+' -> '+data.msg+'  ('+data.dat+')</div>');
	log( ''+data.channel.name+' -> '+data.msg+'  ('+data.dat+')' );
}

function create_chanel_(data)
{
	console.log(data);
	chanel_list(chanel_list_);
}
function follow_channel_(data)
{
	//console.log(data.data);
	$('#data').html('');
	for (v in data.data)
	{
	$('#data').append('<div>'+data.data[v]+'</div>');
	}
	chanel_list(chanel_list_);
	
}
</script>