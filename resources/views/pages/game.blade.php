@extends('layouts.app')

@section('content')
	<div class="col-md-6 col-md-offset-3">
		<canvas id="ctx" width="1000" height="650" style="border:1px solid #000"></canvas>

		<div id="chat-text" style="width:1000px; height:100px; overflow-y:scroll">
		</div>

		<form id="chat-form">
			<input id="chat-input" type="text" style="width:1000px"></input>
		</form>

		<script src="//cdn.socket.io/socket.io-1.0.0.js"></script>
		<script>
			var chatText = document.getElementById('chat-text');
			var chatInput = document.getElementById('chat-input');
			var chatForm = document.getElementById('chat-form');
			var ctx = document.getElementById("ctx").getContext("2d");
			ctx.font = '30px Arial';

			var socket = io.connect('http://localhost:8001');

			socket.on('newPositions', function(data){
				ctx.clearRect(0,0,1000,650);
				for(var i = 0; i < data.player.length; i++)
					ctx.fillText(data.player[i].number, data.player[i].x, data.player[i].y);
				for(var i = 0; i < data.bullet.length; i++)
					ctx.fillRect(data.bullet[i].x-5, data.bullet[i].y-5, 10, 10);
				ctx.fillText('P', data.x, data.y);
			});

			socket.on('addToChat', function(data){
				chatText.innerHTML += '<div>' + data + '</div>';
			});
			socket.on('evalAnswer', function(data){
				console.log(data);
			});

			chatForm.onsubmit = function(e){
				e.preventDefault();
				if(chatInput.value[0] === '/')
					socket.emit('evalServer', chatInput.values.slice(1));
				else
					socket.emit('sendMsgToServer', chatInput.value);
				chatInput.value = '';
			}

			document.onkeydown = function(event){
				if(event.keyCode == 68) //d
					socket.emit('keyPress',{inputId: 'right', state: true});
				if(event.keyCode == 83) //s
					socket.emit('keyPress',{inputId: 'down', state: true});
				if(event.keyCode == 65) //a
					socket.emit('keyPress',{inputId: 'left', state: true});
				if(event.keyCode == 87) //w
					socket.emit('keyPress',{inputId: 'up', state: true});
			}
			document.onkeyup = function(event){
				if(event.keyCode == 68) //d
					socket.emit('keyPress',{inputId: 'right', state: false});
				if(event.keyCode == 83) //s
					socket.emit('keyPress',{inputId: 'down', state: false});
				if(event.keyCode == 65) //a
					socket.emit('keyPress',{inputId: 'left', state: false});
				if(event.keyCode == 87) //w
					socket.emit('keyPress',{inputId: 'up', state: false});
			}

			document.onmousedown = function(event){
				socket.emit('keyPress', {inputId:'attack', state:true});
			}
			document.onmouseup = function(event){
				socket.emit('keyPress', {inputId:'attack', state:false});
			}
			document.onmousemove = function(event){
				var x = -250 + event.clientX - 8;
				var y = -250 + event.clientY - 8;
				var angle = Math.atan2(y,x) / Math.PI * 180;
				socket.emit('keyPress', {inputId:'mouseAngle', state:angle});
			}
		</script>
	</div>
@stop