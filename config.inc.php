<?php

// Flyspray uses ADODB for database access.  You will need to install 
// it somewhere on your server for Flyspray to function.  It can be installed
// inside the Flyspray directory if you wish. The next line needs to be the
// correct path to your adodb.inc.php file.

include_once("/usr/share/adodb/adodb.inc.php");


$dbtype = 'mysql';  // must be a valid adodb db type

$dbhost = 'localhost';  // Name or IP of Database Host
$dbname = 'flyspray';  // The name of the database.
$dbuser = 'USERNAME';   // The user to access the database.
$dbpass = 'PASSWORD';   // The password to go with that username above.


// This is the key that your cookies are encrypted against.
// It is recommended that you change this immediately after installation to make
// it harder for people to hack their cookies and try to take over someone else's 
// account.  Changing it will log out all users, but there are no other consequences. 

$cookiesalt = '4t6dcHiefIkeYcn48B';  

?>
