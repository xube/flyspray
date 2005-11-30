<div id="menu">

<ul id="menu-list">
  <li onmouseover='perms.show()' onmouseout='perms.do_later("hide")'>
	<a href="{$fs->CreateURL('myprofile', null)}" title="{$language['editmydetails']}">
	  <em>{$user->infos['real_name']} ({$user->infos['user_name']})</em>
	</a>
	<div id="permissions">
	  {!tpl_draw_perms($user->perms)}
	</div>
  </li>
<?php
if ($user->perms['open_new_tasks']): ?>
  <li>
  <a id="newtasklink" href="{$fs->CreateURL('newtask', $proj->id)}"
    accesskey="a">{$language['addnewtask']}</a>
  </li>
<?php
endif;

if ($user->perms['view_reports']): ?>
  <li>
  <a id="reportslink" href="{$fs->CreateURL('reports', null)}">{$language['reports']}</a>
  </li>
<?php
endif; ?>
  <li>
<?php if (!empty($user->infos['last_search'])): ?>
  <a id="lastsearchlink" href="{$user->infos['last_search']}"
    accesskey="m">{$language['lastsearch']}</a>
<?php else: ?>
  <a id="lastsearchlink" href="{$baseurl}"
    accesskey="m">{$language['lastsearch']}</a>
<?php endif; ?>
  </li>
<?php if ($user->perms['is_admin']): ?>
  <li>
  <a id="optionslink" href="{$fs->CreateURL('admin', 'prefs')}">{$language['admintoolbox']}</a>
  </li>
<?php endif; ?>

<?php if ($user->perms['manage_project']): ?>
  <li>
  <a id="projectslink"
    href="{$fs->CreateURL('pm', 'prefs', $proj->id)}">{$language['manageproject']}</a>
  </li>
<?php endif; ?>
<?php if ($user->perms['manage_project'] && $pm_pendingreq_num): ?>
  <li>
<?php else: ?>
  <li class="last">
<?php endif; ?>
  <a id="logoutlink" href="{$fs->CreateURL('logout', null)}"
    accesskey="l">{$language['logout']}</a>
  </li>
<?php if ($user->perms['manage_project'] && $pm_pendingreq_num): ?>
  <li class="last">
  <a class="pendingreq attention"
    href="{$fs->CreateURL('pm', 'pendingreq', $proj->id)}">{$pm_pendingreq_num} {$language['pendingreq']}</a>
  </li>
<?php endif; ?>
</ul>

</div>
