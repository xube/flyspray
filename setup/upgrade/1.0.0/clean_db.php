<?php
/* Some DB cleanup */

$db->query('DELETE FROM {notifications} WHERE user_id = 0 OR task_id = 0');
$db->query('DELETE FROM {notification_recipients}');
$db->query('DELETE FROM {notification_messages}');
$db->query('DELETE FROM {cache}');
?>
