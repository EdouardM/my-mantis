﻿# my-mantis
My Mantis Install

## Setup Steps: 

### 1: Projects Setup:
refer to mantis_setup_v.3.0.xlsx -> projects tab

### 2: Categories Setup:
refer to mantis_setup_v.3.0.xlsx -> Categories - Custom Fields tab

### 3: Custom Fields Setup:

<strong>Don't forget UTF-8 enconding on custom_strings_inc.php</strong>

<strong>Don't forget break; on french in custom_strings_inc.php</strong>

refer to mantis_setup_v.3.0.xlsx -> Categories - Custom Fields tab

### 4: Custom Access Levels:
refer to mantis_setup_v.3.0.xlsx -> Standard Fields Customization tab


### 5: Standard Fiels Customization:
    - Reproducibility => Cause
    - Resolution => Hotline Action
    - Severity => Request Type
refer to mantis_setup_v.3.0.xlsx -> Standard Fields Customization tab

### 6: Status Update
    - Update Constants
    - Update enum
    - Translation
    - Workflow
    
### 7: Update Config paraméters

### 8: Setup Config for Time Tracking

### 9: Setup of plugins:
    - The Poser => Nice look
    - jQuery => Useful for custom Reports
    - ArraytoExcel => Depency for Custom Report
    - Custom Reports=> Report
    - ManageUserGroup => To have grouping of users, useful for stats it will replace custom field Service
            - Requires to update 3 files
    - Pokret Billing => Efficient time tracking solution
            - Requires to turn off standard time-tacking in Mantis configuration
            
    

 