<div id="toolbox">
  <h3>{$language['admintoolboxlong']} :: {$language['oslist']}</h3>

  <fieldset class="admin">
    <legend>{$language['operatingsystems']}</legend>
    <?php
    $this->assign('list_type', 'os');
    $this->assign('rows', $proj->listOs(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
