<?php

/*
   ---------------------------------------------------
   | This script contains the notification functions |
   ---------------------------------------------------
*/

// Define the language packs used
$flyspray_prefs = $fs->getGlobalPrefs();
$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'notify.inc');
$fs->get_language_pack($lang, 'details');

// Start of the Notifications class
class Notifications {

   function StoreJabber( $to, $subject, $body )
   {
      // not sure all of these are needed any longer
      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $subject = htmlspecialchars($subject);
      $body = htmlspecialchars($body);

      if (empty($flyspray_prefs['jabber_server'])
          OR empty($flyspray_prefs['jabber_port'])
          OR empty($flyspray_prefs['jabber_username'])
          OR empty($flyspray_prefs['jabber_password']))
            return false;

      $body = str_replace('&amp;', '&', $body);

      // store notification in table
      $db->Query("INSERT INTO flyspray_notification_messages
                  (subject, body, created)
                  VALUES (?, ?, ?)",
                  array($subject, $body, date(U))
                );

      // grab notification id
      $result = $db->Query("SELECT id FROM flyspray_notification_messages
                            WHERE subject= ?
                            AND body= ?
                            AND created= ?",
                            array($subject, $body, date(U))
                          );

      $row = $db->FetchRow($result);
      $notify_id = $row['id'];

      foreach ($to as $jid)
      {
         // store each recipient in table
         $db->Query("INSERT INTO flyspray_notification_recipients
                     (method, notification_id, address)
                     VALUES (?, ?, ?)",
                     array('j', $notify_id, $jid)
                    );

      }

      return TRUE;
   }

   function SendJabber()
   {
      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      if (empty($flyspray_prefs['jabber_server'])
          OR empty($flyspray_prefs['jabber_port'])
          OR empty($flyspray_prefs['jabber_username'])
          OR empty($flyspray_prefs['jabber_password']))
            return false;

      require_once('class.jabber.php');
      $JABBER = new Jabber;

      $JABBER->server      = $flyspray_prefs['jabber_server'];
      $JABBER->port        = $flyspray_prefs['jabber_port'];
      $JABBER->username    = $flyspray_prefs['jabber_username'];
      $JABBER->password    = $flyspray_prefs['jabber_password'];
      $JABBER->resource    = 'Flyspray';

      // get listing of all pending jabber notifications
      $result = $db->Query("SELECT DISTINCT notification_id
                            FROM flyspray_notification_recipients
                            WHERE method='j'");

      if ( $db->CountRows($result) > 0 )
      {
         // we have notifications to process - connect
         $JABBER->Connect() or die("AAAHHHH can't connect!!!!");
         $JABBER->SendAuth() or die("GAHHHH bad auth!!!!");
         sleep(3);
         $JABBER->SendPresence("online", null, null,null,5);
         sleep(3);

         while ( $row = $db->FetchRow($result) )
         {
            $ids[] = $row['notification_id'];
         }
         $desired = join(",", $ids);

         $notifications = $db->Query("SELECT * FROM flyspray_notification_messages
                                      WHERE id in (?)",
                                      array($desired)
                                    );

         // loop through notifications
         while ( $notification = $db->FetchRow($notifications) )
         {
            // check notification age?
            $subject = $notification['subject'];
            $body = $notification['body'];
            $notification_id = $notification['id'];

            $recipients = $db->Query("SELECT * FROM flyspray_notification_recipients
                                      WHERE notification_id = ?
                                      AND method = 'j'",
                                      array($notification_id)
                                    );

            // loop through recipients
            while ( $recipient = $db->FetchRow($recipients) )
            {
               $jid = $recipient['address'];

               // send notification
               if ( $JABBER->connected ) {
                  $JABBER->SendMessage($jid, NULL, NULL,
                     array(
                        "subject"   => $subject,
                        "body"      => $body
                     ));
                  // delete entry from notification_recipients
                  $result = $db->Query("DELETE FROM flyspray_notification_recipients
                                        WHERE notification_id=?
                                        AND method='j'
                                        AND address= ?",
                                        array($notification_id, $jid)
                                      );
               }
            }

            // check to see if there are still recipients for this notification
            $result = $db->Query("SELECT * FROM flyspray_notification_recipients
                                  WHERE notification_id = ?",
                                  array($notification_id)
                                );

            if ( $db->CountRows($result) == 0 )
            {
               // remove notification no more recipients
               $result = $db->Query("DELETE FROM flyspray_notification_messages
                                     WHERE id = ?",
                                     array($notification_id)
                                   );
            }
         }

         // disconnect from server
         $JABBER->Disconnect();
      }
      return TRUE;
   // End of SendJabber() function
   }


   function SendEmail($to, $subject, $body)
   {
      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $flyspray_prefs = $fs->GetGlobalPrefs();
      $project_prefs = $fs->GetProjectPrefs($_COOKIE['flyspray_project']);

      $subject = stripslashes($subject);
      $body = stripslashes($body);
      $body = str_replace('&amp;', '&', $body);

      if (empty($to) OR $to == $_COOKIE['flyspray_userid'])
         return;

      // Get the new email class
      require_once("class.phpmailer.php");

      // Define the class
      $mail = new PHPMailer();

      $mail->From = $flyspray_prefs['admin_email'];
      $mail->FromName = $project_prefs['project_title'];

      $mail->IsMail();                                      // Use PHP's mail() function
                                                            // CHANGE ME WHEN WE MAKE AN OPTION TO USE AN SMTP SERVER

      if (is_array($to))
      {
         foreach ($to as $key => $val)
         {
            $mail->AddAddress($val);                        // Add each address
         }

      } else
      {
         $mail->AddAddress($to);                            // Add a single address
      }

      $mail->WordWrap = 70;                                 // set word wrap to 70 characters
      //$mail->IsHTML(true);                                  // set email format to HTML

      $mail->Subject = $subject;            // CHANGE ME WHEN WE MAKE NOTIFICATION SUBJECTS CUSTOMISABLE
      $mail->Body = $body;
      //$mail->AltBody = $body;

      /*if(!$mail->Send())
      {
         echo "Message could not be sent. <p>";
         //echo "Mailer Error: " . $mail->ErrorInfo;
         exit;
      }*/
      // The above is commented out to stop Flyspray throwing an error.
      // We should fix this by using templating.  Until then, the below line gives no error on failure.
      $mail->Send();

   }


   function Create($type, $task_id)
   {
      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;



      // Get the task details
      $task_details = $fs->getTaskDetails($task_id);

      /* -------------------------------
         | List of notification types: |
         | 1. Task opened              |
         | 2. Task details changed     |
         | 3. Task closed              |
         | 4. Task re-opened           |
         | 5. Dependency added         |
         | 6. Dependency removed       |
         | 7. Comment added            |
         | 8. Attachment added         |
         | 9. Related task added       |
         |10. Taken ownership          |
         |11. Confirmation code        |
         |12. PM request               |
         |13. PM denied request        |
         -------------------------------
      */
      ///////////////////////////////////////////////////////////////
      // New task opened.  Send notification to the category owner //
      ///////////////////////////////////////////////////////////////
      if ($type == '1')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .=  $notify_text['newtaskopened'] . "\n\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $details_text['attachedtoproject'] . ' - ' .  $task_details['project_title'] . "\n";
         $body .= $details_text['summary'] . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $details_text['tasktype'] . ' - ' . $task_details['tasktype_name'] . "\n";
         $body .= $details_text['category'] . ' - ' . $task_details['category_name'] . "\n";
         $body .= $details_text['status'] . ' - ' . $task_details['status_name'] . "\n";
         $body .= $details_text['operatingsystem'] . ' - ' . $task_details['os_name'] . "\n";
         $body .= $details_text['severity'] . ' - ' . $task_details['severity_name'] . "\n";
         $body .= $details_text['priority'] . ' - ' . $task_details['priority_name'] . "\n";
         $body .= $details_text['reportedversion'] . ' - ' . $task_details['reported_version_name'] . "\n";
         $body .= $details_text['dueinversion'] . ' - ' . $task_details['due_in_version_name'] . "\n";
         $body .= $details_text['percentcomplete'] . ' - ' . $task_details['percent_complete'] . "\n";
         $body .= $details_text['details'] . ' - ' . $task_details['detailed_desc'] . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_details['task_id'] . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      //////////////////////////
      // Task details changed //
      //////////////////////////
      if ($type == '2')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['taskchanged'] . "\n\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $details_text['attachedtoproject'] . ' - ' .  stripslashes($task_details['project_title']) . "\n";
         $body .= $details_text['summary'] . ' - ' . stripslashes($task_details['item_summary']) . "\n";
         $body .= $details_text['tasktype'] . ' - ' . $task_details['tasktype_name'] . "\n";
         $body .= $details_text['category'] . ' - ' . $task_details['category_name'] . "\n";
         $body .= $details_text['status'] . ' - ' . $task_details['status_name'] . "\n";
         $body .= $details_text['operatingsystem'] . ' - ' . $task_details['os_name'] . "\n";
         $body .= $details_text['severity'] . ' - ' . $task_details['severity_name'] . "\n";
         $body .= $details_text['priority'] . ' - ' . $task_details['priority_name'] . "\n";
         $body .= $details_text['reportedversion'] . ' - ' . $task_details['reported_version_name'] . "\n";
         $body .= $details_text['dueinversion'] . ' - ' . $task_details['due_in_version_name'] . "\n";
         $body .= $details_text['percentcomplete'] . ' - ' . $task_details['percent_complete'] . "\n";
         $body .= $details_text['details'] . ' - ' . stripslashes($task_details['detailed_desc']) . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_details['task_id'] . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);

      }

      /////////////////
      // Task closed //
      /////////////////
      if ($type == '3')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .=  $notify_text['taskclosed'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id;
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);

      }

      if ($type == '4')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .=  $notify_text['taskreopened'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] .  ")\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      //////////////////////
      // Dependency added //
      //////////////////////
      if ($type == '5')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .=  $notify_text['depadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);

      }

      ////////////////////////
      // Dependency removed //
      ////////////////////////
      if ($type == '6')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['depremoved'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);

      }

      ///////////////////
      // Comment added //
      ///////////////////
      if ($type == '7')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['commentadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);

      }

      //////////////////////
      // Attachment added //
      //////////////////////
      if ($type == '8')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['attachmentadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      ////////////////////////
      // Related task added //
      ////////////////////////
      if ($type == '9')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['relatedadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      /////////////////////
      // Ownership taken //
      /////////////////////
      if ($type == '10')
      {

         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $task_details['assigned_to_name'] . $notify_text['takenownership'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      ///////////////////////
      // Confirmation code //
      ///////////////////////
      if ($type == '11')
      {
         // We need to work out how to move the confirmation code message generation
         // from scripts/modify.php to here.

         $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         // Confirmation code message goes here
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      ////////////////////////
      // Pending PM request //
      ////////////////////////
      if ($type == '12')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['pendingreq'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

      ///////////////////////
      // PM request denied //
      ///////////////////////
      if ($type == '13')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['pmdeny'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         return array($subject, $body);
      }

   // End of Detailed notification function
   }


   // This sends a notification to a specific users, eg project managers.
   function SpecificAddresses($users)
   {

      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $jabber_users = array();
      $email_users = array();

      foreach ($users AS $key => $val)
      {
         // Get each user's notify prefs
         $user_details = $fs->getUserDetails($val);

         if (($flyspray_prefs['user_notify'] == '1' && $user_details['notify_type'] == '1')
            OR $flyspray_prefs['user_notify'] == '2')
         {
               array_push($email_users, $user_details['email_address']);

         } elseif (($flyspray_prefs['user_notify'] == '1' && $user_details['notify_type'] == '2')
            OR $flyspray_prefs['user_notify'] == '3')
         {
               array_push($jabber_users, $user_details['jabber_id']);
         }


      }

      return array($email_users, $jabber_users);

   // End of SpecificAddresses function
   }


   // This sends a notification to multiple users, usually from the notifications tab on a task
   function Address($task_id)
   {
      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $jabber_users = array();
      $email_users = array();

      $get_users = $db->Query("SELECT *
                               FROM flyspray_notifications n
                               LEFT JOIN flyspray_users u ON n.user_id = u.user_id
                               WHERE n.task_id = ?",
                               array($task_id));

      while ($row = $db->FetchArray($get_users))
      {
         // Check for current user
         if ($row['user_id'] != $_COOKIE['flyspray_userid'] &&  $row['user_id'] != $task_details['assigned_to'])
         {
            if (($flyspray_prefs['user_notify'] == '1' && $row['notify_type'] == '1')
            OR $flyspray_prefs['user_notify'] == '2')
            {
               array_push($email_users, $row['email_address']);

            } elseif (($flyspray_prefs['user_notify'] == '1' && $row['notify_type'] == '2')
            OR $flyspray_prefs['user_notify'] == '3')
            {
               array_push($jabber_users, $row['jabber_id']);
            }

         // End of checking for current user
         }

      // End of cycling through user array
      };

      // Now we need to get the person assigned to this task, and add them to the correct address list
      if (!empty($task_details['assigned_to']) && $task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
      {
         $user_details = $fs->getUserDetails($task_details['assigned_to']);

         // Email
         if ($flyspray_prefs['user_notify'] == '2'
            OR ($flyspray_prefs['user_notify'] = '1' && $user_details['notify_type'] == '1')
            && !in_array($user_details['email_address'], $email_users))
         {
            array_push($jabber_users, $user_details['email_address']);

         // Jabber
         } elseif ($flyspray_prefs['user_notify'] == '3'
            OR ($flyspray_prefs['user_notify'] = '1' && $user_details['notify_type'] == '2')
            && !in_array($user_details['jabber_id'], $jabber_users))
         {
            array_push($jabber_users, $user_details['jabber_id']);
         }

      // End of adding the assigned_to address
      }

      // Send back two arrays containing the notification addresses
      return array($email_users, $jabber_users);

   // End of multiple notification function
   }




// End of Notify class
}

?>
