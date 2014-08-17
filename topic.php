<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
	message($lang_common['Bad request'], false, '404 Not Found');

require PUN_ROOT.'include/lang/'.$pun_user['language'].'/topic.php';


// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	$result = $db->query('SELECT topic_id, posted FROM '.$db->prefix.'board_posts WHERE id='.$pid) or error('Unable to fetch topic ID', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request'], false, '404 Not Found');

	list($id, $posted) = $db->fetch_row($result);

	// Determine on which page the post is located (depending on $forum_user['disp_posts'])
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'board_posts WHERE topic_id='.$id.' AND posted<'.$posted) or error('Unable to count previous posts', __FILE__, __LINE__, $db->error());
	$num_posts = $db->result($result) + 1;

	$_GET['p'] = ceil($num_posts / $pun_user['disp_posts']);
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new')
{
	if (!$pun_user['is_guest'])
	{
		// We need to check if this topic has been viewed recently by the user
		$tracked_topics = get_tracked_topics();
		$last_viewed = isset($tracked_topics['topics'][$id]) ? $tracked_topics['topics'][$id] : $pun_user['last_visit'];

		$result = $db->query('SELECT MIN(id) FROM '.$db->prefix.'board_posts WHERE topic_id='.$id.' AND posted>'.$last_viewed) or error('Unable to fetch first new post info', __FILE__, __LINE__, $db->error());
		$first_new_post_id = $db->result($result);

		if ($first_new_post_id)
		{
			header('Location: topic.php?pid='.$first_new_post_id.'#p'.$first_new_post_id);
			exit;
		}
	}

	// If there is no new post, we go to the last post
	redirect('topic.php?id='.$id.'&action=last', '', true);
	exit;
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

$botright_links = '';
if (!$cur_topic['closed'] && $cur_topic['post_reply'])
	$botright_links = '<a href="'.PUN_URL.'post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a>';
else {
	$botright_links = $lang_topic['Topic closed'];
	if ($pun_user['is_admin'])	$botright_links .= ' <a href="'.PUN_URL.'post.php?tid='.$id.'">'.$lang_topic['Post reply'].'</a>'; }

// Add/update this topic in our list of tracked topics
if (!$pun_user['is_guest'])
{
	$tracked_topics = get_tracked_topics();
	$tracked_topics['topics'][$id] = time();
	set_tracked_topics($tracked_topics);
}

$result = $db->query('SELECT COUNT(*) FROM '.$db->prefix.'board_posts WHERE topic_id='.$id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
$cur_topic['num_replies'] = $db->result($result);
if (!$db->num_rows($result)) exit; // tofix db error

$num_pages = ceil(($cur_topic['num_replies'] + 1) / 10);
$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);

$start_from = $pun_user['disp_posts'] * ($p - 1);

$botleft_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, PUN_URL.'topic.php?id='.$id);

if ($pun_config['o_censoring'] == '1')
	$cur_topic['subject'] = censor_words($cur_topic['subject']);


$quickpost = false;
if ((!$cur_topic['closed'] && $cur_topic['post_reply']) || $pun_user['is_admin'])
{
	// Load the post.php language file
	require PUN_ROOT.'include/lang/'.$pun_user['language'].'/post.php';

	$required_fields = array('req_message' => $lang_common['Message']);
	if ($pun_user['is_guest'])
	{
		$required_fields['req_username'] = $lang_post['Guest name'];
		if ($pun_config['p_force_guest_email'] == '1')
			$required_fields['req_email'] = $lang_common['Email'];
	}
	$quickpost = true;
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'topic');
require PUN_ROOT.'header.php';

echo '<div id="brdfooter">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_topic['forum_id'].'">'.pun_htmlspecialchars($cur_topic['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?id='.$id.'?>">'.pun_htmlspecialchars($cur_topic['subject']).'</a></td>'.
		'</table>'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
	'</div>';

echo '<table id="topic">';

require PUN_ROOT.'include/parser.php';

// Retrieve a list of post IDs, LIMIT is (really) expensive so we only fetch the IDs here then later fetch the remaining data
$result = $db->query('SELECT id FROM '.$db->prefix.'board_posts WHERE topic_id='.$id.' ORDER BY id LIMIT '.$start_from.','.$pun_user['disp_posts']) or error('Unable to fetch post IDs', __FILE__, __LINE__, $db->error());

$post_ids = array();
for ($i = 0;$cur_post_id = $db->result($result, $i);$i++)
	$post_ids[] = $cur_post_id;

if (empty($post_ids))
	error('The post table and topic table seem to be out of sync!', __FILE__, __LINE__);

// Retrieve the posts (and their respective poster/online status)
$result = $db->query('SELECT p.id, p.poster_id, p.poster_ip, p.message, p.posted FROM '.$db->prefix.'board_posts AS p WHERE p.id IN ('.implode(',', $post_ids).') ORDER BY p.id', true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
while ($cur_post = $db->fetch_assoc($result))
{
	$user_info = array();
	$post_actions = array();

	if ($pun_user['is_admin'])
		$user_info[] = '<dd><span><a href="moderate.php?get_host='.$cur_post['id'].'" title="'.pun_htmlspecialchars($cur_post['poster_ip']).'">'.$lang_topic['IP address logged'].'</a></span></dd>';

	// Generation post action array (quote, edit, delete etc.)
	if (!$pun_user['is_admin'])
	{
		if (!$cur_topic['closed'] && $cur_topic['post_reply'])
				$post_actions[] = '<span><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a> </span>';
	}
	else
	{
		$post_actions[] = '<span class="postdelete"><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a> </span>';
		$post_actions[] = '<span class="postedit"><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a> </span>';
		$post_actions[] = '<span class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a> </span>';
	}
	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], 0);

	$user_result = $dba->query("SELECT account, username FROM ".$dba_prefix."account WHERE id=".$cur_post['poster_id']) or error($lang_common['DB Error'], __FILE__, __LINE__, $dba->error());
	if (!$dba->num_rows($user_result))	message($lang_common['No user data'], false, '404 Not Found');
	$cur_user = $db->fetch_assoc($user_result);
	$cur_username = ($cur_user['username'] ? $cur_user['username'] : $cur_user['account']);

	echo '<tr id="p'.$cur_post['id'].'" class="post">'.
			'<td class="postleft">'.
				'<a href="topic.php?pid='.$cur_post['id'].'#p'.$cur_post['id'].'#'.$cur_post['id'].'</a> '.implode($post_actions).
				format_time($cur_post['posted'], false, "y/m/d", "h:s").' '.$cur_username.
			'</td>'.
			'<td class="postright">'.$cur_post['message'].'</td>'.
		'</tr>'.
		'<tr class="postinter"></tr>';
}

echo '</table>';

// Display quick post
$cur_index = 1;

?>
<div id="quickpost" class="blockform">
		<form id="quickpostform" method="post" action="post.php?tid=<?php echo $id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
						<input type="hidden" name="form_sent" value="1" />
						<div class="clearer"></div>
<?php

	echo "\t\t\t\t\t\t".'<label class="required"><strong>'.$lang_common['Message'].' <span>'.$lang_common['Required'].'</span></strong><br />';

?>
<textarea id="req_message" name="req_message" rows="7" cols="75" tabindex="<?php echo $cur_index++ ?>"></textarea></label>
<?php /* FluxToolBar */
if (file_exists(FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php'))
	include FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php';
else
{
	require_once PUN_ROOT.'include/cache_fluxtoolbar.php';
	generate_ftb_cache('quickform');
	require FORUM_CACHE_DIR.'cache_fluxtoolbar_quickform.php';
}
?>
			<p class="buttons"><input type="submit" name="submit" class="bigbutton" tabindex="<?php echo $cur_index++ ?>" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /> <input type="submit" name="preview" class="bigbutton" value="<?php echo $lang_topic['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /></p>
		</form>
</div>

<?php
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

$forum_id = $id;
$footer_style = 'board';
require PUN_ROOT.'footer.php';
