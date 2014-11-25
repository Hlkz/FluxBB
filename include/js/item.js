
function disp_list_or_flag(the_field)
{
	var field = $(the_field)
	var listflag = $('#listflag'+field.attr("value"));
	if (listflag.attr("hidden"))
		listflag.removeAttr("hidden");
	else	listflag.attr("hidden", true);
	return true;
}

function flag_check(the_box)
{
	var box = $(the_box);
	var newreq = 0;
	var flagid = box.attr("flagid");
	var flagcount = $("#"+box.attr("fieldid")+"flagcount").attr("value");
	var newreq = 0;
	var newlist = [];
	var newcount = 0;
	for (flagbox = 0; flagbox < flagcount; flagbox++)
		if ($("#lf"+flagid+"b"+flagbox).is(":checked"))
		{
			newlist[newcount++] = " "+$("#lf"+flagid+"b"+flagbox).attr("listname");
			newreq += $("#lf"+flagid+"b"+flagbox).attr("value") - 0;
		}
	$("#"+box.attr("fieldid")).val(newreq);
	$("#"+box.attr("fieldid")+"disp").html(newlist.join(" - "));
	return true;
}

function list_check(the_box)
{
	var box = $(the_box);
    if (box.is(":checked"))
	{
        var group = "input:checkbox[name='" + $(the_box).attr("name") + "']";
        $(group).prop("checked", false);
        box.prop('checked', true);
		$("#"+box.attr("fieldid")).val(box.attr("value"));
		$("#"+box.attr("fieldid")+"disp").html(box.attr("listname"));
    }
	else	box.prop('checked', true);
};


function dont_check(the_box)
{
	var box = $(the_box);
    if (box.is(":checked"))	box.prop('checked', false);
	else					box.prop('checked', true);
};

function update_sql_query()
{
	var sql = "DELETE FROM `"+$("#req_dbname").val()+"`.`"+$("#req_tablename").val()+"` ";
	sql += "WHERE `"+$("#primary_field").attr("value")+"` = '"+mysql_real_escape_string($("#req_"+$("#primary_field").attr("index")).val())+"';\n\n";
	sql += "INSERT INTO `"+$("#req_dbname").val()+"`.`"+$("#req_tablename").val()+"`"+$("#sql_first").attr("value")+"(";
	var tab = [];
	for (field = 0; field < $("#field_count").attr("value"); field++)
		tab[field] = "'"+($("#req_"+field).val() ? mysql_real_escape_string($("#req_"+field).val()) : "")+"'";
	sql += tab.join(', ')+");";
	$("#req_sql").val(sql);
};

function update_sql_query_delete()
{
	var sql = "DELETE FROM `"+$("#req_dbname").val()+"`.`"+$("#req_tablename").val()+"` ";
	sql += "WHERE `"+$("#primary_field").attr("value")+"` = '"+mysql_real_escape_string($("#req_"+$("#primary_field").attr("index")).val())+"';";
	$("#req_sql").val(sql);
}

function mysql_real_escape_string (str) {
	if (!str) return;
    return str.replace(/[\0\x08\x09\x1a\n\r"'\\\%]/g, function (char) {
        switch (char) {
            case "\0":		return "\\0";
            case "\x08":	return "\\b";
            case "\x09":	return "\\t";
            case "\x1a":	return "\\z";
            case "\n":		return "\\n";
            case "\r":		return "\\r";
            case "\"":	case "'":	case "\\":	//case "%":	// prepends a backslash to backslash, percent,
				return "\\"+char;							// and double/single quotes
			case "%": return char;
        }
    });
}
