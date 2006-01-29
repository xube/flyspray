<fieldset class="admin">
    <legend>{$language['lostpw']}</legend>

    <p>{$language['lostpwexplain']}</p>

    <form action="{$baseurl}index.php" method="post">
        <p><b>{$language['username']}</b>

        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="sendmagic" />
        <input class="text" type="text" name="user_name" size="20" maxlength="20" />
        <button type="submit">{$language['sendlink']}</button>
        </p>
    </form>
</fieldset>
