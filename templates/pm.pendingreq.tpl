<div id="toolbox">
  <h3>{L('pmtoolbox')} :: {L('pendingrequests')}</h3>

  <fieldset class="admin">
    <legend>{L('pendingrequests')}</legend>

    <?php if (!count($pendings)): ?>
    {L('nopendingreq')}
    <?php else: ?>
    <table class="requests">
      <tr>
        <th>{L('eventdesc')}</th>
        <th>{L('requestedby')}</th>
        <th>{L('daterequested')}</th>
        <th>{L('reasongiven')}</th>
        <th> </th>
      </tr>
      <?php foreach ($pendings as $req): ?>
      <tr>
        <td>
        <?php if ($req['request_type'] == 1) : ?>
        {L('closetask')} -
        <a href="{CreateURL('details', $req['task_id'])}">FS#{$req['task_id']} :
          {$req['item_summary']}</a>
        <?php elseif ($req['request_type'] == 2) : ?>
        {L('reopentask')} -
        <a href="{CreateURL('details', $req['task_id'])}">FS#{$req['task_id']} :
          {$req['item_summary']}</a>
        <?php elseif ($req['request_type'] == 3) : ?>
        {L('applymember')}
        <?php endif; ?>
        </td>
        <td>{!tpl_userlink($req['user_id'])}</td>
        <td>{formatDate($req['time_submitted'], true)}</td>
        <td>{$req['reason_given']}</td>
        <td>
          <a href="#" class="button" onclick="showhidestuff('denyform{$req['request_id']}');">{L('deny')}</a>
          <div id="denyform{$req['request_id']}" class="denyform">
            <form action="{$baseurl}" method="post">
              <div>
                <input type="hidden" name="do" value="modify" />
                <input type="hidden" name="action" value="denypmreq" />
                <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
                <input type="hidden" name="req_id" value="{$req['request_id']}" />
                {L('reasonforreq')}
                <textarea cols="40" rows="5" name="deny_reason"></textarea>
                <br />
                <button type="submit">{L('deny')}</button>
              </div>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>

  </fieldset>
</div>
