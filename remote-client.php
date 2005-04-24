<?php
/*
   ---------------------------------------------------------------
   | This is the test script for remote.php, the xml-rpc server. |
   | Most functions require authentication with a  Flyspray      |
   | username and password.                                      |
   ---------------------------------------------------------------
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
   <head>
      <title>Test page for the Flyspray XML-RPC interface</title>
   </head>

   <body>
      <h1>Flyspray XML-RPC interface</h1>

<?php
// If nothing was posted, use this first section
if (!isset($_REQUEST['username']))
{
?>
   <div id="intro" style="position:absolute;left:12px;top:80px;width:200px;">
      This is a test page for the Flyspray bug-tracking system XML-RPC interface.  XML-RPC allows
      you to request information from a remote Flyspray installation so that you can include it in your
      local application or website.  You are welcome to request information from the official Flyspray BTS,
      but you are free to point it to another Flyspray installation with the latest revision of remote.php
   </div>

   <div id="taskdetails" style="position:absolute;top:80px;left:250px;border:1px solid black;">
      <form action="remote-client.php" method="get">
      <table>
         <caption>Request task details</caption>
         <tr>
            <td>Base URL to Flyspray</td>
            <td><input name="url" type="text" size="30" value="http://flyspray.rocks.cc/bts/" /></td>
         </tr>
         <tr>
            <td>Your User Name:</td>
            <td><input name="username" type="text" size="30" /></td>
         </tr>
         <tr>
            <td>Your Password:</td>
            <td><input name="password" type="password" size="30" /></td>
         </tr>
         <tr>
            <td>Task ID:</td>
            <td><input name="taskid" type="text" size="4" /></td>
         </tr>
         <tr>
            <td>Debug?</td>
            <td><input name="debug" type="checkbox" value="1" /></td>
         </tr>
         <tr>
            <td colspan="2" align="center"><input type="submit" value="Send Request" /></td>
         </tr>
      </table>
      </form>
   </div>

   <div id="userdetails" style="position:absolute;top:80px;left:600px;border:1px solid black;">
      <form action="remote-client.php" method="get">
      <table>
         <caption>Request user details</caption>
         <tr>
            <td>Base URL to Flyspray</td>
            <td><input name="url" type="text" size="30" value="http://flyspray.rocks.cc/bts/" /></td>
         </tr>
         <tr>
            <td>Your User Name:</td>
            <td><input name="username" type="text" size="30" /></td>
         </tr>
         <tr>
            <td>Your Password:</td>
            <td><input name="password" type="password" size="30" /></td>
         </tr>
         <tr>
            <td>User ID you want info about:</td>
            <td><input name="userid" type="text" size="4" /></td>
         </tr>
         <tr>
            <td>Debug?</td>
            <td><input name="debug" type="checkbox" value="1" /></td>
         </tr>
         <tr>
            <td colspan="2" align="center"><input type="submit" value="Send Request" /></td>
         </tr>
      </table>
      </form>
   </div>





<?
// If something was posted, use this second section
} else
{
   // Include a copy of the xml-rpc library. This can reside anywhere.
   // We're just calling the same copy as the server for convenience.
   require('includes/IXR_Library.inc.php');

   // Define the server. Enter the URL of your flyspray installation, with 'remote.php' at the end.
   $client = new IXR_Client($_REQUEST['url'] . '/remote.php');

   // Enable debug for testing
   if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1')
      $client->debug = true;


   if (isset($_REQUEST['taskid']))
   {
      // Request a task details
      // variables: username, password, task_id
      if(!$client->query('fs.getTask', $_REQUEST['username'], $_REQUEST['password'], $_REQUEST['taskid']))
      {
         die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
      }
   }


   if (isset($_REQUEST['userid']))
   {
      // Request a task details
      // variables: username, password, task_id
      if(!$client->query('fs.getUser', $_REQUEST['username'], $_REQUEST['password'], $_REQUEST['userid']))
      {
         die('Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage());
      }
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

   echo '</pre>';

   echo '<br /><br /><a href="remote-client.php">Do it again!</a>';

// End of script
}
?>