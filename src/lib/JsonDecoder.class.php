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

	
class JsonDecoder {
	
	private $content = null;
	private $error = null;
	
	public function __construct($json) {
		
		$this->content = json_decode( $json  , 'true');
		$this->error = json_last_error();

	}
	
	public function HasError()
	{
		return $this->error != 0;
	}
	
	
    public function GetErrorMessage()
	{
		switch($this->error) {
			case JSON_ERROR_NONE:
				return 'Keine Fehler';
			case JSON_ERROR_DEPTH:
				return 'Maximale Stacktiefe ueberschritten';
			case JSON_ERROR_STATE_MISMATCH:
				return 'Unterlauf oder Nichtuebereinstimmung der Modi';
			case JSON_ERROR_CTRL_CHAR:
				return 'Unerwartetes Steuerzeichen gefunden';
			case JSON_ERROR_SYNTAX:
				return 'Syntaxfehler, ungueltiges JSON';
			case JSON_ERROR_UTF8:
				return 'Missgestaltete UTF-8 Zeichen, moeglicherweise fehlerhaft kodiert';
			default:
				return 'Unbekannter Fehler ' . $this->error;
		}
	 
	}
	
    public function GetLength()
	{
		return strlen($this->content);
	}
	
    public function GetContent()
	{
		return $this->content;
	}
}

?>