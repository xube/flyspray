<title>Lang diff</title>
<pre>
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
        
        echo 'The following translations (keys) are missing in the translation:' . "\n\n";
        foreach ($language as $key => $val) {
            if (!isset($translation[$key])) {
                echo $key . "\n";
            }
        }
    } else {
        die('Translation does not exist.');
    }
?>
</pre>