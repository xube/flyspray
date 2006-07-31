<div id="toolbox">
  <h3>{L('pmtoolbox')} :: {$proj->prefs['project_title']} : {L('groupmanage')}</h3>
  <fieldset class="admin">
    <legend>{L('usersandgroups')}</legend>
    <?php if ($user->perms('is_admin')): ?>
    <p>
      <img src="{$this->get_image('personal')}" alt="" class="middle" /> <a href="{CreateURL('newuser', $proj->id)}">{L('newuser')}</a>
    </p>
    <?php endif; ?>
    <p>
      <img src="{$this->get_image('kuser')}" alt="" class="middle" /> <a href="{CreateURL('newgroup', $proj->id)}">{L('newgroup')}</a>
    </p>

    <div class="groupedit">
    <form action="{$baseurl}" method="get">
        <div>
            <label for="selectgroup">{L('editgroup')}</label>
            <select name="id" id="selectgroup">{!tpl_options($fs->ListGroups($proj->id))}</select>
            <button type="submit">{L('edit')}</button>
            <input type="hidden" name="do" value="pm" />
            <input type="hidden" name="area" value="editgroup" />
        </div>
    </form>
    
    <form action="{$baseurl}" method="get">
        <div>
            <label for="edit_user">{L('edituser')}</label>
            {!tpl_userselect('uid', '', 'edit_user')}               
            <button type="submit">{L('edit')}</button>

            <input type="hidden" name="do" value="user" />
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
        </div>
    </form>
    </div>
  </fieldset>
</div>
