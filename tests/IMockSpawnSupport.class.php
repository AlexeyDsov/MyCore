<?php
interface IMockSpawnSupport {
	
	public function getMock($className);
	
	public function getAny();
	
	public function getNever();
	
	public function getOnce();
	
	public function getExactly($callTimes);
	
	public function getAt($callTime);
	
	public function getReturnArgument($argumentNumber);
	
	public function getReturnCallback(Closure $callBack);
	
	public function getReturnValue($value);
}
?>