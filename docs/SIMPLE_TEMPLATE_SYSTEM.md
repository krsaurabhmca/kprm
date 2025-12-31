# Simple Template System

## Overview

This is a simplified template system that accepts ready HTML templates and replaces placeholders with actual data to generate reports.

## How It Works

1. **Upload HTML Template**: Paste your ready HTML template with placeholders
2. **Placeholder Format**: Use `{{placeholder_name}}` format
3. **Generate Report**: System replaces placeholders with actual data

## Files

- `public/template_manage.php` - Manage templates (list, delete)
- `public/template_editor.php` - **Template Editor with Preview** (paste HTML, bind placeholders)
- `public/template_save.php` - Save/update templates (API)
- `public/template_edit.php` - Edit existing templates (legacy)
- `public/generate_report.php` - Generate report from template
- `function.php` - Contains `generate_report_from_html()` function

## Usage

### 1. Create/Edit Template (Recommended)

1. Go to: `public/template_editor.php`
2. Select client (to see client-specific placeholders)
3. Enter template name
4. **Paste your HTML template** in the textarea
5. **Click placeholders on the left sidebar** to insert them into your template
6. Click **"Preview"** to see how it looks with sample data
7. Click **"Save"** when done

**Features:**
- ✅ Click-to-insert placeholders
- ✅ Live preview with sample data
- ✅ Organized placeholder categories
- ✅ Client-specific placeholders (when client selected)

### 2. Alternative: Upload Template (Legacy)

1. Go to: `public/template_manage.php`
2. Click "New Template"
3. Use the template editor (same as above)

### 2. Generate Report

1. Go to: `public/template_manage.php`
2. Click "Generate" button next to a template
3. Or use: `public/generate_report.php?template_id=1&case_id=123`

## Placeholder Types

### Case Information
- `{{application_number}}`
- `{{product}}`
- `{{region}}`
- `{{state}}`
- `{{branch}}`
- `{{location}}`
- `{{loan_amount}}`
- `{{sample_date}}`
- `{{report_date}}`

### Client Information
- `{{client_name}}`
- Any field from `clients` table
- Any field from `clients_meta` table (by field_name)

### System Generated
- `{{current_date}}` - Current date (d-m-Y)
- `{{report_date}}` - Report date (d-m-Y)
- `{{serial_no}}` - Serial number (padded case ID)
- `{{total_no_of_docs_sampled}}` - Number of documents

### Task Related
- `{{task_name}}`
- `{{task_type}}`
- `{{task_remarks}}`
- `{{no_of_task}}`

### Document Loop
For repeating document sections:
```
{{document_loop_start}}
  <tr>
    <td>{{document_particulars}}</td>
    <td>{{document_type}}</td>
    <td>{{document_status}}</td>
    <td>{{document_remarks}}</td>
  </tr>
{{document_loop_end}}
```

## Example HTML Template

```html
<!DOCTYPE html>
<html>
<head>
    <title>Report</title>
</head>
<body>
    <h1>Report for {{client_name}}</h1>
    <p>Application Number: {{application_number}}</p>
    <p>Date: {{current_date}}</p>
    <p>Product: {{product}}</p>
    
    <table>
        <thead>
            <tr>
                <th>Particulars</th>
                <th>Type</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            {{document_loop_start}}
            <tr>
                <td>{{document_particulars}}</td>
                <td>{{document_type}}</td>
                <td>{{document_status}}</td>
            </tr>
            {{document_loop_end}}
        </tbody>
    </table>
</body>
</html>
```

## Function Usage

```php
// Generate report from HTML template
$result = generate_report_from_html(
    $html_template,  // Your HTML template string
    $case_id,        // Optional: Case ID
    $client_id,      // Optional: Client ID
    $custom_data     // Optional: Custom data array
);

if ($result['success']) {
    echo $result['html']; // Generated HTML
} else {
    echo $result['message']; // Error message
}
```

## Notes

- All placeholders are automatically HTML-escaped for security
- Unmatched placeholders are removed (replaced with empty string)
- Supports both `{{placeholder}}` and `#placeholder#` formats
- Document loops are processed automatically if case_id is provided

