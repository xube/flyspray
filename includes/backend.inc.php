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
                                    FROM {$dbprefix}_notifications
                                    WHERE task_id = ?
                                    AND user_id = ?",
                                    array($task_id, $user_id)
                                  );

         // If the user isn't already on the notification list...
         if (!$db->CountRows($notify_list))
         {
            //  Add them to the notif list
            $db->Query("INSERT INTO {$dbprefix}_notifications
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
         $db->Query("DELETE FROM {$dbprefix}_notifications
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
            $db->Query("UPDATE {$dbprefix}_tasks
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

      // We only accept numeric values for the following args
      if (  !is_numeric($userid)
            OR !is_numeric($projectid)
            OR !is_numeric($type)
            OR !is_numeric($sev)
            OR !is_numeric($dev)
            OR !is_numeric($cat)
            OR !is_numeric($status)
            OR !is_numeric($due)
         )
         return "At least one argument was not numerical.";

      /*
      I trust that Ander's funky javascript can handle sorting and paginating
      the tasks returned by this function, therefore we don't really need
      any of the following variables that we used to use on the previous
      task list page, right?

      $args[11] = $perpage;      // How many results to display
      $args[12] = $pagenum;      // Which page of the search results we're on
      $args[13] = $order;        // Which column to order by
      $args[14] = $sort;         // [asc|desc]ending order for the above column ordering
      $args[15] = $order2;        // Secondary column to order by
      $args[16] = $sort2;         // [asc|desc]ending order for the above column ordering
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
         //return "You don't have permission to view tasks from that project.";
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
         $where[] = "t.item_status = ?";
         $params[] = $status;
      }


      // Select which project we want. If $projectid is zero, we want everything
      if (!empty($projectid))
      {
         //$where[] = "t.attached_to_project = ?";
         //$params[] = $projectid;
      }

      // Restrict query results based upon (lack of) PM permissions
      if (!empty($userid) && $permissions['manage_project'] != '1')
      {
         $where[] = "(t.mark_private = '0' OR t.assigned_to = ?)";
         $params[] = $userid;

      } elseif (!isset($userid))
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
      $sql_params = implode(",", $params);

      // Alrighty.  We should be ok to build the query now!
      $search = $db->Query("SELECT DISTINCT t.task_id
                            FROM {$dbprefix}_tasks t
                            LEFT JOIN {$dbprefix}_notifications fsn ON t.task_id = fsn.task_id
                            WHERE t.task_id > ?
                            AND $sql_where
                            ORDER BY t.task_severity DESC
                            ", $params, 5  // Limiting to five tasks for testing purposes
                          );

      $tasklist = array();

      while ($row = $db->FetchArray($search))
         $tasklist[] = $fs->GetTaskDetails($row['task_id']);

      return $tasklist;

      //return array($where, $sql_params);

      //return $search;

   // End of GenerateTaskList() function
   }


// End of backend class
}
?>