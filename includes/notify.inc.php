<?php

/*
   ---------------------------------------------------
   | This script contains the notification functions |
   ---------------------------------------------------
*/

// flag to indicate if we should be noisy or not
$debug = true;

// Define the language packs used
$flyspray_prefs = $fs->getGlobalPrefs();
$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'notify.inc');
$fs->get_language_pack($lang, 'details');

function debug_print($message){
   global $debug;
   if ( $debug )
   {
      echo "$message<br/>\n";
      flush();
   }
}

// Start of the Notifications class
class Notifications {

   // This function is the wrapper for the others in this class
   // It addresses, creates and stores/sends the notifications
   function Create ( $type, $task_id )
   {
      $to = $this->Address($task_id);
      $msg = $this->GenerateMsg($type, $task_id);
      $this->SendEmail($to[0], $msg[0], $msg[1]);
      $this->StoreJabber($to[1], $msg[0], $msg[1]);

   // End of Create() function
   }

   // This function stores pending jabber notifications in the database
   // They are sent by SendJabber(), below
   function StoreJabber( $to, $subject, $body )
   {
      // not sure all of these are needed any longer
      global $db;
      global $dbprefix;
      global $fs;
//       global $notify_text;
//       global $details_text;
//       global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $subject = htmlspecialchars($subject);
      $body = htmlspecialchars($body);

      if (empty($flyspray_prefs['jabber_server'])
          OR empty($flyspray_prefs['jabber_port'])
          OR empty($flyspray_prefs['jabber_username'])
          OR empty($flyspray_prefs['jabber_password']))
            return false;

      if (empty($to))
         return false;

      $body = str_replace('&amp;', '&', $body);
      $date = date('U');

      // store notification in table
      $db->Query("INSERT INTO {$dbprefix}notification_messages
                  (message_subject, message_body, time_created)
                  VALUES (?, ?, ?)",
                  array($subject, $body, $date)
                );

      // grab notification id
      $result = $db->Query("SELECT message_id FROM {$dbprefix}notification_messages
                            WHERE message_subject = ?
                            AND message_body = ?
                            AND time_created = ?",
                            array($subject, $body, $date)
                          );

      $row = $db->FetchRow($result);
      $message_id = $row['message_id'];

      foreach ($to as $jid)
      {
         // store each recipient in table
         $db->Query("INSERT INTO {$dbprefix}notification_recipients
                     (notify_method, message_id, notify_address)
                     VALUES (?, ?, ?)",
                     array('j', $message_id, $jid)
                    );

      }

      return TRUE;
   }

   // This function is called by scripts/schedule.php to periodically
   // send pending Jabber notifications out using class.jabber.php,
   // which is too slow to be called inline to Flyspray's scripts.
   function SendJabber()
   {
      global $db;
      global $dbprefix;
      global $fs;
//       global $notify_text;
//       global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
//       global $current_user;

      debug_print("Checking Flyspray Jabber configuration...");

      if (empty($flyspray_prefs['jabber_server'])
          OR empty($flyspray_prefs['jabber_port'])
          OR empty($flyspray_prefs['jabber_username'])
          OR empty($flyspray_prefs['jabber_password']))
            return false;

      debug_print("We are configured to use Jabber...");

      require_once('class.jabber.php');
      $JABBER = new Jabber;

      $JABBER->server      = $flyspray_prefs['jabber_server'];
      $JABBER->port        = $flyspray_prefs['jabber_port'];
      $JABBER->username    = $flyspray_prefs['jabber_username'];
      $JABBER->password    = $flyspray_prefs['jabber_password'];
      $JABBER->resource    = 'Flyspray';

      // get listing of all pending jabber notifications
      $result = $db->Query("SELECT DISTINCT message_id
                            FROM {$dbprefix}notification_recipients
                            WHERE notify_method='j'");

      if ( !$db->CountRows($result) )
      {
         debug_print("No notifications to send");
         return false;
      }

      // we have notifications to process - connect
      debug_print("We have notifications to process...");
      debug_print("Starting Jabber session:");

      $JABBER->Connect() or die("AAAHHHH can't connect!!!!");
      debug_print("- Connected");

      $JABBER->SendAuth() or die("GAHHHH bad auth!!!!");
      debug_print("- Auth'd");
      sleep(3);

      $JABBER->SendPresence("online", null, null,null,5);
      debug_print("- Presence");
      sleep(3);

      while ( $row = $db->FetchRow($result) )
      {
         $ids[] = $row['message_id'];
      }

      $desired = join(",", $ids);
      debug_print("message ids to send = {" . $desired . "}");

      // removed array usage as it's messing up the select
      // I suspect this is due to the variable being comma separated
      // Jamin W. Collins 20050328
      $notifications = $db->Query("SELECT * FROM {$dbprefix}notification_messages
                                   WHERE message_id in ($desired)
                                   ORDER BY time_created ASC"
                                 );

      debug_print("number of notifications {" . $db->CountRows($notifications) . "}");

      // loop through notifications
      while ( $notification = $db->FetchRow($notifications) )
      {
         $subject = stripslashes($notification['message_subject']);
         $body = stripslashes($notification['message_body']);

         debug_print("Processing notification {" . $notification['message_id'] . "}");

            $recipients = $db->Query("SELECT * FROM {$dbprefix}notification_recipients
                                      WHERE message_id = ?
                                      AND notify_method = 'j'",
                                      array($notification['message_id'])
                                    );

            // loop through recipients
            while ( $recipient = $db->FetchRow($recipients) )
            {
               $jid = $recipient['notify_address'];
               debug_print("- attempting send to {" . $jid . "}");

               // send notification
               if ( $JABBER->connected ) {
                  $JABBER->SendMessage($jid, NULL, NULL,
                     array(
                        "subject"   => $subject,
                        "body"      => $body
                     ));
                  // delete entry from notification_recipients
                  $result = $db->Query("DELETE FROM {$dbprefix}notification_recipients
                                        WHERE message_id = ?
                                        AND notify_method = 'j'
                                        AND notify_address = ?",
                                        array($notification['message_id'], $jid)
                                      );
                  debug_print("- notification sent");
               } else {
                  debug_print("- not connected");
               }
            }

            // check to see if there are still recipients for this notification
            $result = $db->Query("SELECT * FROM {$dbprefix}notification_recipients
                                  WHERE message_id = ?",
                                  array($notification['message_id'])
                                );

            if ( $db->CountRows($result) == 0 )
            {
               debug_print("No further recipients for message id {" . $notification['message_id'] . "}");
               // remove notification no more recipients
               $result = $db->Query("DELETE FROM {$dbprefix}notification_messages
                                     WHERE message_id = ?",
                                     array($notification['message_id'])
                                   );
               debug_print("- Notification deleted");
            }
         }

         // disconnect from server
         $JABBER->Disconnect();
         debug_print("Disconnected from Jabber server");

      return TRUE;
   // End of SendJabber() function
   }


   function SendEmail($to, $subject, $body)
   {
      global $fs;
//       global $notify_text;
//       global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $project_id;
//       global $current_user;

      $flyspray_prefs = $fs->GetGlobalPrefs();
      $project_prefs = $fs->GetProjectPrefs($project_id);

      //$subject = stripslashes($subject);
      //$body = stripslashes($body);
      $body = str_replace('&amp;', '&', $body);

      if (empty($to) OR $to == $_COOKIE['flyspray_userid'])
         return;

      // Get the new email class
      require_once("class.phpmailer.php");

      // Define the class
      $mail = new PHPMailer();

      $mail->From = $flyspray_prefs['admin_email'];
      $mail->Sender = $flyspray_prefs['admin_email'];
      $mail->FromName = 'Flyspray';
      $mail->CharSet = "UTF-8";

      // Do we want to use a remote mail server?
      if (!empty($flyspray_prefs['smtp_server']))
      {
         $mail->IsSMTP();
         $mail->Host = $flyspray_prefs['smtp_server'];

         if (!empty($flyspray_prefs['smtp_user']))
         {
            $mail->SMTPAuth = true;     // turn on SMTP authentication
            $mail->Username = $flyspray_prefs['smtp_user'];  // SMTP username
            $mail->Password = $flyspray_prefs['smtp_pass']; // SMTP password
         }

      // Use php's built-in mail() function
      } else
      {
         $mail->IsMail();
      }

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


   function GenerateMsg($type, $task_id, $arg1='0')
   {
      global $db;
      global $dbprefix;
      global $fs;
      global $notify_text;
      global $details_text;
      global $flyspray_prefs;
      global $current_user;



      // Get the task details
      $task_details = $fs->getTaskDetails($task_id);
      $project_prefs = $fs->GetProjectPrefs($task_details['attached_to_project']);

      // Set the due date correctly
      if ($task_details['due_date'] == '0')
      {
         $due_date = $details_text['undecided'];
      } else
      {
         $due_date = $fs->FormatDate($task_details['due_date'], false);
      }

      // Set the due version correctly
      if ($task_details['closedby_version'] == '0')
         $task_details['closedby_version'] = $details_text['undecided'];


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
         |14. New assignee             |
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
         $body .= $details_text['assignedto'] . ' - ' . $task_details['assigned_to_name'] . "\n";
         $body .= $details_text['operatingsystem'] . ' - ' . $task_details['os_name'] . "\n";
         $body .= $details_text['severity'] . ' - ' . $task_details['severity_name'] . "\n";
         $body .= $details_text['priority'] . ' - ' . $task_details['priority_name'] . "\n";
         $body .= $details_text['reportedversion'] . ' - ' . $task_details['reported_version_name'] . "\n";
         $body .= $details_text['dueinversion'] . ' - ' . $task_details['due_in_version_name'] . "\n";
         $body .= $details_text['duedate'] . ' - ' . $due_date . "\n";
         $body .= $details_text['details'] . ' - ' . $task_details['detailed_desc'] . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $details_text['attachedtoproject'] . ' - ' .  $task_details['project_title'] . "\n";
         $body .= $details_text['summary'] . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $details_text['tasktype'] . ' - ' . $task_details['tasktype_name'] . "\n";
         $body .= $details_text['category'] . ' - ' . $task_details['category_name'] . "\n";
         $body .= $details_text['status'] . ' - ' . $task_details['status_name'] . "\n";
         $body .= $details_text['assignedto'] . ' - ' . $task_details['assigned_to_name'] . "\n";
         $body .= $details_text['operatingsystem'] . ' - ' . $task_details['os_name'] . "\n";
         $body .= $details_text['severity'] . ' - ' . $task_details['severity_name'] . "\n";
         $body .= $details_text['priority'] . ' - ' . $task_details['priority_name'] . "\n";
         $body .= $details_text['reportedversion'] . ' - ' . $task_details['reported_version_name'] . "\n";
         $body .= $details_text['dueinversion'] . ' - ' . $task_details['due_in_version_name'] . "\n";
         $body .= $details_text['duedate'] . ' - ' . $due_date . "\n";
         $body .= $details_text['percentcomplete'] . ' - ' . $task_details['percent_complete'] . "%\n";
         $body .= $details_text['details'] . ' - ' . $task_details['detailed_desc'] . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $details_text['reasonforclosing'] . ' - ' . $task_details['resolution_name'] . "\n";

         if (!empty($task_details['closure_comment']))
         {
            $body .= $details_text['closurecomment'] . ' - ' . $task_details['closure_comment'] . "\n\n";
         }

         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

         return array($subject, $body);

      }

      ////////////////////
      // Task re-opened //
      ////////////////////
      if ($type == '4')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .=  $notify_text['taskreopened'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] .  ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

         return array($subject, $body);

      }

      ///////////////////
      // Comment added //
      ///////////////////
      if ($type == '7')
      {
         // Get the comment information
         $comment = $db->FetchArray($db->Query("SELECT comment_id, comment_text
                                                FROM {$dbprefix}comments
                                                WHERE user_id = ?
                                                AND task_id = ?
                                                ORDER BY comment_id DESC",
                                                array($current_user['user_id'], $task_id), '1'
                                              )
                                   );

         // Generate the nofication message
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['commentadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= "----------\n";
         $body .= $comment['comment_text'] . "\n";
         $body .= "----------\n\n";

         if ($arg1 == 'files')
            $body .= $notify_text['fileaddedtoo'] . "\n\n";

         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . '#comment' . $comment['comment_id'] . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

         return array($subject, $body);
      }

      /////////////////////
      // Ownership taken //
      /////////////////////
      if ($type == '10')
      {

         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $task_details['assigned_to_name'] . ' ' . $notify_text['takenownership'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

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
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

         return array($subject, $body);
      }

      //////////////////
      // New assignee //
      //////////////////
      if ($type == '14')
      {
         $subject = $notify_text['notifyfrom'] . $project_prefs['project_title'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['assignedtoyou'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ")\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $fs->CreateURL('details', $task_id) . "\n\n";
         $body .= $notify_text['disclaimer'];

         $body = stripslashes($body);

         return array($subject, $body);
      }

   // End of Detailed notification function
   }


   // This sends a notification to a specific users, eg project managers.
   function SpecificAddresses($users)
   {

      global $db;
      global $dbprefix;
      global $fs;
//       global $notify_text;
//       global $details_text;
//       global $project_prefs;
      global $flyspray_prefs;
//       global $current_user;

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
      global $dbprefix;
      global $fs;
//       global $notify_text;
//       global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $jabber_users = array();
      $email_users = array();

      $task_details = $fs->GetTaskDetails($task_id);

      $get_users = $db->Query("SELECT *
                               FROM {$dbprefix}notifications n
                               LEFT JOIN {$dbprefix}users u ON n.user_id = u.user_id
                               WHERE n.task_id = ?",
                               array($task_id));

      while ($row = $db->FetchArray($get_users))
      {
         // Check for current user
         if ($row['user_id'] != $current_user['user_id'] &&  $row['user_id'] != $task_details['assigned_to'])
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
            array_push($email_users, $user_details['email_address']);

         // Jabber
         } elseif ($flyspray_prefs['user_notify'] == '3'
            OR ($flyspray_prefs['user_notify'] = '1' && $user_details['notify_type'] == '2')
            && !in_array($user_details['jabber_id'], $jabber_users))
         {
            array_push($jabber_users, $user_details['jabber_id']);
         }

      // End of adding the assigned_to address
      }

      // Now, we add the project contact addresses...
      // ...but only if the task is public
      $task_details = $fs->getTaskDetails($task_id);
      if ($task_details['mark_private'] != '1')
      {
         $proj_emails = explode(",", $project_prefs['notify_email']);
         $proj_jids = explode(",", $project_prefs['notify_jabber']);

         foreach ($proj_emails AS $key => $val)
         {
            if (!empty($val) && !in_array($val, $email_users))
               array_push($email_users, $val);
         }

         foreach ($proj_jids AS $key => $val)
         {
            if (!empty($val) && !in_array($val, $jabber_users))
               array_push($jabber_users, $val);
         }

      // End of checking if a task is private
      }

      // Send back two arrays containing the notification addresses
      return array($email_users, $jabber_users);

   // End of Address() function
   }




// End of Notify class
}

?>
