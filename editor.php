<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/lang/'.$pun_user['language'].'/item.php';

$loc = $pun_user['fr'] ? '_loc2' : '';
$nloc = $pun_user['fr'] ? '' : '_loc2';
// $action 	= isset($_GET['action']) ? $_GET['action'] : null; UNUSED
$table_name = isset($_GET['table'])	? $_GET['table'] 	: null;
$name 		= isset($_GET['name']) 	? $_GET['name'] 	: null;
$id = isset($_GET['id']) ? (intval($_GET['id']) > 0 ? intval($_GET['id']) : 0) : 0;

if ($name && $id)	$name = null; // $name and $id cant match
if (!$table_name || (!$name && !$id))	message($lang_item['Bad link'], false, '404 Not Found'); 	// Bad link

// Get Table Info
$query = 'SELECT Id, DbName, Name, Display'.$loc.' as Display, Display'.$nloc.' as OtherDisplay, Name'.$loc.'Field as NameField, Name'.$nloc.'Field as OtherNameField, ViewLevel, EditLevel, Tab1, Tab2, Tab3, Tab4, Tab5, Tab6, Tab7, Tab8 FROM item_tables WHERE Display'.$loc.' = \''.$db->escape($table_name).'\' OR Display'.$nloc.' = \''.$db->escape($table_name).'\' OR Name = \''.$db->escape($table_name).'\'';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result)) {
	$query = 'SELECT Id, DbName, Name, Display'.$loc.' as Display, Name'.$loc.'Field as NameField, ViewLevel, EditLevel, Tab1, Tab2, Tab3, Tab4, Tab5, Tab6, Tab7, Tab8 FROM item_tables WHERE Id = \''.$db->escape($table_name).'\'';
	$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result)) message($lang_item['No table data'], false, '404 Not Found'); } 	// No table data
$cur_table = $db->fetch_assoc($result);	// Query OK
$table_pseu = ($cur_table['Display'] ? $cur_table['Display'] : $cur_table['Name']);
if 	(!strcmp($table_name, $cur_table['Display']) || !strcmp($table_name, $cur_table['OtherDisplay']) || !strcmp($table_name, $cur_table['Name']))
	$table_name = $cur_table['Name'];	// Check table_name cAsE or id
else	redirect('editor.php?table='.$table_pseu.($name ? ('&name='.$name) : ('&id='.$id)), '', true);
if ($cur_table['EditLevel'] > $pun_user['level'])	message($lang_item['Access denied'], false, '404 Not Found'); // Access denied
$table_display = $cur_table['Display'] ? $cur_table['Display'] : $cur_table['Name'];
$db2 = new DBLayer($db_host, $db_user, $db_pass, $cur_table['DbName'], $db_prefix, $p_connect);

// Get Fields Info
$prikeys[] = array();	$prikey_count = 0;
$fields[] = array();	$field_count = 0;	$fields_name = null;
$query = 'SELECT COLUMN_NAME as Name, COLUMN_KEY as Pri, DATA_TYPE as Type, Display'.$loc.' as Display, Description'.$loc.' as Description,'
		.'CHARACTER_MAXIMUM_LENGTH as MaxLength, NUMERIC_PRECISION as MaxPrecision, Tab, Line, DisplayOrder, TypeFlags, Linked, LinkedSchema, LinkedName'
		.' FROM item_fields WHERE TABLE_SCHEMA = \''.$cur_table['DbName'].'\' AND TABLE_NAME = \''.$cur_table['Name'].'\' ORDER BY Tab, Line, DisplayOrder, DisplayOrder';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_item['No fields data'], false, '404 Not Found'); 			// No fields data
while ($cur_field = $db->fetch_assoc($result)) {
	if ($cur_field['Pri'] == 'PRI') {
		$prikeys[$prikey_count] = $cur_field;	$prikey_count++; }
	if (!strcmp(strtolower($cur_field['Name']), strtolower($prikeys[0]['Name'])))			$primary_field = $field_count;
	if (!strcmp(strtolower($cur_field['Name']), strtolower($cur_table['NameField'])))		$name_field = $field_count;
	$fields[$field_count] = $cur_field;	$field_count++;	$fields_name[] = $cur_field['Name']; }
if ($field_count < 2)	message($lang_item['No fields data'], false, '404 Not Found'); 					// No fields data
if ($prikey_count != 1)	message("Pas ou trop de primary keys.", false, '404 Not Found'); 				// temp
if (!$cur_table['NameField'])	$cur_table['NameField'] = $prikeys[0]['Name'];							// temp

// Get Item Real Name
if ($name && ($cur_table['NameField'] || $cur_table['OtherNameField'])) $query = 'SELECT '.$prikeys[0]['Name'].', '.$cur_table['NameField'].' FROM '.$table_name
	.' WHERE '.($cur_table['NameField'] ? $cur_table['NameField'].' = \''.$db->escape($name).'\'' : '').($cur_table['NameField'] && $cur_table['NameField'] ? ' OR ' : '')
	.($cur_table['OtherNameField'] ? $cur_table['OtherNameField'].' = \''.$db->escape($name).'\'' : '');
else		$query = 'SELECT '.$prikeys[0]['Name'].', '.$cur_table['NameField'].' FROM '.$table_name.' WHERE '.$prikeys[0]['Name'].' = \''.$id.'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if (!$db2->num_rows($result))	message($lang_item['No item data'], false, '404 Not Found'); 			// No item data
$cur_name = $db2->fetch_assoc($result);
if ($name && !strcmp($name, $cur_name[$cur_table['NameField']]))
	$id = $cur_name[$prikeys[0]['Name']];
else	$name = $cur_name[$cur_table['NameField']];
$link_name 	= pun_htmlspecialchars($table_pseu).'-'.pun_htmlspecialchars($name);
if (!$id)	redirect('edit/'.$link_name, '', true);
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

// Generate SQL Query
$cur_fields = array();
for ($field = 0; $field < $field_count; $field++)
	$cur_fields[$field] = '\''.($cur ? $db->escape(pun_linebreaks(pun_trim($cur[$fields[$field]['Name']]))) : '').'\'';
$sql = 'DELETE FROM `'.$cur_table['DbName'].'`.`'.$table_name.'` WHERE `'.$prikeys[0]['Name'].'` = \''.$id.'\';

INSERT INTO `'.$cur_table['DbName'].'`.`'.$table_name.'`';
$sql_first = '

('.implode($fields_name, ', ').')

VALUES ';
$sql .= $sql_first.'('.implode($cur_fields, ', ').');';

$cur_index = 100;
$interligne = '<div class="interligne"></div>';
$item = '<div class="clearl"></div>'.
	'<label id="sql_first" value="'.$sql_first.'"></label>'.
	'<label id="field_count" value="'.$field_count.'"></label>'.
	'<label id="primary_field" index="'.$primary_field.'" value="'.$prikeys[0]['Name'].'"></label>'.
	'<label id="name_field" index="'.$name_field.' value="'.$cur_table['NameField'].'"></label>'.
	'<form id="edit" method="post" style="float:left; width:100%" action="'.PUN_URL.'save.php?table='.$table_name.'&amp;id='.$id.'" onsubmit="return process_form(this)">'.
		'<input type="hidden" name="form_sent" value="1" />'.
		'<input type="hidden" name="link_name" value="'.$link_name.'" />'.
		'<table><tr><td>'.$tooltip.'</td><td>'.
			'<div class="field"><div class="fieldname" style="float:left">'.$lang_item['Db name'].'</div><div class="fieldcontent" style="float:left">'.
			'<input id="req_dbname" type="text" name="req_dbname" onchange="update_sql_query()" value="'.$cur_table['DbName'].'" size="20" maxlength="20" tabindex="'.$cur_index++.'" /></div></div>'.
			'<div class="field"><div class="fieldname" style="float:left">'.$lang_item['Table name'].'</div><div class="fieldcontent" style="float:left">'.
			'<input id="req_tablename" type="text" name="req_tablename" onchange="update_sql_query()" value="'.$table_name.'" size="20" maxlength="20" tabindex="'.$cur_index++.'" /></div></div>'.
			$interligne;

$lignes = array();
for ($nb = 0; $nb < $field_count; $nb++)
{
	$is_text = (($fields[$nb]['Type'] == 'text') || ($fields[$nb]['TypeFlags'] == 3)) ? 1 : 0;

	if (($fields[$nb]['Type'] == 'char') || ($fields[$nb]['Type'] == 'varchar') || ($fields[$nb]['Type'] == 'text')
		|| ($fields[$nb]['Type'] == 'longtext') || ($fields[$nb]['Type'] == 'blob'))
		$lmi = $fields[$nb]['MaxLength'];
	else if (($fields[$nb]['Type'] == 'tinyint') || ($fields[$nb]['Type'] == 'smallint') || ($fields[$nb]['Type'] == 'mediumint')
		|| ($fields[$nb]['Type'] == 'int') || ($fields[$nb]['Type'] == 'bigint') || ($fields[$nb]['Type'] == 'float'))
		$lmi = $fields[$nb]['MaxPrecision'];
	else if ($fields[$nb]['Type'] != 'timestamp')	message("Unknown type", false, '404 Not Found');

	$lvi = ($lmi > 255 ? 255 : $lmi);	$hvi = ($is_text ? 3 : 0);
	if (($fields[$nb]['TypeFlags'] == 1) || ($fields[$nb]['TypeFlags'] == 2))	$lvi = 8;	// List/Flag

	//	Field START | Field name
	$str = '<div class="field"><div class="fieldname"';
	if (!$is_text) // Field Inline
		$str .= ' style="float:left"';
	if (($fields[$nb]['Type'] == 'list') ||($fields[$nb]['Type'] == 'flag')) // Display list/flag onclick
		$str .= ' value="'.$nb.'" onclick="disp_list_or_flag(this)"';
	$str .= '>'.($fields[$nb]['Display'] ? $fields[$nb]['Display'] : $fields[$nb]['Name']).'</div>';
	// Content START
	$str .= '<div class="fieldcontent"';
	if (!$is_text) // Field Inline
		$str .= ' style="float:left"';
	$str .= '>';

	if ($fields[$nb]['TypeFlags'] == 2) {											// TYPE Flags
		$query = 'SELECT Id, Value, Name, Name_loc2 FROM item_lists WHERE Id = '.$fields[$nb]['Id'];
		$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))	message($lang_item['No list data'], false, '404 Not Found'); // No list data
		$str .= '<input id="req_'.$nb.'" name="req_'.$nb.'" type="text" value="'.(($cur) ? $cur[$fields[$nb]['Name']] : ((!strcmp($fields[$nb]['Name'], $db_tpl[$db_tpl['id']])) ? $id : '')).'" '.
					'size="'.$lvi.'" maxlength="'.$lmi.'" tabindex="'.$cur_index++.'" />';
		$content = '<div id="listflag'.$nb.'" hidden="true"><br/>';
		$cur_flag = ($cur) ? $cur[$fields[$nb]['Name']] : 0;
		$flag_count = 0;
		$flag_disp = array();
		while ($cur_list = $db->fetch_assoc($result)) {
			if (($cur_flag & $cur_list['Value']) == $cur_list['Value']) {
				$flag_disp[] = $cur_list['Name'];
				$flag_checked = 'checked'; }
			else $flag_checked = '';
			$content .= '<input type="checkbox" id="lf'.$fields[$nb]['Id'].'b'.$flag_count.'" '.$flag_checked.' name="lf'.$fields[$nb]['Id'].'" fieldid="req_'.$nb.'" flagid="'.$fields[$nb]['Id'].'" '.
							'onclick="flag_check(this)" value="'.$cur_list['Value'].'" listname="'.$cur_list['Name'].'" />'.$cur_list['Name'].'<br/>';
			$flag_count++; }
		$content .= '</div>';
		$str .= ' <label id="req_'.$nb.'disp">'.implode($flag_disp, ' - ').'</label>';
		$str .= $content;
		$str .= '<label id="req_'.$nb.'flagcount" value="'.$flag_count.'"></label>'; }
	else if ($fields[$nb]['TypeFlags'] == 1) {										// TYPE List
		$query = 'SELECT Id, Value, Name, Name_loc2 FROM item_lists WHERE Id = '.$fields[$nb]['Id'];
		$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))	message($lang_item['No list data'], false, '404 Not Found'); // No list data
		$str .= '<input id="req_'.$nb.'" name="req_'.$nb.'" type="text" value="'.(($cur) ? $cur[$fields[$nb]['Name']] : ((!strcmp($fields[$nb]['Name'], $db_tpl[$db_tpl['id']])) ? $id : '')).'"'
					.' size="'.$lvi.'" maxlength="'.$lmi.'" tabindex="'.$cur_index++.'" />';
		$content = '<div id="listflag'.$nb.'" hidden="true"><br/>';
		$cur_flag = ($cur) ? $cur[$fields[$nb]['Name']] : 0;
		$flag_count = 0;
		while ($cur_list = $db->fetch_assoc($result)) {
			if ($cur_list['Value'] == $cur_flag) {
				$str .= '<label id="req_'.$nb.'disp">'.$cur_list['Name'].'</label>';
				$flag_checked = 'checked'; }
			else $flag_checked = '';
			$content .= '<input type="checkbox" id="lf'.$fields[$nb]['Id'].'b'.$flag_count.'" '.$flag_checked.' name="lf'.$fields[$nb]['Id'].'" fieldid="req_'.$nb.'" flagid="'.$fields[$nb]['Id'].'"'
							.' onclick="list_check(this)" value="'.$cur_list['Value'].'" listname="'.$cur_list['Name'].'" />'.$cur_list['Name'].'<br/>';
			$flag_count++; }
		$content .= '</div>';
		$str .= $content;
		$str .= '<label id="req_'.$nb.'flagcount" value="'.$flag_count.'"></label>'; }
	else if ($is_text) { 															// TYPE Text
		$str .= '<textarea id="req_'.$nb.'" name="req_'.$nb.'" onchange="update_sql_query()" rows="'.$hvi.'" cols="'.$lvi.'" maxlength="'.$lmi.'" tabindex="'.$cur_index++.'">'
					.(($cur) ? ($cur[$fields[$nb]['Name']] ? $cur[$fields[$nb]['Name']] : '') : '').'</textarea>'; }
	else {
		$str .= '<input id="req_'.$nb.'" type="text" name="req_'.$nb.'" onchange="update_sql_query()" size="'.$lvi.'" maxlength="'.$lmi.'" tabindex="'.$cur_index++.'" '.
			'value="'.($cur ? ($cur[$fields[$nb]['Name']] ? $cur[$fields[$nb]['Name']] : ($fields[$nb]['Type'] == 'int' ? '0' : '')) : ($fields[$nb]['Type'] == 'int' ? '0' : '')).'"></input>'; }
	// Content END | Field END
	$str .= '</div></div>';
	if ($nb == 0)
		$str .= '<input type="submit" name="refresh" style="float:left;" class="itembutton" value="'.$lang_item['Refresh item'].'" tabindex="'.$cur_index++.'" accesskey="r" />';

	$tab_count = $fields[$nb]['Tab'] + 1;
	$lignes[$fields[$nb]['Tab']][$fields[$nb]['Line']] .= $str;
}

$cur_index = 60;
if ($lignes[0])	$item .= implode($lignes[0], $interligne); 	// Out of tabs
$item .= '</td></tr></table><div id="tabs" style="float:left; width:100%">';
if ($lignes[1]) { 											// If there is tabs
	$item .= '<ul class="tabbuttonul">'; 					// Tab menu
	for ($tab = 1; $tab < $tab_count; $tab++)
		$item .= '<li class="tabbuttonli" style="display:inline"><a class="tabbutton" href="#tabs-'.$tab.'" tabindex="'.$cur_index++.'" onclick="update_sql_query()">'.($cur_table['Tab'.$tab] ? $cur_table['Tab'.$tab] : $lang_item['Tab'].' '.$tab).'</a></li>';
	$item .= '<li class="tabbuttonli" style="display:inline"><a class="tabbutton" href="#tabs-'.$tab_count.'" tabindex="'.$cur_index++.'" onclick="update_sql_query()">'.$lang_item['SQL'].'</a></li>';
	$item .= '</ul>'.$interligne;
	for ($tab = 1; $tab < $tab_count; $tab++)				// Display tabs
		if ($lignes[$tab]) $item .= '<div id="tabs-'.$tab.'">'.implode($lignes[$tab], $interligne).'</div>';
}
$cur_index = 400;											// SQL TextArea
$item .= '<div id="tabs-'.$tab_count.'"><div class="field"><div class="fieldname">'.$lang_item['SQL'].'</div><div class="fieldcontent">'.
				'<textarea id="req_sql" name="req_sql" cols="135" rows="16" tabindex="'.$cur_index++.'">'.$sql.'</textarea></div></div>'.
		'</div><br/></div>'.								// Submit Links
		'<input type="submit" name="submit2edit" style="float:left; width:300px; margin-right: 24px" class="itembutton" value="'.$lang_item['Save item'].'" tabindex="'.$cur_index++.'" accesskey="s" />'.
		 '<input type="submit" name="submit" style="float:left; width:300px; margin-right: 24px" class="itembutton" value="'.$lang_item['Save item + Back'].'" tabindex="'.$cur_index++.'" accesskey="c" />'.
		 '<a class="itembutton" style="float:left; width:300px; text-align:center; padding:8px"  href="../view.php?table='.$table_name.'&id='.$id.'" tabindex="'.$cur_index++.'" accesskey="b">'.$lang_item['Back'].'</a>'.
	'</form>'; 

// Edit Links
$editlink = ' <a href="'.PUN_URL.'db/'.$link_name.'">'.$lang_item['Back'].'</a>';
//if ($pun_user['is_admin'])
//	$editlink = '<a href="itemedit.php'.$link_name.'">'.$lang_item['Edit item'].'</a>
//				<a onclick="update_sql_query_delete()">'.$lang_item['Delete item'].'</a>';
$editlink .= ' <a href="'.PUN_ULR.'search/'.$link_name.'">'.$lang_item['Search item'].'</a>';

$titlename = $name;
$page_title = $table_display.' - '.$name;
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'editor');
require PUN_ROOT.'header.php';
require PUN_ROOT.'include/parser.php';
?>

<div id="editor">
	<?php

echo '<h2>'.$page_title.' '.$editlink.'</h2><br/>';
echo $item;

$footer_style = 'itemedit';
require PUN_ROOT.'footer.php';
