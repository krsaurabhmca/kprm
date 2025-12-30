<?php require_once('all_header.php');

if (isset($_GET['table_id']) and $_GET['table_id'] != '') 
{
    $table_id = $_GET['table_id'];
    $table_name  = get_data('op_table',$table_id,'table_id')['data'];
}
if (isset($_GET['table_name']) and $_GET['table_name'] != '') 
{
    $table_name = $_GET['table_name'];
}
?>
<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <h1 class="h3 mb-3 float-start col-8">
            
            Bulk Import
           
            </h1>
            <div class="float-end mb-3 col-4">
                <form action='' method='get'>
                    <?php if($user_type=='DEV') { 
                        $table_list = table_list() ;   
                    ?> 
                    <select name='table_name' id='table_name' onChange='submit()' class='select2 form-select'>
                        <?= dropdown($table_list['data'] ,$table_name); ?>
                    </select>
                    <?php  } else { ?> 
                    <select name='table_id' id='table_name' onChange='submit()' class='select2 form-select'>
                        <?= dropdown_list('op_table','id','table_id',$table_id); ?>
                    </select>
                    <?php  } ?> 
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Import data for Table <span class='text-primary'><?= add_space(@$table_name); ?>
                        <div class="float-end">
                        <?php 
                        if (isset($_GET['table_id']) or  isset($_GET['table_name']) ) 
                        {
                            ?>
                        <a href='<?= $base_url ?>system/system_process.php?task=bulk_export&table=<?=$table_name?>' class='btn btn-danger btn-sm'> <i class='fa fa-download'></i> Download Template </a>
                        <?php  } ?>
                      
                        </div>
                        </h5>
                    </div>
                    <div class="card-body">
                    <form action='<?= $base_url ?>system/system_process?task=bulk_import' method='post' enctype="multipart/form-data">
                        <div class='row'>
                            <!--<input type="hidden" name='pkey' value="document_name">-->
                        <div class='col-md-4'>
                            <div class="form-group mb-2">
                                    <label>Select Unique Key </label>
                                    <select class="form-select select2" name='pkey'  required >
                                    <?php echo dropdown_where('op_master_table','column_name','display_name',array('table_name'=>$table_name,'is_edit'=>'YES'), $table_id); ?>
                                    </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
						<div class="form-group">
							<div class="form-group has-success">
                            <label>Choose CSV File </label>
                                <input type='hidden' name='table' value='<?= $table_name ?>'>
								<input type="file" name='file' class='form-control'  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
							</div>
						</div>
					    </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-group has-success">
                                <label class="control-label">Click to Upload</label><br>
                                <button type="submit" class='btn btn-success btn-md' <?= isset($table_name)?'':'disabled' ?> > Import Data </button>
                                </div>
                            </div>
					    </div>
                        </div>
                    </form>
                    </div>
                </div>


                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Import SQL File
                       
                        </h5>
                    </div>
                    <div class="card-body">
                    <form action='<?= $base_url ?>system/system_process?task=import_sql' method='post' enctype="multipart/form-data">
                        <div class='row'>
                       <div class='col-md-3'>
                           
                        </div>
                        
                        <div class="col-md-4">
						<div class="form-group">
							<div class="form-group has-success">
                            <label>Choose SQL File </label>
                               <input type="file" name='file' class='form-control'  accept=".sql,.txt" required>
							</div>
						</div>
					    </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-group has-success">
                                <label class="control-label">Click to Upload</label><br>
                                <button type="submit" class='btn btn-success btn-md' > Import SQL </button>
                                </div>
                            </div>
					    </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>