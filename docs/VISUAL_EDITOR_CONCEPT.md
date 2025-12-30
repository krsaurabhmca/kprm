# Visual Editor Concept - Report Template Designer

## Overview
The Visual Editor is an Excel-like WYSIWYG (What You See Is What You Get) template designer that allows users to create report templates visually without writing HTML code.

## Key Features

### 1. **Excel-like Editing Interface**
- **ContentEditable Canvas**: Main editing area where users can directly type and format content
- **Cell-based Table Editing**: Insert tables and edit cells like Excel
- **Multi-cell Selection**: Select multiple cells (Ctrl+Click or Shift+Click) to apply formatting to all
- **Real-time Preview**: See exactly how the report will look

### 2. **Formatting Toolbar**
- **Text Formatting**: Bold, Italic, Underline, Font Size, Text Color, Alignment
- **Cell Management**: Merge, Split, Border, Width, Height, Text Wrap
- **Background Color**: Fill cells with colors
- **Border Design**: Add borders to cells and tables

### 3. **Component Library**
- **Tables**: Insert tables with custom rows/columns
- **Headers**: Pre-formatted header sections
- **Text Blocks**: Text areas for content
- **Images**: Insert image placeholders
- **Signature Areas**: Signature and stamp placeholders
- **Dividers**: Horizontal lines for section separation
- **Logo/Stamp**: Upload and insert logo/stamp images with resize capability

### 4. **Placeholder System**

#### **Client Meta Variables**
- All fields from `clients_meta` table for the selected client
- Dynamically loaded based on client_id
- Example: `#applicant_name#`, `#address#`, `#phone#`, etc.

#### **Task Variables (Loop)**
- `#task_loop_start#` - Start of task repetition loop
- `#task_loop_end#` - End of task repetition loop
- `#task_serial#` - Auto-incrementing serial number (optional)
- `#task_type#` - Task type from task template
- `#task_name_loop#` - Task name
- `#task_remarks_loop#` - Task remarks/findings

#### **Logo/Stamp Upload**
- Direct upload of logo and stamp images
- Preview before insertion
- Resize controls (width/height)
- Drag to resize in canvas (Ctrl+Drag)
- Images inserted directly into template

### 5. **Excel Data Paste**
- Paste tab-separated data from Excel
- Automatically creates formatted HTML table
- Preserves basic formatting

### 6. **Search Functionality**
- Search placeholders by name or display name
- Real-time filtering of placeholder list
- Quick access to needed variables

## Report Structure Concept

### **Top Section: Client Information**
- Use Client Meta Variables to display client details
- Example: Name, Address, Application Number, etc.

### **Middle Section: Task Loop**
```
#task_loop_start#
Serial: #task_serial# | Type: #task_type# | Task: #task_name_loop#
Findings: #task_remarks_loop#
#task_loop_end#
```
- Repeats for all tasks in the case
- Serial number auto-increments
- Each task shows its type, name, and remarks

### **Bottom Section: Attachments**
- Images displayed 2 per row
- Documents shown as links with icons
- Only attachments marked for report display

## Usage Workflow

1. **Select Client**: Choose client for template (loads client meta fields)
2. **Design Layout**: Use toolbar to format and structure the template
3. **Insert Placeholders**: Click placeholder buttons to insert variables
4. **Upload Assets**: Upload logo/stamp images and resize as needed
5. **Create Task Loop**: Use `#task_loop_start#` and `#task_loop_end#` to repeat task section
6. **Save Template**: Template HTML is saved and can be used for report generation

## Technical Implementation

- **Frontend**: HTML5 ContentEditable, JavaScript, CSS
- **Backend**: PHP for template storage and retrieval
- **Database**: `report_templates` table stores HTML and CSS
- **Report Generation**: Template HTML is processed to replace placeholders with actual data

## Benefits

1. **No Coding Required**: Visual editing eliminates need for HTML knowledge
2. **Real-time Preview**: See results immediately
3. **Flexible Design**: Excel-like features provide professional formatting
4. **Dynamic Content**: Placeholders automatically populate with case data
5. **Reusable Templates**: Create once, use for multiple cases
6. **Client-Specific**: Templates can be customized per client

