<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request'], false, '404 Not Found');

require PUN_ROOT.'include/lang/'.$pun_user['language'].'/common.php';

// Fetch some info about the forum
$result = $db->query('SELECT f.forum_name, f.redirect_url FROM '.$db->prefix.'board_forums AS f WHERE f.id='.$id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');

$cur_forum = $db->fetch_assoc($result);

// Is this a redirect forum? In that case, redirect!
if ($cur_forum['redirect_url'] != '')
{
	header('Location: '.$cur_forum['redirect_url']);
	exit;
}

$cur_forum['sort_by'] = 0;
switch ($cur_forum['sort_by'])
{
	case 0:
		$sort_by = 'last_posted DESC';
		break;
	case 1:
		$sort_by = 'posted DESC';
		break;
	case 2:
		$sort_by = 'subject ASC';
		break;
	default:
		$sort_by = 'last_posted DESC';
		break;
}

// Can we or can we not post new topics? YES WE CAN
$post_link = "\t\t\t".'<p class="postlink conr"><a href="'.PUN_URL.'post.php?fid='.$id.'">'.$lang_forum['Post topic'].'</a></p>'."\n";

// Get topic/forum tracking data
if (!$pun_user['is_guest'])
	$tracked_topics = get_tracked_topics();

// Determine the topic offset (based on $_GET['p'])
$num_pages = ceil($cur_forum['num_topics'] / $pun_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_topics'] * ($p - 1);

// Generate paging links
$botleft_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'forum.php?id='.$id);
$botright_links = '';

$forum_actions = array();

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), pun_htmlspecialchars($cur_forum['forum_name']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'forum');
require PUN_ROOT.'header.php';
$topright_links = '<input type="text" value="temp" /> <a href="'.PUN_URL.'search.php">'.$lang_common['Search'].'</a>';

echo '<div id="brdfooter">'.
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

// If there are topics in this forum
if ($db->num_rows($result))
{
	$topic_ids = array();
	for ($i = 0; $cur_topic_id = $db->result($result, $i); $i++)
		$topic_ids[] = $cur_topic_id;

	$sql = 'SELECT id, subject, posted, num_views, closed, sticky FROM '.$db->prefix.'board_topics WHERE id IN('.implode(',', $topic_ids).') ORDER BY sticky DESC, '.$sort_by.', id DESC';

	$result = $db->query($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());

	$topic_count = 0;
	while ($cur_topic = $db->fetch_assoc($result))
	{
		++$topic_count;
		$status_text = array();
		$item_status = ($topic_count % 2 == 0) ? 'roweven' : 'rowodd';

		$link = PUN_URL.'topic.php?id='.$cur_topic['id'];

		if ($cur_topic['sticky'] == '1')
		{
			$item_status .= ' isticky';
			$status_text[] = '<span class="stickytext">'.$lang_forum['Sticky'].'</span>';
		}

		$subject = '<a class="topiclink" href="'.$link.'"><b>'.pun_htmlspecialchars($cur_topic['subject']).'</b></a>  '.$cur_topic['poster'];
			
		if ($cur_topic['closed'])
		{
			$item_status .= ' iclosed';
			$status_text[] = '<span class="closedtext">'.$lang_forum['Closed'].'</span>';
		}

		// Insert the status text before the subject
		$subject = implode(' ', $status_text).' '.$subject;

		$result2 = $db->query('SELECT COUNT(*) FROM '.$db->prefix.'board_posts WHERE topic_id='.$cur_topic['id']) or error('Unable to fetch topic posts count', __FILE__, __LINE__, $db->error());
		$posts_count = $db->result($result2);

		$num_pages_topic = ceil($posts_count / $pun_user['disp_posts']);

		if ($num_pages_topic > 1)	$subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, PUN_URL.'topic.php?id='.$cur_topic['id']).' ]</span>';
		else						$subject_multipage = null;
		
		echo '<div class="topic" onclick="window.location=\''.$link.'\';">'.$subject.$subject_multipage.'</div>';
	}
}
else
{
	$colspan = ($pun_config['o_topic_views'] == '1') ? 4 : 3;

?>
				<tr class="rowodd inone">
					<td class="tcl" colspan="<?php echo $colspan ?>">
						<div class="tclcon">
							<div>
								<strong><?php echo $lang_forum['Empty forum'] ?></strong>
							</div>
						</div>
					</td>
				</tr>
<?php
}
?>
			</tbody>
			</table>
</div>

<?php
echo '<div id="brdfooter">'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
		'<table class="bigbuttons">'.
			'<td><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></td>'.
			'<td><a href="'.PUN_URL.'forum.php?id='.$id.'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a></td>'.
		'</table>'.
	'</div>';

$forum_id = $id;
$footer_style = 'board';
require PUN_ROOT.'footer.php';
