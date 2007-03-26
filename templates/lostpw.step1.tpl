<fieldset class="box">
    <legend>{L('lostpw')}</legend>

    <p>{L('lostpwexplain')}</p>

    <form action="{CreateUrl('lostpw')}" method="post">
        <p><b>{L('username')}</b>

        <input type="hidden" name="action" value="sendmagic" />
        <input class="text" type="text" value="{Post::val('user_name')}" name="user_name" size="20" maxlength="20" />
        <button type="submit">{L('sendlink')}</button>
        </p>
    </form>
</fieldset>
