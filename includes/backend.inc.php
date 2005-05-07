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

//             $to  = $notify->Address($task_id);
//             $msg = $notify->Create('10', $task_id);
//             $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
//             $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);

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
         return false;

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

      $args[0] =  $userid;       // The user id of the person requesting the tasklist
      $args[1] =  $projectid;    // The project the user wants tasks from. '0' equals all projects
      $args[2] =  $tasks_req;    // 'all', 'assigned', 'reported' or 'watched'
      $args[3] =  $string;       // The search string
      $args[4] =  $type;         // Task Type, from the editable list
      $args[5] =  $sev;          // Severity, from the editable list
      $args[6] =  $dev;          // User id of the person assigned the tasks
      $args[7] =  $cat;          // Category, from the editable list
      $args[8] =  $status;       // Status, from the translatable list
      $args[9] =  $due;          // Version the tasks are due in
      $args[10] = $date;         // Date the tasks are due by

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

      $permissions = $fs->getPermissions($userid, $projectid);
      $project_prefs = $fs->getProjectPrefs($projectid);

      // Check if the user can view tasks from this project
      if ($permissions['view_tasks'] == '1' OR $permissions['global_view'] == '1' OR $project_prefs['others_view'] == '1')
      {
         // If they have permission, let's carry on.  Otherwise, give up.
      } else
      {
         return false;
      }

      // Check the requested status
      if (empty($status))
      {
         $where = "WHERE is_closed <> '1'";

      } elseif ($status == 'closed')
      {
         $where = "WHERE is_closed = '1'";

      } else
      {
         $where = "WHERE item_status = ?";
         $params = $status;
      }


      // Select which project we want. If $projectid is zero, we want everything
      if (!empty($projectid))
      {
         $where .= " AND attached_to_project = ? ";
         $params .= ", $projectid";
      }

      // Restrict query results based upon (lack of) PM permissions
      if ($permissions['manage_project'] != '1')
      {
         $where .= " AND (mark_private = '0' OR assigned_to = ?) ";
         $params .= ", $userid";
      }

      // Change query results based upon type of tasks requested
      if($tasks_req == 'assigned')
      {
         $where .= " AND assigned_to = ? ";
         $params .= ", $userid";

      } elseif ($tasks_req == 'reported')
      {
         $where .= " AND opened_by = ? ";
         $params .= ", $userid";

      } elseif ($tasks_req == 'watched')
      {
         $where .= " AND fsn.user_id = ? ";
         $params .= ", $userid";
      }

      // Calculate due-by-date
      if (!empty($date))
      {
         $where .= " AND due_date <= ? ";
         $params .= ", $date";
      }

      // The search string
      if (!empty($string))
      {
         $string = ereg_replace('\(', " ", $string);
         $string = ereg_replace('\)', " ", $string);
         $string = trim($string);

         $where .= "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ?)";
         $params .= ", %$string%, %$string%, %$string%";
      }

      // Add the other search narrowing criteria
      foreach ($criteria AS $key => $val)
      {
         if (!empty($val))
         {
            $where .= " AND $key = ? ";
            $params .= ", $val";
         }
      }

      // Alrighty.  We should be ok to build the query now!
      $search = $db->FetchArray($db->Query("SELECT t.task_id
                                            FROM {$dbprefix}_tasks t
                                            LEFT JOIN {$dbprefix}_notifications fsn ON t.task_id = fsn.task_id
                                            $where",
                                            array($params)
                                          )
                               );

      $tasklist = array();

      foreach ($search AS $key => $val)
         $tasklist[] = $fs->GetTaskDetails($val);

      return $tasklist;

   // End of GenerateTaskList() function
   }


// End of backend class
}
?>