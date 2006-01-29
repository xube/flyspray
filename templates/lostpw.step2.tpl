<fieldset class="admin">
<legend>{$language['changepass']}</legend>

    <form action="index.php" method="post">
    <table>
      <tr>
        <td><label for="pass1">{$language['changepass']}</label></td>
        <td><input class="password" id="pass1" type="password" name="pass1" size="20" /></td>
      </tr>
      <tr>
        <td><label for="pass2">{$language['confirmpass']}</label></td>
        <td><input class="password" id="pass2" type="password" name="pass2" size="20" /></td>
      </tr>
      </table>
      
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="chpass" />
        <input type="hidden" name="magic_url" value="{Get::val('magic')}" />
        <button type="submit">{$language['savenewpass']}</button>
      </div>
    </form>
</fieldset>

