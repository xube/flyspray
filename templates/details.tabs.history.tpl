<div id="history" class="tab">
  <?php if ($details): ?>
  <b>{$language['selectedhistory']}</b>
  &mdash;
  <a href="{CreateURL('details', Get::val('id'), null, array('history' => 'yep'))}#history">
	 {$language['showallhistory']}</a>
  <?php endif; ?>
  <table class="history">
	 <tr>
		<th>{$language['eventdate']}</th>
		<th>{$language['user']}</th>
		<th>{$language['event']}</th>
	 </tr>

	 <?php if (!count($histories)): ?>
	 <tr><td colspan="3">{$language['nohistory']}</td></tr>
	 <?php else: ?>
	 <?php foreach ($histories as $history): ?>
	 <tr>
		<td>{formatDate($history['event_date'], true)}</td>
		<td>{!tpl_userlink($history['user_id'])}</td>
		<td>{!event_description($history)}</td>
	 </tr>
	 <?php endforeach; ?>
	 <?php endif; ?>
  </table>

  <?php if ($details && isset($GLOBALS['details_previous']) && isset($GLOBALS['details_new'])): ?>
  <table class="history">
	 <tr>
		<th>{$language['previousvalue']}</th>
		<th>{$language['newvalue']}</th>
	 </tr>
	 <tr>
		<td>{!$GLOBALS['details_previous']}</td>
		<td>{!$GLOBALS['details_new']}</td>
	 </tr>
  </table>
  <?php endif; ?>
</div>
