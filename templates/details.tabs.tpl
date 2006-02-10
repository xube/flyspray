<ul id="submenu">
  <?php if ($user->perms['view_comments'] || $proj->prefs['others_view']): ?>
  <li id="commentstab">
  <a href="#comments">{$language['comments']} ({!count($comments)})</a>
  </li>
  <?php endif; ?>

  <li id="relatedtab">
  <a href="#related">{$language['relatedtasks']} ({!count($related)}/{!count($related_to)})</a>
  </li>

  <?php if ($user->perms['manage_project']): ?>
  <li id="notifytab">
  <a href="#notify">{$language['notifications']} ({!count($notifications)})</a>
  </li>
  <?php if (!$task_details['is_closed']): ?>
  <li id="remindtab">
  <a href="#remind">{$language['reminders']} ({!count($reminders)})</a>
  </li>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($user->perms['view_history']): ?>
  <li id="historytab">
    <a href="{CreateURL('details', $task_details['task_id'], null)}#history">{$language['history']}</a>
  </li>
  <?php endif; ?>
</ul>
