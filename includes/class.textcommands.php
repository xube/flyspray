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
        global $db, $user, $proj;
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
            $dd = strpos($line, ':');
            // consider the rest of the input as plain text as soon
            // as no more commands are found
            if (!$dd && isset($last)) {
                $parsed[$last] .= $line . "\n";
            } else if ($dd) {
                $last = strtolower(substr($line, 0, $dd));
                $parsed[$last] = substr($line, $dd + 1) . "\n";
            }
        }
        $parsed = array_map('trim', $parsed);

        if (isset($parsed['task'])) {
            $task = Flyspray::GetTaskDetails($parsed['task']);
            if (!$task) {
                return $this->done(false, 'Selected task (' . trim($parsed['task']) . ') does not exist.');
            }
            $proj = new Project($task['project_id']);
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
        // ... new task
        } else if (isset($parsed['summary'])) {
            list($task_id, $msg, $ok) = TextCommands::create_task($parsed);
            if ($ok) {
                return $this->done(true, 'Task has been created, ID is: ' . $task_id);
            } else {
                return $this->done(false, $msg);
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
        global $proj;

        $body  = '=== ' . $task['project_prefix'] . '#' . $task['prefix_id'] . " ===\r\n";
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
        $body .= CreateURL(array('details', 'task' . $task['task_id'])) . "\r\n\r\n";

        return $body;
    }

    /**
     * This function prepares the parsed data for Backend::create_task()
     * @param array $parsed
     * @access public static
     * @return array
     */
    function create_task($parsed)
    {
        global $db, $fs;

        $args = array();
        // item summary
        if (isset($parsed['summary'])) {
            $args['item_summary'] = $parsed['summary'];
        }
        // and details
        if (isset($parsed['details'])) {
            $args['detailed_desc'] = $parsed['details'];
        }
        // private or not?
        if (isset($parsed['private']) && ($parsed['private'] == '1' || strcasecmp($parsed['private'], L('yes')) == 0)) {
            $args['mark_private'] = 1;
        }
        // project
        if (isset($parsed['project'])) {
            if (is_numeric($parsed['project'])) {
                $args['project_id'] = $parsed['project'];
            } else {
                $pid = $db->GetOne('SELECT project_id FROM {projects} WHERE project_title LIKE ?',
                                   array('%' . $parsed['project'] . '%'));
                $args['project_id'] = $pid;
            }
            $proj = new Project($args['project_id']);
        } else {
            return array('', 'No project specified.', false);
        }

        // severity
        if (isset($parsed['severity'])) {
            if (is_numeric($parsed['severity'])) {
                $args['task_severity'] = $parsed['severity'];
            } else {
                $args['task_severity'] = array_search(strtolower($parsed['severity']), array_map('strtolower', $fs->severities));
            }
        }
        // assignees
        if (isset($parsed['assigned'])) {
            $assignees = explode(',', $parsed['assigned']);
            $assignees = array_map(array('Flyspray', 'username_to_id'), $assignees);
            $args['assigned_to'] = implode(' ', $assignees);
        }
        // custom fields
        foreach ($proj->fields as $field) {
            if (isset($parsed[strtolower($field->prefs['field_name'])])) {
                $value = $parsed[strtolower($field->prefs['field_name'])];
                // you won't enter the ID of the item, so we have to find it first
                if ($field->prefs['field_type'] == FIELD_LIST) {
                    if ($field->prefs['list_type'] == LIST_CATEGORY) {
                        $value = $db->GetOne('SELECT category_id FROM {list_category} WHERE category_name LIKE ?',
                                             array($value));
                    } else {
                        $value = $db->GetOne('SELECT list_item_id FROM {list_items} WHERE item_name LIKE ?',
                                             array($value));
                    }
                }
                $args['field' . $field->id] = $value;
            }
        }

        return Backend::create_task($args);
    }
}

?>