/**
 * KPRM - JSON Dynamic Table Handler
 */

function initJsonTable(fieldName, config, existingData, prefix = 'json_table_') {
    const container = document.getElementById(prefix + 'container_' + fieldName);
    const hiddenInput = document.getElementById(prefix + 'input_' + fieldName);
    
    if (!container || !hiddenInput) return;

    // Default structure for Financial Task Type if config is empty
    if (!config || Object.keys(config).length === 0) {
        config = {
            "P & L Statement": [
                "Sales/Turnover",
                "Purchase",
                "Depreciation",
                "Partner/Director Remuneration",
                "PBT"
            ],
            "Balance Sheet Statement": [
                "Share Capital",
                "Fixed Assets",
                "Sundry Creditor",
                "Sundry debtor",
                "Cash in hand & BANK",
                "Cash in Bank"
            ]
        };
    }

    // Initialize data structure
    let tableData = existingData || {};
    if (typeof tableData === 'string' && tableData !== '') {
        try {
            tableData = JSON.parse(tableData);
        } catch (e) {
            console.error('Failed to parse existing table data:', e);
            tableData = {};
        }
    }
    
    let html = `<table class="jt-table">
        <thead>
            <tr class="jt-header-cols">
                <th>Particular</th>
                <th>Amount as per Provided copy</th>
                <th>Amount as per ITO record</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>`;

    for (const section in config) {
        // Section Header
        html += `<tr>
            <td colspan="4" class="jt-header-section">${section}</td>
        </tr>`;
        
        // Rows
        config[section].forEach((particular, idx) => {
            const rowKey = section.replace(/[^a-zA-Z0-9]/g, '_') + '_' + particular.replace(/[^a-zA-Z0-9]/g, '_');
            const rowData = tableData[rowKey] || { provided: '', ito: '', remark: '', difference: '0' };
            
            html += `<tr class="jt-row" data-section="${section}" data-particular="${particular}" data-rowkey="${rowKey}">
                <td class="jt-particular">${particular}</td>
                <td><input type="text" class="jt-input jt-provided" value="${rowData.provided || ''}" placeholder="0"></td>
                <td><input type="text" class="jt-input jt-ito" value="${rowData.ito || ''}" placeholder="0"></td>
                <td><input type="text" class="jt-input jt-remark" value="${rowData.remark || ''}" placeholder="Remark"></td>
            </tr>`;
        });
    }

    html += `</tbody></table>`;
    container.innerHTML = html;

    // Attach events for calculation
    const wrapper = container.querySelector('.jt-table');
    
    const updateCalculations = () => {
        const rows = wrapper.querySelectorAll('.jt-row');
        let currentData = {};
        
        rows.forEach(row => {
            const rowKey = row.getAttribute('data-rowkey');
            const providedVal = row.querySelector('.jt-provided').value;
            const itoVal = row.querySelector('.jt-ito').value;
            const remarkInput = row.querySelector('.jt-remark');
            
            // Clean values for calculation (remove commas if any)
            const p = parseFloat(providedVal.replace(/,/g, ''));
            const i = parseFloat(itoVal.replace(/,/g, ''));
            
            let diffText = '0';
            let autoRemark = '';

            // Special Case: NA
            if (providedVal.toUpperCase() === 'NA' || itoVal.toUpperCase() === 'NA') {
                diffText = '#VALUE!';
                autoRemark = 'N/A';
            } else if (!isNaN(p) && !isNaN(i)) {
                // Difference: ITO - Provided
                const diff = i - p;
                diffText = diff.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                if (Math.abs(diff) < 0.01) {
                    autoRemark = 'Figure Matching';
                } else {
                    const absDiff = Math.abs(diff).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    autoRemark = 'Figure Difference ' + absDiff;
                }
            } else if (providedVal === '' && itoVal === '') {
                 diffText = '0';
                 autoRemark = '';
            } else {
                 diffText = '0.00';
                 autoRemark = remarkInput.value; 
            }

            // Sync Remark if it was empty or matches a known auto-pattern
            if (providedVal !== '' || itoVal !== '') {
                const currentRemark = remarkInput.value;
                const isAuto = currentRemark === '' || 
                               currentRemark === 'Matching' || 
                               currentRemark === 'Figure Matching' || 
                               currentRemark === 'Figure Matched' || 
                               currentRemark.startsWith('Figure Difference') ||
                               currentRemark.startsWith('Diff:') ||
                               (!isNaN(parseFloat(currentRemark.replace(/,/g, ''))) && currentRemark !== '');

                if (isAuto) {
                    remarkInput.value = autoRemark;
                }
            }
            
            currentData[rowKey] = {
                section: row.getAttribute('data-section'),
                particular: row.getAttribute('data-particular'),
                provided: providedVal,
                ito: itoVal,
                remark: remarkInput.value,
                difference: diffText
            };
        });
        
        hiddenInput.value = JSON.stringify(currentData);
    };

    wrapper.addEventListener('input', (e) => {
        if (e.target.classList.contains('jt-input')) {
            updateCalculations();
        }
    });

    // Initial calculation
    updateCalculations();
}

