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
		var OPTIMAL_GAME_PAGE_WIDTH = 2400;
		var OPTIMAL_GAME_PAGE_HEIGHT = 1156;
		var GAME_ARENA_WIDTH = 800;
		var GAME_ARENA_HEIGHT = 800;
		var onInitialLoad = true;
		var gamePageWidth = getWindowWidth();
		var gamePageHeight = getWindowHeight();
		var playerDiameter = 40;
  		var bulletDiameter = 10;
		var selfId = null;

		//Connection Information
		var SERVER_NAME = "{{ $_SERVER['SERVER_NAME'] }}";
		var SERVER_PORT = 8001;
		var socket = io.connect(SERVER_NAME + ':' + SERVER_PORT);

		//Chat Data
		/*var chatText = document.getElementById('chat-text');
		var chatInput = document.getElementById('chat-input');
		var chatForm = document.getElementById('chat-form');*/

		//Canvas Setup
		var ctx = document.getElementById("ctx").getContext("2d");
		ctx.canvas.width  = gamePageWidth;
  		ctx.canvas.height = gamePageHeight;
  		ctx.font = '14px Arial';

  		var xDrawPosition = getDrawPosition('x');
  		var yDrawPosition = getDrawPosition('y');

  		$('body').on('contextmenu', '#ctx', function(e){ return false; }); //Disables right-click
  		$(window).resize(function(){ resizeCanvas(); });

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
				var x = self.x - Player.list[selfId].x + getDrawPosition('x');
				var y = self.y - Player.list[selfId].y + getDrawPosition('y');
				
				//HP Bar
				var hpWidth = self.hp / self.hpMax * 80;
				ctx.fillStyle = 'red';
				ctx.fillRect(x - (hpWidth / 2), y+45, hpWidth, 5);

				//Player Ball
				drawCircle(x, y, playerDiameter);
				ctx.fillStyle = (self.id == selfId) ? '#0099ff' : '#ff1a1a';
				ctx.fill();

				ctx.lineWidth = 5;
				ctx.strokeStyle = (self.id == selfId) ? '#005c99' : '#990000';
      			ctx.stroke();
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
			self.angle = initPack.angle;

			self.draw = function(){
				var originX = self.x - Player.list[selfId].x + getDrawPosition('x');
				var originY = self.y - Player.list[selfId].y + getDrawPosition('y');

				x = originX + playerDiameter * Math.cos(self.angle * Math.PI / 180);
				y = originY + playerDiameter * Math.sin(self.angle * Math.PI / 180);

				drawCircle(x, y, bulletDiameter);
				ctx.fillStyle = '#484848';
				ctx.fill();
				ctx.lineWidth = 4;
				ctx.strokeStyle = '#000000';
      			ctx.stroke();
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
					if(pack.mouseAngle !== undefined){
						p.mouseAngle = pack.mouseAngle;
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
					if(pack.angle !== undefined){
						b.angle = pack.angle;
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

			ctx.clearRect(0,0,10000,10000);
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

		var drawMap = function(){
			var originX = xDrawPosition - Player.list[selfId].x;
			var originY = yDrawPosition - Player.list[selfId].y;
			var limitXPosition = GAME_ARENA_WIDTH - Player.list[selfId].x + xDrawPosition;
			var limitYPosition = GAME_ARENA_HEIGHT - Player.list[selfId].y + yDrawPosition;

			ctx.lineWidth = 1;
			ctx.strokeStyle = 'black';

			drawLine(originX, originY, limitXPosition, originY); //Top Horizontal
			drawLine(originX, originY, originX, limitYPosition); //Left Vertical
			drawLine(limitXPosition, originY, limitXPosition, limitYPosition); //Right Verticel
			drawLine(originX, limitYPosition, limitXPosition, limitYPosition); //Bottom Horizontal
		}

		var drawScore = function(){
			ctx.fillText("Score: " + Player.list[selfId].score, 0, 15);
		}

		var drawDebugVariables = function(){
			ctx.fillStyle = 'red';
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

		function getWindowWidth(){
			return window.innerWidth;
		}

		function getWindowHeight(){
			return window.innerHeight - 51; //-51px for navbar -1px for navbar border
		}

		function getDrawPosition(axis){
			if(axis == 'x'){
				return getWindowWidth()/2;
			}
			else if(axis == 'y'){
				return getWindowHeight()/2;
			}
		}

		function resizeCanvas(){
			console.log("Resize Event Detected!");

			var newGamePageWidth = getWindowWidth();
			var newGamePageHeight = getWindowHeight();

			if(onInitialLoad){
				console.log("Inital Load");
				var widthScaleFactor = newGamePageWidth/OPTIMAL_GAME_PAGE_WIDTH; //New Width / Optimal Width
				var heightScaleFactor = newGamePageHeight/OPTIMAL_GAME_PAGE_HEIGHT; //New Height / Optimal Height
			}
			else{
				var widthScaleFactor = newGamePageWidth/gamePageWidth; //New Width / Old Width
				var heightScaleFactor = newGamePageHeight/gamePageHeight; //New Height / Old Height
			}

			var aspectRatio = gamePageWidth/gamePageHeight;

			console.log("Old Game Page Width: "+gamePageWidth + "  Old Game Page Height: " + gamePageHeight);	

			if(ctx.canvas.width < newGamePageWidth){
				ctx.canvas.width = newGamePageWidth;
			}
			else if(ctx.canvas.height < newGamePageHeight){
				ctx.canvas.height = newGamePageHeight;
			}

  			ctx.scale(widthScaleFactor, heightScaleFactor);
			console.log("Scaled Width: "+widthScaleFactor + "Height: " + heightScaleFactor);

			gamePageWidth = newGamePageWidth;
			gamePageHeight = newGamePageHeight;
			console.log("New Game Page Width: "+gamePageWidth + "  New Game Page Height: " + gamePageHeight);	

			xDrawPosition = getDrawPosition('x');
  			yDrawPosition = getDrawPosition('y');
  			console.log("Window Width: " + getWindowWidth() + "  Window Height: " + getWindowHeight());
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
			if(event.keyCode == 67) //c
				ctx.scale(0.5, 0.5);
			if(event.keyCode == 86) //v
				ctx.scale(2, 2);
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
			
			var mouseAngle = Math.atan2(getDrawPosition('y') - event.y, getDrawPosition('x') - event.x) * 180 / Math.PI + 180;

			//console.log("Angle: " + mouseAngle);

			socket.emit('keyPress', {inputId:'mouseAngle', state:mouseAngle});
		}
		window.onload = function(){
			resizeCanvas();
			onInitialLoad = false;
		};
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