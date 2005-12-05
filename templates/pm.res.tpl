<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$proj->prefs['project_title']} : {$pm_text['resed']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['resolutions']}</legend>
    <?php
    $this->assign('list_type', 'resolution');
    $this->assign('rows', $proj->listResolutions(false));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
