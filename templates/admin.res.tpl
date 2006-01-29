<div id="toolbox">
  <h3>{$language['admintoolboxlong']} :: {$language['resolutionlist']}</h3>

  <fieldset class="admin">
    <legend>{$language['resolutions']}</legend>
    <?php
    $this->assign('list_type', 'resolution');
    $this->assign('rows', $proj->listResolutions(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
