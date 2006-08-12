<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /
<title>Lang diff</title>
</head>
<body>
<?php
  /*
  Usage: Open this file like .../.langdiff?lang=de in your browser.
         "de" represents your language code.
  */
    $lang = ( isset($_GET['lang']) ? $_GET['lang'] : 'en');
    if (!ctype_alnum($lang)) {
        die('Invalid language name.');
    }
    
    require_once('en.php');
    
    $translation = "$lang.php";
    if ($lang != 'en' && file_exists($translation)) {
        include_once($translation);
        
        echo '<h1>The following translations (keys) are missing in the translation:</h1>';
	echo '<table>';
	$i = 0;
        foreach ($language as $key => $val) {
            if (!isset($translation[$key])) {
                echo '<tr><th>',$key,'</th><td>',$val,'</td></tr>',"\n";
		$i++;
            }
	    
        }
	echo '</table>';
	if ( $i > 0 )
		echo '<p>',$i,' out of ',sizeof($language),' keys to translate.</p>';
	echo '<h1>The following translations (keys) should be deleted in the translation:</h1>';
	echo '<table>';
	foreach ($translation as $key => $val) {
		if ( !isset($language[$key])) {
			echo '<tr><th>',$key,'</th><td>',$val,'</td></tr>',"\n";
		}
	}
	echo '</table>';
    } else {
        die('Translation does not exist.');
    }
?>
</pre>
</body>
</html>
