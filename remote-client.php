<?php
/*
   ---------------------------------------------------------------
   | This is the test script for remote.php, the xml-rpc server. |
   | Most functions require authentication with a  Flyspray      |
   | username and password.                                      |
   ---------------------------------------------------------------
 
   Changes:
   4th August 2005: Angus Hardie Angus@malcolmhardie.com for xmlrpc library instead of ixr
*/

// default server (for easier testing)

$server = "http://flyspray.rocks.cc/bts/remote.php";

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
      but you can also point it to another Flyspray installation with the latest revision of remote.php
      <br /><br />
      Now using <a href="http://phpxmlrpc.sourceforge.net/">phpxmlrpc class</a>      
   </div>

   <div id="taskdetails" style="position:absolute;top:80px;left:250px;border:1px solid black;">
      <form action="remote-client.php" method="get">
      <table>
         <caption>Request information</caption>
         <tr>
            <td>Base URL to Flyspray</td>
            <td><input name="url" type="text" size="30" value="<? echo $server;?>" /></td>
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
            <td colspan="2"><hr /></td>
         </tr>
         <tr>
            <td>Action:</td>
            <td>
               <select name="action">
                  <option value="getTask">Get Task Information</option>
                  <option value="getUser">Get User Information</option>
               </select>
            </td>
         </tr>
         <tr>
            <td>Task ID:</td>
            <td><input name="taskid" type="text" size="4" value="1"/></td>
         </tr>
         <tr>
            <td>User ID:</td>
            <td><input name="userid" type="text" size="4" value="1"/></td>
         </tr>
         <tr>
            <td colspan="2"><hr /></td>
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
   // (switched to xmlrpc)
   require_once 'includes/xmlrpc/xmlrpc.inc';
   
   //extract parts of the submitted url
   $urlParts = parse_url($_REQUEST['url']);

   // Define the server. Enter the URL of your flyspray installation, with 'remote.php' at the end.
   $client = new xmlrpc_client($urlParts['path'],$urlParts['host']);

   // Enable debug for testing
   if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1')
      $client->debug = true;
   
   $action = $_REQUEST['action'];
   $msg = "";
   
   if (!isset($action)) {
      die ("no action specified");
   }

   if ("getTask" == $action) {
      // Request a task details
      // variables: username, password, task_id
      $params = array(new xmlrpcval($_REQUEST['username']),new xmlrpcval($_REQUEST['password']),new xmlrpcval($_REQUEST['taskid']));
      $msg = new xmlrpcmsg('fs.getTask', $params);
   }

   if ("getUser" == $action) {
      $params = array(new xmlrpcval($_REQUEST['username']),new xmlrpcval($_REQUEST['password']),new xmlrpcval($_REQUEST['userid']));
      $msg = new xmlrpcmsg('fs.getUser', $params);
      
   }

   // Define the results from the server
   $response = $client->send($msg);

   // Display the results
   if ($response->faultCode() != 0) {
      
      echo('XML_RPC Error ('.$response->faultCode().') <br /> '.$response->faultString());
      echo '<br /><br /><a href="remote-client.php">Try again</a>';
      die();
   }
   
   
   // Display the results
   echo '<pre>';
   
   $response = php_xmlrpc_decode($response->value());
   
   print_r($response);
   
   echo '</pre>';
   
   echo '<br /><br /><a href="remote-client.php">Do it again!</a>';

// End of script
}
?>