<?php

if (!$db->x->getOne('SELECT count(*) FROM {redundant}')) {
    $sql = $db->query('SELECT * FROM {tasks}');
    while ($row = $sql->fetchRow()) {
        $db->x->execParam('INSERT INTO {redundant} (task_id) VALUES(?)', $row['task_id']);
    }
}

// Opened by
$sql = $db->query("SELECT t.task_id, u.real_name, u.user_name
                     FROM {tasks} t
                LEFT JOIN {users} u ON t.opened_by = u.user_id" );
while ($row = $sql->fetchRow()) {
    $db->x->execParam('UPDATE {redundant} SET opened_by_real_name = ?, opened_by_user_name = ? WHERE task_id = ?', array($row['real_name'], $row['user_name'], $row['task_id']));
}

// Closed by
$sql = $db->query("SELECT t.task_id, u.real_name, u.user_name
                     FROM {tasks} t
                LEFT JOIN {users} u ON t.closed_by = u.user_id" );
while ($row = $sql->fetchRow()) {
    $db->x->execParam('UPDATE {redundant} SET closed_by_real_name = ?, closed_by_user_name = ? WHERE task_id = ?', array($row['real_name'], $row['user_name'], $row['task_id']));
}

// Last edited by
$sql = $db->query("SELECT t.task_id, u.real_name, u.user_name
                     FROM {tasks} t
                LEFT JOIN {users} u ON t.last_edited_by = u.user_id" );
while ($row = $sql->fetchRow()) {
    $db->x->execParam('UPDATE {redundant} SET last_edited_by_real_name = ?, last_edited_by_user_name = ? WHERE task_id = ?', array($row['real_name'], $row['user_name'], $row['task_id']));
}

// Last changed by
// Last changed date
$sql = $db->query("SELECT t.task_id, max(c.date_added) AS max_comment, t.date_closed, t.date_opened, t.last_edited_time,
                          uco.real_name AS uco_real, ucl.real_name AS ucl_real, uop.real_name AS uop_real, ule.real_name AS ule_real,
                          uco.user_name AS uco_user, ucl.user_name AS ucl_user, uop.user_name AS uop_user, ule.user_name AS ule_user
                     FROM {tasks} t
                LEFT JOIN {comments} c ON t.task_id = c.task_id
                LEFT JOIN {users} uco ON c.user_id = uco.user_id
                LEFT JOIN {users} ucl ON t.closed_by = ucl.user_id
                LEFT JOIN {users} uop ON t.opened_by = uop.user_id
                LEFT JOIN {users} ule ON t.last_edited_by = ule.user_id
                 GROUP BY t.task_id" );
while ($row = $sql->fetchRow()) {
    $max = max($row['max_comment'], $row['date_closed'], $row['date_opened'], $row['last_edited_time']);
    $db->x->execParam('UPDATE {redundant} SET last_changed_time = ? WHERE task_id = ?', array($max, $row['task_id']));
     
    if ($row['max_comment'] == $max) {
        $name = 'uco';
    } else if ($row['date_closed'] == $max) {
        $name = 'ucl';
    } else if ($row['date_opened'] == $max) {
        $name = 'uop';
    } else if ($row['last_edited_time'] == $max) {
        $name = 'ule';
    }
    $db->x->execParam('UPDATE {redundant} SET last_changed_by_real_name = ?, last_changed_by_user_name = ? WHERE task_id = ?', array($row[$name . '_real'], $row[$name . '_user'], $row['task_id']));
}

// Attachment count
// Comment count
$sql = $db->query('SELECT * FROM {tasks}');
while ($row = $sql->fetchRow()) {
    $attachments = $db->x->getOne('SELECT count(*) FROM {attachments} WHERE task_id = ?', null, $row['task_id']);
    $comments = $db->x->getOne('SELECT count(*) FROM {comments} WHERE task_id = ?', null, $row['task_id']);
    $votes = $db->x->getOne('SELECT count(*) FROM {votes} WHERE task_id = ?', null, $row['task_id']);
    $db->x->execParam('UPDATE {redundant} SET comment_count = ?, attachment_count = ?, vote_count = ? WHERE task_id = ?', array($comments, $attachments, $votes, $row['task_id']));
}

?>
