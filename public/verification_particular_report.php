<?php
/**
 * KPRM - Verification Particular Report
 * Displays verification particulars grouped by document type
 */

require_once('../system/op_lib.php');
require_once('../function.php');

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

if (!$case_id) {
    die('Invalid case ID');
}

// Get case details
$case_data = get_data('cases', $case_id);
if ($case_data['count'] == 0) {
    die('Case not found');
}

$case = $case_data['data'];
$application_no = $case['application_no'] ?? 'N/A';

// Get complete MIS report
$mis_data = generate_case_mis_report($case_id, [
    'include_pending' => false, // Only show completed/verified documents
    'max_sections' => 10
]);

// Also get simple verification particular data for backward compatibility
$report_data = generate_verification_particular_report($case_id, [
    'include_pending' => false,
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Particular Report - <?= htmlspecialchars($application_no) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .report-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .case-info {
            font-size: 14px;
            color: #666;
        }
        table {
            margin-top: 20px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="report-container">
            <div class="report-header">
                <div class="report-title">Complete MIS Report - Verification Particulars</div>
                <div class="case-info">
                    <strong>Application No:</strong> <?= htmlspecialchars($application_no) ?>
                </div>
            </div>
            
            <?php if ($mis_data): ?>
                <div class="mb-4">
                    <h5 class="mb-3">Complete MIS Report (Single Row Format)</h5>
                    <?= generate_case_mis_table($case_id, ['include_pending' => false, 'max_sections' => 10]) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($report_data)): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <p>No verification data available for this case.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Picked</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Count of Documents</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Holder Name</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Category</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Verification Point</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Local / OGL</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Status</th>
                                <th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= htmlspecialchars($row['document_picked']) ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= htmlspecialchars($row['count_of_documents']) ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;"><?= htmlspecialchars($row['document_holder_name'] ?: 'N/A') ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= htmlspecialchars($row['document_category']) ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= htmlspecialchars($row['verification_point']) ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= htmlspecialchars($row['local_ogl']) ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><?= htmlspecialchars($row['status']) ?></td>
                                    <td style="border: 1px solid #ddd; padding: 8px; text-align: left;"><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 text-center">
                <a href="view_case.php?case_id=<?= $case_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Case
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-1"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</body>
</html>

