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
      global $fs;

      foreach ($tasks AS $key => $task_id)
      {
         // We can't add a user to a notif list if they're already on it, so...
         // Get a list of users who are on the notif list for this task
         $notify_list = $db->Query("SELECT user_id
                                    FROM flyspray_notifications
                                    WHERE task_id = ?
                                    AND user_id = ?",
                                    array($task_id, $user_id)
                                  );

         // If the user isn't already on the notification list...
         if (!$db->CountRows($notify_list))
         {
            //  Add them to the notif list
            $db->Query("INSERT INTO flyspray_notifications
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
      global $fs;

      foreach ($tasks AS $key => $task_id)
      {
         // Remove the notif entry
         $db->Query("DELETE FROM flyspray_notifications
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
      global $fs;
      global $notify;

      foreach ($tasks AS $key => $task_id)
      {
         // Get the task details
         $task_details = @$fs->getTaskDetails($task_id);

         // Get the user's permissions for the project this task belongs to
         $perms = $fs->checkPermissions($user_id, $task_details['attached_to_project']);

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
            $db->Query("UPDATE flyspray_tasks
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



// End of backend class
}
?>