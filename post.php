<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($tid < 1 && $fid < 1 || $tid > 0 && $fid > 0)
	message($lang_common['Bad request'], false, '404 Not Found');

// Fetch some info about the topic and/or the forum
if ($tid)	$result = $db->query('SELECT f.id, f.forum_name, f.redirect_url, f.post_reply, f.post_topic, t.subject, t.closed
								FROM '.$db->prefix.'board_topics AS t INNER JOIN '.$db->prefix.'board_forums AS f ON f.id=t.forum_id
								WHERE t.id='.$tid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
else	$result = $db->query('SELECT f.id, f.forum_name, f.redirect_url, f.post_reply, f.post_topic
								FROM '.$db->prefix.'board_forums AS f
								WHERE f.id='.$fid) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
								
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');

$cur_posting = $db->fetch_assoc($result);
$is_subscribed = $tid && $cur_posting['is_subscribed'];

// Is someone trying to post into a redirect forum?
if ($cur_posting['redirect_url'] != '')
	message($lang_common['Bad request'], false, '404 Not Found');

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

if ($tid && $pun_config['o_censoring'] == '1')
	$cur_posting['subject'] = censor_words($cur_posting['subject']);

// Do we have permission to post?
if ((($tid && (($cur_posting['post_replies'] == '' && $pun_user['g_post_replies'] == '0') || $cur_posting['post_replies'] == '0')) ||
	($fid && (($cur_posting['post_topics'] == '' && $pun_user['g_post_topics'] == '0') || $cur_posting['post_topics'] == '0')) ||
	(isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
	!$is_admmod)
	message($lang_common['No permission'], false, '403 Forbidden');

// Start with a clean slate
$errors = array();


// Did someone just hit "Submit" or "Preview"?
if (isset($_POST['form_sent']))
{
	// Flood protection
	if (!isset($_POST['preview']) && $pun_user['last_post'] != '' && (time() - $pun_user['last_post']) < $pun_user['g_post_flood'])
		$errors[] = sprintf($lang_common['Flood start'], $pun_user['g_post_flood'], $pun_user['g_post_flood'] - (time() - $pun_user['last_post']));

	// If it's a new topic
	if ($fid)
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
		else if ($pun_config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$pun_user['is_admmod'])
			$errors[] = $lang_common['All caps subject'];
	}

	// If the user is logged in we get the username and email from $pun_user
	if (!$pun_user['is_guest'])
	{
		$username = $pun_user['username'];
		$email = $pun_user['email'];
	}
	// Otherwise it should be in $_POST
	else
	{
		$username = pun_trim($_POST['req_username']);
		$email = strtolower(pun_trim(($pun_config['p_force_guest_email'] == '1') ? $_POST['req_email'] : $_POST['email']));
		$banned_email = false;

		// Load the register.php/prof_reg.php language files
		require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';
		require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

		// It's a guest, so we have to validate the username
		check_username($username);

		if ($pun_config['p_force_guest_email'] == '1' || $email != '')
		{
			require PUN_ROOT.'include/email.php';
			if (!is_valid_email($email))
				$errors[] = $lang_common['Invalid email'];

			// Check if it's a banned email address
			// we should only check guests because members' addresses are already verified
			if ($pun_user['is_guest'] && is_banned_email($email))
			{
				if ($pun_config['p_allow_banned_email'] == '0')
					$errors[] = $lang_prof_reg['Banned email'];

				$banned_email = true; // Used later when we send an alert email
			}
		}
	}

	// Clean up message from POST
	$orig_message = $message = pun_linebreaks(pun_trim($_POST['req_message']));

	// Here we use strlen() not pun_strlen() as we want to limit the post to PUN_MAX_POSTSIZE bytes, not characters
	if (strlen($message) > PUN_MAX_POSTSIZE)
		$errors[] = sprintf($lang_common['Too long message'], forum_number_format(PUN_MAX_POSTSIZE));
	else if ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admmod'])
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

	$hide_smilies = isset($_POST['hide_smilies']) ? '1' : '0';
	$subscribe = isset($_POST['subscribe']) ? '1' : '0';
	$stick_topic = isset($_POST['stick_topic']) && $is_admmod ? '1' : '0';

	// Replace four-byte characters (MySQL cannot handle them)
	$message = strip_bad_multibyte_chars($message);

	$now = time();

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		require PUN_ROOT.'include/search_idx.php';

		// If it's a reply
		if ($tid)
		{
			$new_tid = $tid;

			// Insert the new post
			$db->query('INSERT INTO '.$db->prefix.'board_posts (poster_id, poster_ip, message, posted, topic_id)
							VALUES(\''.$pun_user['id'].'\', \''.$db->escape(get_remote_address()).'\', \''.$db->escape($message).'\', '.$now.', '.$tid.')')
							or error('Unable to create post', __FILE__, __LINE__, $db->error());
			$new_pid = $db->insert_id();

			update_search_index('post', $new_pid, $message);
		}
		// If it's a new topic
		else if ($fid)
		{
			// Create the topic
			$db->query('INSERT INTO '.$db->prefix.'board_topics (subject, posted, last_posted, sticky, closed, forum_id) VALUES(\''.$db->escape($subject).'\', '.$now.', '.$now.', '.$stick_topic.', 0, '.$fid.')') or error('Unable to create topic', __FILE__, __LINE__, $db->error());
			$new_tid = $db->insert_id();

			$db->query('INSERT INTO '.$db->prefix.'board_posts (poster_id, poster_ip, message, posted, topic_id)
							VALUES(\''.$pun_user['id'].'\', \''.$db->escape(get_remote_address()).'\', \''.$db->escape($message).'\', '.$now.', '.$new_tid.')')
							or error('Unable to create post', __FILE__, __LINE__, $db->error());
			$new_pid = $db->insert_id();

			update_search_index('post', $new_pid, $message, $subject);
		}

		redirect('topic.php?pid='.$new_pid.'#p'.$new_pid, $lang_common['Post redirect'], true);
	}
}


// If a topic ID was specified in the url (it's a reply)
if ($tid)
{
	$action = $lang_common['Post reply'];
	$form = '<form id="post" method="post" action="post.php?action=post&amp;tid='.$tid.'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';

	// If a quote ID was specified in the url
	if (isset($_GET['qid']))
	{
		$qid = intval($_GET['qid']);
		if ($qid < 1)	message($lang_common['Bad request'], false, '404 Not Found');

		$result = $db->query('SELECT poster_id, message FROM '.$db->prefix.'board_posts WHERE id='.$qid.' AND topic_id='.$tid) or error('Unable to fetch quote info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');
		list($q_poster_id, $q_message) = $db->fetch_row($result);

		$user_result = $dba->query("SELECT account, username FROM ".$dba_prefix."account WHERE id=".$q_poster_id) or error($lang_common['DB Error'], __FILE__, __LINE__, $dba->error());
		if (!$dba->num_rows($user_result))	message($lang_common['No user data'], false, '404 Not Found');
		$cur_user = $db->fetch_assoc($user_result);
		$q_poster_pseudo = $cur_user['username'];

		// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
		if (strpos($q_message, '[code]') !== false && strpos($q_message, '[/code]') !== false)
		{
			list($inside, $outside) = split_text($q_message, '[code]', '[/code]');

			$q_message = implode("\1", $outside);
		}

		// Remove [img] tags from quoted message
		$q_message = preg_replace('%\[img(?:=(?:[^\[]*?))?\]((ht|f)tps?://)([^\s<"]*?)\[/img\]%U', '\1\3', $q_message);

		// If we split up the message before we have to concatenate it together again (code tags)
		if (isset($inside))
		{
			$outside = explode("\1", $q_message);
			$q_message = '';

			$num_tokens = count($outside);
			for ($i = 0; $i < $num_tokens; ++$i)
			{
				$q_message .= $outside[$i];
				if (isset($inside[$i]))
					$q_message .= '[code]'.$inside[$i].'[/code]';
			}

			unset($inside);
		}

		if ($pun_config['o_censoring'] == '1')	$q_message = censor_words($q_message);

		$q_message = pun_htmlspecialchars($q_message);

		if ($pun_config['p_message_bbcode'] == '1')
		{
			// If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
			if (strpos($q_poster_pseudo, '[') !== false || strpos($q_poster_pseudo, ']') !== false)
			{
				if (strpos($q_poster_pseudo, '\'') !== false)
					$q_poster_pseudo = '"'.$q_poster_pseudo.'"';
				else
					$q_poster_pseudo = '\''.$q_poster_pseudo.'\'';
			}
			else
			{
				// Get the characters at the start and end of $q_poster_pseudo
				$ends = substr($q_poster_pseudo, 0, 1).substr($q_poster_pseudo, -1, 1);

				// Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
				if ($ends == '\'\'')
					$q_poster_pseudo = '"'.$q_poster_pseudo.'"';
				else if ($ends == '""')
					$q_poster_pseudo = '\''.$q_poster_pseudo.'\'';
			}

			$quote = '[quote='.$q_poster_pseudo.']'.$q_message.'[/quote]'."\n";
		}
		else
			$quote = '> '.$q_poster_pseudo.' '.$lang_common['wrote']."\n\n".'> '.$q_message."\n";
	}
}
// If a forum ID was specified in the url (new topic)
else if ($fid)
{
	$action = $lang_common['Post topic'];
	$form = '<form id="post" method="post" action="post.php?action=post&amp;fid='.$fid.'" onsubmit="return process_form(this)">';
}
else
	message($lang_common['Bad request'], false, '404 Not Found');


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $action);
$required_fields = array('req_email' => $lang_common['Email'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('post');

if (!$pun_user['is_guest'])
	$focus_element[] = ($fid) ? 'req_subject' : 'req_message';
else
{
	$required_fields['req_username'] = $lang_common['Guest name'];
	$focus_element[] = 'req_username';
}

define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

echo '<div id="brdheader">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_posting['id'].'">'.pun_htmlspecialchars($cur_posting['forum_name']).'</a></td>';
			if (isset($_POST['req_subject']))	echo '<td>'.pun_htmlspecialchars($_POST['req_subject']).'</td>';
			if (isset($cur_posting['subject']))	echo '<td><a href="'.PUN_URL.'topic.php?id='.$tid.'">'.pun_htmlspecialchars($cur_posting['subject']).'</a></td>';
echo	'</table>'.
	'</div>';

if (!empty($errors)) // Display errors if any
{
	echo '<div id="posterror" class="block">'.
			'<h2><span>'.$lang_common['Post errors'].'</span></h2>'.
			'<div class="box">'.
				'<div class="inbox error-info">'.
					'<p>'.$lang_common['Post errors info'].'</p>'.
					'<ul class="error-list">';
	foreach ($errors as $cur_error)	echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
	echo 			'</ul>'.
				'</div>'.
			'</div>'.
		'</div>';
}
else if (isset($_POST['preview'])) // Preview post
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

$cur_index = 1;

echo '<div id="postform" class="blockform">'.
		$form."\n".
		'<div class="inform">'.
			'<fieldset>'.
				'<div class="infldset">'.
					'<input type="hidden" name="form_sent" value="1" />'.
					'<h2><span>'.$action.'</span></h2>'.
				'</div>'.
				'<div class="infldset">';
if ($fid)	echo 	'<label class="required"><strong>'.$lang_common['Subject'].'<span>'.$lang_common['Required'].'</span></strong><br/><br/>'.
						'<input class="longinput" type="text" name="req_subject" value="'.((isset($_POST['req_subject'])) ? pun_htmlspecialchars($subject) : '').'" size="80" maxlength="70" tabindex="'.$cur_index++.'" />'.
					'</label><br/>';
echo 				'<label class="required"><strong>'.$lang_common['Message'].'<span>'.$lang_common['Required'].'</span></strong><br/><br/>'.
						'<textarea id="req_message" name="req_message" cols="95" rows="20" tabindex="'.$cur_index++.'">'.(isset($_POST['req_message']) ? pun_htmlspecialchars($orig_message) : (isset($quote) ? $quote : '')).'</textarea>'.
					'</label>'.
				'</div>';
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
if ($fid && $is_admin)
	$checkboxes[] = '<label><input type="checkbox" name="stick_topic" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['stick_topic']) ? ' checked="checked"' : '').' />'.$lang_common['Stick topic'].'<br /></label>';

if (!empty($checkboxes))
{
	echo '</div>'.
			'<div class="inform">'.
				'<fieldset>'.
					'<legend>'.$lang_common['Options'].'</legend>'.
					'<div class="infldset">'.
						'<div class="rbox">'.
							implode("\n\t\t\t\t\t\t\t", $checkboxes)."\n".
						'</div>'.
					'</div>'.
				'</fieldset>';
}
echo	'</div>'.
			'<p class="buttons"><input type="submit" class="bigbutton" name="submit" value="'.$lang_common['Post message'].'" tabindex="'.$cur_index++.'" accesskey="s" />'.
			'<input type="submit" class="bigbutton" name="preview" value="'.$lang_common['Preview'].'" tabindex="<?php echo $cur_index++ ?>" accesskey="p" /></p>'.
		'</form>'.
	'</div>'.
'</div>';

if ($tid && $pun_config['o_topic_review'] != '0') // Check to see if the topic review is to be displayed
{
	require_once PUN_ROOT.'include/parser.php';

	echo '<table id="topic">'.
			'<h2><span>'.$lang_common['Topic review'].'</span></h2>';

	$post_count = 0;
	$result = $db->query('SELECT id, poster_id, message, posted FROM '.$db->prefix.'board_posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT '.$pun_config['o_topic_review']) or error('Unable to fetch topic review', __FILE__, __LINE__, $db->error());
	while ($cur_post = $db->fetch_assoc($result))
	{
		$post_count++;

		$cur_post['message'] = parse_message($cur_post['message'], 0);

		$user_result = $dba->query("SELECT username FROM ".$dba_prefix."account WHERE id=".$cur_post['poster_id']) or error($lang_common['DB Error'], __FILE__, __LINE__, $dba->error());
		if (!$dba->num_rows($user_result))	message($lang_common['No user data'], false, '404 Not Found');
		$cur_user = $dba->fetch_assoc($user_result);

		echo '<tr id="p'.$cur_post['id'].'" class="post">'.
				'<td class="postleft">'.
					$cur_user['username'].'<br/>'.
					'#'.$cur_post['id'].' '.
					format_time($cur_post['posted'], false, "y/m/d", "h:s").
				'</td>'.
				'<td class="postright">'.$cur_post['message'].'</td>'.
			'</tr>'.
			'<tr class="postinter"></tr>';
	}
	echo '</table>';
}

echo '<div id="brdfooter">'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$cur_posting['id'].'">'.pun_htmlspecialchars($cur_posting['forum_name']).'</a></td>';
			if (isset($_POST['req_subject']))	echo '<td>'.pun_htmlspecialchars($_POST['req_subject']).'</td>';
			if (isset($cur_posting['subject']))	echo '<td><a href="'.PUN_URL.'topic.php?id='.$tid.'">'.pun_htmlspecialchars($cur_posting['subject']).'</a></td>';
echo	'</table>'.
	'</div>';

require PUN_ROOT.'footer.php';
