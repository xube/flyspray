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
    
    ############ Task one: Send reminders ############
    $get_reminders = $db->Query("SELECT  r.reminder_message AS message, r.*
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

    ############ Task two: send stored notifications ############
    Notifications::send_stored();
    
    ############ Task three: send project manager digests ############
    $sql = $db->Query('SELECT project_id, project_title, last_digest
                         FROM {projects}
                        WHERE send_digest = 1 AND last_digest < ?',
                        array(time() - 60*60*24*7));
    while ($project = $db->FetchRow($sql)) {
        // find out all project managers
        $pms = $db->Query('SELECT uig.user_id
                             FROM {users_in_groups} uig
                        LEFT JOIN {groups} g ON uig.group_id = g.group_id
                            WHERE g.project_id = ? AND g.manage_project = 1',
                            array($project['project_id']));
        
        // Now generate the message, we are interested in opened/reopened, closed and assigned tasks
        $opened = $reopened = $closed = $assigned = array();
        $message = L('digestfor') . ' ' . $project['project_title'] . ":\n\n";
        $sql = $db->Query('SELECT h.event_type, lr.item_name AS resolution_name, t.task_id, t.item_summary, u.user_name, u.real_name
                             FROM {history} h
                        LEFT JOIN {tasks} t ON h.task_id = t.task_id
                        LEFT JOIN {users} u ON h.user_id = u.user_id
                        LEFT JOIN {list_items} lr ON h.new_value = lr.list_item_id AND event_type = 2
                            WHERE t.project_id = ? AND event_type IN (1,2,13,19)
                                  AND event_date > ?',
                            array($project['project_id'], max($project['last_digest'], time() - 60*60*24*7)));
                            
        while ($row = $db->FetchRow($sql)) {
            switch ($row['event_type'])
            {
                case '1':
                    $opened[] = sprintf("FS#%d: %s\n-> %s %s (%s)",
                                         $row['task_id'], $row['item_summary'], L('openedby'), $row['real_name'], $row['user_name']);
                    break;
                case '2':
                    $closed[] = sprintf("FS#%d: %s (%s)\n-> %s %s (%s)",
                                         $row['task_id'], $row['item_summary'], $row['resolution_name'], L('openedby'), $row['real_name'], $row['user_name']);
                    break;
                case '13':
                    $reopened[] = sprintf("FS#%d: %s\n-> %s %s (%s)",
                                         $row['task_id'], $row['item_summary'], L('reopenedby'), $row['real_name'], $row['user_name']);
                    break;
                case '19':
                    $assigned[] = sprintf("FS#%d: %s\n-> %s %s (%s)",
                                         $row['task_id'], $row['item_summary'], L('assignedto'), $row['real_name'], $row['user_name']);
                    break;
            }
        }

        foreach (array('opened', 'closed', 'reopened', 'assigned') as $type) {
            if (count($$type)) {
                $message .= L($type . 'tasks') . ':' . "\n" . implode("\n", $$type) . "\n\n";
            }
        }
        
        if (Notifications::send_now($db->FetchCol($pms), ADDRESS_USER, NOTIFY_DIGEST, array('message' => $message))) {
            $db->Query('UPDATE {projects} SET last_digest = ? WHERE project_id = ?',
                        array(time(), $project['project_id']));
        }
    }
    
    
    //wait 10 minutes for the next loop.
    sleep(600);

} while(true); //forever ¡¡¡ ( oh well. a least will not stop unless killed or the server restarted)

@register_shutdown_function('unlink', Flyspray::get_tmp_dir() . '/flysprayreminders.run');

} else {

    die("you are not authorized to start the remider daemon");
}

?>
