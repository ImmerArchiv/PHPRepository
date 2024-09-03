<?php

$repository = $argv[1];
$maxsize = $argv[2];


$token = strtolower(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', 
		mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), 
		mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), 
		mt_rand(0, 65535), mt_rand(0, 65535)));

$error = null;





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
		}


$salt = password_hash($token, PASSWORD_BCRYPT);
echo "repository $repository\r\n";
echo "token $token\r\n";

echo "mkdir $repository\r\n";
echo "mkdir $repository" . DIRECTORY_SEPARATOR . "tmp\r\n";
echo "mkdir $repository" . DIRECTORY_SEPARATOR . "log\r\n";
echo "chown -R www-data $repository\r\n";
echo "chgrp -R www-data $repository\r\n";

echo "vi conf/bearer.inc.php\r\n";
echo '$bearer["'.$repository.'"] = \''.$salt.'\';' . "\r\n";


echo "vi conf/repositories.inc.php\r\n";
echo '$repositoryconfig["'.$repository.'"] = array(' ."\r\n";
echo '       "maxsize" => "'.$maxsizebytes.'" );' . "\r\n";





?>
