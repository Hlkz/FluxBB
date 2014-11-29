<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

$loc = $pun_user['fr'] ? '_loc2' : '';
$table_name = isset($_GET['table'])	? $_GET['table'] 	: null;

if ($table_name)
{
	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
}
else
{
	$database = '<div id="database">';

	// Get Categories Info
	$query = 'SELECT Id, Name'.$loc.' as Name, Description'.$loc.' as Description FROM item_categories WHERE View = 1 ORDER by DisplayOrder';
	$result_cat = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result_cat))	message($lang_item['No category data'], false, '404 Not Found'); 			// No category data
	while ($cur_cat = $db->fetch_assoc($result_cat)) {
		$database .= '<div><div onclick="toggle_categories(\'category'.$cur_cat['Id'].'\')">'.$cur_cat['Name'].'</div>'.
						($cur_cat['Description'] ? '<div class="tabledesc">'.$cur_cat['Description'].'</div>' : '').
						'<div id="category'.$cur_cat['Id'].'" class="category">';
		
		// Get SubCategories Info
		$subcats = array(); $subcatcount = 0;
		$subcat['Id'] = $subcatcount; $subcat['Name'] = ''; $subcat['Description'] = '';
		$subcats[$subcatcount] = $subcat; $subcatcount++;
		$query = 'SELECT Id, Name'.$loc.' as Name, Description'.$loc.' as Description FROM item_subcategories WHERE View = 1 ORDER by DisplayOrder';
		$result_subcat = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result_subcat))
		while ($cur_subcat = $db->fetch_assoc($result_subcat)) {
			$subcat['Id'] = $cur_subcat['Id']; $subcat['Name'] = $cur_subcat['Name']; $subcat['Description'] = $cur_subcat['Description'];
			$subcats[$subcatcount] = $subcat; $subcatcount++; }
		foreach ($subcats as $subcat) {
			$database .= '<div class="subcategory">'.$subcat['Name'].($subcat['Description'] ? '<div class="tabledesc">'.$subcat['Description'].'</div>' : '');

			// Get Tables Info
			$query = 'SELECT Id, DbName, Name, Display'.$loc.' as Display, Description'.$loc.' as Description, ViewLevel, EditLevel FROM item_tables'
					.' WHERE Category = '.$cur_cat['Id'].' AND SubCategory = '.$subcat['Id'].' ORDER by Category, SubCategory, Display'.$loc;
					
			$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result))
			while ($cur_table = $db->fetch_assoc($result)) {
				$link = PUN_URL.'database.php?table='.$cur_table['Name'];
				$table_field = '<a href="'.$link.'" class="forumlink">'.($cur_table['Display'] ? $cur_table['Display'] : $cur_table['Name']).'</a>';
				if ($cur_table['Description'.$loc])	$table_field .= "\n\t\t\t\t\t\t\t\t".'<div class="tabledesc">'.$cur_table['Description'].'</div>';
				$database .= '<div class="table" onclick="window.location=\''.$link.'\'">'.$table_field.'</div>'; }

			$database .= '</div>'; }
		
		$database .= '</div></div>';
	}

	$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']));
}

define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'db');
require PUN_ROOT.'header.php';

echo '<div id="idx0" class="block"><div class="box">'.
		'<div class="inbox">'.$database.'</div>'.
	'</div></div>';

$footer_style = 'database';
require PUN_ROOT.'footer.php';
