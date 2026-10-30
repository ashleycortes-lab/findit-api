# SYSTEM INTEGRATION AND ARCHITECTURE (SIA) & IAS
## Final Project Implementation Checklist & Technical Documentation

**System Title:** **FINDIT: Campus Lost & Found Retrieval, Custody Tracking & Storage Locker Management System**  
**Course Code:** System Integration and Architecture 1 (SIA) / Information Assurance & Security 1 (IAS)  
**Database & Backend Engine:** Supabase PostgreSQL & Cloud Storage  

---

## Implementation Checklist Status

| Component | Standard / Notation | Status |
| :--- | :--- | :--- |
| **1. Use Case Diagram** | Standard UML Use Case with primary/secondary actors & boundary | Completed |
| **2. System Flow Diagram** | Swimlanes & Pools (User, Staff, Admin, Supabase Cloud) | Completed |
| **3. Entity Relationship Diagram (ERD)** | Relational Schema (PKs, FKs, Data Types, Relationships) | Completed |
| **4. System Flowchart** | Standard Flowchart Symbols (Process, Decisions, I/O, Storage) | Completed |
| **5. Network Diagram** | Physical & Logical Network Flow (Clients, Routers, Cloud SSL/TLS) | Completed |

---

## 1. Use Case Diagram (System Users)

```mermaid
flowchart LR
    subgraph Actors ["System Actors"]
        STU["Student / Campus User<br/>(Primary Actor)"]
        STF["Campus Staff / Security<br/>(Operational Actor)"]
        ADM["System Administrator<br/>(Governance Actor)"]
        EXT_SB["Supabase Cloud Platform<br/>(Database, Auth, Storage)"]
    end

    subgraph SystemBoundary ["FINDIT System Boundary"]
        UC1(["Register Account with OTP Verification"])
        UC2(["Authenticate & Secure Session Login"])
        UC3(["Report Lost Item (No Camera - Upload Photo)"])
        UC4(["Report Found Item (Camera Capture / Multi-upload)"])
        UC5(["Browse & Search Item Blotter Feed"])
        UC6(["Filter Items by Month (Jan - Dec) & Date"])
        UC7(["Verify Item Status (Pending / Matched / Claimed / Resolved)"])
        UC8(["Assign Storage Locker (Unique Number Selector)"])
        UC9(["Manage User Accounts & Roles (RBAC)"])
        UC10(["Inspect Tamper-Proof Security Audit Trails"])
        UC11(["Export Cloud Database Snapshot Backup"])
        UC12(["Account Security (OTP Password Reset & Lockout)"])
    end

    %% Student Connections
    STU --> UC1
    STU --> UC2
    STU --> UC3
    STU --> UC4
    STU --> UC5
    STU --> UC12

    %% Staff Connections
    STF --> UC2
    STF --> UC4
    STF --> UC5
    STF --> UC7
    STF --> UC8

    %% Admin Connections
    ADM --> UC2
    ADM --> UC5
    ADM --> UC6
    ADM --> UC7
    ADM --> UC8
    ADM --> UC9
    ADM --> UC10
    ADM --> UC11
    ADM --> UC12

    %% External System
    UC3 -.-> EXT_SB
    UC4 -.-> EXT_SB
    UC8 -.-> EXT_SB
    UC10 -.-> EXT_SB
    UC11 -.-> EXT_SB
```

---

## 2. System Flow Diagram (Swimlanes and Pools)

```mermaid
sequenceDiagram
    autonumber
    actor User as Campus User (Student/Faculty)
    participant ClientApp as FINDIT Client UI (SPA)
    actor Staff as Campus Staff / Security
    actor Admin as System Administrator
    participant Supabase as Supabase (PostgreSQL & Storage)

    Note over User,Supabase: Phase 1: Found Item Surrender & Intake
    User->>ClientApp: Submit Found Item (Photo Evidence & Details)
    ClientApp->>Supabase: Upload Photo to 'item-photos' Bucket & Insert into 'items' Table
    Supabase-->>ClientApp: Return Item Record (Status: 'Pending')
    
    Note over User,Supabase: Phase 2: Custody & Locker Storage Assignment
    Staff->>ClientApp: Inspect Surrendered Item in Staff Console
    Staff->>ClientApp: Select Available Storage Locker (e.g., Locker #07)
    ClientApp->>ClientApp: Validate Locker Not Occupied by Active Item
    ClientApp->>Supabase: Update item (locker_location: '7', status: 'In Custody')
    ClientApp->>Supabase: Insert Audit Log ('LOCKER_ASSIGNMENT', StaffEmail)

    Note over User,Supabase: Phase 3: Claim Verification & Audit Resolution
    User->>Staff: Provide Claim Verification Proof
    Staff->>ClientApp: Change Status to 'Resolved'
    ClientApp->>Supabase: Update status to 'Resolved' & Free Locker to 'Unassigned'
    ClientApp->>Supabase: Insert Audit Log ('ITEM_STATUS_CHANGE', 'Resolved')

    Note over User,Supabase: Phase 4: Administrative Governance & Audit Reporting
    Admin->>ClientApp: Access Admin Panel (January - December Date Filter)
    ClientApp->>Supabase: Query filtered audit logs and item custody statistics
    Supabase-->>Admin: Render filtered reports and allow JSON/CSV export
```

---

## 3. Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS ||--o{ ITEMS : "reports"
    USERS ||--o{ AUDIT_LOGS : "triggers"
    USERS ||--o{ PASSWORD_RESETS : "initiates"
    ITEMS ||--o{ ITEM_TRACE_LOGS : "tracks"
    LOCKERS ||--o| ITEMS : "stores"

    USERS {
        uuid id PK "Primary Key (gen_random_uuid())"
        varchar email UK "Campus Email (Unique)"
        varchar full_name "User Full Name"
        varchar role "Student | Staff | Admin"
        varchar password_hash "SHA-256 Hashed Password"
        boolean is_active "Account Status"
        boolean is_locked "Brute-force Lockout Flag"
        int failed_attempts "Consecutive Failed Counter"
        timestamp lockout_until "Lockout Expiration"
        timestamp created_at "Registration Timestamp"
    }

    ITEMS {
        bigserial id PK "Sequential Item Blotter ID"
        uuid user_id FK "References USERS(id)"
        varchar type "Lost | Found"
        varchar title "Item Title"
        varchar category "Electronics, Valuables, IDs, Keys, Books, Others"
        text description "Detailed Description"
        varchar location "Campus Location Found/Lost"
        varchar status "Pending | In Custody | Matched | Claimed | Resolved"
        varchar locker_location "Locker #1 - #30 or 'Unassigned'"
        varchar image_url "Supabase Storage Public URL"
        timestamp date_reported "Timestamp of Blotter Entry"
        timestamp created_at "Creation Timestamp"
    }

    LOCKERS {
        int locker_number PK "Locker Identifier (1 to 30)"
        varchar status "Available | Occupied | Maintenance"
        bigint current_item_id FK "References active ITEMS(id)"
    }

    AUDIT_LOGS {
        bigserial id PK "Audit Record Identifier"
        varchar action_type "AUTH | ITEM_STATUS | LOCKER_ASSIGNMENT | USER_MGMT"
        varchar performed_by "Email of Actor"
        text details "Tamper-proof Log Details"
        varchar ip_address "Client IP Address"
        timestamp created_at "Log Event Timestamp"
    }

    ITEM_TRACE_LOGS {
        bigserial id PK "Trace Identifier"
        bigint item_id FK "References ITEMS(id)"
        varchar action "Intake, Relocated, Verified, Released"
        varchar performed_by "Staff Email"
        timestamp timestamp "Trace Timestamp"
    }

    PASSWORD_RESETS {
        bigserial id PK "Reset Identifier"
        varchar email "Associated Email"
        varchar otp_code "6-Digit Secure OTP"
        boolean is_used "One-Time Usage Status"
        timestamp expires_at "Expiration Window (15 Mins)"
    }
```

---

## 4. System Flowchart

```mermaid
flowchart TD
    START([User Launches FINDIT System]) --> AUTH_CHECK{Authenticated Session?}
    
    %% Auth Sub-process
    AUTH_CHECK -- No --> LOGIN_SCREEN[/Enter Campus Email & Password/]
    LOGIN_SCREEN --> PASS_CHECK{Verify SHA-256 Hash?}
    
    PASS_CHECK -- Invalid --> ATTEMPT_INC[Increment Failed Attempts]
    ATTEMPT_INC --> LOCKOUT_CHECK{Failed Attempts >= 3?}
    LOCKOUT_CHECK -- Yes --> LOCK_ACC[Lock Account with Progressive Timer & CAPTCHA]
    LOCK_ACC --> LOG_AUTH_FAIL[(Record FAILED in Audit Logs)] --> LOGIN_SCREEN
    LOCKOUT_CHECK -- No --> SHOW_ERR[/Display Remaining Attempts/] --> LOGIN_SCREEN
    
    PASS_CHECK -- Valid --> RESET_FAIL[Reset Failed Attempts = 0]
    RESET_FAIL --> ROLE_EVAL{Evaluate Role}
    AUTH_CHECK -- Yes --> ROLE_EVAL

    %% Role Routing
    ROLE_EVAL -- Student / User --> USER_PORTAL[Load Campus Blotter Feed]
    ROLE_EVAL -- Staff --> STAFF_PORTAL[Load Custody & Locker Management]
    ROLE_EVAL -- Admin --> ADMIN_PORTAL[Load Admin Governance & Date Filters]

    %% Report Filing Branch
    USER_PORTAL --> FILE_REPORT{Report Type?}
    FILE_REPORT -- Lost Item --> UPLOAD_FILE[/Upload Photo from File (Camera Disabled)/]
    FILE_REPORT -- Found Item --> CAPTURE_PHOTO[/Capture via Camera or Select File/]
    
    UPLOAD_FILE --> SUBMIT_ITEM[Upload Media to Supabase Storage Bucket]
    CAPTURE_PHOTO --> SUBMIT_ITEM
    SUBMIT_ITEM --> SAVE_DB[(Insert Record into 'items' Table)]
    SAVE_DB --> REFRESH_FEED[/Update Blotter Feed Display/]

    %% Staff Locker Assignment Branch
    STAFF_PORTAL --> SELECT_ITEM[Select Active Found Item]
    SELECT_ITEM --> PICK_LOCKER[/Select Locker Number (1 to 30)/]
    PICK_LOCKER --> LOCKER_VAL{Is Locker Currently Occupied?}
    
    LOCKER_VAL -- Yes --> REJECT_LOCKER[/Show Warning: Locker Occupied - Duplicate Prevented/]
    REJECT_LOCKER --> PICK_LOCKER
    
    LOCKER_VAL -- No --> ASSIGN_LOCKER[(Update item: locker_location = num)]
    ASSIGN_LOCKER --> LOG_LOCKER[(Log 'LOCKER_ASSIGNMENT' to Audit Trail)]

    %% Claim & Resolution
    ASSIGN_LOCKER --> VERIFY_CLAIM{Citizen Claims Item?}
    VERIFY_CLAIM -- Verified --> RESOLVE_ITEM[(Update status = 'Resolved' & locker = 'Unassigned')]
    RESOLVE_ITEM --> LOG_RESOLVE[(Log 'ITEM_STATUS_CHANGE' to Audit Trail)]
    LOG_RESOLVE --> END_NODE([Cycle Complete])
```

---

## 5. Network Diagram (Network Flow)

```mermaid
graph TB
    subgraph CampusUserZone ["Client Endpoints (Campus Network)"]
        STUDENT_DEV["Student Smartphone / Laptop (Wi-Fi)"]
        STAFF_STATION["Guard Post / Security Staff Station"]
        ADMIN_PC["Campus Administrator Workstation"]
    end

    subgraph NetworkEdge ["Campus Perimeter & Transport Security"]
        FIREWALL["Campus Perimeter Firewall & Router"]
        TLS_EDGE["Cloudflare TLS 1.3 / HTTPS Encryption Termination"]
    end

    subgraph SupabaseCloud ["Supabase Backend-as-a-Service (Cloud Infrastructure)"]
        API_GATEWAY["Kong API Gateway (Port 443 HTTPS / REST API)"]
        POSTGREST["PostgREST REST API Engine"]
        GOTRUE["GoTrue Authentication Engine (Tokens & Passwords)"]
        PG_DB[("PostgreSQL 15 Relational Database Cluster<br/>(Tables: items, users, audit_logs, lockers)")]
        STORAGE_S3[("S3 Object Storage Bucket<br/>(Bucket: 'item-photos')")]
    end

    %% Client Communication
    STUDENT_DEV -- "HTTPS (Port 443)" --> FIREWALL
    STAFF_STATION -- "HTTPS (Port 443)" --> FIREWALL
    ADMIN_PC -- "HTTPS (Port 443)" --> FIREWALL

    %% Perimeter to Edge
    FIREWALL --> TLS_EDGE
    TLS_EDGE --> API_GATEWAY

    %% API Gateway Routing
    API_GATEWAY --> GOTRUE
    API_GATEWAY --> POSTGREST
    API_GATEWAY --> STORAGE_S3

    %% Database Operations
    POSTGREST <--> PG_DB
    GOTRUE <--> PG_DB

    classDef client fill:#e0f2fe,stroke:#0284c7,stroke-width:2px;
    classDef edge fill:#fef3c7,stroke:#d97706,stroke-width:2px;
    classDef cloud fill:#dcfce7,stroke:#16a34a,stroke-width:2px;

    class STUDENT_DEV,STAFF_STATION,ADMIN_PC client;
    class FIREWALL,TLS_EDGE edge;
    class API_GATEWAY,POSTGREST,GOTRUE,PG_DB,STORAGE_S3 cloud;
```
