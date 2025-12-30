

    $(document).on('click', "#btn_add_task", function () {

        let client_name = $("#client_name").val();
        let case_id = $("#case_id").val();
        let task_type = $("#task_type").val();
        let task_name = $("#task_name").val();

        if (task_type == '' || task_name == '') {
            alert("Sorry! Select Task Types or task Name");
        } else {
            let url = `task_add.php?client_name=${encodeURIComponent(client_name)}&case_id=${case_id}&task_type=${task_type}&task_name=${task_name}`;
            document.getElementById('taskFrame').src = url;

            // Show the modal using Bootstrap 5 API
            let modalElement = document.getElementById('taskModal');
            let modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Add event listener to refresh page when modal is closed
            modalElement.addEventListener('hidden.bs.modal', function () {
                location.reload();
            }, { once: true }); // 'once' to prevent multiple bindings
        }
    });




    $(document).ready(function () {
        // Event delegation for any input in any form
        $(document).on('blur change', 'form :input', function () {
            var $currentInput = $(this);
            // Get the parent form of the current input
            var $form = $currentInput.closest('form');
            var task = $form.attr('action');
            // Prepare the data using the parent form's hidden fields
            var formData = {
                id: $form.find("input[name='id']").val(),
                table_name: $form.find("input[name='table_name']").val(),
                field_name: $currentInput.attr('name'),
                field_value: $currentInput.val()
            };

            // Avoid processing if input is hidden or not valid
            if (!formData.field_name || $currentInput.is(":hidden") || $currentInput.is(":button")) {
                return;
            }

            $.ajax({
                url: '../master_process.php?task=update_field', // Server-side update script
                type: 'POST',
                data: formData,
                success: function (response) {
                    console.log('Updated:', response);
                },
                error: function () {
                    console.log('Update failed!');
                }
            });
        });
    });




    $(document).on('change', "#task_type", function () {
        let task_type = $(this).val();

        $.ajax({
            url: '../master_process.php?task=get_task_list', // Server-side update script
            type: 'POST',
            data: { 'task_type': task_type },
            dataType: 'json',
            success: function (response) {
                console.log('Updated:', response);

                $('#task_name').empty(); //.append('<option value="">Select Task</option>');
                $.each(response, function (index, item) {
                    $('#task_name').append(
                        $('<option>', {
                            value: item.id,
                            text: item.task_name
                        })
                    );
                });
            },
            error: function () {
                console.log('Update failed!');
            }
        });
    });




    $(document).on('click', '.assign', function () {
        var arr = [];
        var x = $(this).data('id');
        var table = $(this).data('table');
        $("#case_list").val(x);
        $("#table_name").val(table);
        $("#assign_model").modal('show');

        $.ajax({
            type: 'POST',
            url: master_url + "Verifier_list",
            data: { table: table },
            success: function (data) {
                console.log(data);
                $('#allocated_to').empty().append('<option value="">Select Verifier</option>');
                let verifiers = (typeof data === 'string') ? JSON.parse(data) : data;
                verifiers.forEach(function (verifier) {
                    let displayText = verifier.full_name;
                    if (verifier.area_name) {
                        displayText += ' - ' + verifier.area_name;
                    }

                    $('#allocated_to').append(
                        $('<option></option>').val(verifier.id).text(displayText)
                    );
                });
            },
            error: function () {
                alert('Failed to fetch verifier list.');
            }
        });


    });


    $(document).on('click', '#btn_confirm', function () {
        var table = $("#table_name").val();
        var del_row = $($(this).closest("tr"));
        var case_list = $("#case_list").val();
        var allocated_to = $("#allocated_to").val();
        if (allocated_to == '') {
            notyf("Kinldy Select Verifier", "error");
        }
        else {

            $.ajax({
                'type': 'POST',
                'url': master_url + "case_allocated",
                'data': { 'case_list': case_list, 'allocated_to': allocated_to, 'table': table },
                success: function (data) {
                    //console.log(data);
                    var obj = JSON.parse(data);
                    notyf(obj.msg, obj.status);
                    //del_row.hide(500);
                    location.reload();
                }
            });
        }
    });

    //  Verification  Logic & Modal 


    $(document).on('click', '.verify_now', function () {
        $("#verify_model").modal('show');
        var table = $(this).data("table");
        var id = $(this).data("id");
        $("#upload_task_id").val(id);
        $("#upload_table_name").val(table);
    });


    $(document).on('click', "#verify_btn", function () {
        let main_task = $(this).val();
        let table_name = $("#upload_table_name").val();
        let task_id = $("#upload_task_id").val();
        let attachment = $("#target_v_attachment").val();

        let remarks = $("#task_remarks").val();
        $.ajax({
            url: '../master_process.php?task=verify_task', // Server-side update script
            type: 'POST',
            data: { 'table_name': table_name, 'task_id': task_id, 'v_attachment': attachment, 'remarks': remarks },
            dataType: 'json',
            success: function (obj) {
                console.log('Updated:', obj);
                notyf(obj.msg, obj.status);
                location.reload();
            },
            error: function () {
                console.log('Update failed!');
            }
        });
    });
    // Report Section 

    $(document).on('click', ".btn_report", function () {
        $("#report_model").modal('show');
        var table = $(this).data("table");
        var id = $(this).data("id");
        $("#report_task_id").val(id);
        $("#report_table_name").val(table);

        $.ajax({
            url: '../master_process.php?task=get_report', // Server-side update script
            type: 'POST',
            data: { 'table_name': table, 'task_id': id },
            //dataType:'json',
            success: function (obj) {
                console.log(obj);
                $("#txt_report").val(obj);
            },
            error: function () {
                console.log('Update failed!');
            }
        });

    });

    $(document).on('click', "#send_report", function () {
        var table = $("#report_table_name").val();
        var task_id = $("#report_task_id").val();
        var remarks = $("#txt_report").val();
        $.ajax({
            'type': 'POST',
            'url': master_url + "close_case",
            'data': { 'table_name': table, 'task_id': task_id, 'remarks': remarks },
            success: function (data) {
                //console.log(data);
                var obj = JSON.parse(data);
                notyf(obj.msg, obj.status);
                location.reload();
            }
        });
    });

    $(document).on('click', '.btn_review', function () {
        var table = $(this).data("table");
        var id = $(this).data("id");
        $("#review_table_name").val(table);
        $("#review_case_id").val(id);

        $.ajax({
            url: master_url + "get_customer_data",
            type: 'POST',
            data: { table: table, id: id },
            dataType: 'json',
            success: function (response) {
                console.log(response);
                if (response.status === 'success') {
                    $('#name').text(response.table);
                    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    const pdfExtensions = ['pdf'];
                    const docExtensions = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];

                    let attachments = response.data.v_attachment || response.data.attachment ;
                    let files = [];
                    let html = '';
                    const selectedAlready = $('#hidden_attachment_input').val().split(',').map(f => f.trim());

                    if (attachments) {
                        if (typeof attachments === 'string') {
                            try {
                                const parsed = JSON.parse(attachments);
                                files = Array.isArray(parsed) ? parsed : attachments.split(',').map(f => f.trim());
                            } catch (e) {
                                files = attachments.split(',').map(f => f.trim());
                            }
                        } else if (Array.isArray(attachments)) {
                            files = attachments;
                        }

                        if (files.length > 0) {
                            html += `
                            <div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="selectAllAttachments">
                                    <label class="form-check-label" for="selectAllAttachments"><strong>Select All</strong></label>
                                </div>
                            </div>
                            <div style="display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 10px; padding: 5px;" id="attachments_wrapper">
                        `;

                            files.forEach((file, index) => {
                                const fileExtension = file.split('.').pop().toLowerCase();
                                const isChecked = selectedAlready.includes(file) ? 'checked' : '';
                                // const fileUrl = '../upload/' + file;
                                const fileUrl = file;

                                html += `<div class="d-inline-block text-center" style="flex-shrink: 0;">`;
                                html += `
                                <div class="form-check mb-1">
                                    <input class="form-check-input attachment-check" type="checkbox" name="selected_files[]" value="${file}" id="fileCheck${index}" ${isChecked}>
                                    <label class="btn btn-border border-primary view-doc-btn file-label" for="fileCheck${index}" title='click to View Details' data-url="${file}" >${file}</label>
                                </div>
                            `;

                                // if (imageExtensions.includes(fileExtension)) {
                                //     html += `
                                //         <button class="view-doc-btn btn btn-primary" data-url="${file}">
                                //         <img src="${fileUrl}" class="img-thumbnail" style="max-width:300px; max-height:250px;"></button>`;
                                // } else if (pdfExtensions.includes(fileExtension)) {
                                //     html += `
                                //     <div style="display: inline-block; text-align: center;">
                                //         <button class="view-doc-btn btn btn-primary" data-url="${file}">View PDF Doc</button>
                                //     </div>
                                // `;

                                // } else if (docExtensions.includes(fileExtension)) {
                                //     html += `<div><i class="fa fa-file-word-o fa-2x"></i><br><button class="view-doc-btn btn btn-primary" data-url="${file}">View Doc</button></div>`;
                                // } else {
                                //     html += `<div><i class="fa fa-file-o fa-2x"></i><br>
                                //         <button class="view-doc-btn btn btn-primary" data-url="${file}">View Doc</button></div>`;
                                // }
                                
                                html += `</div>`;
                            });

                            html += `</div>`;
                        } else {
                            html = `<span class="text-muted">No attachment Preview found.</span>`;
                        }
                    } else {
                        html = `<span class="text-muted">No attachment found .</span>`;
                    }

                    $('#prev_attachment').html(html);

                    // Update hidden field on checkbox change
                    function updateSelectedFiles() {
                        let selectedFiles = [];
                        $('input[name="selected_files[]"]:checked').each(function () {
                            selectedFiles.push($(this).val().trim());
                        });
                        $('#hidden_attachment_input').val(selectedFiles.join(','));
                    }

                    // Attach checkbox event
                    $(document).off('change', '.attachment-check').on('change', '.attachment-check', updateSelectedFiles);

                    // Select All functionality
                    $(document).off('change', '#selectAllAttachments').on('change', '#selectAllAttachments', function () {
                        const checked = $(this).is(':checked');
                        $('.attachment-check').prop('checked', checked);
                        updateSelectedFiles();
                    });

                    updateSelectedFiles();

                    // if (table === 'task_physical') {
                    //     $('#btn_positive').hide();
                    // } else if (table === 'task_ito' || table === 'task_banking') {
                    //     $('#ai_remarks').css('display', 'none');
                    // }

                    $('#requirement').text(response.requirement);
                    $('#Verifier_remarks').val(response.data.remarks || '');
                    $('#admin_remark').val(response.admin_remark || '');
                    $("#ai_remarks").attr('data-table', table);
                    $("#ai_remarks").attr('data-id', id);
                    $("#review_model").modal('show');
                    $('.fields').html(response.html);
                } else {
                    console.log("Failed to load data.");
                }
            }
        });
    });

    $(document).on('click', '.reviewed', function () {
        $("#review_model").modal('show');
        var table = $(this).data("table");
        var id = $(this).data("id");
        $.ajax({
            'type': 'POST',
            'url': master_url + "close_case",
            'data': { 'table_name': table, 'task_id': task_id, 'remarks': remarks },
            success: function (data) {
                //console.log(data);
                var obj = JSON.parse(data);
                notyf(obj.msg, obj.status);
                location.reload();
            }
        });
        $("#upload_task_id").val(id);
        $("#upload_table_name").val(table);
    });



    // $(document).on('click', '.btn_negetive', function () {
    //     var case_status = $(this).data("status");
    //     //$('#case_status').val(case_status);
    //     // $('#admin_remark').val($('#Verifier_remarks').val());

    //     var c_reason = prompt("Please enter your reason:");
    //     if (c_reason !== null) {
    //         $('#admin_remark').val(c_reason);
    //     } else {
    //         console.log("User cancelled the input.");
    //     }
    // });

    // $(document).on('click', '.btn_cnv', function () {
    //     var case_status = $(this).data("status");
    //     //$('#case_status').val(case_status);
    //     // $('#admin_remark').val($('#Verifier_remarks').val());

    //     var c_reason = prompt("Please enter your reason:");
    //     if (c_reason !== null) {
    //         $('#admin_remark').val(c_reason);
    //     } else {
    //         console.log("User cancelled the input.");
    //     }
    // });

    $(document).on('click', '.btn_positive, .btn_negetive, .btn_cnv', function () {
        $("#review_model").modal('show');
        var table = $('#review_table_name').val();
        var task_id = $('#review_case_id').val();
        var admin_remark = $('#admin_remark').val();
        var r_status = $(this).data("status");
        var case_status = $(this).data("status");
        $('#case_status').val(case_status);

        $.ajax({
            'type': 'POST',
            'url': master_url + "get_remarks",
            'data': { 'table_name': table, 'task_id': task_id, 'case_status': r_status, 'admin_remark': admin_remark, 'case_status': case_status },
            success: function (data) {
                console.log(data);
                $('#admin_remark').val(data);
                // 			var obj = JSON.parse(data);
                // 			notyf(obj.msg, obj.status);
                // 			location.reload();
            }
        });
    });


    $(document).on('click', '.save_btn', function () {
        $("#review_model").modal('show');
        var table = $('#review_table_name').val();
        var task_id = $('#review_case_id').val();
        var admin_remark = $('#admin_remark').val();
        var r_status = $("#case_status").val();
        var attachment = $("#hidden_attachment_input").val();
        var docs_in_report = $("#docs_in_report").is(":checked") ? "YES" : "NO";
        
        if(r_status=='' )
        {
            alert("Kindly Select Case Status");
        }
        else if( admin_remark =='')
        {
            alert("Admin Remark Missing");
        }
        else{
            $.ajax({
                type: 'POST',
                url: master_url + "close_case",
                data: {
                    'table_name': table,
                    'task_id': task_id,
                    'case_status': r_status,
                    'admin_remark': admin_remark,
                    'report_attachment': attachment,
                    'docs_in_report': docs_in_report
                },
                success: function (data) {
                    var obj = JSON.parse(data);
                    notyf(obj.msg, obj.status);
                    location.reload();
                }
            });
        }
    });



   $(document).on('click', '#ai_remarks', function () {
    $("#aiChatModal").modal('show');

    var table = $('#ai_remarks').data('table');
    var id = $('#ai_remarks').data('id');
    var task_id = $('#review_case_id').val();
    var admin_remark = $('#admin_remark').val();
    var verifier_remarks = $('#Verifier_remarks').val();
    var r_status = $(this).data("status");
    $('#case_status').val(r_status);

    // === Ask user for extra prompt ===
    // var userPrompt = prompt("Enter additional instructions for AI:", "");
    // if (userPrompt === null) {
    //     return; // user cancelled
    // }

    $.ajax({
        type: 'get',
        url: base_url + "ai?task_table=" + table 
                     + "&task_id=" + id 
                    //  + "&remarks=" + encodeURIComponent(verifier_remarks) 
                    // + "&remarks=" + encodeURIComponent(admin_remark) 
                     + "&prompt=" + encodeURIComponent(admin_remark),
        dataType: 'JSON',
        beforeSend: function () {
            $('#ai_remarks').html('Please wait ...');
        },
        success: function (data1) {
            if (data1.response) {
                $("#admin_remark").val(data1.response);
            } else {
                alert("AI did not return a response.");
            }
        },
        complete: function () {
            $('#ai_remarks').html('Generate With AI');
        }
    });
});



    //=====Chaneg Status BUTTON =========//
    $(document).on('click', '.change_task_status', function () {
        var id = $(this).attr("data-id");
        var table = $(this).attr("data-table");
        var status = $(this).attr("data-status");
        bootbox.confirm({
            message: "Do you really want to change the status?",
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
                        'url': master_url + "change_status",
                        'data': { 'id': id, 'table_name': table, 'status': status },
                        success: function (data) {
                            console.log(data);
                            var obj = JSON.parse(data);
                            notyf(obj.msg, obj.status);
                            location.reload();
                        }
                    });
                }
            }
        });
    });


    $(document).on('change', '.custom-file-uploader', function () {
        const files = this.files;
        if (!files.length) return;

        const table = $(this).data('table');
        const field = $(this).data('field');
        const size = $(this).data('size') || '';

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('uploadimg[]', files[i]);  // important!
        }

        formData.append('table', table);
        formData.append('field', field);
        formData.append('size', size);

        $("#update_btn").prop('disabled', true);

        $.ajax({
            url: sys_url + "multi_upload",  // double-check this URL is correct
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            cache: false,

            beforeSend: function () {
                $("#custom_file_preview").html(
                    `<h3 id="upload_waiting" class="text-center text-muted">
                    <i class='fa fa-spinner fa-spin' style='font-size:40px'></i><br> 
                    Please Wait While Uploading the files..
                </h3>`
                );
            },

            success: function (data) {
                let obj;
                try {
                    obj = JSON.parse(data);
                } catch (e) {
                    notyf("Invalid JSON response", "error");
                    $("#update_btn").prop('disabled', false);
                    return;
                }

                $("#hidden_attachment_input").val(obj.img_name);
                $("#custom_file_preview").empty();

                obj.id.forEach(val => {
                    const ext = val.split('.').pop().toLowerCase();
                    if (["jpg", "jpeg", "png", "gif"].includes(ext)) {
                        $("#custom_file_preview").append(`<img src="../upload/${val}" width="100px" height="100px" class="img-thumbnail">`);
                    } else {
                        $("#custom_file_preview").append(`<a href="../upload/${val}" download class="btn btn-border border-secondary m-2 text-primary"><i class="fa fa-download"></i> Download</a>`);
                    }
                });

                notyf(obj.msg || "Uploaded", obj.status || "success");
                $("#update_btn").prop('disabled', false);
            },

            complete: function () {
                $("#upload_waiting").hide();
            },

            error: function (xhr, status, error) {
                notyf("Upload failed: " + error, "error");
                $("#update_btn").prop('disabled', false);
            }
        });
    });


    $(document).on('click', '.task_edit', function () {
        var table = $(this).data("table");
        var id = $(this).data("id");
        $("#case_table_name").val(table);
        $("#edit_case_id").val(id);
        $("#task_edit_model").modal('show');
        $.ajax({
            url: master_url + "get_case_data",
            type: 'POST',
            data: { table: table, id: id },
            dataType: 'json',
            success: function (response) {
                console.log(response);
                if (response.status === 'success') {
                    $('#name').text(response.table);
                    let html = '';
                    $("#task_edit_model").modal('show');
                    $('.fields').html(response.html);
                } else {
                    console.log("Failed to load data.");
                }
            }
        });
    });


   $(document).on('click', '.update_case_data', function () {
    var form = $('.edit_case_form');

    $.ajax({
        url: master_url + "update_case_data",
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function (response) {
            console.log(response);
            if (response.status === 'success') {
                notyf(response.msg, response.status);
                // $("#task_edit_model").modal('hide');
                window.location.reload();
            } else {
                console.log("Failed to update data.");
            }
        }
    });
});


   $(document).on('click', '#chat_btn', function () {
    var form = $('.edit_case_form');

    $.ajax({
        url: "ai_chat.php",
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function (response) {
            console.log(response);
            if (response.status === 'success') {
                notyf(response.msg, response.status);
                // $("#task_edit_model").modal('hide');
                window.location.reload();
            } else {
                console.log("Failed to update data.");
            }
        }
     });
    });

