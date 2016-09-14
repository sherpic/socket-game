var Entity = require('./Entity');
var Bullet = require('./Bullet');

module.exports = function(param){
	var self = Entity(param);

	self.class = param.class;
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
	self.currentSpeedX = 0;
	self.currentSpeedY = 0;
	self.movementStatus = 'stopped';
	self.affectedByBoundaries = true;
	self.friction = 0.75;
	self.hpMax = 100;
	self.hp = self.hpMax;
	self.beingHit = false;
	self.shooting = false;
	self.betweenShotTime = getBetweenShotTime(param.class);
	self.reloading = false;
	self.reloadTime = getReloadTime(param.class);
	self.numberOfBullets = getMaxBullets(param.class);
	self.maxBullets = self.numberOfBullets;
	self.kills = 0;
	self.deaths = 0;
	self.collisionRadiusX = 55;
	self.collisionRadiusY = 30;

	var super_update = self.update;
	self.update = function(){
		self.updateSpd();
		super_update();
		if(self.pressingAttack && !self.shooting && !self.reloading){
			if(self.numberOfBullets > 0){
				self.shooting = true;
				self.shootBullet(self.mouseAngle);
				self.numberOfBullets--;
				setTimeout(function(){
					self.shooting = false;
				}, self.betweenShotTime);
			}
			else{
				self.reloading = true;
				setTimeout(function(){
					self.numberOfBullets = self.maxBullets;
					self.reloading = false;
				}, self.reloadTime);
			}
		}
	}
	self.shootBullet = function(angle){
		var variance;
		if(self.movementStatus == 'moving'){
			variance = 10;
		}
		else{
			variance = 3;
		}
		var inaccuracy = Math.floor(Math.random()*(variance-(-variance+1))-variance);

		if(self.class == 'shotgun'){
			var shotgunVariance = 10;
			for(i = 0; i < 8; i++){
				var b = Bullet({
					parent: self.id,
					class: self.class, 
					angle: angle + Math.floor(Math.random()*(shotgunVariance-(-shotgunVariance+1))-shotgunVariance) + inaccuracy,
					x: self.x,
					y: self.y,
				});
				Bullet.list[b.id] = b;
			}
		}
		else{
			var b = Bullet({
				parent: self.id, 
				class: self.class, 
				angle: angle + inaccuracy,
				x: self.x,
				y: self.y,
			});
			Bullet.list[b.id] = b;
		}
	}

	self.updateSpd = function(){
		if(self.pressingLeft && !self.pressingRight && !self.pressingUp && !self.pressingDown && self.x > 20){
			self.spdX = -self.maxSpd;
			self.spdY = 0;
		}
		else if(!self.pressingLeft && self.pressingRight && !self.pressingUp && !self.pressingDown && self.x < process.env.GAME_WIDTH-20){
			self.spdX = self.maxSpd;
			self.spdY = 0;
		}
		else if(!self.pressingLeft && !self.pressingRight && self.pressingUp && !self.pressingDown && self.y > 20){
			self.spdX = 0;
			self.spdY = -self.maxSpd;
		}
		else if(!self.pressingLeft && !self.pressingRight && !self.pressingUp && self.pressingDown && self.y < process.env.GAME_HEIGHT-20){
			self.spdX = 0;
			self.spdY = self.maxSpd;
		}
		else if(self.pressingLeft && !self.pressingRight && self.pressingUp && !self.pressingDown && self.x > 20 && self.y > 20){
			self.spdX = (0.71*-self.maxSpd);
			self.spdY = (0.71*-self.maxSpd);
		}
		else if(self.pressingLeft && !self.pressingRight && !self.pressingUp && self.pressingDown && self.x > 20 && self.y < process.env.GAME_HEIGHT-20){
			self.spdX = (0.71*-self.maxSpd);
			self.spdY = (0.71*self.maxSpd);
		}
		else if(!self.pressingLeft && self.pressingRight && self.pressingUp && !self.pressingDown && self.x < process.env.GAME_WIDTH-20 && self.y > 20){
			self.spdX = (0.71*self.maxSpd);
			self.spdY = (0.71*-self.maxSpd);
		}
		else if(!self.pressingLeft && self.pressingRight && !self.pressingUp && self.pressingDown && self.x < process.env.GAME_WIDTH-20 && self.y < process.env.GAME_HEIGHT-20){
			self.spdX = (0.71*self.maxSpd);
			self.spdY = (0.71*self.maxSpd);
		}
		else if(self.pressingLeft && !self.pressingRight && self.pressingUp && !self.pressingDown && self.x > 10 && self.x < 20 && self.y > 20){
			self.spdX = 0;
			self.spdY = -self.maxSpd;
		}
		else if(self.pressingLeft && !self.pressingRight && !self.pressingUp && self.pressingDown && self.x > 10 && self.x < 20 && self.y < process.env.GAME_HEIGHT-20){
			self.spdX = 0;
			self.spdY = self.maxSpd;
		}
		else if(!self.pressingLeft && self.pressingRight && self.pressingUp && !self.pressingDown && self.x > process.env.GAME_WIDTH-20 && self.x < process.env.GAME_WIDTH-10 && self.y > 20){
			self.spdX = 0;
			self.spdY = -self.maxSpd;
		}
		else if(!self.pressingLeft && self.pressingRight && !self.pressingUp && self.pressingDown && self.x > process.env.GAME_WIDTH-20 && self.x < process.env.GAME_WIDTH-10 && self.y < process.env.GAME_HEIGHT-20){
			self.spdX = 0;
			self.spdY = self.maxSpd;
		}

		else if(self.pressingLeft && !self.pressingRight && self.pressingUp && !self.pressingDown && self.x > 20 && self.y > 10 && self.y < 20){
			self.spdX = -self.maxSpd;
			self.spdY = 0;
		}
		else if(self.pressingLeft && !self.pressingRight && !self.pressingUp && self.pressingDown && self.x > 20 && self.y > process.env.GAME_HEIGHT-20 && self.y < process.env.GAME_HEIGHT-10){
			self.spdX = -self.maxSpd;
			self.spdY = 0;
		}
		else if(!self.pressingLeft && self.pressingRight && self.pressingUp && !self.pressingDown && self.x < process.env.GAME_WIDTH-20 && self.y > 10 && self.y < 20){
			self.spdX = self.maxSpd;
			self.spdY = 0;
		}
		else if(!self.pressingLeft && self.pressingRight && !self.pressingUp && self.pressingDown && self.x < process.env.GAME_WIDTH-20 && self.y > process.env.GAME_HEIGHT-20 && self.y < process.env.GAME_HEIGHT-10){
			self.spdX = self.maxSpd;
			self.spdY = 0;
		}
		else{
			self.spdY = 0;
			self.spdX = 0;
		}
	}

	self.updatePosition = function(){
		//Gradually slows player to a stop
		if(self.spdX != 0 || self.spdY != 0){
			self.movementStatus = 'moving';
			self.currentSpeedX = self.spdX;
			self.currentSpeedY = self.spdY;
		}
		else if(self.movementStatus == 'moving' && self.spdX == 0 && self.spdY == 0){
			self.movementStatus = 'slowing';
		}
		else if(self.movementStatus == 'slowing'){
			self.currentSpeedX = self.currentSpeedX * self.friction;
			self.currentSpeedY = self.currentSpeedY * self.friction;
		}
		else{
			self.movementStatus = 'stopped';
		}
		self.x += self.currentSpeedX;
		self.y += self.currentSpeedY;
	}

	self.respawn = function(){
		self.hp = self.hpMax;
		self.x = Math.random() * process.env.GAME_WIDTH;
		self.y = Math.random() * process.env.GAME_HEIGHT;
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
			class: self.class,
			x: self.x,
			y: self.y,
			mouseAngle: self.mouseAngle,
			pressingAttack: self.pressingAttack,
			number: self.number,
			hp:self.hp,
			hpMax:self.hpMax,
			kills:self.kills,
			deaths:self.deaths,
			numberOfBullets:self.numberOfBullets,
			maxBullets:self.maxBullets,
		};
	}
	self.getUpdatePack = function(){
		return {
			id: self.id,
			class: self.class,
			x: self.x,
			y: self.y,
			mouseAngle: self.mouseAngle,
			pressingAttack: self.pressingAttack,
			hp:self.hp,
			beingHit:self.beingHit,
			kills:self.kills,
			deaths:self.deaths,
			hpMax:self.hpMax,
			numberOfBullets:self.numberOfBullets,
			reloading:self.reloading,
		};
	}

	function getMaxBullets(playerClass){
		switch(playerClass){
			case 'pistol':
				return 8;
				break;
			case 'smg':
				return 32;
				break;
			case 'shotgun':
				return 5;
				break;
			case 'assault':
				return 30;
				break;
			case 'bolt-action-rifle':
				return 6;
				break;
			case 'machine-gun':
				return 100;
				break;
		}
	}

	function getBetweenShotTime(playerClass){
		switch(playerClass){
			case 'pistol':
				return 350;
				break;
			case 'smg':
				return 100;
				break;
			case 'shotgun':
				return 1000;
				break;
			case 'assault':
				return 130;
				break;
			case 'bolt-action-rifle':
				return 2000;
				break;
			case 'machine-gun':
				return 100;
				break;
		}
	}

	function getReloadTime(playerClass){
		switch(playerClass){
			case 'pistol':
				return 2000;
				break;
			case 'smg':
				return 2000;
				break;
			case 'shotgun':
				return 4200;
				break;
			case 'assault':
				return 3200;
				break;
			case 'bolt-action-rifle':
				return 3200;
				break;
			case 'machine-gun':
				return 5000;
				break;
		}
	}
	return self;
}