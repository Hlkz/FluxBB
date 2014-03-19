<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view'], false, '403 Forbidden');


// Load the index.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/index.php';

$forum_actions = array();

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'index');
require PUN_ROOT.'header.php';

echo '<div id="idx0" class="block"><div class="box"><div class="inbox"><p>Aucune news à afficher.</p></div></div></div>';

// Collect some statistics from the database
if (file_exists(FORUM_CACHE_DIR.'cache_users_info.php'))
	include FORUM_CACHE_DIR.'cache_users_info.php';

if (!defined('PUN_USERS_INFO_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require PUN_ROOT.'include/cache.php';

	generate_users_info_cache();
	require FORUM_CACHE_DIR.'cache_users_info.php';
}

if (!empty($forum_actions))
{

?>
<div class="linksb">
	<div class="inbox crumbsplus">
		<p class="subscribelink clearb"><?php echo implode(' - ', $forum_actions); ?></p>
	</div>
</div>
<?php

}

$footer_style = 'index';
require PUN_ROOT.'footer.php';
