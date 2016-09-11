var socket = require('socket.io');
var express = require('express');
var http = require('http');

var app = express();
var serv = http.createServer(app);

var io = socket.listen(serv);

var SOCKET_LIST = {};
process.env.PORT = 8001;

process.env.DEBUG = false;
process.env.GAME_WIDTH = 1000;
process.env.GAME_HEIGHT = 1000;

serv.listen(process.env.PORT);
console.log("Server started.");

var initPack = {player:[], bullet:[]};
var removePack = {player:[], bullet:[]};

var Player = require('./game/Player');
var Bullet = require('./game/Bullet');

GLOBAL.playerList = {};

Player.onConnect = function(socket){
	var player = Player({
		id: socket.id
	});
	GLOBAL.playerList[player.id] = player;
	initPack.player.push(player.getInitPack());

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
	for(var i in GLOBAL.playerList)
		players.push(GLOBAL.playerList[i].getInitPack());
	return players;
}

Player.onDisconnect = function(socket){
	delete GLOBAL.playerList[socket.id];
	removePack.player.push(socket.id);
}

Player.update = function(){
	var pack = [];
	for(var i in GLOBAL.playerList){
		var player = GLOBAL.playerList[i];
		player.update();
		pack.push(player.getUpdatePack());
	}
	return pack;
}

Player.resetOneTickOnlyVariables = function(){
	for(var i in GLOBAL.playerList){
		var player = GLOBAL.playerList[i];
		player.beingHit = false;
	}
}

Bullet.list = {};

Bullet.update = function(){
	var pack = [];
	for(var i in Bullet.list){
		var bullet = Bullet.list[i];
		initPack.bullet.push(bullet.getInitPack());
		if(bullet.toRemove){
			if(bullet.deathTimer < 5){
				bullet.deathTimer++;
			}
			else{
				delete Bullet.list[i];
				removePack.bullet.push(bullet.id);
			}
		}
		pack.push(bullet.getUpdatePack());
		bullet.update();
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

setInterval(function(){
	//Bullet has to be updated ahead of player as player reactions depend upon bullet collisions
	var pack = {
		bullet:Bullet.update(),
		player:Player.update(),
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

	Player.resetOneTickOnlyVariables();

}, 40)