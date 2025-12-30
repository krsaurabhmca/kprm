# Report Templates System Guide

## Overview
The Report Templates System allows you to create dynamic, customizable report formats for each client. Each client can have multiple report templates, and templates can be task-specific or general.

## Database Structure

### Tables Created
1. **`report_templates`** - Stores report format templates
2. **`report_template_variables`** - Maps variables to data sources (for future use)

### Key Features
- Multiple templates per client
- Task-specific templates
- Visual designer with drag-and-drop components
- Placeholder system (#variable_name#)
- Custom CSS support
- Default template selection

## Getting Started

### 1. Create Database Tables
Run the SQL file to create the necessary tables:
```sql
SOURCE db/create_report_templates_table.sql;
```

### 2. Access Report Templates
Navigate to: **Report Templates Management** (add to menu)

## Creating a Report Template

### Step 1: Basic Information
1. Go to **Report Templates** → **Add New Template**
2. Select the **Client**
3. Enter **Template Name** (e.g., "Document Verification Report")
4. Choose **Template Type**:
   - **Standard**: General purpose template
   - **Custom**: Custom format
   - **Task Specific**: Only for specific task types
5. Set as **Default** if this should be the default template for the client

### Step 2: Design the Template

#### Option A: Visual Designer (Recommended)
1. Click **"Open Visual Designer"** button
2. Use the sidebar components to add:
   - **Header**: Document title
   - **Table**: Data tables
   - **Text Block**: Paragraphs
   - **Image**: Visit photos
   - **Signature**: Signature areas
   - **Divider**: Horizontal lines
3. Click on variables from the sidebar to insert placeholders
4. Click **Save** to save your design

#### Option B: HTML Editor
1. Write HTML directly in the **Template HTML** field
2. Use placeholders like `#variable_name#`
3. Add custom CSS if needed

### Step 3: Available Placeholders

#### System Variables
- `#case_id#` - Case ID
- `#application_no#` - Application Number
- `#client_name#` - Client Name
- `#task_name#` - Task Name
- `#current_date#` - Current Date (d-M-Y format)
- `#current_time#` - Current Time
- `#current_datetime#` - Current Date & Time

#### Task Data Variables
- `#applicant_name#` - Applicant Name
- `#address#` - Address
- `#verifier_remarks#` - Verifier Remarks
- `#review_status#` - Review Status (Positive/Negative/CNV)
- `#review_remarks#` - Final Review Remarks
- Any field from `tasks_meta` can be used as `#field_name#`

#### Case Info Variables
- Any field from `case_info` JSON can be used as `#field_name#`

#### Special Placeholders
- `#visit_photos#` - All selected attachments/images
- `#attachments#` - Same as visit_photos

### Step 4: Example Template

```html
<div style="text-align: center; margin-bottom: 30px;">
    <h2 style="border-bottom: 2px solid #333; padding-bottom: 10px;">
        Document Verification Remarks
    </h2>
    <p><strong>Agency Name:</strong> (#client_name#)</p>
</div>

<table class="table table-bordered" style="width: 100%; margin: 20px 0;">
    <thead>
        <tr>
            <th>Field</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>App ID</strong></td>
            <td>#application_no#</td>
        </tr>
        <tr>
            <td><strong>Applicant Name</strong></td>
            <td>#applicant_name#</td>
        </tr>
        <tr>
            <td><strong>Date</strong></td>
            <td>#current_date#</td>
        </tr>
    </tbody>
</table>

<div style="margin: 20px 0;">
    <h4>Verification Remarks</h4>
    <p style="line-height: 1.6; text-align: justify;">
        #verifier_remarks#
    </p>
</div>

<div style="margin: 20px 0;">
    <h4>Visit Photo</h4>
    <div style="text-align: center;">
        #visit_photos#
    </div>
</div>

<div style="margin-top: 50px;">
    <p><strong>Verifier Signature and Stamp of Agency</strong></p>
    <div style="border: 1px solid #333; width: 200px; height: 100px; display: inline-block;">
        Signature Area
    </div>
</div>
```

## Generating Reports

### From View Case Page
1. Navigate to a completed task
2. Click **"Generate Report"** button
3. The system will:
   - Find the default template for the client (or task-specific template)
   - Replace all placeholders with actual data
   - Display the formatted report
4. Click **"Print Report"** to print

### Direct URL
```
generate_report.php?case_task_id=123&template_id=5
```

## Template Selection Logic

When generating a report, the system:
1. Uses `template_id` if provided in URL
2. Otherwise, finds default template for the client
3. If task-specific, matches by `task_type`
4. Falls back to first active template for client

## Best Practices

1. **Use Semantic Placeholders**: Use clear variable names like `#applicant_name#` instead of `#name#`

2. **Test Templates**: Use the preview feature to test templates before saving

3. **CSS Styling**: Add custom CSS for better formatting:
```css
.report-header {
    font-size: 18px;
    font-weight: bold;
    text-align: center;
}
```

4. **Responsive Design**: Consider print styles:
```css
@media print {
    .no-print { display: none; }
    body { padding: 0; }
}
```

5. **Multiple Templates**: Create different templates for different report types:
   - Standard Verification Report
   - Detailed Analysis Report
   - Summary Report

## Managing Templates

### List Templates
- View all templates or filter by client
- See template type, default status, and actions

### Edit Template
- Click **Edit** icon to modify template
- Use Visual Designer for easy editing
- Update HTML directly if needed

### Delete Template
- Click **Delete** icon (soft delete - sets status to INACTIVE)
- Templates are not permanently deleted

### Set Default
- Only one template per client can be default
- Setting a new default automatically unsets the previous one

## Advanced Features

### Task-Specific Templates
1. Set **Template Type** to "Task Specific"
2. Select the **Task Type**
3. This template will only be used for tasks of that type

### Custom CSS
Add custom CSS in the **Custom CSS** field:
```css
.report-container {
    font-family: 'Times New Roman', serif;
}
table {
    border-collapse: collapse;
    width: 100%;
}
```

### Dynamic Content
- Use conditional logic in HTML (requires JavaScript)
- Format dates using PHP date functions in template
- Include multiple images using `#visit_photos#`

## Troubleshooting

### Placeholders Not Replacing
- Check variable name spelling (case-sensitive)
- Ensure data exists in task_data or case_info
- Check that template is active

### Images Not Showing
- Verify attachments are marked `display_in_report = 'YES'`
- Check file paths are correct
- Ensure images are uploaded successfully

### Template Not Found
- Verify template is set as default for client
- Check template status is ACTIVE
- Ensure client_id matches

## Future Enhancements

- Variable mapping system (map placeholders to specific data sources)
- Template versioning
- Template preview with sample data
- Export templates (import/export)
- Template categories
- Conditional sections in templates

