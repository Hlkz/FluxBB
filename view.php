<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/lang/'.$pun_user['language'].'/item.php';

$table_name = isset($_GET['table'])	? $_GET['table'] 	: null;
$name 		= isset($_GET['name']) 	? $_GET['name'] 	: null;
$id = isset($_GET['id']) ? (intval($_GET['id']) > 0 ? intval($_GET['id']) : 0) : 0;

if ($name && $id)	$name = null; // $name and $id can not match
if (!$table_name || (!$name && !$id))	message($lang_item['Bad link'], false, '404 Not Found'); 	// Bad link

// Get Table Info
$query = 'SELECT Id, Fields, DbName, Name, Display, PrimaryField, NameField, View, Edit, Tab1, Tab2, Tab3, Tab4, Tab5, Tab6, Tab7, Tab8 FROM item_tables WHERE Name = \''.$db->escape($table_name).'\'';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	$query = 'SELECT Id, Fields, DbName, Name, Display, PrimaryField, NameField, View, Edit, Tab1, Tab2, Tab3, Tab4, Tab5, Tab6, Tab7, Tab8 FROM item_tables WHERE Id = \''.$db->escape($table_name).'\'';
	$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) message($lang_item['No table data'], false, '404 Not Found'); } 	// No table data
$cur_table = $db->fetch_assoc($result); // Query OK
if (!strcmp($table_name, $cur_table['Name']))	$table_name = $cur_table['Name']; // Check table_name cAsE or id
else	redirect('view.php?table='.$cur_table['Name'].($name ? ('&name='.$name) : ('&id='.$id)), '', true);
if (!$cur_table['View'] && !$pun_user['is_admin'])	message($lang_item['Access denied'], false, '404 Not Found'); // Access denied
$table_display = $cur_table['Display'] ? $cur_table['Display'] : $cur_table['Name'];
$db2 = new DBLayer($db_host, $db_user, $db_pass, $cur_table['DbName'], $db_prefix, $p_connect);

// Get Item Real Name
if ($name)	$query = 'SELECT '.$cur_table['PrimaryField'].', '.$cur_table['NameField'].' FROM '.$table_name.' WHERE '.$cur_table['NameField'].'=\''.$db->escape($name).'\'';
else		$query = 'SELECT '.$cur_table['PrimaryField'].', '.$cur_table['NameField'].' FROM '.$table_name.' WHERE '.$cur_table['PrimaryField'].'=\''.$id.'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if (!$db2->num_rows($result)) message($lang_item['No item data'], false, '404 Not Found'); 			// No item data
$cur_name = $db2->fetch_assoc($result);
if ($name && !strcmp($name, $cur_name[$cur_table['NameField']]))
	$id = $cur_name[$cur_table['PrimaryField']];
else
	$name = $cur_name[$cur_table['NameField']];
$link_name 	= '?table='.pun_htmlspecialchars($table_name).'&name='.pun_htmlspecialchars($name);
if (!$id)	redirect('view.php'.$link_name, '', true);
$link_id 	= '?table='.pun_htmlspecialchars($table_name).'&id='.$id;
$query = 'SELECT COUNT(*) FROM '.$table_name.' WHERE '.$cur_table['NameField'].'=\''.$db->escape($name).'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if ($db2->result($result) != 1) $link_name = '?table='.pun_htmlspecialchars($table_name).'&id='.$id.'&name='.pun_htmlspecialchars($name);

// Get Fields Info
$fields[] = array();
$field_count = 0;
$query = 'SELECT Id, Fields, TabId, LineId, Name, Type, Display, LinkedTableId, EditorTextLength, EditorTextHeight FROM item_fields WHERE Fields = '.$cur_table['Fields'].' ORDER BY TabId, LineId, Id';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_item['No fields data'], false, '404 Not Found'); 			// No fields data
while ($cur_field = $db->fetch_assoc($result)) {
	$fields[$field_count] = $cur_field;
	$field_count++; }
if ($field_count < 2)	message($lang_item['No fields data'], false, '404 Not Found'); 					// No fields data

// Get Item Info
$query = 'SELECT '.$fields[0]['Name'];
for ($nb = 1; $nb < $field_count; $nb++)
	$query .= ', '.$fields[$nb]['Name'];
$query .= ' FROM '.$table_name.' WHERE '.$cur_table['PrimaryField'].' = \''.$id.'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if (!$db2->num_rows($result))	message($lang_item['No item data']."coiji", false, '404 Not Found'); 	// No item data
$cur = $db2->fetch_assoc($result);

$editlink = '';
if ($cur_table['Edit'] || $pun_user['is_admin'])
	$editlink = '<a href="../editor.php'.$link_name.'">'.$lang_item['Edit item'].'</a>';
			//	<a href="itemdelete.php'.$link_name.'">'.$lang_item['Delete item'].'</a>';
$editlink .= ' <a href="itemsearch.php'.$link_name.'">'.$lang_item['Search item'].'</a>';

$titlename = $name;
$page_title = $table_display.' - '.$name;
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'view');
require PUN_ROOT.'header.php';
require PUN_ROOT.'include/parser.php';
?>

<div id="item">
	<?php

echo '<h2>'.$page_title.' '.$editlink.'</h2><br/>';

$lignes = array();
for ($nb = /*($name && !$pun_user['is_admin']) ? 1 : */0; $nb < $field_count; $nb++)
{
	//	Field START | Field name
	$str = '<div class="field"><div class="fieldname"';
	if ($fields[$nb]['Type'] != 'text') // Field Inline
		$str .= ' style="float:left"';
	if (($fields[$nb]['Type'] == 'list') ||($fields[$nb]['Type'] == 'flag')) // Display list/flag onclick
		$str .= ' value="'.$nb.'" onclick="disp_list_or_flag(this)"';
	$str .= '>'.($fields[$nb]['Display'] ? $fields[$nb]['Display'] : $fields[$nb]['Name']).'</div>';
	// Content START
	$str .= '<div class="fieldcontent"';
	if ($fields[$nb]['Type'] != 'text') // Field Inline
		$str .= ' style="float:left"';
	$str .= '>';

	if (!$cur[$fields[$nb]['Name']] && ($fields[$nb]['Type'] == 'varchar' || $fields[$nb]['Type'] == 'text'))
		$str .= ' - ';
	else {
		// Content link START
		if ($fields[$nb]['LinkedTableId'])	$str .= '<a href="view.php?table='.$fields[$nb]['LinkedTableId'].'&id='.$cur[$fields[$nb]['Name']].'">';
		// Content
		if ($fields[$nb]['Type'] == 'text') // Type text
			$str .= parse_message($cur[$fields[$nb]['Name']], 0);
		else if ($fields[$nb]['Type'] == 'list')
		{
			$str .= $cur[$fields[$nb]['Name']];
			$query = 'SELECT Id, Value, Name, Name_loc2 FROM item_lists WHERE Id = '.$fields[$nb]['Id'].' AND Value = '.$cur[$fields[$nb]['Name']];
			$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
			//if (!$db->num_rows($result))	redirect('itemsearch.php?table='.$table_name, '', true); // Lists doesnt exist tofix
			if ($cur_list = $db->fetch_assoc($result))
				$str .= ' - '.$cur_list['Name'];
		}
		else if ($fields[$nb]['Type'] == 'flag')
		{
			$str .= $cur[$fields[$nb]['Name']];
			$query = 'SELECT Id, Value, Name, Name_loc2 FROM item_lists WHERE Id = '.$fields[$nb]['Id'].' AND Value & '.$cur[$fields[$nb]['Name']];
			$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
			//if (!$db->num_rows($result))	redirect('itemsearch.php?table='.$table_name, '', true); // Lists doesnt exist tofix
			while ($cur_list = $db->fetch_assoc($result))
				$str .= ' - '.$cur_list['Name'];
		}
		else	$str .= $cur[$fields[$nb]['Name']];	// Type int
		// Content link END
		if ($fields[$nb]['LinkedTableId'])	$str .= '</a>';
	}
	// Content END | Field END
	$str .= '</div></div>';

	$tab_count = $fields[$nb]['TabId'] + 1;
	$lignes[$fields[$nb]['TabId']][$fields[$nb]['LineId']] .= $str;
}

$interligne = '<div class="interligne"></div>';
if ($lignes[0])	echo implode($lignes[0], $interligne); 	// Out of tabs
if ($lignes[1]) { 										// If there is tabs
	echo '<div id="tabs" style="float:left"><ul class="tabbuttonul">'; // Tab menu
	for ($tab = 1; $tab < $tab_count; $tab++)
		echo '<li style="display:inline" class="tabbuttonli"><a href="#tabs-'.$tab.'">'.$cur_table['Tab'.$tab].'</a></li>';
	echo '</ul>';
	for ($tab = 1; $tab < $tab_count; $tab++)			// Display tabs
		if ($lignes[$tab]) echo '<div id="tabs-'.$tab.'">'.implode($lignes[$tab], $interligne).'</div>'; 
	echo '</div>'; }

?>

<?php

$footer_style = 'item';
require PUN_ROOT.'footer.php';
