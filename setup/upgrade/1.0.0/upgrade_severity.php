<?php

// Add severities
$db->Execute('INSERT INTO {lists} (list_name, list_type, project_id) VALUES(?,?,?)', array('Severities', 1, 0));
$sev_id = $db->GetOne('SELECT list_id FROM {lists} ORDER BY list_id DESC', array());
$sev_ids = array();
$li = 0;
foreach (array(5 => 'Critical', 4 => 'High', 3 => 'Medium', 2 => 'Low', 1 => 'Very Low') as $key => $severity) {
    $db->Execute('INSERT INTO {list_items} (item_name, list_position, show_in_list, list_id) VALUES(?,?,1,?)', array($severity, $li++, $sev_id));
    $sev_ids[$key] = $db->GetOne('SELECT list_item_id FROM {list_items} ORDER BY list_item_id DESC');
    
    $db->Execute('UPDATE {history} SET new_value = ? WHERE new_value = ? AND field_changed = ?', array($sev_ids[$key], $key, 'task_severity'));
    $db->Execute('UPDATE {history} SET old_value = ? WHERE old_value = ? AND field_changed = ?', array($sev_ids[$key], $key, 'task_severity'));
}


$db->Execute('INSERT INTO {fields} (field_name, field_type, list_id, project_id, default_value, force_default) VALUES(?,?,?,?,?,?)',
              array('Severity', FIELD_LIST, $sev_id, 0, 2, 1));
$field_id = $db->GetOne("SELECT field_id FROM {fields} WHERE project_id=? AND field_name = ? ORDER BY field_id DESC", array(0, 'Severity'));

$tasks = $db->Execute("SELECT task_severity, task_id FROM {tasks}");
while ($row = $tasks->FetchRow()) {
    $db->Execute('INSERT INTO {field_values} (task_id, field_value, field_id) VALUES (?,?,?)', array($row['task_id'], $sev_ids[$row['task_severity']], $field_id));

}
$db->Execute('UPDATE {history} SET field_changed = ? WHERE field_changed = ? AND event_type = 3', array($field_id, 'task_severity'));
$db->Execute('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?', array($field_id, 'color_field'));
?>