<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['verlisted']}</h3>
  <fieldset class="admin">
    <legend>{$admin_text['versions']}</legend>
    <?php
    $this->assign('list_type', 'version');
    $this->assign('rows', $proj->listVersions(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
