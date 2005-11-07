<h1>{$register_text['registernewuser']}</h1>

<form action="{$baseurl}" name="form2" method="post" id="registernewuser">
  <table class="admin">
    <tr>
      <td colspan="2">{$register_text['entercode']}</td>
    </tr>
    <tr>
      <td><label for="confirmation_code">{$register_text['confirmationcode']}</label></td>
      <td><input id="confirmation_code" name="confirmation_code" type="text" size="20" maxlength="20" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="user_pass">{$register_text['password']}</label></td>
      <td><input id="user_pass" name="user_pass" type="password" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="user_pass2">{$register_text['confirmpass']}</label></td>
      <td><input id="user_pass2" name="user_pass2" type="password" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="registeruser" />
        <input type="hidden" name="magic_url" value="{Get::val('magic')}" />
        <input class="adminbutton" type="submit" name="buSubmit" value="{$register_text['registeraccount']}" />
      </td>
    </tr>
  </table>
</form>
