<?php
/*
 * This file is meant to add every hack that is needed to fix default PHP
 * behaviours, and to ensure that our PHP env will be able to run flyspray
 * correctly.
 *
 */

// as the very first step, we live is register_globals Off world forever..
//This code was written By Stefan Esser from the hardened PHP project (sesser@php.net)
// it's now part of the PHP manual

function unregister_GLOBALS()
{
   if (!ini_get('register_globals')) {
       return;
   }

   // Might want to change this perhaps to a nicer error
   if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
       die('GLOBALS overwrite attempt detected');
   }

   // Variables that shouldn't be unset
   $noUnset = array('GLOBALS',  '_GET',
                     '_POST',    '_COOKIE',
                     '_REQUEST', '_SERVER',
                     '_ENV',    '_FILES');

   $input = array_merge($_GET,    $_POST,
                         $_COOKIE, $_SERVER,
                         $_ENV,    $_FILES,
                         isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());

   foreach ($input as $k => $v) {
       if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
           unset($GLOBALS[$k]);
       }
   }
}

unregister_GLOBALS();

//then we procede with our checks and stuff


// Check PHP Version (Must Be at least 4.3)
// For 0.9.9, this should redirect to the error page
if (PHP_VERSION  < '4.3.0') {
    die('Your version of PHP is not compatible with Flyspray, '
            .'please upgrade to at least PHP version 4.3.0');
}

// This to stop PHP being retarded and using the '&' char for session id delimiters
ini_set('arg_separator.output','&amp;');

// MySQLi driver is _useless_ if zend.ze1_compatibility_mode is enabled
// in fact you should never use this setting,the damn thing does not work.

if(PHP_VERSION >= '5.0') {
	ini_set('zend.ze1_compatibility_mode',0);
}

// This is for retarded Windows servers not having REQUEST_URI
if (!isset($_SERVER['REQUEST_URI']))
{
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    }
    else {
        $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
    }

    if ($_SERVER['QUERY_STRING']) {
        $_SERVER['REQUEST_URI'] .=  '?'.$_SERVER['QUERY_STRING'];
    }
}
// always live in a world without magic quotes
function fix_gpc_magic(&$item, $key) {
    if (is_array($item)) {
        array_walk($item, 'fix_gpc_magic');
    } else {
        $item = stripslashes($item);
    }
}

if (ini_get("magic_quotes_gpc")) {
    array_walk($_GET, 'fix_gpc_magic');
    array_walk($_POST, 'fix_gpc_magic');
    array_walk($_COOKIE, 'fix_gpc_magic');
    array_walk($_REQUEST, 'fix_gpc_magic');
}
?>
