<?php
/*
    This script is the AJAX callback that performs a search
    for users, and returns them in an ordered list.
*/

define('IN_FS', true);

header('Content-type: text/html; charset=utf-8');

$webdir = dirname(dirname(dirname(Filters::noXSS($_SERVER['PHP_SELF']))));
require_once('../../header.php');

echo TextFormatter::render(Post::val('text'));

?>
