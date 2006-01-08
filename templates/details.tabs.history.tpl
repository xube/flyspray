<div id="history" class="tab">
  <?php if ($details): ?>
  <b>{$details_text['selectedhistory']}</b>
  &mdash;
  <a href="{$fs->createUrl('details', Get::val('id'), null, array('history' => 'yep'))}#history">
	 {$details_text['showallhistory']}</a>
  <?php endif; ?>
  <table class="history">
	 <tr>
		<th>{$details_text['eventdate']}</th>
		<th>{$details_text['user']}</th>
		<th>{$details_text['event']}</th>
	 </tr>

	 <?php if (!count($histories)): ?>
	 <tr><td colspan="3">{$details_text['nohistory']}</td></tr>
	 <?php else: ?>
	 <?php foreach ($histories as $history): ?>
	 <tr>
		<td>{$fs->formatDate($history['event_date'], true)}</td>
		<td>{!tpl_userlink($history['user_id'])}</td>
		<td>{!event_description($history)}</td>
	 </tr>
	 <?php endforeach; ?>
	 <?php endif; ?>
  </table>

  <?php if ($details && isset($details_previous) && isset($details_new)): ?>
  <table class="history">
	 <tr>
		<th>{$details_text['previousvalue']}</th>
		<th>{$details_text['newvalue']}</th>
	 </tr>
	 <tr>
		<td>{!$details_previous}</td>
		<td>{!$details_new}</td>
	 </tr>
  </table>
  <?php endif; ?>
</div>
