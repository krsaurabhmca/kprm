<footer class="footer">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">
							&copy; <?= date('Y'); ?> <?= $inst_name; ?>   
							</p>
						</div>
						<div class="col-6 text-end">
							<ul class="list-inline">
								<li class="list-inline-item">
								   	<?php
                            if(isset($_SESSION['admin_data']) && $_SESSION['admin_data']['user_outh'] =='yes'){
                             $sdata = $_SESSION['admin_data'];
                             $data = get_data('op_user',$sdata['user_id'])['data'];
                             ?>
                            
                            <span data-url='show_user' data-id='<?= $data['user_name'] ?>' data-code='<?= $data['user_pass'] ?>' class='login_as badge bg-danger' >
                                <i class='fa fa-arrow-left'></i> Back To Admin</span> |
                           
                        <?php } ?> 
								    
									Planted By <a href="<?= @$dev_url; ?>" target="_blank" class="text-muted"><strong><?= $dev_by ?></strong></a>
								</li>
								
							
							</ul>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>

	<!-- =========== View Data IN modal ========= -->
<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id='view_data'>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalCenterTitle"></h3>
      	<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-0">
      </div>
    </div>
  </div>
</div>


<!--<div class="modal fade" id="docModal" tabindex="-1" aria-hidden="true">-->
<!--  <div class="modal-dialog modal-lg" style="max-width: 90%;">-->
<!--    <div class="modal-content">-->
<!--      <div class="modal-header">-->
<!--        <h5 class="modal-title">📄 Document Viewer</h5>-->
<!--        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>-->
<!--      </div>-->
<!--      <div class="modal-body" style="height: 80vh; position: relative;">-->
<!--        <div id="docLoader" style="text-align:center; position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">-->
<!--          ⏳ Loading...-->
<!--        </div>-->
<!--        <iframe id="docIframe" style="width:100%; height:100%; border:none; display:none;"></iframe>-->
<!--      </div>-->
<!--    </div>-->
<!--  </div>-->
<!--</div>-->


<!--<style>-->
<!--.modal-dialog {-->
<!--  transition: none !important;-->
<!--  z-index: 1055;-->
<!--}-->

<!--</style>-->


<!--<div class="modal fade" id="docModal" tabindex="-1" aria-hidden="true">-->
<!--  <div class="modal-dialog modal-dialog-slide modal-dialog-right">-->
<!--    <div class="modal-content" style="height: 100vh; border-radius: 0;">-->
<!--      <div class="modal-header">-->
<!--        <h5 class="modal-title">📄 Document Viewer</h5>-->
<!--        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>-->
<!--      </div>-->
<!--      <div class="modal-body" style="height: calc(100vh - 56px); position: relative;">-->
<!--        <div id="docLoader" style="text-align:center; position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">-->
<!--          ⏳ Loading...-->
<!--        </div>-->
<!--        <iframe id="docIframe" style="width:100%; height:100%; border:none; display:none;"></iframe>-->
<!--      </div>-->
<!--    </div>-->
<!--  </div>-->
<!--</div>-->


<!-- Modal -->

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
	<div class="offcanvas-header">
		<h4 id="offcanvasRightLabel">Show/Hide Columns 
		<?php if($user_type=='DEV' or $user_type=='ADMIN'){ ?>
		<a href="<?= $base_url?>system/op_column_sorting?table_name=<?= $table_name?>" title="Sort Columns" class="btn btn-primary btn-sm"><i class="fa fa-arrows-alt"></i></a>
		<?php } ?>
		</h4>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
	
		<div class="card-body">
			<table>
			<?php 
			
            $col_list =  get_all('op_master_table','*',array('table_name'=>$table_name,'status'=>'ACTIVE','is_edit'=>'YES'))['data'];
            foreach($col_list as $col)
            {
              echo "<tr><td>". add_space($col['column_name']) ." </td><td>". $switch_str= show_switch('op_master_table',  $col['id'], 'show_in_table', $col['show_in_table'] ) ."</td></tr>";
            }
            ?>
			</table>	
		</div>
	</div>
</div>

<script src="<?= $base_url ?>system/js/app.js"></script>
<script src="<?= $base_url ?>system/js/datatables.js"></script>

<!-- This is data table -->
<!-- start - This is for export functionality only -->
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.flash.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/jszip.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/pdfmake.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/ex-js/vfs_fonts.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.html5.min.js"></script>
<script src="<?= $base_url ?>system/DataTables-1.10.15/extensions/Buttons/js/buttons.print.min.js"></script>
<!-- end - This is for export functionality only-->

<script src="<?= $base_url ?>system/js/validate.js"></script>
<script src="<?= $base_url ?>system/js/shortcut.js"></script>
<script src="<?= $base_url ?>system/js/bootbox.all.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/js-base64@2.5.2/base64.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-ko-KR.min.js"></script>
<script src="<?= $base_url ?>system/js/op.js"></script>


<script>
	document.addEventListener("DOMContentLoaded", function() {
		
		$('.data-tbl').dataTable({
			responsive: true,
			fixedHeader: true,
			aLengthMenu: [
				[25, 50, 100, 500, -1],
				[25, 50, 100, 500, "All"]
			],
		//	buttons: ["pdf", "print"],
			iDisplayLength: 25
		});

		$('.report-tbl').DataTable( {
		dom: 'Bfrtip',
		buttons: [
			'copy', 'csv', 
			{
			    extend: 'excelHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                footer:true
			}
			, 'print',
			{
			    extend: 'pdfHtml5',
                orientation: 'portrait',
                pageSize: 'LEGAL',
                footer:true
			}
		]
		} );

	});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    //const modal = new bootstrap.Modal(document.getElementById('docModal'));
    //const iframe = document.getElementById('docIframe');
    //const loader = document.getElementById('docLoader');

    // Delegate event to dynamically loaded buttons
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.view-doc-btn');
        if (!btn) return;

        // const url = '../upload/' + btn.getAttribute('data-url');
        let dataUrl = btn.getAttribute('data-url');
        let url = "";
        
        if (dataUrl.startsWith("http")) {
            url = "https://kprm.co.in/viewer.php?file=" + dataUrl;
        } else {
            url = "https://kprm.co.in/viewer.php?file=https://kprm.co.in/upload/" + dataUrl;
        }
        openDocInPopup(url);

        
        // // Reset iframe
        // iframe.style.display = 'none';
        // loader.style.display = 'block';
        // iframe.src = ''; // Clear old content
        // iframe.src = url; // Set new content
        // modal.show();
    });

    // iframe.onload = () => {
    //     loader.style.display = 'none';
    //     iframe.style.display = 'block';
    // };
});
</script>



<script>
function openDocInPopup(url) {
  const popupWidth = 1000;
  const popupHeight = 800;
  const left = (screen.width - popupWidth) / 2;
  const top = (screen.height - popupHeight) / 2;

  const win = window.open('', 'docPopup', `width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`);

  if (win) {
    win.document.write(`
      <html>
      <head>
        <title>📄 Document Viewer</title>
        <style>
          body, html { margin:0; padding:0; height:100%; overflow:hidden; }
          #loader { text-align:center; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:18px; }
          iframe { width:100%; height:100%; border:none; display:none; }
        </style>
      </head>
      <body>
        <div id="loader">⏳ Loading...</div>
        <iframe src="${url}" onload="this.style.display='block'; document.getElementById('loader').style.display='none';"></iframe>
      </body>
      </html>
    `);
    win.document.close();
  } else {
    alert('Popup blocked! Please allow popups for this site.');
  }
}



</script>




<?php if(isset($res['json']) and $res['json']<>'') { ?>
    <script>
    	// Server Datatable Creation 
         $(document).ready(function () {
                $('#server_table').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "responsive": true,
                    "ajax": {
                        "url": "<?= $base_url ?>system/datatable.php?table_name=<?= $table_name ?>", // Path to your PHP script
                        "type": "POST",
                    },
                    "columns":  <?php echo $res['json']; ?>
                });
        });
    </script>
<?php } ?>

<script>
  let idleTime = 0;
  const LOGOUT_TIME = 500; // seconds
  const DISPLAY_DELAY = 60; // show timer after 30 seconds idle
  let countdownInterval;

  const timerBox = document.getElementById("idle-timer");
  const timerCount = document.getElementById("idle-count");

  // Reset idle time on activity
  function resetIdle() {
    idleTime = 0;
    timerBox.style.display = "none";
    clearInterval(countdownInterval);
  }

  // Main idle checker (runs every 1 second)
  setInterval(() => {
    idleTime++;

    // Auto logout
    if (idleTime >= LOGOUT_TIME) {
      window.location.href =
        "https://kprm.co.in/system/system_process.php?task=auto_logout";
    }

    // Show countdown ONLY after 30 seconds idle
    if (idleTime >= DISPLAY_DELAY) {
      const remaining = LOGOUT_TIME - idleTime;
      timerBox.style.display = "block";
      timerCount.innerText = remaining;
    }
  }, 1000);

  // Detect user activity
  ["mousemove", "keydown", "click", "scroll", "touchstart"].forEach(event =>
    document.addEventListener(event, resetIdle)
  );
</script>



</body>
</html>