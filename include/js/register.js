<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var required_fields = {
<?php
	// Output a JavaScript object with localised field names
	$tpl_temp = count($required_fields);
	foreach ($required_fields as $elem_orig => $elem_trans) {
		echo "\t\t\"".$elem_orig.'": "'.addslashes(str_replace('&#160;', ' ', $elem_trans));
		if (--$tpl_temp) echo "\",\n";
		else echo "\"\n\t};\n"; }
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

$(function(){
    $("div[id^='field_req_']").hover(function(){
			$(this).find("div[id^='hover_field_req']").show();

			$(window).mousemove(function(e){
				$("div[id^='hover_field_req']").css({left:e.pageX+15, top:e.pageY+15});
			});
		}
							,function(){
			$(this).find("div[id^='hover_field_req']").hide();
		}
	);        
});

</SCRIPT>