<?php foreach($data as $milestone): ?>

<div class="box roadmap">
<h3>{L('changelogfor')} {$milestone['name']}</h3>

<p>
  <?php foreach ($milestone['resolutions'] as $name => $count): ?>
  <strong>{$name}</strong>: {$count} {L('tasks')} <br />
  <?php endforeach; ?>
</p>

<dl class="roadmap changelog">
    <?php foreach($milestone['tasks'] as $task):
          if(!$user->can_view_task($task)) continue; ?>
      <dt class="task {$fs->GetColorCssClass($task)}">
        {!tpl_tasklink($task['task_id'])} <small>({$task['res_name']})</small>
      </dt>
      <dd id="dd{$task['task_id']}" ></dd>
    <?php endforeach; ?>
</dl>

</div>
<?php endforeach; ?>

<?php if (!count($data)): ?>
<div class="box roadmap">
<p><em>{L('nochangelog')}</em></p>
</div>
<?php else: ?>
<p><a href="{$this->url(array('changelog', 'proj' . $proj->id), array('txt' => 'true'))}"><img src="{$this->get_image('mime/text')}" alt="" /> {L('textversion')}</a></p>
<?php endif; ?>
