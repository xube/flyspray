<?php

/*
   ---------------------------------------------------
   | This script contains the notification functions |
   ---------------------------------------------------
*/

// Define the language packs used
$fs->get_language_pack($lang, 'notify.inc');
$fs->get_language_pack($lang, 'notify.inc');

// Start of the Notifications class
class Notifications {

   function SendJabber( $to, $subject, $body )
   {
      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $body = htmlspecialchars($body);

      if (empty($flyspray_prefs['jabber_server'])
          OR empty($flyspray_prefs['jabber_port'])
          OR empty($flyspray_prefs['jabber_username'])
          OR empty($flyspray_prefs['jabber_password']))
            return false;

      require_once("class.jabber.php");

      $JABBER = new Jabber;

      $JABBER->server         = $flyspray_prefs['jabber_server'];
      $JABBER->port           = $flyspray_prefs['jabber_port'];
      $JABBER->username       = $flyspray_prefs['jabber_username'];
      $JABBER->password       = $flyspray_prefs['jabber_password'];
      $JABBER->resource       = "Flyspray";

      $JABBER->Connect() or die("Couldn't connect to Jabber service!  There is a possiblity that the remote server is down.");
      $JABBER->SendAuth() or die("Couldn't authenticate with Jabber service!  Perhaps the Flyspray administrator has the wrong username or password in the options.");

      $JABBER->SendPresence();

      if (is_array($to)) {

         while (list($key, $val) = each($to))
         {
            $JABBER->SendMessage($val,
                                 "normal",
                                 NULL,
                                 array( // body, thread... whatever
                                       "subject"   => $subject,
                                       "body"      => $body
                                       ),
                                 $payload
                                 );
            //sleep(1);
         }

      } else
      {
         $JABBER->SendMessage($to,
                              "normal",
                              NULL,
                              array( // body, thread... whatever
                                    "subject"   => $subject,
                                    "body"      => $body
                                    ),
                              $payload
                              );

         //sleep(1);
      }

      sleep(1);
      $JABBER->Disconnect();

/*
         $sClient='Flyspray';

         $sBody = str_replace('&amp;', '&', $sBody);

         $socket = fsockopen ( $flyspray_prefs['jabber_server'], $flyspray_prefs['jabber_port'], $errno, $errstr, 30 );
         if ( !$socket ) {
            return '$errstr (' . $errno . ')';
         } else {

            fputs($socket, '<?xml version="1.0" encoding="UTF-8"?><stream:stream to="' . $flyspray_prefs['jabber_server'] . '" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">' );
            fgets( $socket, 1 );

            fputs( $socket, '<iq type="set" id="AUTH_01"><query xmlns="jabber:iq:auth"><username>' . $flyspray_prefs['jabber_username'] . '</username><password>' . $flyspray_prefs['jabber_password'] . '</password><resource>' . $sClient . '</resource></query></iq>' );
            fgets( $socket, 1 );
            //sleep(1);
            if ( is_array( $vTo ))
            {
               foreach ($vTo as $sTo)
               {
                  fputs ( $socket, '<message to="' . $sTo . '" ><body><![CDATA[' . $sBody . ']]></body><subject><![CDATA[' . $sSubject . ']]></subject></message>' );
               }
            } else
            {
                 fputs ( $socket, '<message to="' . $vTo . '" ><body><![CDATA[' . $sBody . ']]></body><subject><![CDATA[' . $sSubject . ']]></subject></message>' );
            }

            fclose ( $socket );
         }
*/
   //return TRUE;
   }


   function SendEmail($to, $subject, $message) {

      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $flyspray_prefs = $fs->GetGlobalPrefs();
      $project_prefs = $fs->GetProjectPrefs($_COOKIE['flyspray_project']);

      if (empty($to) OR $to == $_COOKIE['flyspray_userid'])
         return;

      // Get the new email class
      require("class.phpmailer.php");

      // Define the class
      $mail = new PHPMailer();

      $mail->From = $flyspray_prefs['admin_email'];
      $mail->FromName = $project_prefs['project_title'];

      $mail->IsMail();                                      // Use PHP's mail() function

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
      $mail->Body = $message;
      $mail->Body = $message;

      if(!$mail->Send())
      {
         echo "Message could not be sent. <p>";
         //echo "Mailer Error: " . $mail->ErrorInfo;
         exit;
      }

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
         | 4. Dependency added         |
         | 5. Dependency removed       |
         | 6. Comment added            |
         | 7. Attachment added         |
         | 8. Related task added       |
         | 9. Assigned to you          |
         |10. No longer assigned to you|
         -------------------------------
      */
      ///////////////////////////////////////////////////////////////
      // New task opened.  Send notification to the category owner //
      ///////////////////////////////////////////////////////////////
      if ($type == '1')
      {
         // Get the category owner for this task
         if (!empty($task_details['category_owner']))
         {
            $send_to = $task_details['category_owner'];

         } elseif (!empty($cat_details['parent_id']))
         {
            // If not, see if we can get the parent category owner
            $parent_cat_details = $db->FetchArray($db->Query('SELECT category_owner
                                                              FROM flyspray_list_category
                                                              WHERE category_id = ?',
                                                              array($cat_details['parent_id'])));

            // If there's a parent category owner, send to them
            if (!empty($parent_cat_details['category_owner']))
               $send_to = $parent_cat_details['category_owner'];

         };

         // Otherwise send it to the default category owner
         if (empty($send_to))
            $send_to = $project_prefs['default_cat_owner'];

         // Generate the nofication message
         $subject = $notify_text['notifyfrom'];

         $body =  $notify_text['newtaskopened'] . "\n\n";
         $body .= $details_text['userwho'] . ' - ' . $task_details['opened_by_name'] . "\n\n";
         $body .= $details_text['attached_to_project'] . ' - ' .  $task_details['project_title'] . "\n";
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
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_details['task_id'] . "\n";

         // Send this notification
         $this->Single($send_to, $subject, $body);

         return true;
      }

      //////////////////////////
      // Task details changed //
      //////////////////////////
      if ($type == '2')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'];

         $body =  $notify_text['taskchanged'] . "\n\n";
         $body .= $details_text['userwho'] . ' - ' . $task_details['last_edited_by_name'] . "\n\n";
         $body .= $details_text['attached_to_project'] . ' - ' .  $task_details['project_title'] . "\n";
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
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_details['task_id'] . "\n";

         if (!empty($task_details['assigned_to']) && $task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
            $this->Single($task_details['assigned_to'], $subject, $body);

         $this->Multiple($task_id, $subject, $body);

         return true;
      }

      /////////////////
      // Task closed //
      /////////////////
      if ($type == '3')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'];

         $body =  $notify_text['taskclosed'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $details_text['userwho'] . ' - ' . $task_details['closed_by_name'] . "\n\n";
         $body .= $details_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id;

         if (!empty($task_details['assigned_to']) && $task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
            $this->Single($task_details['assigned_to'], $subject, $body);

         $this->Multiple($task_id, $subject, $body);

         return true;
      }

      //////////////////////
      // Dependency added //
      //////////////////////
      if ($type == '4')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'];

         $body =  $notify_text['depadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $details_text['userwho'] . ' - ' . $current_user['real_name'] . "\n\n";
         $body .= $details_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id;

         if (!empty($task_details['assigned_to']) && $task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
            $this->Single($task_details['assigned_to'], $subject, $body);

         $this->Multiple($task_id, $subject, $body);

         return true;
      }

      ////////////////////////
      // Dependency removed //
      ////////////////////////
      if ($type == '5')
      {
         // Generate the nofication message
         $subject = $notify_text['notifyfrom'];

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['depremoved'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $details_text['userwho'] . ' - ' . $current_user['real_name'] . "\n\n";
         $body .= $details_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         if (!empty($task_details['assigned_to']) && $task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
            $this->Single($task_details['assigned_to'], $subject, $body);

         $this->Multiple($task_id, $subject, $body);
         return true;
      }

      ///////////////////
      // Comment added //
      ///////////////////
      if ($type == '6')
      {
         // Generate the nofication message
         $subject = "Notification from Flyspray";

         $body = $notify_text['donotreply'] . "\n\n";
         $body .= $notify_text['commentadded'] . "\n\n";
         $body .= 'FS#' . $task_id . ' - ' . $task_details['item_summary'] . "\n";
         $body .= $notify_text['userwho'] . ' - ' . $current_user['real_name'] . "\n\n";
         $body .= $notify_text['moreinfo'] . "\n";
         $body .= $flyspray_prefs['base_url'] . '?do=details&amp;id=' . $task_id . "\n\n";
         $body .= $notify_text['disclaimer'];

         //if (!empty($task_details['assigned_to']) && ($task_details['assigned_to'] != $_COOKIE['flyspray_userid']))
            $this->Single($task_details['assigned_to'], $subject, $body);

         //$this->Multiple($task_id, $subject, $body);

         //return true;

         return array($subject, $body);
      }
      if ($type == '7')
      {
         return true;
      }
      if ($type == '8')
      {
         return true;
      }
      if ($type == '9')
      {
         return true;
      }
      if ($type == '10')
      {
         return true;
      }


   // End of Detailed notification functino
   }


   // This sends a notification to a single user
   function Single($user_id, $subject, $body)
   {

      global $db;
      global $fs;
      global $notify_text;
      global $details_text;
      global $project_prefs;
      global $flyspray_prefs;
      global $current_user;

      $user_details = $fs->getUserDetails($user_id);

      //$body = $notify_text['donotreply'] . "\n\n" . $body . "\n\n" . $notify_text['disclaimer'];

      // Email
      if ($flyspray_prefs['user_notify'] == '2'
          OR ($flyspray_prefs['user_notify'] = '1' && $user_details['notify_type'] == '1'))
      {
         $this->SendEmail($user_details['email_address'], $subject, $body);

      // Jabber
      } elseif ($flyspray_prefs['user_notify'] == '3'
          OR ($flyspray_prefs['user_notify'] = '1' && $user_details['notify_type'] == '2'))
      {
         $this->SendJabber($user_details['email_address'], $subject, $body);
      }

   // End of Single function
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

      $body = $notify_text['donotreply'] . "\n\n" . $body . "\n\n" . $notify_text['disclaimer'];

      $task_details = $fs->GetTaskDetails($task_id);

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
