<?php
/*
   --------------------------------------------------
   | Edit comment                                   |
   | =======================                        |
   | This script allows users with the appropriate  |
   | permissions to edit comments attached to tasks |
   --------------------------------------------------
*/

$fs->get_language_pack('admin');
$page->uses('admin_text');

if (Get::has('id') && Get::has('task_id') && $permissions['edit_comments'] == '1') {
    // Get the comment details
    $res = $db->Query("SELECT  *
                         FROM  {comments}
                        WHERE  comment_id = ? AND task_id = ?",
                        array(Get::val('id'), Get::val('task_id')));
    $comment = $db->FetchArray($res);
    $page->assign('comment', $comment);

    $res = $db->Query("SELECT real_name FROM {users} WHERE user_id = ?", array($row['user_id']));
    $page->assign('user_name', $db->FetchArray($res));


    $page->display('editcomment.tpl');
} else {
    $fs->Redirect( $fs->createURL('error', null) );
}
?>
