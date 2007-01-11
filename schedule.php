<?php

  /********************************************************\
  | Scheduled Jobs (poor man's cron)                       |
  | ~~~~~~~~~~~~~~                                         |
  | This script checks for pending scheduled notifications |
  | and sends them at the right time.                      |
  \********************************************************/

define('IN_FS', true);

/**
 * Developers warning :
 * Be aware while debugging this, it actually daemonize ¡¡
 * it runs **forever** in the background every ten minutes
 * to simulate a real cron task, it WONT STOP if you click
 * stop in your browser, it will only stop if you restart
 * your webserver.
 */

require_once 'header.php';


if((isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') &&
    (isset($conf['general']['reminder_daemon']) && $conf['general']['reminder_daemon'] == '1')) {

//keep going, execute the script in the background
ignore_user_abort(true);
set_time_limit(0);

include_once BASEDIR . '/includes/class.notify.php';

do {
    //we touch the file on every single iteration to avoid
    //the possible restart done by Startremiderdaemon method
    //in class.flyspray.conf 
touch(Flyspray::get_tmp_dir() . '/flysprayreminders.run');
    
$user = new User(0);
$now = time();

$get_reminders = $db->Query("SELECT  r.*
                               FROM  {reminders} r
                         INNER JOIN  {users}     u ON u.user_id = r.to_user_id
                         INNER JOIN  {tasks}     t ON r.task_id = t.task_id
                         INNER JOIN  {projects}  p ON t.project_id = p.project_id
                              WHERE  t.is_closed = '0' AND r.start_time < ?
                                                       AND r.last_sent + r.how_often < ?
                           ORDER BY  r.reminder_id", array(time(), time()));

while ($row = $db->FetchRow($get_reminders)) {
    if (Notifications::send_now($row['to_user_id'], ADDRESS_USER, NOTIFY_REMINDER, $row)) {
       // Update the database with the time sent
       $db->Query("UPDATE  {reminders}
                      SET  last_sent = ?
                    WHERE  reminder_id = ?",
                   array(time(), $row['reminder_id']));
    }
}

// send those stored notifications
Notifications::send_stored();
//wait 10 minutes for the next loop.
sleep(600);

//forever ¡¡¡ ( oh well. a least will not stop unless killed or the server restarted)
} while(true);

@register_shutdown_function('unlink', Flyspray::get_tmp_dir() . '/flysprayreminders.run');

} else {

    die("you are not authorized to start the remider daemon");
}

?>
