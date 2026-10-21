<?php

function fail($message)
{
	fwrite(STDERR, $message . PHP_EOL);
	exit(1);
}

function writeConfig($path, $variable, $values, $comment)
{
	$permissions = fileperms($path);
	if ($permissions === false) {
		throw new RuntimeException('Could not read permissions for ' . $path);
	}

	$temporaryPath = tempnam(dirname($path), '.adduser-');
	if ($temporaryPath === false) {
		throw new RuntimeException('Could not create temporary config file');
	}

	$content = "<?php\n/** " . $comment . " */\n$" . $variable . ' = ' . var_export($values, true) . ";\n";
	if (file_put_contents($temporaryPath, $content, LOCK_EX) === false
		|| !chmod($temporaryPath, $permissions & 0777)
		|| !rename($temporaryPath, $path)) {
		unlink($temporaryPath);
		throw new RuntimeException('Could not update ' . $path);
	}
}

if ($argc !== 3) {
	fail('Usage: php adduser.php <username> "<size>"');
}

$repository = $argv[1];
$maxsize = $argv[2];

if (!preg_match('/^[a-z]+$/D', $repository)) {
	fail('Username must contain lowercase letters only.');
}

if (!preg_match('/^([1-9][0-9]*)\s+(B|KB|MB|GB|TB)$/iD', $maxsize, $matches)) {
	fail('Size must use a positive number and one of these units: B, KB, MB, GB, TB.');
}

$configDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'conf';
$configPath = $configDirectory . DIRECTORY_SEPARATOR . 'config.inc.php';
$bearerPath = $configDirectory . DIRECTORY_SEPARATOR . 'bearer.inc.php';
$repositoriesPath = $configDirectory . DIRECTORY_SEPARATOR . 'repositories.inc.php';

foreach (array($configPath, $bearerPath, $repositoriesPath) as $path) {
	if (!is_file($path) || !is_readable($path) || !is_writable($path)) {
		fail('Required config file is missing or not accessible: ' . $path);
	}
}

require_once $configPath;
require_once $bearerPath;
require_once $repositoriesPath;

if (!defined('DATAPATH') || !is_dir(DATAPATH) || !is_writable(DATAPATH)) {
	fail('DATAPATH from conf/config.inc.php must exist and be writable.');
}

if (!isset($bearer) || !is_array($bearer)
	|| !isset($repositoryconfig) || !is_array($repositoryconfig)) {
	fail('Repository config files do not contain the expected arrays.');
}

if (array_key_exists($repository, $bearer) || array_key_exists($repository, $repositoryconfig)) {
	fail('Repository already exists: ' . $repository);
}

if (!extension_loaded('bcmath')) {
	fail('The PHP bcmath extension is required.');
}

$unitSizes = array(
	'B' => '1',
	'KB' => '1024',
	'MB' => '1048576',
	'GB' => '1073741824',
	'TB' => '1099511627776'
);
$unit = strtoupper($matches[2]);
$maxsizeBytes = bcmul($matches[1], $unitSizes[$unit], 0);

$dataPath = rtrim(DATAPATH, DIRECTORY_SEPARATOR);
$repositoryPath = $dataPath . DIRECTORY_SEPARATOR . $repository;
if (file_exists($repositoryPath)) {
	fail('Repository path already exists: ' . $repositoryPath);
}

$token = bin2hex(random_bytes(32));
$hash = password_hash($token, PASSWORD_BCRYPT);
if ($hash === false) {
	fail('Could not create bearer token hash.');
}

$directories = array(
	$repositoryPath,
	$repositoryPath . DIRECTORY_SEPARATOR . 'tmp',
	$repositoryPath . DIRECTORY_SEPARATOR . 'log'
);

foreach ($directories as $directory) {
	if (!mkdir($directory, 0770) || !chown($directory, 'www-data')
		|| !chgrp($directory, 'www-data') || !chmod($directory, 0770)) {
		fail('Could not create or set permissions on ' . $directory);
	}
}

$logPath = $repositoryPath . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'log.txt';
if (!touch($logPath) || !chown($logPath, 'www-data')
	|| !chgrp($logPath, 'www-data') || !chmod($logPath, 0660)) {
	fail('Could not create or set permissions on ' . $logPath);
}

$bearer[$repository] = $hash;
$repositoryconfig[$repository] = array('maxsize' => $maxsizeBytes);

try {
	writeConfig($bearerPath, 'bearer', $bearer, 'Repository - Bearer Token');
	writeConfig($repositoriesPath, 'repositoryconfig', $repositoryconfig, 'Repository - Configuration');
} catch (RuntimeException $error) {
	fail($error->getMessage());
}

printf("Repository created: %s\n", $repository);
printf("Repository path: %s\n", $repositoryPath);
printf("Maximum size: %s bytes\n", $maxsizeBytes);
printf("Bearer token (store securely): %s\n", $token);