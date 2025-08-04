# Entity Relationship Diagram (ERD)

## Visual Database Schema

```
                    FORM BUILDER DATABASE SCHEMA
                           
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  ┌─────────────────┐   creates    ┌─────────────────┐   receives   ┌─────────────────┐
│  │     USERS       │──────────────>│     FORMS       │─────────────>│   SUBMISSIONS   │
│  ├─────────────────┤              ├─────────────────┤              ├─────────────────┤
│  │ 🔑 id (PK)      │              │ 🔑 id (PK)      │              │ 🔑 id (PK)      │
│  │ 👤 username     │              │ 📝 title        │              │ 🆔 uniqueId     │
│  │ 📧 email        │              │ 📄 description  │              │ 🔢 editCode     │
│  │ 🔒 password     │              │ 🗂️  fields       │ (JSON)       │ 🔗 formId (FK)  │
│  │ 👑 role         │ (ENUM)       │ ✅ isActive     │              │ 📊 data         │ (JSON)
│  │ 🛡️  permissions │ (JSON)       │ ✏️  allowEditing │              │ 👤 submitterInfo│ (JSON)
│  │ ✅ isActive     │              │ 🔗 createdBy(FK)│              │ 💳 paymentInfo  │ (JSON)
│  │ 📅 createdAt    │              │ ⚙️  settings     │ (JSON)       │ 📊 status       │ (ENUM)
│  │ 📅 updatedAt    │              │ 📊 submissionCnt│              │ 📁 files        │ (JSON)
│  └─────────────────┘              │ 📈 analytics    │ (JSON)       │ 📝 adminNotes   │
│                                   │ 🔗 customUrl    │              │ 📚 editHistory  │ (JSON)
│                                   │ 📅 createdAt    │              │ 💳 paymentMethod│ (ENUM)
│                                   │ 📅 updatedAt    │              │ 📅 createdAt    │
│                                   └─────────────────┘              │ 📅 updatedAt    │
│                                                                    └─────────────────┘
│                                                                             
│  ┌─────────────────┐                              ┌─────────────────┐      
│  │  BKASH_TOKENS   │                              │    SETTINGS     │      
│  ├─────────────────┤                              ├─────────────────┤      
│  │ 🔑 id (PK)      │                              │ 🔑 id (PK)      │      
│  │ 🏷️  service     │                              │ 🔑 key          │      
│  │ 🔐 authToken    │                              │ 📊 value        │ (JSON)
│  │ 🔄 refreshToken │                              │ 🏷️  category    │      
│  │ ⏰ tokenExpiresAt│                              │ ✅ isActive     │      
│  │ ⏰ lastTokenCall │                              │ 📅 createdAt    │      
│  │ 🔢 tokenCallCnt │                              │ 📅 updatedAt    │      
│  │ ✅ isActive     │                              └─────────────────┘      
│  │ 📅 createdAt    │                                                      
│  │ 📅 updatedAt    │                                                      
│  └─────────────────┘                                                      
│                                                                             
└─────────────────────────────────────────────────────────────────────────────┘

## Relationship Details

### Primary Relationships
┌─────────────┬─────────────┬──────────────┬─────────────────────────────┐
│ From Table  │ To Table    │ Relationship │ Description                 │
├─────────────┼─────────────┼──────────────┼─────────────────────────────┤
│ users       │ forms       │ 1:N          │ User creates multiple forms │
│ forms       │ submissions │ 1:N          │ Form has many submissions   │
└─────────────┴─────────────┴──────────────┴─────────────────────────────┘

### Foreign Key Constraints
┌─────────────────┬──────────────┬─────────────────┬──────────────────┐
│ Child Table     │ Child Column │ Parent Table    │ Parent Column    │
├─────────────────┼──────────────┼─────────────────┼──────────────────┤
│ forms           │ createdBy    │ users           │ id               │
│ submissions     │ formId       │ forms           │ id               │
└─────────────────┴──────────────┴─────────────────┴──────────────────┘

## Data Flow Diagram

```
   ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
   │    USER     │───>│    FORM     │───>│ SUBMISSION  │───>│   PAYMENT   │
   │ Registration│    │  Creation   │    │   Submit    │    │ Processing  │
   └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
          │                   │                   │                   │
          v                   v                   v                   v
   ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
   │   users     │    │    forms    │    │ submissions │    │ paymentInfo │
   │   table     │    │    table    │    │    table    │    │ (JSON field)│
   └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

## Table Sizes and Performance

### Estimated Record Counts (Production)
```
┌─────────────┬─────────────┬─────────────┬─────────────────────────────┐
│ Table       │ Records     │ Growth Rate │ Storage Estimate            │
├─────────────┼─────────────┼─────────────┼─────────────────────────────┤
│ users       │ 1,000       │ 10/month    │ ~200KB                      │
│ forms       │ 100         │ 5/month     │ ~50KB (excl. JSON fields)   │
│ submissions │ 10,000      │ 500/month   │ ~5MB (incl. JSON data)      │
│ settings    │ 50          │ 1/month     │ ~10KB                       │
│ bkash_tokens│ 5           │ Minimal     │ ~5KB                        │
└─────────────┴─────────────┴─────────────┴─────────────────────────────┘
```

## Index Strategy

### High-Performance Queries
```sql
-- Most common queries and their indexes:

-- 1. User login (email/username lookup)
SELECT * FROM users WHERE email = ? OR username = ?;
INDEX: users(email), users(username)

-- 2. Get user's forms
SELECT * FROM forms WHERE createdBy = ? AND isActive = 1;
INDEX: forms(createdBy, isActive)

-- 3. Get form submissions by date
SELECT * FROM submissions WHERE formId = ? ORDER BY createdAt DESC;
INDEX: submissions(formId, createdAt)

-- 4. Find submission by unique ID
SELECT * FROM submissions WHERE uniqueId = ?;
INDEX: submissions(uniqueId) [UNIQUE]

-- 5. Admin dashboard - pending submissions
SELECT COUNT(*) FROM submissions WHERE status = 'pending';
INDEX: submissions(status)
```

## JSON Field Structures

### Form Fields Definition
```json
{
  "fields": [
    {
      "id": "field_001",
      "type": "text",
      "label": "Full Name",
      "required": true,
      "validation": {
        "minLength": 2,
        "maxLength": 100,
        "pattern": "^[a-zA-Z\\s]+$"
      },
      "placeholder": "Enter your full name",
      "order": 1
    },
    {
      "id": "field_002", 
      "type": "email",
      "label": "Email Address",
      "required": true,
      "validation": {
        "email": true
      },
      "order": 2
    },
    {
      "id": "field_003",
      "type": "select",
      "label": "Graduation Year",
      "required": true,
      "options": [
        {"value": "2020", "label": "2020"},
        {"value": "2021", "label": "2021"},
        {"value": "2022", "label": "2022"}
      ],
      "order": 3
    }
  ]
}
```

### Submission Data
```json
{
  "data": {
    "field_001": "Dr. John Doe",
    "field_002": "john.doe@example.com", 
    "field_003": "2021"
  },
  "submitterInfo": {
    "ipAddress": "192.168.1.100",
    "userAgent": "Mozilla/5.0...",
    "submitTime": "2025-08-04T10:30:00Z",
    "sessionId": "sess_123456"
  },
  "paymentInfo": {
    "amount": 1000.00,
    "currency": "BDT",
    "method": "bkash",
    "transactionId": "TXN123456789",
    "status": "completed",
    "completedAt": "2025-08-04T10:35:00Z",
    "gateway": {
      "provider": "bkash",
      "paymentID": "TR001234567890",
      "trxID": "8M1A2B3C4D"
    }
  }
}
```

### User Permissions
```json
{
  "permissions": [
    "forms.create",
    "forms.edit_own", 
    "submissions.view_own",
    "submissions.edit_own",
    "payments.view_own"
  ]
}
```

## Security Considerations

### Data Protection
- **PII Encryption**: Consider encrypting sensitive personal data
- **Payment Data**: Payment details stored as references, not card numbers
- **File Security**: Files stored outside web root with access controls
- **Audit Trail**: Edit history tracks all changes with timestamps

### Access Control Matrix
```
┌─────────────────┬─────┬──────┬────────────┬─────────────┬─────────────┐
│ Resource        │User │Admin │Form Manager│Payment Appr.│Sub. Viewer  │
├─────────────────┼─────┼──────┼────────────┼─────────────┼─────────────┤
│ Create Forms    │  ✅  │  ✅   │     ✅      │      ❌      │      ❌      │
│ Edit Own Forms  │  ✅  │  ✅   │     ✅      │      ❌      │      ❌      │
│ Edit All Forms  │  ❌  │  ✅   │     ✅      │      ❌      │      ❌      │
│ View Submissions│  ❌  │  ✅   │     ✅      │      ✅      │      ✅      │
│ Edit Submissions│  ❌  │  ✅   │     ❌      │      ❌      │      ❌      │
│ Approve Payments│  ❌  │  ✅   │     ❌      │      ✅      │      ❌      │
│ Export Data     │  ❌  │  ✅   │     ✅      │      ❌      │      ✅      │
└─────────────────┴─────┴──────┴────────────┴─────────────┴─────────────┘
```

This comprehensive database model provides a solid foundation for the form builder application with proper relationships, indexing, and security considerations.
