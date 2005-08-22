<?php
/*
   This script is to convert everything from the old "attachments"
   tabs to comments+attachments on the "comments" tab.
*/

// Set up the database access
include_once('../header.php');

// Get a list of the attachments
$attachments = $db->Query("SELECT * FROM {$dbprefix}attachments
                           WHERE comment_id < '1'
                           AND date_added > '0'"
                         );

// Cycle through each attachment
while($row = $db->FetchArray($attachments))
{
   // Create a comment
   $db->Query("INSERT INTO {$dbprefix}comments
               (task_id, date_added, user_id, comment_text)
               VALUES ( ?, ?, ?, ? )",
               array($row['task_id'], $row['date_added'], $row['added_by'], $row['file_desc']));

   // Retrieve the comment ID
   $comment = $db->FetchRow($db->Query("SELECT * FROM {$dbprefix}comments
                                        WHERE comment_text = ?
                                        ORDER BY comment_id DESC",
                                        array($row['file_desc']), 1
                                      )
                           );

   // Update the attachment entry to point it to the comment ID
   $db->Query("UPDATE {$dbprefix}attachments
               SET comment_id = ?
               WHERE attachment_id = ?",
               array($comment['comment_id'], $row['attachment_id'])
             );

}

//echo 'Your attachments are now converted to the new format.  Running this script more than once will do nothing.';


?>