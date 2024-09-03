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
 
require_once 'lib/RequestManager.class.php';
require_once 'lib/BagitManager.class.php';


$request = new RequestManager();

 
$request->validatePostRequest();
$request->validateBearerToken();
$request->validateJsonData();
$request->authoriseToken();

$path = $request->GetRepositoryPath();
$config = $request->GetRepositoryConfig();

$bagit = $request->GetData('bagit',REGEX_BAGIT);
$name = $request->GetData('name',REGEX_FILENAME);

$offset = $request->GetData('offset',REGEX_LONG);
$maxlength = $request->GetData('maxlength',REGEX_LONG);

$bagman = new BagitManager($path,$config);


$file = $bagman->GetFilePath($bagit,$name);

if($file == null)
	$request->error('HTTP/1.0 400 Bad Request','file not found');

$request->okfilepart('HTTP/1.0 200 Ok',$file,$offset,$maxlength);
 
?>