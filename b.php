<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')	message($lang_common['No view'], false, '403 Forbidden');

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'board');
require PUN_ROOT.'header.php';
$topright_links = '<input type="text" value="temp" /> <a href="'.PUN_URL.'search.php">'.$lang_common['Search'].'</a>';

echo '<div id="brdheader">'.
		'<div class="topright">'.$topright_links.'</div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
		'</table>'.
		'<div class="nobot"></div>'.
	'</div>';

echo '<div id="board">';

$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url
						FROM '.$db->prefix.'board_categories AS c INNER JOIN '.$db->prefix.'board_forums AS f
						ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

$cur_category = 0;	$cat_count = 0;	$forum_count = 0;
while ($cur_forum = $db->fetch_assoc($result))
{
	if ($cur_forum['cid'] != $cur_category) { // A new category since last iteration?
		if ($cur_category != 0)	echo "</div>";
		++$cat_count;
		$forum_count = 0;
		if ($cat_count > 1)	echo '<div style="height:18px"></div>';
		echo '<div id="idx'.$cat_count.'" class="blocktable">'.
				'<h2><span>'.$lang_common[pun_htmlspecialchars($cur_forum['cat_name'])].'</span></h2>';
		$cur_category = $cur_forum['cid']; }
	
	$forum_read = false;
	if (!$pun_user['is_guest']) {
		$topic_result = $db->query('SELECT COUNT(*) FROM '.$db->prefix.'board_topics WHERE forum_id='.$cur_forum['fid']) or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
		$topic_count = $db->result($topic_result);
		$tracked_result = $db->query('SELECT COUNT(*) FROM board_tracked_topic WHERE user_id = '.$pun_user['id'].' AND forum_id = '.$cur_forum['fid']) or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
		$tracked_topic = $db->result($tracked_result);
		$forum_read = $topic_count == $tracked_topic; }

	++$forum_count;
	
	$link = "";
	if ($cur_forum['redirect_url'] != '') {	// Is this a redirect forum?
		$link = pun_htmlspecialchars($cur_forum['redirect_url']);
		$forum_field = '<h3><span class="redirtext">'.$lang_index['Link to'].'</span> <a href="'.$link.'" title="'.$lang_index['Link to'].' '.pun_htmlspecialchars($cur_forum['redirect_url']).'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a></h3>';
		$num_topics = $num_posts = '-';
		$item_status .= ' iredirect';
		$icon_type = 'icon'; }
	else {
		$link = PUN_URL.'forum.php?id='.$cur_forum['fid'];
		$forum_field = '<a href="'.$link.'" class="forumlink">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a>';
		$num_topics = $cur_forum['num_topics'];
		$num_posts = $cur_forum['num_posts']; }

	if ($cur_forum['forum_desc'] != '')	$forum_field .= "\n\t\t\t\t\t\t\t\t".'<div class="forumdesc">'.$cur_forum['forum_desc'].'</div>';
	echo '<div class="forum'.($forum_read ? ' read' : '').'" onclick="window.location=\''.$link.'\'">'.$forum_field.'</div>';

}

if ($cur_category > 0)	echo "</div></div>"; // Is board empty?
else					echo '<div id="idx0" class="block"><div class="box"><div class="inbox"><p>'.$lang_common['Board empty'].'</p></div></div></div>';

echo '<div id="brdfooter">'.
		'<div class="nobot"></div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
		'</table>'.
	'</div>';

require PUN_ROOT.'footer.php';
