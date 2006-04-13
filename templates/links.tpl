<?php $proj_id = (isset($old_project)) ? $old_project : $proj->id; ?>
<div id="menu">

<ul id="menu-list">
  <li onmouseover="perms.do_later('show')" onmouseout="perms.hide()">
	<a href="{CreateURL('myprofile', null)}" title="{L('editmydetails')}">
	  <em>{$user->infos['real_name']} ({$user->infos['user_name']})</em>
	</a>
	<div id="permissions">
	  {!tpl_draw_perms($user->perms)}
	</div>
  </li>
<?php
if ($proj->id != 0 && $user->perms['open_new_tasks']): ?>
  <li>
  <a id="newtasklink" href="{CreateURL('newtask', $proj_id)}"
    accesskey="a">{L('addnewtask')}</a>
  </li>
<?php
endif;

if ($user->perms['view_reports']): ?>
  <li>
  <a id="reportslink" href="{CreateURL('reports', null, null, array('project' => $proj->id))}">{L('reports')}</a>
  </li>
<?php
endif; ?>
  <li>
  <a id="lastsearchlink" onclick="activelink('lastsearchlink')" href="javascript:showhidestuff('mysearches')" accesskey="m">{L('mysearch')}</a>
  <div id="mysearches">
    <strong id="nosearches" <?php if(count($user->searches)): ?>class="hide"<?php endif; ?>>{L('nosearches')}</strong>
    <table id="mysearchestable">
    <?php foreach ($user->searches as $search): ?>
    <tr id="rs{$search['id']}" <?php if($search == end($user->searches)): ?>class="last"<?php endif; ?>>
      <td><a href="{$baseurl}?{Url::query_from_array(unserialize($search['search_string']))}">{$search['name']}</a></td>
      <td class="searches_delete">
        <a href="javascript:deletesearch('{$search['id']}','{$baseurl}')">
        <img src="{$this->get_image('button_cancel')}" width="16" height="16" title="{L('delete')}" alt="{L('delete')}" /></a>
      </td>
    </tr>
    <?php endforeach; ?>
    </table>
  </div>
  </li>
<?php if ($user->perms['is_admin']): ?>
  <li>
  <a id="optionslink" href="{CreateURL('admin', 'prefs')}">{L('admintoolbox')}</a>
  </li>
<?php endif; ?>

<?php if ($proj->id != 0 && $user->perms['manage_project']): ?>
  <li>
  <a id="projectslink"
    href="{CreateURL('pm', 'prefs', $proj_id)}">{L('manageproject')}</a>
  </li>
<?php endif; ?>
<?php if ($user->perms['manage_project'] && $pm_pendingreq_num): ?>
  <li>
<?php else: ?>
  <li class="last">
<?php endif; ?>
  <a id="logoutlink" href="{CreateURL('logout', null)}"
    accesskey="l">{L('logout')}</a>
  </li>
<?php if ($user->perms['manage_project'] && $pm_pendingreq_num): ?>
  <li class="last">
  <a class="pendingreq attention"
    href="{CreateURL('pm', 'pendingreq', $proj_id)}">{$pm_pendingreq_num} {L('pendingreq')}</a>
  </li>
<?php endif; ?>
</ul>

</div>
