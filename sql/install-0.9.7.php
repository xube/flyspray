<?php

if (isset($_GET['p'])) {
   $page = $_GET['p'];
} else {
   $page = '1';
};

session_start();
include('../includes/functions.inc.php');
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Flyspray setup</title>
  <link rel="icon" href="../favicon.ico" type="image/png" />
  <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
  <link href="../themes/Bluey/theme.css" rel="stylesheet" type="text/css" />
</head>
<body>

<h1 id="title"></h1>

<br />

<div id="content" style="min-height: 50%;">

<?php

echo '<h3>Flyspray 0.9.7 Setup</h3>';

//////////////////////////////
// Page one, the intro page //
//////////////////////////////
if ($page == '1') {

   if (file_exists('../flyspray.conf.php')) {
      die('Setup has already been completed.  If you really want to run it again, remove flyspray.conf.php');
   };

   echo 'Flyspray does not appear to be set up on this server.  Click the button below to start the configuration process.';
   echo '<br /><br />';
   echo 'You might like to read the <a href="http://flyspray.rocks.cc/?p=Installation" target="_blank">installation instructions</a> to help you through Setup.';
   echo '<br /><br />';
   echo "\n";
   echo '<form action="install-0.9.7.php" method="get">';
   echo "\n";
   echo '<input type="hidden" name="p" value="2" />';
   echo "\n";
   echo '<input class="adminbutton" type="submit" value="Continue to next page" />';
   echo "\n";
   echo '</form>';


//////////////////////////////////////////////////////////
// Page two, where the user enters their config details //
//////////////////////////////////////////////////////////
} elseif ($page == '2') {

   /*if (file_exists('../flyspray.conf.php')) {
      die('Setup has already been completed.  If you really want to run it again, remove flyspray.conf.php');
   };*/

?>

Adjust the settings below to suit your system's setup.  Note that you must have already created
the database that you wish to use for Flyspray, and it must NOT have a Flyspray structure already
inside it.  If you do not follow these instructions, setup will fail.

<br /><br />

<?php
// This line gets the operating system so that we know which way to put slashes in the path
strstr( PHP_OS, "WIN") ? $slash = "\\" : $slash = "/";
$basedir = realpath('../') . $slash;
$adodbpath = $basedir . 'adodb' . $slash . 'adodb.inc.php';

if (!isset($_SESSION['basedir'])) {
   $_SESSION['basedir'] = $basedir;
   $_SESSION['adodbpath'] = $adodbpath;
   $_SESSION['dbname'] = 'flyspray';
   $_SESSION['dbhost'] = 'localhost';
   $_SESSION['dbuser'] = 'root';
};
?>

<form action="install-0.9.7.php?p=3" method="post">

<fieldset>
<legend>General</legend>
<table cellpadding="5">
   <tr>
      <td>Filesystem path to the main Flyspray directory:<br />
      <i style="font-size: smaller;">The trailing slash is required</i>
      </td>
      <td>
      <input type="text" size="50" maxlength="200" name="basedir" value="<?php echo $_SESSION['basedir'];?>" /></td>
   </tr>
   <tr>
      <td>
      Location of the adodb.inc.php file:<br />
      <i style="font-size: smaller;">Include 'adodb.inc.php' in this field, as shown</i>
      </td>
      <td><input type="text" size="50" maxlength="200" name="adodbpath" value="<?php echo $_SESSION['adodbpath'];?>" /></td>
   </tr>
</table>
</fieldset>

<br />

<fieldset>
<legend>Database</legend>
<table cellpadding="7">
   <tr>
      <td>
      Database server type:<br />
      <i style="font-size: smaller;">Other database types may work, but are unsupported</i>
      </td>
      <td>
         <select name="dbtype">
            <option value="mysql" <?php if ($_SESSION['dbtype'] == 'mysql') { echo 'SELECTED';};?>>MySQL</option>
            <option value="pgsql" <?php if ($_SESSION['dbtype'] == 'pgsql') { echo 'SELECTED';};?>>PostgreSQL</option>
         </select>
      </td>
   </tr>
   <tr>
      <td>Database server hostname (or domain name):</td>
      <td><input type="text" size="20" maxlength="200" name="dbhost" value="<?php echo $_SESSION['dbhost'];?>" /></td>
   </tr>
   <tr>
      <td>Database name:<br />
      <i style="font-size: smaller;">This database must already exist.</i>
      </td>
      <td>
      <input type="text" size="20" maxlength="200" name="dbname" value="<?php echo $_SESSION['dbname'];?>" />
      </td>
   </tr>
   <tr>
      <td>Database server username:<br />
      <i style="font-size: smaller;">This database user must already exist.</i>
      </td>
      <td>
      <input type="text" size="20" maxlength="200" name="dbuser" value="<?php echo $_SESSION['dbuser'];?>" />
      </td>
   </tr>
   <tr>
      <td>Database server password:</td>
      <td><input type="password" size="20" maxlength="200" name="dbpass" value="<?php echo $_SESSION['dbpass'];?>" /></td>
   </tr>
</table>

<br /><br />

<table cellpadding="5">
   <tr>
      <td>
      <input class="adminbutton" type="submit" value="Continue to next page" />
      </td>
   </tr>
</table>
</form>
</fieldset>



<?php
/////////////////////////////////////////////////////
// Page three, check that submitted values are ok. //
/////////////////////////////////////////////////////
} elseif ($page == '3') {

   /*if (file_exists('../flyspray.conf.php')) {
      die('Setup has already been completed.  If you really want to run it again, remove flyspray.conf.php');
   };*/

   $_SESSION['basedir'] = stripslashes($_POST['basedir']);
   $_SESSION['adodbpath'] = stripslashes($_POST['adodbpath']);
   $_SESSION['dbtype'] = $_POST['dbtype'];
   $_SESSION['dbname'] = $_POST['dbname'];
   $_SESSION['dbhost'] = $_POST['dbhost'];
   $_SESSION['dbuser'] = $_POST['dbuser'];
   $_SESSION['dbpass'] = $_POST['dbpass'];

   if ($_POST['basedir'] != ''
      && $_POST['adodbpath'] != ''
      && $_POST['dbhost'] != ''
      && $_POST['dbname'] != ''
      && $_POST['dbuser'] != ''
      && $_POST['dbpass'] != ''
      ) {

         // Now, check for the correct path to the adodb.inc.php file
         if (!file_exists($_SESSION['adodbpath']))
         {
            die('The path to adodb.inc.php wasn\'t set correctly.  <a href="?p=2">Go back and fix it up.</a>');

         }

// Save flyspray.conf.php

         // If the adodbpath is correct, continue to saving flyspray.conf.php
         $filename = '../flyspray.conf.php';

         // Create a random cookie salt
         $cookiesalt = substr(md5(microtime()), 0, 2);

         // Copy the skeleton config file to the Flyspray directory
         if (!@copy("flyspray.conf.skel", "../flyspray.conf.php")) {
            die ('Cannot create flyspray.conf.php in the Flyspray directory.  Perhaps we do not have write permission?  Check the directory permissions, then  <a href="?p=2">go back and try again</a>.');
         };

         $somecontent = '

[general]
basedir = "' . $_SESSION['basedir'] . '"      ; Location of your Flyspray installation
cookiesalt = "' . $cookiesalt . '"            ; Randomisation value for cookie encoding
adodbpath = "' . $_SESSION['adodbpath']. '"   ; Path to the main ADODB include file
output_buffering = "on"                       ; Available options: "on" or "gzip"
passwdcrypt = "md5"                           ; Available options: "crypt", "md5", "sha1"

[database]
dbtype = "' . $_SESSION['dbtype'] . '"        ; Type of database ("mysql" or "pgsql" are currently supported)
dbhost = "' . $_SESSION['dbhost'] . '"        ; Name or IP of your database server
dbname = "' . $_SESSION['dbname'] . '"        ; The name of the database
dbprefix = "flyspray"                           ; The prefix to the Flyspray tables
dbuser = "' . $_SESSION['dbuser'] . '"        ; The user to access the database
dbpass = "' . $_SESSION['dbpass'] . '"        ; The password to go with that username above
';

// Let's make sure the file exists and is writable first.
if (is_writable($filename)) {

   // In our example we're opening $filename in append mode.
   // The file pointer is at the bottom of the file hence
   // that's where $somecontent will go when we fwrite() it.
   if (!$handle = fopen($filename, 'a')) {
         echo "Cannot open file ($filename)";
         exit;
   };

   // Write $somecontent to our opened file.
   if (fwrite($handle, $somecontent) === FALSE) {
       echo "Cannot write to file ($filename)";
       exit;
   };

   fclose($handle);
  // End of saving flyspray.conf.php


   // Tell the user what just happened

   echo 'Your config settings were successfully saved to flyspray.conf.php';
   echo '<br /><br />';
   echo 'Next, we are going to try setting up your database using the settings you just provided.  ';
   echo 'The next page may be a little slow to load, because it has a lot of database work to do.';
   echo '<br /><br />';
   echo '<b>REMEMBER:</b> Your database must already exist, and NOT already contain a Flyspray structure.';
   echo '<br /><br />';
   echo "\n";
   echo '<form action="install-0.9.7.php" method="get">';
   echo "\n";
   echo '<input type="hidden" name="p" value="4" />';
   echo "\n";
   echo '<input class="adminbutton" type="submit" value="Continue to next page" />';
   echo "\n";
   echo '</form>';

// If saving the config file failed
} else {
   echo "Could not save settings to flyspray.conf.php.  Perhaps we do not have write permission?";
};

   // If the user hasn't filled in all the fields
   } else {

      echo 'You need to fill in all the fields.  <a href="install-0.9.7.php?p=2">Go back and finish it.</a>';

   };


/////////////////////////////////////////////////////
// Page Four, where we insert the database schema. //
/////////////////////////////////////////////////////
} elseif ($page == '4') {

   if (!isset($_SESSION['basedir'])) {
      Header("Location: install-0.9.7.php?p=1");
   };


// Activate adodb
include_once ($_SESSION['adodbpath']);

// Define our functions class
$fs = new Flyspray;

// Open a connection to the database
$res = @$fs->dbOpen($_SESSION['dbhost'], $_SESSION['dbuser'], $_SESSION['dbpass'], $_SESSION['dbname'], $_SESSION['dbtype']);
if (!$res) {
   die('Flyspray was unable to connect to the database.<br /><br /><b>The reason was:</b> '.$fs->dblink->ErrorMsg().'.<br /><br /> Go back and <a href="install-0.9.7.php?p=2">check your settings!</a>');
}

// See if the Flyspray db schema already exists.  exists = bad.
//$check_db = $fs->dbQuery("SELECT * FROM flyspray_tasks");
//if ($fs->dbCountRows($check_db)) {
//   die('The Flyspray database structure already seems to exist.  We are not going to create it again!<br /><br />To restart this setup, first remove flyspray.conf.php from the Flyspray directory.');
//};

// Retrieve the database schema into a string
$sql_file = file_get_contents('flyspray-0.9.7.' . $_SESSION['dbtype']);

// Separate each query
$sql = explode(';', $sql_file);

// Cycle through the queries and insert them into the database
while (list($key, $val) = each($sql)) {
   $insert = $fs->dbQuery($val);
};

// Add code to detect the URL to Flyspray, and put it in the $base_url variable
// Then update the database with it
//$update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = $base_url WHERE pref_name = 'base_url'");

echo 'The Flyspray configuration process is complete.  The Flyspray developers hope that you have many hours ';
echo 'of increased productivity though the use of this software.  If you find Flyspray useful, please consider ';
echo 'returning to the Flyspray website and <a href="http://flyspray.rocks.cc/?p=Download">making a donation</a>.';
echo '<br /><br />';
echo 'Click "Finish Setup" to log into Flyspray as the default "super" user, and create yourself a personal admin account.';
echo '<br /><br />';
echo 'Remember to read the <a href="http://flyspray.rocks.cc/?p=First_Login" target=_blank">First Login documentation</a> while you do that.';
echo '<br /><br />';

   echo "\n";
   echo '<form action="../index.php?do=authenticate" method="post">';
   echo "\n";
   echo '<input type="hidden" name="prev_page" value="index.php?do=newuser" />';
   echo "\n";
   echo '<input type="hidden" name="username" value="super" />';
   echo "\n";
   echo '<input type="hidden" name="password" value="super" />';
   echo "\n";
   echo '<input class="adminbutton" type="submit" value="Finish Setup" />';
   echo "\n";
   echo '</form>';


// End of pages
};
?>

</div>

</body>

</html>
