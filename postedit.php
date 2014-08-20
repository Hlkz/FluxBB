<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)	message($lang_common['Bad request'], false, '404 Not Found');

$result = $db->query('SELECT f.id AS fid, f.forum_name, f.redirect_url, f.post_reply, f.post_topic, t.id AS tid, t.subject, t.posted, t.sticky, t.closed, p.poster_id, p.message FROM '.$db->prefix.'board_posts AS p INNER JOIN '.$db->prefix.'board_topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'board_forums AS f ON f.id=t.forum_id WHERE p.id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');
$cur_post = $db->fetch_assoc($result);

$result = $db->query('SELECT id FROM '.$db->prefix.'board_posts WHERE topic_id ='.$cur_post['tid'].' ORDER BY id ASC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');
$first_post_id = $db->result($result);

$can_edit_subject = $id == $first_post_id;

if ($pun_config['o_censoring'] == '1') {
	$cur_post['subject'] = censor_words($cur_post['subject']);
	$cur_post['message'] = censor_words($cur_post['message']); }

// Do we have permission to edit this post?
if (($pun_user['g_edit_posts'] == '0' ||
	$cur_post['poster_id'] != $pun_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$pun_user['is_admin'])
	message($lang_common['No permission'], false, '403 Forbidden');

// Start with a clean slate
$errors = array();

if (isset($_POST['form_sent']))
{
	// If it's a topic it must contain a subject
	if ($can_edit_subject)
	{
		$subject = pun_trim($_POST['req_subject']);

		if ($pun_config['o_censoring'] == '1')
			$censored_subject = pun_trim(censor_words($subject));

		if ($subject == '')
			$errors[] = $lang_common['No subject'];
		else if ($pun_config['o_censoring'] == '1' && $censored_subject == '')
			$errors[] = $lang_common['No subject after censoring'];
		else if (pun_strlen($subject) > 70)
			$errors[] = $lang_common['Too long subject'];
		else if ($pun_config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$pun_user['is_admin'])
			$errors[] = $lang_common['All caps subject'];
	}

	// Clean up message from POST
	$message = pun_linebreaks(pun_trim($_POST['req_message']));

	// Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
	if (strlen($message) > PUN_MAX_POSTSIZE)
		$errors[] = sprintf($lang_common['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
	else if ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admin'])
		$errors[] = $lang_common['All caps message'];

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1')
	{
		require PUN_ROOT.'include/parser.php';
		$message = preparse_bbcode($message, $errors);
	}

	if (empty($errors))
	{
		if ($message == '')
			$errors[] = $lang_common['No message'];
		else if ($pun_config['o_censoring'] == '1')
		{
			// Censor message to see if that causes problems
			$censored_message = pun_trim(censor_words($message));

			if ($censored_message == '')
				$errors[] = $lang_common['No message after censoring'];
		}
	}

	$stick_topic = isset($_POST['stick_topic']) ? '1' : '0';
	if (!$pun_user['is_admin'])
		$stick_topic = $cur_post['sticky'];

	// Replace four-byte characters (MySQL cannot handle them)
	$message = strip_bad_multibyte_chars($message);

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		require PUN_ROOT.'include/search_idx.php';

		if ($can_edit_subject)
		{
			// Update the topic and any redirect topics
			$db->query('UPDATE '.$db->prefix.'board_topics SET subject=\''.$db->escape($subject).'\', sticky='.$stick_topic.' WHERE id='.$cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

			// We changed the subject, so we need to take that into account when we update the search words
			update_search_index('edit', $id, $message, $subject);
		}
		else
			update_search_index('edit', $id, $message);

		// Update the post
		$db->query('UPDATE '.$db->prefix.'board_posts SET message=\''.$db->escape($message).'\' WHERE id='.$id) or error('Unable to update post', __FILE__, __LINE__, $db->error());

		redirect('topic.php?pid='.$id.'#p'.$id, $lang_common['Edit redirect']);
	}
}



$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_common['Edit post']);
$required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('edit', 'req_message');
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

$cur_index = 1;

echo '<div id="brdfooter">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_post['fid'].'">'.pun_htmlspecialchars($cur_post['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?pid='.$id.'">'.pun_htmlspecialchars($cur_post['subject']).'</a></td>'.
			'<td>'.$lang_common['Edit post'].'</td>';
echo	'</table>'.
	'</div>';

// If there are errors, we display them
if (!empty($errors))
{
	echo '<div id="posterror" class="block">'.
			'<h2><span>'.$lang_common['Post errors'].'</span></h2>'.
			'<div class="box">'.
				'<div class="inbox error-info">'.
					'<p>'.$lang_common['Post errors info'].'</p>'.
					'<ul class="error-list">';
	foreach ($errors as $cur_error)
		echo			'<li><strong>'.$cur_error.'</strong></li>';
	echo			'</ul>'.
				'</div>'.
			'</div>'.
		'</div>';
}
else if (isset($_POST['preview']))
{
	require_once PUN_ROOT.'include/parser.php';
	echo '<h2><span>'.$lang_board['Preview'].'</span></h2>'.
		'<table id="topic">'.
			'<tr id="p'.$cur_post['id'].'" class="post">'.
				'<td class="postleft">'.
					$pun_user['username'].
				'</td>'.
				'<td class="postright">'.parse_message($message, 0).'</td>'.
			'</tr>'.
			'<tr class="postinter"></tr>'.
		'</table>';
}

echo '<div id="editform" class="blockform">'.
		'<form id="edit" method="post" action="postedit.php?id='.$id.'&amp;action=edit" onsubmit="return process_form(this)">'.
			'<div class="inform">'.
				'<fieldset>'.
					'<div class="infldset">'.
						'<input type="hidden" name="form_sent" value="1" />'.
						'<h2><span>'.$lang_common['Edit post'].'</span></h2>'.
					'</div>'.
					'<div class="infldset">';
if ($can_edit_subject)
	echo				'<label class="required"><strong>'.$lang_common['Subject'].'<span>'.$lang_common['Required'].'</span></strong>'.
							'<input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="'.$cur_index++.'" value="'.(pun_htmlspecialchars(isset($_POST['req_subject']) ? $_POST['req_subject'] : $cur_post['subject'])).'" /><br/>'.
						'</label><br/>';
echo					'<label class="required"><strong>'.$lang_common['Message'].'<span>'.$lang_common['Required'].'</span></strong><br/>'.
							'<textarea id="req_message" name="req_message" cols="200" rows="14" tabindex="'.$cur_index++.'">'.(pun_htmlspecialchars(isset($_POST['req_message']) ? $message : $cur_post['message'])).'</textarea><br/>
						</label>';
// FluxToolBar
if (file_exists(FORUM_CACHE_DIR.'cache_fluxtoolbar_form.php'))
	include FORUM_CACHE_DIR.'cache_fluxtoolbar_form.php';
else {
	require_once PUN_ROOT.'include/cache_fluxtoolbar.php';
	generate_ftb_cache('form');
	require FORUM_CACHE_DIR.'cache_fluxtoolbar_form.php'; }

echo 			'<div class="infldset">'.
					'<ul class="bblinks">'.
						'<li><span><a href="help.php#bbcode" onclick="window.open(this.href); return false;">'.$lang_common['BBCode'].'</a> '.(($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']).'</span></li>'.
						'<li><span><a href="help.php#url" onclick="window.open(this.href); return false;">'.$lang_common['url tag'].'</a> '.(($pun_config['p_message_bbcode'] == '1' && $pun_user['g_post_links'] == '1') ? $lang_common['on'] : $lang_common['off']).'</span></li>'.
						'<li><span><a href="help.php#img" onclick="window.open(this.href); return false;">'.$lang_common['img tag'].'</a> '.(($pun_config['p_message_bbcode'] == '1' && $pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']).'</span></li>'.
						'<li><span><a href="help.php#smilies" onclick="window.open(this.href); return false;">'.$lang_common['Smilies'].'</a> '.(($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']).'</span></li>'.
					'</ul>'.
				'</div>'.
			'</fieldset>';
$checkboxes = array();
if ($can_edit_subject && $pun_user['is_admin'])
{
	if (isset($_POST['stick_topic']) || $cur_post['sticky'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
	else
		$checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'" />'.$lang_common['Stick topic'].'<br /></label>';
}
if ($pun_config['o_smilies'] == '1')
{
	if (isset($_POST['hide_smilies']) || $cur_post['hide_smilies'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="'.($cur_index++).'" />'.$lang_common['Hide smilies'].'<br /></label>';
	else
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'" />'.$lang_common['Hide smilies'].'<br /></label>';
}
if (!empty($checkboxes))
	echo 	'</div>'.
				'<div class="inform">'.
					'<fieldset>'.
						'<legend>'.$lang_common['Options'].'</legend>'.
						'<div class="infldset">'.
							'<div class="rbox">'.
								implode("\n\t\t\t\t\t\t\t", $checkboxes)."\n".
							'</div>'.
						'</div>'.
					'</fieldset>';
echo 			'</div>'.
				'<p class="buttons"><input type="submit" class="bigbutton" name="submit" value="'.$lang_common['Submit'].'" tabindex="'.$cur_index++.'" accesskey="s" />'.
				'<input type="submit" class="bigbutton" name="preview" value="'.$lang_common['Preview'].'" tabindex="'.$cur_index++.'" accesskey="p" /></p>'.
			'</form>'.
	'</div>';

echo '<div id="brdfooter">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_post['fid'].'">'.pun_htmlspecialchars($cur_post['forum_name']).'</a></td>'.
			'<td><a href="'.PUN_URL.'topic.php?pid='.$id.'">'.pun_htmlspecialchars($cur_post['subject']).'</a></td>'.
			'<td>'.$lang_common['Edit post'].'</td>';
echo	'</table>'.
	'</div>';

require PUN_ROOT.'footer.php';
