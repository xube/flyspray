=== {$proj->prefs['project_title']} ===

<?php foreach($data as $milestone): ?>
{L('changelogfor')} {$milestone['name']}

<?php if(count($milestone['tasks'])): ?>

    <?php foreach($milestone['tasks'] as $task):
          if(!$user->can_view_task($task)) continue; ?>
    {$proj->prefs['project_prefix']}#{$task['prefix_id']} - {$task['item_summary']} ({$task['res_name']})

    <?php endforeach; ?>

<?php endif; ?>

<?php endforeach; ?>
