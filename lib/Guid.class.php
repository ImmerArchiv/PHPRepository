<?php

/*
 *  Copyright notice
 *
 *  (c) 2016 Dirk Friedenberger <archiv10@frittenburger.de>
 *
 *  All rights reserved
 *
 *  This script is part of the Archiv10.PHPRepository project. The PHPRepository is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */



class Guid {
	
	private $guid = null;

	public function __construct() {
	   $this->guid = strtolower($this->CreateGuid());
	}

    private function CreateGUID()
	{
	 /*
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}
	  */
	  return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', 
		mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), 
		mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), 
		mt_rand(0, 65535), mt_rand(0, 65535));
	}

	public function __toString()
	{
	  return $this->guid;
	}
}

?>