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

*/

// Get the main headerfile.  It calls other important files
require('header.php');

// Call the library that does all the funky stuff
// http://scripts.incutio.com/xmlrpc/
require('includes/IXR_Library.inc.php');

// Basic test function that returns the time
function getTime($args) {
   return date('H:i:s');
}

// Function to return a task's details.
function getTask($args) {

   global $fs;

   $username   = $args[0];
   $password   = $args[1];
   $task_id    = $args[2];

   // First, check the user has a valid, active login.  If not, then stop.
   if (!$fs->checkLogin($username, $password))
   {
      return new IXR_Error(-1, 'Your credentials were incorrect, or your account has been disabled.');
   }

   // Get the user_id
   $user_id = $fs->checkLogin($username, $password);

   // Get the task details
   $task_details = @$fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $permissions = $fs->checkPermissions($user_id, $task_details['attached_to_project']);

   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
   {
      return new IXR_Error(-3, 'The requested task does not exist.');
   }

   // Compare permissions to view this task
   if ($task_details['project_is_active'] == '1'
         && ($task_details['others_view'] == '1' OR $permissions['view_tasks'] == '1')
         && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user_id)
               OR $permissions['manage_project'] == '1' OR $task_details['mark_private'] != '1'))
   {
      $can_view = 'yes';
   }

   if ($can_view != 'yes')
   {
      return new IXR_Error(-2, 'You do not have permission to perform this function.');
   }

   return array(
                  task_id              =>    $task_details['task_id'],
                  attached_to_project  =>    $task_details['project_title'],
                  task_type            =>    $task_details['tasktype_name'],
                  date_opened          =>    $task_details['date_opened'],
                  opened_by            =>    $task_details['opened_by_name'],
                  is_closed            =>    $task_details['is_closed'],
                  date_closed          =>    $task_details['date_closed'],
                  closed_by            =>    $task_details['closed_by_name'],
                  closure_comment      =>    $task_details['closure_comment'],
                  item_summary         =>    $fs->formatText($task_details['item_summary']),
                  detailed_desc        =>    $fs->formatText($task_details['detailed_desc']),
                  item_status          =>    $task_details['status_name'],
                  assigned_to          =>    $task_details['assigned_to_name'],
                  resolution_reason    =>    $task_details['resolution_name'],
                  product_category     =>    $task_details['category_name'],
                  product_version      =>    $task_details['reported_version_name'],
                  closedby_version     =>    $task_details['due_in_version_name'],
                  operating_system     =>    $task_details['os_name'],
                  task_severity        =>    $task_details['severity_name'],
                  task_priority        =>    $task_details['priority_name'],
                  last_edited_by       =>    $task_details['last_edited_by_name'],
                  last_edited_time     =>    $task_details['last_edited_time'],
                  percent_complete     =>    $task_details['percent_complete'],
                  mark_private         =>    $task_details['mark_private']
               );

// End of getTask function
}

// Define the server
$server = new IXR_Server(array(
    'test.getTime'   => 'getTime',
    'fs.getTask'     => 'getTask',

));

?>