<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request'], false, '404 Not Found');

// Fetch some info about the forum
$result = $db->query('SELECT forum_name, redirect_url, post_topic FROM '.$db->prefix.'board_forums WHERE id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');

$cur_forum = $db->fetch_assoc($result);

if ($cur_forum['redirect_url'] != '') {	// Redirect link?
	header('Location: '.$cur_forum['redirect_url']);
	exit; }

$cur_forum['sort_by'] = 0;
switch ($cur_forum['sort_by']) {
	case 0:		$sort_by = 'last_posted DESC';	break;
	case 1:		$sort_by = 'posted DESC';		break;
	case 2:		$sort_by = 'subject ASC';		break;
	default:	$sort_by = 'last_posted DESC';	}

$num_pages = ceil($cur_forum['num_topics'] / $pun_user['disp_topics']); // Determine the topic offset (based on $_GET['p'])
$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_topics'] * ($p - 1);
$botleft_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'forum.php?id='.$id); // Generate paging links

$botright_links = '';
if (!$pun_user['is_guest'] && ($cur_forum['post_topic'] || $pun_user['is_mj']))	// Can we post new thread?
	$botright_links = '<a href="'.PUN_URL.'post.php?fid='.$id.'">'.$lang_common['Post topic'].'</a>';

$page_title = pun_htmlspecialchars($cur_forum['forum_name']);
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'forum');
require PUN_ROOT.'header.php';
$topright_links = '<input type="text" value="temp" /> <a href="'.PUN_URL.'search.php">'.$lang_common['Search'].'</a>';

echo '<div id="brdheader">'.
		'<div class="topright">'.$topright_links.'</div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$id.'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a></td>'.
		'</table>'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
	'</div>';

echo '<div id="forum">';

// Retrieve a list of topic IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
$result = $db->query('SELECT id FROM '.$db->prefix.'board_topics WHERE forum_id='.$id.' ORDER BY sticky DESC, '.$sort_by.', id DESC LIMIT '.$start_from.', '.$pun_user['disp_topics']) or error('Unable to fetch topic IDs', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result)) // If there are topics in this forum
{
	echo '<div id="forumheader">'.
			'<div style="display:inline-block; min-width:500px">'.$lang_common['Topics'].'</div>'.
			'<div style="display:inline-block;">'.$lang_common['Author'].'</div>'.
		'</div>';

	$topic_ids = array();
	for ($i = 0; $cur_topic_id = $db->result($result, $i); $i++)
		$topic_ids[] = $cur_topic_id;

	$sql = 'SELECT id, subject, posted, num_views, closed, sticky FROM '.$db->prefix.'board_topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC';
	$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());

	$topic_count = 0;
	while ($cur_topic = $db->fetch_assoc($result))
	{
		$topic_read = false;
		if (!$pun_user['is_guest']) {
			$tracked_result = $db->query('SELECT user_id FROM board_tracked_topic WHERE user_id = '.$pun_user['id'].' AND topic_id = '.$cur_topic['id'].' LIMIT 1') or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
			if ($db->result($tracked_result))	$topic_read = true; }

		$post_result = $db->query('SELECT poster_id FROM '.$db->prefix.'board_posts WHERE topic_id = \''.$cur_topic['id'].'\' ORDER BY id ASC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($post_result))	continue;
		$cur_author_id = $db->result($post_result); // get Author Id

		$author_result = $dba->query('SELECT username FROM '.$dba_prefix.'account WHERE id = '.$cur_author_id) or error($lang_common['DB Error'], __FILE__, __LINE__, $dba->error());
		if (!$dba->num_rows($author_result))	continue;
		$cur_author = $dba->result($author_result); // get Author Username

		++$topic_count;
		$status_text = array();
		$item_status = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';

		$link = PUN_URL.'topic.php?id='.$cur_topic['id'];

		if ($cur_topic['sticky'] == '1') {
			$item_status .= ' isticky';
			$status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>'; }

		$subject = '<a class="topiclink" href="'.$link.'"><b>'.pun_htmlspecialchars($cur_topic['subject']).'</b></a>';

		if ($cur_topic['closed']) {
			$item_status .= ' iclosed';
			$status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>'; }

		// Insert the status text before the subject
		$subject = implode(' ', $status_text).' '.$subject;

		$result2 = $db->query('SELECT COUNT(*) FROM '.$db->prefix.'board_posts WHERE topic_id='.$cur_topic['id']) or error('Unable to fetch topic posts count', __FILE__, __LINE__, $db->error());
		$posts_count = $db->result($result2);

		$num_pages_topic = ceil($posts_count / $pun_user['disp_posts']);

		if ($num_pages_topic > 1)	$subject_multipage = ' <span class="pagestext">[ '.paginate($num_pages_topic, -1, PUN_URL.'topic.php?id='.$cur_topic['id']).' ]</span>';
		else						$subject_multipage = null;
		
		echo '<div class="topic'.($topic_read ? ' read' : '').'" onclick="window.location=\''.$link.'\';">'.
				'<div style="display:inline-block; min-width:500px">'.
					$subject.$subject_multipage.
				'</div>'.
				'<div style="display:inline-block">'.
					'<a href="'.PUN_URL.'account.php?id='.$cur_author_id.'">'.$cur_author.'</a>'.
				'</div>'.
			'</div>';
	}
}
else {
	$colspan = ($pun_config['o_topic_views'] == '1') ? 4 : 3;
	echo '<div class="botleft">'.$lang_common['Forum empty'].'</div>'; }

echo '</div>';

echo '<div id="brdfooter">'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$id.'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a></td>'.
		'</table>'.
	'</div>';

require PUN_ROOT.'footer.php';
