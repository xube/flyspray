<fieldset class="admin">
<legend>{$register_text['registernewuser']}</legend>

<form action="{$baseurl}" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td><label for="username">{$register_text['username']}</label></td>
      <td><input class="required text" id="username" name="user_name" type="text" size="20" maxlength="32" /> {$register_text['validusername']}</td>
    </tr>
    <tr>
      <td><label for="realname">{$register_text['realname']}</label></td>
      <td><input class="required text" id="realname" name="real_name" type="text" size="30" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="emailaddress">{$register_text['emailaddress']}</label></td>
      <td><input id="emailaddress" name="email_address" class="text" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="jabberid">{$register_text['jabberid']}</label></td>
      <td><input id="jabberid" name="jabber_id" type="text" class="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="notify_type">{$register_text['notifications']}</label></td>
      <td>
        <select id="notify_type" name="notify_type">
          <option value="1">{$register_text['email']}</option>
          <option value="2">{$register_text['jabber']}</option>
        </select>
      </td>
    </tr>
  </table>
  
  <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="sendcode" />
    <button type="submit" name="buSubmit">{$register_text['sendcode']}</button>
  </div>
  
  <p>{!$register_text['note']}</p>
</form>
</fieldset>
