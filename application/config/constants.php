<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0755);
 

define('FOPEN_READ', 'rb');
define('FOPEN_READ_WRITE', 'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); 
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b');  
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_READ_WRITE_CREATE', 'a+b');
define('FOPEN_WRITE_CREATE_STRICT', 'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');
 
define('SHOW_DEBUG_BACKTRACE', TRUE);
 

define('EXIT_SUCCESS', 0);  
define('EXIT_ERROR', 1);  
define('EXIT_CONFIG', 3);  
define('EXIT_UNKNOWN_FILE', 4); 
define('EXIT_UNKNOWN_CLASS', 5);  
define('EXIT_UNKNOWN_METHOD', 6);  
define('EXIT_USER_INPUT', 7); 
define('EXIT_DATABASE', 8);  
define('EXIT__AUTO_MIN', 9); 
define('EXIT__AUTO_MAX', 125); 
