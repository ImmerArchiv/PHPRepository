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
$request->validateMultiPartData();
$request->authoriseToken();

$path = $request->GetRepositoryPath();
$config = $request->GetRepositoryConfig();

$tempname = $request->GetData('tempname',REGEX_FILENAME);
$uploadname = $request->GetArrayDataUnsecure('data','tmp_name'); //comes form webserver
$uploadsize = $request->GetArrayData('data','size',REGEX_LONG);

//Append File
$bagman = new BagitManager($path,$config);
$error = $bagman->UploadFilePart($tempname,$uploadname,$uploadsize);

if($error != null)
	$request->error('HTTP/1.0 400 Bad Request',$error);

$request->ok('HTTP/1.0 201 Created','part successfuly stored', array("tempname" => $tempname));

 
?>