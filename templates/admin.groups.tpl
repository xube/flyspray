<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('usersandgroups')}</h3>
  <fieldset class="admin">
    <legend>{L('usersandgroups')}</legend>
    <p>
      <img src="{$this->get_image('personal')}" alt="" class="middle" /> <a href="{CreateURL('newuser', $proj->id)}">{L('newuser')}</a>
    </p>
    <p>
      <img src="{$this->get_image('kuser')}" alt="" class="middle" /> <a href="{CreateURL('newgroup', $proj->id)}">{L('newgroup')}</a>
    </p>

    <div class="groupedit">
    <form action="{$baseurl}" method="get">
        <div>
            <label for="selectgroup">{L('editgroup')}</label>
            <select name="id" id="selectgroup">{!tpl_options(Flyspray::ListGroups())}</select>
            <button type="submit">{L('edit')}</button>
            <input type="hidden" name="do" value="admin" />
            <input type="hidden" name="area" value="editgroup" />
        </div>
    </form>
    
    <form action="{$baseurl}" method="get">
        <div>
            <label for="edit_user">{L('edituser')}</label>
            {!tpl_userselect('user_id', '', 'edit_user')}       
            <button type="submit">{L('edit')}</button>

            <input type="hidden" name="do" value="admin" />
            <input type="hidden" name="area" value="users" />
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
        </div>
    </form>
    </div>
  </fieldset>
</div>
