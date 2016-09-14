var Entity = require('./Entity');

module.exports = function(param){
	var self = Entity(param);
	self.id = Math.random();
	self.angle = param.angle;
	self.diameter = 2;
	self.damage = getDamage(param.class);
	self.spdX = Math.cos(param.angle/180*Math.PI) * 40;
	self.spdY = Math.sin(param.angle/180*Math.PI) * 40;
	self.affectedByBoundaries = false;
	self.parent = param.parent;

	self.liveTimer = 0;
	self.deathTimer = 0;
	self.toRemove = false;
	var super_update = self.update;
	self.update = function(){
		if(self.liveTimer++ > 15)
			self.toRemove = true;
		super_update();

		for(var i in GLOBAL.playerList){
			var player = GLOBAL.playerList[i];
			if(self.collidingWith(player) && !self.toRemove && self.parent !== player.id){
				self.toRemove = true;
				player.hp -= self.damage;
				player.beingHit = true;
				if(player.hp <= 0){
					var shooter = GLOBAL.playerList[self.parent];
					if(shooter)
						shooter.recordKill();
					player.recordDeath();
					player.respawn();
				}
			}
		}
	}
	self.getInitPack = function(){
		return {
			id:self.id,
			x:self.x,
			y:self.y,
			diameter:self.diameter,
		};
	}
	self.getUpdatePack = function(){
		return {
			id:self.id,
			x:self.x,
			y:self.y,
			angle:self.angle,
			deathTimer:self.deathTimer,
			diameter:self.diameter,
		};
	}
	function getDamage(playerClass){
		switch(playerClass){
			case 'pistol':
				return 40;
				break;
			case 'smg':
				return 40;
				break;
			case 'shotgun':
				return 20;
				break;
			case 'assault':
				return 50;
				break;
			case 'bolt-action-rifle':
				return 90;
				break;
			case 'machine-gun':
				return 40;
				break;
		}
	}
	return self;
}