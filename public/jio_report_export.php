<?php
/**
 * KPRM - Jio Case PDF Report (Robust Table Detection)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once('../system/op_lib.php');
    require_once('../function.php');

    $case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
    if (!$case_id) throw new Exception("Case ID is required");

    // 1. DATA COLLECTION
    $case_res = get_data('cases', $case_id);
    if ($case_res['count'] == 0) throw new Exception("Case not found");
    $case = $case_res['data'];
    $case_info = json_decode($case['case_info'] ?? '{}', true);

    $client_res = get_data('clients', $case['client_id']);
    $client_data = $client_res['data'] ?? [];
    
    // FETCH TASKS IN ORDER (Original Sequence)
    $sql_tasks_ordered = "SELECT * FROM case_tasks WHERE case_id = $case_id AND status = 'ACTIVE' ORDER BY id ASC";
    $res_tasks = mysqli_query($con, $sql_tasks_ordered);
    $tasks = [];
    while($row = mysqli_fetch_assoc($res_tasks)) $tasks[] = $row;
    
    // Comprehensive Mapping
    $map = [];
    $latest_rev = null;
    $agency_name = 'KPRM MANAGEMENT & INVESTIGATION SERVICES PVT LTD';
    
    $remarks_html_list = [];
    $remark_sr = 1;
    $has_negative = false;
    $all_positive = true;
    $has_any_pending = (empty($tasks));
    
    foreach ($tasks as $t) {
        $t_data = json_decode($t['task_data'] ?? '{}', true);
        $t_name = strtoupper(trim($t['task_name']));
        $t_review_status = strtoupper($t_data['review_status'] ?? '');
        
        // Merge ALL fields for flat map
        foreach($t_data as $k => $v) if(!empty($v)) $map[$k] = $v;

        // Specific mapping overrides
        if (strpos($t_name, 'RESI') !== false) {
            $map['resi_remarks'] = $t_data['review_remarks'] ?? $t_data['remark'] ?? '';
            $map['resi_detailed'] = $t_data['detailed_resi_remarks'] ?? $t_data['detailed_remarks'] ?? '';
        }
        if (strpos($t_name, 'OFFICE') !== false || strpos($t_name, 'BUSINESS') !== false) {
            $map['office_remarks'] = $t_data['review_remarks'] ?? $t_data['remark'] ?? '';
            $map['office_detailed'] = $t_data['detailed_office_remarks'] ?? $t_data['detailed_remarks'] ?? '';
        }

        // Logic Aligned with MIS
        if (!empty($t_review_status)) {
            if ($t_review_status == 'NEGATIVE') $has_negative = true;
            if ($t_review_status != 'POSITIVE') $all_positive = false;
        } else {
            $has_any_pending = true;
            $all_positive = false;
        }

        // --- NEW: Financial Table Detection (Robust) ---
        $remark_content = "";
        $has_financial = false;
        
        foreach($t_data as $f_key => $f_val) {
            // Check if this field contains a financial table
            if (!empty($f_val) && (is_array($f_val) || is_string($f_val))) {
                $table_html = render_financial_table_readonly($f_val);
                // If it rendered a table (contains jt-table class)
                if (strpos($table_html, 'jt-table') !== false) {
                    $remark_content .= "<div class='mt-2 mb-2'><strong>" . ucwords(str_replace('_', ' ', $f_key)) . ":</strong>" . $table_html . "</div>";
                    $has_financial = true;
                }
            }
        }

        $standard_remark = $t_data['review_remarks'] ?? $t['review_remarks'] ?? '';
        if (!empty($standard_remark) || $has_financial) {
            $remarks_html_list[] = "
                <div class='mb-3 pb-2 border-bottom'>
                    <div class='fw-bold text-dark'>" . $remark_sr . ". " . $t['task_name'] . "</div>
                    <div class='ps-3 text-secondary'>" . nl2br($standard_remark) . "</div>
                    " . $remark_content . "
                </div>";
            $remark_sr++;
        }

        if ($t['reviewed_at']) {
            $tr = strtotime($t['reviewed_at']);
            if (!$latest_rev || $tr > $latest_rev) $latest_rev = $tr;
        }

        if (!empty($t['assigned_to'])) {
            $v_res = get_data('verifier', $t['assigned_to']);
            if ($v_res['count'] > 0) $agency_name = strtoupper($v_res['data']['verifier_name']);
        }
    }

    // Status Words
    $pos_word = $client_data['positve_status'] ?: 'Positive';
    $neg_word = $client_data['negative_status'] ?: 'Negative';
    $cn_word = $client_data['cnv_status'] ?: 'CNV';

    if ($has_any_pending) $overall_status = 'PENDING';
    elseif ($has_negative) $overall_status = strtoupper($neg_word);
    elseif ($all_positive) $overall_status = strtoupper($pos_word);
    else $overall_status = strtoupper($cn_word);

    $final_remarks_html = implode("", $remarks_html_list);
    
    $app_id = $case_info['app_id'] ?? $case['application_no'] ?? 'N/A';
    $customer_name = $case_info['customer_name'] ?? $map['applicant_name'] ?? 'N/A';
    $reg_date = date('d-M-Y', strtotime($case['created_at']));
    $close_date = $latest_rev ? date('d-M-Y', $latest_rev) : 'N/A';

    // Agency Logic
    $agency_logo_url = '';
    if (!empty($client_data['agency_id'])) {
        $agency_res = get_data('agency', intval($client_data['agency_id']));
        if ($agency_res['count'] > 0) {
            $agency = $agency_res['data'];
            $agency_name = strtoupper($agency['agency_name']);
            $logo_file = $agency['agency_stamp'] ?: $agency['logo'];
            if ($logo_file) $agency_logo_url = '../upload/' . $logo_file;
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jio Report - Case #<?php echo $case_id; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; padding: 20px; font-family: 'Inter', sans-serif; font-size: 11px; }
        .report-page { background: white; width: 210mm; min-height: 297mm; padding: 15mm; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1); position: relative; }
        .table-jio { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; page-break-inside: avoid; }
        .table-jio th, .table-jio td { border: 1px solid #000; padding: 6px 10px; vertical-align: top; word-wrap: break-word; }
        .header-bg { background-color: #FFFF00 !important; font-weight: bold; text-align: center; color: #000; font-size: 13px; border: 2px solid #000 !important; }
        .label-cell { width: 40%; font-weight: bold; background-color: #f7f7f7 !important; }
        .value-cell { width: 60%; }
        .section-keep { page-break-inside: avoid !important; break-inside: avoid !important; }
        .photo-container { border: 1px solid #000; padding: 10px; margin-bottom: 20px; page-break-inside: avoid !important; break-inside: avoid !important; }
        .photo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .photo-item { text-align: center; border: 1px solid #eee; padding: 5px; }
        .photo-img { width: 100%; height: 180px; object-fit: cover; border: 1px solid #ccc; }
        .stamp-box { height: 120px; display: flex; align-items: center; justify-content: center; }
        .status-badge { padding: 4px 12px; font-weight: bold; border: 1px solid #000; border-radius: 4px; display: inline-block; }
        .btn-download { position: fixed; top: 30px; right: 30px; z-index: 1000; border-radius: 30px; padding: 12px 25px; font-weight: 600; box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4); }
        .html2pdf__page-break { page-break-before: always !important; height: 0 !important; border: none !important; margin: 0 !important; }
        /* Financial Table Styles - Forcing consistent look in PDF */
        .jt-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 5px; table-layout: auto !important; }
        .jt-table th, .jt-table td { border: 1px solid #999 !important; padding: 3px 5px !important; }
        .jt-header-section { background: #eee !important; font-weight: bold; text-align: center; }
        .jt-header-cols th { background: #fafafa !important; font-weight: bold; font-size: 8.5px; text-transform: uppercase; }
        .jt-particular { width: 35%; font-weight: 600; }
        @media print { .btn-download, .no-print { display: none !important; } .report-page { box-shadow: none; margin: 0; width: 100%; padding: 0; } body { background: white; padding: 0; } }
    </style>
</head>
<body>

    <button id="download-btn" class="btn btn-danger btn-download">
        <i class="fas fa-file-pdf me-2"></i> DOWNLOAD FINAL PDF
    </button>

    <div id="report-content" class="report-page">
        <!-- Main Header -->
        <table class="table-jio section-keep">
            <thead>
                <tr><th colspan="2" class="header-bg">RCU / CPV Template: Jio Credit Ltd.</th></tr>
            </thead>
            <tbody>
                <tr><td class="label-cell">File No / Application No</td><td class="value-cell fw-bold text-primary"><?php echo $app_id; ?></td></tr>
                <tr><td class="label-cell">Customer Name</td><td class="value-cell fw-bold"><?php echo $customer_name; ?></td></tr>
                <tr><td class="label-cell">Residence Address with PIN</td><td class="value-cell"><?php echo $map['residence_address'] ?? $case_info['address'] ?? 'N/A'; ?></td></tr>
                <tr><td class="label-cell">Office Address with PIN</td><td class="value-cell"><?php echo $map['office_address'] ?? 'N/A'; ?></td></tr>
                <tr><td class="label-cell">Mobile No</td><td class="value-cell"><?php echo $case_info['mobile'] ?? $map['mobile_number'] ?? 'N/A'; ?></td></tr>
                <tr><td class="label-cell">Verification Location</td><td class="value-cell"><?php echo $case_info['location'] ?? 'N/A'; ?></td></tr>
                <tr><td class="label-cell">Product</td><td class="value-cell"><?php echo $case_info['product'] ?? 'N/A'; ?></td></tr>
            </tbody>
        </table>

        <!-- Checks Section -->
        <table class="table-jio section-keep" style="margin-top: -16px;">
            <thead>
                <tr><th colspan="2" class="header-bg">RCU / CPV Checks Required</th></tr>
            </thead>
            <tbody>
                <tr><td class="label-cell">Resi Visit</td><td class="value-cell"><?php echo $map['resi_visit'] ?? 'DONE'; ?></td></tr>
                <tr><td class="label-cell">Office Visit</td><td class="value-cell"><?php echo $map['office_visit'] ?? 'DONE'; ?></td></tr>
                <tr><td class="label-cell">Documents to be sampled</td><td class="value-cell"><?php echo $map['documents_sampled'] ?? 'All Relevant KYC'; ?></td></tr>
            </tbody>
        </table>

        <!-- Verification Details Section -->
        <table class="table-jio section-keep" style="margin-top: -16px;">
            <thead>
                <tr><th colspan="2" class="header-bg">Verification Details</th></tr>
            </thead>
            <tbody>
                <tr><td class="label-cell">Residence Verification Remarks</td><td class="value-cell"><?php echo nl2br($map['resi_remarks'] ?? 'N/A'); ?></td></tr>
                <tr><td class="label-cell">Residence Type</td><td class="value-cell"><?php echo $map['residence_type'] ?? 'N/A'; ?></td></tr>
                <tr><td class="label-cell">Detailed Resi Visit Findings</td><td class="value-cell"><?php echo nl2br($map['resi_detailed'] ?? 'N/A'); ?></td></tr>
                
                <tr><td class="label-cell">Office Verification Remarks</td><td class="value-cell"><?php echo nl2br($map['office_remarks'] ?? 'N/A'); ?></td></tr>
                <tr><td class="label-cell">Office Locality Type</td><td class="value-cell"><?php echo $map['office_locality'] ?? 'N/A'; ?></td></tr>
                <tr><td class="label-cell">Detailed Office Visit Findings</td><td class="value-cell"><?php echo nl2br($map['office_detailed'] ?? 'N/A'); ?></td></tr>
            </tbody>
        </table>

        <!-- Final Decision Section (MIS ATTACHED) -->
        <table class="table-jio section-keep" style="margin-top: -16px;">
            <thead>
                <tr><th colspan="2" class="header-bg">Overall RCU / CPV Findings & Decision (MIS Aligned)</th></tr>
            </thead>
            <tbody>
                <tr><td class="label-cell">Overall Case Status</td><td class="value-cell fw-bold">
                    <?php 
                        $status_class = 'bg-secondary';
                        if($overall_status == 'PENDING') $status_class = 'bg-warning text-dark';
                        elseif(strpos($overall_status, strtoupper($pos_word)) !== false) $status_class = 'bg-success text-white';
                        else $status_class = 'bg-danger text-white';
                    ?>
                    <div class="status-badge <?php echo $status_class; ?>"><?php echo $overall_status; ?></div>
                </td></tr>
                <tr><td class="label-cell">Final Remarks Summary</td><td class="value-cell" style="min-height: 120px; font-size: 10.5px; line-height: 1.4;">
                    <div class="remarks-container">
                        <?php echo $final_remarks_html; ?>
                    </div>
                </td></tr>
                <tr><td class="label-cell">TAT (Turnaround Time)</td><td class="value-cell"><?php echo $reg_date . ' to ' . $close_date; ?></td></tr>
            </tbody>
        </table>

        <!-- Agency Footer -->
        <table class="table-jio section-keep" style="margin-top: -16px;">
            <thead>
                <tr><th colspan="2" class="header-bg">Agency Approval Details</th></tr>
            </thead>
            <tbody>
                <tr><td class="label-cell">Agency Name</td><td class="value-cell fw-bold"><?php echo $agency_name; ?></td></tr>
                <tr>
                    <td class="label-cell" style="vertical-align: middle;">Seal & Authorized Signature</td>
                    <td class="value-cell">
                        <div class="stamp-box">
                            <?php if($agency_logo_url): ?>
                                <img src="<?php echo $agency_logo_url; ?>" style="height: 100px;">
                            <?php else: ?>
                                <div class="text-muted small">Official Stamp Area</div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Photographs -->
        <div class="html2pdf__page-break"></div>
        <div style="margin-top: 20px;">
            <div class="header-bg p-2 mb-3">Visit Photographs / Evidence</div>
            <?php 
            foreach($tasks as $t): 
                $imgRes = get_all('attachments', '*', ['task_id' => $t['id'], 'status' => 'ACTIVE']);
                $images = array_filter($imgRes['data'] ?? [], function($at) {
                    $ext = strtolower(pathinfo($at['file_name'] ?? '', PATHINFO_EXTENSION));
                    return in_array($ext, ['jpg', 'jpeg', 'png']);
                });
                
                if(!empty($images)):
            ?>
                <div class="photo-container">
                    <div class="fw-bold mb-2 text-dark bg-light p-1 border"><?php echo $t['task_name']; ?></div>
                    <div class="photo-grid">
                        <?php foreach($images as $img): ?>
                            <div class="photo-item">
                                <img src="../upload/<?php echo $img['file_url']; ?>" class="photo-img">
                                <div class="small mt-1 text-muted"><?php echo htmlspecialchars($img['file_name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>

    <script>
        document.getElementById('download-btn').addEventListener('click', function() {
            const element = document.getElementById('report-content');
            
            // Sanitize App ID for filename
            let appId = '<?php echo addslashes($app_id); ?>';
            let cleanAppId = appId.replace(/[\/\\?%*:|"<>]/g, '_');
            
            const options = {
                margin: [5, 5, 5, 5],
                filename: cleanAppId + '_Jio_Report.pdf',
                image: { type: 'jpeg', quality: 1.0 },
                html2canvas: { scale: 3, useCORS: true, letterRendering: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> PRESERVING FORMAT...';
            this.disabled = true;

            html2pdf().set(options).from(element).save().then(() => {
                this.innerHTML = '<i class="fas fa-file-pdf me-2"></i> DOWNLOAD FINAL PDF';
                this.disabled = false;
            });
        });
    </script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
<?php
} catch (Exception $e) {
    die("<div class='alert alert-danger'>Report Error: " . $e->getMessage() . "</div>");
}
