<?php

// Convert lists
$sql = $db->Execute('SELECT * FROM {list_items}');
if ($sql && !$sql->FetchRow()) {
    foreach (array('os' => 'operating_system', 'resolution' => 'resolution_reason', 'status' => 'item_status',
                           'tasktype' => 'task_type', 'version' => array('product_version', 'closedby_version')) as $list => $cols) {
        $sql = $db->Execute('SELECT DISTINCT project_id FROM {list_'.$list.'}');

        while ($row = $sql->FetchRow()) {
            // Create new list
            if ($list == 'version') {
                $db->Execute('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array($list, 2, $row['project_id']));
            } else {
                $db->Execute('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array($list, 1, $row['project_id']));
            }
            $list_id = $db->GetOne('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', array($row['project_id']), 1);

            // Transfer list items
            $res = $db->Execute('SELECT * FROM {list_' . $list . '} WHERE project_id = ?', array($row['project_id']));
            while ($item = $res->FetchRow()) {
                if ($list == 'version') {
                    $db->Execute('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id, version_tense)
                                     VALUES (?, ?, ?, ?, ?)',
                               array($item[$list . '_name'], $item['list_position'], $item['show_in_list'], $list_id, $item['version_tense']));
                } else {
                    $db->Execute('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id)
                                     VALUES (?, ?, ?, ?)',
                               array($item[$list . '_name'], $item['list_position'], $item['show_in_list'], $list_id));
                }

                $tt = $db->GetOne('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC', array());
                // Update existing tasks
                settype($cols, 'array');
                foreach ($cols as $col) {
                    $db->Execute('UPDATE {tasks} SET ' . $col .' = ? WHERE '. $col . '= ?', array($tt, $item[$list . '_id']));
                    // and history tables
                    $db->Execute('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($tt, $item[$list . '_id'], $col));
                    $db->Execute('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($tt, $item[$list . '_id'], $col));
                }
                if ($list == 'resolution') {
                    $db->Execute('UPDATE {history} SET new_value = ? WHERE new_value = ? AND event_type = 2', array($tt, $item[$list . '_id']));
                }
            }
        }
    }

    // Categories
    $sql = $db->Execute('SELECT DISTINCT project_id FROM {list_category}');

    while ($row = $sql->FetchRow()) {
        // Create new list
        $db->Execute('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('category', 3, $row['project_id']));
        $list_id = $db->GetOne('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', array($row['project_id']), 1);
        $db->Execute('UPDATE {list_category} SET list_id = ? WHERE project_id = ?', array($list_id, $row['project_id']));
    }
}

// Convert fields
$sql = $db->Execute('SELECT * FROM {field_values}');
if ($sql && !$sql->FetchRow()) {
    // Add priorities
    $db->Execute('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('Priorities', 1, 0));
    $pri_id = $db->GetOne('SELECT list_id FROM {lists} ORDER BY list_id DESC', array());
    $pri_ids = array();
    $li = 0;
    foreach (array(6 => 'Flash', 5 => 'Immediate', 4 => 'Urgent', 3 => 'High', 2 => 'Normal', 1 => 'Low') as $key => $priority) {
        $db->Execute('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id) VALUES(?,?,1,?)', array($priority, $li++, $pri_id));
        $pri_ids[$key] = $db->GetOne('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC');

        $db->Execute('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($pri_ids[$key], $key, 'task_priority'));
        $db->Execute('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($pri_ids[$key], $key, 'task_priority'));
    }

    foreach (array('task_type' => 'Task Type',
                   'item_status' => 'Status',
                   'due_date' => 'Due date',
                   'operating_system' => 'Operating System',
                   'task_priority' => 'Priority',
                   'closedby_version' => 'Due in Version',
                   'product_version' => 'Reported version',
                   'product_category' => 'Category') as $field => $name) {
        $db->Execute('INSERT INTO {fields} (field_name, field_type, list_id, project_id) VALUES(?,?,?,0)',
                   array($name, ($field == 'due_date' ? 2 : 1), ($field == 'task_priority' ? $pri_id : 1)));
        $field_id = $db->GetOne('SELECT field_id FROM {fields} ORDER BY field_id DESC');

        $db->Execute('UPDATE {history} SET field_changed = ? WHERE field_changed = ? AND event_type = 3', array($field_id, $field));
        if ($field == 'closedby_version') {
            $db->Execute('UPDATE {projects} SET roadmap_field = ?', array($field_id));
        }

        // Add values
        $tasks = $db->Execute("SELECT {$field}, task_id FROM {tasks}");
        if ($field != 'task_priority') {
            while ($row = $tasks->FetchRow()) {
                $db->Execute('INSERT INTO {field_values} (task_id, field_value, field_id) VALUES (?,?,?)', array($row['task_id'], $row[$field], $field_id));
            }
        } else {
            while ($row = $tasks->FetchRow()) {
                $db->Execute('INSERT INTO {field_values} (task_id, field_value, field_id) VALUES (?,?,?)', array($row['task_id'], @intval($pri_ids[$row[$field]]), $field_id));
            }
        }
    }
    // Clean columns
    $db->Execute('UPDATE {projects} SET visible_columns = ?', array('id severity summary progress'));
    $db->Execute('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array('id severity summary progress', 'visible_columns'));
}

// Try to guess resolution list
$sql = $db->GetOne('SELECT pref_value FROM {prefs} WHERE pref_name = ?', array('resolution_list'));
if ($sql == 0) {
    // find list
    $list = $db->GetOne('SELECT list_id FROM {lists} WHERE list_name = ? AND project_id = 0', array('resolution'), 1);
    $db->Execute('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array($list, 'resolution_list'));
}
?>