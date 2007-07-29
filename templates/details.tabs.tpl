<ul id="submenu">
  <?php if ($user->perms('view_comments') || $proj->prefs['others_view'] || ($user->isAnon() && $task['task_token'] && Get::val('task_token') == $task_details['task_token'])): ?>
  <li id="commentstab">
  <a href="#comments">{L('comments')} ({count($comments)})</a>
  </li>
  <?php endif; ?>

  <li id="relatedtab">
  <a href="#related">{L('relateditems')} ({count($related)} / {count($duplicates)}<?php if (isset($svnlog)): ?> / {count($svnlog)}<?php endif; ?>)</a>
  </li>

  <?php if ($user->perms('manage_project')): ?>
  <li id="notifytab">
  <a href="#notify">{L('notifications')} ({count($notifications)})</a>
  </li>
  <?php if (!$task['is_closed']): ?>
  <li id="remindtab">
  <a href="#remind">{L('reminders')} ({count($reminders)})</a>
  </li>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($user->perms('view_history')): ?>
  <li id="historytab">
    <a id="historytaba" onmousedown="getHistory('{$task['task_id']}', '{$this->relativeUrl($baseurl)}', 'history', '{Get::val('details')}');"
       href="{$this->url(array('details', 'task' . $task['task_id']))}#history">{L('history')}</a>
  </li>
  <?php endif; ?>
</ul>
