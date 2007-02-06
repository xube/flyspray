<?php

// Convert lists
$sql = $db->Query('SELECT * FROM {list_items}');
if (!$db->CountRows($sql)) {
    foreach (array('os' => 'operating_system', 'resolution' => 'resolution_reason', 'status' => 'item_status',
                           'tasktype' => 'task_type', 'version' => array('product_version', 'closedby_version')) as $list => $cols) {
        $sql = $db->Query('SELECT DISTINCT project_id FROM {list_'.$list.'}');
        
        while ($row = $db->FetchRow($sql)) {
            // Create new list
            if ($list == 'version') {
                $db->Query('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array($list, 'versions', $row['project_id']));
            } else {
                $db->Query('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array($list, 'basic', $row['project_id']));
            }
            $res = $db->Query('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', array($row['project_id']), 1);
            $list_id = $db->FetchOne($res);
            
            // Transfer list items
            $res = $db->Query('SELECT * FROM {list_' . $list . '} WHERE project_id = ?', array($row['project_id']));
            while ($item = $db->FetchRow($res)) {
                if ($list == 'version') {
                    $db->Query('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id, version_tense)
                                     VALUES (?, ?, ?, ?, ?)',
                               array($item[$list . '_name'], $item['list_position'], $item['show_in_list'], $list_id, $item['version_tense']));
                } else {
                    $db->Query('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id)
                                     VALUES (?, ?, ?, ?)',
                               array($item[$list . '_name'], $item['list_position'], $item['show_in_list'], $list_id));
                }
                        
                $tt = $db->Query('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC', array(), 1);
                $tt = $db->FetchOne($tt);
                // Update existing tasks
                settype($cols, 'array');
                foreach ($cols as $col) {
                    $db->Query('UPDATE {tasks} SET ' . $col .' = ? WHERE '. $col . '= ?', array($tt, $item[$list . '_id']));
                    // and history tables
                    $db->Query('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($tt, $item[$list . '_id'], $col));
                    $db->Query('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($tt, $item[$list . '_id'], $col));
                }
                if ($list == 'resolution') {
                    $db->Query('UPDATE {history} SET new_value = ? WHERE new_value = ? AND event_type = 2', array($tt, $item[$list . '_id']));
                }
            }
        }
    }
    
    // Categories
    $sql = $db->Query('SELECT DISTINCT project_id FROM {list_category}');
        
    while ($row = $db->FetchRow($sql)) {
        // Create new list
        $db->Query('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('category', 'category', $row['project_id']));
        $res = $db->Query('SELECT list_id FROM {lists} WHERE project_id = ? ORDER BY list_id DESC', array($row['project_id']), 1);
        $list_id = $db->FetchOne($res);
        $db->Query('UPDATE {list_category} SET list_id = ? WHERE project_id = ?', array($list_id, $row['project_id']));
    }
}
?>