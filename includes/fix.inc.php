<?php
/*
 * This file is meant to add every hack that is needed to fix default PHP
 * behaviours, and to ensure that our PHP env will be able to run flyspray
 * correctly.
 *
 */

// Check PHP Version (Must Be at least 4.3)
// For 0.9.9, this should redirect to the error page
if (PHP_VERSION  < '4.3.0') {
    die('Your version of PHP is not compatible with Flyspray, '
            .'please upgrade to at least PHP version 4.3.0');
}

// This to stop PHP being retarded and using the '&' char for session id delimiters
ini_set('arg_separator.output','&amp;');

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

?>
