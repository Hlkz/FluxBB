<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/lang/'.$pun_user['language'].'/prof_reg.php';

if(isset($_POST['check_account']))
{
    $check_account = $_POST['check_account'];
	$err = false; $msg = '';

	if ((pun_strlen($check_account) < 2) || (pun_strlen($check_account) > 12)) {
		$err = true;
		$msg = $lang_prof_reg['Account bad length']; }
	else if (!preg_match('/^[a-zA-ZàÀäßçÇÄéÉèÈëËöÖùÙüÜñÑ].*$/', $check_account)) {
		$err = true;
		$msg = $lang_prof_reg['Account bad start']; }
	else if (!preg_match('/^[a-zA-Z0-9àÀäßçÇÄéÉèÈëËöÖùÙüÜñÑ]*$/', $check_account)) {
		$err = true;
		$msg = $lang_prof_reg['Account bad chars']; }
	else {
		$result = $dba->query('SELECT `id` FROM '.$dba->prefix.'`account` WHERE `account`="'.$check_account.'"') or error('Unable to fetch user info', __FILE__, __LINE__, $dba->error());
		if ($dba->result($result)) {
			$err = true;
			$msg = $lang_prof_reg['Account taken']; }
		else $msg = $lang_prof_reg['Account available']; }

	$attr = $err ? 'true' : 'false';
	$color = $err ? 'red' : 'green';
	$color2 = $err ? '#fdd' : '#dfd';
	echo '<div id="req_account_err" error="'.$attr.'" style="padding:0px 3px 0px 3px; border-style:solid; border-width:1px; color:'.$color.'; border-color:'.$color.'; background:'.$color2.';">'.$msg.'</div>';
}
else if(isset($_POST['username']))
{
    $username = $_POST['username'];
	$err = false; $msg = '';

	if ((pun_strlen($username) < 2) || (pun_strlen($username) > 12)) {
		$err = true;
		$msg = $lang_prof_reg['Username bad length']; }
	else if (!preg_match('/^[a-zA-ZàÀäßçÇÄéÉèÈëËöÖùÙüÜñÑ].*$/', $username)) {
		$err = true;
		$msg = $lang_prof_reg['Username bad start']; }
	else if (!preg_match('/^[a-zA-Z0-9àÀäßçÇÄéÉèÈëËöÖùÙüÜñÑ]*$/', $username)) {
		$err = true;
		$msg = $lang_prof_reg['Username bad chars']; }
	else {
		$result = $dba->query('SELECT `id` FROM '.$dba->prefix.'`account` WHERE `username`="'.$username.'"') or error('Unable to fetch user info', __FILE__, __LINE__, $dba->error());
		if ($dba->result($result)) {
			$err = true;
			$msg = $lang_prof_reg['Username taken']; }
		else $msg = $lang_prof_reg['Username available']; }
	
	$attr = $err ? 'true' : 'false';
	$color = $err ? 'red' : 'green';
	$color2 = $err ? '#fdd' : '#dfd';
	echo '<div id="req_username_err" error="'.$attr.'" style="padding:0px 3px 0px 3px; border-style:solid; border-width:1px; color:'.$color.'; border-color:'.$color.'; background:'.$color2.';">'.$msg.'</div>';
}
else if(isset($_POST['password1']))
{
    $password1 = $_POST['password1'];
	$err = false; $msg = '';

	if (pun_strlen($password1) < 4) {
		$err = true;
		$msg = $lang_prof_reg['Pass too short']; }
	else $msg = $lang_prof_reg['Pass valid'];

	$attr = $err ? 'true' : 'false';
	$color = $err ? 'red' : 'green';
	$color2 = $err ? '#fdd' : '#dfd';
	echo '<div id="req_password1_err" error="'.$attr.'" style="padding:0px 3px 0px 3px; border-style:solid; border-width:1px; color:'.$color.'; border-color:'.$color.'; background:'.$color2.';">'.$msg.'</div>';
}
else if(isset($_POST['password2']))
{
	$err = !$_POST['password2']; $msg = $err ? $lang_prof_reg['Pass not match'] : $lang_prof_reg['Pass match'];

	$attr = $err ? 'true' : 'false';
	$color = $err ? 'red' : 'green';
	$color2 = $err ? '#fdd' : '#dfd';
	echo '<div id="req_password2_err" error="'.$attr.'" style="padding:0px 3px 0px 3px; border-style:solid; border-width:1px; color:'.$color.'; border-color:'.$color.'; background:'.$color2.';">'.$msg.'</div>';
}
else if(isset($_POST['email']))
{
	$email = $_POST['email'];
	$err = false; $msg = '';
	
	if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $email)) {
		$err = true;
		$msg = $lang_prof_reg['Email wrong']; }
	else $msg = $lang_prof_reg['Email valid'];
	
	$attr = $err ? 'true' : 'false';
	$color = $err ? 'red' : 'green';
	$color2 = $err ? '#fdd' : '#dfd';
	echo '<div id="req_email_err" error="'.$attr.'" style="padding:0px 3px 0px 3px; border-style:solid; border-width:1px; color:'.$color.'; border-color:'.$color.'; background:'.$color2.';">'.$msg.'</div>';
}

?>