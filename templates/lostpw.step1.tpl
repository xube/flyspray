<fieldset class="admin">
    <legend>{$admin_text['lostpw']}</legend>

    <p>{$admin_text['lostpwexplain']}</p>

    <form action="{$baseurl}index.php" method="post">
        <p><b>{$admin_text['username']}</b>

        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="sendmagic" />
        <input class="text" type="text" name="user_name" size="20" maxlength="20" />
        <button type="submit">{$admin_text['sendlink']}</button>
        </p>
    </form>
</fieldset>
