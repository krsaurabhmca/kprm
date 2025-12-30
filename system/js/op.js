"use strict";
/* Apprise Js By OfferPlant*/

// for navbar active
$(function(){
    var url = window.location.pathname, 
        urlRegExp = new RegExp(url.replace(/\/$/,'') + "$"); // create regexp to match current url pathname and remove trailing slash if present as it could collide with the link in navigation in case trailing slash wasn't present there
        // now grab every link from the navigation
        $('.sidebar-item a').each(function(){
            // and test its normalized href against the url pathname regexp
            if(urlRegExp.test(this.href.replace(/\/$/,''))){
                // $('li').removeClass('active');
                $(this).parents('li').addClass('active');
                $(this).parents('ul').addClass('show');
                $(this).parents('li').parents('li').children('a').removeClass('collapsed');
            }
        });
});

//=======AUTO LOGOUT AFTER 2 Min of Inactivity ====//
var base_url = 'https://kprm.co.in/';
var sys_url = '../system/system_process.php?task=';
var master_url = '../master_process.php?task=';
var mobiles = [];
var timeSinceLastMove = 0;
$(document).on('mousemove , keyup', function () {
	timeSinceLastMove = 0;
});

//vanila javascript
$(document).on('select2:open', () => {
    document.querySelector('.select2-search__field').focus();
});

function checkTime() {
	timeSinceLastMove++;
	if (timeSinceLastMove > 10 * 60) {
		autologout();
	}
	else {
	    setTimeout(checkTime, 10000);
	}
}

function check_device()
{
    var device ="";
    if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
      // true for mobile device
      device ="MOBILE";
    }else{
        device ="DESKTOP";
      // false for not mobile device
    }
    return device;
}

function notyf(msg, status)
{
	const notyf = new Notyf({'ripple':true,dismissible:true,position:{x:'right',y:'top'}});
	if(status =='success')
	{
	notyf.success(msg);
	}
	if(status =='error')
	{
	notyf.error(msg);
	}
}

//=====Query BUTTON =========//

function fetch_data(task, param = null) {
	var api_url = master_url+"" + task;
	var ajax = $.ajax({
		url: api_url,
		method: 'POST',
		dataType: "json",
		data: param,
		async: false,
		cache: false,
	});
	var data = $.parseJSON(ajax.responseText);
	return data; // .staus .url .id	
}
function addspace(input) {
	var newtstr = input.replace("_", " &nbsp; ");
	var words = newtstr.split(' ');
	var CapitalizedWords = [];
	words.forEach(element => {
		CapitalizedWords.push(element[0].toUpperCase() + element.slice(1, element.length));
	});
	return CapitalizedWords.join('');
}

function objtable(mydata) {
	var table = $('<table class="table" width="100%" >');
	var tblHeader = "<tr>";
	for (var k in mydata[0]) tblHeader += "<th>" + addspace(k) + "</th>";
	tblHeader += "</tr>";
	$(tblHeader).appendTo(table);
	$.each(mydata, function (index, value) {
		var TableRow = "<tr>";
		$.each(value, function (key, val) {
			TableRow += "<td>" + val + "</td>";
		});
		TableRow += "</tr>";
		$(table).append(TableRow);
	});
	return ($(table));
}

//=====INSERT BUTTON =========//
$(document).on('click', "#insert_btn", function (event) {
	$("#insert_frm").validate();
	if ($("#insert_frm").valid()) {
		var task = $("#insert_frm").attr('action');
		var type = $("#insert_frm").attr('type');
		var url =master_url + task
		if(type=='system')
		{
			url =sys_url + task
		}
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data = $("#insert_frm").serialize();
		$.ajax({
			'type': 'POST',
			'url': url,
			'data': data,
			success: function (data) {
				console.log(data);
				//alert(data);
				var obj = JSON.parse(data);
				$('#insert_frm')[0].reset();
				if ($('#uploadForm').length != 0) {
					$('#uploadForm')[0].reset();
				}
				//notyf(obj.msg, obj.status);

				$("#insert_btn").html("Save Details");
				$("#insert_btn").removeAttr("disabled");
				if (obj.url != null) {
					bootbox.alert(obj.msg, function () {
						window.location.replace(obj.url);
					});
				}
				else if (obj.modal != null) {
				    notyf(obj.msg, obj.status);
					setTimeout(	$("#"+obj.modal).modal('hide'),1500);
				}
				else {
					notyf(obj.msg, obj.status);
				}
			}

		});
	}
});

//=====UPDATE BUTTON =========//
$(document).on("click","#update_btn",function () {
	$("#update_frm").validate();

	if ($("#update_frm").valid()) {
		var task = $("#update_frm").attr('action');
		var type = $("#update_frm").attr('type');
		var url =master_url + task
		if(type=='system')
		{
			url =sys_url + task
		}
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data = $("#update_frm").serialize();
		$.ajax({
			'type': 'POST',
			'url': url,
			'data': data,
			success: function (data) {
				//alert(data);
				console.log(data);
				var obj = JSON.parse(data);
				
				if (obj.status =='success') {
				    $('#update_frm')[0].reset();
				}

				$("#update_btn").html("Save Details");
				$("#update_btn").removeAttr("disabled");
				if (obj.url != null && obj.status =='success') {
				    
					bootbox.alert(obj.msg, function () {
						window.location.replace(obj.url);
					});
				}
				else {
					notyf(obj.msg, obj.status);
				}
			}

		});
	}
});


//=====DELETE BUTTON =========//
$(document).on('click', '.delete_btn', function () {
	var del_row = $($(this).closest("tr"));
	var id = $(this).attr("data-id");
	var table = $(this).attr("data-table");
	var pkey = $(this).attr("data-pkey");
	bootbox.confirm({
		message: "Do you really want to delete this?",
		buttons:
		{
			confirm: {
				label: 'Yes',
				className: 'btn-success'
			},
			cancel: {
				label: 'No',
				className: 'btn-danger'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"master_delete",
					'data': { 'id': id, 'table': table, 'pkey': pkey },
					success: function (data) {
						//console.log(data);
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						del_row.hide(500);
					}
				});
			}
		}
	});
});

//=====REMOVE BUTTON =========//
$(document).on('click', '.remove_btn', function () {
	var del_row = $($(this).closest("tr"));
	var id = $(this).attr("data-id");
	var table = $(this).attr("data-table");
	var pkey = $(this).attr("data-pkey");
	bootbox.confirm({
		message: "Do you want to Remove this?",
		buttons:
		{
			confirm: {
				label: 'Yes',
				className: 'btn-dark'
			},
			cancel: {
				label: 'No',
				className: 'btn-primary'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"master_remove",
					'data': { 'id': id, 'table': table, 'pkey': pkey },
					success: function (data) {
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						del_row.hide(500);
					}
				});
			}
		}
	});
});


//=====ACTIVE / BLOCK BUTTON =========//
$(document).on('click',".active_block", function () {
	var del_row = $($(this).closest("tr"));
	var data_table = $(this).attr('data-table');
	var data_status = $(this).attr('data-status');
	var id = $(this).attr('data-id');
	
		bootbox.confirm({
			message: "Do you really want to " + data_status + " records ?",
			buttons:
			{
				confirm: {
					label: 'Yes',
					className: 'btn-success btn-sm'
				},
				cancel: {
					label: 'No',
					className: 'btn-danger btn-sm'
				}
			},
			callback: function (result) {
				if (result == true) {
					$.ajax({
						'type': 'POST',
						'url': sys_url+"active_block",
						'data': { 'table': data_table, 'status': data_status, 'id':id},
						success: function (data) {
							//console.log(data);
							//var obj = JSON.parse(data);
							notyf(" Records " + data_status + " Successfully", "success");
							del_row.hide(500);
						}
					});
				}
			}
		});
	
});



//=====BLOCK BUTTON =========//
$(".block_btn").on('click', function () {
	var del_row = $($(this).closest("tr"));
	var id = $(this).attr("data-id");
	var table = $(this).attr("data-table");
	var pkey = $(this).attr("data-pkey");
	bootbox.confirm({
		message: "Do you really want to BLOCK this?",
		buttons:
		{
			confirm: {
				label: 'Yes',
				className: 'btn-info'
			},
			cancel: {
				label: 'No',
				className: 'btn-warning'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"master_block",
					'data': { 'id': id, 'table': table, 'pkey': pkey },
					success: function (data) {
						//alert(data);
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						del_row.hide(500);
					}
				});
			}
		}
	});
});

//=====BLOCK USER =========//
$(".block_user").on('click', function () {
	var del_row = $($(this).closest("tr"));
	var id = $(this).attr("data-id");
	var st = $(this).attr("data-status");
	bootbox.confirm({
		message: "Do you really want to " + st + "  this User Account?",
		buttons:
		{
			confirm: {
				label: 'Yes',
				className: 'btn-success'
			},
			cancel: {
				label: 'No',
				className: 'btn-danger'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"block_user",
					'data': { 'id': id, 'data_status': st },
					success: function (data) {
						//alert(data);
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						//del_row.hide(500); 
						location.reload();
					}
				});
			}
		}
	});
});


// === Dynamic District Section From State ==/
$(document).on('change',".state_name",function(){
    var state = $(this).val();
	$.ajax({
		type: "GET",
		url: sys_url+'get_dist',
		data: 'state=' + state,
		success: function (data) {
		    $(".district_name").html(data);
		}
	});
});

// === Dynamic Block Section From Discrict ==/
$(document).on('change',".district_name",function(){
    var district = $(this).val();
	$.ajax({
		type: "GET",
		url: sys_url+'get_block',
		data: 'district=' + district,
		success: function (data) {
		    $(".block_name").html(data);
		}
	});
});



//========= LOGIN BUTTON ===========//
$("#login_btn").click(function () {
	$("#login_frm").validate();
	if ($("#login_frm").valid()) {
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data = $("#login_frm").serialize();
		$.ajax({
			'type': 'POST',
			'url': 'system_process?task=verify_login',
			'data': data,
			success: function (data) {
				var obj = JSON.parse(data);
				if (obj.status.trim() == 'success') {
					notyf("Login Success...", obj.status);
					window.location = obj.url;
				}
				else {
				// 	notyf("Sorry Some Thing Went Wrong", "error");
					notyf(obj.msg, obj.status);
					//$("#login_frm")[0].reset();
					$("#login_btn").html("Secure Login");
					$("#login_btn").attr("disabled", false);
				}
			}

		});
	}
});

//========= LOGIN As BUTTON ===========//
$(document).on('click',".login_as",function () {
	var user_name = $(this).attr("data-id");
	var user_pass = $(this).attr("data-code");
	var data = {
		'user_name': user_name,
		'user_pass': user_pass
	}
	$.ajax({
		'type': 'POST',
		'url': sys_url+"login_as",
		'data': data,
		success: function (data) {
			//alert(data);
			var obj = JSON.parse(data);

			if (obj.status.trim() == 'success') {
				notyf("Login Success...", obj.status);
				window.location = base_url+'system/op_dashboard';
			}
			else {
				notyf("Sorry Some Thing Went Wrong", "error");
				$("#login_frm")[0].reset();
				$("#login_btn").html("Secure Login");
				$("#login_btn").attr("disabled", false);
			}
		}

	});
});


$("#selectAll").change(function(){
$(".chk").prop("checked", $("#selectAll").prop('checked'));
});

//===========LOGOUT WITH CONFIRAMTION ==========//
function logout() {
	bootbox.confirm({
		message: "You you really want to logout ?",
		buttons:
		{
			confirm: {
				label: '<i class="fa fa-check"></i> Logout',
				className: 'btn-success'
			},
			cancel: {
				label: '<i class="fa fa-times"></i> Cancel',
				className: 'btn-danger'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					type: 'POST',
					url: sys_url+'logout',
					data: { 'rtype': 'AJAX' },
					success: function (data) {
						console.log(data);
						var obj = JSON.parse(data);
						window.location = 'op_login';
						notyf(obj.msg, obj.status);
					}
				});
			}
		}
	});
}

function autologout() {
	$.ajax({
		'type': 'POST',
		'url': sys_url+"logout",
		success: function (data) {
			//alert(data);
			var obj = JSON.parse(data);

			window.location = 'op_login';
			notyf(obj.msg, obj.status);
		}
	});
}


//===========ADD SINGLE DATA ===========//
$("#add_btn").click(function () {
	var msg = $(this).attr('data-msg');
	var table = $(this).attr('data-table');
	var col = $(this).attr('data-col');
	bootbox.prompt(msg, function (udata) {

		if (udata) {
			var tdata = { "table": table, 'col': col, 'value': udata };
			$.ajax({
				'type': 'POST',
				'url': sys_url+"add_data",
				'data': tdata,
				success: function (data) {
					////alert(data);
					var obj = JSON.parse(data);
					notyf(obj.msg, obj.status);
				}
			});
		}
	});
});


//===========Reply Data ===========//
$(document).on("click",".reply_btn", function () {
	var table = $(this).attr('data-table');
	var col = $(this).attr('data-col');
	var id = $(this).attr('data-id');
	bootbox.prompt("Reply", function (udata) {
		if (udata) {
			var tdata = { "table_name": table, 'col': col, 'value': udata, 'id':id };
			$.ajax({
				'type': 'POST',
				'url': sys_url+"add_data",
				'data': tdata,
				success: function (data) {
					var obj = JSON.parse(data);
					notyf(obj.msg, obj.status);
				}
			});
		}
	});
});


//===========CREATE NEW TABLE ===========//
$("#add_table").click(function () {
	bootbox.prompt("Enter Table Name", function (udata) {
		if (udata) {
			$.ajax({
				'type': 'POST',
				'url': 'system_process?task=add_table',
				'data': {table_name:udata},
				success: function (data) {
					//var obj = JSON.parse(data);
					notyf("Table Created Successfully", "success");
					setTimeout(()=>location.reload(),1500);
				}
			});
		}
	});
});



//=====DELETE BUTTON =========//
$(document).on('click', '.delete_table', function () {
	var del_row = $($(this).closest("tr"));
	var id = $(this).attr("data-id");
	var table = $(this).attr("data-table");

	bootbox.confirm({
		message: "All Data Related to this Table <b> ("+ table +") </b> will be permanently deleted and can be recover. <br> Are you sure to delete  this?",
		buttons:
		{
			confirm: {
				label: 'Yes Delete ',
				className: 'btn-danger'
			},
			cancel: {
				label: 'No By Mistatke ',
				className: 'btn-info'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"delete_table",
					'data': { 'id': id, 'table_name': table },
					success: function (data) {
					
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						del_row.hide(500);
					}
				});
			}
		}
	});
});



//=====DELETE ALL SELECTED RECORD =========//
$(document).on('click', '#delete_btn', function () {
	var sel_id =[];
	$(".chk:checked").each(function(){
		sel_id.push($(this).val());
	});
	if(sel_id.length>0)
	{
	var table = $(this).attr("data-table");
	bootbox.confirm({
		message: "Do You really want to delete all selected data. <br> <b> Are you sure to permanently delete this? </b> ",
		buttons:
		{
			confirm: {
				label: 'Yes ! Delete ',
				className: 'btn-danger'
			},
			cancel: {
				label: 'No ! Its my Mistatke ',
				className: 'btn-info'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"master_delete_multiple",
					'data': { 'sel_id': sel_id, 'table_name': table },
					success: function (data) {
					
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						setTimeout(()=>location.reload(),1500);
					}
				});
			}
		}
	});
	}else{
		notyf("At least one record must be selected", "error");
	}
});


//=====REMOVE ALL SELECTED RECORD =========//
$(document).on('click', '#remove_btn', function () {
	var sel_id =[];
	$(".chk:checked").each(function(){
		sel_id.push($(this).val());
	});
	if(sel_id.length>0)
	{
	var table = $(this).attr("data-table");
	bootbox.confirm({
		message: "Do You really want to remove all selected data ? </b> ",
		buttons:
		{
			confirm: {
				label: 'Yes ! Remove ',
				className: 'btn-dark'
			},
			cancel: {
				label: 'No ! Its my Mistatke ',
				className: 'btn-primary'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"master_remove_multiple",
					'data': { 'sel_id': sel_id, 'table_name': table },
					success: function (data) {
					
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						setTimeout(()=>location.reload(),1500);
					}
				});
			}
		}
	});
	}else{
		notyf("At least one record must be selected", "error");
	}
});



//===========CREATE NEW TABLE ===========//
$("#add_role").click(function () {
	bootbox.prompt("Enter Role Name ", function (udata) {
		if (udata) {
			$.ajax({
				'type': 'POST',
				'url': 'system_process?task=add_role',
				'data': {role_name:udata},
				success: function (data) {
					//var obj = JSON.parse(data);
					console.log(data);
					notyf("Role Created Successfully", "success");
					//setTimeout(()=>location.reload(),1500);
				}
			});
		}
	});
});



//======FORGET PASSWORD USING PROMPT BOX =======/
$("#forget_password").click(function () {
	bootbox.prompt("Enter a valid Username ", function (str) {
		if (str) {
			$.ajax({
				'type': 'POST',
				'url': sys_url+"forget_password",
				'data': 'user_name=' + str,
				success: function (data) {
					//alert(data);
					var obj = JSON.parse(data);
					notyf(obj.msg, obj.status);
				}
			});
		}
	});
});


//======Change PASSWORD of Logged In User =======/
$("#change_password").click(function () {
	$(this).attr("disabled", true);
	$(this).html("Please Wait...");
	$("#update_frm").validate();

	if ($("#update_frm").valid()) {
	    var task = $("#update_frm").attr('action');
		var type = $("#update_frm").attr('type');
		var url =sys_url + task
		if(type=='system')
		{
			url ='system_process?task=' + task
		}
		var cp = $("#current_password").val();
		var np = $("#new_password").val();
		var rp = $("#repeat_password").val();
		if (np != rp) {
			notyf("New password and Repeat password Not matched", "error");

		}
		else {
			$.ajax({
				'type': 'POST',
				'url': url,
				'data': 'new_password=' + np + '&current_password=' + cp,
				success: function (data) {
					////alert(data);
					var obj = JSON.parse(data);
					if (obj.status.trim() == 'success') {
						notyf("Password Changed Succesfully", obj.status);
						$("#update_frm")[0].reset();
						logout();
					}
					else {
						notyf("Sorry! Unable to Chanage Password ", "error");
						$("#update_frm")[0].reset();
						$("#change_password").attr("disabled", false);
					}
				}
			});
		}
	}

});


function populate(frm, data) {
	//$("#edit_modal").show();
	$.each(data, function (key, value) {
		var ctrl = $('[name=' + key + ']', frm);
		switch (ctrl.prop("type")) {
			case "radio": case "checkbox":
				ctrl.each(function () {
					if ($(this).attr('value') == value) $(this).attr("checked", value);
				});
				break;
			case "select":
				$("option", ctrl).each(function () {
					if (this.value == value) { this.selected = true; }
				});
				break;
			default:
				ctrl.val(value);
		}
	});
}

function json2table(selector, myList) {
	var columns = addAllColumnHeaders(myList, selector);

	for (var i = 0; i < myList.length; i++) {
		var row$ = $('<tr/>');
		for (var colIndex = 0; colIndex < columns.length; colIndex++) {
			var cellValue = myList[i][columns[colIndex]];
			if (cellValue == null) cellValue = "";
			row$.append($('<td/>').html(cellValue));
		}
		$(selector).append(row$);
	}
}

function addAllColumnHeaders(myList, selector) {
	var columnSet = [];
	var headerTr$ = $('<tr/>');

	for (var i = 0; i < myList.length; i++) {
		var rowHash = myList[i];
		for (var key in rowHash) {
			if ($.inArray(key, columnSet) == -1) {
				columnSet.push(key);
				headerTr$.append($('<th/>').html(key));
			}
		}
	}
	$(selector + ' thead').append(headerTr$);

	return columnSet;
}


function exportxls(id ='data-tbl') {
	var tab_text = "<table border='1px'><tr>";
	var textRange; var j = 0;
	let tab = document.getElementById(id); // id of table

	for (j = 0; j < tab.rows.length; j++) {
		tab_text = tab_text + tab.rows[j].innerHTML + "</tr>";
		//tab_text=tab_text+"</tr>";
	}

	tab_text = tab_text + "</table>";
	// tab_text= tab_text.replace(/<A[^>]*>|<\/A>/g, "");//remove if u want links in your table
	tab_text = tab_text.replace(/<img[^>]*>/gi, ""); // remove if u want images in your table
	tab_text = tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); // reomves input params

	var ua = window.navigator.userAgent;
	var msie = ua.indexOf("MSIE ");

	if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))      // If Internet Explorer
	{
		txtArea1.document.open("txt/html", "replace");
		txtArea1.document.write(tab_text);
		txtArea1.document.close();
		txtArea1.focus();
		sa = txtArea1.document.execCommand("SaveAs", true, "export.xls");
	}
	else                 //other browser not tested on IE 11
		sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));

	return (sa);
}



$(document).on('click',".cancelreceipt", function () {
	var id = $(this).data('id');
	var remarks = prompt("Enter Cause to cancel");
	//alert(remarks); 
	if (remarks == '' || remarks === null) {
		notyf("Enter A Valid Cause ", "error");
	}
	else {

		$.ajax({
			method: 'post',
			url: '../master_process.php?task=receipt_cancel',
			data: {
				receipt_id: id,
				cancel_remarks: remarks
			},
			beforeSend: function () {
				//console.log(id+remarks);
			},
			success: function (res) {
				var obj = JSON.parse(res);
				notyf(obj.msg, obj.status);
			}

		});
	}
});





$(".cancel_admin_txn").on('click', function () {
	var id = $(this).data('id');
	var remarks = prompt("Enter Cause to cancel");
	//alert(remarks); 
	if (remarks == '' || remarks === null) {
		notyf("Enter A Valid Cause ", "error");
	}
	else {

		$.ajax({
			method: 'post',
			url: 'system/master_process.php?task=cancel_admin_txn',
			data: {
				txn_id: id,
				cancel_remarks: remarks
			},
			beforeSend: function () {
				//console.log(id+remarks);
			},
			success: function (res) {
				var obj = JSON.parse(res);
				notyf(obj.msg, obj.status);
			}

		});
	}
});

// Voice Search  with Vi Class //

$(document).on('focus',".vi", function(){
	var speech = true;
	window.SpeechRecognition = window.SpeechRecognition
					|| window.webkitSpeechRecognition;

	const recognition = new SpeechRecognition();
	recognition.interimResults = true;

	recognition.addEventListener('result', e => {
		const transcript = Array.from(e.results)
			.map(result => result[0])
			.map(result => result.transcript)
			.join('')
//		console.log(transcript);
	    $(this).val(transcript);
	});
	
	if (speech === true) {
		recognition.start();
		recognition.addEventListener('end');
		//recognition.addEventListener('end', recognition.start);
	}
});


/* VIEW DATA IN MODAL */
$(document).on('click', '.view_data', function (e) {
	e.preventDefault();
	$('#view_data').modal('show').find('.modal-title').html($(this).attr('data-title'));
	$('#view_data').modal('show').find('.modal-body').load($(this).attr('data-href'));
});

/* GLOBAL SHORTCUTS */


shortcut.add("ctrl+s", function () {
	//alert("CTRL +S");
	$("#update_btn").trigger('click');
});

shortcut.add("ctrl+shift+f", function () {
	//alert("CTRL +f");
	$("#search_text").focus();
});


//=====Chnage YES/NO in Master Table =========//
$(document).on('click', ".yesno", function() {
    var x = $(this);
    var table = $(this).data('table');
    var status = $(this).data('status');
    var column = $(this).data('column');
	var id = $(this).data('id');
    //alert(status);
	var nstatus ='YES';
    if(status=='YES')
    {
        nstatus ='NO';
    }
	
        $.ajax({
            'type': 'POST',
            'url': sys_url+"update_master_permission",
            'data': { 'table':table, 'status': nstatus, 'id': id,'column':column },
            success: function(data) {
                //alert(data);
                //var obj = JSON.parse(data);
                notyf(" Status Changed Succesfully", "success");
				x.data('status',nstatus);
				x.closest('.form-check-label').html(nstatus);
                //location.reload();
            }
        }); 
});

// UPALOAD ANY FILE 

$(document).on('change',".upload_img",function(){
    var table = $(this).data('table');
    console.log(table);
    var field = $(this).data('field');
    var size = $(this).data('size');
    var img =  document.getElementById(field);
    var file = img.files[0];
    var formData = new FormData();
    $("#update_btn").attr("disabled", true);
     formData.append('uploadimg', file);
     formData.append('size', size);
    // console.log(formData);
     $.ajax({
     url: sys_url+"upload",
     type: "POST",
     data: formData,
     contentType: false,
     cache: false,
     processData:false,
     success: function(data){
         var obj = JSON.parse(data);
         $("#target_"+field).val(obj.id);
         if(obj.id !=0)
         {
         $("#"+field+"_display").html("<a href='../upload/"+obj.id+"' download  class='btn btn-border border-secondary m-2 text-primary' > <i class='fa fa-download'></i> Download</a>");
         }
         notyf(obj.msg,obj.status);
         $("#update_btn").attr("disabled", false);
     },
     error: function(){} 	        
     });
 });
 
// Upload Multiple File of any Type 

$(document).on('change',".upload_multi_img",function(){
   
    var table = $(this).data('table');
    var field = $(this).data('field');
    var formData = new FormData();
    var size = $(this).data('size');
    let dt = $('input[type=file]')[0].files;
    for (let i = 0; i < dt.length; i++) {
        // console.log(dt[i]);
         formData.append("uploadimg[]", dt[i]);
    }
    
    $("#update_btn").attr("disabled", true);
    //  formData.append('uploadimg', dt);
     formData.append('size', size);
    // console.log(formData);
     $.ajax({
     url: sys_url+"multi_upload",
     type: "POST",
     data: formData,
     contentType: false,
     cache: false,
     processData:false,
     success: function(data){
         var obj = JSON.parse(data);
         $("#target_"+field).val(obj.img_name);
         
         obj.id.map((val)=>{
             let ext = val.split('.').pop();
             console.log(ext);
             
             if(ext =='jpg' || ext =='png' ){
                  $("#"+field+"_display").append("<img src='../upload/"+val+"' width='100px' height='100px' class='img-thumbnail'> ");
             }else{
                //  $("#"+field+"_display").append("<a href='"+obj.src+"/"+val+"' download  class='btn btn-border border-secondary m-2 text-primary' > <i class='fa fa-download'></i> Download</a>");
                  $("#"+field+"_display").append("<a href='../upload/"+val+"' download  class='btn btn-border border-secondary m-2 text-primary' > <i class='fa fa-download'></i> Download</a>");
             }
             
         })
         notyf(obj.msg,obj.status);
         $("#update_btn").attr("disabled", false);
     },
     beforeSend:function()
     {
          $("#"+field+"_display").html("<h3 id='waiting' class='text-center text-muted'> <i class='fa fa-spinner fa-spin' style='font-size:40px'></i> <br> Please Wait While Uploading the files.. </h3>");
     },
     complete:function()
     {
          $("#waiting").hide();
     },
     error: function(){} 	        
     });
 });
 
 
 // === Dynamic Column List From Table ==/
$(document).on('change blur' , '#table_list', function(){
    var val = $(this).val();
    $.ajax({
        type: "GET",
        url: sys_url+"get_column",
        data: 'table_name=' + val,
        success: function(data) {
           // console.log(data);
            $("#column_list").html(data);
        }
    });
});

// Sync table Structute to OP_master_table 
$(document).on('click', '.sync_table', function(){
    var val = $(this).data('table');
    $.ajax({
        type: "GET",
        url: sys_url+"sync_table",
        data: 'table_name=' + val,
        success: function(data) {
			location.reload();
        },
		
    });
});


// Reset Opex Structute to New
$(document).on('click' , '#reset_opex', function(){
    bootbox.confirm({
		message: "Do you really want to reset full application?",
		buttons:
		{
			confirm: {
				label: 'Yes',
				className: 'btn-success'
			},
			cancel: {
				label: 'No',
				className: 'btn-danger'
			}
		},
		callback: function (result) {
			if (result == true) {
	
			$.ajax({
				type: "GET",
				url: sys_url+"reset_opex",
				success: function(data) {
					location.reload();
				},
			});
			}
		}
		});
});


// Create New Opex Structute of Project
$(document).on('click' , '#create_opex', function(){
    bootbox.confirm({
		message: "Do you really want to Recreate full application?",
		buttons:
		{
			confirm: {
				label: 'Yes ! Continue',
				className: 'btn-success'
			},
			cancel: {
				label: 'No ! Sorry',
				className: 'btn-danger'
			}
		},
		callback: function (result) {
			if (result == true) {
			$.ajax({
				type: "GET",
				url: sys_url+"create_opex",
				success: function(data) {
					location.reload();
				},
			});
			}
		}
		});
});

// Reset Configuration  

$(document).on('click', '#reset_config', function() {
    var del_row = $($(this).closest("tr"));
    var id = $(this).attr("data-id");
    var table = $(this).attr("data-table");
    var pkey = $(this).attr("data-pkey");
    var per = $(this).attr("data-per");
    bootbox.confirm({
        message: "Do you really want to reset all configuration ?",
        buttons: {
            confirm: {
                label: 'Yes',
                className: 'btn-success'
            },
            cancel: {
                label: 'No',
                className: 'btn-danger'
            }
        },
        callback: function(result) {
            if (result == true) {
                $.ajax({
                    'type': 'POST',
                    'url': sys_url+"reset_config",
                    'data': {},
                    success: function(data) {
                       var obj = JSON.parse(data);
                       notyf("Data Reset Success", "success");
                       setTimeout(()=>location.reload(),1500);
                    }
                });
            }
        }
    });
});

// Summer Note From  Footer 

$(document).ready(function() {
	$(".summernote").summernote({
		placeholder: 'Type your text here',
		tabsize: 2,
		height: 400,
		airMode: false,
		callbacks: {
			onBlur: function(e) {
				var data = $(".summernote").val();
				console.log(data);
				$(".summerdata").val(Base64.encode(data));
			}
		}
	});
});

  if ($(".select2").length > 0) {
        $(".select2").select2(); // Initialize

        $(".select2").on("select2:select", function (evt) {
            var element = evt.params.data.element;
            var $element = $(element);
            $element.detach();
            $(this).append($element).trigger("change");
        });
    }

// Chnage parent Menu 

//=====DELETE ALL SELECTED RECORD =========//
$(document).on('click', '#change_menu_btn', function () {
	var sel_id =[];
	$(".chk:checked").each(function(){
		sel_id.push($(this).val());
	});
	if(sel_id.length>0)
	{
	var main_menu = $("#main_menu").val();
	bootbox.confirm({
		message: "Do You really want to Change Main Menu of all selected submenu. </b> ",
		buttons:
		{
			confirm: {
				label: 'Yes ! Change  ',
				className: 'btn-danger'
			},
			cancel: {
				label: 'No ! Its my Mistatke ',
				className: 'btn-info'
			}
		},
		callback: function (result) {
			if (result == true) {
				$.ajax({
					'type': 'POST',
					'url': sys_url+"bulk_menu_update",
					'data': { 'sel_id': sel_id, 'parent': main_menu},
					success: function (data) {
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
						setTimeout(()=>location.reload(),1500);
					}
				});
			}
		}
	});
	}else{
		notyf("At least one record must be selected", "error");
	}
});

// Bulk Edit & Save 
$(document).on('dblclick','.edit_box',function(){
     $(this).attr("contenteditable",true);
});
      
$(document).on('keydown','.edit_box',function(){
     if (event.keyCode == 13) {
          event.preventDefault();
           var x = confirm("Do You want to save the change ?");
           if(x==true)
           {
                var new_value = $(this).text();
                var table = $(this).data('table');
                var column = $(this).data('column');
                var id = $(this).data('id');
                var input = $(this).data('input'); 
                
                $.ajax({
					'type': 'POST',
					'url': sys_url+"quick_update_data",
					'data':{'table_name':table, 'column':column, 'value':new_value,'id':id},
					success: function (data) {
						var obj = JSON.parse(data);
						notyf(obj.msg, obj.status);
					}
				});
           }
        }
});

