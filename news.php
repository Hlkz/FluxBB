<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'news');
require PUN_ROOT.'header.php';

echo '<div id="idx0" class="block"><div class="box"><div class="inbox"><p>En construction.</p></div></div></div>';

$footer_style = 'index';
require PUN_ROOT.'footer.php';
