<?php
/*
   -----------------------------------------
   | This script is a series of functions  |
   | for sending information to remote     |
   | clients making requests using xml-rpc |
   -----------------------------------------

   List of errors:

   -1    Login failed.
   -2    No permission
   -3    Task does not exist

   Changes:
   4th August 2005: Angus Hardie Angus@malcolmhardie.com for xmlrpc library instead of ixr
*/

// Get the main headerfile.  It calls other important files
require('header.php');

// use xmlrpc library (library + server library)
require_once 'includes/xmlrpc/xmlrpc.inc';
require_once 'includes/xmlrpc/xmlrpcs.inc';


//////////////////////////////////////////////////
// Start of function to return a task's details //
//////////////////////////////////////////////////
function getTask($args)
{
   global $fs;

   $username   = php_xmlrpc_decode($args->getParam(0));
   $password   = php_xmlrpc_decode($args->getParam(1));
   $task_id    = php_xmlrpc_decode($args->getParam(2));

   // First, check the user has a valid, active login.  If not, then stop.
   if (!$fs->checkLogin($username, $password))
      return new xmlrpcresp (0, -1, 'Your credentials were incorrect, or your account has been disabled.');

   // Get the user_id
   $user_id = $fs->checkLogin($username, $password);

   // Get the task details
   $task_details = $fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $permissions = $fs->getPermissions($user_id, $task_details['attached_to_project']);

   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
      return new xmlrpcresp (0,-3, 'The requested task does not exist.');

   // Compare permissions to view this task
   if ($task_details['project_is_active'] == '1'
         && ($task_details['others_view'] == '1' OR $permissions['view_tasks'] == '1')
         && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user_id)
               OR $permissions['manage_project'] == '1' OR $task_details['mark_private'] != '1'))
      $can_view = true;


   if (!$can_view)
      return new xmlrpcresp (0,-2, 'You do not have permission to perform this function.');

   $result = array (
                  'task_id'              =>    $task_details['task_id'],
                  'attached_to_project'  =>    $task_details['project_title'],
                  'task_type'            =>    $task_details['tasktype_name'],
                  'date_opened'          =>    $task_details['date_opened'],
                  'opened_by'            =>    $task_details['opened_by_name'],
                  'is_closed'            =>    $task_details['is_closed'],
                  'date_closed'          =>    $task_details['date_closed'],
                  'closed_by'            =>    $task_details['closed_by_name'],
                  'closure_comment'      =>    $task_details['closure_comment'],
                  'item_summary'         =>    $fs->formatText($task_details['item_summary']),
                  'detailed_desc'        =>    $fs->formatText($task_details['detailed_desc']),
                  'item_status'          =>    $task_details['status_name'],
                  'assigned_to'          =>    $task_details['assigned_to_name'],
                  'resolution_reason'    =>    $task_details['resolution_name'],
                  'product_category'     =>    $task_details['category_name'],
                  'product_version'      =>    $task_details['reported_version_name'],
                  'closedby_version'     =>    $task_details['due_in_version_name'],
                  'operating_system'     =>    $task_details['os_name'],
                  'task_severity'        =>    $task_details['severity_name'],
                  'task_priority'        =>    $task_details['priority_name'],
                  'last_edited_by'       =>    $task_details['last_edited_by_name'],
                  'last_edited_time'     =>    $task_details['last_edited_time'],
                  'percent_complete'     =>    $task_details['percent_complete'],
                  'mark_private'         =>    $task_details['mark_private'],
               );

   return new xmlrpcresp(php_xmlrpc_encode($result));

// End of getTask function
}

//////////////////////////////////////////
// Start of function to open a new task //
//////////////////////////////////////////





///////////////////////////////////////
// Start of function to close a task //
///////////////////////////////////////
function closeTask($args)
{
   global $fs;
   global $db;
   include_once('includes/notify.inc.php');
   $notify = new Notifications;

   $username   = php_xmlrpc_decode($args->getParam(0));
   $password   = php_xmlrpc_decode($args->getParam(1));
   $task_id    = php_xmlrpc_decode($args->getParam(2));
   $reason     = php_xmlrpc_decode($args->getParam(3));
   $comment    = php_xmlrpc_decode($args->getParam(4));
   $mark100    = php_xmlrpc_decode($args->getParam(5));

   // First, check the user has a valid, active login.  If not, then stop.
   if (!$fs->checkLogin($username, $password))
   {
      return new xmlrpcresp (0,-1, 'Your credentials were incorrect, or your account has been disabled.');
   }

   // Get the user_id
   $user_id = $fs->checkLogin($username, $password);

   // Get the task details
   $task_details = @$fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $permissions = $fs->getPermissions($user_id, $task_details['attached_to_project']);

   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
   {
      return new xmlrpcresp (0,-3, 'The requested task does not exist.');
   }

    // Get info on the dependencies
    $check_deps = $db->Query("SELECT * FROM flyspray_dependencies d
                                LEFT JOIN flyspray_tasks t on d.dep_task_id = t.task_id
                                WHERE d.task_id = ?",
                                array($task_id));

    // Cycle through the dependencies, checking if any are still open
    while ($deps_details = $db->FetchArray($check_deps)) {
      if ($deps_details['is_closed'] != '1') {
        $deps_open = 'yes';
      };
    };

   // Compare permissions to view this task
   if ($task_details['project_is_active'] == '1'
         && ($task_details['others_view'] == '1' OR $permissions['view_tasks'] == '1')
         && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user_id)
         OR ($permissions['manage_project'] == '1' OR $task_details['mark_private'] != '1'))
         && (($permissions['close_own_tasks'] == '1' && $task_details['assigned_to'] == $current_user['user_id'])
         OR $permissions['close_other_tasks'] == '1')
       )
   {
      $can_close = 'yes';
   }

   if ($can_close != 'yes')
   {
      return new IXR_Error(-2, 'You do not have permission to perform this function.');
   }

   // Check if we should mark the task 100% complete
   if(!empty($mark100))
   {
      $db->Query("UPDATE flyspray_tasks
                  SET percent_complete = '100'
                  WHERE task_id = ?",
                  array($task_id)
                 );
   }

   //Do it.  Do it.  Close the task, now!
   $db->Query("UPDATE flyspray_tasks
               SET is_closed = '1',
               resolution_reason = ?,
               closure_comment = ?,
               date_closed = ?,
               closed_by = ?
               WHERE task_id = ?",
               array($reason, $comment, date(U), $user_id, $task_id)
             );

   // Log this to the task's history
   $fs->logEvent($task_id, 2, $reason, $comment);

   // Generate notifications
   $notify->Create('3', $task_id);

   return true;

// End of close task function
}

function getUser($args)
{
   global $fs;
   global $db;

   $username   = php_xmlrpc_decode($args->getParam(0));
   $password   = php_xmlrpc_decode($args->getParam(1));
   $req_user   = php_xmlrpc_decode($args->getParam(2));

   // First, check the user has a valid, active login.  If not, then stop.
   if (!$fs->checkLogin($username, $password))
   {
      return new xmlrpcresp (0,-1, 'Your credentials were incorrect, or your account has been disabled.');
   }

   // Get the user_id
   $user_id = $fs->checkLogin($username, $password);

   // Get the user's permissions
   $permissions = $fs->getPermissions($user_id, true);

   if ($permissions['is_admin'] == '1' or $user_id == $req_user)
   {
      $user_details = $fs->getUserDetails($req_user);

      // If the task doesn't exist, stop.
      if (!is_numeric($user_details['user_id']))
      {
         return new xmlrpcresp (0,-3, 'The requested user does not exist.');
      }

      $result = array ( 'user_id'         => $user_details['user_id'],
                     'user_name'       => $user_details['user_name'],
                     'real_name'       => $user_details['real_name'],
                     'jabber_id'       => $user_details['jabber_id'],
                     'email_address'   => $user_details['email_address'],
                     'account_enabled' => $user_details['account_enabled'],
                   );
      
      return new xmlrpcresp(php_xmlrpc_encode($result));

   } else
   {
      return new xmlrpcresp (0,-2, 'You do not have permission to perform this function.');
   }


// End of getUser function
}


// Define the server
$server = new xmlrpc_server(array(

                                  'fs.getTask' =>  array( 'function' => 'getTask' ),
                                  'fs.getUser' =>  array( 'function' => 'getUser' ),
                                  'fs.closeTask' =>  array( 'function' => 'closeTask' )

                                 )
                            );

?>