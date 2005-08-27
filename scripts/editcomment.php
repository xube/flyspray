<?php
/*
   --------------------------------------------------
   | Edit comment                                   |
   | =======================                        |
   | This script allows users with the appropriate  |
   | permissions to edit comments attached to tasks |
   --------------------------------------------------
*/


$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'admin');

if (isset($_GET['id']) && $permissions['edit_comments'] == '1')
{
   // Get the comment details
   $getcomments = $db->Query("SELECT * FROM {$dbprefix}comments WHERE comment_id = ?", array($_GET['id']));
   while ($row = $db->FetchArray($getcomments))
   {
      $getusername = $db->Query("SELECT real_name FROM {$dbprefix}users WHERE user_id = ?", array($row['user_id']));
      list($user_name) = $db->FetchArray($getusername);

      $formatted_date = $fs->formatDate($row['date_added'], true);
      $comment_text = htmlspecialchars(stripslashes($row['comment_text']),ENT_COMPAT,'utf-8');
   ?>

   <h3><?php echo $admin_text['editcomment'];?></h3>

   <form action="index.php" method="post">
      <div class="admin">
         <p><?php echo "{$admin_text['commentby']} $user_name - $formatted_date";?></p>
         <p>
            <textarea cols="72" rows="10" name="comment_text"><?php echo $comment_text;?></textarea>
         </p>
         <p class="buttons">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="editcomment" />
            <input type="hidden" name="task_id" value="<?php echo $row['task_id'];?>" />
            <input type="hidden" name="comment_id" value="<?php echo $_GET['id'];?>" />
            <input type="hidden" name="previous_text" value="<?php echo $comment_text;?>" />
            <input class="adminbutton" type="submit" value="<?php echo $admin_text['saveeditedcomment'];?>" />
         </p>
      </div>
   </form>

<?php
   // End of looping
   }
// End of editing a comment
}
?>