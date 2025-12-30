<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Advance Table Builder By OP</title>
  <style>
    body { font-family: Arial; padding: 5px; }
    table { border-collapse: collapse; width: 100%; }
    td, th {
      border: 1px solid #ddd;
      padding: 8px;
      min-width: 80px;
      cursor: pointer;
    }
    td.selected {
      background: #d0f0ff;
    }
    .toolbar button, .toolbar select, .toolbar input {
      margin: 5px;
      padding: 5px;
    }
  </style>
</head>
<body>

<h2>🧾 Advance Report Builder</h2>

<div class="toolbar">
  <button onclick="addRow()">➕ Row</button>
  <button onclick="addCol()">➕ Column</button>
  <button onclick="deleteRow()">🗑️ Row</button>
  <button onclick="deleteCol()">🗑️ Col</button>
  <button onclick="mergeCells()">🔀 Merge</button>
  <button onclick="splitCell()">🔁 Split</button>

  <select id="fontSize" onchange="applyStyle('fontSize', this.value)">
    <option value="">Font Size</option>
    <option value="8px">8</option>
    <option value="10px">10</option>
    <option value="12px">12</option>
    <option value="14px">14</option>
    <option value="16px">16</option>
    <option value="18px">18</option>
  </select>

<!-- Add below buttons/controls in your .toolbar div -->
<select onchange="applyStyle('textAlign', this.value)">
  <option value="">Text Align</option>
  <option value="left">Left</option>
  <option value="center">Center</option>
  <option value="right">Right</option>
  <option value="justify">Justify</option>
</select>

<select onchange="applyStyle('border', this.value)">
  <option value="">Cell Border</option>
  <option value="1px solid #000">Solid</option>
  <option value="1px dashed #000">Dashed</option>
  <option value="1px dotted #000">Dotted</option>
  <option value="none">None</option>
</select>

  <input type="color" onchange="applyStyle('color', this.value)" title="Text Color" />
  <input type="color" onchange="applyStyle('backgroundColor', this.value)" title="Background Color" />
  
  <button onclick="addPlaceholder()">📌 Placeholder</button>
  <button onclick="downloadAsHTML()">⬇ Download HTML</button>
</div>

<table id="editorTable" contenteditable="true" onclick="handleCellClick(event)" >
  <tbody>
    <tr><td></td><td></td></tr>
    <tr><td></td><td></td></tr>
  </tbody>
</table>

<script>
  
	let selectedCells = [];
	let firstCell = null;

	function handleCellClick(e) {
	  if (e.target.tagName !== 'TD') return;
	  const cell = e.target;

	  // Ctrl+Click for toggle
	  if (e.ctrlKey) {
		cell.classList.toggle('selected');
		toggleCellSelection(cell);
		if (!firstCell) firstCell = cell;
		return;
	  }

	  // Shift+Click for continuous block selection
	  if (e.shiftKey && firstCell) {
		clearSelections();
		selectRectangle(firstCell, cell);
		return;
	  }

	  // Single click
	  clearSelections();
	  cell.classList.add('selected');
	  selectedCells = [cell];
	  firstCell = cell;
	}


function selectRectangle(cell1, cell2) {
  const table = document.getElementById("editorTable");

  const row1 = cell1.parentElement.rowIndex;
  const col1 = cell1.cellIndex;
  const row2 = cell2.parentElement.rowIndex;
  const col2 = cell2.cellIndex;

  const rMin = Math.min(row1, row2);
  const rMax = Math.max(row1, row2);
  const cMin = Math.min(col1, col2);
  const cMax = Math.max(col1, col2);

  for (let r = rMin; r <= rMax; r++) {
    for (let c = cMin; c <= cMax; c++) {
      const cell = table.rows[r].cells[c];
      if (cell) {
        cell.classList.add('selected');
        selectedCells.push(cell);
      }
    }
  }
}

document.addEventListener("keydown", function (e) {
  const table = document.getElementById("editorTable");
  const active = document.activeElement;

  if (active.tagName !== "TD") return;

  let cell = active;
  let row = cell.parentElement;
  let rowIndex = row.rowIndex;
  let cellIndex = cell.cellIndex;

  // TAB = Next cell →
  if (e.key === "Tab" && !e.shiftKey) {
    e.preventDefault();
    let next = row.cells[cellIndex + 1];
    if (!next && row.nextElementSibling) {
      next = row.nextElementSibling.cells[0];
    }
    if (next) next.focus();
  }

  // Shift + TAB = Previous cell ←
  if (e.key === "Tab" && e.shiftKey) {
    e.preventDefault();
    let prev = row.cells[cellIndex - 1];
    if (!prev && row.previousElementSibling) {
      prev = row.previousElementSibling.cells[row.previousElementSibling.cells.length - 1];
    }
    if (prev) prev.focus();
  }

  // ENTER = Down cell ↓
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    if (table.rows[rowIndex + 1]) {
      const down = table.rows[rowIndex + 1].cells[cellIndex];
      if (down) down.focus();
    }
  }

  // Shift + ENTER = Up cell ↑
  if (e.key === "Enter" && e.shiftKey) {
    e.preventDefault();
    if (table.rows[rowIndex - 1]) {
      const up = table.rows[rowIndex - 1].cells[cellIndex];
      if (up) up.focus();
    }
  }
});


  function toggleCellSelection(cell) {
    const index = selectedCells.indexOf(cell);
    if (index > -1) {
      selectedCells.splice(index, 1);
    } else {
      selectedCells.push(cell);
    }
  }

  function clearSelections() {
    selectedCells.forEach(c => c.classList.remove('selected'));
    selectedCells = [];
  }

function addRow() {
  const table = document.getElementById('editorTable');
  const row = table.insertRow();
  const colCount = table.rows[0]?.cells.length || 1;

  for (let i = 0; i < colCount; i++) {
    const cell = row.insertCell();
    cell.setAttribute("tabindex", "0");
    cell.contentEditable = "true";
  }
}

function addCol() {
  const table = document.getElementById('editorTable');

  for (let i = 0; i < table.rows.length; i++) {
    const cell = table.rows[i].insertCell();
    cell.setAttribute("tabindex", "0");
    cell.contentEditable = "true";
  }
}

document.querySelectorAll('#editorTable td').forEach(cell => {
  cell.setAttribute("tabindex", "0");
  cell.contentEditable = "true";
});


  function deleteRow() {
    if (selectedCells.length === 0) return alert("Select a cell to delete row.");
    const row = selectedCells[0].parentElement;
    row.remove();
    clearSelections();
  }

  function deleteCol() {
    if (selectedCells.length === 0) return alert("Select a cell to delete column.");
    const colIndex = selectedCells[0].cellIndex;
    const table = document.getElementById('editorTable');
    for (let i = 0; i < table.rows.length; i++) {
      if (table.rows[i].cells[colIndex])
        table.rows[i].deleteCell(colIndex);
    }
    clearSelections();
  }

  function mergeCells() {
    if (selectedCells.length < 2) return alert("Select 2+ cells with Ctrl key to merge.");
    const first = selectedCells[0];
    let content = selectedCells.map(c => c.innerHTML).join(' ');
    first.innerHTML = content;
    first.colSpan = selectedCells.length;
    for (let i = 1; i < selectedCells.length; i++) {
      selectedCells[i].remove();
    }
    clearSelections();
  }

  function splitCell() {
    if (selectedCells.length === 0) return alert("Select a merged cell.");
    const cell = selectedCells[0];
    const span = cell.colSpan || 1;
    if (span <= 1) return alert("Cell is not merged.");

    const row = cell.parentElement;
    cell.colSpan = 1;
    for (let i = 1; i < span; i++) {
      const newCell = row.insertCell(cell.cellIndex + i);
      newCell.innerHTML = '';
    }
    clearSelections();
  }

  function applyStyle(styleProp, value) {
    selectedCells.forEach(cell => {
      cell.style[styleProp] = value;
    });
  }

  function addPlaceholder() {
    if (selectedCells.length === 0) return alert("Select a cell");
    const name = prompt("Enter placeholder name:");
    if (name) selectedCells[0].innerHTML += ` ##${name}`;
  }

  function downloadAsHTML() {
  const table = document.getElementById('editorTable').cloneNode(true);
  clearSelections(); // remove selection styles

  // Make cells clean and non-editable
  const cells = table.querySelectorAll("td");
  cells.forEach(cell => {
    cell.removeAttribute('class');
    cell.contentEditable = false;
    cell.setAttribute("style", cell.getAttribute("style") || "border:1px solid #000;");
  });

  // Add full HTML structure with meta tags
  const htmlContent = `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Downloaded Report</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    td { border: 1px solid #000; padding: 8px; }
  </style>
</head>
<body>
  ${table.outerHTML}
</body>
</html>`;

  // Create Blob and download
  const blob = new Blob([htmlContent], { type: 'text/html' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = "report.html";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

  
  document.addEventListener("keydown", function (e) {
  const table = document.getElementById("editorTable");
  const active = document.activeElement;
  if (!table || active.tagName !== "TD") return;

  let row = active.parentElement;
  let rowIndex = row.rowIndex;
  let cellIndex = active.cellIndex;
  let newRowIndex = rowIndex;
  let newColIndex = cellIndex;

  if (e.key === "Tab") {
    e.preventDefault();
    if (e.shiftKey) newColIndex--;
    else newColIndex++;

  } else if (e.key === "Enter") {
    e.preventDefault();
    if (e.shiftKey) newRowIndex--;
    else newRowIndex++;

  } else if (["ArrowRight", "ArrowLeft", "ArrowUp", "ArrowDown"].includes(e.key)) {
    e.preventDefault();
    if (e.key === "ArrowRight") newColIndex++;
    if (e.key === "ArrowLeft") newColIndex--;
    if (e.key === "ArrowDown") newRowIndex++;
    if (e.key === "ArrowUp") newRowIndex--;
  } else {
    return; // Don't handle other keys
  }

  // Try to move focus
  if (table.rows[newRowIndex] && table.rows[newRowIndex].cells[newColIndex]) {
    const targetCell = table.rows[newRowIndex].cells[newColIndex];
    targetCell.focus();

    if (e.shiftKey) {
      // Shift+Arrow = Extend selection
      clearSelections(); // Optional: comment if you want multi
      selectRectangle(firstCell || active, targetCell);
    } else {
      clearSelections();
      targetCell.classList.add("selected");
      selectedCells = [targetCell];
      firstCell = targetCell;
    }
  }
});

</script>

</body>
</html>
