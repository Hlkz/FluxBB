<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if (isset($_POST['submit']) || isset($_POST['submit2edit']))
{
	if (!isset($_POST['req_sql']) || !isset($_POST['req_tablename']))
		message($lang_common['Bad request'], false, '404 Not Found'); 

	$sql 		= $_POST['req_sql'];
	$table_name = $_POST['req_tablename'];

	// Get Table Info
	$query = 'SELECT DbName, Edit FROM item_tables WHERE Name = \''.$db->escape($table_name).'\'';
	$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) message($lang_item['No table data'], false, '404 Not Found'); // No table data
	$cur_table = $db->fetch_assoc($result); // Query OK
	if (!$cur_table['Edit'] && !$pun_user['is_admin'])	message($lang_item['Access denied'], false, '404 Not Found'); // Access denied
	$db2 = new DBLayer($db_host, $db_user, $db_pass, $cur_table['DbName'], $db_prefix, $p_connect);

	file_put_contents('log.sql', '
	');

	$result = $db2->queries($sql) or error($lang_db['DB Error'], __FILE__, __LINE__, $db2->error());
	file_put_contents("log.sql", $sql);

	if (isset($_POST['submit']))
		redirect('view.php'.$_POST['link_name'], '', true);
	else
		redirect('editor.php'.$_POST['link_name'], '', true);
}
else if (isset($_POST['refresh']))
{
	//confirm_referrer('editor.php');
	$table_name = isset($_GET['table']) ? $_GET['table'] : null;
	redirect('editor.php?table='.$table_name.($_POST['req_0'] ? '&id='.$_POST['req_0'] : ''), '', true);
}

exit;
