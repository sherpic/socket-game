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

			var socket = io.connect('http://localhost:8001');

			//game
			var ctx = document.getElementById("ctx").getContext("2d");
			ctx.font = '30px Arial';

			var Player = function(initPack){
				var self = {};
				self.id = initPack.id;
				self.number = initPack.number;
				self.x = initPack.x;
				self.y = initPack.y;
				self.hp = initPack.hp;
				self.hpMax = initPack.hpMax;
				self.score = initPack.score;

				self.draw = function(){
					var hpWidth = 30 * self.hp / self.hpMax;
					ctx.fillRect(self.x - hpWidth/2, self.y - 40, hpWidth, 4);
					ctx.fillText(self.number, self.x, self.y);

					ctx.fillText(self.score, self.x, self.y-60);
				}

				Player.list[self.id] = self;

				return self;
			}
			Player.list = {};

			var Bullet = function(initPack){
				var self = {};
				self.id = initPack.id;
				self.x = initPack.x;
				self.y = initPack.y;

				self.draw = function(){
					ctx.fillRect(self.x-5, self.y-5, 10, 10);
				}

				Bullet.list[self.id] = self;
				return self;
			}
			Bullet.list = {};

			socket.on('init', function(data){
				for(var i = 0; i < data.player.length; i++){
					new Player(data.player[i]);
				}
				for(var i = 0; i < data.bullet.length; i++){
					new Bullet(data.bullet[i]);
				}
			});

			socket.on('update', function(data){
				for(var i = 0; i < data.player.length; i++){
					var pack = data.player[i];
					var p = Player.list[pack.id];
					if(p){
						if(pack.x !== undefined){
							p.x = pack.x;
						}
						if(pack.y !== undefined){
							p.y = pack.y;
						}
						if(pack.hp !== undefined){
							p.hp = pack.hp;
						}
						if(pack.score !== undefined){
							p.score = pack.score;
						}
					}
				}
				for(var i = 0; i < data.bullet.length; i++){
					var pack = data.bullet[i];
					var b = Bullet.list[data.bullet[i].id];
					if(b){
						if(pack.x !== undefined){
							b.x = pack.x;
						}
						if(pack.y !== undefined){
							b.y = pack.y;
						}
					}
				}
			});

			socket.on('remove', function(data){
				for(var i = 0; i < data.player.length; i++){
					delete Player.list[data.player[i]];
				}
				for(var i = 0; i < data.bullet.length; i++){
					delete Bullet.list[data.bullet[i]];
				}
			});

			setInterval(function(){
				ctx.clearRect(0,0,1000,650);
				for(var i in Player.list){
					Player.list[i].draw();
				}
				for(var i in Bullet.list){
					Bullet.list[i].draw();
				}
			}, 40);

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