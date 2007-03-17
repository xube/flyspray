<?php

/**
 * Flyspray
 *
 * Text commands class
 *
 * This script contains all the functions we need for the
 * execution of text commands.
 *
 * @license http://opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 * @package flyspray
 * @author Florian Schmitz
 */

class TextCommands
{
    var $result = false;
    var $msg = '';

    /**
     * This function parses and executes commands from emails and Jabber messages
     * @param string $text
     * @param string $from email or Jabber ID
     * @param integer $type NOTIFY_EMAIL or NOTIFY_JABBER
     * @access public
     */
    function TextCommands($text, $from, $type)
    {
        global $db, $user;
        // First of all, let's get the user
        // TODO: authentication
        $uid = $db->GetOne('SELECT user_id
                              FROM {users}
                             WHERE email_address = ? AND ? = ?
                                   OR
                                   jabber_id = ? AND ? = ?',
                            array($from, $type, NOTIFY_EMAIL, $from, $type, NOTIFY_JABBER));
        if ($type == NOTIFY_JABBER) {
            $user = new User($uid);
        }

        if ($user->isAnon()) {
            return $this->done(false, 'Your Jabber ID is not in use on this Flyspray installation.');
        }

        $parsed = array();

        $text = explode("\n", $text);
        foreach ($text as $line) {
            $line = trim($line);
            $dd = strpos($line, ':');
            // consider the rest of the input as plain text as soon
            // as no more commands are found
            if (!$dd && isset($last)) {
                $parsed[$last] .= $line . "\n";
            } else if ($dd) {
                $last = substr($line, 0, $dd);
                $parsed[$last] = substr($line, $dd + 1) . "\n";
            }
        }

        if (isset($parsed['task'])) {
            $task = Flyspray::GetTaskDetails($parsed['task']);
            if (!$task) {
                return $this->done(false, 'Selected task (' . trim($parsed['task']) . ') does not exist.');
            }
        }
        // Now lets see what is to be done ...
        // ... add comment
        if (isset($task) && isset($parsed['comment'])) {
            if (!Backend::add_comment($task, $parsed['comment'])) {
                return $this->done(false, 'Adding a comment failed.');
            }
        // ... view task
        } else if (isset($parsed['task'])) {
            if ($user->can_view_task($task)) {
                return $this->done(true, TextCommands::view_task($task));
            } else {
                return $this->done(false, 'You have no permission to view this task.');
            }
        }
    }

    function done($result, $msg)
    {
        $this->result = (bool) $result;
        $this->msg    = $msg;
        return true;
    }

    /**
     * This function displays a text in plain text
     * @param array $task
     * @access public static
     * @return string
     */
    function view_task($task)
    {
        $proj = new Project($task['project_id']);

        $body  = '=== FS#' . $task['task_id'] . " ===\r\n";
        $state = $task['is_closed'] ? L('closed') : ($task['closed_by'] ? L('reopened') : L('open'));
        $body .= L('state') . ': ' . $state . "\r\n";
        $body .= L('summary') . ': ' . $task['item_summary'] . "\r\n";
        $body .= L('openedby') . ': ' . $task['opened_by_name'] . "\r\n";
        $body .= L('attachedtoproject') . ': ' .  $proj->prefs['project_title'] . "\r\n";
        $body .= L('assignedto') . ': ' . implode(', ', $task['assigned_to_name']) . "\r\n";
        $body .= L('severity') . ': ' . $task['severity_name'] . "\r\n";
        foreach ($proj->fields as $field) {
            $body .= $field->prefs['field_name'] . ': ';
            $body .= $field->view($task, array(), PLAINTEXT) . "\r\n";
        }
        $body .= L('details') . ": \r\n" . $task['detailed_desc'] . "\r\n\r\n";
        $body .= L('moreinfo') . "\r\n";
        $body .= CreateURL('details', $task['task_id']) . "\r\n\r\n";

        return $body;
    }
}

?>