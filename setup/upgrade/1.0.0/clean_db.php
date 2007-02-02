<?php
/* Some DB cleanup */

$db->Query('DELETE FROM {notifications} WHERE user_id = 0 OR task_id = 0');

?>