<?php
// This script checks for pending scheduled notifications
// and sends them at the right time.
include('../header.php');

$now = date(U);

$get_reminders = $fs->dbQuery("SELECT * FROM flyspray_reminders ORDER BY reminder_id");

// Cycle through all reminders in the database table
while ($row = $fs->dbFetchRow($get_reminders)) {

	// Check to see if it's time to send a reminder
	if (($row['start_time'] < $now) && (($row['last_sent'] + $row['how_often']) < $now)) {

		// Send the reminder

        $lang = $flyspray_prefs['lang_code'];
        require("../lang/$lang/functions.inc.php");

        $jabber_users = array();
        $email_users = array();

		// Get the user's notification type and address
        $get_details = $fs->dbQuery("SELECT notify_type, jabber_id, email_address
                FROM flyspray_users
                WHERE user_id = ?",
                array($row['to_user_id']));

        while ($subrow = $fs->dbFetchArray($get_details)) {

            if (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '1')
                    OR ($flyspray_prefs['user_notify'] == '2')) {
                array_push($email_users, $subrow['email_address']);
            } elseif (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '2')
                    OR ($flyspray_prefs['user_notify'] == '3')) {
                array_push($jabber_users, $subrow['jabber_id']);
            };
        };

        $subject = $functions_text['notifyfrom'] . " " . $project_prefs['project_title'];
        $message = stripslashes($row['reminder_message']);

        // Pass the recipients and message onto the Jabber Message function
        $fs->JabberMessage(
                $flyspray_prefs['jabber_server'],
                $flyspray_prefs['jabber_port'],
                $flyspray_prefs['jabber_username'],
                $flyspray_prefs['jabber_password'],
                $jabber_users,
                $subject,
                $message,
                "Flyspray"
                );


        // Pass the recipients and message onto the mass email function
        $fs->SendEmail($email_users, $subject, $message);
		
		// Update the database with the time sent
		$update_db = $fs->dbQuery("UPDATE flyspray_reminders SET last_sent = ? WHERE reminder_id = ?", array($now, $row['reminder_id']));

		// Debug
		echo "Reminder Sent!<br>";

	};
};


?>

<html>
<head>
<title>Scheduled Reminders</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body>
<h1>Nothing to see here.</h1>
This is a backend script that really isn't meant to be displayed in your browser.
</body>
</html>
