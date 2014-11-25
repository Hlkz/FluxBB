<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')	message($lang_common['No view'], false, '403 Forbidden');

$page_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$page_name = isset($_GET['page']) ? $_GET['page'] : null;
if ($page_id < 1 && !$page_name)	message($lang_common['Bad request'], false, '404 Not Found');

$result = $db->query('SELECT id, parent_id FROM '.$db->prefix.'site_items WHERE '.($page_name ? ('url = \''.$page_name.'\'') : ('id = \''.$page_id.'\'')))
						or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');
$cur_page = $db->fetch_assoc($result);

$parents = array();
$cur_parent_id = $cur_page['parent_id'];
while ($cur_parent_id) {
	$result = $db->query('SELECT id, name'.($pun_user['fr'] ? '_loc2' : '').' AS name, url, parent_id FROM '.$db->prefix.'site_items WHERE id = \''.$cur_parent_id.'\'')
							or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))	continue;
	$cur_parent = $db->fetch_assoc($result);
	$cur_parent_id = $cur_parent['parent_id'];
	if ($cur_parent['url'])
		$parents[] = '<td><a href="'.PUN_URL.'site/'.$cur_parent['url'].'">'.$cur_parent['name'].'</a></td>';
}
$parents = array_reverse($parents);

require PUN_ROOT.'include/parser.php';
$display = display_site_item($page_id, $page_name);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'site');
require PUN_ROOT.'header.php';

echo '<div id="brdheader">';
if (!empty($parents))
	echo '<table class="bigbuttons">'.
			implode($parents).
		'</table>';
echo	'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
	'</div>';

echo '<div id="page">'.
		$display.
	'</div>';

echo '<div id="brdfooter">'.
		'<div class="botright">'.$botright_links.'</div>'.
		'<div class="botleft">'.$botleft_links.'</div>'.
		'<table class="bigbuttons">'.
			implode($parents).
		'</table>'.
	'</div>';

require PUN_ROOT.'footer.php';
