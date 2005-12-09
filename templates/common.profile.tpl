<fieldset class="admin">
  <form action="{$baseurl}" method="post">
    <table class="admin">
      <tr>
        <td><label for="realname">{$admin_text['realname']}</label></td>
        <td>
          <input id="realname" type="text" name="real_name" size="50" maxlength="100"
            value="{$theuser->infos['real_name']}" />
        </td>
      </tr>
      <tr>
        <td><label for="emailaddress">{$admin_text['emailaddress']}</label></td>
        <td>
          <input id="emailaddress" type="text" name="email_address" size="50" maxlength="100"
            value="{$theuser->infos['email_address']}" />
        </td>
      </tr>
      <tr>
        <td><label for="jabberid">{$admin_text['jabberid']}</label></td>
        <td>
          <input id="jabberid" type="text" name="jabber_id" size="50" maxlength="100"
            value="{$theuser->infos['jabber_id']}" />
        </td>
      </tr>
      <tr>
        <td><label for="notifytype">{$admin_text['notifytype']}</label></td>
        <td>
          <?php if ($fs->prefs['user_notify'] == '1'): ?>
          <select id="notifytype" name="notify_type">
            {!tpl_options(array('None', 'Email', 'Jabber'), $theuser->infos['notify_type'])}
          </select>
          <?php else: ?>
          {$admin_text['setglobally']}
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td><label for="dateformat">{$admin_text['dateformat']}</label></td>
        <td>
          <input id="dateformat" name="dateformat" type="text" size="40" maxlength="30"
            value="{$theuser->infos['dateformat']}" />
        </td>
      </tr>
      <tr>
        <td><label for="dateformat_extended">{$admin_text['dateformat_extended']}</label></td>
        <td>
          <input id="dateformat_extended" name="dateformat_extended" type="text"
            size="40" maxlength="30" value="{$theuser->infos['dateformat_extended']}" />
        </td>
      </tr>
      <tr>
        <td><label for="tasks_perpage">{$admin_text['tasksperpage']}</label></td>
        <td>
          <select name="tasks_perpage" id="tasks_perpage">
            {!tpl_options(array(10, 25, 50, 100, 250), $theuser->infos['tasks_perpage'], true)}
          </select>
        </td>
      </tr>
      <?php if ($user->perms['is_admin']): ?>
      <tr>
        <td><label for="accountenabled">{$admin_text['accountenabled']}</label></td>
        <td>{!tpl_checkbox('account_enabled', $theuser->infos['account_enabled'], 'accountenabled')}</td>
      </tr>
      <tr>
        <td><label for="groupin">{$newuser_text['globalgroup']}</label></td>
        <td>
          <select id="groupin" class="adminlist" name="group_in">
            {!tpl_options($groups, $theuser->infos['global_group'])}
          </select>
          <input type="hidden" name="record_id" value="{$theuser->infos['global_record_id']}" />
        </td>
      </tr>
      <?php endif; ?>
      <tr>
        <td colspan="2"><hr /></td>
      </tr>
      <tr>
        <td><label for="changepass">{$admin_text['changepass']}</label></td>
        <td><input id="changepass" type="password" name="changepass" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td><label for="confirmpass">{$admin_text['confirmpass']}</label></td>
        <td><input id="confirmpass" type="password" name="confirmpass" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td colspan="2" class="buttons">
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="edituser" />
          <input type="hidden" name="user_id" value="{$theuser->id}" />
          <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
          <input class="adminbutton" type="submit" value="{$admin_text['updatedetails']}" />
        </td>
      </tr>
    </table>
  </form>
</fieldset>