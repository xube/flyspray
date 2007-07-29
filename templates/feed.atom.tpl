{!'<?xml version="1.0" ?>'}
<feed xmlns="http://www.w3.org/2005/Atom">
  <title type="text">{$fs->prefs['page_title']}</title>
  <subtitle type="text">
    {$feed_description}
  </subtitle>
  <id>{$this->relativeUrl($baseurl)}</id>
  <?php if($feed_image): ?>
  <icon>{$feed_image}</icon>
  <?php endif; ?>
  <updated>{date('Y-m-d\TH:i:s\Z',$most_recent)}</updated>
  <link rel="self" type="text/xml" href="feed.php?feed_type=atom"/>
  <link rel="alternate" type="text/html" hreflang="en" href="{$_SERVER['SCRIPT_NAME']}"/>
  <?php foreach ($task_details as $row): ?>
  <entry>
    <title>{$row['project_prefix']}#{$row['prefix_id']}: {$row['item_summary']}</title>
    <link href="{$this->url(array('details', 'task' . $row['task_id']))}" />
    <updated>{date('Y-m-d\TH:i:s\Z',intval($row['last_edited_time']))}</updated>    
    <published>{date('Y-m-d\TH:i:s\Z',intval($row['date_opened']))}</published>
    <content type="xhtml" xml:lang="en" xml:base="http://diveintomark.org/">
      <div xmlns="http://www.w3.org/1999/xhtml">
        {!$this->text->render($row['detailed_desc'])}
      </div>
    </content>
    <author><name>{$row['real_name']}</name></author>
    <id>{$this->relativeUrl($baseurl)}:{$row['task_id']}</id>
  </entry>
  <?php   endforeach; ?>
</feed>
