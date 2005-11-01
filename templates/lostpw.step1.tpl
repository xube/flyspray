<h3>{$admin_text['lostpw']}</h3>

<p>{$admin_text['lostpwexplain']}</p>
<br />

<div class="admin">
  <form action="{$baseurl}index.php" method="post">
    <b>{$admin_text['username']}</b>

    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="sendmagic" />
    <input class="admintext" type="text" name="user_name" size="20" maxlength="20" />
    <input class="adminbutton" type="submit" value="{$admin_text['sendlink']}" />
  </form>
</div>
