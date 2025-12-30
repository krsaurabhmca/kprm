# KPRM - Case Initialization Guide

## Overview

The case initialization system allows you to create cases through a multi-step process:
1. Select Client
2. Fill Client Meta Form (dynamic fields based on client configuration)
3. Select Task Type (PHYSICAL, ITO, BANKING)
4. Select Task Name (based on selected task type)
5. Fill Task Meta Form (dynamic fields based on task configuration)

## Database Structure

### Tables Used:
- **`clients`**: Client master data
- **`clients_meta`**: Client-specific field definitions (where `by_client = 'YES'`)
- **`cases`**: Case records
- **`tasks`**: Task templates/definitions
- **`tasks_meta`**: Task-specific field definitions (where `by_client = 'YES'`)
- **`case_tasks`**: Task instances assigned to cases (create using `db/create_case_tasks_table.sql`)

## Setup Instructions

### Step 1: Create case_tasks Table
```sql
SOURCE db/create_case_tasks_table.sql;
```

### Step 2: Configure Client Meta Fields
1. Go to **Clients Meta Management**
2. Add fields for each client where `by_client = 'YES'`
3. Set appropriate `input_type` (TEXT, DATE, NUMBER, SELECT, TEXTAREA)
4. Set `is_unique = 'YES'` for required fields

### Step 3: Configure Task Meta Fields
1. Go to **Tasks Meta Management**
2. For each task, add fields where `by_client = 'YES'`
3. These fields will appear in Step 5 (Task Data Form)

## Usage Flow

### Step 1: Select Client
- User selects a client from dropdown
- Click "Next" to proceed

### Step 2: Client Info Form
- System loads `clients_meta` fields where `by_client = 'YES'`
- Dynamic form is generated based on field definitions
- User fills in client-specific information
- Click "Save & Continue" to create case and proceed

### Step 3: Select Task Type
- User selects task type: PHYSICAL, ITO, or BANKING
- Click "Next" to proceed

### Step 4: Select Task Name
- System loads tasks filtered by selected task type
- User selects specific task (e.g., "Residence", "ITR", "Banking")
- Click "Next" to proceed

### Step 5: Task Data Form
- System loads `tasks_meta` fields for selected task where `by_client = 'YES'`
- Dynamic form is generated based on task field definitions
- User fills in task-specific data
- Click "Save Case & Task" to complete

## File Structure

```
public/
├── add_new_case.php          # Main multi-step form
├── save_case_step.php        # Handler for saving each step
└── case_manage.php           # Case listing page

function.php
├── build_client_meta_form()  # Generates client meta form
└── build_task_meta_form()    # Generates task meta form

db/
└── create_case_tasks_table.sql  # SQL to create case_tasks table
```

## Field Types Supported

- **TEXT**: Single-line text input
- **DATE**: Date picker
- **NUMBER**: Numeric input
- **SELECT**: Dropdown (options need to be configured)
- **TEXTAREA**: Multi-line text input

## Data Flow

1. **Client Meta** → Stored in session temporarily, then saved to case
2. **Case** → Created in `cases` table with `client_id` and `application_no`
3. **Task Instance** → Created in `case_tasks` table with:
   - `case_id`: Reference to case
   - `task_template_id`: Reference to task template
   - `task_data`: JSON containing all task meta field values
   - `task_status`: PENDING (default)

## Next Steps After Case Creation

1. **Verification**: Assign task to verifier
2. **Verifier Form**: Verifier fills fields where `by_verifier = 'YES'`
3. **Review**: Reviewer cross-checks data
4. **Report Generation**: Generate final report using all collected data

## Troubleshooting

### Issue: No fields showing in Step 2
- **Solution**: Check `clients_meta` table for the selected client
- Ensure `by_client = 'YES'` and `status = 'ACTIVE'`

### Issue: No fields showing in Step 5
- **Solution**: Check `tasks_meta` table for the selected task
- Ensure `by_client = 'YES'` and `status = 'ACTIVE'`

### Issue: case_tasks table doesn't exist
- **Solution**: Run `SOURCE db/create_case_tasks_table.sql;`

### Issue: No tasks showing in Step 4
- **Solution**: Check `tasks` table for tasks with matching `task_type` and `status = 'ACTIVE'`

---

**Note**: The system uses session storage for client meta data during the multi-step process. Ensure sessions are properly configured.

