<?php

    require('../header.php');

    //Tasks opened
    $fs->dbQuery("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type)
                    SELECT task_id, opened_by AS user_id, date_opened AS event_date, 1 AS event_type
                    FROM flyspray_tasks");

    //Tasks closed
    $fs->dbQuery("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type, new_value)
                    SELECT task_id, closed_by AS user_id, date_closed AS event_date, 2 AS event_type, resolution_reason AS new_value
                    FROM flyspray_tasks
                    WHERE is_closed = 1");

    //Tasks edited
    $fs->dbQuery("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type)
                    SELECT task_id, last_edited_by AS user_id, last_edited_time AS event_date, 3 AS event_type
                    FROM flyspray_tasks
                    WHERE last_edited_by <> 0");

    //Comments added
    $fs->dbQuery("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type, new_value)
                    SELECT t.task_id, c.user_id AS user_id, c.date_added AS event_date, 4 AS event_type, c.comment_id AS new_value
                    FROM flyspray_tasks t
                    RIGHT JOIN flyspray_comments c ON t.task_id = c.task_id");

    //Attachments added
    $fs->dbQuery("INSERT INTO flyspray_history (task_id, user_id, event_date, event_type, new_value)
                    SELECT t.task_id, a.added_by AS user_id, a.date_added AS event_date, 7 AS event_type, a.attachment_id AS new_value
                    FROM flyspray_tasks t
                    RIGHT JOIN flyspray_attachments a ON t.task_id = a.task_id");

?>