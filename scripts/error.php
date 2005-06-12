<?php
/*
   This is the generic 'error' script.  You are usually redirected here if
   you requested a page or task that doesn't exist, or if you entered an
   illegal URL and it was caught by a regexp.
*/

$fs->get_language_pack($lang, 'main');

echo '<div id="error">';
echo $language['errorpage'];
echo '</div>';

?>