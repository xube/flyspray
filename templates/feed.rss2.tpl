{!'<?xml version="1.0" ?>'}
<rss version="2.0">
  <channel>
    <title>Flyspray</title>
    <lastBuildDate>{date('r',$most_recent)}</lastBuildDate>
    <description>{$feed_description}</description>
    <link>{$baseurl}</link>
    <?php if($feed_image): ?>
    <image>
      <url>{$feed_image}</url>
      <link>{$baseurl}</link>
      <title>[Logo]</title>
    </image>
    <?php endif;
    foreach($task_details as $row):?>
    <item>
      <title>{$row['item_summary']}</title>
      <pubDate>{date('r',intval($row['last_edited_time']))}</pubDate>
      <description>{strip_tags($row['detailed_desc'])}</description>
      <link>{CreateURL('details', $row['task_id'])}</link>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
