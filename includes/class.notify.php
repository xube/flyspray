<?php
/*
   ---------------------------------------------------
   | This script contains the notification functions |
   ---------------------------------------------------
*/

require BASEDIR . '/includes/external/swift-mailer/Swift.php';

class NotificationsThread extends Swift_Events_Listener {

    var $task_id = 0;
    var $thread_info = array();
    var $message_history = array();

    function NotificationsThread($task_id, $db)
    {
        $this->task_id = $task_id;
        $this->message_history = $db->GetAll('SELECT recipient_id, message_id FROM
                                               {notification_threads} WHERE task_id = ?', array($task_id));
    }

    /**
     * beforeSendPerformed
     * do this beforeSendPerformed aint that clear ? ;)
     * @param object &$e
     * @access public
     * @return void
     */
    function beforeSendPerformed(&$e) {

        $message = $e->getMessage();
        $recipients = $e->getRecipients();
        $to = $recipients->getTo();
        $to = array_pop($to);
        $to = ezmlm_hash($to->getAddress());
        $references = array();

        if(count($this->message_history)) {

            foreach($this->message_history as $history) {

                if($history['recipient_id'] != $to) {
                    continue;
                }

                $references[] = $history['message_id'];
            }
            // if there is more than one then we have a conversation..
            if(count($references)) {
                //"The last identifier in References identifies the parent"
                $message->headers->set('References', implode(" ", array_reverse($references)));
            }
        }

        $this->thread_info[] = array($this->task_id, $to, $message->generateId('flyspray'));
    }
}

class Notifications
{
    /**
     * Just decides whether or not to send in background
     * @param mixed $to string or array...the type of address (email, task ID, user ID) is specified below
     * @param integer $to_type type of $to address
     * @param integer $type type of notification
     * @param array $data additional info needed for notification
     * @access public
     * @return bool
     */
    function send($to, $to_type, $type, $data = array())
    {
        global $fs;

        $proj =& new Project(0);
        $data['project'] = $proj;
        $data['notify_type'] = $type;

        if ($fs->prefs['send_background'] && $to_type != ADDRESS_EMAIL) {
            return Notifications::send_later($to, $to_type, $type, $data);
        } else {
            return Notifications::send_now($to, $to_type, $type, $data);
        }
    }

    /**
     * Sends notifications *now*
     * @param mixed $to string or array...the type of address (email, task ID, user ID) is specified below
     * @param integer $to_type type of $to address
     * @param integer $type type of notification
     * @param array $data additional info needed for notification
     * @access public
     * @return bool
     */
    function send_now($to, $to_type, $type, $data = array())
    {
        global $db, $fs, $proj;

        $emails = array();
        $jids = array();
        $result = true;

        if(defined('FS_NO_MAIL')) {
            return true;
        }

        switch ($to_type)
        {
            case ADDRESS_DONE:
                // from send_stored()
                list($emails, $jids) = $to;
                $data = unserialize($data['message_data']);
                $subject = $data['subject'];
                $body = $data['body'];
                break;

            case ADDRESS_EMAIL:
                // this happens on email confirmation, when no user exists
                $emails = (is_array($to)) ? $to : array($to);
                break;

            case ADDRESS_USER:
                // list of user IDs
                list($emails, $jids) = Notifications::user_to_address($to, $type);
                break;

            case ADDRESS_TASK:
                // now we need everyone on the notification list and the assignees
                list($emails, $jids) = Notifications::task_notifications($to, $type, ADDRESS_EMAIL);
                $data['task_id'] = $to;
                break;
        }

        if (isset($data['task_id'])) {
            $data['task'] = Flyspray::getTaskDetails($data['task_id']);
            // we have project specific options
            $pid = $db->GetOne('SELECT project_id FROM {tasks} WHERE task_id = ?', array($data['task_id']));
            $data['project'] = new Project($pid);
        }

        if ($to_type != ADDRESS_DONE) {
            list($subject, $body) = Notifications::generate_message($type, $data);
        }

        if (isset($data['task_id'])) {
            // Now, we add the project contact addresses,
            // but only if the task is public
            $data['task'] = Flyspray::getTaskDetails($data['task_id']);
            if ($data['task']['mark_private'] != '1' && in_array($type, Flyspray::int_explode(' ', $data['project']->prefs['notify_types'])))
            {
                $proj_emails = preg_split('/[\s,;]+/', $proj->prefs['notify_email'], -1, PREG_SPLIT_NO_EMPTY);
                $proj_jids   = preg_split('/[\s,;]+/', $proj->prefs['notify_jabber'], -1, PREG_SPLIT_NO_EMPTY);

                $emails = array_merge($proj_emails, $emails);
                $jids   = array_merge($proj_jids, $emails);
            }
        }

        // Now we start sending
        if (count($emails)) {
            // first choose method
            if ($fs->prefs['smtp_server']) {
                Swift_ClassLoader::load('Swift_Connection_SMTP');
                // connection... SSL, TLS or none
                if ($fs->prefs['email_ssl']) {
                    $connection =& new Swift_Connection_SMTP($fs->prefs['smtp_server'], SWIFT_SMTP_PORT_SECURE, SWIFT_SMTP_ENC_SSL);
                } else if ($fs->prefs['email_tls']) {
                    $connection =& new Swift_Connection_SMTP($fs->prefs['smtp_server'], SWIFT_SMTP_PORT_SECURE, SWIFT_SMTP_ENC_TLS);
                } else {
                    $connection =& new Swift_Connection_SMTP($fs->prefs['smtp_server']);
                }
                if ($fs->prefs['smtp_user']) {
                    $connection->setUsername($fs->prefs['smtp_user']);
                    $connection->setPassword($fs->prefs['smtp_pass']);
                }
                if(defined('FS_SMTP_TIMEOUT')) {
                    $swiftconn->setTimeout(FS_SMTP_TIMEOUT);
                }
            } else {
                Swift_ClassLoader::load('Swift_Connection_NativeMail');
                $connection =& new Swift_Connection_NativeMail;
            }
            $swift =& new Swift($connection);

            if (isset($data['task_id'])) {
                $swift->attachPlugin(new NotificationsThread($data['task_id'], $db), 'MessageThread');
            }

            if (defined('FS_MAIL_DEBUG')) {
                $swift->log->enable();
                 Swift_ClassLoader::load('Swift_Plugin_VerboseSending');
                $view =& new Swift_Plugin_VerboseSending_DefaultView();
                $swift->attachPlugin(new Swift_Plugin_VerboseSending($view), "verbose");
            }

            $message =& new Swift_Message($subject, $body);
            // check for reply-to
            if (isset($data['project']) && $data['project']->prefs['notify_reply']) {
                $message->setReplyTo($data['project']->prefs['notify_reply']);
            }

            if (isset($data['project']) && isset($data['project']->prefs['bounce_address'])) {
                $message->setReturnPath($data['project']->prefs['bounce_address']);
            }

            $message->headers->setCharset('utf-8');
            $message->headers->set('Precedence', 'list');
            $message->headers->set('X-Mailer', 'Flyspray');

            // Add custom headers, possibly
            if (isset($data['headers'])) {
                $headers = array_map('trim', explode("\n", $data['headers']));
                foreach ($headers as $header) {
                    list($name, $value) = explode(':', $header);
                    $message->headers->set($name, $value);
                }
            }

            $recipients =& new Swift_RecipientList();
            $recipients->addTo($emails);

            // && $result purpose: if this has been set to false before, it should never become true again
            // to indicate an error
            $result = ($swift->batchSend($message, $recipients, $fs->prefs['admin_email']) === count($emails)) && $result;

            if (isset($data['task_id'])) {

                $plugin =& $swift->getPlugin('MessageThread');

                $db->Execute('INSERT INTO {notification_threads} (task_id, recipient_id, message_id)
                                   VALUES (?, ?, ?)', $plugin->thread_info);
            }

            $swift->disconnect();
        }

        if (count($jids)) {
            $jids = array_unique($jids);
            if (!$fs->prefs['jabber_username'] ||
                !$fs->prefs['jabber_password']) {
                return false;
            }

            // nothing that can't be guessed correctly ^^
            if (!$fs->prefs['jabber_port']) {
                $fs->prefs['jabber_port'] = 5222;
            }

            include  BASEDIR . '/includes/class.jabber2.php';

            $jabber =& new Jabber($fs->prefs['jabber_username'],
                                 $fs->prefs['jabber_password'],
                                 $fs->prefs['jabber_security'],
                                 $fs->prefs['jabber_port'],
                                 $fs->prefs['jabber_server']);
            $jabber->login();

            foreach ($jids as $jid) {
                $result = $jabber->send_message($jid, $body, $subject, 'normal') && $result;
            }
        }

        return $result;
    }

    /**
     * Sends notifications *later*, so stores them in the database
     * @param mixed $to string or array...the type of address (email, task ID, user ID) is specified below
     * @param integer $to_type type of $to address
     * @param integer $type type of notification
     * @param array $data additional info needed for notification
     * @access public
     * @return bool
     */
    function send_later($to, $to_type, $type, $data = array())
    {
        global $db, $user;

        // we only "send later" to registered users
        if ($to_type == ADDRESS_EMAIL) {
            return false;
        }

        if ($to_type == ADDRESS_TASK) {
            $data['task_id'] = $to;
            list(,, $to) = Notifications::task_notifications($to, $type, ADDRESS_USER);
        } // otherwise we already have a list of users

        $to = (array) $to;

        if (isset($data['task_id'])) {
            $data['task'] = Flyspray::getTaskDetails($data['task_id']);
            // we have project specific options
            $pid = $db->GetOne('SELECT project_id FROM {tasks} WHERE task_id = ?', array($data['task_id']));
            $data['project'] =& new Project($pid);
        }

        list($data['subject'], $data['body']) = Notifications::generate_message($type, $data);
        $time = time(); // on a sidenote: never do strange things like $date = time() or $time = date('U');

        // just in case
        if (!$db->Execute('INSERT INTO {notification_messages}
                                (message_data, time_created)
                         VALUES (?, ?)',
                        array(serialize($data), $time))) {
            return false;
        }

        // ugly but postgre doesn't give us a choice?
        $message_id = $db->GetOne('SELECT message_id
                                     FROM {notification_messages}
                                    WHERE time_created = ?
                                 ORDER BY message_id DESC',
                                    array($time), 1);

        foreach ($to as $user_id) {
            if ($user_id == $user->id && !$user->infos['notify_own']) {
                continue;
            }

            $db->Execute('INSERT INTO {notification_recipients}
                                    (message_id, user_id)
                             VALUES (?, ?)', array($message_id, $user_id));
        }

        return true;
    }

    /**
     * Sends notifications already stored in the DB by send_later()
     * @access public
     * @return bool
     */
    function send_stored()
    {
        global $db;

        $emails = array();
        $jids = array();

        // First we get the messages in chronological order...
        $sql = $db->Execute('SELECT message_id, message_data FROM {notification_messages} ORDER BY time_created DESC');
        while ($row = $sql->FetchRow())
        {
            $data = unserialize($row['message_data']);
            // ...and after that the corresponding recipients
            $rec = $db->Execute('SELECT nr.user_id, u.notify_type, u.notify_own, u.email_address,
                                      u.jabber_id, u.notify_blacklist
                                 FROM {notification_recipients} nr
                            LEFT JOIN {users} u ON nr.user_id = u.user_id
                                WHERE message_id = ?',
                                array($row['message_id']));

            while ($msg = $rec->FetchRow()) {
                Notifications::add_to_list($emails, $jids, $msg, $data['notify_type']);
            }

            if (Notifications::send_now(array($emails, $jids), ADDRESS_DONE, 0, $row)) {
                $db->Execute('DELETE FROM {notification_recipients} WHERE message_id = ?', array($row['message_id']));
                $db->Execute('DELETE FROM {notification_messages} WHERE message_id = ?', array($row['message_id']));
            }
        }
    }

    /**
     * Gets user IDs or addresses needed for a task notification
     * @param integer $task_id
     * @param integer $notify_type
     * @param integer $output addresses or user IDs
     * @access public
     * @return array array($emails, $jids, $users)
     */
    function task_notifications($task_id, $notify_type, $output = ADDRESS_EMAIL)
    {
        global $db, $fs, $user;

        $users = array();
        $jids = array();
        $emails = array();

        // Get list of users from the notification tab
        $users1 = $db->Execute('SELECT u.user_id, u.notify_type, u.notify_own, u.email_address,
                                     u.jabber_id, u.notify_blacklist
                                FROM {notifications} n
                           LEFT JOIN {users} u ON n.user_id = u.user_id
                               WHERE n.task_id = ?',
                               array($task_id));
        // Get assignees
        $users2 = $db->Execute('SELECT u.user_id, u.notify_type, u.notify_own, u.email_address,
                                     u.jabber_id, u.notify_blacklist
                                FROM {assigned} a
                           LEFT JOIN {users} u ON a.user_id = u.user_id
                               WHERE a.task_id = ?',
                               array($task_id));
        $notif_list = array_merge($users1->GetArray(), $users2->GetArray());

        foreach ($notif_list as $row)
        {
            // do not send notifs on own actions if the user does not want to
            if (!$row['user_id'] || $row['user_id'] == $user->id && !$user->infos['notify_own']) {
                continue;
            }

            // if only user IDs are needed, skip the address part
            if ($output == ADDRESS_USER) {
                $users[] = $row['user_id'];
                continue;
            }

            Notifications::add_to_list($emails, $jids, $row, $notify_type);
        }

        return array($emails, $jids, array_unique($users));
    }

    /**
     * Converts user IDs to addresses
     * @param array $users
     * @param integer $notify_type
     * @access public
     * @return array array($emails, $jids)
     */
    function user_to_address($users, $notify_type)
    {
        global $db, $fs, $user;

        $jids = array();
        $emails = array();

        $users = (is_array($users)) ? $users : array($users);

        if (count($users) < 1) {
            return array();
        }

        $sql = $db->Execute('SELECT *
                             FROM {users}
                            WHERE' . substr(str_repeat(' user_id = ? OR ', count($users)), 0, -3),
                           array_values($users));

        while ($row = $sql->FetchRow())
        {
            // do not send notifs on own actions if the user does not want to
            // unless he is the only recipient (confirm code etc.)
            if ($row['user_id'] == $user->id && !$user->infos['notify_own'] && count($users) > 1) {
                continue;
            }

            Notifications::add_to_list($emails, $jids, $row, $notify_type);
        }

        return array($emails, $jids);
    }

    /**
     * Adds a user to $jids/$emails depending on the notification type
     * @param array $emails
     * @param array $jids
     * @param array $row
     * @param integer $notify_type type of current notification
     * @access public
     */
    function add_to_list(&$emails, &$jids, &$row, $notify_type = 0)
    {
        global $fs;

        if ($notify_type && in_array($notify_type, Flyspray::int_explode(' ', $row['notify_blacklist']))) {
            return;
        }

        if (trim($row['email_address']) && (
             ($fs->prefs['user_notify'] == '1' && ($row['notify_type'] == NOTIFY_EMAIL || $row['notify_type'] == NOTIFY_BOTH) )
             || $fs->prefs['user_notify'] == '2'))
        {
            $emails[] = $row['email_address'];
        }

        if (trim($row['jabber_id']) && (
             ($fs->prefs['user_notify'] == '1' && ($row['notify_type'] == NOTIFY_JABBER || $row['notify_type'] == NOTIFY_BOTH) )
             || $fs->prefs['user_notify'] == '3'))
        {
            $jids[] = $row['jabber_id'];
        }
    }

    /**
     * Generates a message depending on the $type and using $data
     * @param integer $type
     * @param array $data usually contains task_id => $id ... using by-ref to add headers
     * @return array array($subject, $body)
     * @access public
     */
    function generate_message($type, &$data = array())
    {
        global $db, $fs, $user;

        // Get the string of modification
        $notify_type_msg = array(
            0 => L('none'),
            NOTIFY_TASK_OPENED     => L('taskopened'),
            NOTIFY_TASK_CHANGED    => L('pm.taskchanged'),
            NOTIFY_TASK_CLOSED     => L('taskclosed'),
            NOTIFY_TASK_REOPENED   => L('pm.taskreopened'),
            NOTIFY_DEP_ADDED       => L('pm.depadded'),
            NOTIFY_DEP_REMOVED     => L('pm.depremoved'),
            NOTIFY_COMMENT_ADDED   => L('commentadded'),
            NOTIFY_REL_ADDED       => L('relatedadded'),
            NOTIFY_OWNERSHIP       => L('ownershiptaken'),
            NOTIFY_PM_REQUEST      => L('pmrequest'),
            NOTIFY_PM_DENY_REQUEST => L('pmrequestdenied'),
            NOTIFY_NEW_ASSIGNEE    => L('newassignee'),
            NOTIFY_REV_DEP         => L('revdepadded'),
            NOTIFY_REV_DEP_REMOVED => L('revdepaddedremoved'),
            NOTIFY_ADDED_ASSIGNEES => L('assigneeadded'),
        );

        // Generate the nofication message
        if (isset($data['project']->prefs['notify_subject']) && !$data['project']->prefs['notify_subject']) {
            $data['project']->prefs['notify_subject'] = '[%p][#%t] %s';
        }
        if (!isset($data['project']->prefs['notify_subject']) || !isset($notify_type_msg[$type]))  {
            $subject = L('notifyfromfs');
        } else {
            $replacement = array('%p' => $data['project']->prefs['project_title'] ,
                                 '%s' => $data['task']['item_summary'],
                                 '%t' => $data['task_id'],
                                 '%a' => $notify_type_msg[$type],
                                 '%u' => $user->infos['user_name'],
                                 '%n' => $type);
            $subject         = strtr($data['project']->prefs['notify_subject'], $replacement);
            $data['headers'] = strtr($data['project']->prefs['mail_headers'], $replacement);
        }

        $subject = strtr($subject, "\r\n", ' ');


        /* -------------------------------
        | List of notification types: |
        | 1. Task opened              |
        | 2. Task details changed     |
        | 3. Task closed              |
        | 4. Task re-opened           |
        | 5. Dependency added         |
        | 6. Dependency removed       |
        | 7. Comment added            |
        | 9. Related task added       |
        |10. Taken ownership          |
        |11. Confirmation code        |
        |12. PM request               |
        |13. PM denied request        |
        |14. New assignee             |
        |15. Reversed dep             |
        |16. Reversed dep removed     |
        |17. Added to assignees list  |
        |18. Anon-task opened         |
        |19. Password change          |
        |20. New user                 |
        -------------------------------
        */

        $body = L('donotreply') . "\r\n\r\n";

        switch ($type)
        {
            case NOTIFY_TASK_OPENED:
                $body .=  L('newtaskopened') . "\r\n\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n\r\n";
                $body .= L('attachedtoproject') . ' - ' .  $data['project']->prefs['project_title'] . "\r\n";
                $body .= L('summary') . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('assignedto') . ' - ' . implode(', ', $data['task']['assigned_to_name']) . "\r\n";
                $body .= L('severity') . ' - ' . $data['task']['severity_name'] . "\r\n";
                foreach ($data['project']->fields as $field) {
                    $body .= $field->prefs['field_name'] . ' - ' . $field->view($data['task'], array(), PLAINTEXT) . "\r\n";
                }
                $body .= L('details') . " - \r\n" . $data['task']['detailed_desc'] . "\r\n\r\n";

                if (isset($data['files'])) {
                    $body .= L('fileaddedtoo') . "\r\n\r\n";
                    $subject .= ' (' . L('attachmentadded') . ')';
                }

                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_TASK_CHANGED:
                $body .= L('taskchanged') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ': ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";

                foreach ($data['changes'] as $change)
                {
                    if ($change[0] == 'assigned_to_name') {
                        $change[1] = implode(', ', $change[1]);
                        $change[2] = implode(', ', $change[2]);
                    }

                    if ($change[0] == 'detailed_desc') {
                        $body .= $change[3] . ":\r\n-------\r\n" . $change[2] . "\r\n-------\r\n";
                    } else {
                        $body .= $change[3] . ': ' . ( ($change[1]) ? $change[1] : '[-]' ) . ' -> ' . ( ($change[2]) ? $change[2] : '[-]' ) . "\r\n";
                    }
                }

                $body .= "\r\n" . L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_TASK_CLOSED:
                $body .=  L('notify.taskclosed') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n\r\n";
                $body .= L('reasonforclosing') . ' ' . $data['task']['resolution_name'] . "\r\n";

                if (!empty($data['task']['closure_comment'])) {
                    $body .= L('closurecomment') . ' ' . $data['task']['closure_comment'] . "\r\n\r\n";
                }

                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_TASK_REOPENED:
                $body .=  L('notify.taskreopened') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] .  ")\r\n\r\n";
                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_DEP_ADDED:
                $depend_task = Flyspray::getTaskDetails($data['dep_task']);

                $body .=  L('newdep') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n\r\n";
                $body .= L('newdepis') . ':' . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\r\n";
                $body .= CreateURL(array('details', 'task' . $depend_task['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_DEP_REMOVED:
                $depend_task = Flyspray::getTaskDetails($data['dep_task']);

                $body .= L('notify.depremoved') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n\r\n";
                $body .= L('removeddepis') . ':' . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\r\n";
                $body .= CreateURL(array('details', 'task' . $depend_task['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_COMMENT_ADDED:
                // Get the comment information
                $result = $db->Execute('SELECT comment_text FROM {comments} WHERE comment_id = ?', array($data['cid']));
                $comment = $result->FetchRow();

                $body .= L('notify.commentadded') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n\r\n";
                $body .= "----------\r\n";
                $body .= $comment['comment_text'] . "\r\n";
                $body .= "----------\r\n\r\n";

                if (isset($data['files'])) {
                    $body .= L('fileaddedtoo') . "\r\n\r\n";
                    $subject .= ' (' . L('attachmentadded') . ')';
                }
                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . '#comment' . $data['cid'] . "\r\n\r\n";
                break;

            case NOTIFY_REL_ADDED:
                $related_task = Flyspray::getTaskDetails($data['rel_task']);

                $body .= L('notify.relatedadded') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n\r\n";
                $body .= L('relatedis') . ':' . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $related_task['task_id'] . ' - ' . $related_task['item_summary'] . "\r\n";
                $body .= CreateURL(array('details', 'task' . $related_task['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_OWNERSHIP:
                $body .= implode(', ', $data['task']['assigned_to_name']) . ' ' . L('takenownership') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n\r\n";
                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_CONFIRMATION:
                $body .= L('noticefrom') . " {$data['project']->prefs['project_title']}\r\n\r\n";
                $body .= L('addressused') . "\r\n\r\n";
                $body .= "{$data[0]}index.php?do=register&magic_url={$data[1]}\r\n\r\n";
                // In case that spaces in the username have been removed
                $body .= L('username') . ": $data[2] \r\n";
                $body .= L('confirmcodeis') . " $data[3] \r\n\r\n";
                break;

            case NOTIFY_PM_REQUEST:
                $body .= L('requiresaction') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n\r\n";
                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_PM_DENY_REQUEST:
                $body .= L('pmdeny') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n\r\n";
                $body .= L('denialreason') . ':' . "\r\n";
                $body .= $data['deny_reason'] . "\r\n\r\n";
                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_NEW_ASSIGNEE:
                $body .= L('assignedtoyou') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n\r\n";
                $body .= L('moreinfo') . "\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_REV_DEP:
                $depend_task = Flyspray::getTaskDetails($data['dep_task']);

                $body .= L('taskwatching') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n\r\n";
                $body .= L('isdepfor') . ':' . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\r\n";
                $body .= CreateURL(array('details', 'task' . $depend_task['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_REV_DEP_REMOVED:
                $depend_task = Flyspray::getTaskDetails($data['dep_task']);

                $body .= L('taskwatching') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n\r\n";
                $body .= L('isnodepfor') . ':' . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $depend_task['task_id'] . ' - ' .  $depend_task['item_summary'] . "\r\n";
                $body .= CreateURL(array('details', 'task' . $depend_task['task_id'])) . "\r\n\r\n";
                break;

            case NOTIFY_ADDED_ASSIGNEES:
                $body .= L('useraddedtoassignees') . "\r\n\r\n";
                $body .= $data['task']['project_prefix'] . '#' . $data['task_id'] . ' - ' . $data['task']['item_summary'] . "\r\n";
                $body .= L('userwho') . ' - ' . $user->infos['real_name'] . ' (' . $user->infos['user_name'] . ")\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id'])) . "\r\n\r\n\r\n";
                break;

            case NOTIFY_ANON_TASK:
                $body .= L('thankyouforbug') . "\r\n\r\n";
                $body .= CreateURL(array('details', 'task' . $data['task_id']), array('task_token' => $data['token'])) . "\r\n\r\n";
                break;

            case NOTIFY_PW_CHANGE:
                $body = L('messagefrom'). $data[0] . "\r\n\r\n";
                $body .= L('magicurlmessage')." \r\n";
                $body .= "{$data[0]}index.php?do=lostpw&magic_url=$data[1]\r\n";
                break;

            case NOTIFY_NEW_USER:
                $body = L('messagefrom'). $data[0] . "\r\n\r\n";
                $body .= L('newuserregistered')." \r\n\r\n";
                $body .= L('username') . ': ' . $data[1] . "\r\n";
                $body .= L('realname') . ': ' . $data[2] . "\r\n";
                if ($data[6]) {
                    $body .= L('password') . ': ' . $data[5] . "\r\n";
                }
                $body .= L('emailaddress') . ': ' . $data[3] . "\r\n";
                if($data[4]) {
                    $body .= L('jabberid') . ':' . $data[4] . "\r\n\r\n";
                }
                break;

            case NOTIFY_REMINDER:
            case NOTIFY_DIGEST:
                $body = $data['message'] . "\r\n\r\n";
                break;
        }

        $body .= L('disclaimer');
        return array($subject, $body);
    }
}

?>
