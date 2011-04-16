<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

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