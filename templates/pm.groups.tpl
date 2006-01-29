<div id="toolbox">
  <h3>{$language['pmtoolbox']} :: {$proj->prefs['project_title']} : {$language['groupmanage']}</h3>
  <fieldset class="admin">
    <legend>{$language['usersandgroups']}</legend>
    <p><a href="{CreateURL('newgroup', $proj->id)}">{$language['newgroup']}</a></p>
    <?php
    $this->display('common.groups.tpl');
    ?>
  </fieldset>
</div>
