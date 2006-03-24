<h2>{$proj->prefs['project_title']}</h2>

<?php foreach($data as $milestone): ?>

<div class="admin roadmap">
<h3 style="cursor:pointer;" onclick="<?php
foreach($milestone['open_tasks'] as $task): ?>
     showhidestuff('dd{$task['task_id']}');
<?php endforeach; ?>
">{L('roadmapfor')} {$milestone['name']} <span class="DoNotPrint fade">[++]</span></h3>

<p><img src="{$baseurl}themes/{$proj->prefs['theme_style']}/percent-{(round($milestone['percent_complete']/10)*10)}.png"
				title="{(round($milestone['percent_complete']/10)*10)}% {L('complete')}"
				alt="" width="200" height="20" />
</p>

<p>{$milestone['percent_complete']}% of
   <a href="{$baseurl}index.php?tasks=&amp;project={$proj->id}&amp;due={$milestone['id']}&amp;status[]=">
     {count($milestone['all_tasks'])} {L('tasks')}
   </a> {L('completed')}
   <?php if(count($milestone['open_tasks'])): ?>
   <a href="{$baseurl}index.php?tasks=&amp;project={$proj->id}&amp;due={$milestone['id']}">{count($milestone['open_tasks'])} {L('opentasks')}</a>
   <?php endif; ?>
</p>

<?php if(count($milestone['open_tasks'])): ?>
<dl class="roadmap">
    <?php foreach($milestone['open_tasks'] as $task):
          if(!$user->can_view_task($task)) continue; ?>
      <dt class="severity{$task['task_severity']}" onclick="showhidestuff('dd{$task['task_id']}')">
        {!tpl_tasklink($task['task_id'])} <b class="DoNotPrint fade">[+]</b>
      </dt>
      <dd id="dd{$task['task_id']}" >
        {!tpl_formatText(substr($task['detailed_desc'], 0, 500) . ((strlen($task['detailed_desc']) > 500) ? '...' : ''),
                         false, 'task', $task['task_id'], $task['content'])}
        <br style="position:absolute;" />
      </dd>
    <?php endforeach; ?>
</dl>

<?php endif; ?>
</div>
<?php endforeach; ?>
