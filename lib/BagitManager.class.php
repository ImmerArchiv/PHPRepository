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
 
class BagitManager {
	
	private $path = null;
    private $config = null;
	
	public function __construct($path,$config) {
	   $this->path = $path;
	   $this->config = $config;
	}
	
	public function CreateBagit($bagit,$info)
	{
		
		$p = $this->path . DIRECTORY_SEPARATOR . strtolower($bagit);
		
		if(file_exists($p))
			return "bagit exists";
		
		//creates folders 
		mkdir($p);
		mkdir($p . DIRECTORY_SEPARATOR . 'data');

		//creates bagit.txt
		$fh = fopen($p . DIRECTORY_SEPARATOR . 'bagit.txt','w');
	    if(!$fh) return "could not create bagit.txt";	
        fwrite($fh, "BagIt-version: 0.97\r\n",1024);
        fwrite($fh, "Tag-File-Character-Encoding: UTF-8\r\n",1024);
        fclose($fh);

		//creates empty manifest-md5.txt
		$fh = fopen($p . DIRECTORY_SEPARATOR . 'manifest-md5.txt','w');
	    if(!$fh) return "could not create manifest-md5.txt";	
        fclose($fh);
		
		//bag-info.txt with content of info
		$fh = fopen($p . DIRECTORY_SEPARATOR . 'bag-info.txt','w');
	    if(!$fh) return "could not create bag-info.txt";	
		foreach ( $info as $elm )
			foreach ( $elm as $key => $value )
			{   
			   fwrite($fh, "$key: ".utf8_decode($value)."\r\n",1024);
			}
        fclose($fh);
		
	
		return null;
	}
	
	public function UploadFilePart($tempname,$uploadfile,$size)
	{
		$p = $this->path . DIRECTORY_SEPARATOR . "tmp";
		$file = $p . DIRECTORY_SEPARATOR . $tempname;
		
		if(!$this->checkSize($size))
		  return "Quota reached";	
		
		if(!is_writable ( $p ))
		  return "File not writable in tmp-Directory";
		
		$data = file_get_contents($uploadfile);
		file_put_contents($file, $data, FILE_APPEND);
		
			
		return null;	
	}
	
	public function AppendFile($bagit,$name,$md5,$tempname)
	{
		$p = $this->path . DIRECTORY_SEPARATOR . $bagit;
		
		$tempfile = $this->path . DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . $tempname;
		if(!file_exists($tempfile))
			return "tempfile not exists";
		
		$filename = 'data' . DIRECTORY_SEPARATOR . $name;
		$file = $p . DIRECTORY_SEPARATOR . $filename;
		if(file_exists($file))
			return "file exists";
		
		if(!is_writable ( $p ))
		  return "File not writable in data-Directory";
		
		//Check Repository Size
		$size = filesize($tempfile);
		if(!$this->checkSize($size))
		  return "Quota reached";	
		
		rename($tempfile, $file);
		

		$md5file = md5_file($file);
		
		if($md5file != $md5)
		{
			 unlink($file);
			 return "wrong md5 hash";
		}
		
		//readonly for user
		if(!chmod($file, 0400))
		{
			 unlink($file);
			 return "change mode failed";
		}
		
		//append md5 hash to manifest-md5.txt
		$fh = fopen($p . DIRECTORY_SEPARATOR . 'manifest-md5.txt','a');
	    if(!$fh) return null;	
		fwrite($fh, "$md5 $filename\r\n",1024);
        fclose($fh);
		
		$time = date(DATE_RFC2822);
		$this->logChange("append file name=$name, md5=$md5file, bagit=$bagit, time=$time\r\n");
		
		return null;
	}
	
	
	private function checkSize($length)
	{
		$status = $this->getDirectorySize($this->path);
		$current = $status['size'];
		$maxsize = $this->config['maxsize'];
		
		$sum = bcadd($current,$length);
		if(bccomp($sum,$maxsize) <= 0)
		   return true;
		   
		return false; //repository is full
	}
	
	private function logChange($current)
	{
		$file = $this->path . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "log.txt";
		file_put_contents($file, $current, FILE_APPEND);
	}
	
	private function readBagitInfo($bagit)
	{
		$fh = fopen($this->path . DIRECTORY_SEPARATOR . $bagit . DIRECTORY_SEPARATOR . 'bag-info.txt', "r");
		if(!$fh) return null;
		$info = array();
		while (($line = fgets($fh)) !== false) {
		   if(ctype_space($line)) continue;
           list( $key, $value ) = explode( ":", $line, 2 );
		   
		   array_push($info , array ($key => utf8_encode(trim($value))));
        }
        fclose($fh);
	    return $info;
	}
	
	
	private function readBagitStatus($bagit)
	{
		if(!date_default_timezone_set("UTC"))
		{
			return null;
		}
		if(!is_dir($this->path . DIRECTORY_SEPARATOR . $bagit))
			return null;
		
		$status = $this->getDirectorySize($this->path . DIRECTORY_SEPARATOR . $bagit);
		return $status;
	}
	
	
	private function readBagitData($bagit)
	{
		$p = $this->path . DIRECTORY_SEPARATOR . $bagit;
		$fh = fopen( $p . DIRECTORY_SEPARATOR . 'manifest-md5.txt', "r");
		if(!$fh) return null;
		$data = array();
		while (($line = fgets($fh)) !== false) {
		   if(ctype_space($line)) continue;
           list( $md5, $filepath ) = explode( " ", $line, 2 );
		   list( $temp, $name ) = explode( DIRECTORY_SEPARATOR, $filepath, 2 );
		   $fsize = filesize($p . DIRECTORY_SEPARATOR . trim($filepath));
		   if(!file_exists($p . DIRECTORY_SEPARATOR . trim($filepath))) $fsize = -1;
		   array_push($data , array ("name" => trim($name), "md5" => $md5, "length" => $fsize));
        }
        fclose($fh);
	    return $data;
	}
	
	
	private function getDirectorySize($path) 
	{
		$bytes = 0;
		$files = 0;
		$bagits = 0;
		$lastModified = 0;
		$path = realpath($path);
		if($path!==false){
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
				$name = $object->getFilename();
				$parent = basename(dirname($object->getRealPath()));
				
				if($parent == "tmp") continue;
				if($parent == "log") continue;
				
				if($parent == "data")
				{
					$bytes = bcadd($bytes,$object->getSize());
					if($object->getMTime() > $lastModified)
						$lastModified = $object->getMTime();
				    $files++;
				}
				else
				{
					if($name == "bagit.txt") $bagits++;
				}
			}
		}
		return array("lastmodified" => date(DATE_RFC2822,$lastModified), "files" => $files, "bagits" => $bagits, "size" => (string)$bytes);
    }
	
	public function Status()
	{
		if(!date_default_timezone_set("UTC"))
		{
			return null;
		}
		
		$status = $this->getDirectorySize($this->path);
		$status["maxsize"] = $this->config['maxsize'];
		return $status;
	}
	
	
	public function ListAll($skip,$take)
	{
		$a = glob($this->path . '/*' , GLOB_ONLYDIR);
		$l = count($a);
		
		$bagits = array();
		
		for($x = $skip;$x < $l && $x < ($skip + $take);$x++)
		{
		  $bag = $a[$x];
		  $basename = basename($bag);
		  if($basename == "tmp") continue; //Temp Upload Directory
		  if($basename == "log") continue; //Log Directory
		  
		  $info = $this->readBagitInfo($basename);
		  $status = $this->readBagitStatus($basename);
		  $b = array("bagit" => $basename, "info" => $info, "status" => $status);
		  array_push( $bagits , $b);
		}
		
		return array("bagits" => $bagits);
		
	}
	
	public function ListOne($bagit)
	{
		if(!is_dir($this->path . DIRECTORY_SEPARATOR . $bagit))
			return null;
		
		$list = array();
		
		$list['bagit'] = $bagit;
		$list['info']  = $this->readBagitInfo($bagit);
		$list['status']  = $this->readBagitStatus($bagit);
		$list['data']  = $this->readBagitData($bagit);
		
	    return $list;
		
	}
	
	public function CalculateChecksum($bagit,$name)
	{
		$file = $this->path . DIRECTORY_SEPARATOR . $bagit . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $name;
		if(!file_exists($file))
			return null;
	 
	 	$list = array();
	    $list['bagit'] = $bagit;
		$list['name']  = $name;
		$list['md5']  = md5_file($file);
		
	    return $list;
	}
	
	public function GetFilePath($bagit,$name)
	{
		$file = $this->path . DIRECTORY_SEPARATOR . $bagit . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $name;
		if(!file_exists($file))
			return null;
	    return $file;
	}
	
	public function GetLogFilePath()
	{
		$file = $this->path . DIRECTORY_SEPARATOR . "log" . DIRECTORY_SEPARATOR . "log.txt";
		if(!file_exists($file))
			return null;
	    return $file;
	}
}

?>