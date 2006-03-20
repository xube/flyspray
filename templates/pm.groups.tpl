<div id="toolbox">
  <h3>{L('pmtoolbox')} :: {$proj->prefs['project_title']} : {L('groupmanage')}</h3>
  <fieldset class="admin">
    <legend>{L('usersandgroups')}</legend>
    <p><a href="{CreateURL('newgroup', $proj->id)}">{L('newgroup')}</a></p>
    <?php
    $this->display('common.groups.tpl');
    ?>
  </fieldset>
</div>
