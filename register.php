<?php
define('PUN_ROOT', dirname(__FILE__).'/');
define('PUN_URL', dirname('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'/');
require PUN_ROOT.'include/common.php';

if ($pun_config['o_regs_allow'] == '0')	message($lang_register['No new regs']);

$errors = array(); // Start with a clean slate

if (isset($_POST['form_sent']))
{
	// Check that someone from this IP didn't register a user within the last 2 minutes (was last hour) (DoS prevention)
	$result = $db->query('SELECT 1 FROM '.$db->prefix.'users WHERE registration_ip=\''.$db->escape(get_remote_address()).'\' AND registered>'.(time() - 120)) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result))	message($lang_register['Registration flood']);

	$account = pun_trim($_POST['req_account']);
	$username = pun_trim($_POST['req_username']);
	$password1 = pun_trim($_POST['req_password1']);
	$password2 = pun_trim($_POST['req_password2']);
	$email = strtolower(pun_trim($_POST['req_email']));

	// Validate fields
	if ($username == '')	$username = $account;
	check_account($account);
	check_username($username);
	if (pun_strlen($password1) < 4)		$errors[] = $lang_prof_reg['Pass too short'];
	else if ($password1 != $password2)	$errors[] = $lang_prof_reg['Pass not match'];
	require PUN_ROOT.'include/email.php';

	if (!is_valid_email($email))	$errors[] = $lang_common['Invalid email'];

	$language = $pun_user['language']; // Make sure we got a valid language string
	if (!file_exists(PUN_ROOT.'lang/'.$language.'/common.php'))	$language = $pun_config['o_default_lang'];

	$timezone = 0; //round($_POST['timezone'], 1);
	$dst = 0; //isset($_POST['dst']) ? '1' : '0';

	$email_setting = intval($_POST['email_setting']);
	if ($email_setting < 0 || $email_setting > 2)	$email_setting = $pun_config['o_default_email_setting'];

	if (empty($errors)) // Did everything go according to plan?
	{
		$now = time(); // Insert the new user into the database. We do this now to get the last inserted ID for later use

		$intial_group_id = ($pun_config['o_regs_verify'] == '0') ? $pun_config['o_default_user_group'] : PUN_UNVERIFIED;
		$password_hash = pun_hash(strtoupper($account).':'.strtoupper($password1));

		$dba->query('INSERT INTO account(account, username, sha_pass_hash, email, joindate, last_ip, expansion) VALUES(\''.$dba->escape($account).'\', \''.$dba->escape($username).'\', \''.$password_hash.'\', \''.$dba->escape($email).'\', '.$now.', \''.$dba->escape(get_remote_address()).'\', 2)') or error('Unable to create user', __FILE__, __LINE__, $dba->error());
		$new_uid = $dba->insert_id();

		// Add the user
		$db->query('INSERT INTO '.$db->prefix.'users (id, group_id, email, email_setting, timezone, dst, language, style, registered, registration_ip, last_visit) VALUES('.$new_uid.', '.$intial_group_id.', \''.$db->escape($email).'\', '.$email_setting.', '.$timezone.' , '.$dst.', \''.$db->escape($language).'\', \''.$pun_config['o_default_style'].'\', '.$now.', \''.$db->escape(get_remote_address()).'\', '.$now.')') or error('Unable to create user', __FILE__, __LINE__, $db->error());

		if ($pun_config['o_regs_verify'] == '0') {
			if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))	require PUN_ROOT.'include/cache.php'; // Regenerate the users info cache
			generate_users_info_cache(); }
		pun_setcookie($new_uid, $password_hash, time() + $pun_config['o_timeout_visit']);
		redirect(pun_htmlspecialchars($_POST['redirect_url']));
	}
}

if(isset($_POST['check_account'])) {
    $chack_account = $_POST['check_account'];
	$result = $dba->query('SELECT id FROM '.$dba->prefix.'account WHERE account='.$check_account) or error('Unable to fetch user info', __FILE__, __LINE__, $dba->error());
	if ($result) echo '<font color="red">Unavaliable.</font>';
    else         echo '<font color="green">Avaliable.</font>'; }

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_register['Register']);
$required_fields = array('req_account' => $lang_common['Account'], 'req_username' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email' => $lang_common['Email']);
$focus_element = array('register', 'req_account');
define('PUN_ACTIVE_PAGE', 'register');
require PUN_ROOT.'header.php';

$timezone = isset($timezone) ? $timezone : $pun_config['o_default_timezone'];
$dst = isset($dst) ? $dst : $pun_config['o_default_dst'];
$email_setting = isset($email_setting) ? $email_setting : $pun_config['o_default_email_setting'];

if (!empty($errors)) { // Display errors if any
	echo '<div id="posterror" class="block">'.
			'<h2><span>'.$lang_register['Registration errors'].'</span></h2>'.
				'<div class="box">'.
					'<div class="inbox error-info">'.
						$lang_register['Registration errors info'].
						'<ul class="error-list">';
	foreach ($errors as $cur_error)	echo '<li><strong>'.$cur_error.'</strong></li>';
	echo 				'</ul>'.
					'</div>'.
				'</div>'.
			'</div>'; }

$redirect_url = get_referrer();
if (!isset($redirect_url))	$redirect_url = '/';
// else if (preg_match('%viewtopic\.php\?pid=(\d+)$%', $redirect_url, $matches))
//	$redirect_url .= '#p'.$matches[1];

echo '<div id="regerr" hidden="true"></div>'.
	'<div id="regform" class="blockform">'.
		'<h2><span>'.$lang_register['Register'].'</span></h2>'.
		'<div class="box">'.
		'<form id="register" name="register" method="post" action="register.php?action=register" onsubmit="this.register.disabled=true;if(process_form(this)){return true;}else{this.register.disabled=false;return false;}">'.
			'<div class="inform">'.
				'<fieldset>'.
					'<div class="infldset">'.
						'<input type="hidden" name="form_sent" value="1" />'.
						'<input type="hidden" name="redirect_url" value="'.pun_htmlspecialchars($redirect_url).'" />'.
						'<label class="infld conl required">'.
							'<div id="field_req_account" style="display:inline"><div id="hover_field_req_account" style="display:none; position:absolute">'.
								'<div class="tooltip"><div class="tooltipleft">'.$lang_common['ToolTip Account'].'</div></div>'.
							'</div>'.
							'<strong>'.$lang_common['Account'].'</div><span>'.$lang_common['Required'].'</span></strong>'.
							'<input type="text" id="req_account"  name="req_account"  value="'.(isset($_POST['req_account']) ?  pun_htmlspecialchars($_POST['req_account']) : '').'"  size="25" maxlength="12" />'.
						'</label>'.
						'<label class="conl" id="rep_account"><label class="" id="req_account_err"></label>'.
						'</label>'.

						'<label class="infld conl required">'.
							'<div id="field_req_username" style="display:inline"><div id="hover_field_req_username" style="display:none; position:absolute">'.
								'<div class="tooltip"><div class="tooltipleft">'.$lang_common['ToolTip Username'].'</div></div>'.
							'</div>'.
							'<strong>'.$lang_common['Username'].'</div><span>'.$lang_common['Required'].'</span></strong>'.
							'<input type="text" id="req_username"  name="req_username"  value="'.(isset($_POST['req_username']) ?  pun_htmlspecialchars($_POST['req_username']) : '').'"  size="25" maxlength="12" />'.
						'</label>'.
						'<label class="conl" id="rep_username"><div id="req_username_err"></div>'.
						'</label>'.
					'</div>'.
				'</fieldset>'.

				'<fieldset>'.
					'<div class="infldset">'.
						'<label class="infld conl required">'.
							'<div id="field_req_password" style="display:inline"><div id="hover_field_req_password" style="display:none; position:absolute">'.
								'<div class="tooltip"><div class="tooltipleft">'.$lang_common['ToolTip Password'].'</div></div>'.
							'</div>'.
							'<strong>'.$lang_common['Password'].'</div><span>'.$lang_common['Required'].'</span></strong>'.
							'<input type="password" id="req_password1" name="req_password1" value="'.(isset($_POST['req_password1']) ? pun_htmlspecialchars($_POST['req_password1']) : '').'" size="16" />'.
						'</label>'.
						'<label class="conl" id="rep_password1"><label id="req_password1_err"></label>'.
						'</label>'.

						'<label class="infld conl required">'.
							'<div id="field_req_password2" style="display:inline"><div id="hover_field_req_password2" style="display:none; position:absolute">'.
								'<div class="tooltip"><div class="tooltipleft">'.$lang_common['ToolTip Password2'].'</div></div>'.
							'</div>'.
							'<strong>'.$lang_common['Password2'].'</div><span>'.$lang_common['Required'].'</span></strong>'.
							'<input type="password" id="req_password2" name="req_password2" value="'.(isset($_POST['req_password2']) ? pun_htmlspecialchars($_POST['req_password2']) : '').'" size="16" />'.
						'</label>'.
						'<label class="conl" id="rep_password2"><label id="req_password2_err"></label>'.
						'</label>'.

					'</div>'.
				'</fieldset>'.
				'<fieldset>'.
					'<div class="infldset">'.
						'<label class="infld conl required">'.
							'<div id="field_req_email" style="display:inline"><div id="hover_field_req_email" style="display:none; position:absolute">'.
								'<div class="tooltip"><div class="tooltipleft">'.$lang_common['ToolTip Email'].'</div></div>'.
							'</div>'.
							'<strong>'.$lang_common['Email'].'</div><span><'.$lang_common['Required'].'</span></strong>'.
							'<input type="text" id="req_email" name="req_email" value="'.(isset($_POST['req_email']) ? pun_htmlspecialchars($_POST['req_email']) : '').'" size="50" maxlength="80" />'.
						'</label>'.
						'<label class="conl" id="rep_email"><div id="req_email_err"></label>'.
					'</div>'.
				'</fieldset>'.
			'</div>'.
			'<p class="buttons"><input type="submit" id="register" name="register" value="'.$lang_common['Register'].'" /></p>'.
		'</form>'.
	'</div>'.
'</div>';

require PUN_ROOT.'footer.php';
