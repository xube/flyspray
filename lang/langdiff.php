#!/usr/bin/php -q
<?php
  /*
  Usage:
      - chdir to language directory
      - langdiff.php en <yourlanguage>
  */
  list($lang1, $lang2) = array($argv[1], $argv[2]);
  if (empty($lang1) or empty($lang2)) {
    die("Usage: $argv[0] reference_dir language_dir");
  }
    
  reportMissingFiles($lang1, $lang2);
  reportMissingVariables($lang1, $lang2);

  // ============================================================
 
  /** report variables missing in translation module
  @param  string $lang1	    reference directory
  @param  string $lang2	    translation directory
  $param  string $module    compared module filename 
			    (without directory path, with .php extension)
  */
  function reportMissingVariablesInModule($lang1, $lang2, $module) {
    if (!file_exists("$lang1/$module") or 
    !file_exists("$lang2/$module")) return;
    
    $before = get_defined_vars();
    require_once("$lang1/$module");
    $after_en = get_defined_vars();
    $new_var = array_keys(array_diff($after_en, $before));
    $new_var_name = $new_var[1];
    $new_var[$lang1] = $$new_var_name;
    require_once("$lang2/$module");
    $new_var[$lang2] = $$new_var_name;

    $arrdiff = array_diff(
      array_keys($new_var[$lang1]),
      array_keys($new_var[$lang2]));
    foreach($arrdiff as $varname) {
      $diff[] = $varname;
    }
    if (@count($diff)) {
      printHeader("Variables missing in module $module");
      foreach($diff as $varname) {
	printLine($varname);
      }
    }
  }

  /** report variables missing in translation
  @param string $lang1  reference directory
  @param string $lang2  translation directory
  */
  function reportMissingVariables($lang1, $lang2) {
    foreach (getDirContents($lang2) as $module) {
      reportMissingVariablesInModule($lang1, $lang2, $module);
    }
  }
  
  /** report files present in first directory, missing from the second
  @param string $lang1  reference directory
  @param string $lang2  translation directory
  */
  function reportMissingFiles($lang1, $lang2) {
    $reference	= getDirContents($lang1);
    $translation= getDirContents($lang2);
    sort($reference);
    sort($translation);
    $diff = array_diff($reference, $translation);
    if (count($diff) > 0) {
      printHeader("Missing files");
      foreach ($diff as $filename) {
	printLine($filename);
      }
    }
  }

  /** get list of files in the directory
  @param string $directory  directory location
  */
  function getDirContents($directory) {
    $files = array();
    if (is_dir($directory)) {
      $dir = opendir($directory);
      while ($filename = readdir($dir)) {
	$fullfilename = "$directory/$filename";
	if (is_file($fullfilename)) {
	  $files[] = $filename;
	}
      }
      closedir($dir);
    }
    return $files;
  }
  
  function printHeader($text)	{ print "==== $text ====\n"; }
  function printLine($text)	{ print "     $text\n"; }

?>
