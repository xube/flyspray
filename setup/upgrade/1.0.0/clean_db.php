<?php
/* Some DB cleanup */

$db->Execute('DELETE FROM {notifications} WHERE user_id = 0 OR task_id = 0');
$db->Execute('DELETE FROM {notification_recipients}');
$db->Execute('DELETE FROM {notification_messages}');
?>