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
 
require_once 'conf/config.inc.php';
require_once 'conf/bearer.inc.php';
require_once 'conf/repositories.inc.php';

require_once 'lib/JsonEncoder.class.php';
require_once 'lib/JsonDecoder.class.php';
require_once 'lib/Constants.php';

class RequestManager {
	
	
	private $token = null;
	private $data = null;

	public function __construct() {
	}
	
    function error($header, $message)
	{  
       $data = array('state' => 'error' ,  'message' => $message);
	   
	   $encoder = new JsonEncoder($data);

   	   header($header);
   	   header('Content-Type: application/json');
	   header("Content-Length: ". $encoder->GetLength());
	   echo $encoder->GetContent();
	   exit;
	}
	
	function ok($header, $message, $jsondata)
	{
       $data = array('state' => 'ok' ,  'message' => $message);
	   foreach ( $jsondata as $key => $value )
	   {
		   $data[$key] = $value;
	   }
	   
	   $encoder = new JsonEncoder($data);

	   if($encoder->HasError())
	   {
		   $this->error($header, $encoder->GetErrorMessage());
	   }
	   
   	   header($header);
   	   header('Content-Type: application/json');
	   header("Content-Length: ".$encoder->GetLength());
	   echo $encoder->GetContent();
	   exit;
	}
	
	function okfilepart($header,  $fullPath, $offset, $maxlength)
	{
	   if ($fd = fopen ($fullPath, 'rb')) {
			$fsize = filesize($fullPath);
			
			if($offset >= $fsize)
			{
				close($fd);
				exit;
			}
			
			if($maxlength > $fsize - $offset)    
			 $maxlength = $fsize - $offset;
			
			$path_parts = pathinfo($fullPath);
			//$ext = strtolower($path_parts["extension"]);
			header($header);
			header("Content-type: application/octet-stream");
			// use 'attachment' to force a file download
			header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");
			header("Content-length: $maxlength");
			header("Cache-control: private"); //use this to open files directly
			
			
	        fseek($fd,$offset);
			for($l = 0;$l < $maxlength;$l += 2048)
			{
			   $len = 2048; 
			   if($len > $maxlength - $l)
				   $len = $maxlength - $l;
			   $bin = fread($fd, $len); 
			   echo $bin;
			}
            fclose ($fd);
	   }
	   exit;
	}
	
	function okfile($header,  $fullPath)
	{
	   if ($fd = fopen ($fullPath, 'rb')) {
		   
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			//$ext = strtolower($path_parts["extension"]);
			header($header);
			header("Content-type: application/octet-stream");
			// use 'attachment' to force a file download
			header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");
			header("Content-length: $fsize");
			header("Cache-control: private"); //use this to open files directly
			while(!feof($fd)) {
				$buffer = fread($fd, 2048);
				echo $buffer;
			}
	   }
	   fclose ($fd);
	   exit;
	}
	
	
	function validatePostRequest()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') 
		  $this->error('HTTP/1.0 400 Bad Request', 'use POST request for communication');
	}
	
	function validateBearerToken()
	{
		$headers = apache_request_headers();
	    if(isset($headers['Authorization'])){
			$matches = array();
			preg_match('/Bearer (.*)/', $headers['Authorization'], $matches);
			if(isset($matches[1])){
			  $this->token = $matches[1];
			}
		}
	    
		if($this->token == null)
		{
			$this->error('HTTP/1.0 401 Unauthorized', 'use Authorization Header');
		}
	}
	
	function validateJsonData()
	{
		  $json = file_get_contents('php://input',true);
		  if($json == null)
			   $this->error('HTTP/1.0 400 Bad Request', 'no json data received');
		  
		  $decoder = new JsonDecoder($json);
          if($decoder->HasError())
			   $this->error('HTTP/1.0 400 Bad Request', $decoder->GetErrorMessage());
		   
		  $this->data = $decoder->GetContent();
		  if($this->data == null)
			   $this->error('HTTP/1.0 400 Bad Request', 'empty hjson data received');
	}
	
	function validateMultiPartData()
	{
		  $this->data = array();
		  foreach($_POST as $key => $value){
			  $this->data[$key] = $value;
		  }
		  foreach($_FILES as $key => $value){
			  $this->data[$key] = $value;
		  }
		  if($this->data == null)
			   $this->error('HTTP/1.0 400 Bad Request', 'no post data');
	}
	
	function validData($key,$value,$pattern)
	{
		if(trim($value) === '')
			$this->error('HTTP/1.0 400 Bad Request', $key .' has empty value');
		
		if(!preg_match($pattern,$value)) 
		    $this->error('HTTP/1.0 400 Bad Request', $key .' has invalid value');
		
		return $value;
	}
	
	function GetDataUnsecure($key)
	{
		if (!array_key_exists($key,$this->data))
	       $this->error('HTTP/1.0 400 Bad Request', 'json not contains key: '.$key);

		return $this->data[$key];
	}
	
	function GetData($key,$pattern)
	{
		return $this->validData($key,$this->GetDataUnsecure($key),$pattern);
	}
	
	
	function GetArrayDataUnsecure($key,$key2)
	{
		if (!array_key_exists($key,$this->data))
	       $this->error('HTTP/1.0 400 Bad Request', 'data not contains key: '.$key);
		if (!array_key_exists($key2,$this->data[$key]))
	       $this->error('HTTP/1.0 400 Bad Request', 'file not contains key: '.$key2);
		return $this->data[$key][$key2];
	}
	function GetArrayData($key,$key2,$pattern)
	{
		return $this->validData($key ."/"+$key2, $this->GetArrayDataUnsecure($key,$key2),$pattern);
	}
	
	
	
	function GetRepositoryPath()
	{
		$path = DATAPATH . DIRECTORY_SEPARATOR . $this->GetData('repository',REGEX_REPOSITORY);
		if (!file_exists($path)) {
		    $this->error('HTTP/1.0 400 Bad Request', 'path not exists: '.$path);
	    }
		return $path;
	}
	
	function GetRepositoryConfig()
	{
      global $repositoryconfig;
	  $repository = $this->GetData('repository',REGEX_REPOSITORY);
	  if(!array_key_exists ( $repository , $repositoryconfig ))
	  {
		    $this->error('HTTP/1.0 500 Internal Server Error', 'configuration not exists');
	  }
      return $repositoryconfig[$repository];
	}
	
	function authoriseToken()
	{
	  global $bearer;
	  $repository = $this->GetData('repository',REGEX_REPOSITORY);
	  if((!array_key_exists ( $repository , $bearer ))
	   || (!password_verify($this->token,$bearer[$repository])))
      {
		 $this->error('HTTP/1.0 401 Unauthorized', 'Unauthorized Access');
      }
	}
	
}
?>