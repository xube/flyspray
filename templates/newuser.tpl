<fieldset class="admin">
<legend>{L('registernewuser')}</legend>

<form action="{$baseurl}index.php" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="newuser" />
        <label for="username">{L('username')}</label>
      </td>
      <td><input id="username" name="user_name" class="required text" type="text" size="20" maxlength="20" onblur="checkname(this.value);" /><br><span id="errormessage"></span></td>
    </tr>
    <tr>
      <td><label for="userpass">{L('password')}</label></td>
      <td><input id="userpass" class="required password" name="user_pass" type="password" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="userpass2">{L('confirmpass')}</label></td>
      <td><input id="userpass2" class="required password" name="user_pass2" type="password" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="realname">{L('realname')}</label></td>
      <td><input id="realname" name="real_name" class="required text" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="emailaddress">{L('emailaddress')}</label></td>
      <td><input id="emailaddress" name="email_address" class="text" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="jabberid">{L('jabberid')}</label></td>
      <td><input id="jabberid" name="jabber_id" class="text" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="notify_type">{L('notifications')}</label></td>
      <td>
        <select id="notify_type" name="notify_type">
          <option value="0">{L('none')}</option>
          <option value="1">{L('email')}</option>
          <option value="2">{L('jabber')}</option>
          <option value="3">{L('both')}</option>
        </select>
      </td>
    </tr>
    <?php if ($user->perms['is_admin']): ?>
    <tr>
      <td><label for="groupin">{L('globalgroup')}</label></td>
      <td>
        <select id="groupin" class="adminlist" name="group_in">
          {!tpl_options($group_names)}
        </select>
      </td>
    </tr>
    <?php endif; ?>
    <tr>
      <td colspan="2" class="buttons">
        <button type="submit" id="buSubmit">{L('registeraccount')}</button>
      </td>
    </tr>
  </table>
</form>
</fieldset>
