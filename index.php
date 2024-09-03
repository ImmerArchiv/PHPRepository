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
 
require_once 'lib/Template.class.php';
require_once 'lib/Guid.class.php';

 
$frame = new Template();
$frame->load("frame.tpl");


	 
// Title - Platzhalter ersetzen
$frame->assign( "website_title", "Archiv10.PHPRepository" );
$content = new Template();
if(!file_exists('conf/config.inc.php') && !file_exists('conf/bearer.inc.php'))
{
	
	$ready = false;
	$error = null;
	
	
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $rootpath = "C:\\BagItRepository";
	} else {
		$rootpath = "/var/BagItRepository";
	}
	$repository = "default";
	$token = new Guid();
	$maxsize = "100 GB";
	$rootpathcreate = false;
		

	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') 
	{
		
		$rootpath       = $_POST['rootpath'];
		$rootpathcreate = isset($_POST['rootpathcreate']) ? true : false;
		$repository     = $_POST['repository'];
		$token          = $_POST['token'];
        $maxsize        = $_POST['maxsize'];
		$maxsizebytes   = -1;
		//create if not exists
		if(!is_dir($rootpath) && $rootpathcreate)
		{
			if(!mkdir($rootpath))
				$error = "{L_ERRORCREATEFOLDER}";
			
		}
		else if(!is_dir($rootpath))
		{
			$error = "{L_ERRORFOLDERNOTEXISTS}";
		}
		
		//check config file creation
		if(!is_writable ( 'conf' ))
		{
			$error = "{L_ERRORWRITECONFIG}";
		}

        //Check maximal size
		if(preg_match("/(\d+)\s+([TGMKB]{1,2})/",$maxsize,$treffer))
		{
			$factor = intval($treffer[1]);
			$unit = 0;
			switch($treffer[2])
			{
				case "B": $unit = 1; break;
				case "KB": $unit = 1024; break;
				case "MB": $unit = 1024 * 1024; break;
				case "GB": $unit = 1024 * 1024 * 1024; break;
				case "TB": $unit = 1024 * 1024 * 1024 * 1024; break;
				default: $error = '"' . $treffer[2]+'"' . " {L_ERRORUNIT}"; break;
			}
			if($factor >= 1)
			{
				$maxsizebytes = bcmul($factor,$unit);
			}
			else
			{
				$error = '"' . $treffer[1]+'"' . " {L_ERRORFACTOR}";
			}
		}
		else
		{
			$error = "{L_ERRORMAXSIZE}";
		}

		
		if($error == null)
		{
			//Create PHP-ConfigFiles
			$fh = fopen('conf/config.inc.php','w');
	        if(!$fh) 
				$error == "{L_ERRORCREATECONFIG}";
			else
			{
		       fwrite($fh, "<?php \r\n",1024);
			   fwrite($fh, "/** BagitPath */\r\n",1024);
			   fwrite($fh, 'define("DATAPATH",\''. $rootpath .'\');' . "\r\n",1024);
		       fwrite($fh, "?>",1024);
               fclose($fh);
			}
			
			
			//Create Repository
			
			$p = $rootpath . DIRECTORY_SEPARATOR . $repository;
			if(!is_dir($p))
				mkdir($p);
			
			//Temp Directory
			$pt = $p . DIRECTORY_SEPARATOR . 'tmp';
			if(!is_dir($pt))
				mkdir($pt);
				
			//Log Directory
			$pl = $p . DIRECTORY_SEPARATOR . 'log';
			if(!is_dir($pl))
				mkdir($pl);
				
			$fh = fopen('conf/bearer.inc.php','w');
	        if(!$fh) 
				$error == "{L_ERRORCREATEBEARER}";
			else
			{
			   $salt = password_hash($token, PASSWORD_BCRYPT);
		       fwrite($fh, "<?php \r\n",1024);
			   fwrite($fh, "/** Repository - Bearer Token */\r\n",1024);
			   fwrite($fh, '$bearer = array();' . "\r\n",1024);
			   fwrite($fh, '$bearer["'.$repository.'"] = \''.$salt.'\';' . "\r\n",1024);
		       fwrite($fh, "?>",1024);
               fclose($fh);
			}
			
			$fh = fopen('conf/repositories.inc.php','w');
            if(!$fh) 
				$error == "{L_ERRORCREATEMAXSIZE}";
			else
			{
		       fwrite($fh, "<?php \r\n",1024);
			   fwrite($fh, "/** Repository - Configuration*/\r\n",1024);
			   fwrite($fh, '$repositoryconfig = array();' . "\r\n",1024);
			   fwrite($fh, '$repositoryconfig["'.$repository.'"] = array(' ."\r\n",1024);
			   fwrite($fh, '       "maxsize" => "'.$maxsizebytes.'" );' . "\r\n",1024);
		       fwrite($fh, "?>",1024);
               fclose($fh);
			}

			if($error == null) $ready = true;

		}


    }
	else
	{
		//Get - first call
		if (version_compare(PHP_VERSION, '5.5.0') < 0) {
			$error = 'Es wird mindestens PHP 5.5.0 ben&ouml;tigt. Aktuelle Version: ' . PHP_VERSION;
		}
		//phpversion(EXTENSION);
	}
	
	if($ready)
	{
		//Install
		$content->load("ready.tpl");
		$content->assign("repository" , $repository );
		$content->assign("token" , $token);
	}
	else
	{
	
		//Install
		$content->load("forms.tpl");
		$content->assign("message" , $error != null ? '<div class="alert alert-danger" role="alert">'. $error .'</div>' : '');
		$content->assign("rootpath" , $rootpath);
		$content->assign("rootpathcreate" , $rootpathcreate?'checked="checked"':'');
		$content->assign("repository" , $repository );
		$content->assign("token" , $token);
		$content->assign("maxsize" , $maxsize);
	}
}
else
{
   //Repository Main - Page anzeigen
   $content->load("index.tpl");
	
		
	 
}

$frame->assign( "content", $content->html() );
// Die Sprachdatei laden
$langs[] = "lang.de.php";
$lang = $frame->loadLanguage($langs);


//Version.xml
$xml = simplexml_load_file('version.xml');


//version
$url  = $xml->entry->url;
$six = strpos($url,"PHPRepository/") + 14;
$version = substr($url, $six);

//date
$date = date_parse($xml->entry->commit->date);


$frame->assign("version",$version);
$frame->assign("date",sprintf("%02d.%02d.%04d", $date["day"], $date["month"], $date["year"]));

echo $frame->html();

?>

