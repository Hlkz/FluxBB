<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/item.php';

if (isset($_POST['submit']) || isset($_POST['submit2edit']))
{
	confirm_referrer('editor.php');

	if (isset($_POST['req_sql']))
	{
		file_put_contents('log.sql', '
		');
		$sql = $_POST['req_sql'];
		$result = $db->queries($sql) or error($lang_db['DB Error'], __FILE__, __LINE__, $db->error());
		file_put_contents("log.sql", $sql);
	}

	if (isset($_POST['submit']))
		redirect('view.php'.$_POST['link_name'], '', true);
	else
		redirect('editor.php'.$_POST['link_name'], '', true);
}
else if (isset($_POST['refresh']))
{
	confirm_referrer('editor.php');
	$table_name = isset($_GET['table']) ? $_GET['table'] : null;
	redirect('editor.php?table='.$table_name.($_POST['req_0'] ? '&id='.$_POST['req_0'] : ''), '', true);
}

exit;
