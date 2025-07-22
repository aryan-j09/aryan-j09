<?php
$dev_data = array('id'=>'-1','firstname'=>'Aryan','lastname'=>'Jain','username'=>'Aryan_jain','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');
if(!defined('base_url')) define('base_url',"https://sbpanchal.com/cms/");
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('dev_data')) define('dev_data',$dev_data);
if(!defined('DB_SERVER')) define('DB_SERVER',"localhost");
if(!defined('DB_USERNAME')) define('DB_USERNAME',"sbpanchaldev");
if(!defined('DB_PASSWORD')) define('DB_PASSWORD',"SBpanchal@devak2");
if(!defined('DB_NAME')) define('DB_NAME',"Db_SB_Panchal_2022");
?>