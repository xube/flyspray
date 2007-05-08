<form id="projectselectorform" action="{$_SERVER['SCRIPT_NAME']}" method="get">
	<p>
		<select name="project">
			{!tpl_options(array_merge(array(0 => L('allprojects')), $fs->projects), $proj->id)}
		</select>
		<button type="submit" value="1" name="switch">{L('switch')}</button>
		<input type="hidden" name="do" value="{$do}" />
		<?php
			$check = array('area', 'id');
			if ($do == 'reports') {
				$check = array_merge($check, array('open', 'close', 'edit', 'assign', 'repdate', 'comments', 'attachments',
							'related', 'notifications', 'reminders', 'within', 'duein', 'fromdate', 'todate'));
			}
			foreach ($check as $key):
				if (Get::has($key)): ?>
					<input type="hidden" name="{$key}" value="{Get::val($key)}" />
			<?php endif; endforeach; ?>
	</p>
</form>

<ul class="menu-list">
    <li class="first">
      <a id="toplevellink" href="{CreateURL('toplevel')}">{L('overview')}</a>
    </li>

    <li>
    <a id="homelink"
        href="{CreateURL(array('index', 'proj' . $proj->id))}">{L('tasklist')}</a>
    </li>

    <?php if ($proj->id && $user->perms('open_new_tasks')): ?>
      <li>
      <a id="newtasklink" href="{CreateURL(array('newtask', 'proj' . $proj->id))}"
        accesskey="a">{L('addnewtask')}</a>
      </li>
    <?php elseif ($proj->id && $user->isAnon() && $proj->prefs['anon_open']): ?>
      <li>
        <a id="anonopen" href="?do=newtask&amp;project={$proj->id}">{L('opentaskanon')}</a>
      </li>
    <?php endif; ?>

    <?php if ($user->perms('view_reports')): ?>
      <li>
      <a id="reportslink" href="{CreateURL(array('reports', 'proj' . $proj->id))}">{L('reports')}</a>
      </li>
    <?php endif; ?>

    <?php if ($proj->id): ?>
    <li>
    <a id="roadmaplink"
        href="{CreateURL(array('roadmap', 'proj' . $proj->id))}">{L('roadmap')}</a>
    </li>

    <li>
 	    <a id="changeloglink"
 	    href="{CreateURL(array('changelog', 'proj' . $proj->id))}">{L('changelog')}</a>
 	</li>
    <?php endif; ?>

    <?php if ($proj->id && $user->perms('manage_project')): ?>
      <li>
      <a id="projectslink"
        href="{CreateURL(array('pm', 'proj' . $proj->id, 'prefs'))}">{L('manageproject')}</a>
      </li>
    <?php endif; ?>

    <?php if ($proj->id && isset($pm_pendingreq_num) && $pm_pendingreq_num): ?>
      <li>
        <a class="pendingreq attention"
           href="{CreateURL(array('pm', 'proj' . $proj->id, 'pendingreq'))}">{$pm_pendingreq_num} {L('pendingreq')}</a>
      </li>
    <?php endif; ?>
</ul>