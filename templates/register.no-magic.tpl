<fieldset class="admin">
<legend>{L('registernewuser')}</legend>

<form action="{$baseurl}" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td><label for="username">{L('username')}</label></td>
      <td><input class="required text" id="username" name="user_name" type="text" size="20" maxlength="32" onblur="checkname(this.value);" /> {L('validusername')}<br><strong><span id="errormessage"></span></strong></td>
    </tr>
    <tr>
      <td><label for="realname">{L('realname')}</label></td>
      <td><input class="required text" id="realname" name="real_name" type="text" size="30" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="emailaddress">{L('emailaddress')}</label></td>
      <td><input id="emailaddress" name="email_address" class="text" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="jabberid">{L('jabberid')}</label></td>
      <td><input id="jabberid" name="jabber_id" type="text" class="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="notify_type">{L('notifications')}</label></td>
      <td>
        <select id="notify_type" name="notify_type">
          <option value="1">{L('email')}</option>
          <option value="2">{L('jabber')}</option>
          <option value="3">{L('both')}</option>
        </select>
      </td> 
    </tr>
  </table>
 <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="sendcode" />
    <button type="submit" name="buSubmit" id="buSubmit">{L('sendcode')}</button>
  </div>
  
  <p>{!L('note')}</p>
</form>
</fieldset>
