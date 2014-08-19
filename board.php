<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

// Get list of forums and topics with new posts since last visit
if (!$pun_user['is_guest'])
{
	$result = $db->query('SELECT t.forum_id, t.id FROM '.$db->prefix.'board_topics AS t INNER JOIN '.$db->prefix.'board_forums AS f ON f.id=t.forum_id') or error('Unable to fetch new topics', __FILE__, __LINE__, $db->error());

	$new_topics = array();
	while ($cur_topic = $db->fetch_assoc($result))
		$new_topics[$cur_topic['forum_id']][$cur_topic['id']];

	$tracked_topics = get_tracked_topics();
}

$forum_actions = array();

// Display a "mark all as read" link
if (!$pun_user['is_guest'])
	$forum_actions[] = '<a href="misc.php?action=markread">'.$lang_common['Mark all as read'].'</a>';

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

// Print the categories and forums
$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url
						FROM '.$db->prefix.'board_categories AS c INNER JOIN '.$db->prefix.'board_forums AS f
						ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

$cur_category = 0;
$cat_count = 0;
$forum_count = 0;
while ($cur_forum = $db->fetch_assoc($result))
{
	if ($cur_forum['cid'] != $cur_category) // A new category since last iteration?
	{
		if ($cur_category != 0)
			echo "</div>";

		++$cat_count;
		$forum_count = 0;

		if ($cat_count > 1)
			echo '<div style="height:18px"></div>';
		
		echo '<div id="idx'.$cat_count.'" class="blocktable">'.
				'<h2><span>'.$lang_common[pun_htmlspecialchars($cur_forum['cat_name'])].'</span></h2>';

		$cur_category = $cur_forum['cid'];
	}

	++$forum_count;
	$item_status = ($forum_count % 2 == 0) ? 'roweven' : 'rowodd';
	$forum_field_new = '';
	$icon_type = 'icon';

	$link = "";

	// Is this a redirect forum?
	if ($cur_forum['redirect_url'] != '') {
		$link = pun_htmlspecialchars($cur_forum['redirect_url']);
		$forum_field = '<h3><span class="redirtext">'.$lang_index['Link to'].'</span> <a href="'.$link.'" title="'.$lang_index['Link to'].' '.pun_htmlspecialchars($cur_forum['redirect_url']).'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a></h3>';
		$num_topics = $num_posts = '-';
		$item_status .= ' iredirect';
		$icon_type = 'icon'; }
	else {
		$link = PUN_URL.'forum.php?id='.$cur_forum['fid'];
		$forum_field = '<a href="'.$link.'" class="forumlink">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a>'.(!empty($forum_field_new) ? ' '.$forum_field_new : '');
		$num_topics = $cur_forum['num_topics'];
		$num_posts = $cur_forum['num_posts']; }

	if ($cur_forum['forum_desc'] != '')
		$forum_field .= "\n\t\t\t\t\t\t\t\t".'<div class="forumdesc">'.$cur_forum['forum_desc'].'</div>';

	echo '<div class="forum" onclick="window.location=\''.$link.'\'">'.$forum_field.'</div>';

}

// Did we output any categories and forums?
if ($cur_category > 0)
	echo "</div></div>";
else
	echo '<div id="idx0" class="block"><div class="box"><div class="inbox"><p>'.$lang_index['Empty board'].'</p></div></div></div>';

echo '<div id="brdfooter">'.
		'<div class="nobot"></div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
		'</table>'.
	'</div>';

$footer_style = 'board';
require PUN_ROOT.'footer.php';
