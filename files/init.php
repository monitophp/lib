<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET,PATCH,POST,PUT,DELETE,OPTIONS');
/**
 * This file can be edited to set a default timezone and other settings
 */
date_default_timezone_set('America/Bahia');
\MonitoLib\App::setDebug(2);
