<?php
/*
   -----------------------------------------
   | This script is a series of functions  |
   | for sending information to remote     |
   | clients making requests using xml-rpc |
   -----------------------------------------

   List of errors:
*/

define('LOGIN_FAILED',-1);    //Login failed.
define('PERMISSION_DENIED',-2);   //No permission
define('NO_SUCH_TASK',-3);    //Task does not exist
define('NO_SUCH_USER',-3);    //user does not exist (also -3 for compatibility)
define('CREATE_TASK_FAILED',-4);    //Error creating task

/*
   Changes:
   4th August 2005: Angus Hardie Angus@malcolmhardie.com for xmlrpc library instead of ixr
   10th August 2005: Angus Hardie Angus@malcolmhardie.com refactored code and
                     added new information and task creation functions

   Requires the xmlrpc library
   http://phpxmlrpc.sourceforge.net
   should be located in a directory called xmlrpc in the root of the flyspray directory
*/

// Get the main headerfile.  It calls other important files
require('header.php');

$lang = "en";


// define a version for this interface so that clients can figure out
// if a particular function is available
// not sure how this should work, but increase the number if a function gets added
define('FS_XMLRPC_VERSION','1.0');


// use xmlrpc library (library + server library)
require_once $conf['general']['baseurl'] . 'includes/xmlrpc.inc';
require_once $conf['general']['baseurl'] . 'includes/xmlrpcs.inc';

//////////////////////////////////////////////////
// Login/Authentication functions               //
//////////////////////////////////////////////////

/**
** checks if there is a currently valid and active user logged in
** @param the arguments for the current xmlrpc request
** @returns the userid of the current user or false if there is no such user
**/
function checkRPCLogin($args)
{
   global $fs;

   $username   = php_xmlrpc_decode($args->getParam(0));
   $password   = php_xmlrpc_decode($args->getParam(1));

   return $fs->checkLogin($username, $password);
}

/**
**   factored out login error message
**/
function loginErrorResponse()
{
   return xmlrpcError(LOGIN_FAILED,'Your credentials were incorrect, or your account has been disabled.');
}

/**
** factored out general xmlrpc error response
**/
function xmlrpcError($code,$message)
{
   return new xmlrpcresp(0,$code,$message);

}

/**
** encodes a php array or object into an xmlrpc response object
** @param the array or object to be encoded
** @returns a new xmlrpcresponse object
**/
function xmlrpcEncodedArrayResponse($data)
{

      return new xmlrpcresp(php_xmlrpc_encode($data));
}

//////////////////////////////////////////////////
// Start of function to return a task's details //
//////////////////////////////////////////////////
function getTask($args)
{
   global $fs;

   $task_id    = php_xmlrpc_decode($args->getParam(2));

   // First, check the user has a valid, active login.  If not, then stop.
   // get the user information

   $user_id = checkRPCLogin($args);

   // if the user doesn't hava a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }



   // Get the task details
   $task_details = $fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $permissions = $fs->getPermissions($user_id, $task_details['attached_to_project']);

   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
      return new xmlrpcresp (0,NO_SUCH_TASK, 'The requested task does not exist.');

   // Compare permissions to view this task
   if ($task_details['project_is_active'] == '1'
         && ($task_details['others_view'] == '1' OR $permissions['view_tasks'] == '1')
         && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user_id)
               OR $permissions['manage_project'] == '1' OR $task_details['mark_private'] != '1'))
      $can_view = true;


   if (!$can_view)
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to perform this function.');

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

   return xmlrpcEncodedArrayResponse($result);

// End of getTask function
}



//////////////////////////////////////////
// Information retrieval functions      //
//////////////////////////////////////////

// these functions get the information needed
// to fill out the create task form


/**
** function to return an array giving details about this flyspray installation
** only to authorized users
 ** @param the arguments from the client
 **/
function getVersion($args)
{
   global $fs;
   global $db;


   // First, check the user has a valid, active login.  If not, then stop.
   // get the user information

   $user_id = checkRPCLogin($args);

   // if the user doesn't have a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }

   $result = array( 'application' => 'flyspray', 'access'=>'xmlrpc','version'=> $fs->version, 'fs_xmlrpcversion'=>FS_XMLRPC_VERSION);

   // return the result

   return xmlrpcEncodedArrayResponse($result);

}






/**
** returns an array that represents the result of query
 ** the array is an associative array
 ** the key is specified by keyname and is one of the columns from the query
 ** the value is specified by valuename and is also one of the columne in the query
 ** @param the query to execute
 ** @param the key string
 ** @param the value string
 ** @param an array of parameters for the query string (replacing ? in the query)
 **/
function arrayForQuery($query,$keyName,$valueName,$queryParam=array())
{
   global $db;

   $get_severity = $db->query($query,$queryParam);

   while ($row = $db->FetchArray($get_severity)) {
      $result[$row[$keyName]] = $row[$valueName];

   };
   return $result;
}


/**
** returns an array of possible task type values
**/
function taskTypeArray()
{
   return arrayForQuery("SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype ORDER BY list_position"
                        ,'tasktype_id','tasktype_name');
}
/**
** returns an array of possible task category values
**/
function categoryArray()
{
   return arrayForQuery("SELECT category_id, category_name FROM flyspray_list_category ORDER BY list_position"
                        ,'category_id','category_name');
}
/**
** returns an array of possible task status values
**/
function statusArray()
{
   global $lang;
   require("lang/$lang/status.php");

   return $status_list;
}

/**
** returns an array of possible task severity values
**/
function severityArray()
{
   global $lang;
   require("lang/$lang/severity.php");
   return $severity_list;

}
/**
** returns an array of possible task priority values
**/
function priorityArray()
{
   global $lang;
   require("lang/$lang/priority.php");
   return $priority_list;
}

/**
** returns an array of possible operating system values
**/
function operatingSystemArray()
{
   global $project_id;

   return arrayForQuery("SELECT os_id, os_name FROM flyspray_list_os WHERE project_id = ? AND show_in_list = ? ORDER BY list_position"
                        ,'os_id','os_name',array($project_id, '1'));

}

/**
** returns a list of versions that a task can be reported as occurring in
**/
function reportedVersionArray()
{
    global $project_id;

   return arrayForQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE project_id = ? AND show_in_list = ? AND version_tense = ? ORDER BY list_position",'version_id','version_name',array($project_id, '1', '2'));

}


/**
** returns a list of versions that a task can be due in
** @FIXME localization for undecided type is missing
** @BUG
**/
function dueInVersionArray()
{
  global $project_id;

   $result = arrayForQuery("SELECT version_id, version_name FROM flyspray_list_version WHERE project_id = ? AND show_in_list = ? AND version_tense = ? ORDER BY list_position",'version_id','version_name',array($project_id, '1', '3'));
   // note undecided should appear at the top of the list
   array_unshift($result,'undecided');


   return $result;

}

/**
** returns a list of the users that the task could be assigned to
** @BUG currently it doesn't handle groups (how?)
** @FIXME
**/
function assignedUserListArray()
{


   global $project_id,$dbprefix;

   $query = "SELECT user_id, real_name from {$dbprefix}users";



   return arrayForQuery($query,'user_id','real_name');


}

/**
** returns a compound array of all of the data that should be needed for a new task form
**/
function taskDataArray()
{
   $result['taskType'] = taskTypeArray();
   $result['category'] = categoryArray();
   $result['status'] = statusArray();
   $result['severity'] = severityArray();
   $result['priority'] = priorityArray();
   $result['operatingSystem'] = operatingSystemArray();
   $result['reportedVersion'] = reportedVersionArray();
   $result['dueInVersion'] = dueInVersionArray();
   $result['assignedUserList'] = assignedUserListArray();

   return $result;
}




/**
** function to return an array as an xmlrpc response
** @param the arguments from the client
** @param the name of the array to be returned
**/
function resultFromQueryAsArray($args,$arrayName)
{
   global $fs;
   global $db;

   // First, check the user has a valid, active login.  If not, then stop.
   // get the user information

   $user_id = checkRPCLogin($args);

   // if the user doesn't hava a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }

   $array = getArrayData($arrayName);

   // return the array as a xmlrpc response

   return xmlrpcEncodedArrayResponse($array);

}



function getArrayListForName($args)
{
   $requestedArray = php_xmlrpc_decode($args->getParam(2));

   return resultFromQueryAsArray($args,$requestedArray);
}


function getArrayData($arrayName)
{

   switch($arrayName) {
      case "taskType":
         return taskTypeArray();
      case "category":
         return categoryArray();
      case "status":
         return statusArray();
      case "severity":
         return severityArray();
      case "priority":
         return priorityArray();
      case "operatingSystem":
         return operatingSystemArray();
      case "reportedVersion":
         return reportedVersionArray();
      case "dueInVersion":
         return dueInVersionArray();
      case "assignedUserList":
         return assignedUserListArray();
      default:
      case "taskData":
         return taskDataArray();
   }


}


// wrapper functions for some commonly used arrays

function getTaskTypeList($args)
{
   return resultFromQueryAsArray($args,"taskType");

}

function getCategoryList($args)
{
   return resultFromQueryAsArray($args,"category");

}

function getStatusList($args)
{
   return resultFromQueryAsArray($args,"status");
}
function getNewTaskData($args)
{
   return resultFromQueryAsArray($args,"taskData");
}


//////////////////////////////////////////
// Start of function to open a new task //
//////////////////////////////////////////


/**
**    create a new task
**    @param the arguments to create the new task
**    @returns xmlrpcresp giving the result of the operation
**/
function openTask($args)
{
   global $fs;
   global $db;
   global $be;
   include_once('includes/notify.inc.php');
   $notify = new Notifications();


   $taskData   = php_xmlrpc_decode($args->getParam(2));

   // First, check the user has a valid, active login.  If not, then stop.
   // get the user information

   $user_id = checkRPCLogin($args);

   // if the user doesn't have a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }

   // Get the task details
   $task_details = @$fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $permissions = $fs->getPermissions($user_id, $taskData['project_id']);




   // compulsory args
   $taskData[0] = $user_id;
   $taskData[1] = $taskData['project_id'];

   $taskData[2] = $taskData['item_summary'];
   $taskData[3] = $taskData['detailed_desc'];

   $taskData[4] = $taskData['task_type'];
   $taskData[5] = $taskData['product_category'];
   $taskData[6] = $taskData['product_version'];
   $taskData[7] = $taskData['operating_system'];
   $taskData[8] = $taskData['task_severity'];

   // permission based arguments
   // these get used if the permissions are sufficient
   // otherwise these values get overridden in $be->createtask

   $taskData[9] = $taskData['assigned_to'];
   $taskData[10] = $taskData['closedby_version'];
   $taskData[11] = $taskData['task_priority'];
   $taskData[12] = $taskData['due_date'];
   $taskData[13] = $taskData['item_status'];


   // data should now be loaded



   // get permissions for the project
   $project_prefs = $fs->GetProjectPrefs($taskData['project_id']);

   // check permissions here rather than waiting until we get to
   // the backend module. Saves time and effort
   if ($permissions['open_new_tasks'] != '1' && $project_prefs['anon_open'] != '1') {
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to perform this function.');
   }


   // creeate the new task
   // we may or may not get a result back depending on the
   //version of the be module
   $result = $be->createTask($taskData);



   // if the result is empty then return a generic result
   // if the result is not empty then assume that if we get an array back then success
   // otherwise if we get a string back then it's probably an error message

   if (empty($result)) {

      $result =  "create task returned - no data returned";
   } else if (!is_array($result)) {
      // return the error
      return new xmlrpcresp (0,CREATE_TASK_FAILED, $result);

   }


   // return success
   return xmlrpcEncodedArrayResponse($result);
}

///////////////////////////////////////
// Start of function to close a task //
///////////////////////////////////////
function closeTask($args)
{
   global $fs;
   global $db;
   include_once('includes/notify.inc.php');
   $notify = new Notifications;


   $task_id    = php_xmlrpc_decode($args->getParam(2));
   $reason     = php_xmlrpc_decode($args->getParam(3));
   $comment    = php_xmlrpc_decode($args->getParam(4));
   $mark100    = php_xmlrpc_decode($args->getParam(5));

   // First, check the user has a valid, active login.  If not, then stop.
   // get the user information

   $user_id = checkRPCLogin($args);

   // if the user doesn't have a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }

   // Get the task details
   $task_details = @$fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $permissions = $fs->getPermissions($user_id, $task_details['attached_to_project']);

   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
   {
      return new xmlrpcresp (0,NO_SUCH_TASK, 'The requested task does not exist.');
   }

    // Get info on the dependencies
    $check_deps = $db->Query("SELECT * FROM {$dbprefix}dependencies d
                                LEFT JOIN {$dbprefix}tasks t on d.dep_task_id = t.task_id
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
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to perform this function.');
   }

   // Check if we should mark the task 100% complete
   if(!empty($mark100))
   {
      $db->Query("UPDATE {$dbprefix}tasks
                  SET percent_complete = '100'
                  WHERE task_id = ?",
                  array($task_id)
                 );
   }

   //Do it.  Do it.  Close the task, now!
   $db->Query("UPDATE {$dbprefix}tasks
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

   $req_user   = php_xmlrpc_decode($args->getParam(2));

   // First, check the user has a valid, active login.  If not, then stop.
   // get the user information

   $user_id = checkRPCLogin($args);

   // if the user doesn't have a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }

   // Get the user's permissions
   $permissions = $fs->getPermissions($user_id, true);

   if ($permissions['is_admin'] == '1' or $user_id == $req_user)
   {
      $user_details = $fs->getUserDetails($req_user);

      // If the task doesn't exist, stop.
      if (!is_numeric($user_details['user_id']))
      {
         return new xmlrpcresp (0,NO_SUCH_USER, 'The requested user does not exist.');
      }

      $result = array ( 'user_id'         => $user_details['user_id'],
                     'user_name'       => $user_details['user_name'],
                     'real_name'       => $user_details['real_name'],
                     'jabber_id'       => $user_details['jabber_id'],
                     'email_address'   => $user_details['email_address'],
                     'account_enabled' => $user_details['account_enabled'],
                   );

      return xmlrpcEncodedArrayResponse($result);

   } else
   {
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to perform this function.');
   }


// End of getUser function
}


// Define the server
$server = new xmlrpc_server(array(
                                  'fs.getVersion' => array( 'function' => 'getVersion' ),
                                  'fs.getTask' =>  array( 'function' => 'getTask' ),
                                  'fs.getUser' =>  array( 'function' => 'getUser' ),
                                  'fs.closeTask' =>  array( 'function' => 'closeTask' ),
                                  'fs.getTaskTypeList' =>  array( 'function' => 'getTaskTypeList' ),
                                  'fs.getCategoryList'=>  array( 'function' => 'getCategoryList' ),
                                  'fs.getNewTaskData'=>  array( 'function' => 'getNewTaskData'),
                                  'fs.openTask' => array( 'function' => 'openTask'),
                                  'fs.getArrayListForName' => array( 'function' => 'getArrayListForName')
                                 )
                            );

?>
