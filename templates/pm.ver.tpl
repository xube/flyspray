<div id="toolbox">
  <h3>{$language['pmtoolbox']} :: {$proj->prefs['project_title']} : {$language['verlisted']}</h3>

  <fieldset class="admin">
    <legend>{$language['versions']}</legend>
    <?php
    $this->assign('list_type', 'version');
    $this->assign('rows', $proj->listVersions(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
