<?php
$dev_data = array(
	'id' => '-1',
	'firstname' => 'Aryan',
	'lastname' => 'Jain',
	'username' => 'Aryan_jain',
	'password' => '',
	'last_login' => '',
	'date_updated' => '',
	'date_added' => ''
);

function env_or_default($key, $default = '') {
	$value = getenv($key);
	return ($value !== false && $value !== '') ? $value : $default;
}

if(!defined('base_url')) define('base_url', env_or_default('CMS_BASE_URL', "https://sbpanchal.com/cms/"));
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('dev_data')) define('dev_data',$dev_data);

if(!defined('DB_SERVER')) define('DB_SERVER', env_or_default('CMS_DB_SERVER', "localhost"));
if(!defined('DB_USERNAME')) define('DB_USERNAME', env_or_default('CMS_DB_USERNAME'));
if(!defined('DB_PASSWORD')) define('DB_PASSWORD', env_or_default('CMS_DB_PASSWORD'));
if(!defined('DB_NAME')) define('DB_NAME', env_or_default('CMS_DB_NAME'));

if(DB_USERNAME === '' || DB_PASSWORD === '' || DB_NAME === ''){
	http_response_code(500);
	exit('Server configuration error: missing database environment variables.');
}
?>