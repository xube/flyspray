<h1>{$register_text['registernewuser']}</h1>

<p><em>{$register_text['requiredfields']}</em> <strong>*</strong></p>

<form action="{$baseurl}" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td><label for="username">{$register_text['username']}</label></td>
      <td><input id="username" name="user_name" type="text" size="20" maxlength="20" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="realname">{$register_text['realname']}</label></td>
      <td><input id="realname" name="real_name" type="text" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="emailaddress">{$register_text['emailaddress']}</label></td>
      <td><input id="emailaddress" name="email_address" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="jabberid">{$register_text['jabberid']}</label></td>
      <td><input id="jabberid" name="jabber_id" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label>{$register_text['notifications']}</label></td>
      <td>
        <input type="radio" name="notify_type" value="1" checked="checked" />{$register_text['email']} <br />
        <input type="radio" name="notify_type" value="2" />{$register_text['jabber']}
      </td>
    </tr>
    <tr>
      <td colspan="2">{$register_text['note']}</td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="sendcode" />
        <input class="adminbutton" type="submit" name="buSubmit" value="{$register_text['sendcode']}" />
      </td>
    </tr>
  </table>
</form>
