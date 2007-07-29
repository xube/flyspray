<div id="menu">

<?php if ($user->isAnon()):
          $this->display('loginbox.tpl');
      else: ?>
      <script type="text/javascript">updateTimezone({$user->infos['time_zone']});</script>
<ul id="menu-list">
  <li class="first" onmouseover="perms.do_later('show')" onmouseout="perms.hide()">
	<a id="profilelink" href="{$this->url('myprofile')}" title="{L('editmydetails')}">
	  <em>{$user->infos['real_name']} ({$user->infos['user_name']})</em>
	</a>
	<div id="permissions">
	  {!tpl_draw_perms($user->perms)}
	</div>
  </li>
  <li>
  <a id="lastsearchlink" href="#" accesskey="m" onclick="showhidestuff('mysearches');return false;" class="inactive">{L('mysearch')}</a>
  <div id="mysearches">
    <?php $this->display('links.searches.tpl'); ?>
  </div>
  </li>
<?php if ($user->perms('is_admin')): ?>
  <li>
  <a id="optionslink" href="{$this->url(array('admin', 'prefs'))}">{L('admintoolbox')}</a>
  </li>
<?php endif; ?>

  <li>
  <a id="logoutlink" href="{$this->url('authenticate', array('logout' => 1))}"
    accesskey="l">{L('logout')}</a>
  </li>
  <?php if (isset($_SESSION['was_locked'])): ?>
  <li>
    <span id="locked">{L('accountwaslocked')}</span>
  </li>
  <?php elseif (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
  <li>
    <span id="locked">{sprintf(L('failedattempts'), $_SESSION['login_attempts'])}</span>
  </li>
  <?php endif; unset($_SESSION['login_attempts'], $_SESSION['was_locked']); ?>

</ul>
<?php endif; ?>
</div>

<div id="pm-menu">

<div id="projectselector">
    <form id="projectselectorform" action="{$this->relativeUrl($baseurl)}index.php" method="get">
       <div>
        <select name="project">
          {!tpl_options(array_merge(array(0 => L('allprojects')), $fs->projects), $proj->id)}
        </select>
        <button type="submit" value="1" name="switch">{L('switch')}</button>
        <input type="hidden" name="do" value="{$do}" />
        <?php $check = array('area', 'id', 'user_id');
              if ($do == 'reports') {
                $check = array_merge($check, array('open', 'close', 'edit', 'assign', 'repdate', 'comments', 'attachments',
                                'related', 'notifications', 'reminders', 'within', 'duein', 'fromdate', 'todate'));
              } else if ($do == 'pm' || $do == 'admin') {
                $check = array_merge($check, $fs->perms); // save a group's permission settings
              }
              foreach ($check as $key):
              if (Get::has($key)): ?>
        <input type="hidden" name="{$key}" value="{Get::val($key)}" />
        <?php endif;
              endforeach; ?>
      </div>
    </form>
</div>

<ul id="pm-menu-list">
    <li class="first">
      <a id="toplevellink" href="{$this->url('toplevel')}">{L('overview')}</a>
    </li>

    <li>
    <a id="homelink"
        href="{$this->url(array('index', 'proj' . $proj->id))}">{L('tasklist')}</a>
    </li>

    <?php if ($proj->id && ($user->perms('open_new_tasks') || $user->isAnon() && $proj->prefs['anon_open']) ): ?>
      <li>
        <a id="newtasklink" href="{$this->url(array('newtask', 'proj' . $proj->id))}"
           accesskey="a">{L('addnewtask')}</a>
      </li>
    <?php elseif(!$proj->id && $user->can_open_task($fs->prefs['default_project'])): ?>
      <li>
        <a id="newtasklink" href="{$this->url(array('newtask', 'proj' . $fs->prefs['default_project']))}"
           accesskey="a">{L('addnewtask')}</a>
      </li>
    <?php endif; ?>

    <?php if ($user->perms('view_reports')): ?>
      <li>
      <a id="reportslink" href="{$this->url(array('reports', 'proj' . $proj->id))}">{L('reports')}</a>
      </li>
    <?php endif; ?>

    <?php if ($proj->id): ?>
    <?php if ($proj->prefs['roadmap_field']): ?>
    <li>
    <a id="roadmaplink"
        href="{$this->url(array('roadmap', 'proj' . $proj->id))}">{L('roadmap')}</a>
    </li>
    <?php endif; ?>
    <li>
    <a id="changeloglink"
        href="{$this->url(array('changelog', 'proj' . $proj->id))}">{L('changelog')}</a>
    </li>
    <?php endif; ?>

    <?php if ($proj->id && $user->perms('manage_project')): ?>
      <li>
      <a id="projectslink"
        href="{$this->url(array('pm', 'proj' . $proj->id, 'prefs'))}">{L('manageproject')}</a>
      </li>
    <?php endif; ?>

    <?php if ($proj->id && isset($pm_pendingreq_num) && $pm_pendingreq_num): ?>
      <li>
        <a class="pendingreq attention"
           href="{$this->url(array('pm', 'proj' . $proj->id, 'pendingreq'))}">{$pm_pendingreq_num} {L('pendingreq')}</a>
      </li>
    <?php endif; ?>
</ul>
</div>
