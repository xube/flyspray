<ul id="submenu">
  <?php if ($user->perms['view_comments'] || $proj->prefs['others_view']): ?>
  <li id="commentstab">
  <a href="#comments">{$details_text['comments']} ({!count($comments)})</a>
  </li>
  <?php endif; ?>

  <li id="relatedtab">
  <a href="#related">{$details_text['relatedtasks']} ({!count($related)}/{!count($related_to)})</a>
  </li>

  <?php if ($user->perms['manage_project']): ?>
  <li id="notifytab">
  <a href="#notify">{$details_text['notifications']} ({!count($notifications)})</a>
  </li>
  <li id="remindtab">
  <a href="#remind">{$details_text['reminders']} ({!count($reminders)})</a>
  </li>
  <?php endif; ?>

  <?php if ($user->perms['view_history']): ?>
  <li id="historytab">
  <a href="#history">{$details_text['history']}</a>
  </li>
  <?php endif; ?>
</ul>
