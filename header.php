<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN')) exit;

// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache'); // For HTTP/1.0 compatibility
// Send the Content-type header in case the web server is setup to send something else
header('Content-type: text/html; charset=utf-8');

// Load the template
if (defined('PUN_ADMIN_CONSOLE'))	$tpl_file = 'admin.tpl';
else if (defined('PUN_HELP'))		$tpl_file = 'help.tpl';
else								$tpl_file = 'main.tpl';

if (file_exists(PUN_ROOT.'style/'.$pun_user['style'].'/'.$tpl_file)) {
	$tpl_file = PUN_ROOT.'style/'.$pun_user['style'].'/'.$tpl_file;
	$tpl_inc_dir = PUN_ROOT.'style/'.$pun_user['style'].'/'; }
else {
	$tpl_file = PUN_ROOT.'include/template/'.$tpl_file;
	$tpl_inc_dir = PUN_ROOT.'include/user/'; }

$tpl_main = file_get_contents($tpl_file);


// START SUBST - <pun_include "*">
preg_match_all('%<pun_include "([^"]+)">%i', $tpl_main, $pun_includes, PREG_SET_ORDER);

foreach ($pun_includes as $cur_include)
{
	ob_start();

	$file_info = pathinfo($cur_include[1]);
	
	if (!in_array($file_info['extension'], array('php', 'php4', 'php5', 'inc', 'html', 'txt'))) // Allow some extensions
		error(sprintf($lang_common['Pun include extension'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file), pun_htmlspecialchars($file_info['extension'])));
		
	if (strpos($file_info['dirname'], '..') !== false) // Don't allow directory traversal
		error(sprintf($lang_common['Pun include directory'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file)));

	// Allow for overriding user includes, too.
	if (file_exists($tpl_inc_dir.$cur_include[1]))
		require $tpl_inc_dir.$cur_include[1];
	else if (file_exists(PUN_ROOT.'include/user/'.$cur_include[1]))
		require PUN_ROOT.'include/user/'.$cur_include[1];
	else	error(sprintf($lang_common['Pun include error'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file)));

	$tpl_temp = ob_get_contents();
	$tpl_main = str_replace($cur_include[0], $tpl_temp, $tpl_main);
	ob_end_clean();
}
// END SUBST - <pun_include "*">


// START SUBST - <pun_language>
$tpl_main = str_replace('<pun_language>', $lang_common['lang_identifier'], $tpl_main);
// END SUBST - <pun_language>


// START SUBST - <pun_content_direction>
$tpl_main = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_main);
// END SUBST - <pun_content_direction>


// START SUBST - <pun_head>
ob_start();

// Define $p if it's not set to avoid a PHP notice
$p = isset($p) ? $p : null;

// Is this a page that we want search index spiders to index?
if (!defined('PUN_ALLOW_INDEX'))
	echo '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />'."\n";

?>
<title><?php echo generate_page_title($page_title, null /* $p */) ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo PUN_URL.'style/'.$pun_user['style'].'.css' ?>" />
<?php

if (defined('PUN_ADMIN_CONSOLE')) {
	if (file_exists('style/'.$pun_user['style'].'/base_admin.css'))
		echo '<link rel="stylesheet" type="text/css" href="'.PUN_URL.'style/'.$pun_user['style'].'/base_admin.css" />'."\n";
	else
		echo '<link rel="stylesheet" type="text/css" href="'.PUN_URL.'style/imports/base_admin.css" />'."\n"; }

// Tabs
echo '<script src="'.PUN_URL.'include/js/jquery-1.10.2.js"></script>'.
	'<script src="'.PUN_URL.'include/js/jquery-ui.js"></script>'.
	'<script> $(function() { $( "#tabs" ).tabs(); }); </script>';

if (PUN_ACTIVE_PAGE == 'view' || PUN_ACTIVE_PAGE == 'editor')
	echo '<script src="'.PUN_URL.'include/js/item.js"></script>';

if (PUN_ACTIVE_PAGE == 'register')
{
	echo '<script src="'.PUN_URL.'include/js/jquery-1.2.6.min.js"></script>';
	require PUN_ROOT.'include/js/register.js';
}
else if (isset($required_fields)) // Output JavaScript to validate form (make sure required fields are filled out)
{ ?>
<script type="text/javascript">
function process_form(the_form)
{
	var required_fields = {
<?php // Output a JavaScript object with localised field names
	$tpl_temp = count($required_fields);
	foreach ($required_fields as $elem_orig => $elem_trans)
	{
		echo "\t\t\"".$elem_orig.'": "'.addslashes(str_replace('&#160;', ' ', $elem_trans));
		if (--$tpl_temp) echo "\",\n";
		else echo "\"\n\t};\n";
	} ?>
	if (document.all || document.getElementById)
	{
		for (var i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i];
			if (elem.name && required_fields[elem.name] && !elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
			{
				alert('"' + required_fields[elem.name] + '" <?php echo $lang_common['required field'] ?>');
				elem.focus();
				return false;
			}
		}
	}
	return true;
}
</script><?php
}

// JavaScript tricks for IE6 and older
echo '<!--[if lte IE 6]><script type="text/javascript" src="'.PUN_URL.'style/imports/minmax.js"></script><![endif]-->'."\n";

if (isset($page_head))
	echo implode("\n", $page_head)."\n";

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_head>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <pun_head>


// START SUBST - <body>
if (isset($focus_element))
{
	$tpl_main = str_replace('<body onload="', '<body onload="document.getElementById(\''.$focus_element[0].'\').elements[\''.$focus_element[1].'\'].focus();', $tpl_main);
	$tpl_main = str_replace('<body>', '<body onload="document.getElementById(\''.$focus_element[0].'\').elements[\''.$focus_element[1].'\'].focus()">', $tpl_main);
}
// END SUBST - <body>

// START SUBST - <pun_page>
$tpl_main = str_replace('<pun_page>', htmlspecialchars(basename($_SERVER['PHP_SELF'], '.php')), $tpl_main);
// END SUBST - <pun_page>


// START SUBST - <pun_title>
$tpl_main = str_replace('<pun_title>', '<h1><a href="'.PUN_URL.'index.php">'.pun_htmlspecialchars($pun_config['o_board_title']).'</a></h1>', $tpl_main);
// END SUBST - <pun_title>


// START SUBST - <pun_desc>
$tpl_main = str_replace('<pun_desc>', '<div id="brddesc">'.$pun_config['o_board_desc'].'</div>', $tpl_main);
// END SUBST - <pun_desc>


// START SUBST - <pun_mainwidth>
$tpl_main = str_replace('<pun_mainwidth>', (
			(PUN_ACTIVE_PAGE == 'board'	|| PUN_ACTIVE_PAGE == 'forum' 	|| PUN_ACTIVE_PAGE == 'topic') 	? 	'960px'
		:(	(PUN_ACTIVE_PAGE == 'view' 	|| PUN_ACTIVE_PAGE == 'editor') 								? 	'1200px'
		: 																									'960px'		)), $tpl_main);
// END SUBST - <pun_mainwidth>


// START SUBST - <pun_url>
$tpl_main = str_replace('<pun_url>', PUN_URL, $tpl_main);
// END SUBST - <pun_url>


// START SUBST - <pun_navlinks>
$links = array();

//$links[] = '<li id="navindex"'.((PUN_ACTIVE_PAGE == 'index') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'index.php">'.$lang_common['Nav Index'].'</a></li>';
$links[] = '<li id="navgame"'.((PUN_ACTIVE_PAGE == 'game') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'game.php">'.$lang_common['Nav Game'].'</a></li>';
$links[] = '<li id="navnews"'.((PUN_ACTIVE_PAGE == 'news') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'news.php">'.$lang_common['Nav News'].'</a></li>';
$links[] = '<li id="navboard"'.((PUN_ACTIVE_PAGE == 'board') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'board.php">'.$lang_common['Nav Board'].'</a></li>';
$links[] = '<li id="navdb"'.((PUN_ACTIVE_PAGE == 'database') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'database.php">'.$lang_common['Nav Database'].'</a></li>';

// Are there any additional navlinks we should insert into the array before imploding it?
/* if ($pun_user['g_read_board'] == '1' && $pun_config['o_additional_navlinks'] != '') {
	if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $pun_config['o_additional_navlinks']."\n", $extra_links))
		$num_links = count($extra_links[1]); // Insert any additional links into the $links array (at the correct index)
		for ($i = 0; $i < $num_links; ++$i)
			array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra'.($i + 1).'">'.$extra_links[2][$i].'</li>')); } } */

$tpl_temp = '<div id="headermenu" class="inbox">'."\n\t\t\t".'<ul>'."\n\t\t\t\t".implode("\n\t\t\t\t", $links)."\n\t\t\t".'</ul>'."\n\t\t".'</div>';
$tpl_main = str_replace('<pun_navlinks>', $tpl_temp, $tpl_main);
// END SUBST - <pun_navlinks>


// START SUBST - <pun_account>
$tpl_temp = '<div id="headeraccount">';

if ($pun_user['is_guest'])	$tpl_temp .= $lang_common['Not logged in'].' <a href="'.PUN_URL.'login.php">'.$lang_common['Login'].'</a>';
else						$tpl_temp .= $lang_common['Logged in as'].' '.$pun_user['username'].' <a href="'.PUN_URL.'login.php?action=out">'.$lang_common['Logout'].'</a>'.
										'<a href="'.PUN_URL.'account.php?id='.$pun_user['id'].'">'.'<div class="space"></div>'.
										$lang_common['Manage account'].'</a>';
$tpl_temp .= '<div class="space"></div>'.
			'<a href="'.PUN_URL.'register.php">'.$lang_common['Signin'].'</a>'.
			'<div class="space"></div>';

$lang_to_switch = "English";	$languages = forum_list_langs();
if (count($languages) > 1)	foreach ($languages as $temp)	if ($pun_user['language'] != $temp)	$lang_to_switch = $temp;
$tpl_temp .= '<form id="lang_form" method="POST" action="'.PUN_URL.'?lang='.$lang_to_switch.'" style="display:inline">'.
			'<input type="hidden" name="lang_redirect_url" value="http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'" />'.
			'<input class="lang_button" type="submit" value="'.$lang_common['Nav Language'].'" /></form>';
$tpl_temp .= '</div>';
$tpl_main = str_replace('<pun_account>', $tpl_temp, $tpl_main);
// END SUBST - <pun_account>


// START SUBST - <pun_main>
ob_start();

define('PUN_HEADER', 1);
