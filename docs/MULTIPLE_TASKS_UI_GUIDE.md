# KPRM - Multiple Tasks UI Guide

## Overview

The case initialization system now supports **multiple tasks per case** with an intuitive accordion interface. Each task displays its fields in an organized, collapsible panel.

## New Flow

### Step 1: Select Client
- User selects a client from dropdown
- Uses `dropdown_list()` function

### Step 2: Fill Client Meta Form
- Dynamic form generated from `clients_meta` where `by_client = 'YES'`
- Creates the case record
- Saves client meta data

### Step 3: Add Multiple Tasks (Accordion Interface)
- **View All Tasks**: All tasks for the case are displayed in an accordion
- **Add New Task**: Click "Add New Task" button to open modal
- **Task Details**: Each accordion panel shows:
  - Task name and type
  - Task status badge
  - All task fields with values
  - Edit/Delete buttons

## Accordion Features

### Task Display
- **Collapsible Panels**: Each task is in its own accordion panel
- **Status Badges**: Color-coded status indicators
  - PENDING (yellow)
  - IN_PROGRESS (blue)
  - COMPLETED (green)
  - REJECTED (red)
- **Task Type Badge**: Shows PHYSICAL, ITO, or BANKING
- **Field Values**: All task meta fields displayed in organized grid

### Add Task Modal
1. Select **Task Type** (PHYSICAL, ITO, BANKING)
2. Select **Task Name** (filtered by type)
3. Fill **Task Fields** (dynamically loaded based on selected task)
4. Click **Add Task** to save

### Task Management
- **Edit Task**: Click "Edit Task" button to modify task data
- **Delete Task**: Click "Delete Task" button to remove task
- **View Fields**: Expand accordion to see all field values

## Database Structure

### Tables Used
- **`cases`**: Case master record
- **`case_tasks`**: Task instances linked to cases
  - `case_id`: FK to cases.id
  - `task_template_id`: FK to tasks.id
  - `task_data`: JSON containing all task meta field values
  - `task_status`: PENDING, IN_PROGRESS, COMPLETED, REJECTED
- **`tasks_meta`**: Field definitions for each task template

## AJAX Endpoints

### `get_tasks_by_type.php`
- Returns tasks filtered by task type
- Used to populate task dropdown in modal

### `get_task_fields.php`
- Returns HTML form fields for selected task
- Only shows fields where `by_client = 'YES'`
- Dynamically loads when task is selected

## UI Components

### Accordion Structure
```html
<div class="accordion" id="tasksAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button">
                Task Name | Status Badge | Type Badge
            </button>
        </h2>
        <div class="accordion-collapse collapse">
            <div class="accordion-body">
                <!-- Task Fields Display -->
                <!-- Edit/Delete Buttons -->
            </div>
        </div>
    </div>
</div>
```

### Modal Structure
```html
<div class="modal" id="addTaskModal">
    <!-- Task Type Select -->
    <!-- Task Name Select -->
    <!-- Dynamic Task Fields Container -->
</div>
```

## JavaScript Functions

### `loadTaskNames()`
- Loads tasks when task type is selected
- Updates task dropdown

### `loadTaskFields(taskId)`
- Loads task fields when task is selected
- Displays form fields in modal

### `editTask(taskInstanceId, taskTemplateId)`
- Opens edit page for task
- Passes task instance and template IDs

### `deleteTask(taskInstanceId)`
- Deletes task via AJAX
- Confirms before deletion
- Reloads page after success

## Usage Example

1. **Create Case**:
   - Select client → Fill client info → Case created

2. **Add First Task**:
   - Click "Add New Task"
   - Select PHYSICAL → Select "Residence"
   - Fill fields (applicant_name, address, etc.)
   - Click "Add Task"

3. **Add More Tasks**:
   - Click "Add New Task" again
   - Select ITO → Select "ITR"
   - Fill fields
   - Click "Add Task"

4. **View All Tasks**:
   - All tasks shown in accordion
   - Expand each to see fields
   - Edit or delete as needed

5. **Complete Setup**:
   - Click "Complete Case Setup"
   - Redirects to case management

## Benefits

✅ **Multiple Tasks**: Add unlimited tasks to a case  
✅ **Organized View**: Accordion makes it easy to see all tasks  
✅ **Quick Access**: Expand/collapse individual tasks  
✅ **Field Visibility**: All task fields visible at a glance  
✅ **Easy Management**: Edit/delete tasks inline  
✅ **Dynamic Forms**: Fields load based on task selection  
✅ **Status Tracking**: Visual status indicators  

---

**Note**: Ensure `case_tasks` table exists. Run `SOURCE db/create_case_tasks_table.sql;` if needed.

