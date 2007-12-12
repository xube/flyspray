<?php

class Flyspray {

   var $dbhost;
   var $dbuser;
   var $dbpass;
   var $dbname;
   var $dbtype;

   function getGlobalPrefs() {
      $get_prefs = $this->dbQuery("SELECT pref_name, pref_value FROM flyspray_prefs");

      $global_prefs = array();

      while (list($pref, $value) = $this->dbFetchRow($get_prefs)) {
         $temp_array = array("$pref"  => "$value");
         $global_prefs = $global_prefs + $temp_array;
      }

      return $global_prefs;
   }
   
   function dbOpen($dbhost = '', $dbuser = '', $dbpass = '', $dbname = '', $dbtype = '') {
   
      $this->dbhost = $dbhost;
      $this->dbuser = $dbuser;
      $this->dbpass = $dbpass;
      $this->dbname = $dbname;
      $this->dbtype = $dbtype;
   
      $dblink = mysql_connect($dbhost, $dbuser, $dbpass);
      mysql_select_db($dbname);

      return $dblink;
   }

   function dbClose() {
      mysql_close ();
   }

   function dbQuery($sql) {
	   $result =  mysql_query($sql) or die (mysql_error());
   	return $result;
   }

   function dbCountRows($result) {
	   $num_rows = mysql_num_rows($result);
   	return $num_rows;
   }

   function dbFetchRow($result) {
	   $row = mysql_fetch_row($result);
   	return $row;
   }

   function dbFetchArray($result) {
	   $db_array = mysql_fetch_array($result);
   	return $db_array;
   }

 // Thanks to Mr Lance Conry for this query that saved me a lot of effort.
// Check him out at http://www.rhinosw.com/
function GetTaskDetails($task_id) {

   $flyspray_prefs = $this->GetGlobalPrefs();
   $lang = $flyspray_prefs['lang_code'];

	$get_details = $this->dbQuery("SELECT *,
						vr.version_name as reported_version_name,
						vd.version_name as due_in_version_name
						FROM flyspray_tasks t
						LEFT JOIN flyspray_list_category c ON t.product_category = c.category_id
						LEFT JOIN flyspray_list_os o ON t.operating_system = o.os_id
						LEFT JOIN flyspray_list_resolution r ON t.resolution_reason = r.resolution_id
						LEFT JOIN flyspray_list_tasktype tt ON t.task_type = tt.tasktype_id
						LEFT JOIN flyspray_list_version vr ON t.product_version = vr.version_id
						LEFT JOIN flyspray_list_version vd ON t.closedby_version = vd.version_id
   
						WHERE t.task_id = '$task_id'
						");
	$get_details = $this->dbFetchArray($get_details);

	$status_id = $get_details['item_status'];
    require("lang/$lang/status.php");
	$tmp_array = array("status_name" => $status_list[$status_id]);
	$get_details = $get_details + $tmp_array;

	$severity_id = $get_details['task_severity'];
    require("lang/$lang/severity.php");
	$tmp_array = array("severity_name" => $severity_list[$severity_id]);
	$get_details = $get_details + $tmp_array;

	return $get_details;
}


// Thank you to Mr Lance Conry for this awesome, FAST Jabber message function
// Check him out at http://www.rhinosw.com/
function JabberMessage( $sHost, $sPort, $sUsername, $sPassword, $vTo, $sSubject, $sBody, $sClient='Flyspray' ) {
   
   $flyspray_prefs = $this->GetGlobalPrefs();

   // We can only sent jabber messages if the jabber setup is done
   if ($flyspray_prefs['jabber_server'] != ''
     && $flyspray_prefs['jabber_port'] != ''
     && $flyspray_prefs['jabber_username'] != ''
     && $flyspray_prefs['jabber_password'] != ''
	 && ($flyspray_prefs['user_notify'] == '1'
	 OR $flyspray_prefs['user_notify'] == '3')
   ) {
   
   $socket = fsockopen ( $sHost, $sPort, $errno, $errstr, 30 );
   if ( !$socket ) {
      return '$errstr (' . $errno . ')';
   } else {

   fputs($socket, '<?xml version="1.0" encoding="UTF-8"?><stream:stream to="' . $sHost . '" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">' );
   echo fgets( $socket, 1 );

   fputs( $socket, '<iq type="set" id="AUTH_01"><query xmlns="jabber:iq:auth"><username>' . $sUsername . '</username><password>' . $sPassword . '</password><resource>' . $sClient . '</resource></query></iq>' );
   echo fgets( $socket, 1 );

   if ( is_array( $vTo )) {
       foreach ($vTo as $sTo) {
           //fputs ( $socket, '<message to="' . $sTo . '" ><body>' . $sBody . '</body><subject>' . $sSubject . '</subject></message>' );
           fputs ( $socket, '<message to="' . $sTo . '" ><body><![CDATA[' . $sBody . ']]></body><subject><![CDATA[' . $sSubject . ']]></subject></message>' );
       }
    }
    else {
       fputs ( $socket, '<message to="' . $vTo . '" ><body>' . $sBody . '</body><subject>' . $sSubject . '</subject></message>' );
    }
   
   fclose ( $socket );
   }
   
   // End of checking that jabber is set up
   }
   
   return TRUE;
}

   function SendEmail($to, $message) {

   $flyspray_prefs = $this->GetGlobalPrefs();

   $lang = $flyspray_prefs['lang_code'];
   require("lang/$lang/functions.inc.php");

   if (is_array($to)) {

     foreach($to as $address) {

	   $subject = "{$functions_text['notifyfrom']} \"{$flyspray_prefs['project_title']}\"";
	   $headers = "From: {$flyspray_prefs['project_title']} <{$flyspray_prefs['admin_email']}>\r\n";

	   $message = str_replace('&amp;', '&', $message);
	   $message = "
========================================================
{$functions_text['autogenerated']}
========================================================
\n\n" . $message;

	   mail ($address, $subject, $message, $headers);
	 };

   } else {

	$subject = "{$functions_text['notifyfrom']} \"{$flyspray_prefs['project_title']}\"";
	$headers = "From: {$flyspray_prefs['project_title']} <{$flyspray_prefs['admin_email']}>\r\n";

	$message = str_replace('&amp;', '&', $message);
	$message = "
========================================================
{$functions_text['autogenerated']}
========================================================
\n\n" . $message;

	mail ($to, $subject, $message, $headers);

	};
	return "<br />";
   // End of mass mail function
   }

   // This function sends out basic messages at specified times.
   function SendBasicNotification($to, $message) {

       $flyspray_prefs = $this->GetGlobalPrefs();

       $lang = $flyspray_prefs['lang_code'];
       require("lang/$lang/functions.inc.php");

	   $get_user_details = $this->dbQuery("SELECT real_name, jabber_id, email_address, notify_type FROM flyspray_users WHERE user_id = '$to'");
	   list($real_name, $jabber_id, $email_address, $notify_type) = $this->dbFetchArray($get_user_details);

	   // if app preferences say to use jabber, or if the user can (and has) selected jabber
	   // and the jabber options are entered in the applications preferences
	   if (($flyspray_prefs['user_notify'] == '3')
	     OR ($flyspray_prefs['user_notify'] == '1' && $notify_type == '2')

		 && $flyspray_prefs['jabber_server'] != ''
	     && $flyspray_prefs['jabber_port'] != ''
	     && $flyspray_prefs['jabber_username'] != ''
	     && $flyspray_prefs['jabber_password'] != ''
	     ) {

	     $message = stripslashes($message);

		$this->JabberMessage(
		  				$flyspray_prefs['jabber_server'],
						$flyspray_prefs['jabber_port'],
						$flyspray_prefs['jabber_username'],
						$flyspray_prefs['jabber_password'],
						$jabber_id,
						"{$functions_text['notifyfrom']} {$flyspray_prefs['project_title']}",
						$message,
						"Flyspray"
						);
		return "<br>";

	    // if app preferences say to use email, or if the user can (and has) selected email
		} elseif (($flyspray_prefs['user_notify'] == '2') OR ($flyspray_prefs['user_notify'] == '1' && $notify_type == '1')) {

			$to = "$real_name <$email_address>";
			$this->SendEmail($to, $message);

			/*$subject = "Notification from \"{$flyspray_prefs['project_title']}\"";
			$headers = "From: {$flyspray_prefs['project_title']} <{$flyspray_prefs['admin_email']}>";

			$message = str_replace('&amp;', '&', $message);
			$message = "
========================================================
THIS IS AN AUTOMATICALLY GENERATED MESSAGE, DO NOT REPLY
========================================================
\n\n" . $message;

			mail ($to, $subject, $message, $headers);
			//return "Sent email notifications.<br>";*/
		};
	// End of basic notification function
   }


   // Detailed notification function - generates and passes arrays of recipients
   // These are the additional people who want to be notified of a task changing
   function SendDetailedNotification($task_id, $message) {

   $flyspray_prefs = $this->GetGlobalPrefs();

   $lang = $flyspray_prefs['lang_code'];
   require("lang/$lang/functions.inc.php");

   $jabber_users = array();
   $email_users = array();

   $get_users = $this->dbQuery("SELECT user_id FROM flyspray_notifications WHERE task_id = '$task_id'");

   while ($row = $this->dbFetchArray($get_users)) {

      $get_details = $this->dbQuery("SELECT notify_type, jabber_id, email_address
	  								FROM flyspray_users
									WHERE user_id = '{$row['user_id']}'
									");
      while ($subrow = $this->dbFetchArray($get_details)) {

		if (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '1')
			OR ($flyspray_prefs['user_notify'] == '2')) {
			array_push($email_users, $subrow['email_address']);
		} elseif (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '2')
			OR ($flyspray_prefs['user_notify'] == '3')) {
			array_push($jabber_users, $subrow['jabber_id']);
		};
	  };
   };

	    $message = stripslashes($message);

			// Pass the recipients and message onto the Jabber Message function
			$this->JabberMessage(
		  					$flyspray_prefs['jabber_server'],
							$flyspray_prefs['jabber_port'],
							$flyspray_prefs['jabber_username'],
							$flyspray_prefs['jabber_password'],
							$jabber_users,
							"{$functions_text['notifyfrom']} {$flyspray_prefs['project_title']}",
							$message,
							"Flyspray"
							);

		
			// Pass the recipients and message onto the mass email function
			$this->SendEmail($email_users, $message);

    return "<br>";
   // End of detailed notification function
   }



   // This function generates a query of users for the "Assigned To" list
   function listUserQuery() {
     $flyspray_prefs = $this->getGlobalPrefs();

     $query = "SELECT * FROM flyspray_users WHERE account_enabled = '1'";

	 $these_groups = explode(" ", $flyspray_prefs['assigned_groups']);
	 while (list($key, $val) = each($these_groups)) {
	 	if (!isset($first_done)) {
	 		$query = $query . " AND group_in = '$val'";
			$first_done = 'yes';
		} else {
			$query = $query . " OR group_in = '$val'";
		};
	};
	$user_query = $query . " ORDER BY group_in ASC";
	return $user_query;
  }


  // This provides funky page numbering
  // Thanks to Nathan Fritz for this.  http://www.netflint.net/
  function pagenums($pagenum, $perpage, $pagesper, $totalcount, $extraurl)
{
$flyspray_prefs = $this->GetGlobalPrefs();
$lang = $flyspray_prefs['lang_code'];
require("lang/$lang/functions.inc.php");
    if ($pagenum - 1000 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 1000) . $extraurl . "\">{$functions_text['back']} 1,000</a> - ";
    if ($pagenum - 100 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 100) . $extraurl . "\">{$functions_text['back']} 100</a> - ";
    if ($pagenum - 10 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 10) . $extraurl . "\">{$functions_text['back']} 10</a> - ";
    if ($pagenum - 1 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 1) . $extraurl . "\">{$functions_text['back']}</a> - ";
    $start = floor($pagenum - ($pagesper / 2)) + 1;
    if ($start <= 0) $start = 0;
    $finish = $pagenum + ceil($pagesper / 2);
    if ($finish > $totalcount / $perpage) $finish = floor($totalcount / $perpage);
    for ($pagelink = $start; $pagelink <= $finish;  $pagelink++)
    {
        if ($pagelink != $start) $output .= " - ";
        if ($pagelink == $pagenum) {
            $output .= "<a href=\"?pagenum=" . ($pagelink) . "$extraurl\"><b>" . ($pagelink + 1) . "</b></a>";
        } else {
            $output .= "<a href=\"?pagenum=" . ($pagelink) . "$extraurl\">" . ($pagelink + 1) . "</a>";
        }
    }
    if ($pagenum + 1 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1) . $extraurl . "\">{$functions_text['forward']}</a> ";
    if ($pagenum + 10 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 10) . $extraurl . "\">{$functions_text['forward']} 10</a> ";
    if ($pagenum + 100 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 100) . $extraurl . "\">{$functions_text['forward']} 100</a> ";
    if ($pagenum + 1000 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1000) . $extraurl . "\">{$functions_text['forward']} 1,000</a> ";
    return $output;
}

// End of Flyspray class
}

?>
