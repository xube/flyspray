<?php
   /**********************************************************\
   | This script removes duplicate db entries                 |
   \**********************************************************/

// Users

$users = $db->Execute('SELECT * FROM {users} ORDER BY user_id ASC');

while ($row = $users->FetchRow())
{
    if (!isset($deleted[$row['user_name']])) {
        $deleted[$row['user_name']] = $row['user_id'];
    }

    $db->Execute('DELETE FROM {users} WHERE user_name = ? AND user_id != ?',
                array($row['user_name'], $deleted[$row['user_name']]));
}


$users = $db->Execute('SELECT * FROM {registrations} ORDER BY reg_id ASC');

while ($row = $users->FetchRow())
{
    if (!isset($deleted[$row['user_name']])) {
        $deleted[$row['user_name']] = $row['reg_id'];
    }

    $db->Execute('DELETE FROM {registrations} WHERE user_name = ? AND reg_id != ?',
                array($row['user_name'], $deleted[$row['user_name']]));
}

// Users in groups

$sql = $db->Execute('SELECT * FROM {users_in_groups} ORDER BY record_id');
while ($row = $sql->FetchRow())
{
    $db->Execute('DELETE FROM {users_in_groups} WHERE user_id = ? AND group_id = ? AND record_id <> ?',
               array($row['user_id'], $row['group_id'], $row['record_id']));
}

// Group names

$sql = $db->Execute('SELECT * FROM {groups} ORDER BY group_id ASC');
while ($row = $sql->FetchRow())
{
    $col = 'belongs_to_project';
    if (!isset($row[$col])) {
        $col = 'project_id';
    }

    $db->Execute('DELETE FROM {groups} WHERE group_name = ? AND '.$col.' = ? AND group_id <> ?',
               array($row['group_name'], $row[$col], $row['group_id']));
}

// Out of range value adjusted for column..
$sql = $db->Execute('SELECT * {tasks}');
while ($row = $sql->FetchRow())
{
    $db->Execute('UPDATE {tasks} SET date_closed = ?, last_edited_time = ? WHERE task_id = ?',
               array(intval($row['date_closed']), intval($row['last_edited_time']), $row['task_id']));
    if (isset($row['due_date'])) {
       $db->Execute('UPDATE {tasks} SET due_date = ? WHERE task_id = ?',
                   array(intval($row['due_date']), $row['task_id']));
    }
}
?>