<h1>{$newuser_text['registernewuser']}</h1>

<p><em>{$newuser_text['requiredfields']}</em> <strong>*</strong></p>

<form action="{$baseurl}index.php" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="newuser" />
        <label for="username">{$newuser_text['username']}</label>
      </td>
      <td><input id="username" name="user_name" class="text" type="text" size="20" maxlength="20" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass">{$newuser_text['password']}</label></td>
      <td><input id="userpass" class="password" name="user_pass" type="password" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass2">{$newuser_text['confirmpass']}</label></td>
      <td><input id="userpass2" class="password" name="user_pass2" type="password" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="realname">{$newuser_text['realname']}</label></td>
      <td><input id="realname" name="real_name" class="text" type="text" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="emailaddress">{$newuser_text['emailaddress']}</label></td>
      <td><input id="emailaddress" name="email_address" class="text" type="text" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="jabberid">{$newuser_text['jabberid']}</label></td>
      <td><input id="jabberid" name="jabber_id" class="text" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label>{$newuser_text['notifications']}</label></td>
      <td>
        <input type="radio" name="notify_type" value="0" checked="checked" />{$newuser_text['none']} <br />
        <input type="radio" name="notify_type" value="1" />{$newuser_text['email']} <br />
        <input type="radio" name="notify_type" value="2" />{$newuser_text['jabber']} <br />
      </td>
    </tr>
    <?php if ($user->perms['is_admin']): ?>
    <tr>
      <td><label for="groupin">{$newuser_text['globalgroup']}</label></td>
      <td>
        <select id="groupin" class="adminlist" name="group_in">
          {!tpl_options($group_names)}
        </select>
      </td>
    </tr>
    <?php endif; ?>
    <tr>
      <td colspan="2" class="buttons">
        <button type="submit">{$newuser_text['registeraccount']}</button>
      </td>
    </tr>
  </table>
</form>
