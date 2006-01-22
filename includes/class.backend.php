<?php
/*
   ------------------------------------------------------------
   | This script contains reusable functions we use to modify |
   | various things in the Flyspray database tables.          |
   ------------------------------------------------------------
*/

class Backend
{
    /* This function is used to ADD a user to the
       Notification list of multiple tasks (if desired).
       Expected args are user_id and an array of tasks
    */
    function AddToNotifyList($user_id, $tasks)
    {
        global $db, $fs;

        settype($tasks, 'array');

        foreach ($tasks AS $key => $task_id) {
            $sql = $db->Query("SELECT notify_id
                                 FROM {notifications}
                                WHERE task_id = ? and user_id = ?",
                              array($task_id, $user_id));
           
            if (!$db->CountRows($sql)) {
                $db->Query("INSERT INTO {notifications} (task_id, user_id)
                                 VALUES  (?,?)", array($task_id, $user_id));
                $fs->logEvent($task_id, 9, $user_id);
            }
        }
    }


    /* This function is used to REMOVE a user from the
       Notification list of multiple tasks (if desired).
       Expected args are user_id and an array of tasks.
    */
    function RemoveFromNotifyList($user_id, $tasks)
    {
        global $db, $fs;

        settype($tasks, 'array');

        foreach ($tasks AS $key => $task_id) {
            $db->Query("DELETE FROM  {notifications}
                              WHERE  task_id = ? AND user_id = ?",
                    array($task_id, $user_id));
            if ($db->affectedRows()) {
                $fs->logEvent($task_id, 10, $user_id);
            }
        }
    }


    /* This function is for a user to assign multiple tasks to themselves.
       Expected args are user_id and an array of tasks.
    */
    function AssignToMe(&$user, $tasks)
    {
        global $db, $fs;
        global $notify;

        settype($tasks, 'array');

        foreach ($tasks as $key => $task_id) {
            // Get the task details
            // FIXME make it less greedy in term of SQL
            $task = @$fs->getTaskDetails($task_id);
            $proj = new Project($task['attached_to_project']);
            $user->get_perms($proj);

            if ($user->can_view_project($proj)
                    && $user->can_view_task($task)
                    && $user->can_take_ownership($task))
            {
               $db->Query("DELETE FROM {assigned}
                                 WHERE task_id = ?",
                                       array($task_id));

               $db->Query("INSERT INTO {assigned}
                                       (task_id, user_id)
                                VALUES (?,?)",
                                       array($task_id, $user->id));

                if ($db->affectedRows()) {
                    $fs->logEvent($task_id, 19, $user->id, implode(' ', $task['assigned_to']));
                    $notify->Create('10', $task_id);
                }
            }
        }
    }
    
    /* This function is for a user to assign multiple tasks to themselves.
       Expected args are user_id and an array of tasks.
    */
    function AddToAssignees(&$user, $tasks)
    {
        global $db, $fs;
        global $notify;

        settype($tasks, 'array');

        foreach ($tasks as $key => $task_id) {
            // Get the task details
            // FIXME make it less greedy in term of SQL
            $task = @$fs->getTaskDetails($task_id);
            $proj = new Project($task['attached_to_project']);
            $user->get_perms($proj);

            if ($user->can_view_project($proj)
                    && $user->can_view_task($task)
                    && $user->can_add_to_assignees($task))
            {
               $db->Query("INSERT INTO {assigned}
                                       (task_id, user_id)
                                VALUES (?,?)",
                                       array($task_id, $user->id));

                if ($db->affectedRows()) {
                    $fs->logEvent($task_id, 29, $user->id, implode(' ', $task['assigned_to']));
                    $notify->Create('16', $task_id);
                }
            }
        }
    }

    /*
       This function handles file uploads.  Flyspray doesn't allow
       anonymous uploads of files, so the $user is necessary.
       $taskid is the task that the files will be attached to
       $commentid is only valid if the files are to be attached to a comment
     */
    function UploadFiles(&$user, $taskid, $commentid = '0')
    {
        global $db, $fs;
        global $notify;

        mt_srand($fs->make_seed());

        // Retrieve some important information
        $task = $fs->GetTaskDetails($taskid);
        $project = new Project($task['attached_to_project']);
        $user->get_perms($project);

        if (!$user->perms['create_attachments']) {
            return false;
        }

        $res = false;

        foreach ($_FILES['userfile']['error'] as $key => $error) {
            if ($error != UPLOAD_ERR_OK) {
                continue;
            }

            $fname = $taskid.'_'.mt_rand();
            while (file_exists($path = 'attachments/'.$fname)) {
                $fname = $taskid.'_'.mt_rand();
            }

            $tmp_name = $_FILES['userfile']['tmp_name'][$key];

            // Then move the uploaded file and remove exe permissions
            @move_uploaded_file($tmp_name, $path);
            @chmod($path, 0644);

            if (!file_exists($path)) {
                // there was an error ...
                // file was not uploaded correctly
                continue;
            }

            $res = true;

            $db->Query("INSERT INTO  {attachments}
                                     ( task_id, comment_id, file_name,
                                       file_type, file_size, orig_name,
                                       added_by, date_added )
                             VALUES  (?, ?, ?, ?, ?, ?, ?, ?)",
                    array($taskid, $commentid, $fname,
                        $_FILES['userfile']['type'][$key],
                        $_FILES['userfile']['size'][$key],
                        $_FILES['userfile']['name'][$key],
                        $user->id, time()));

            // Fetch the attachment id for the history log
            $result = $db->Query("SELECT  attachment_id
                                    FROM  {attachments}
                                   WHERE  task_id = ?
                                ORDER BY  attachment_id DESC",
                    array($taskid), 1);
            $fs->logEvent($taskid, 7, $db->fetchOne($result));
        }

        return $res;
    }

    /************************************************************************************************
     * below this line, functions are old wrt new flyspray internals.  they will
     * need to be refactored
     */

   /* This function creates a new task.  Due to the nature of lists
      being specified in the database, we can't really accept default
      values, right?
   */
   function CreateTask($args)
   {
      global $db;
      global $fs;
      global $user;

      $notify = new Notifications();


      if (!is_array($args))
         return "We were not given an array of arguments to process.";


      $args['user_id'] = $args['user_id'];    // The user id of the user creating this task (numerical, 0 = anon)
     
      // Get some information about the project and the user's permissions
      $project       = new Project($args['attached_to_project']);
      $user          = new User($args['user_id']);
      $user->get_perms($project);

      // Check permissions for the specified user (or anonymous) to open tasks
      if ($user->perms['open_new_tasks'] != '1' && $project->prefs['anon_open'] != '1')
      {
         return "permission denied";
      }

      // Some fields can have default values set
      if ($user->perms['modify_all_tasks'] != '1')
      {
         
         $args['closedby_version'] = 0;
         $args['task_priority'] = 2;
         $args['due_date'] = 0;
         $args['item_status'] = 1;
      } 
      
      
      $assigned_user_list = $args['assigned'];
      // fix assigned user list o an array if it isn't currently
      // but only if it isn't empty
      if ($assigned_user_list) 
      {
         if (!is_array($args['assigned'])) 
         {
            $assigned_user_list = array($args['assigned']);
         }
      }
      
      
      // check that values are numeric where necessary
      $checkArray = array("attached_to_project", "task_type","product_category",
                          "product_version","operating_system",
                          "task_severity","closedby_version","task_priority","due_date","item_status");

      
      
      foreach ($checkArray as $item) 
      {

         if (!is_numeric($args[$item])) 
         {
            return "value for $item is not numeric ($item='".($args[$item])."')";
         }
      }
      
      $result = $db->Query("SELECT  max(task_id)+1 FROM {tasks}");
      $task_id = $db->FetchOne($result);  

      $args['task_id'] = $task_id;
      $args['opened_by'] = $args['user_id'];
      $args['date_opened'] = date('U');
      $args['last_edited_time'] = date('U');
      
     
      
      $valid_keys = array_merge($checkArray, 
                                array('task_id','date_opened', 'last_edited_time', 'opened_by', 'detailed_desc', 'item_summary'));
      
      
      // remove any value/key pairs that are not in valid_keys (or check_array)
      foreach ($args as $key => $val) 
      {
         
         if (!in_array($key,$valid_keys)) 
         {
            unset($args[$key]);
         }
      }
      
     // modified database insert, now the whole thing is built from the $args array
      $cols = implode(",",array_keys($args));
      $values = implode(",",array_fill(0,count($args),"?"));
      
            $db->Query("INSERT INTO {tasks} 
                        ($cols)
                        VALUES($values)",
                        array_values($args)
                      );
      
      if (!$result) 
      {
         // failure to insert task
         return "failed to create new task (db failure)";
      }

      
      
      $task_details = array('task_id'=>$task_id,'item_summary'=>$args['item_summary']);
      $task_id = $args['task_id'];
      
      if ($assigned_user_list)  
      {
         
          foreach ($assigned_user_list as $assigned_user_id)
          {
            $db->Query("INSERT INTO {assigned} 
                        (task_id, user_id) 
                        VALUES (?,?)",
                       array($task_id, $assigned_user_id));
          }
         
         
         // Log to task history
                  
         $fs->logEvent($task_id, 14, join(" ",$assigned_user_list));
         
         // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
         $to   = $notify->SpecificAddresses($assigned_user_list);
         $msg  = $notify->GenerateMsg('14', $task_id);
         $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
         $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);
         
      }
      
      // Log that the task was opened
      $fs->logEvent($task_details['task_id'], 1);

      $result = $db->Query("SELECT * FROM {list_category}
                            WHERE category_id = ?",
                            array($category)
                          );
      $cat_details = $db->FetchArray($result);

      // We need to figure out who is the category owner for this task
      if (!empty($cat_details['category_owner']))
      {
         $owner = $cat_details['category_owner'];

      } elseif (!empty($cat_details['parent_id']))
      {
         $result = $db->Query("SELECT category_owner
                               FROM {list_category}
                               WHERE category_id = ?",
                               array($cat_details['parent_id']));
         $parent_cat_details = $db->FetchArray($result);

         // If there's a parent category owner, send to them
         if (!empty($parent_cat_details['category_owner']))
            $owner = $parent_cat_details['category_owner'];
      }

      // Otherwise send it to the default category owner
      if (empty($owner))
         $owner = $project->prefs['default_cat_owner'];

      if ($owner) 
      {
         $be->AddToNotifyList($owner, array($task_id));
         
         $fs->logEvent($task_id, 9, $owner);
      }
      
      // Create the Notification
      $notify->Create('1', $task_id);
      
      // If the reporter wanted to be added to the notification list
      if ($args['notifyme'] == '1' && $userid != $owner) 
      {
         $be->AddToNotifyList($user->id, $userid);
      }

      // give some information back
      return $task_details;

   // End of CreateTask() function
   }
    
    function isNewTaskValid($taskResult)
    {
        if (empty($taskResult))
            return false;
        if (is_array($taskResult))
            return true;

        return false;
    }

   /* This function takes an array of arguments, and returns a
      nested array of task details ready to be formatted for display.
   */
   function GenerateTaskList($args)
   {
       global $fs;
       
       $taskIdList =  $this->getTaskIdList($args);
       
       $taskList = array();
       
       foreach($taskIdList as $taskId) {
           
           $taskList[] = $fs->GetTaskDetails($taskId);
       }
      
       return $taskList;
   }
    
   /*
      This function takes an array of arguments and returns a list of 
      task ids that match.
    
    Originally all of this was in generateTaskList but it got refactored
    to support the xmlrpc interface
    */
   function getTaskIdList($args) 
   {
      if (!is_array($args))
         return "We were not given an array of arguments to process.";

      global $db;
      global $fs;

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
            //OR !is_numeric($status) // ok to remove? e.g. status can be 'closed'
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

      $project = new Project($projectid);
      $user = new User($userid);
      $user->get_perms($project);

      // Check if the user can view tasks from this project
      if ($user->perms['view_tasks'] == '1' OR $user->perms['global_view'] == '1' OR $project->prefs['others_view'] == '1')
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
      if (!$user->isAnon() && $user->perms['manage_project'] != '1')
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
                            FROM {tasks} t
                            LEFT JOIN {notifications} fsn ON t.task_id = fsn.task_id
                            WHERE t.task_id > ?
                            AND $sql_where
                            ORDER BY t.task_severity DESC, t.task_id ASC
                            ", $params, $limit
                          );

      $tasklist = array();

      while ($row = $db->FetchArray($search))
         $tasklist[] = $row['task_id'];

      return $tasklist;

      //return $where;

      //return $search;

   // End of GenerateTaskList() function
   }

}
?>
