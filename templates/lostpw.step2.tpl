<h3>{$admin_text['changepass']}</h3>
<br />

<div class="admin">
  <form action="index.php" method="post">
    <table>
      <tr>
        <td><b>{$admin_text['changepass']}</b></td>
        <td><input class="admintext" type="password" name="pass1" size="20" /></td>
      </tr>
      <tr>
        <td><b>{$admin_text['confirmpass']}</b></td>
        <td><input class="admintext" type="password" name="pass2" size="20" /></td>
      </tr>
      <tr>
        <td></td>
        <td>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="chpass" />
          <input type="hidden" name="magic_url" value="{Get::val('magic')}" />
          <input class="adminbutton" type="submit" value="{$admin_text['savenewpass']}" />
        </td>
      </tr>
    </table>
  </form>
</div>

