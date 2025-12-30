# KPRM - Tasks CRUD with Auto Meta Update Guide

## Overview

The Tasks CRUD system automatically extracts variables from report formats and updates `tasks_meta` table when tasks are saved.

## Features

✅ **Full CRUD Operations**: Create, Read, Update, Delete tasks  
✅ **Auto Meta Update**: Automatically extracts variables from report formats  
✅ **Smart Field Detection**: Detects input types and field sources  
✅ **Multiple Format Support**: Extracts from positive_format, negative_format, and cnv_format  

## How It Works

### 1. Task CRUD Pages

- **`tasks_manage.php`**: List all tasks
- **`tasks_add.php`**: Add/Edit task with form

### 2. Auto Meta Update Process

When a task is saved:

1. **Extract Variables**: System scans all three format fields for `#variable_name#` patterns
2. **Detect Properties**: Automatically determines:
   - Input type (TEXT, DATE, NUMBER, TEXTAREA, SELECT)
   - Field source (by_client, by_verifier, by_findings)
   - Display name (human-readable)
3. **Update tasks_meta**: Creates or updates entries in `tasks_meta` table

### 3. Variable Extraction

The system extracts variables from:
- `positive_format`: Variables used in positive reports
- `negative_format`: Variables used in negative reports  
- `cnv_format`: Variables used in CNV (Could Not Verify) reports

**Example Format:**
```
Residence - Applicant Name – #applicant_name#- Visit done by field executive 
at the given address – #address#. Met with #met_with# at the given address 
and he/she has confirm that, he/she is staying at the address for the last 
#time_period# at #ownership# premises.
```

**Extracted Variables:**
- `#applicant_name#` → Applicant Name (TEXT, by_client=YES)
- `#address#` → Address (TEXTAREA, by_client=YES)
- `#met_with#` → Met With (TEXT, by_verifier=YES)
- `#time_period#` → Time Period (TEXT, by_verifier=YES)
- `#ownership#` → Ownership (SELECT, by_verifier=YES)

## Field Property Detection

### Input Type Detection

| Pattern | Input Type |
|---------|-----------|
| Contains "date", "dob", "joining", "registration" | DATE |
| Contains "amount", "income", "rent", "area", "tax" | NUMBER |
| Contains "remark", "address", "transaction", "tpc" | TEXTAREA |
| Contains "type", "ownership" | SELECT |
| Default | TEXT |

### Field Source Detection

| Pattern | Source |
|---------|-------|
| Contains "applicant", "document_no", "pan", "bank", "account" | by_client = YES |
| Contains "met_with", "locality", "nob", "time_period" | by_verifier = YES |
| Contains "status" | by_findings = YES |

## Usage

### Creating a New Task

1. Go to **Tasks Management**
2. Click **Add New Task**
3. Fill in task details:
   - Task Type (PHYSICAL, ITO, BANKING)
   - Task Name
   - Positive Format (with `#variables#`)
   - Negative Format (with `#variables#`)
   - CNV Format (with `#variables#`)
4. Click **Save**
5. System automatically:
   - Extracts all variables from formats
   - Creates/updates `tasks_meta` entries
   - Shows success message

### Editing a Task

1. Go to **Tasks Management**
2. Click **Edit** on a task
3. Update report formats
4. Click **Save**
5. System automatically updates `tasks_meta` with new variables

### Viewing Tasks Meta

1. Go to **Tasks Meta Management**
2. Filter by task_id to see all fields for a task
3. Edit individual field properties if needed

## Database Structure

### tasks Table
```sql
- id
- task_type (PHYSICAL, ITO, BANKING)
- task_name
- positive_format (text with #variables#)
- negative_format (text with #variables#)
- cnv_format (text with #variables#)
```

### tasks_meta Table
```sql
- id
- task_id (FK to tasks.id)
- field_name (variable name without #)
- display_name (human-readable name)
- input_type (TEXT, DATE, NUMBER, TEXTAREA, SELECT)
- by_client (YES/NO)
- by_verifier (YES/NO)
- by_findings (YES/NO)
```

## Function Reference

### `update_tasks_meta_from_formats($task_id, $positive_format, $negative_format, $cnv_format)`

Extracts variables from all three formats and updates tasks_meta.

**Parameters:**
- `$task_id`: Task ID
- `$positive_format`: Positive report format text
- `$negative_format`: Negative report format text
- `$cnv_format`: CNV report format text

**Returns:**
```php
[
    'success' => true,
    'inserted' => 5,  // New fields added
    'updated' => 3,    // Existing fields updated
    'total' => 8       // Total variables found
]
```

## Best Practices

1. **Use Consistent Variable Names**: Use lowercase with underscores (e.g., `#applicant_name#`)
2. **Include All Formats**: Fill positive_format, negative_format, and cnv_format for complete coverage
3. **Review Auto-Detection**: Check tasks_meta after saving to verify field properties
4. **Manual Override**: Edit tasks_meta entries if auto-detection is incorrect
5. **Test Variables**: Verify all variables are extracted correctly

## Troubleshooting

### Issue: Variables not extracted
- **Solution**: Ensure variables are wrapped in `#` (e.g., `#variable_name#`)
- Check format fields are not empty

### Issue: Wrong input type detected
- **Solution**: Manually edit tasks_meta entry to correct input_type

### Issue: Wrong field source (by_client/by_verifier)
- **Solution**: Manually edit tasks_meta entry to correct source flags

### Issue: Display name not correct
- **Solution**: System uses mapping table, but you can manually edit display_name in tasks_meta

---

**Note**: The auto-update runs automatically when tasks are saved. No manual intervention needed!

