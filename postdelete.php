<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request'], false, '404 Not Found');

// Fetch some info about the post, the topic and the forum
$result = $db->query('SELECT f.id AS fid, f.forum_name, f.redirect_url, f.post_reply, f.post_topic, t.id AS tid, t.subject, t.closed, p.posted, p.poster_id, p.message FROM '.$db->prefix.'board_posts AS p INNER JOIN '.$db->prefix.'board_topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'board_forums AS f ON f.id=t.forum_id WHERE p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))
	message($lang_common['Bad request'], false, '404 Not Found');

$cur_post = $db->fetch_assoc($result);

$result = $db->query('SELECT id FROM '.$db->prefix.'board_posts WHERE topic_id='.$cur_post['tid'].' ORDER BY id ASC LIMIT 1') or error('Unable to fetch topic posts count', __FILE__, __LINE__, $db->error());
$first_id = $db->result($result);
$is_topic_post = ($first_id == $id);

// Do we have permission to edit this post?
if (!$pun_user['is_admin'])
	message($lang_common['No permission'], false, '403 Forbidden');

if (isset($_POST['delete']))
{
	// Make sure they got here from the site
	confirm_referrer('postdelete.php');

	require PUN_ROOT.'include/search_idx.php';

	if ($is_topic_post)
	{
		// Delete the topic and all of its posts
		delete_topic($cur_post['tid']);

		redirect('forum.php?id='.$cur_post['fid'], $lang_common['Topic del redirect'], true);
	}
	else
	{
		// Delete just this one post
		delete_post($id, $cur_post['tid']);

		// Redirect towards the previous post
		$result = $db->query('SELECT id FROM '.$db->prefix.'board_posts WHERE topic_id='.$cur_post['tid'].' AND id < '.$id.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		$post_id = $db->result($result);
		redirect('topic.php?pid='.$post_id, $lang_common['Post del redirect'], true);
	}
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Delete post']);
define ('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

require PUN_ROOT.'include/parser.php';
$cur_post['message'] = parse_message($cur_post['message'], 0);

echo '<div id="brdheader">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_post['fid'].'">'.pun_htmlspecialchars($cur_post['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?pid='.$id.'">'.pun_htmlspecialchars($cur_post['subject']).'</a></td>'.
			'<td>'.$lang_common['Delete post'].'</td>';
echo	'</table>'.
	'</div>';

$user_result = $dba->query("SELECT username FROM ".$dba_prefix."account WHERE id=".$cur_post['poster_id']) or error($lang_common['DB Error'], __FILE__, __LINE__, $dba->error());
if (!$dba->num_rows($user_result))	message($lang_common['No user data'], false, '404 Not Found');
$cur_post['poster_username'] = $dba->result($user_result);

echo '<div id="deleteform">'.
			'<form method="post" action="postdelete.php?id='.$id.'">'.
				'<div class="inform">'.
					'<fieldset>'.
						'<div class="infldset">'.
							'<h2><span>'.$lang_common['Delete post'].'</span></h2>'.
						'</div>'.
						'<div class="infldset">'.
							(($is_topic_post) ? '<strong>'.$lang_common['Topic warning'].'</strong>' : '<strong>'.$lang_common['Warning'].'</strong>').'<br/>'.
							$lang_common['Delete info'].
						'</div>'.
						'<div class="infldset">'.
							'<input type="submit" class="bigbutton" name="delete" value="'.$lang_common['Confirm delete'].'" />'.
						'</div>'.
					'</fieldset>'.
				'</div>'.
			'</form>'.
		'</div>';

echo '<table id="topic">'.
		'<tr id="p'.$cur_post['id'].'" class="post">'.
			'<td class="postleft">'.
				$cur_post['poster_username'].'<br/>'.
				'#'.$cur_post['id'].' '.
				format_time($cur_post['posted'], false, "y/m/d", "h:s").
			'</td>'.
			'<td class="postright">'.$cur_post['message'].'</td>'.
		'</tr>'.
		'<tr class="postinter"></tr>'.
	'</table>';

echo '<div id="brdfooter">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_post['fid'].'">'.pun_htmlspecialchars($cur_post['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?pid='.$id.'">'.pun_htmlspecialchars($cur_post['subject']).'</a></td>'.
			'<td>'.$lang_common['Delete post'].'</td>';
echo	'</table>'.
	'</div>';

require PUN_ROOT.'footer.php';
