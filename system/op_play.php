<?php include('header.php')?> 
<!-- Include the Ace editor CDN -->
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src/ace.js"></script>
<!-- Include the Ace editor theme CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src/theme-twilight.css">
<!-- Include the Ace editor mode PHP -->
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src/mode-php.js"></script>
<!-- Include the Ace editor ext-language_tools (for autocomplete and snippets) -->
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src/ext-language_tools.js"></script>
<style>
  /* Set the editor's and output's size and position */
  #editor-container, #output-container {
    position: absolute;
    top: 16px;
    bottom: 0;
    overflow: auto;
  }

  #editor-container {
    left: 0;
    right: calc(50% - 0px); /* Initial width of the editor */
    z-index:1;
  }

  #output-container {
    right: 0;
    left: calc(50% + 0px); /* Initial width of the output */
    border-left: 0px solid #f0f0f0; /* Divider */
  }

  /* Divider styles */
  #divider {
    position: absolute;
    top: 0px;
    width: 4px;
    bottom: 0;
    /*background-color: transparent;*/
    background-color: red;
    cursor: ew-resize; /* East-West (horizontal) resize cursor */
    left: calc(50% - 5px); /* Initial position of the divider */
    z-index: 100; /* Ensure the divider is above the editor and output */
    
  }
  
  /* Set the editor's size */
  #editor {
    position: absolute;
    top: 16px;
    left: 0;
    bottom: 0;
    width: 100%;
  }
  /* Set the output's size and position */
  #output {
    position: absolute;
    top: 16px;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    border: 1px solid #ccc;
    overflow: auto;
    display: none; /* Hide the output initially */
  }
</style>
</head>
<body>
  
<div>
  <a href='op_dashboard.php' class='btn btn-border border-light btn-sm'><i class='fa fa-home'></i> Home</a>
  <button onclick="newFile()" class='btn btn-danger btn-sm'><i class='fa fa-file'></i> New</button>
  <select id="fileList" style='height:24px;margin-top:2px;'></select>
  <button onclick="openFile()" class='btn btn-primary btn-sm'> <i class='fa fa-folder'></i> Open</button>
  <button onclick="saveFile()" class='btn btn-success btn-sm'> <i class='fa fa-save'></i> Save</button>
  <label class='badge bg-warning' style='height:24px;'><input type="checkbox" id="liveEdit"> Live Edit</label>
  <button onclick="toggleWordWrap()" class='btn btn-dark btn-sm'> <i class='fa fa-arrows-alt-h'></i>Wrap</button>
</div>

<div id="editor-container">
  <div id="editor"></div>
</div>
<div id="divider"></div>
<div id="output-container">
  <iframe id="output"></iframe>
</div>

<script>
  var editor = ace.edit("editor");
  editor.setTheme("ace/theme/twilight");
  editor.getSession().setMode("ace/mode/php");
  editor.getSession().setTabSize(2);
  editor.setOptions({
    enableBasicAutocompletion: true,
    enableSnippets: true,
    enableLiveAutocompletion: true
  });

  var output = document.getElementById('output');

  function newFile() {
    var filename = prompt("Enter file name:");
    if (filename) {
      fetch('op_player.php?save=' + filename, {
        method: 'POST',
        body: ''
      })
      .then(() => {
        refreshFileList();
        document.getElementById('fileList').value = filename;
        openFile();
      });
    }
  }

  function refreshFileList() {
    fetch('op_player.php?list')
      .then(response => response.json())
      .then(data => {
        var fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        data.forEach(file => {
          var option = document.createElement('option');
          option.text = file;
          option.value = file;
          fileList.add(option);
        });
      });
  }

  function openFile() {
    var filename = document.getElementById('fileList').value;
    fetch('op_player.php?load=' + filename)
      .then(response => response.text())
      .then(data => editor.setValue(data));
  }

  function saveFile() {
    var filename = document.getElementById('fileList').value;
    if (filename) {
     if (window.confirm("Are you sure you want to save the file?")) {
          fetch('op_player.php?save=' + filename, {
            method: 'POST',
            body: editor.getValue()
          });
        }
    }
    updateOutput();
  }
  
   function toggleWordWrap() {
    editor.getSession().setUseWrapMode(!editor.getSession().getUseWrapMode());
  }
   

  // Live Edit functionality
  var liveEditCheckbox = document.getElementById('liveEdit');
  liveEditCheckbox.addEventListener('change', function() {
    if (liveEditCheckbox.checked) {
      updateOutput();
      editor.getSession().on('change', updateOutput);
      output.style.display = 'block';
    } else {
      editor.getSession().off('change', updateOutput);
      output.style.display = 'none';
    }
  });

function updateOutput() {
  var code = editor.getValue();
  var filename = document.getElementById('fileList').value;
  console.log(filename);
  let file_path ='../public/'+filename;
  fetch(file_path, {
    method: 'POST',
    body: code
  })
  .then(response => response.text())
  .then(data => {
    output.contentWindow.document.open();
    output.contentWindow.document.write(data);
    output.contentWindow.document.close();
  });
}


  refreshFileList();

  var divider = document.getElementById('divider');
  var editorContainer = document.getElementById('editor-container');
  var outputContainer = document.getElementById('output-container');
  var isDragging = false;

  // Event listeners for mouse down, move, and up
  divider.addEventListener('mousedown', function(e) {
    isDragging = true;
  });

  document.addEventListener('mousemove', function(e) {
    if (isDragging) {
      var x = e.pageX;
      var editorWidth = x - editorContainer.offsetLeft;
      editorContainer.style.right = window.innerWidth - x + 'px';
      outputContainer.style.left = x + 'px';
    }
  });

  document.addEventListener('mouseup', function(e) {
    isDragging = false;
  });

</script>

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