<fieldset>
<legend>{$language['registernewuser']}</legend>

<form action="{$baseurl}" name="registernewuser" method="post" id="registernewuser">
  <p>{$language['entercode']}</p>
  <table class="admin">
    <tr>
      <td><label for="confirmation_code">{$language['confirmationcode']}</label></td>
      <td><input id="confirmation_code" class="text" name="confirmation_code" type="text" size="20" maxlength="20" /></td>
    </tr>
    <tr>
      <td><label for="user_pass">{$language['password']}</label></td>
      <td><input id="user_pass" class="password" name="user_pass" type="password" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="user_pass2">{$language['confirmpass']}</label></td>
      <td><input id="user_pass2" class="password" name="user_pass2" type="password" size="20" maxlength="100" /></td>
    </tr>
  </table>

    <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="registeruser" />
        <input type="hidden" name="magic_url" value="{Get::val('magic')}" />
        <button type="submit" name="buSubmit">{$language['registeraccount']}</button>
    </div>
</form>
</fieldset>
