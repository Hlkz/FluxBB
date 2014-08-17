<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache'); // For HTTP/1.0 compatibility

// Send the Content-type header in case the web server is setup to send something else
header('Content-type: text/html; charset=utf-8');

// Load the template
if (defined('PUN_ADMIN_CONSOLE'))
	$tpl_file = 'admin.tpl';
else if (defined('PUN_HELP'))
	$tpl_file = 'help.tpl';
else
	$tpl_file = 'main.tpl';

if (file_exists(PUN_ROOT.'style/'.$pun_user['style'].'/'.$tpl_file))
{
	$tpl_file = PUN_ROOT.'style/'.$pun_user['style'].'/'.$tpl_file;
	$tpl_inc_dir = PUN_ROOT.'style/'.$pun_user['style'].'/';
}
else
{
	$tpl_file = PUN_ROOT.'include/template/'.$tpl_file;
	$tpl_inc_dir = PUN_ROOT.'include/user/';
}

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
	else
		error(sprintf($lang_common['Pun include error'], pun_htmlspecialchars($cur_include[0]), basename($tpl_file)));

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

if (defined('PUN_ADMIN_CONSOLE'))
{
	if (file_exists('style/'.$pun_user['style'].'/base_admin.css'))
		echo '<link rel="stylesheet" type="text/css" href="'.PUN_URL.'style/'.$pun_user['style'].'/base_admin.css" />'."\n";
	else
		echo '<link rel="stylesheet" type="text/css" href="'.PUN_URL.'style/imports/base_admin.css" />'."\n";
}

// Tabs
?>
	<script src="../include/jquery-1.10.2.js"></script>
	<script src="../include/jquery-ui.js"></script>
	<script> $(function() { $( "#tabs" ).tabs(); }); </script>
<?php

if (PUN_ACTIVE_PAGE == 'view' || PUN_ACTIVE_PAGE == 'editor')
{
?>	<script src="../include/item.js"></script>	<?php
}

if (PUN_ACTIVE_PAGE == 'register')
{
?>

<script type="text/javascript" src="../include/jquery-1.2.6.min.js"></script>
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var required_fields = {
<?php
	// Output a JavaScript object with localised field names
	$tpl_temp = count($required_fields);
	foreach ($required_fields as $elem_orig => $elem_trans)
	{
		echo "\t\t\"".$elem_orig.'": "'.addslashes(str_replace('&#160;', ' ', $elem_trans));
		if (--$tpl_temp) echo "\",\n";
		else echo "\"\n\t};\n";
	}
?>
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
			else if (elem.name && required_fields[elem.name] && elem.getAttribute('error') != 'false' && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
			{
				alert(document.getElementById(elem.id+'_err').innerHTML);
				elem.focus();
				return false;
			}
		}
	}
	return true;
}
/* ]]> */
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $("input").not($(":button")).keypress(function (evt) {
            if (evt.keyCode == 13) {
                var id = this.id;
				if (id == 'req_account')
				{
					document.getElementById('req_password1').focus();
					return false;
				}
				else if (id !== 'register' ) {
					$(this).change();
                    var fields = $(this).parents('form:eq(0),body').find('button, input, textarea, select');
                    var index = fields.index(this);
                    if (index > -1 && (index + 1) < fields.length) {
                        fields.eq(index + 1).focus();
                    }
                    return false;
                }
            }
        });
    });
</script>
<SCRIPT type="text/javascript">
pic1 = new Image(16, 16); 
pic1.src = "include/loader.gif";

$(document).ready(function() {
	$("#req_account").change(function() {
		var account = $("#req_account").val();
		$("#req_username").val(account);
		$("#req_username").change();
		$.ajax({
			type: "POST",
			url: "register_check.php",
			data: "check_account="+ account,
			success: function(msg) {
				$("#rep_account").ajaxComplete(function(event, request, settings) {
					$("#rep_account").html(msg);
					if ($("#req_account_err").attr('error') == 'true')
						$("#req_account").attr('error', 'true');
					else $("#req_account").attr('error', 'false');
				}); }
		});
	});
	$("#req_username").change(function() {
		$.ajax({
			type: "POST",
			url: "register_check.php",
			data: "username="+ $("#req_username").val(),
			success: function(msg) {
				$("#rep_username").ajaxComplete(function(event, request, settings) {
					$("#rep_username").html(msg);
					if ($("#req_username_err").attr('error') == 'true')
						$("#req_username").attr('error', 'true');
					else $("#req_username").attr('error', 'false');
				}); }
		});
	});
	$("#req_password1").change(function() {
		if ($("#req_password2").val() !== "")
			$("#req_password2").change();
		$.ajax({
			type: "POST",
			url: "register_check.php",
			data: "password1="+ $("#req_password1").val(),
			success: function(msg) {
				$("#rep_password1").ajaxComplete(function(event, request, settings) {
					$("#rep_password1").html(msg);
					if ($("#req_password1_err").attr('error') == 'true')
						$("#req_password1").attr('error', 'true');
					else $("#req_password1").attr('error', 'false');
				}); }
		});
	});
	$("#req_password2").change(function() {
		var match = 0;
		if ($("#req_password1").val() == $("#req_password2").val())
			match = 1;
		$.ajax({
			type: "POST",
			url: "register_check.php",
			data: "password2="+ match,
			success: function(msg) {
				$("#rep_password2").ajaxComplete(function(event, request, settings) {
					$("#rep_password2").html(msg);
					if ($("#req_password2_err").attr('error') == 'true')
						$("#req_password2").attr('error', 'true');
					else $("#req_password2").attr('error', 'false');
				}); }
		});
	});
	$("#req_email").change(function() {
		$.ajax({
			type: "POST",
			url: "register_check.php",
			data: "email="+ $("#req_email").val(),
			success: function(msg) {
				$("#rep_email").ajaxComplete(function(event, request, settings) {
					$("#rep_email").html(msg);
					if ($("#req_email_err").attr('error') == 'true')
						$("#req_email").attr('error', 'true');
					else $("#req_email").attr('error', 'false');
				}); }
		});
	});
});
</SCRIPT>

<?php
}
else if (isset($required_fields))
{
	// Output JavaScript to validate form (make sure required fields are filled out)

?>
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var required_fields = {
<?php
	// Output a JavaScript object with localised field names
	$tpl_temp = count($required_fields);
	foreach ($required_fields as $elem_orig => $elem_trans)
	{
		echo "\t\t\"".$elem_orig.'": "'.addslashes(str_replace('&#160;', ' ', $elem_trans));
		if (--$tpl_temp) echo "\",\n";
		else echo "\"\n\t};\n";
	}
?>
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
/* ]]> */
</script>

<?php

}

// JavaScript tricks for IE6 and older
echo '<!--[if lte IE 6]><script type="text/javascript" src="style/imports/minmax.js"></script><![endif]-->'."\n";

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
$tpl_main = str_replace('<pun_mainwidth>', (PUN_ACTIVE_PAGE == 'board' || PUN_ACTIVE_PAGE == 'forum' || PUN_ACTIVE_PAGE == 'topic') ? '960px' : '1200px', $tpl_main);
// END SUBST - <pun_title>


// START SUBST - <pun_navlinks>
$links = array();

$links[] = '<li id="navindex"'.((PUN_ACTIVE_PAGE == 'index') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'index.php">'.$lang_common['Index'].'</a></li>';
$links[] = '<li id="navboard"'.((PUN_ACTIVE_PAGE == 'board') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'board.php">'.$lang_common['Board'].'</a></li>';

$links[] = '<li id="navdb"><a href="'.PUN_URL.'db/spelldbc-Fireball">Fireball</a></li>';

/*if ($pun_user['is_guest'])
{
	$links[] = '<li id="navregister"'.((PUN_ACTIVE_PAGE == 'register') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'register.php">'.$lang_common['Register'].'</a></li>';
	$links[] = '<li id="navlogin"'.((PUN_ACTIVE_PAGE == 'login') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'login.php">'.$lang_common['Login'].'</a></li>';
}
else
{
	$links[] = '<li id="navprofile"'.((PUN_ACTIVE_PAGE == 'profile') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'profile.php?id='.$pun_user['id'].'">'.$lang_common['Profile'].'</a></li>';

	if ($pun_user['is_admmod'])
		$links[] = '<li id="navadmin"'.((PUN_ACTIVE_PAGE == 'admin') ? ' class="isactive"' : '').'><a href="'.PUN_URL.'admin_index.php">'.$lang_common['Admin'].'</a></li>';

	$links[] = '<li id="navlogout"><a href="'.PUN_URL.'login.php?action=out&amp;id='.$pun_user['id'].'&amp;csrf_token='.pun_hash($pun_user['id'].pun_hash(get_remote_address())).'">'.$lang_common['Logout'].'</a></li>';
}*/

	$lang = "English";
	$languages = forum_list_langs();
	if (count($languages) > 1)
		foreach ($languages as $temp)
			if ($pun_user['language'] != $temp)
				$lang = $temp;
	$actual_link = PUN_URL;
	if ((substr($actual_link, -4) == ".php") || (substr($actual_link, -1) == "/"))
		$links[] = '<li id="navlanguage"><a href="'.$actual_link.'?lang='.$lang.'">'.$lang_common['Language'].'</a></li>';
	else
		$links[] = '<li id="navlanguage"><a href="'.$actual_link.'&lang='.$lang.'">'.$lang_common['Language'].'</a></li>';

// Are there any additional navlinks we should insert into the array before imploding it?
if ($pun_user['g_read_board'] == '1' && $pun_config['o_additional_navlinks'] != '')
{
	if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $pun_config['o_additional_navlinks']."\n", $extra_links))
	{
		// Insert any additional links into the $links array (at the correct index)
		$num_links = count($extra_links[1]);
		for ($i = 0; $i < $num_links; ++$i)
			array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra'.($i + 1).'">'.$extra_links[2][$i].'</li>'));
	}
}

$tpl_temp = '<div id="headermenu" class="inbox">'."\n\t\t\t".'<ul>'."\n\t\t\t\t".implode("\n\t\t\t\t", $links)."\n\t\t\t".'</ul>'."\n\t\t".'</div>';
$tpl_main = str_replace('<pun_navlinks>', $tpl_temp, $tpl_main);
// END SUBST - <pun_navlinks>

// START SUBST - <pun_main>
ob_start();

define('PUN_HEADER', 1);
