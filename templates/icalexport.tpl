BEGIN:VCALENDAR
VERSION:2.0
PRODID:Flyspray - {$proj->prefs['project_title']}
<?php foreach ($tasks as $task): ?>
<?php foreach ($proj->fields as $name => $field):
if ($field->prefs['field_type'] != FIELD_DATE || !in_array($name, $visible) || !$task[$name]) continue; ?>
BEGIN:VEVENT
DTSTART:{date('Ymd', $task[$name])}
DTEND:{date('Ymd', $task[$name] + 60 * 60 * 24)}
SUMMARY:{$task['project_prefix']}#{$task['prefix_id']} - {$field->prefs['field_name']}
END:VEVENT
<?php endforeach;
foreach ($datecols as $name => $dbcol):
if (!isset($task[$dbcol]) || !$task[$dbcol]) continue; ?>
BEGIN:VEVENT
DTSTART:{date('Ymd\THi00', $task[$dbcol])}
DTEND:{date('Ymd\THi00', $task[$dbcol] + 60)}
SUMMARY:{$task['project_prefix']}#{$task['prefix_id']} - {L($name)}
END:VEVENT
<?php endforeach; endforeach; ?>
END:VCALENDAR