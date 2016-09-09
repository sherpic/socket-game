var Entity = require('./Entity');

module.exports = function(param){
	var self = Entity(param);
	self.id = Math.random();
	self.angle = param.angle;
	self.diameter = 2;
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

		console.log(Player.list);

		for(var i in Player.list){
			var player = Player.list[i];
			if(self.collidingWith(player) && self.parent !== player.id){
				self.toRemove = true;
				player.hp -= 1;
				player.beingHit = true;
				if(player.hp <= 0){
					var shooter = Player.list[self.parent];
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
	return self;
}