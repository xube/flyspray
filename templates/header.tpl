    <?php $this->display('baseheader.tpl'); ?>
    <!-- Remove this to remove the logo -->
    <h1 id="title"><a href="{$baseurl}">{$proj->prefs['project_title']}</a></h1>

    <?php $this->display('links.tpl'); ?>

    <?php if (isset($_SESSION['SUCCESS']) && isset($_SESSION['ERROR'])): ?>
    <div id="mixedbar" class="mixed bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['SUCCESS']}<br />{$_SESSION['ERROR']}</div></div>
    <?php elseif (isset($_SESSION['ERROR'])): ?>
    <div id="errorbar" class="error bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['ERROR']}</div></div>
    <?php elseif (isset($_SESSION['SUCCESS'])): ?>
    <div id="successbar" class="success bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['SUCCESS']}</div></div>
    <?php endif; ?>

    <div id="content">
      <div id="showtask">
        <form action="{$baseurl}index.php" method="get">
          <div>
            <button type="submit">{L('showtask')} #</button>
            <input id="taskid" name="show_task" class="text" type="text" size="10" accesskey="t" />
          </div>
        </form>
      </div>

      <div class="clear"></div>
      <?php $show_message = array('details', 'index', 'newtask', 'reports', 'depends');
            $actions = explode('.', Req::val('action'));
            if ($proj->prefs['intro_message'] && (in_array($do, $show_message) || in_array(reset($actions), $show_message))): ?>
      <div id="intromessage">{!TextFormatter::render($proj->prefs['intro_message'], false, 'msg', $proj->id,
                               ($proj->prefs['last_updated'] < $proj->prefs['cache_update']) ? $proj->prefs['pm_instructions'] : '')}</div>
      <?php endif; ?>
