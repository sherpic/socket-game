var socket = require('socket.io');
var express = require('express');
var http = require('http');

var app = express();
var serv = http.createServer(app);

var io = socket.listen(serv);

var SOCKET_LIST = {};
var PORT = 8001;

var DEBUG = true;
var GAME_WIDTH = 1000;
var GAME_HEIGHT = 1000;
var playerCollisionRadiusX = 70;
var playerCollisionRadiusY = 40;

serv.listen(PORT);
console.log("Server started.");

var Entity = function(param){
	var self = {
		x:50,
		y:50,
		spdX:0,
		spdY:0,
		id:"",
	}
	if(param){
		if(param.x)
			self.x = param.x;
		if(param.y)
			self.y = param.y;
		if(param.id)
			self.id = param.id;
	}

	self.update = function(){
		self.updatePosition();
	}
	self.updatePosition = function(){
		if(!self.affectedByBoundaries){
			self.x += self.spdX;
			self.y += self.spdY;
		}
	}
	self.getDistance = function(player){
		return Math.sqrt(Math.pow(self.x-player.x,2) + Math.pow(self.y-player.y,2));
	}
	self.collidingWith = function(player){
		var mouseAngleRadians = player.mouseAngle * (Math.PI / 180);
		var collisionCenterX = (Math.cos(mouseAngleRadians) * (playerCollisionRadiusX-30)) + player.x;
		var collisionCenterY = (Math.sin(mouseAngleRadians) * (playerCollisionRadiusX-30)) + player.y;

		var collisionFactor = Math.pow(self.x - collisionCenterX, 2)/Math.pow(playerCollisionRadiusX, 2) + Math.pow(self.y - collisionCenterY, 2)/Math.pow(playerCollisionRadiusY, 2);

		if(collisionFactor <= 1){
			console.log("Collided: " + collisionFactor);
			return true;
		}
		return false;
	}
	return self;
}

var Player = function(param){
	var self = Entity(param);

	self.number = "" + Math.floor(10*Math.random());
	self.pressingRight = false;
	self.pressingLeft = false;
	self.pressingUp = false;
	self.pressingDown = false;
	self.pressingAttack = false;
	self.mouseAngle = 0;
	self.windowWidth = 0;
	self.windowHeight = 0;
	self.maxSpd = 10;
	self.affectedByBoundaries = true;
	self.hp = 10;
	self.hpMax = 10;
	self.kills = 0;
	self.deaths = 0;

	var super_update = self.update;
	self.update = function(){
		self.updateSpd();
		super_update();
		if(self.pressingAttack){
			self.shootBullet(self.mouseAngle);
		}
	}
	self.shootBullet = function(angle){
		var b = Bullet({
			parent: self.id, 
			angle: angle,
			x: self.x,
			y: self.y,
		});
	}

	self.updateSpd = function(){
		if(self.pressingLeft && self.x > 20 && !self.pressingDown && !self.pressingUp){
			self.spdX = -self.maxSpd;
			self.spdY = 0;
		}
		else if(self.pressingRight && self.x < GAME_WIDTH-20 && !self.pressingDown && !self.pressingUp){
			self.spdX = self.maxSpd;
			self.spdY = 0;
		}
		else if(self.pressingUp && self.y > 20 && !self.pressingLeft && !self.pressingRight){
			self.spdY = -self.maxSpd;
			self.spdX = 0;
		}
		else if(self.pressingDown && self.y < GAME_HEIGHT-20 && !self.pressingLeft && !self.pressingRight){
			self.spdY = self.maxSpd;
			self.spdX = 0;
		}
		else if(self.pressingLeft && self.x > 20 && self.pressingUp && self.y > 20){
			self.spdX = (0.71*-self.maxSpd);
			self.spdY = (0.71*-self.maxSpd);
		}
		else if(self.pressingLeft && self.x > 20 && self.pressingDown && self.y < GAME_HEIGHT-20){
			self.spdX = (0.71*-self.maxSpd);
			self.spdY = (0.71*self.maxSpd);
		}
		else if(self.pressingRight && self.x < GAME_WIDTH-20 && self.pressingUp && self.y > 20){
			self.spdX = (0.71*self.maxSpd);
			self.spdY = (0.71*-self.maxSpd);
		}
		else if(self.pressingRight && self.x < GAME_WIDTH-20 && self.pressingDown && self.y < GAME_HEIGHT-20){
			self.spdX = (0.71*self.maxSpd);
			self.spdY = (0.71*self.maxSpd);
		}
		else if(self.pressingLeft && self.x > 20 && self.pressingUp && self.y <= 20){
			self.spdX = -self.maxSpd;
		}
		else if(self.pressingLeft && self.x > 20 && self.pressingDown && self.y >= GAME_HEIGHT-20){
			self.spdX = -self.maxSpd;
		}
		else if(self.pressingRight && self.x < GAME_WIDTH-20 && self.pressingUp && self.y <= 20){
			self.spdX = self.maxSpd;
		}
		else if(self.pressingRight && self.x < GAME_WIDTH-20 && self.pressingDown && self.y >= GAME_HEIGHT-20){
			self.spdX = self.maxSpd;
		}
		else if(self.pressingLeft && self.x <= 20 && self.pressingUp && self.y > 20){
			self.spdY = self.maxSpd;
		}
		else if(self.pressingLeft && self.x <= 20 && self.pressingDown && self.y < GAME_HEIGHT-20){
			self.spdY = self.maxSpd;
		}
		else if(self.pressingRight && self.x >= GAME_WIDTH-20 && self.pressingUp && self.y > 20){
			self.spdY = self.maxSpd;
		}
		else if(self.pressingRight && self.x >= GAME_WIDTH-20 && self.pressingDown && self.y < GAME_HEIGHT-20){
			self.spdY = self.maxSpd;
		}
		else{
			self.spdY = 0;
			self.spdX = 0;
		}
	}

	self.updatePosition = function(){
		self.x += self.spdX;
		self.y += self.spdY;
	}

	self.respawn = function(){
		self.hp = self.hpMax;
		self.x = Math.random() * GAME_WIDTH;
		self.y = Math.random() * GAME_HEIGHT;
	}

	self.recordKill = function(){
		self.kills += 1;
	}

	self.recordDeath = function(){
		self.deaths += 1;
	}

	self.getInitPack = function(){
		return {
			id: self.id,
			x: self.x,
			y: self.y,
			mouseAngle: self.mouseAngle,
			number: self.number,
			hp:self.hp,
			hpMax:self.hpMax,
			kills:self.kills,
			deaths:self.deaths,
		};
	}
	self.getUpdatePack = function(){
		return {
			id: self.id,
			x: self.x,
			y: self.y,
			mouseAngle: self.mouseAngle,
			hp:self.hp,
			kills:self.kills,
			deaths:self.deaths,
			hpMax:self.hpMax,
		};
	}

	Player.list[self.id] = self;
	initPack.player.push(self.getInitPack());
	return self;
}
Player.list = {};
Player.onConnect = function(socket){
	var player = Player({
		id: socket.id
	});
	socket.on('keyPress', function(data){
		if(data.inputId === 'left')
			player.pressingLeft = data.state;
		else if(data.inputId === 'right')
			player.pressingRight = data.state;
		else if(data.inputId === 'up')
			player.pressingUp = data.state;
		else if(data.inputId === 'down')
			player.pressingDown = data.state;
		else if(data.inputId === 'attack')
			player.pressingAttack = data.state;
		else if(data.inputId === 'mouseAngle')
			player.mouseAngle = data.state;
	});
	socket.on('loseFocus', function(){
		player.pressingLeft = false;
		player.pressingRight = false;
		player.pressingUp = false;
		player.pressingDown = false;
		player.pressingAttack = false;
	});

	socket.emit('init', {
		selfId:socket.id,
		player:Player.getAllInitPack(),
		bullet:Bullet.getAllInitPack(),
	});
}

Player.getAllInitPack = function(){
	var players = [];
	for(var i in Player.list)
		players.push(Player.list[i].getInitPack());
	return players;
}

Player.onDisconnect = function(socket){
	delete Player.list[socket.id];
	removePack.player.push(socket.id);
}

Player.update = function(){
	var pack = [];
	for(var i in Player.list){
		var player = Player.list[i];
		player.update();
		pack.push(player.getUpdatePack());
	}
	return pack;
}

var Bullet = function(param){
	var self = Entity(param);
	self.id = Math.random();
	self.angle = param.angle;
	self.spdX = Math.cos(param.angle/180*Math.PI) * 10;
	self.spdY = Math.sin(param.angle/180*Math.PI) * 10;
	self.affectedByBoundaries = false;
	self.parent = param.parent;

	self.timer = 0;
	self.toRemove = false;
	var super_update = self.update;
	self.update = function(){
		if(self.timer++ > 100)
			self.toRemove = true;
		super_update();

		for(var i in Player.list){
			var player = Player.list[i];
			player.mouseAngle
			if(self.collidingWith(player) && self.parent !== player.id){
				player.hp -= 1;
				if(player.hp <= 0){
					var shooter = Player.list[self.parent];
					if(shooter)
						shooter.recordKill();
					player.recordDeath();
					player.respawn();
				}
				self.toRemove = true;
			}
		}
	}
	self.getInitPack = function(){
		return {
			id:self.id,
			x:self.x,
			y:self.y,
		};
	}
	self.getUpdatePack = function(){
		return {
			id:self.id,
			x:self.x,
			y:self.y,
			angle:self.angle,
		};
	}
	Bullet.list[self.id] = self;
	initPack.bullet.push(self.getInitPack());
	return self;
}
Bullet.list = {};

Bullet.update = function(){
	var pack = [];
	for(var i in Bullet.list){
		var bullet = Bullet.list[i];
		bullet.update();
		if(bullet.toRemove){
			delete Bullet.list[i];
			removePack.bullet.push(bullet.id);
		}
		else
			pack.push(bullet.getUpdatePack());
	}
	return pack;
}

Bullet.getAllInitPack = function(){
	var bullets = [];
	for(var i in Bullet.list)
		bullets.push(Bullet.list[i].getInitPack());
	return bullets;
}

io.sockets.on('connection', function(socket){
	socket.id = Math.random();
	SOCKET_LIST[socket.id] = socket;

	Player.onConnect(socket);
	socket.on('disconnect', function(){
		delete SOCKET_LIST[socket.id];
		Player.onDisconnect(socket);
	});
	socket.on('sendMsgToServer', function(data){
		var playerName = ("" + socket.id).slice(2,7);
		for(var i in SOCKET_LIST){
			SOCKET_LIST[i].emit('addToChat', playerName + ': ' + data);
		}
	});
	socket.on('evalServer', function(data){
		if(!DEBUG)
			return;
		var res = eval(data);
		socket.emit('evalAnswer', res);
	});
});

var initPack = {player:[], bullet:[]};
var removePack = {player:[], bullet:[]};

setInterval(function(){
	var pack = {
		player:Player.update(),
		bullet:Bullet.update(),
	}

	for(var i in SOCKET_LIST){
		var socket = SOCKET_LIST[i];
		socket.emit('init', initPack);
		socket.emit('update', pack);
		socket.emit('remove', removePack);
	}
	initPack.player = [];
	initPack.bullet = [];
	removePack.player = [];
	removePack.bullet = [];

}, 1000/25)

