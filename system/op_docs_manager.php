<?php require_once("all_header.php"); 
        $folder = "../upload/"; // specify the folder where files are stored
?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Document Manager</h1>
            <div class="container">
                <div class='card'>
                <div class='card-header'>
                    <div class='row'> 
                     <div class='col-md-7'> 
                            <form action="" method="post" enctype="multipart/form-data">
                                <b> Upload New File</b><br>
                            <input type="file" name="file" class="form-control-file" required>
                            <button type="submit" name="upload" class="btn btn-success btn-sm mt-2">Upload</button>
                            </form>
                     </div>
                     <div class='col-md-5'> 
                              <!-- Rename Form -->
                                <?php
                                if (isset($_GET['action']) && $_GET['action'] == 'rename' && isset($_GET['file'])) {
                                    $fileToRename = $_GET['file'];
                                    echo '<b>Rename File: ' . $fileToRename . '</b>';
                                ?>
                                
                                
                                    
                                    <form action="?action=rename&file=' . $fileToRename . '" method="post" class="form-inline">
                                        
                                        <div class="input-group mb-3">
  <input type="text" class="form-control input-sm" name="newName" placeholder="Enter new file name" required>
  <div class="input-group-append">
    <span class="btn btn-primary" >Rename</span>
  </div>
</div>
                                  
                                    </form>
                                
                                <?php }   ?>
                     </div>
                    </div>
                </div>
                <div class='card-body'>
                    
               
                 <?php
      
        // Function to display files
        function displayFiles($folder) {
            global $base_url;
            $files = scandir($folder);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo '<div class="row align-items-center mb-1">';
                    echo '<div class="col"> <i class="fa fa-file"></i> ' . $file . '</div>';
                    echo '<div class="col-2">
                     <span data-url="'.$base_url.'upload/'.$file.'" class="docs_link btn btn-dark btn-sm" title ="Copy"><i class="fa fa-link"></i></span>
                     <a href="?action=rename&file=' . $file . '" class="btn btn-primary btn-sm" title ="Rename"><i class="fa fa-pencil"></i></a>
                     <a href="?action=delete&file=' . $file . '" class="btn btn-danger btn-sm" title ="Delete"><i class="fa fa-trash"></i></a>
                     </div>';
                    echo '</div>';
                }
            }
        }

        // Function to delete file
        function deleteFile($folder, $file) {
            $path = $folder . $file;
            if (file_exists($path)) {
                unlink($path);
                echo '<div class="alert alert-success alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
							
								    File deleted successfully!							  
							</div>
						</div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
								   File not found!							  
							</div>
						</div><div class="alert alert-danger" role="alert"></div>';
            }
        }

        // Function to rename file
        function renameFile($folder, $oldName, $newName) {
            $oldPath = $folder . $oldName;
            $newPath = $folder . $newName;
            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
                echo '<div class="alert alert-success alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
							
								    File renamed successfully!							  
							</div>
						</div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
							    File not found!								  
							</div>
						</div>';
            }
        }

        // Handle file operations
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action == 'delete' && isset($_GET['file'])) {
                $fileToDelete = $_GET['file'];
                deleteFile($folder, $fileToDelete);
            } elseif ($action == 'rename' && isset($_GET['file']) && isset($_POST['newName'])) {
                $fileToRename = $_GET['file'];
                $newName = $_POST['newName'];
                renameFile($folder, $fileToRename, $newName);
            }
        }

        // Display files
        echo '<div class="mb-3">';
        echo '<h3>Files</h3>';
        displayFiles($folder);
        echo '</div>';
        ?>
        <!-- Upload Form -->
        <div>
            
            <?php
            // Handle file upload
            if (isset($_POST['upload'])) {
                $uploadedFile = $_FILES['file'];
                $fileName = $uploadedFile['name'];
                $tempFile = $uploadedFile['tmp_name'];
                $destination = $folder . $fileName;
                if (move_uploaded_file($tempFile, $destination)) {
                    echo '<div class="alert alert-success alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
							    File uploaded successfully!						  
							</div>
						</div>';
                } else {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							<div class="alert-icon">
								<i class="far fa-fw fa-bell"></i>
							</div>
							<div class="alert-message">
							    >Failed to upload file!						  
							</div>
						</div>';
                }
            }
            ?>
        </div>

      
    
						</div>
						</div>
						</div>

		</div>
	</main>
<?php 
require_once("footer.php"); ?>

<script>
$(document).on("click",".docs_link",function(){
	var x = $(this).data('url');
	navigator.clipboard.writeText(x);
	//notyf("URL Copied <b>" + x +"</b>","success");
	notyf("URL Copied Successfully ","success");
});
</script>