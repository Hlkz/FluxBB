<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')	message($lang_common['No view'], false, '403 Forbidden');

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)	message($lang_common['Bad request'], false, '404 Not Found');

// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	$result = $db->query('SELECT topic_id, posted FROM '.$db->prefix.'board_posts WHERE id='.$pid) or error('Unable to fetch topic ID', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');

	list($id, $posted) = $db->fetch_row($result);

	// Determine on which page the post is located (depending on $forum_user['disp_posts'])
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'board_posts WHERE topic_id='.$id.' AND posted<'.$posted) or error('Unable to count previous posts', __FILE__, __LINE__, $db->error());
	$num_posts = $db->result($result) + 1;

	$_GET['p'] = ceil($num_posts / $pun_user['disp_posts']);
}
else if ($action == 'last') { // If action=last, we redirect to the last post
	$result = $db->query('SELECT MAX(id) FROM '.$db->prefix.'board_posts WHERE topic_id='.$id) or error('Unable to fetch last post info', __FILE__, __LINE__, $db->error());
	$last_post_id = $db->result($result);

	if ($last_post_id) {
		redirect(PUN_URL.'topic.php?pid='.$last_post_id.'#p'.$last_post_id, '', true);
		exit; }
}

$result = $db->query('SELECT t.subject, t.closed, t.sticky, f.id AS forum_id, f.forum_name, f.post_reply
						FROM '.$db->prefix.'board_topics AS t
						INNER JOIN '.$db->prefix.'board_forums AS f ON f.id=t.forum_id
						WHERE t.id='.$id) or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');
$cur_topic = $db->fetch_assoc($result);

$botright_links = array();
if (!$pun_user['is_guest'])
{
	if (!$cur_topic['post_reply'])	$botright_links[] = $lang_common['Forum closed'];
	else if ($cur_topic['closed'])	$botright_links[] = $lang_common['Topic closed'];
	if ($pun_user['is_mj'] || (!$cur_topic['closed'] && $cur_topic['post_reply']))
		$botright_links[] = '<a href="'.PUN_URL.'post.php?tid='.$id.'">'.$lang_common['Post reply'].'</a>';
}
if ($botright_links)	$botright_links = implode($botright_links, ' ');
else					$botright_links = '';

$result = $db->query('SELECT COUNT(*) FROM '.$db->prefix.'board_posts WHERE topic_id='.$id) or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
$cur_topic['num_replies'] = $db->result($result);
if (!$db->num_rows($result)) exit; // tofix db error

$num_pages = ceil($cur_topic['num_replies'] / $pun_user['disp_posts']);
$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);

$start_from = $pun_user['disp_posts'] * ($p - 1);

$botleft_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, PUN_URL.'topic.php?id='.$id);

if ($pun_config['o_censoring'] == '1')	$cur_topic['subject'] = censor_words($cur_topic['subject']);

$page_title = pun_htmlspecialchars($cur_topic['subject']);
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'topic');
require PUN_ROOT.'header.php';

echo '<div id="brdheader">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_topic['forum_id'].'">'.pun_htmlspecialchars($cur_topic['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?id='.$id.'?>">'.pun_htmlspecialchars($cur_topic['subject']).'</a></td>'.
		'</table>'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
	'</div>';

require PUN_ROOT.'include/parser.php';

// Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
$result = $db->query('SELECT id FROM '.$db->prefix.'board_posts WHERE topic_id='.$id.' ORDER BY id LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

$post_ids = array();
for ($i = 0; $cur_post_id = $db->result($result, $i); $i++)	$post_ids[] = $cur_post_id;
if (empty($post_ids))	error('The post table and topic table seem to be out of sync!', __FILE__, __LINE__);

$author_name = '';	$posts = array();	$post_count = 0;	$topic_actions = array();

// Retrieve the posts (and their respective poster/online status)
$result = $db->query('SELECT p.id, p.poster_id, p.poster_ip, p.message, p.posted FROM '.$db->prefix.'board_posts AS p WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
while ($cur_post = $db->fetch_assoc($result))
{
	$post_actions = array();
	if (!$pun_user['is_guest'])
	{
		if ($pun_user['is_mj'] || (!$cur_topic['closed'] && $cur_topic['post_reply']))
			$post_actions[] = '<span class="postquote"><a href="'.PUN_URL.'post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_common['Quote'].'</a></span>';
		if ($pun_user['is_mj'] || (!$cur_topic['closed'] && $cur_topic['post_reply'] && $pun_user['id'] == $cur_post['poster_id']))
		{
			$post_actions[] = '<span class="postedit"><a href="'.PUN_URL.'postedit.php?id='.$cur_post['id'].'">'.$lang_common['Edit'].'</a></span>';
			if (!$post_count)	$topic_actions[] = '<span class="postedit"><a href="'.PUN_URL.'postedit.php?id='.$cur_post['id'].'">'.$lang_common['Edit'].'</a></span>';
		}
		if ($pun_user['is_mj'])
		{
			$post_actions[] = '<span class="postdelete"><a href="'.PUN_URL.'postdelete.php?id='.$cur_post['id'].'">'.$lang_common['Delete'].'</a></span>';
			if (!$post_count)	$topic_actions[] = '<span class="postdelete"><a href="'.PUN_URL.'postdelete.php?id='.$cur_post['id'].'">'.$lang_common['Delete'].'</a></span>';
		}
	}

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], 0);

	$user_result = $dba->query("SELECT account, username FROM ".$dba_prefix."account WHERE id=".$cur_post['poster_id']) or error($lang_common['DB Error'], __FILE__, __LINE__, $dba->error());
	if (!$dba->num_rows($user_result))	message($lang_common['No user data'], false, '404 Not Found');
	$cur_user = $dba->fetch_assoc($user_result);
	$cur_username = $cur_user['username'];
	if (!$post_count)	$author_name = $cur_username;

	$posts[] = 	'<tr id="p'.$cur_post['id'].'" class="post">'.
					'<td class="postleft">'.
						'<a href="'.PUN_URL.'account.php?id='.$cur_post['poster_id'].'">'.$cur_username.'</a><br/>'.
						'<a href="'.PUN_URL.'topic.php?pid='.$cur_post['id'].'#p'.$cur_post['id'].'">#'.$cur_post['id'].'</a> '.
						format_time($cur_post['posted'], false, "y/m/d", "h:s").'<br/>'.
						implode($post_actions, ' ').
					'</td>'.
					'<td class="postright">'.$cur_post['message'].'</td>'.
				'</tr>';
	$post_count++;
}
	
echo 	'<div id="topicname">'.
			'<div class="conl"><h2>'.pun_htmlspecialchars($cur_topic['subject']).'</h2></div>'.
			'<div class="conl topicactions">'.implode($topic_actions, ' ').'</div>'.
			'<div class="conr"><h2>'.$lang_common['Author:'].' '.$author_name.'</h2></div>'.
		'</div>'.
		'<table id="topic">'.
			implode($posts, '<tr class="postinter"></tr>').
		'</table>';

if (!$pun_user['is_guest'] && ($pun_user['is_mj'] || (!$cur_topic['closed'] && $cur_topic['post_reply']))) // Display quick post
{
	$cur_index = 1;

	echo '<div id="quickpost" class="blockform">'.
			'<form id="quickpostform" method="post" action="'.PUN_URL.'post.php?tid='.$id.'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">'.
				'<div class="inform">'.
					'<fieldset>'.
						'<div class="infldset">'.
							'<input type="hidden" name="form_sent" value="1" />'.
							'<label class="required"><strong>'.$lang_common['Message'].' <span>'.$lang_common['Required'].'</span></strong><br/><br/>'.
								'<textarea id="req_message" name="req_message" cols="95" rows="7" tabindex="'.$cur_index++.'"></textarea>'.
							'</label>'.
						'</div>'.
					'</fieldset>';
	// FluxToolBar
	if (file_exists(FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php'))
		include FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php';
	else {
		require_once PUN_ROOT.'include/cache_fluxtoolbar.php';
		generate_ftb_cache('quickform');
		require FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php'; }

	echo 			'<p class="buttons"><input type="submit" name="submit" class="bigbutton" tabindex="'.$cur_index++.'" value="'.$lang_common['Post message'].'" accesskey="s" />'.
					'<input type="submit" name="preview" class="bigbutton" value="'.$lang_common['Preview'].'" tabindex="'.$cur_index++.'" accesskey="p" /></p>'.
				'</div>'.
			'</form>'.
		'</div>';
}

echo '<div id="brdfooter">'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_topic['forum_id'].'">'.pun_htmlspecialchars($cur_topic['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?id='.$id.'?>">'.pun_htmlspecialchars($cur_topic['subject']).'</a></td>'.
		'</table>'.
	'</div>';

if ($pun_config['o_topic_views'] == '1') // Increment "num_views" for topic
	$db->query('UPDATE '.$db->prefix.'board_topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

if (!$pun_user['is_guest'])	{ // Add tracked topic
	$db->query('DELETE FROM '.$db->prefix.'board_tracked_topic WHERE user_id = '.$pun_user['id'].' AND topic_id = '.$id) or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix.'board_tracked_topic (user_id, forum_id, topic_id) VALUES (\''.$pun_user['id'].'\', \''.$cur_topic['forum_id'].'\', \''.$id.'\')') or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
}

$forum_id = $id;
$footer_style = 'board';
require PUN_ROOT.'footer.php';
