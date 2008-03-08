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


if ($conf['general']['reminder_daemon'] && (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || php_sapi_name() === 'cli')) {

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
    $reminders = $db->x->getAll("SELECT  r.reminder_message AS message, r.*
                                   FROM  {reminders} r
                             INNER JOIN  {users}     u ON u.user_id = r.to_user_id
                             INNER JOIN  {tasks}     t ON r.task_id = t.task_id
                             INNER JOIN  {projects}  p ON t.project_id = p.project_id
                                  WHERE  t.is_closed = '0' AND r.start_time < ?
                                                           AND r.last_sent + r.how_often < ?
                               ORDER BY  r.reminder_id", null, array(time(), time()));

    foreach ($reminders as $row) {
        if (Notifications::send_now($row['to_user_id'], ADDRESS_USER, NOTIFY_REMINDER, $row)) {
           // Update the database with the time sent
           $db->x->execParam('UPDATE  {reminders}
                            SET  last_sent = ?
                          WHERE  reminder_id = ?', null, 
                         array(time(), $row['reminder_id']));
        }
    }

    ############ Task two: send stored notifications ############
    Notifications::send_stored();

    ############ Task three: send project manager digests ############
    $sql = $db->x->getAll('SELECT project_id, project_title, last_digest, project_prefix
                         FROM {projects}
                        WHERE send_digest = 1 AND last_digest < ?',
                        null, time() - 60*60*24*7);
    foreach ($sql as $project) {
        // find out all project managers
        $pms = $db->x->GetCol('SELECT uig.user_id
                              FROM {users_in_groups} uig
                         LEFT JOIN {groups} g ON uig.group_id = g.group_id
                             WHERE g.project_id = ? AND g.manage_project = 1',
                             null, $project['project_id']);

        // Now generate the message, we are interested in opened/reopened, closed and assigned tasks
        $opened = $reopened = $closed = $assigned = array();
        $message = L('digestfor') . ' ' . $project['project_title'] . ":\n\n";
        $evt = $db->x->getAll('SELECT h.event_type, lr.item_name AS resolution_name, t.task_id,
                                    t.item_summary, u.user_name, u.real_name, t.prefix_id
                               FROM {history} h
                          LEFT JOIN {tasks} t ON h.task_id = t.task_id
                          LEFT JOIN {users} u ON h.user_id = u.user_id
                          LEFT JOIN {list_items} lr ON h.new_value = lr.list_item_id AND event_type = 2
                              WHERE t.project_id = ? AND event_type IN (1,2,13,19)
                                    AND event_date > ?', null,
                              array($project['project_id'], max($project['last_digest'], time() - 60*60*24*7)));

        foreach ($evt as $row) {
            switch ($row['event_type'])
            {
                case '1':
                    $opened[] = sprintf("%s#%d: %s\n-> %s %s (%s)",
                                         $project['project_prefix'], $row['prefix_id'], $row['item_summary'], L('openedby'), $row['real_name'], $row['user_name']);
                    break;
                case '2':
                    $closed[] = sprintf("%s#%d: %s (%s)\n-> %s %s (%s)",
                                         $project['project_prefix'], $row['prefix_id'], $row['item_summary'], $row['resolution_name'], L('openedby'), $row['real_name'], $row['user_name']);
                    break;
                case '13':
                    $reopened[] = sprintf("%s#%d: %s\n-> %s %s (%s)",
                                         $project['project_prefix'], $row['prefix_id'], $row['item_summary'], L('reopenedby'), $row['real_name'], $row['user_name']);
                    break;
                case '19':
                    $assigned[] = sprintf("%s#%d: %s\n-> %s %s (%s)",
                                         $project['project_prefix'], $row['prefix_id'], $row['item_summary'], L('assignedto'), $row['real_name'], $row['user_name']);
                    break;
            }
        }

        foreach (array('opened', 'closed', 'reopened', 'assigned') as $type) {
            if (count($$type)) {
                $message .= L($type . 'tasks') . ':' . "\n" . implode("\n", $$type) . "\n\n";
            }
        }

        if (Notifications::send_now($pms, ADDRESS_USER, NOTIFY_DIGEST, array('message' => $message))) {
            $db->x->execParam('UPDATE {projects} SET last_digest = ? WHERE project_id = ?',
                        array(time(), $project['project_id']));
        }
    }

    ############ Task four: Close tasks ############
    $tasks = $db->query('SELECT t.*, c.date_added, max(c.date_added) FROM {tasks} t
                        LEFT JOIN {comments} c ON t.task_id = c.task_id
                            WHERE is_closed = 0 AND close_after > 0
                         GROUP BY t.task_id');
    while ($row = $tasks->FetchRow()) {
        if (max($row['date_added'], $row['last_edited_time']) + $row['close_after'] < time()) {
            Backend::close_task($row['task_id'], $row['resolution_reason'], $row['closure_comment'], false);
            $db->x->execParam('UPDATE {tasks} SET close_after = 0 WHERE task_id = ?', $row['task_id']);
        }
    }

    //wait 10 minutes for the next loop.
    if (php_sapi_name() !== 'cli') {
        sleep(600);
    }

} while(php_sapi_name() !== 'cli'); //forever ¡¡¡ ( oh well. a least will not stop unless killed or the server restarted)

@register_shutdown_function('unlink', Flyspray::get_tmp_dir() . '/flysprayreminders.run');

} else {

    die('You are not authorized to start the remider daemon.');
}

?>
