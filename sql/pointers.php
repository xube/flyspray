<?php

include('../header.php');

?>
<html>
<head>
<title>Flyspray upgrade script</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf8">
<link href="themes/Bluey/theme.css" rel="stylesheet" type="text/css">
</head>

<body>
<h2 class="subheading">Flyspray Upgrade - Create user <-> group pointers</h2>
<?php
$page = $_GET['page'];
if (!$page  OR $page == '1') {


  echo "<table class=\"admin\"><tr><td class=\"admintext\">This script will create the appropriate pointers in your MYSQL database so that your existing users and groups function correctly in the development version.";
  echo " You should ensure that your database settings are correct in <b>flyspray.conf.php</b>, and that you have inserted the development SQL file into your Flyspray database before continuing.  This script will DELETE all previous entries in the flyspray_users_in_groups table!";
  echo "<br><br><a href=\"" . $_SERVER['PHP_SELF'] . "?page=2\">Create pointers now!</a></td></tr></table>";


} elseif ($page == '2') {

  $delete = $fs->dbQuery("DELETE FROM flyspray_users_in_groups");
  
  $user_query = $fs->dbQuery("SELECT * FROM flyspray_users ORDER BY user_id ASC");
  while ($row = $fs->dbFetchArray($user_query)) {
    $insert = $fs->dbQuery("INSERT INTO flyspray_users_in_groups
                            (user_id, group_id)
                            VALUES(?, ?)",
                            array($row['user_id'], $row['group_in']));
  };
  
  
  echo "<table class=\"admin\"><tr><td class=\"admintext\">Your users and group pointers have been created. It is recommended that you don't run this script again.  If you add users to the development version, then run this script again, it will NOT create the entries for the new users.  Flyspray will be BROKEN, so DON'T RUN IT AGAIN!<br><br>";
  echo "<a href=\"../\">Take me to Flyspray development version now!</a></td></tr><table>";

};