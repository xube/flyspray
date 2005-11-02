<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$proj->prefs['project_title']} : {$pm_text['oslisted']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['operatingsystems']}</legend>
    <?php
    $this->assign('list_type', 'os');
    $this->assign('rows', $proj->listOs());
    $this->display('pm._list.tpl');
    ?>
  </fieldset>
</div>
