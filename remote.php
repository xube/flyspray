<?php
/*
   -----------------------------------------
   | This script is a series of functions  |
   | for sending information to remote     |
   | clients making requests using xml-rpc |
   -----------------------------------------

   List of errors:
*/

define('IN_FS', true);
define('LOGIN_FAILED',-1);    //Login failed.
define('PERMISSION_DENIED',-2);   //No permission
define('NO_SUCH_TASK',-3);    //Task does not exist
define('NO_SUCH_USER',-3);    //user does not exist (also -3 for compatibility)
define('CREATE_TASK_FAILED',-4);    //Error creating task
define('CREATE_COMMENT_FAILED',-4);    //Error creating task

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
require_once 'header.php';

//require('includes/class.tpl.php');

$lang = "en";


// define a version for this interface so that clients can figure out
// if a particular function is available
// not sure how this should work, but increase the number if a function gets added
define('FS_XMLRPC_VERSION','1.3');


// use xmlrpc library (library + server library)
require_once BASEDIR . '/includes/xmlrpc.inc';
require_once BASEDIR . '/includes/xmlrpcs.inc';

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


/**
    sets a default value of zero
**/
function valueOrZero($value)
{
    
    if ($value)
        return $value;
    else
        return 0;
}



function setProject($project_id) 
{
	global $fs;
	global $proj;
	
   // get a list of valid projects
   
   $projectList = projectListArray();
   
   // use project if valid id, otherwise use the default
   
   if (array_key_exists($project_id,$projectList)) {
      
      $proj = new Project($project_id);
   } else {
      
      $proj = new Project($fs->prefs['default_project']);
   }
   
	
}

/**
    returns a list of projects 
    pass in false as argument 2 to get a list of all projects
    or true (default) to get active projects only
    (arg 0 is username, 1 is password)
**/
function getProjects($args)
{
    global $proj;
    $activeOnly = false;
    
    $user_id = checkRPCLogin($args);
    
    // if the user doesn't hava a valid login then return an error response
    if (!$user_id) {
        return loginErrorResponse();
    }
    
    
    $taskArgs = php_xmlrpc_decode($args->getParam(2));
    
    $activeOnly = $taskArgs['activeonly'];
    
    $array = projectListArray($activeOnly);
    
    return xmlrpcEncodedArrayResponse($array);
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
   
   $user = new user($user_id);

   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
      return new xmlrpcresp (0,NO_SUCH_TASK, 'The requested task does not exist.');

   if (!$user->can_view_task($task_details)) {
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to perform this function.');
   }

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
                     'item_summary'         =>    $task_details['item_summary'],
                     'detailed_desc'        =>    TextFormatter::render($task_details['detailed_desc']),
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

   $arrayQuery = $db->query($query,$queryParam);

   return rotateArray($db->fetchAllArray($arrayQuery),$keyName,$valueName);
}


/**
** takes an array that consists of key->value pairs and returns a pair consisting of the key keyName and the value ValueName
** taken from the array. Useful for extracting data from adodb queries
**/
function rotateArray($array,$keyName,$valueName)
{
   
   $result = array();
   
   foreach($array as $row) {
      
      $result[$row[$keyName]] = $row[$valueName];
      
   }
   return $result;
}


/**
** returns an array of possible task type values
**/
function taskTypeArray()
{
   global $proj;
	
   return rotateArray($proj->listTaskTypes(),"tasktype_id","tasktype_name");
	
	
}
/**
** returns an array of possible task category values
**/
function categoryArray()
{
	
   global $proj;
	
   return rotateArray($proj->listCategories(),"category_id","category_name");
	
}
/**
** returns an array of possible task status values
**/
function statusArray()
{
    global $db;
    $status_list = array();
    $sql = $db->Query('SELECT status_id, status_name FROM {list_status}');
    while ($row = $db->FetchRow($sql)) {
        $status_list[$row[0]] = $row[1];
    }

    return $status_list;
}

/**
** returns an array of possible task severity values
**/
function severityArray()
{
   global $fs;
   return $fs->severities;

}
/**
** returns an array of possible task priority values
**/
function priorityArray()
{
   global $fs;
   return $fs->priotities;
}

/**
** returns an array of possible operating system values
**/
function operatingSystemArray()
{
   global $proj;

   return rotateArray($proj->listOs(),'os_id','os_name');

}

/**
** returns a list of versions that a task can be reported as occurring in
 **/
function reportedVersionArray()
{
   global $proj;
   
   return rotateArray($proj->listVersions(false,2),'version_id','version_name');
   
}


/**
** returns a list of versions that a task can be due in
** @FIXME localization for undecided type is missing
** @BUG
**/
function dueInVersionArray()
{
   global $proj;
   
   return rotateArray($proj->listVersions(false,3),'version_id','version_name');

}

/**
** returns a list of projects
 ** @BUG currently it doesn't handle groups (how?)
 ** @FIXME
 **/
function projectListArray($activeOnly=true)
{
   
   global $fs;
   return rotateArray($fs->listProjects($activeOnly),'project_id','project_title');
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
   $result['projectList'] = projectListArray();

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

   $argData = php_xmlrpc_decode($args->getParam(2));
   
   // set current project
   setProject($argData['projectid']);
   
   $array = getArrayData($arrayName);

   // return the array as a xmlrpc response

   return xmlrpcEncodedArrayResponse($array);

}


function getArrayListForName($args)
{
   global $proj;
   
   $requestedArray = php_xmlrpc_decode($args->getParam(2));
   
   $project_id = $requestedArray['projectid'];
   
   
   setProject($project_id);
   
   //echo  "projectid = $proj->id, $project_id";
   
   return resultFromQueryAsArray($args,$requestedArray['arrayname']);
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
      case "projectList":
         return projectListArray();
      default:
      case "taskData":
         return taskDataArray();
   }


}


// wrapper functions for some commonly used arrays

/*function getTaskTypeList($args)
{
   return resultFromQueryAsArray($args,"taskType");

}

function getCategoryList($args)
{
   return resultFromQueryAsArray($args,"category");

}*/

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
   global $proj;
   global $user;
   
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
   
   
   
   setProject($taskData['project_id']);

   // Get the task details
   $task_details = @$fs->getTaskDetails($task_id);

   // Get the user's permissions for the project this task belongs to
   $user = new user($user_id);

   $permissions = $user->perms;


   // compulsory args
   $taskData['user_id'] = $user_id;
   $taskData['attached_to_project'] = $taskData['project_id'];
   
   // task data is now used directly

   // get permissions for the project
   $project_prefs = $proj->prefs;
   
   

   // check permissions here rather than waiting until we get to
   // the backend module. Saves time and effort
   if ($permissions['open_new_tasks'] != '1' && $project_prefs['anon_open'] != '1') {
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to open a new task.');
   }


   // creeate the new task
   // we may or may not get a result back depending on the
   //version of the be module
   $result = $be->createTask($taskData);



   // if the result isn't valid return a failure message

   if (is_null($result))
   {
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
   $user = new user($user_id);
   
   // If the task doesn't exist, stop.
   if (!is_numeric($task_details['task_id']))
   {
      return new xmlrpcresp (0,NO_SUCH_TASK, 'The requested task does not exist.');
   }

    // Get info on the dependencies
    $check_deps = $db->Query("SELECT * FROM {dependencies} d
                                LEFT JOIN {tasks} t on d.dep_task_id = t.task_id
                                WHERE d.task_id = ?",
                                array($task_id));

    // Cycle through the dependencies, checking if any are still open
    while ($deps_details = $db->FetchRow($check_deps)) {
      if ($deps_details['is_closed'] != '1') {
        $deps_open = 'yes';
      };
    };

   // Compare permissions to view this task
   if ($task_details['project_is_active'] == '1'
         && ($task_details['others_view'] == '1' OR $permissions['view_tasks'] == '1')
         && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user_id)
         OR ($permissions['manage_project'] == '1' OR $task_details['mark_private'] != '1'))
         && (($permissions['close_own_tasks'] == '1' && $task_details['assigned_to'] == $user->id)
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
      $db->Query("UPDATE {tasks}
                  SET percent_complete = '100'
                  WHERE task_id = ?",
                  array($task_id)
                 );
   }

   //Do it.  Do it.  Close the task, now!
   $db->Query("UPDATE {tasks}
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
   $notify->Create(NOTIFY_TASK_CLOSED, $task_id);

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
   $user = new user($user_id);

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



////////////////////////////////////////////////////
// Start of function to list tasks with filtering //
////////////////////////////////////////////////////
function filterTasks($args)
{
   
   global $fs, $db, $be, $proj;
   
   
   $user_id = checkRPCLogin($args);
   
   // if the user doesn't have a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }
   
   $user = new user($user_id);
   
   $requestArgs = php_xmlrpc_decode($args->getParam(2));
   
   
   
   if (empty($requestArgs['project_id'])) {
      $requestArgs['project_id'] = $proj->id;
   } else {
      $proj = new Project($requestArgs['project_id']);
   }
   
   // Compare permissions to view this task
   if ($user->perms('view_tasks') == '0' && $proj->prefs['others_view'] == '0') {
      return new xmlrpcresp (0,PERMISSION_DENIED, 'You do not have permission to perform this function.');
   }  
   
   // clean up the input arguments
   if ($requestArgs['status_id'] == "not closed") {
         $requestArgs['status_id'] = "";
   }
   
   if ($requestArgs['limit'] <= 0) {
        $requestArgs['limit'] = -1;
   }
   
   // build the task array
   
   $taskArgs[0] = $user_id;
   $taskArgs[1] = valueOrZero($requestArgs['project_id']);
   $taskArgs[2] = $requestArgs['tasks_req'];
   $taskArgs[3] = $requestArgs['search'];
   $taskArgs[4] = valueOrZero($requestArgs['type_id']);
   $taskArgs[5] = valueOrZero($requestArgs['severity_id']);
   $taskArgs[6] = valueOrZero($requestArgs['user_id']);
   $taskArgs[7] = valueOrZero($requestArgs['category_id']);
   $taskArgs[8] = $requestArgs['status_id'];
   $taskArgs[9] = valueOrZero($requestArgs['due_in_version_id']);
   $taskArgs[10] = $requestArgs['due_date'];
   $taskArgs[11] = $requestArgs['limit'];
 
   
   
   // can't yet do os_id or priority_id
   // need to add this to backend.class.php
   
   // get the task list (use the id only function)
   $taskList = $be->getTaskIdList($taskArgs);
   
   
   // return to client
   return xmlrpcEncodedArrayResponse($taskList);
   // end of filter tasks function   
}


////////////////////////////////////////////////////
// Start of function to add comment//
////////////////////////////////////////////////////
function addComment($args)
{
	
   global $fs, $db, $be, $proj,$user,$notify,$_FILES;
   include_once('includes/notify.inc.php');
   $notify = new Notifications();
	
   
   
	
   $user_id = checkRPCLogin($args);
   
   // if the user doesn't have a valid login then return an error response
   if (!$user_id) {
      return loginErrorResponse();
   }
   
   
   
   
   
   $requestArgs = php_xmlrpc_decode($args->getParam(2));
   
   
   $task_id = $requestArgs['taskid'];
   $comment_text = $requestArgs['commenttext'];
   
   
   
   
   
   $task = $fs->getTaskDetails($task_id);
   
   setProject($task['project_id']);
   
   $user = new user($user_id);   
   
   if(!$user->perms('add_comments')) {
      
      return xmlrpcError(PERMISSION_DENIED,'No permissions to add comment for user');
   }
   
   if ($task['is_closed'] || $proj->prefs['comment_closed']) {
      return xmlrpcError(PERMISSION_DENIED,'Comment closed');
   }
   
   
   
   
   $result = $be->add_comment($task, $comment_text, $time = null);
   
   if ($result == false) {
      return xmlrpcError(CREATE_COMMENT_FAILED,'Failed to create comment');
   }
   
   $result = $tast['task_id'];
   
   return xmlrpcEncodedArrayResponse($result);
   
}

// Define the server

$server = new xmlrpc_server(NULL,0);

$server->add_to_map('fs.getVersion','getVersion',NULL,NULL);
$server->add_to_map('fs.getTask','getTask',NULL,NULL);
$server->add_to_map('fs.getUser','getUser',NULL,NULL);
$server->add_to_map('fs.closeTask','closeTask',NULL,NULL);
$server->add_to_map('fs.getNewTaskData','getNewTaskData',NULL,NULL);
$server->add_to_map('fs.openTask','openTask',NULL,NULL);
$server->add_to_map('fs.getArrayListForName','getArrayListForName',NULL,NULL);
$server->add_to_map('fs.filterTasks','filterTasks',NULL,NULL);
$server->add_to_map('fs.getProjects','getProjects',NULL,NULL);
$server->add_to_map('fs.addComment','addComment',NULL,NULL);
$server->service();
?>
