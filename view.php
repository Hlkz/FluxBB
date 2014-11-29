<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/lang/'.$pun_user['language'].'/item.php';

$loc = $pun_user['fr'] ? '_loc2' : '';
$nloc = $pun_user['fr'] ? '' : '_loc2';
$table_name = isset($_GET['table'])	? $_GET['table'] 	: null;
$name 		= isset($_GET['name']) 	? $_GET['name'] 	: null;
$id = isset($_GET['id']) ? (intval($_GET['id']) > 0 ? intval($_GET['id']) : 0) : 0;

if ($name && $id)	$name = null; // $name and $id can not match
if (!$table_name || (!$name && !$id))	message($lang_item['Bad link'], false, '404 Not Found'); 	// Bad link

// Get Table Info
$query = 'SELECT Id, DbName, Name, Display'.$loc.' as Display, Display'.$nloc.' as OtherDisplay, Name'.$loc.'Field as NameField, ViewLevel, EditLevel, Tab1, Tab2, Tab3, Tab4, Tab5, Tab6, Tab7, Tab8 FROM item_tables WHERE Display'.$loc.' = \''.$db->escape($table_name).'\' OR Display'.$nloc.' = \''.$db->escape($table_name).'\' OR Name = \''.$db->escape($table_name).'\'';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	$query = 'SELECT Id, DbName, Name, Display'.$loc.' as Display, Name'.$loc.'Field as NameField, ViewLevel, EditLevel, Tab1, Tab2, Tab3, Tab4, Tab5, Tab6, Tab7, Tab8 FROM item_tables WHERE Id = \''.$db->escape($table_name).'\'';
	$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) message($lang_item['No table data'], false, '404 Not Found'); } 	// No table data
$cur_table = $db->fetch_assoc($result);	// Query OK
$table_pseu = ($cur_table['Display'] ? $cur_table['Display'] : $cur_table['Name']);
if 	(!strcmp($table_name, $cur_table['Display']) || !strcmp($table_name, $cur_table['OtherDisplay']) || !strcmp($table_name, $cur_table['Name']))
	$table_name = $cur_table['Name'];	// Check table_name cAsE or id
else	redirect('view.php?table='.$table_pseu.($name ? ('&name='.$name) : ('&id='.$id)), '', true);
if ($pun_user['level'] > $pun_user['level'])	message($lang_item['Access denied'], false, '404 Not Found'); // Access denied
$table_display = $cur_table['Display'] ? $cur_table['Display'] : $cur_table['Name'];
$db2 = new DBLayer($db_host, $db_user, $db_pass, $cur_table['DbName'], $db_prefix, $p_connect);

// Get Fields Info
$prikeys[] = array();	$prikey_count = 0;
$fields[] = array();	$field_count = 0;
$query = 'SELECT COLUMN_NAME as Name, COLUMN_KEY as Pri, DATA_TYPE as Type, Display'.$loc.' as Display, Description'.$loc.' as Description, Tab, Line, DisplayOrder, TypeFlags, Linked, LinkedSchema, LinkedName'
		.' FROM item_fields WHERE TABLE_SCHEMA = \''.$cur_table['DbName'].'\' AND TABLE_NAME = \''.$cur_table['Name'].'\' ORDER BY Tab, Line, DisplayOrder, DisplayOrder';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_item['No fields data'], false, '404 Not Found'); 			// No fields data
while ($cur_field = $db->fetch_assoc($result)) {
	if ($cur_field['Pri'] == 'PRI') {
		$prikeys[$prikey_count] = $cur_field;	$prikey_count++; }
	$fields[$field_count] = $cur_field;	$field_count++; }
if ($field_count < 2)	message($lang_item['No fields data'], false, '404 Not Found'); 					// No fields data
if ($prikey_count != 1)	message("Pas ou trop de primary keys.", false, '404 Not Found'); 				// temp
if (!$cur_table['NameField'])	$cur_table['NameField'] = $prikeys[0]['Name'];							// temp

// Get Item Real Name
if ($name)	$query = 'SELECT '.$prikeys[0]['Name'].', '.$cur_table['NameField'].' FROM '.$table_name.' WHERE '.$cur_table['NameField'].'=\''.$db->escape($name).'\'';
else		$query = 'SELECT '.$prikeys[0]['Name'].', '.$cur_table['NameField'].' FROM '.$table_name.' WHERE '.$prikeys[0]['Name'].'=\''.$id.'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if (!$db2->num_rows($result))	message($lang_item['No item data'], false, '404 Not Found'); 			// No item data
$cur_name = $db2->fetch_assoc($result);
if ($name && !strcmp($name, $cur_name[$cur_table['NameField']]))
	$id = $cur_name[$prikeys[0]['Name']];
else
	$name = $cur_name[$cur_table['NameField']];
$link_name 	= pun_htmlspecialchars($table_pseu).'-'.pun_htmlspecialchars($name);
if (!$id)	redirect('db/'.$link_name, '', true);
$link_id 	= pun_htmlspecialchars($table_pseu).'='.$id;
$query = 'SELECT COUNT(*) FROM '.$table_name.' WHERE '.$cur_table['NameField'].'=\''.$db->escape($name).'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if ($db2->result($result) != 1) $link_name = pun_htmlspecialchars($table_pseu).'='.$id.'/'.pun_htmlspecialchars($name);

// Get Item Info
$query = 'SELECT '.$fields[0]['Name'];
for ($nb = 1; $nb < $field_count; $nb++)
	$query .= ', '.$fields[$nb]['Name'];
$query .= ' FROM '.$table_name.' WHERE '.$prikeys[0]['Name'].' = \''.$id.'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if (!$db2->num_rows($result))	message($lang_item['No item data'], false, '404 Not Found'); 	// No item data
$cur = $db2->fetch_assoc($result);

// Get ToolTip Info
$tooltip = '';
$query = 'SELECT Name'.$loc.'Field as NameField, IconField, IconType, Description'.$loc.'Field as DescriptionField, Field0, Field1, Field2, Field3, Field4 FROM item_tooltip'
		.' WHERE DbName = \''.$cur_table['DbName'].'\' AND TableName = \''.$db->escape($table_name).'\'';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if ($db->num_rows($result)) {	// There is a ToolTip for this Table
	$cur_tooltip = $db->fetch_assoc($result);
	$tooltip .= '<div class="itemtooltip" '.($cur_tooltip['IconField'] ?
					'style="background: url(\''.PUN_URL.'img/icons/'.get_icon_name($cur[$cur_tooltip['IconField']], $cur_tooltip['IconType']).'.png\');
							background-repeat: no-repeat; min-height: 70px;">' : '>');
	$tooltip .= '<table class="itemtooltip-content"><tr><td>';
	if ($cur_tooltip['NameField'])
		$tooltip .= '<div class="itemtooltip-name">'.$cur[$cur_tooltip['NameField']].'</div>';
	$tooltip .= '</td><th style="background-position: top right"></th></tr>'
				.'<tr><th style="background-position: bottom left"></th><th style="background-position: bottom right"></th></tr></table></div>';
}

$editlink = '';
if ($cur_table['Edit'] || $pun_user['is_admin'])
	$editlink = '<a href="'.PUN_URL.'edit/'.$link_name.'">'.$lang_item['Edit item'].'</a>';
			//	<a href="itemdelete.php'.$link_name.'">'.$lang_item['Delete item'].'</a>';
$editlink .= ' <a href="'.PUN_URL.'search/'.$link_name.'">'.$lang_item['Search item'].'</a>';

$titlename = $name;
$page_title = $table_display.' - '.$name;
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'view');
require PUN_ROOT.'header.php';
require PUN_ROOT.'include/parser.php';
?>

<div id="item">
	<?php

echo '<h2>'.$page_title.' '.$editlink.'</h2><br/>'.
		$tooltip.'<div class="clearl"></div>';

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

	$tab_count = $fields[$nb]['Tab'] + 1;
	$lignes[$fields[$nb]['Tab']][$fields[$nb]['Line']] .= $str;
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
