<?php require_once('all_header.php');

if(isset($_REQUEST['table_name']))
{
    $filter['table_name'] = $table_name = $_REQUEST['table_name'];
    $filter['status'] = 'ACTIVE';
    $filter['is_display'] = 'YES';
    $filter['is_edit'] = 'YES';
}

$res = get_all('op_master_table','*',$filter, 'display_id');
?>
<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">
        <a href='op_table_manager?table_name=<?=$table_name?>' class='px-3 text-dark'> <i class='fa fa-arrow-left'></i> </a>

        Arrange Column </h1>
         
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    
                                    Arrange Column of 
                                    <?php 
                                    echo $table_name;
                                    ?>
                                   
                                    <div class="page-title-right">
                                    <button class='btn btn-success btn-sm' id='save'>
                                        Save 
                                    </button>
                                    </form>
                                    </div>

                                </div>
                    </div>
                    <div class="card-body">
                        
                    <ul id="simpleList" class="list-group">
                    <?php
                    if($res['count'] > 0)
                    {
                        foreach((array) $res['data'] as $row)
                        {
                            $display_id =$row['id'];
                            $details =$row['input_type'];
                            $display = ($row['display_name']!='')?" (". $row['display_name'] .")":"";
                            $title =$row['column_name'] . $display;
                            $cls = $req= '';
                            if($details=='Label')
                            {
                                $cls = 'label';
                            }

                            if($row['is_required']=='YES')
                            {
                                $req = '⭐';
                            }
                            
                            // echo "<li class='list-group-item $cls' data-id='$display_id'>";
                            // echo "<i class='fa fa-arrows-alt' ></i> ".  $title ." <span class='badge bg-danger'>".$details. "</span> $req </li>";
                            
                            echo "<li class='list-group-item $cls d-flex align-items-center' data-id='$display_id'>
                                <input type='checkbox' class='me-2 row-check'>
                                 <i class='fa fa-arrows-alt'></i>  $title 
                                <span class='badge bg-danger ms-2'> $details </span> $req
                              </li>";
                                ?>
                        <?php
                        } 
                    }
                    ?>
                                       
                    </ul> 

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>



<script src="https://SortableJS.github.io/Sortable/Sortable.js"></script>
    <script>
        $(document).on('click','.ls-modal', function(e){
          e.preventDefault();
          $('#view_data').modal('show').find('.modal-title').html($(this).attr('data-title'));
          $('#view_data').modal('show').find('.modal-body').load($(this).attr('href'));
        });
    </script>
    
 <script>
  Sortable.create(simpleList, { 
    multiDrag: true,
    selectedClass: "bg-light", // when selected
    animation: 150
  });

  // Checkbox click = toggle selection
  $(document).on("change", ".row-check", function(){
      let li = $(this).closest("li")[0];
      if(this.checked){
          li.classList.add("selected");
      } else {
          li.classList.remove("selected");
      }
  });

  // Save button
  $(document).on('click', '#save', function(){
      var a = [];
      $('#simpleList .list-group-item').each(function() {
         a.push($(this).attr('data-id'));
      });
      $.ajax({
        type:'POST',
        url:'system_process?task=sort_column',
        data:{columns:a},
        success:function(data){
            var obj = JSON.parse(data);
            notyf(obj.msg,obj.status);
        }
      });
  });
</script>
