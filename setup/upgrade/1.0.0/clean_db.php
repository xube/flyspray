<?php
/* Some DB cleanup */

$db->x->autoExecute('{notifications}', null, MDB2_AUTOQUERY_DELETE, 'user_id = 0 OR task_id = 0');
$db->x->autoExecute('{notification_recipients}', null, MDB2_AUTOQUERY_DELETE);
$db->x->autoExecute('{notification_messages}', null, MDB2_AUTOQUERY_DELETE);
$db->x->autoExecute('{cache}', null, MDB2_AUTOQUERY_DELETE);
$db->x->execParam("UPDATE {users} SET email_address = NULL WHERE email_address = ''");
$db->x->execParam("UPDATE {users} SET jabber_id = NULL WHERE jabber_id = ''");
?>
