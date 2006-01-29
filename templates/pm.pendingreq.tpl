<div id="toolbox">
  <h3>{$language['pmtoolbox']} :: {$language['pendingrequests']}</h3>

  <fieldset class="admin">
    <legend>{$language['pendingrequests']}</legend>

    <?php if (!count($pendings)): ?>
    {$language['nopendingreq']}
    <?php else: ?>
    <table class="requests">
      <tr>
        <th>{$language['eventdesc']}</th>
        <th>{$language['requestedby']}</th>
        <th>{$language['daterequested']}</th>
        <th>{$language['reasongiven']}</th>
        <th> </th>
      </tr>
      <?php foreach ($pendings as $req): ?>
      <tr>
        <td>
        <?php if ($req['request_type'] == 1) : ?>
        {$language['closetask']} -
        <a href="{CreateURL('details', $req['task_id'])}">FS#{$req['task_id']} :
          {$req['item_summary']}</a>
        <?php elseif ($req['request_type'] == 2) : ?>
        {$language['reopentask']} -
        <a href="{CreateURL('details', $req['task_id'])}">FS#{$req['task_id']} :
          {$req['item_summary']}</a>
        <?php elseif ($req['request_type'] == 3) : ?>
        {$language['applymember']}
        <?php endif; ?>
        </td>
        <td>{!tpl_userlink($req['user_id'])}</td>
        <td>{formatDate($req['time_submitted'], true)}</td>
        <td>{$req['reason_given']}</td>
        <td>
          <a href="#" class="button" onclick="showhidestuff('denyform{$req['request_id']}');">{$language['deny']}</a>
          <div id="denyform{$req['request_id']}" class="denyform">
            <form action="{$baseurl}" method="post">
              <div>
                <input type="hidden" name="do" value="modify" />
                <input type="hidden" name="action" value="denypmreq" />
                <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
                <input type="hidden" name="req_id" value="{$req['request_id']}" />
                {$language['reasonforreq']}
                <textarea cols="40" rows="5" name="deny_reason"></textarea>
                <br />
                <button type="submit">{$language['deny']}</button>
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
