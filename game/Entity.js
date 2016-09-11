module.exports = function(param){
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
		var collisionCenterX = (Math.cos(mouseAngleRadians) * (player.collisionRadiusX-20)) + player.x;
		var collisionCenterY = (Math.sin(mouseAngleRadians) * (player.collisionRadiusX-20)) + player.y;

		var collisionFactor = Math.pow(Math.cos(mouseAngleRadians) * (self.x - collisionCenterX) + Math.sin(mouseAngleRadians) * (self.y - collisionCenterY), 2) / Math.pow(player.collisionRadiusX, 2) + Math.pow(Math.sin(mouseAngleRadians) * (self.x - collisionCenterX) - Math.cos(mouseAngleRadians) * (self.y - collisionCenterY), 2) / Math.pow(player.collisionRadiusY, 2);

		if(collisionFactor <= 1){
			return true;
		}
		return false;
	}
	return self;
}