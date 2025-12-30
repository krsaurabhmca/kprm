<?php
require_once("../system/all_header.php"); 
$link = decode($_GET['link']);

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  
  <main class="content">
            <div class="container-fluid p-0"> 
                
            <div class="card">
                
              <div class="card-header">
                      <h3>📊 Excel File Upload & Table Structure Builder</h3>
              </div>   
      

            <div class="card-body">

  <!-- Table Name Dropdown -->
  <div class="mb-3 row">
    <label for="table_name" class="col-sm-3 col-form-label">Select Table Name:</label>
    <div class="col-sm-9">
      <select name="table_name" id="table_name" class="form-select select2">
        <?= dropdown_list('clients','id','name', $link['id']); ?>
      </select>
    </div>
  </div>

  <!-- Excel File Upload -->
  <div class="mb-3 row">
    <label for="excel_file" class="col-sm-3 col-form-label">Upload Excel File:</label>
    <div class="col-sm-9">
      <input type="file" id="excel_file" class="form-control">
    </div>
  </div>

  <!-- Sheet Selector -->
  <div class="mb-3 row">
    <label for="sheet_selector" class="col-sm-3 col-form-label">Select Sheet:</label>
    <div class="col-sm-9">
      <select id="sheet_selector" class="form-select"></select>
    </div>
  </div>

  <!-- Start Row Input -->
  <div class="mb-3 row">
    <label for="start_row" class="col-sm-3 col-form-label">Start Row (0 = Header):</label>
    <div class="col-sm-3">
      <input type="number" id="start_row" class="form-control" value="0" min="0">
    </div>
  </div>

  <!-- Excel Data Display -->
  <div id="excel_data" class="table-responsive mb-3"></div>

  <!-- Create Table Button -->
  <div class="text-end">
    <button class="btn btn-success" id="create_table_btn">🧱 Create Table From Selected</button>
  </div>

</div>
</div>

</main>

        
<?php        require_once("../system/footer.php"); ?>
  <script>
    let workbookData = null;
    let currentSheetData = [];

    $('#excel_file').on('change', function(e) {
      const file = e.target.files[0];
      const reader = new FileReader();
      reader.onload = function(evt) {
        const data = new Uint8Array(evt.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        workbookData = workbook;

        // Fill sheet selector
        let html = '';
        workbook.SheetNames.forEach((name, i) => {
          html += `<option value="${name}">${name}</option>`;
        });
        $('#sheet_selector').html(html);

        loadSheet(workbook.SheetNames[0]); // load first sheet initially
      };
      reader.readAsArrayBuffer(file);
    });

    $('#sheet_selector, #start_row').on('change', function() {
      const sheet = $('#sheet_selector').val();
      loadSheet(sheet);
    });

    function loadSheet(sheetName) {
      const worksheet = workbookData.Sheets[sheetName];
      const rawData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      const startRow = parseInt($('#start_row').val()) || 0;

      if (rawData.length <= startRow) {
        $('#excel_data').html('<div class="text-danger">Not enough rows in sheet.</div>');
        return;
      }

      currentSheetData = rawData.slice(startRow);
      const headers = currentSheetData[0];

      let html = '<table class="table table-bordered"><thead><tr>';
      headers.forEach((heading, i) => {
        html += `<th><input type="checkbox" class="col-check" data-index="${i}" checked> ${heading}</th>`;
      });
      html += '</tr></thead><tbody>';

      for (let i = 1; i < currentSheetData.length; i++) {
          html += '<tr>';
        
          currentSheetData[i].forEach(cell => {
            const cellValue = (cell ?? '').toString();
            const displayValue = cellValue.length > 50 ? cellValue.substring(0, 50) + '…' : cellValue;
            html += `<td title="${cellValue.replace(/"/g, '&quot;')}">${displayValue}</td>`;
          });
        
          html += '</tr>';
        }

      html += '</tbody></table>';

      $('#excel_data').html(html);
    }

    $('#create_table_btn').on('click', function() {
      let tableName = $('#table_name').val().trim();
      if (tableName == '') return alert('⚠️ Enter a table name');

      const headers = currentSheetData[0];
      let cols = [];

      $('.col-check:checked').each(function() {
        const index = $(this).data('index');
        cols.push({ name: headers[index] ?? `col${index}`, index });
      });

      if (cols.length === 0) return alert('Please select at least one column.');

      $.post('create_structure.php', {
        table_name: tableName,
        columns: JSON.stringify(cols)
      }, function(response) {
        alert(response);
      });
    });
  </script>
