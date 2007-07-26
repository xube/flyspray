<?php

// Add severities
if (!isset($fs->prefs['color_field'])) {
$db->x->execParam('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('Severities', 1, 0));
$sev_id = $db->x->GetOne('SELECT list_id FROM {lists} ORDER BY list_id DESC');
$sev_ids = array();
$li = 0;
foreach (array(5 => 'Critical', 4 => 'High', 3 => 'Medium', 2 => 'Low', 1 => 'Very Low') as $key => $severity) {
    $db->x->execParam('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id) VALUES(?,?,1,?)', array($severity, $li++, $sev_id));
    $sev_ids[$key] = $db->x->GetOne('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC');
    
    $db->x->execParam('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($sev_ids[$key], $key, 'task_severity'));
    $db->x->execParam('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($sev_ids[$key], $key, 'task_severity'));
}


$db->x->execParam('INSERT INTO {fields} (field_name, field_type, list_id, project_id, default_value, force_default) VALUES(?,?,?,?,?,?)',
              array('Severity', FIELD_LIST, $sev_id, 0, 2, 1));
$field_id = $db->x->GetOne("SELECT field_id FROM {fields} WHERE project_id=? AND field_name = ? ORDER BY field_id DESC", null, array(0, 'Severity'));

$tasks = $db->query("SELECT * FROM {tasks}");
while ($row = $tasks->FetchRow()) {
    if (!isset($row['task_severity'])) {
        continue;
    }
    $db->x->execParam('INSERT INTO {field_values} (task_id, field_value, field_id) VALUES (?,?,?)', array($row['task_id'], $sev_ids[$row['task_severity']], $field_id));

}
$db->x->execParam('UPDATE {history} SET field_changed = ? WHERE field_changed = ? AND event_type = 3', array($field_id, 'task_severity'));
$db->x->execParam('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array($field_id, 'color_field'));
}
?>
