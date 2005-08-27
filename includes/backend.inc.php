<?php
/*
   ------------------------------------------------------------
   | This script contains reusable functions we use to modify |
   | various things in the Flyspray database tables.          |
   ------------------------------------------------------------
*/

class Backend {

   /* This function is used to ADD a user to the
      Notification list of multiple tasks (if desired).
      Expected args are user_id and an array of tasks
   */
   function AddToNotifyList($user_id, $tasks)
   {
      global $db;
      global $dbprefix;
      global $fs;

      foreach ($tasks AS $key => $task_id)
      {
         // We can't add a user to a notif list if they're already on it, so...
         // Get a list of users who are on the notif list for this task
         $notify_list = $db->Query("SELECT user_id
                                    FROM {$dbprefix}notifications
                                    WHERE task_id = ?
                                    AND user_id = ?",
                                    array($task_id, $user_id)
                                  );

         // If the user isn't already on the notification list...
         if (!$db->CountRows($notify_list))
         {
            //  Add them to the notif list
            $db->Query("INSERT INTO {$dbprefix}notifications
                        (task_id, user_id)
                        VALUES(?,?)",
                        array($task_id, $user_id)
                       );

            // Log this event to the task history
            $fs->logEvent($task_id, 9, $user_id);
         }

      // End of cycling through the tasks
      }

      return true;

   // End of AddToNotifyList() function
   }


   /* This function is used to REMOVE a user from the
      Notification list of multiple tasks (if desired).
      Expected args are user_id and an array of tasks.
   */
   function RemoveFromNotifyList($user_id, $tasks)
   {
      global $db;
      global $dbprefix;
      global $fs;

      foreach ($tasks AS $key => $task_id)
      {
         // Remove the notif entry
         $db->Query("DELETE FROM {$dbprefix}notifications
                     WHERE task_id = ?
                     AND user_id = ?",
                     array($task_id, $user_id)
                    );

         // Log this event to the task history
         $fs->logEvent($task_id, 10, $user_id);

      // End of cycling through the tasks
      }

      return true;

   // End of RemoveFromNotifyList() function
   }


   /* This function is for a user to assign multiple tasks to themselves.
      Expected args are user_id and an array of tasks.
   */
   function AssignToMe($user_id, $tasks)
   {
      global $db;
      global $dbprefix;
      global $fs;
      global $notify;

      foreach ($tasks AS $key => $task_id)
      {
         // Get the task details
         $task_details = @$fs->getTaskDetails($task_id);

         // Get the user's permissions for the project this task belongs to
         $perms = $fs->getPermissions($user_id, $task_details['attached_to_project']);

         // Check permissions first
         if ($task_details['project_is_active'] == '1'
           && ($task_details['others_view'] == '1' OR $perms['view_tasks'] == '1')
           && (($task_details['mark_private'] == '1' && $task_details['assigned_to'] == $user_id)
             OR $perms['manage_project'] == '1' OR $task_details['mark_private'] != '1')
           && (($perms['assign_to_self'] == '1' && empty($task_details['assigned_to']))
             OR $perms['assign_others_to_self'] == '1')
           && $task_details['assigned_to'] != $user_id )
         {
            // Make the change in assignment
            $db->Query("UPDATE {$dbprefix}tasks
                        SET assigned_to = ?, item_status = '3'
                        WHERE task_id = ?",
                        array($user_id, $task_id));

            // Log this event to the task history
            $fs->logEvent($task_details['task_id'], 19, $user_id, $task_details['assigned_to']);

            // Get the notifications going
            $notify->Create('10', $task_id);

         // End of permission check
         }

      // End of cycling through the tasks
      }

      return true;

   // End of AssignToMe() function
   }


   /* This function takes an array of arguments, and returns a
      nested array of task details ready to be formatted for display.
   */
   function GenerateTaskList($args)
   {
      if (!is_array($args))
         return "We were not given an array of arguments to process.";

      global $db;
      global $dbprefix;
      global $fs;
      global $flyspray_prefs;

      /*
      Since all variables will be passed to this function by Ander's
      XHMLHttpRequest implementation, we know that they will all be set,
      and all be valid.  Therefore we don't need to check that the variables
      are correct and safe, right?
      */

      $userid     = $args[0];    // The user id of the person requesting the tasklist
      $projectid  = $args[1];    // The project the user wants tasks from. '0' equals all projects
      $tasks_req  = $args[2];    // 'all', 'assigned', 'reported' or 'watched'
      $string     = $args[3];    // The search string
      $type       = $args[4];    // Task Type, from the editable list
      $sev        = $args[5];    // Severity, from the editable list
      $dev        = $args[6];    // User id of the person assigned the tasks
      $cat        = $args[7];    // Category, from the editable list
      $status     = $args[8];    // Status, from the translatable list
      $due        = $args[9];    // Version the tasks are due in
      $date       = $args[10];   // Date the tasks are due by
      $limit      = $args[11];   // The amount of tasks requested.  '0' = all

      // We only accept numeric values for the following args
      if (  !is_numeric($userid)
            OR !is_numeric($projectid)
            OR !is_numeric($type)
            OR !is_numeric($sev)
            OR !is_numeric($dev)
            OR !is_numeric($cat)
            OR !is_numeric($status)
            OR !is_numeric($due)
            OR !is_numeric($limit)
         )
         return "At least one required argument was not numerical.";

      /*
      I trust that Ander's funky javascript can handle sorting and paginating
      the tasks returned by this function, therefore we don't really need
      any of the following variables that we used to use on the previous
      task list page, right?

      $args[12] = $perpage;      // How many results to display
      $args[13] = $pagenum;      // Which page of the search results we're on
      $args[14] = $order;        // Which column to order by
      $args[15] = $sort;         // [asc|desc]ending order for the above column ordering
      $args[16] = $order2;        // Secondary column to order by
      $args[17] = $sort2;         // [asc|desc]ending order for the above column ordering
      */

      $criteria = array('task_type'          => $type,
                        'task_severity'      => $sev,
                        'assigned_to'        => $dev,
                        'product_category'   => $cat,
                        'closedby_version'   => $due,
                       );

      if (!empty($userid))
         $permissions = @$fs->getPermissions($userid, $projectid);

      $project_prefs = $fs->getProjectPrefs($projectid);

      // Check if the user can view tasks from this project
      if ($permissions['view_tasks'] == '1' OR $permissions['global_view'] == '1' OR $project_prefs['others_view'] == '1')
      {
         // If they have permission, let's carry on.  Otherwise, give up.
      } else
      {
         return "You don't have permission to view tasks from that project.";
      }

      $where = array();
      $params = array('0');

      // Check the requested status
      if (empty($status))
      {
         $where[] = "t.is_closed <> '1'";

      } elseif ($status == 'closed')
      {
         $where[] = "t.is_closed = '1'";

      } else
      {
         $where[] = "t.item_status = ? AND t.is_closed <> '1'";
         $params[] = $status;
      }


      // Select which project we want. If $projectid is zero, we want everything
      if (!empty($projectid))
      {
         $where[] = "t.attached_to_project = ?";
         $params[] = $projectid;
      }

      // Restrict query results based upon (lack of) PM permissions
      if (!empty($userid) && $permissions['manage_project'] != '1')
      {
         $where[] = "(t.mark_private = '0' OR t.assigned_to = ?)";
         $params[] = $userid;

      } elseif (empty($userid))
      {
         $where[] = "t.mark_private = '0'";
      }

      // Change query results based upon type of tasks requested
      if($tasks_req == 'assigned')
      {
         $where[] = "t.assigned_to = ?";
         $params[] = $userid;

      } elseif ($tasks_req == 'reported')
      {
         $where[] = "t.opened_by = ?";
         $params[] = $userid;

      } elseif ($tasks_req == 'watched')
      {
         $where[] = "fsn.user_id = ?";
         $params[] = $userid;
      }

      // Calculate due-by-date
      if (!empty($date))
      {
         $where[] = "(t.due_date < ? AND t.due_date <> '0' AND t.due_date <> '')";
         $params[] = strtotime("$date +24 hours");
      }

      // The search string
      if (!empty($string))
      {
         $string = ereg_replace('\(', " ", $string);
         $string = ereg_replace('\)', " ", $string);
         $string = trim($string);

         $where[] = "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ?)";
         $params[] = "%$string%";
         $params[] = "%$string%";
         $params[] = "%$string%";
      }

      // Add the other search narrowing criteria
      foreach ($criteria AS $key => $val)
      {
         if (!empty($val))
         {
            $where[] = "t.$key = ?";
            $params[] = $val;
         }
      }

      // Expand the $params
      $sql_where = implode(" AND ", $where);

      // Alrighty.  We should be ok to build the query now!
      $search = $db->Query("SELECT DISTINCT t.task_id
                            FROM {$dbprefix}tasks t
                            LEFT JOIN {$dbprefix}notifications fsn ON t.task_id = fsn.task_id
                            WHERE t.task_id > ?
                            AND $sql_where
                            ORDER BY t.task_severity DESC, t.task_id ASC
                            ", $params, $limit
                          );

      $tasklist = array();

      while ($row = $db->FetchArray($search))
         $tasklist[] = $fs->GetTaskDetails($row['task_id']);

      return $tasklist;

      //return $where;

      //return $search;

   // End of GenerateTaskList() function
   }


   /* This function creates a new task.  Due to the nature of lists
      being specified in the database, we can't really accept default
      values, right?
   */
   function CreateTask($args)
   {
      global $db;
      global $dbprefix;
      global $fs;
      global $flyspray_prefs;
      //global $notify;

      $notify = new Notifications();


      if (!is_array($args))
         return "We were not given an array of arguments to process.";




      // Here's a list of the arguments we accept
      // These args are complusory
      $userid        = $args[0];    // The user id of the user creating this task (numerical, 0 = anon)
      $projectid     = $args[1];    // Project to which we're attaching this task (numerical)

      $summary       = $args[2];    // Item Summary (string)
      $desc          = $args[3];    // Detailed Description (string)

      $tasktype      = $args[4];    // Task Type (numerical)
      $category      = $args[5];    // Product Category (numerical)
      $version       = $args[6];    // Version this task was REPORTED in (numerical)
      $os            = $args[7];    // Operating system (numerical)
      $severity      = $args[8];    // Task severity (numerical)

      // These args are only set by someone with 'modify all tasks' permission
      $assigned      = $args[9];    // User id of who is being assigned the task (numerical)
      $duever        = $args[10];   // The version this task is DUE in (numerical)
      $priority      = $args[11];   // Task Priority (numerical)
      $duedate       = $args[12];   // Due Date (10 digit numerical)
      $status        = $args[13];   // Item Status (numerical)


      // Get some information about the project and the user's permissions
      $project_prefs = $fs->GetProjectPrefs($projectid);
      $permissions   = $fs->GetPermissions($userid, $projectid);

      // Check permissions for the specified user (or anonymous) to open tasks
      if ($permissions['open_new_tasks'] != '1' && $project_prefs['anon_open'] != '1')
         return false;


      // Some fields can have default values set
      if (empty($assigned) OR $permissions['modify_all_tasks'] != '1')
      {
         $assigned = 0;
         $duever = 0;
         $priority = 2;
         $duedate = 0;
         $status = 1;
      }

      $checkArray = array("userid","projectid","tasktype","category","version","os","severity",
                          "assigned","duever","priority","duedate","status");


      foreach ($checkArray as $item) {

         if (!is_numeric($$item)) {
            return "value for $item is not numeric ($item='".($$item)."')";
         }
      }



      // Here comes the database insert!
      $db->Query("INSERT INTO {$dbprefix}tasks
                  (attached_to_project,
                  task_type,
                  date_opened,
                  opened_by,
                  item_summary,
                  detailed_desc,
                  item_status,
                  assigned_to,
                  product_category,
                  product_version,
                  closedby_version,
                  operating_system,
                  task_severity,
                  task_priority,
                  due_date)
                  VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                  array($projectid,
                  $tasktype,
                  date('U'),
                  $userid,
                  $summary,
                  $desc,
                  $status,
                  $assigned,
                  $category,
                  $version,
                  $duever,
                  $os,
                  $severity,
                  $priority,
                  $duedate)
                );

      // Get the task id back
      $task_details = $db->FetchArray($db->Query("SELECT task_id, item_summary, product_category
                                                FROM {$dbprefix}tasks
                                                WHERE item_summary = ?
                                                AND detailed_desc = ?
                                                ORDER BY task_id DESC",
                                                array($summary, $desc), 1));


      $taskid = $task_details['task_id'];
      // Log that the task was opened
      $fs->logEvent($task_details['task_id'], 1);

      $cat_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}list_category
                                                 WHERE category_id = ?",
                                                 array($category)
                                               )
                                    );

      // We need to figure out who is the category owner for this task
      if (!empty($cat_details['category_owner']))
      {
         $owner = $cat_details['category_owner'];

      } elseif (!empty($cat_details['parent_id']))
      {
         $parent_cat_details = $db->FetchArray($db->Query("SELECT category_owner
                                                           FROM {$dbprefix}list_category
                                                           WHERE category_id = ?",
                                                           array($cat_details['parent_id'])
                                                         )
                                              );

         // If there's a parent category owner, send to them
         if (!empty($parent_cat_details['category_owner']))
            $owner = $parent_cat_details['category_owner'];
      }

      // Otherwise send it to the default category owner
      if (empty($owner))
         $owner = $project_prefs['default_cat_owner'];

      if (!empty($owner))
      {
         // Category owners now get auto-added to the notification list for new tasks
         $insert = $db->Query("INSERT INTO {$dbprefix}notifications
                              (task_id, user_id)
                              VALUES(?, ?)",
                              array($taskid, $owner)
                             );

         $fs->logEvent($taskid, 9, $owner);

         // Create the Notification
         $notify->Create('1', $taskid);

      // End of checking if there's a category owner set, and notifying them.
      }

      // give some information back
      return $task_details;

   // End of CreateTask() function
   }

   /* This function came from the php function page for mt_srand()
      seed with microseconds to create a random filename
   */
   function make_seed()
   {
      list($usec, $sec) = explode(' ', microtime());
      return (float) $sec + ((float) $usec * 100000);
   // End of make_seed() function
   }


   /*
      This function handles file uploads.  Flyspray doesn't allow
      anonymous uploads of files, so the $userid is necessary.
      $taskid is the task that the files will be attached to
      $files is an array of files with attributes intact.  It should be $_FILES passed directly
      $commentid is only valid if the files are to be attached to a comment
   */
   function UploadFiles($userid, $taskid, $files, $commentid = '0')
   {
      global $db;
      global $dbprefix;
      global $fs;
      global $flyspray_prefs;
      global $notify;

      // Retrieve some important information
      $task_details  = $fs->GetTaskDetails($taskid);
      $permissions   = $fs->GetPermissions($userid, $task_details['attached_to_project']);

      // If the user hasn't permission, give up.
      if ($permissions['create_attachments'] != '1')
         return false;

      foreach ($files['userfile']['error'] as $key => $error)
      {
         if ($error == UPLOAD_ERR_OK)
         {
            $files_added = 'yes';

            mt_srand($this->make_seed());
            $randval = mt_rand();
            $file_name = $taskid."_$randval";

            $path = 'attachments/' . $file_name;

            $tmp_name = $files['userfile']['tmp_name'][$key];

            // Then move the uploaded file into the attachments directory and remove exe permissions
            @move_uploaded_file($tmp_name, $path);
            @chmod($path, 0644);

            // Only add the listing to the database if the file was actually uploaded successfully
            if (file_exists($path))
            {
               $db->Query("INSERT INTO {$dbprefix}attachments
                           (task_id, comment_id, orig_name, file_name,
                            file_type, file_size, added_by, date_added)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                           array($taskid,
                                 $commentid,
                                 $files['userfile']['name'][$key],
                                 $file_name,
                                 $files['userfile']['type'][$key],
                                 $files['userfile']['size'][$key],
                                 $userid,
                                 '0')
                         );

               // Fetch the attachment id for the history log
               $attachment = $db->FetchRow($db->Query("SELECT attachment_id
                                                       FROM {$dbprefix}attachments
                                                       WHERE task_id = ?
                                                       ORDER BY attachment_id DESC",
                                                       array($taskid), 1
                                                     )
                                          );
               // Log to task history
               $fs->logEvent($taskid, 7, $attachment['attachment_id']);
            }
         }
      }

   // End of UploadFiles() function
   }


// End of backend class
}
?>