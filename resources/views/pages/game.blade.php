@extends('layouts.app')

@section('content')
	<canvas id="ctx"></canvas>
	<!-- <div id="chat-text" style="width:1000px; height:100px; overflow-y:scroll">
	</div>

	<form id="chat-form">
		<input id="chat-input" type="text" style="width:1000px"></input>
	</form> -->
	<script src="//cdn.socket.io/socket.io-1.0.0.js"></script>
	<script>
		//Game Environment
		var DEBUG = true;
		var GAME_ARENA_WIDTH = 3000;
		var GAME_ARENA_HEIGHT = 2500;
		var gamePageWidth = window.innerWidth;
		var gamePageHeight = window.innerHeight - 51; //-50px for navbar -1px for navbar border
		var selfId = null;

		//Connection Information
		var SERVER_NAME = "{{ $_SERVER['SERVER_NAME'] }}";
		var SERVER_PORT = 8001;
		var socket = io.connect(SERVER_NAME + ':' + SERVER_PORT);

		//Chat Data
		/*var chatText = document.getElementById('chat-text');
		var chatInput = document.getElementById('chat-input');
		var chatForm = document.getElementById('chat-form');*/

		//Images
		var Img = {};
		Img.bullet = new Image();
		Img.bullet.src = '/img/bullet.png';

		//Canvas Setup
		var ctx = document.getElementById("ctx").getContext("2d");
		ctx.canvas.width  = gamePageWidth;
  		ctx.canvas.height = gamePageHeight;
  		ctx.font = '14px Arial';

  		var xDrawPosition = window.innerWidth/2;
  		var yDrawPosition = (window.innerHeight - 51)/2;

  		$('body').on('contextmenu', '#ctx', function(e){ return false; });
  		window.addEventListener('resize', resizeCanvas, false);

		var Player = function(initPack){
			var self = {};
			self.id = initPack.id;
			self.number = initPack.number;
			self.x = initPack.x;
			self.y = initPack.y;
			self.mouseAngle = initPack.mouseAngle;
			self.hp = initPack.hp;
			self.hpMax = initPack.hpMax;
			self.score = initPack.score;

			self.draw = function(){
				var x = self.x - Player.list[selfId].x + gamePageWidth/2;
				var y = self.y - Player.list[selfId].y + gamePageHeight/2;
				
				var hpWidth = window.innerWidth * 0.04 * self.hp / self.hpMax;
				var playerWidth = window.innerWidth * 0.02;
				var playerHeight = window.innerHeight * 0.02;

				ctx.fillStyle = 'red';
				ctx.fillRect(x - hpWidth/1.96, y + window.innerWidth * 0.033, hpWidth, window.innerWidth * 0.003);

				//Player Ball
				drawCircle(x, y, playerWidth - 5);
				drawCircle(x, y, playerWidth);
				ctx.fillRect(x, y, 1, 1); //Middle Point
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
				var width = Img.bullet.width/2;
				var height = Img.bullet.width/2;

				var x = self.x - Player.list[selfId].x + gamePageWidth/2;
				var y = self.y - Player.list[selfId].y + gamePageHeight/2;

				ctx.drawImage(Img.bullet, 0, 0, Img.bullet.width, Img.bullet.height, x-width/2, y-height/2, width, height);
			}

			Bullet.list[self.id] = self;
			return self;
		}
		Bullet.list = {};

		socket.on('init', function(data){
			if(data.selfId)
				selfId = data.selfId;
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
			if(!selfId)
				return;
			ctx.clearRect(0,0,gamePageWidth,gamePageHeight);

			resizeCanvas();
			drawMap();

			if(DEBUG){
				drawDebugVariables();
			}else{
				drawScore();
			}

			for(var i in Player.list){
				Player.list[i].draw();
			}
			for(var i in Bullet.list){
				Bullet.list[i].draw();
			}
		}, 40);

		var resizeCanvas = function(){
			gamePageWidth = window.innerWidth;
			gamePageHeight = window.innerHeight - 51; //-50px for navbar -1px for navbar border
			ctx.canvas.width = gamePageWidth;
			ctx.canvas.height = gamePageHeight;
		}

		var drawMap = function(){
			drawLine(xDrawPosition - Player.list[selfId].x, yDrawPosition - Player.list[selfId].y, xDrawPosition - Player.list[selfId].x, GAME_ARENA_HEIGHT + Player.list[selfId].y);
			drawLine(xDrawPosition - Player.list[selfId].x, yDrawPosition - Player.list[selfId].y, GAME_ARENA_WIDTH - Player.list[selfId].x, yDrawPosition - Player.list[selfId].y);
		}

		var drawScore = function(){
			ctx.fillText("Score: " + Player.list[selfId].score, 0, 15);
		}

		var drawDebugVariables = function(){
			var playerData = Player.list[selfId];

			ctx.fillText("Debug", 0, 15);
			ctx.fillText("Score: " + Player.list[selfId].score, 0, 30);
			ctx.fillText("ID: " + playerData.number, 0, 45);
			ctx.fillText("X: " + playerData.x, 0, 60);
			ctx.fillText("Y: " + playerData.y, 0, 75);
			ctx.fillText("Angle: " + playerData.mouseAngle, 0, 90);
			ctx.fillText("HP: " + playerData.hp, 0, 105);
			ctx.fillText("HP Max: " + playerData.hpMax, 0, 120);
		}

		var drawLine = function(originX, originY, destinationX, destinationY){
			ctx.beginPath();
			ctx.moveTo(originX, originY);
			ctx.lineTo(destinationX, destinationY);
			ctx.stroke();
		}

		var drawCircle = function(x, y, diameter){
			ctx.beginPath();
			ctx.arc(x, y, diameter, 0, 2 * Math.PI);
			ctx.stroke();
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
			/*console.log("Mouse X: " + event.x + "  Mouse Y: " + event.y);
			console.log("Playr X: " + Player.list[selfId].x + "  Playr Y: " + Player.list[selfId].y);*/
			
			var angle = Math.atan2((gamePageHeight/2) - event.y, (gamePageWidth/2) - event.x) * 180 / Math.PI + 180;

			//console.log("Angle: " + angle);

			socket.emit('keyPress', {inputId:'mouseAngle', state:angle});
		}

		//Chat (Disabled until needed again)
		/*socket.on('addToChat', function(data){
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
		}*/
	</script>
@stop