<h2>{$roadmap_text['roadmapfor']} {$proj->prefs['project_title']}</h2>

<?php foreach($data as $milestone): ?>

<div class="admin roadmap">
<h3>{$milestone['name']}</h3>

<p><img src="{$baseurl}themes/{$proj->prefs['theme_style']}/percent-{(round($milestone['percent_complete']/10)*10)}.png"
				title="{(round($milestone['percent_complete']/10)*10)}% {$details_text['complete']}"
				alt="{(round($milestone['percent_complete']/10)*10)}%" width="200" height="20" />
</p>

<p>{$milestone['percent_complete']}% of
   <a href="{$baseurl}index.php?tasks=&project={$proj->id}&due=2&status=all">
     {count($milestone['all_tasks'])} {$roadmap_text['tasks']}
   </a> {$roadmap_text['completed']}
   <?php if(count($milestone['open_tasks'])): ?>
   <a href="{$baseurl}index.php?tasks=&project={$proj->id}&due=2">{count($milestone['open_tasks'])} {$roadmap_text['opentasks']}</a>
   <?php endif; ?>
</p>

<?php if(count($milestone['open_tasks'])): ?>
<ul>
    <li>
    {!implode('</li><li>', array_map('tpl_tasklink', $milestone['open_tasks']))}
    </li>
</ul>
<?php endif; ?>
</div>
<?php endforeach; ?>