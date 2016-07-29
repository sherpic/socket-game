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
		var GAME_ARENA_WIDTH = 1000;
		var GAME_ARENA_HEIGHT = 1000;
		var gamePageWidth = getWindowWidth();
		var gamePageHeight = getWindowHeight();
		var playerDiameter = 20;
		var playerCollisionRadiusX = 55;
		var playerCollisionRadiusY = 30;
		var selfId = null;

		//Timing
		var date = new Date();

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
			self.pressingAttack = initPack.pressingAttack;
			self.hp = initPack.hp;
			self.beingHit = false;
			self.hpMax = initPack.hpMax;
			self.kills = initPack.kills;
			self.deaths = initPack.deaths;
			self.numberOfBullets = initPack.numberOfBullets;
			self.reloading = false;
			self.shootingFrame = 0;

			self.drawBody = function(){
				var x = self.x - Player.list[selfId].x + getDrawPosition('x');
				var y = self.y - Player.list[selfId].y + getDrawPosition('y');

				//HP Bar
				var hpWidth = self.hp / self.hpMax * 80;
				ctx.fillStyle = 'red';
				ctx.fillRect(x - (hpWidth / 2), y+35, hpWidth, 5);

				//Player Ball
				drawCircle(x, y, playerDiameter);
				ctx.fillStyle = (self.id == selfId) ? '#0099ff' : '#ff1a1a';
				ctx.fill();

				ctx.lineWidth = 4;
				ctx.strokeStyle = (self.id == selfId) ? '#005c99' : '#990000';
      			ctx.stroke();

      			if(DEBUG){
      				//Origin Point
      				ctx.fillStyle = 'red';
      				ctx.fillRect(x-2, y-2, 5, 5);

      				//Collision Radius
      				ctx.lineWidth = 1;
	      			ctx.strokeStyle = 'red';
      				var mouseAngleRadians = self.mouseAngle * (Math.PI / 180);
					var collisionCenterX = (Math.cos(mouseAngleRadians) * (playerCollisionRadiusX-20)) + (self.x - Player.list[selfId].x + getDrawPosition('x'));
					var collisionCenterY = (Math.sin(mouseAngleRadians) * (playerCollisionRadiusX-20)) + (self.y - Player.list[selfId].y + getDrawPosition('y'));
	      			drawEllipse(collisionCenterX, collisionCenterY, playerCollisionRadiusX, playerCollisionRadiusY, mouseAngleRadians, 0, 2 * Math.PI);
      			}
			}
			self.drawGun = function(){
				var playerModel = [[[[78.00, 1.57080],[78.03, 1.54516],[52.04, 1.53235],[52.00, 1.57080]],[[49.00, 1.57080],[48.26, 1.46700],[34.93, 1.33971],[16.28, 0.82885],[12.73, 0.78540]],[[61.03, 1.53802],[53.46, 1.43948],[35.74, 1.25790],[25.81, 0.62025],[22.85, 0.40489],[14.00, 0.00000]],[[78.00, 1.57080],[78.03, 1.59643],[52.04, 1.60924],[52.00, 1.57080]],[[49.00, 1.57080],[48.26, 1.67459],[34.93, 1.80189],[16.28, 2.31274],[12.73, 2.35619]],[[61.03, 1.60357],[53.46, 1.70211],[35.74, 1.88370],[25.81, 2.52134],[22.85, 2.73670],[14.00, 3.14159]]],[[[76.00,1.57080],[76.03, 1.54449],[50.04,1.53082],[50.00,1.57080]],[[78.00,1.57080],[78.01,1.55798],[76.01,1.55764]],[[59.54,1.43603],[61.52,1.44039]],[[49.00,1.57080],[48.26,1.46700],[34.93,1.33971],[16.28,0.82885],[12.73,0.78540]],[[61.03,1.53802],[53.46,1.43948],[35.74,1.25790],[25.81,0.62025],[22.85,0.40489],[14.00,0.00000]],[[78.00,1.57080],[78.03,1.59643],[52.04,1.60924],[52.00,1.57080]],[[78.00,1.57080],[78.01,1.58361],[76.01,1.58395]],[[49.00,1.57080],[48.26,1.67459],[34.93,1.80189],[16.28,2.31274],[12.73,2.35619]],[[61.03,1.60357],[53.46,1.70211],[35.74,1.88370],[25.81,2.52134],[22.85,2.73670],[14.00,3.14159]]],[[[74.00,1.57080],[74.03,1.54378],[48.04,1.52915],[48.00,1.57080]],[[76.00,1.57080],[76.01,1.55764],[74.01,1.55728]],[[60.88,1.32183],[62.82,1.32968]],[[47.00,1.57080],[46.27,1.46253],[32.98,1.32582],[14.87,0.73782],[11.40,0.66104]],[[59.03,1.53691],[51.48,1.43439],[33.84,1.23970],[24.70,0.55431],[22.14,0.32175],[14.00,0.00000]],[[76.00,1.57080],[76.03,1.59710],[50.04,1.61077],[50.00,1.57080]],[[76.00,1.57080],[76.01,1.58395],[74.01,1.58431]],[[47.00,1.57080],[46.27,1.67907],[32.98,1.81577],[14.87,2.40378],[11.40,2.48055]],[[59.03,1.60468],[51.48,1.70720],[33.84,1.90189],[24.70,2.58728],[22.14,2.81984],[14.00,3.14159]]],[[[76.00,1.57080],[76.03,1.54449],[50.04,1.53082],[50.00,1.57080]],[[78.00,1.57080],[78.01,1.55798],[76.01,1.55764]],[[62.63,1.22885],[64.51,1.23924]],[[49.00,1.57080],[48.26,1.46700],[34.93,1.33971],[16.28,0.82885],[12.73,0.78540]],[[61.03,1.53802],[53.46,1.43948],[35.74,1.25790],[25.81,0.62025],[22.85,0.40489],[14.00,0.00000]],[[78.00,1.57080],[78.03,1.59643],[52.04,1.60924],[52.00,1.57080]],[[78.00,1.57080],[78.01,1.58361],[76.01,1.58395]],[[49.00,1.57080],[48.26,1.67459],[34.93,1.80189],[16.28,2.31274],[12.73,2.35619]],[[61.03,1.60357],[53.46,1.70211],[35.74,1.88370],[25.81,2.52134],[22.85,2.73670],[14.00,3.14159]]]];

				var drawColor = (self.id == selfId) ? '#005c99' : '#990000';

				//Hit Animation
				if(self.beingHit){
					var diameterModifier = -1;
					self.beingHit = false;
					drawColor = '#ff1a1a';
				}
				else{
					var diameterModifier = 0;
				}

				//Recoil animation
				if(self.pressingAttack && !self.reloading){
					if(self.shootingFrame < 3){
						self.shootingFrame++;
					}
					else{
						self.shootingFrame = 0;
					}
					sketch(self.x, self.y, self.mouseAngle, playerModel[self.shootingFrame], drawColor, diameterModifier);
					
				}
				else{
					self.shootingFrame = 0;
					sketch(self.x, self.y, self.mouseAngle, playerModel[self.shootingFrame], drawColor, diameterModifier);
				}
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
			self.diameter = initPack.diameter;
			self.angle = initPack.angle;

			self.draw = function(){
				var originX = self.x - Player.list[selfId].x + getDrawPosition('x');
				var originY = self.y - Player.list[selfId].y + getDrawPosition('y');

				x = originX + (playerDiameter + 100) * Math.cos(self.angle * Math.PI / 180);
				y = originY + (playerDiameter + 100) * Math.sin(self.angle * Math.PI / 180);

				if(self.deathTimer > 0 && self.deathTimer < 6){
					//Bullet explodes
					setInterval(function(){
						self.diameter = self.diameter * 1.3;
					}, 40);
					ctx.fillStyle = 'rgba(72,72,72,' + (1 - self.deathTimer * 0.3) + ')';
					ctx.strokeStyle = 'rgba(0,0,0,' + (1 - self.deathTimer * 0.3) + ')';
				}
				else{
					ctx.fillStyle = '#484848';
					ctx.strokeStyle = '#000000';
				}

				drawCircle(x, y, self.diameter);
				ctx.fill();
				ctx.lineWidth = 4;
				
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
					if(pack.pressingAttack !== undefined){
						p.pressingAttack = pack.pressingAttack;
					}
					if(pack.hp !== undefined){
						p.hp = pack.hp;
					}
					if(pack.beingHit !== undefined){
						p.beingHit = pack.beingHit;
					}
					if(pack.kills !== undefined){
						p.kills = pack.kills;
					}
					if(pack.deaths !== undefined){
						p.deaths = pack.deaths;
					}
					if(pack.numberOfBullets !== undefined){
						p.numberOfBullets = pack.numberOfBullets;
					}
					if(pack.reloading !== undefined){
						p.reloading = pack.reloading;
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
					if(pack.deathTimer !== undefined && pack.deathTimer > 0){
						b.deathTimer = pack.deathTimer;
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
			drawMap();

			if(DEBUG){
				drawDebugVariables();
			}else{
				drawScore();
			}

			for(var i in Player.list){
				Player.list[i].drawBody();
				Player.list[i].drawGun();
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

			ctx.fillStyle = '#efeff5';
			ctx.fillRect(originX,originY, GAME_ARENA_WIDTH, GAME_ARENA_HEIGHT);

			ctx.lineWidth = 1;
			ctx.strokeStyle = '#a1a1c4';
			//Horizontal Loop
			for(i = 0; i < GAME_ARENA_WIDTH; i+= 40){
				drawLine(originX, originY + i, limitXPosition, originY + i);
			}
			for(i = 0; i < GAME_ARENA_WIDTH; i+= 40){
				drawLine(originX + i, originY, originX + i, limitYPosition);
			}

			ctx.lineWidth = 3;
			ctx.strokeStyle = 'black';
			drawLine(originX, originY, limitXPosition, originY); //Top Horizontal
			drawLine(originX, originY, originX, limitYPosition); //Left Vertical
			drawLine(limitXPosition, originY, limitXPosition, limitYPosition); //Right Verticel
			drawLine(originX, limitYPosition, limitXPosition, limitYPosition); //Bottom Horizontal
		}

		var drawScore = function(){
			ctx.fillText("Kills: " + Player.list[selfId].kills + " Deaths: " + Player.list[selfId].deaths, 0, 15);
		}

		var drawDebugVariables = function(){
			ctx.fillStyle = 'red';
			var playerData = Player.list[selfId];
			ctx.fillText("Debug", 0, 15);
			ctx.fillText("Kills: " + Player.list[selfId].kills + " Deaths: " + Player.list[selfId].deaths, 0, 30);
			ctx.fillText("ID: " + playerData.number, 0, 45);
			ctx.fillText("X: " + playerData.x, 0, 60);
			ctx.fillText("Y: " + playerData.y, 0, 75);
			ctx.fillText("Angle: " + playerData.mouseAngle, 0, 90);
			ctx.fillText("HP: " + playerData.hp, 0, 105);
			ctx.fillText("HP Max: " + playerData.hpMax, 0, 120);
			ctx.fillText("Number Of Bullets: " + playerData.numberOfBullets, 0, 135);
			ctx.fillText("Reloading: " + playerData.reloading, 0, 150);
		}

		//Receives multi-dimensional array of playerModel containing arrays of points
		var sketch = function(selfX, selfY, mouseAngle, playerModel, drawColor, diameterModifier = 0){
			//Translate Co-Ordinates relative to player's position
			var translatedPlayerModel = [];

			for(h = 0; h < playerModel.length; h++){ //Iterate through individual player model pieces

				var translatedPlayerModelPiece = [];

				for(i = 0; i < playerModel[h].length; i++){ //Iterate through individual player model piece co-ordinates
					var originX = selfX - Player.list[selfId].x + getDrawPosition('x');
					var originY = selfY - Player.list[selfId].y + getDrawPosition('y');
					
					var angle = playerModel[h][i][1];

					var mouseAngleRadians = mouseAngle * (Math.PI / 180) - (Math.PI)/2;
					var diameter = playerModel[h][i][0] + diameterModifier;

					var xOffset = Math.cos(angle + mouseAngleRadians) * diameter * 1.5;
					var yOffset = Math.sin(angle + mouseAngleRadians) * diameter * 1.5;

					translatedPlayerModelPiece.push([originX + xOffset, originY + yOffset]);
				}
				translatedPlayerModel.push(translatedPlayerModelPiece);
			}
			
			ctx.lineWidth = 4;
			ctx.strokeStyle = drawColor;
			ctx.beginPath();

			for(h = 0; h < translatedPlayerModel.length; h++){//Iterate through translated player model pieces
				ctx.moveTo(translatedPlayerModel[h][0][0], translatedPlayerModel[h][0][1]);//Move to starting co-ords of each player model piece
				for(i = 0; i < translatedPlayerModel[h].length; i++){//Iterate through translated co-ordinates in each player model piece
					ctx.lineTo(translatedPlayerModel[h][i][0], translatedPlayerModel[h][i][1]);
				}
				ctx.stroke();
			}
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

		var drawEllipse = function(x, y, radiusX, radiusY, rotation, startAngle, endAngle, anticlockwise = false){
			ctx.beginPath();
			ctx.ellipse(x, y, radiusX, radiusY, rotation, startAngle, endAngle, anticlockwise);
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
				return gamePageWidth/2;
			}
			else if(axis == 'y'){
				return gamePageHeight/2;
			}
		}

		function resizeCanvas(){
			var aspectRatio = gamePageWidth/gamePageHeight;

			var newGamePageWidth = getWindowWidth();
			var newGamePageHeight = getWindowHeight();

			var widthScaleFactor = newGamePageWidth/gamePageWidth; //New Width / Old Width
			var heightScaleFactor = newGamePageHeight/gamePageHeight; //New Height / Old Height

			ctx.scale(widthScaleFactor, heightScaleFactor);

			gamePageWidth = newGamePageWidth;
			gamePageHeight = newGamePageHeight;

			ctx.canvas.width = gamePageWidth;
			ctx.canvas.height = gamePageHeight;

			xDrawPosition = getDrawPosition('x');
  			yDrawPosition = getDrawPosition('y');
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
		window.onblur = function(){
			socket.emit('loseFocus');
		}

		document.onmousedown = function(event){
			socket.emit('keyPress', {inputId:'attack', state:true});
		}
		document.onmouseup = function(event){
			socket.emit('keyPress', {inputId:'attack', state:false});
		}
		document.onmousemove = function(event){
			var mouseAngle = Math.atan2(getDrawPosition('y') - event.y + 80, getDrawPosition('x') - event.x) * 180 / Math.PI + 180;
			socket.emit('keyPress', {inputId:'mouseAngle', state:mouseAngle});
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