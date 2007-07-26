<?php
/*
    This script renders a given text for preview purposes
*/

define('IN_FS', true);

header('Content-type: text/html; charset=utf-8');

$webdir = dirname(dirname(dirname(htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8'))));

require_once('../../header.php');

$page = new FSTpl();
echo $page->text->render(Post::val('text'), false, null, null, null, Post::val('plugins'))

?>
