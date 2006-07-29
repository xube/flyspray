<?php foreach ($projects as $project): ?>
<div class="admin">
<h2>{$project['project_title']}</h2>
<p><strong>{L('viewtasks')}</strong>:
    <a href="{$baseurl}index.php?do=index&amp;project={$project['project_id']}&amp;status[]=">{L('All')}</a> |
    <a href="{$baseurl}index.php?do=index&amp;project={$project['project_id']}&amp;status[]=open">{L('open')}</a> |
    <?php if (!$user->isAnon()): ?>
      <a href="{$baseurl}index.php?do=index&amp;project={$project['project_id']}&amp;dev={$user->id}">{L('assignedtome')}</a> |
      <a href="{$baseurl}index.php?do=index&amp;project={$project['project_id']}&amp;only_watched=1">{L('taskswatched')}</a> |
      <a href="{$baseurl}index.php?do=index&amp;project={$project['project_id']}&amp;opened={$user->id}">{L('tasksireported')}</a> |
    <?php endif; ?>
    <a href="{$baseurl}index.php?do=index&amp;project={$project['project_id']}&amp;openedfrom=-1+week">{L('recentlyopened')}</a>
</p>
<p><strong>{L('stats')}</strong>: {$stats[$project['project_id']]['open']} {L('opentasks')}, {$stats[$project['project_id']]['all']} {L('totaltasks')}.</p>
<?php if (isset($most_wanted[$project['project_id']])): ?>
<p>
  <strong>{L('mostwanted')}:</strong>
  <ul>
    <?php foreach($most_wanted[$project['project_id']] as $task): ?>
    <li>{!tpl_tasklink($task['task_id'])}, {$task['num_votes']} {L('vote(s)')}</li>
    <?php endforeach; ?>
  </ul>
</p>
<?php endif; ?>
<p><strong>{L('feeds')}</strong>: <a href="{$baseurl}feed.php?feed_type=rss1&amp;project={$project['project_id']}">RSS 1.0</a> | 
              <a href="{$baseurl}feed.php?feed_type=rss2&amp;project={$project['project_id']}">RSS 2.0</a> |
              <a href="{$baseurl}feed.php?feed_type=atom&amp;project={$project['project_id']}">Atom</a></p>
</div>
<?php endforeach; ?>