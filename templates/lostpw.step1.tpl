<fieldset class="box">
    <legend>{L('lostpw')}</legend>

    <p>{L('lostpwexplain')}</p>

    <form action="{$this->url('lostpw')}" method="post">
        <p><b>{L('usernameoremail')}</b>

        <input type="hidden" name="action" value="sendmagic" />
        <input class="text" type="text" value="{Post::val('user_name')}" name="user_name" size="20" maxlength="20" />
        <button type="submit">{L('sendlink')}</button>
        </p>
    </form>
</fieldset>
