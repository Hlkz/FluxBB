<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/item.php';

// $action 	= isset($_GET['action']) ? $_GET['action'] : null; UNUSED
$table_name = isset($_GET['table'])	? $_GET['table'] 	: null;
$name 		= isset($_GET['name']) 	? $_GET['name'] 	: null;
$id = isset($_GET['id']) ? (intval($_GET['id']) > 0 ? intval($_GET['id']) : 0) : 0;

if ($name && $id)	$name = null; // $name and $id cant match
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
$fields[] = array();	$fields_name[] = array();
$field_count = 0; $primary_field = 'id'; $name_field = 'name';
$query = 'SELECT Id, Fields, TabId, LineId, Name, Type, Display, LinkedTableId, EditorTextLength, EditorTextHeight FROM item_fields WHERE Fields = '.$cur_table['Fields'].' ORDER BY TabId, LineId, Id';
$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
if (!$db->num_rows($result))	message($lang_item['No fields data'], false, '404 Not Found'); 		// No fields data
while ($cur_field = $db->fetch_assoc($result)) {
	$fields[$field_count] = $cur_field;
	$fields_name[$field_count] = '`'.$cur_field['Name'].'`';
	if (!strcmp(strtolower($cur_field['Name']), strtolower($cur_table['PrimaryField'])))	$primary_field = $field_count;
	if (!strcmp(strtolower($cur_field['Name']), strtolower($cur_table['NameField'])))		$name_field = $field_count;
	$field_count++; }
if ($field_count < 2)	message($lang_item['No fields data'], false, '404 Not Found'); 				// No fields data

// Get Item Info
$query = 'SELECT '.$fields[0]['Name'];
for ($nb = 1; $nb < $field_count; $nb++)
	$query .= ', '.$fields[$nb]['Name'];
$query .= ' FROM '.$table_name.' WHERE '.$cur_table['PrimaryField'].' = \''.$id.'\'';
$result = $db2->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db2->error());
if (!$db2->num_rows($result))	message($lang_item['No item data'], false, '404 Not Found'); 		// No item data
$cur = $db2->fetch_assoc($result);

// Generate SQL Query
$cur_fields = array();
for ($field = 0; $field < $field_count; $field++)
	$cur_fields[$field] = '\''.$db->escape(pun_linebreaks(pun_trim($cur[$fields[$field]['Name']]))).'\'';
$sql = 'DELETE FROM `'.$cur_table['DbName'].'`.`'.$table_name.'` WHERE `'.$cur_table['PrimaryField'].'` = \''.$id.'\';

INSERT INTO `'.$cur_table['DbName'].'`.`'.$table_name.'`';
$sql_first = '

('.implode($fields_name, ', ').')

VALUES ';
$sql .= $sql_first.'('.implode($cur_fields, ', ').');';

$cur_index = 100;
$interligne = '<div class="interligne"></div>';
$item = '<label id="sql_first" value="'.$sql_first.'"></label>'.
	'<label id="field_count" value="'.$field_count.'"></label>'.
	'<label id="primary_field" index="'.$primary_field.'" value="'.$cur_table['PrimaryField'].'"></label>'.
	'<label id="name_field" index="'.$name_field.' value="'.$cur_table['NameField'].'"></label>'.
	'<form id="edit" method="post" style="float:left; width:100%" action="itemsave.php?table='.$table_name.'&amp;id='.$id.'" onsubmit="return process_form(this)">'.
		'<input type="hidden" name="form_sent" value="1" />'.
		'<input type="hidden" name="link_name" value="'.$link_name.'" />'.
		'<div class="field"><div class="fieldname" style="float:left">'.$lang_item['Db name'].'</div><div class="fieldcontent" style="float:left">'.
		'<input id="req_dbname" type="text" name="req_dbname" onchange="update_sql_query()" value="'.$cur_table['DbName'].'" size="20" maxlength="20" tabindex="'.$cur_index++.'" /></div></div>'.
		'<div class="field"><div class="fieldname" style="float:left">'.$lang_item['Table name'].'</div><div class="fieldcontent" style="float:left">'.
		'<input id="req_tablename" type="text" name="req_tablename" onchange="update_sql_query()" value="'.$table_name.'" size="20" maxlength="20" tabindex="'.$cur_index++.'" /></div></div>'.
		$interligne;

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

	switch ($fields[$nb]['Type']) {
		case 'text':
			$lvi = $fields[$nb]['EditorTextLength'] ? $fields[$nb]['EditorTextLength'] : 60;
			$hvi = $fields[$nb]['EditorTextHeight'] ? $fields[$nb]['EditorTextHeight'] : 10;
			$str .= '<textarea id="req_'.$nb.'" name="req_'.$nb.'" onchange="update_sql_query()" rows="'.$hvi.'" cols="'.$lvi.'" tabindex="'.$cur_index++.'">'.(($cur) ? $cur[$fields[$nb]['Name']] : '').'</textarea>';
			break;
		case 'list':
			$lvi = $fields[$nb]['EditorTextLength'] ? $fields[$nb]['EditorTextLength'] : 8;
			$query = 'SELECT Id, Value, Name, Name_loc2 FROM item_lists WHERE Id = '.$fields[$nb]['Id'];
			$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result))	message($lang_item['No list data'], false, '404 Not Found'); // No list data
			$str .= '<input id="req_'.$nb.'" name="req_'.$nb.'" type="text" value="'.(($cur) ? $cur[$fields[$nb]['Name']] : ((!strcmp($fields[$nb]['Name'], $db_tpl[$db_tpl['id']])) ? $id : '')).'" size="'.$lvi.'" maxlength="'.$lvi.'" tabindex="'.$cur_index++.'" />';
			$content = '<div id="listflag'.$nb.'" hidden="true"><br/>';
			$cur_flag = ($cur) ? $cur[$fields[$nb]['Name']] : 0;
			$flag_count = 0;
			while ($cur_list = $db->fetch_assoc($result)) {
				if ($cur_list['Value'] == $cur_flag) {
					$str .= '<label id="req_'.$nb.'disp">'.$cur_list['Name'].'</label>';
					$flag_checked = 'checked'; }
				else $flag_checked = '';
				$content .= '<input type="checkbox" id="lf'.$fields[$nb]['Id'].'b'.$flag_count.'" '.$flag_checked.' name="lf'.$fields[$nb]['Id'].'" fieldid="req_'.$nb.'" flagid="'.$fields[$nb]['Id'].'" onclick="list_check(this)" value="'.$cur_list['Value'].'" listname="'.$cur_list['Name'].'" />'.$cur_list['Name'].'<br/>';
				$flag_count++; }
			$content .= '</div>';
			$str .= $content;
			$str .= '<label id="req_'.$nb.'flagcount" value="'.$flag_count.'"></label>';
			break;
		case 'flag':
			$lvi = $fields[$nb]['EditorTextLength'] ? $fields[$nb]['EditorTextLength'] : 8;
			$query = 'SELECT Id, Value, Name, Name_loc2 FROM item_lists WHERE Id = '.$fields[$nb]['Id'];
			$result = $db->query($query) or error($lang_item['DB Error'], __FILE__, __LINE__, $db->error());
			if (!$db->num_rows($result))	message($lang_item['No list data'], false, '404 Not Found'); // No list data
			$str .= '<input id="req_'.$nb.'" name="req_'.$nb.'" type="text" value="'.(($cur) ? $cur[$fields[$nb]['Name']] : ((!strcmp($fields[$nb]['Name'], $db_tpl[$db_tpl['id']])) ? $id : '')).'" size="'.$lvi.'" maxlength="'.$lvi.'" tabindex="'.$cur_index++.'" />';
			$content = '<div id="listflag'.$nb.'" hidden="true"><br/>';
			$cur_flag = ($cur) ? $cur[$fields[$nb]['Name']] : 0;
			$flag_count = 0;
			$flag_disp = array();
			while ($cur_list = $db->fetch_assoc($result)) {
				if (($cur_flag & $cur_list['Value']) == $cur_list['Value']) {
					$flag_disp[] = $cur_list['Name'];
					$flag_checked = 'checked'; }
				else $flag_checked = '';
				$content .= '<input type="checkbox" id="lf'.$fields[$nb]['Id'].'b'.$flag_count.'" '.$flag_checked.' name="lf'.$fields[$nb]['Id'].'" fieldid="req_'.$nb.'" flagid="'.$fields[$nb]['Id'].'" onclick="flag_check(this)" value="'.$cur_list['Value'].'" listname="'.$cur_list['Name'].'" />'.$cur_list['Name'].'<br/>';
				$flag_count++; }
			$content .= '</div>';
			$str .= ' <label id="req_'.$nb.'disp">'.implode($flag_disp, ' - ').'</label>';
			$str .= $content;
			$str .= '<label id="req_'.$nb.'flagcount" value="'.$flag_count.'"></label>';
			break;
		case 'int':
		case 'varchar':
		default:
			$lvi = $fields[$nb]['EditorTextLength'] ? $fields[$nb]['EditorTextLength'] : 30;
			$str .= '<input id="req_'.$nb.'" type="text" name="req_'.$nb.'" onchange="update_sql_query()" value="'.(($cur) ? $cur[$fields[$nb]['Name']] : ((!strcmp($fields[$nb]['Name'], $db_tpl[$db_tpl['id']])) ? $id : '')).'" size="'.$lvi.'" maxlength="'.$lvi.'" tabindex="'.$cur_index++.'"></input>';
			break;
	}
	// Content END | Field END
	$str .= '</div></div>';
	if ($nb == 0)
		$str .= '<input type="submit" name="refresh" style="float:left;" class="itembutton" value="'.$lang_item['Refresh item'].'" tabindex="'.$cur_index++.'" accesskey="r" />';

	$tab_count = $fields[$nb]['TabId'] + 1;
	$lignes[$fields[$nb]['TabId']][$fields[$nb]['LineId']] .= $str;
}

$cur_index = 60;
if ($lignes[0])	$item .= implode($lignes[0], $interligne); 	// Out of tabs
$item .= '<div id="tabs" style="float:left; width:100%">';
if ($lignes[1]) { 											// If there is tabs
	$item .= '<ul class="tabbuttonul">'; 					// Tab menu
	for ($tab = 1; $tab < $tab_count; $tab++)
		$item .= '<li class="tabbuttonli" style="display:inline"><a class="tabbutton" href="#tabs-'.$tab.'" tabindex="'.$cur_index++.'" onclick="update_sql_query()">'.$cur_table['Tab'.$tab].'</a></li>';
	$item .= '<li class="tabbuttonli" style="display:inline"><a class="tabbutton" href="#tabs-'.$tab_count.'" tabindex="'.$cur_index++.'" onclick="update_sql_query()">'.$lang_item['SQL'].'</a></li>';
	$item .= '</ul>'.$interligne;
	for ($tab = 1; $tab < $tab_count; $tab++)				// Display tabs
		if ($lignes[$tab]) $item .= '<div id="tabs-'.$tab.'">'.implode($lignes[$tab], $interligne).'</div>';
}
$cur_index = 400;											// SQL TextArea
$item .= '<div id="tabs-'.$tab_count.'"><div class="field"><div class="fieldname">'.$lang_item['SQL'].'</div><div class="fieldcontent">'.
				'<textarea id="req_sql" name="req_sql" cols="160" rows="16" tabindex="'.$cur_index++.'">'.$sql.'</textarea></div></div>'.
		'</div><br/></div>'.								// Submit Links
		'<input type="submit" name="submit2edit" style="float:left; width:300px; margin-right: 24px" class="itembutton" value="'.$lang_item['Save item'].'" tabindex="'.$cur_index++.'" accesskey="s" />'.
		 '<input type="submit" name="submit" style="float:left; width:300px; margin-right: 24px" class="itembutton" value="'.$lang_item['Save item + Back'].'" tabindex="'.$cur_index++.'" accesskey="c" />'.
		 '<a class="itembutton" style="float:left; width:300px; text-align:center; padding:8px"  href="../view.php?table='.$table_name.'&id='.$id.'" tabindex="'.$cur_index++.'" accesskey="b">'.$lang_item['Back'].'</a>'.
	'</form>'; 

// Edit Links
$editlink = '';
if ($pun_user['is_admin'])
	$editlink = '<a href="itemedit.php'.$link_name.'">'.$lang_item['Edit item'].'</a>
				<a href="#tabs-'.$tab_count.'" onclick="update_sql_query_delete()">'.$lang_item['Delete item'].'</a>';
$editlink .= ' <a href="itemsearch.php'.$link_name.'">'.$lang_item['Search item'].'</a>';

$titlename = $name;
$page_title = $table_display.' - '.$name;
define('PUN_ALLOW_INDEX', 1);
define('PUN_ACTIVE_PAGE', 'editor');
require PUN_ROOT.'header.php';
require PUN_ROOT.'include/parser.php';
?>

<div id="item">
	<?php

echo '<h2>'.$page_title.' '.$editlink.'</h2><br/>';
echo $item;

$footer_style = 'itemedit';
require PUN_ROOT.'footer.php';
