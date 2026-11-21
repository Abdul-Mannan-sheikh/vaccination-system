# Entity Relationship Diagram: VaxTrack

Paste the Mermaid block below into https://mermaid.live to see the picture, then export it as PNG or SVG for the project report.

```mermaid
erDiagram
    USERS ||--o{ CHILDREN : "parent has"
    USERS ||--o| HOSPITALS : "hospital login"
    USERS ||--o{ BOOKINGS : "parent requests"
    USERS ||--o{ NOTIFICATIONS : receives
    CHILDREN ||--o{ CHILD_VACCINATIONS : "has schedule"
    VACCINES ||--o{ CHILD_VACCINATIONS : "scheduled as"
    HOSPITALS ||--o{ CHILD_VACCINATIONS : administers
    HOSPITALS ||--o{ BOOKINGS : hosts
    CHILD_VACCINATIONS ||--o{ BOOKINGS : "booked via"

    USERS {
        int id PK
        string name
        string email UK
        string password
        string phone
        string address
        string city
        enum role "admin, parent, hospital"
        enum status "active, blocked"
        timestamp created_at
    }
    HOSPITALS {
        int id PK
        int user_id FK
        string name
        string address
        string city
        string phone
        decimal latitude
        decimal longitude
        enum status "active, inactive"
    }
    CHILDREN {
        int id PK
        int parent_id FK
        string name
        date dob
        enum gender
        string blood_group
    }
    VACCINES {
        int id PK
        string name
        string protects_from
        int age_days
        bool is_available
    }
    CHILD_VACCINATIONS {
        int id PK
        int child_id FK
        int vaccine_id FK
        date due_date
        enum status "pending, booked, vaccinated"
        date vaccinated_on
        int hospital_id FK
    }
    BOOKINGS {
        int id PK
        int child_vaccination_id FK
        int parent_id FK
        int hospital_id FK
        date preferred_date
        time preferred_time
        enum status "pending, approved, rejected, completed, cancelled"
        string admin_remark
    }
    NOTIFICATIONS {
        int id PK
        int user_id FK
        string title
        string message
        bool is_read
    }
```

## Table summary

| Table | Purpose |
|---|---|
| users | One login table for admin, parent and hospital (role column) |
| hospitals | Hospital profile, linked to its login |
| children | Children registered by a parent |
| vaccines | Master vaccination schedule (age in days, availability) |
| child_vaccinations | Personal schedule generated for each child, one row per vaccine |
| bookings | Parent request, admin decision and hospital result |
| notifications | Dashboard alerts |