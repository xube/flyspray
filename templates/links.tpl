<p id="menu">
  <em>{$current_user['real_name']} ({$current_user['user_name']})</em>

<?php
if ($permissions['open_new_tasks']): ?>
  <small> | </small>
  <a id="newtasklink" href="{$fs->CreateURL('newtask', $proj->id)}"
    accesskey="a">{$language['addnewtask']}</a>
<?php
endif;

if ($permissions['view_reports']): ?>
  <small> | </small>
  <a id="reportslink" href="{$fs->CreateURL('reports', null)}"
    accesskey="r">{$language['reports']}</a>
<?php
endif; ?>

  <small> | </small>
  <a id="editmydetailslink" href="{$fs->CreateURL('myprofile', null)}"
    accesskey="e">{$language['editmydetails']}</a>

  <small> | </small>
<?php if (!empty($current_user['last_search'])): ?>
  <a id="lastsearchlink" href="{$current_user['last_search']}"
    accesskey="m">{$language['lastsearch']}</a>
<?php else: ?>
  <a id="lastsearchlink" href="{$baseurl}"
    accesskey="m">{$language['lastsearch']}</a>
<?php endif; ?>

<?php if ($permissions['is_admin']): ?>
  <small> | </small>
  <a id="optionslink" href="{$fs->CreateURL('admin', 'prefs')}">{$language['admintoolbox']}</a>
<?php endif; ?>

<?php if ($permissions['manage_project']): ?>
  <small> | </small>
  <a id="projectslink"
    href="{$fs->CreateURL('pm', 'prefs', $proj->id)}">{$language['manageproject']}</a>
<?php endif; ?>

  <small> | </small>
  <a id="logoutlink" href="{$fs->CreateURL('logout', null)}"
    accesskey="l">{$language['logout']}</a>

<?php if ($permissions['manage_project'] && $pm_pendingreq_num): ?>
  <small> | </small>
  <a id="pendingreq" class="attention"
    href="{$fs->CreateURL('pm', 'pendingreq', $proj->id)}">{$num_req} {$language['pendingreq']}</a>
<?php endif; ?>

</p>
