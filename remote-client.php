<?php
/*
   ---------------------------------------------------------------
   | This is the test script for remote.php, the xml-rpc server. |
   | server.  Most functions require authentication with a       |
   | Flyspray username and password.                             |
   ---------------------------------------------------------------
*/

// Include a copy of the xml-rpc library. This can reside anywhere.
// We're just calling the same copy as the server for convenience.
require('includes/IXR_Library.inc.php');

// Define the server. Enter the URL of your flyspray installation, with 'remote.php' at the end.
$client = new IXR_Client('http://localhost/~tony/flyspray-dev/remote.php');

// Enable debug for testing
//$client->debug = true;

// Request a task details
// variables: username, password, task_id
if(!$client->query('fs.getTask', 'super', 'super', '2'))
{
   die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
}


// Define the results from the server
$response = $client->getResponse();

// Display the results
echo '<pre>';

if (is_array($response))
{
   foreach($response as $key => $val)
   {
      echo $key . ' => ' . $val . '<br />';
   }
} else
{
   print_r($response);
}

echo '</pre>'

?>