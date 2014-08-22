<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')	message($lang_common['No view'], false, '403 Forbidden');

$page_id = isset($_GET['page']) ? intval($_GET['page']) : 0;
if ($page_id < 1)	message($lang_common['Bad request'], false, '404 Not Found');

$result = $db->query('SELECT name'.($pun_user['fr'] ? '_loc2' : '').' AS name FROM '.$db->prefix.'site_items WHERE id = \''.$page_id.'\'') or error($lang_common['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_common['Bad request'], false, '404 Not Found');
$page_name = $db->result($result);

require PUN_ROOT.'include/parser.php';
$display = display_site_item($page_id, $page_name);

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'site');
require PUN_ROOT.'header.php';

echo '<div id="page">'.
		$display.
	'</div>';

require PUN_ROOT.'footer.php';
