# PNS-Dhampur API Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Data Models](#data-models)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)
7. [Webhooks](#webhooks)
8. [SDK and Libraries](#sdk-and-libraries)
9. [Examples](#examples)
10. [Testing](#testing)

## Introduction

The PNS-Dhampur School Management System provides a comprehensive RESTful API for integrating with external systems, mobile applications, and third-party services. This API allows secure access to student information, attendance data, academic records, and administrative functions.

### Base URL
```
Production: https://pns-dhampur.edu.in/api
Staging: https://staging.pns-dhampur.edu.in/api
Development: http://localhost:8000/api
```

### API Version
Current Version: `v1`

All API endpoints are prefixed with `/v1/` to maintain version compatibility.

### Content Type
All requests and responses use JSON format:
```
Content-Type: application/json
Accept: application/json
```

### Response Format
All API responses follow a consistent structure:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data
    },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "version": "1.0.0",
        "request_id": "req_123456789"
    }
}
```

## Authentication

### Authentication Methods

#### 1. API Token Authentication
```http
Authorization: Bearer your-api-token-here
```

#### 2. Session Authentication (Web)
```http
X-CSRF-TOKEN: csrf-token-value
Cookie: laravel_session=session-value
```

### Obtaining API Tokens

#### Personal Access Tokens
```http
POST /v1/auth/tokens
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password",
    "token_name": "Mobile App Token",
    "abilities": ["read", "write"]
}
```

Response:
```json
{
    "success": true,
    "data": {
        "token": "1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz",
        "token_type": "Bearer",
        "expires_at": "2025-01-15T10:30:00Z",
        "abilities": ["read", "write"]
    }
}
```

#### Token Management
```http
# List tokens
GET /v1/auth/tokens

# Revoke token
DELETE /v1/auth/tokens/{token_id}

# Refresh token
POST /v1/auth/tokens/{token_id}/refresh
```

### Authentication Scopes

| Scope | Description |
|-------|-------------|
| `read` | Read access to all resources |
| `write` | Create and update resources |
| `delete` | Delete resources |
| `admin` | Administrative functions |
| `biometric` | Biometric device management |
| `reports` | Generate and access reports |

## API Endpoints

### Authentication Endpoints

#### Login
```http
POST /v1/auth/login
```

Request:
```json
{
    "email": "user@example.com",
    "password": "password",
    "remember": true
}
```

Response:
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "teacher"
        },
        "token": "1|abc123...",
        "expires_at": "2025-01-15T10:30:00Z"
    }
}
```

#### Logout
```http
POST /v1/auth/logout
Authorization: Bearer {token}
```

#### Password Reset
```http
POST /v1/auth/forgot-password
```

Request:
```json
{
    "email": "user@example.com"
}
```

### User Management

#### Get Current User
```http
GET /v1/user
Authorization: Bearer {token}
```

Response:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "teacher",
        "permissions": ["read", "write"],
        "profile": {
            "phone": "+91-9876543210",
            "address": "123 Main St, City",
            "avatar": "https://example.com/avatar.jpg"
        }
    }
}
```

#### Update User Profile
```http
PUT /v1/user/profile
Authorization: Bearer {token}
```

Request:
```json
{
    "name": "John Doe Updated",
    "phone": "+91-9876543210",
    "address": "456 New St, City"
}
```

#### Change Password
```http
POST /v1/user/change-password
Authorization: Bearer {token}
```

Request:
```json
{
    "current_password": "old_password",
    "new_password": "new_password",
    "new_password_confirmation": "new_password"
}
```

### Student Management

#### List Students
```http
GET /v1/students
Authorization: Bearer {token}
```

Query Parameters:
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `class_id` (integer): Filter by class
- `section` (string): Filter by section
- `search` (string): Search by name or admission number
- `status` (string): Filter by status (active, inactive, graduated)

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "admission_number": "ADM001",
            "name": "Alice Johnson",
            "class": {
                "id": 1,
                "name": "Class 10",
                "section": "A"
            },
            "date_of_birth": "2008-05-15",
            "gender": "female",
            "status": "active",
            "parent_contact": "+91-9876543210",
            "created_at": "2024-01-01T00:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "last_page": 8
    }
}
```

#### Get Student Details
```http
GET /v1/students/{id}
Authorization: Bearer {token}
```

Response:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "admission_number": "ADM001",
        "name": "Alice Johnson",
        "class": {
            "id": 1,
            "name": "Class 10",
            "section": "A"
        },
        "personal_info": {
            "date_of_birth": "2008-05-15",
            "gender": "female",
            "blood_group": "O+",
            "address": "123 Student St, City",
            "phone": "+91-9876543210"
        },
        "parent_info": {
            "father_name": "Robert Johnson",
            "mother_name": "Mary Johnson",
            "guardian_phone": "+91-9876543210",
            "emergency_contact": "+91-9876543211"
        },
        "academic_info": {
            "admission_date": "2024-01-01",
            "roll_number": "10A001",
            "status": "active",
            "previous_school": "ABC School"
        },
        "biometric_info": {
            "enrolled": true,
            "template_count": 2,
            "last_updated": "2024-01-10T10:00:00Z"
        }
    }
}
```

#### Create Student
```http
POST /v1/students
Authorization: Bearer {token}
```

Request:
```json
{
    "admission_number": "ADM002",
    "name": "Bob Smith",
    "class_id": 1,
    "section": "B",
    "date_of_birth": "2008-03-20",
    "gender": "male",
    "blood_group": "A+",
    "address": "456 Student Ave, City",
    "phone": "+91-9876543212",
    "parent_info": {
        "father_name": "John Smith",
        "mother_name": "Jane Smith",
        "guardian_phone": "+91-9876543212",
        "emergency_contact": "+91-9876543213"
    }
}
```

#### Update Student
```http
PUT /v1/students/{id}
Authorization: Bearer {token}
```

#### Delete Student
```http
DELETE /v1/students/{id}
Authorization: Bearer {token}
```

### Teacher Management

#### List Teachers
```http
GET /v1/teachers
Authorization: Bearer {token}
```

Query Parameters:
- `page`, `per_page`, `search` (same as students)
- `subject_id` (integer): Filter by subject
- `department` (string): Filter by department
- `status` (string): Filter by status

#### Get Teacher Details
```http
GET /v1/teachers/{id}
Authorization: Bearer {token}
```

Response:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "employee_id": "EMP001",
        "name": "Dr. Sarah Wilson",
        "email": "sarah.wilson@pns-dhampur.edu.in",
        "phone": "+91-9876543214",
        "department": "Mathematics",
        "designation": "Senior Teacher",
        "subjects": [
            {
                "id": 1,
                "name": "Mathematics",
                "classes": ["Class 9", "Class 10"]
            }
        ],
        "qualifications": [
            "M.Sc. Mathematics",
            "B.Ed."
        ],
        "experience_years": 10,
        "joining_date": "2014-06-01",
        "status": "active"
    }
}
```

### Attendance Management

#### Mark Attendance
```http
POST /v1/attendance
Authorization: Bearer {token}
```

Request:
```json
{
    "class_id": 1,
    "section": "A",
    "date": "2024-01-15",
    "period": 1,
    "attendance_data": [
        {
            "student_id": 1,
            "status": "present"
        },
        {
            "student_id": 2,
            "status": "absent"
        },
        {
            "student_id": 3,
            "status": "late"
        }
    ]
}
```

#### Get Attendance
```http
GET /v1/attendance
Authorization: Bearer {token}
```

Query Parameters:
- `class_id` (integer): Required
- `section` (string): Required
- `date` (date): Specific date (YYYY-MM-DD)
- `date_from` (date): Start date for range
- `date_to` (date): End date for range
- `student_id` (integer): Specific student

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "student": {
                "id": 1,
                "name": "Alice Johnson",
                "admission_number": "ADM001"
            },
            "date": "2024-01-15",
            "period": 1,
            "status": "present",
            "marked_by": {
                "id": 1,
                "name": "Dr. Sarah Wilson"
            },
            "marked_at": "2024-01-15T09:00:00Z",
            "remarks": null
        }
    ]
}
```

#### Attendance Summary
```http
GET /v1/attendance/summary
Authorization: Bearer {token}
```

Query Parameters:
- `student_id` (integer): Required
- `month` (string): YYYY-MM format
- `academic_year` (string): YYYY-YYYY format

Response:
```json
{
    "success": true,
    "data": {
        "student": {
            "id": 1,
            "name": "Alice Johnson",
            "admission_number": "ADM001"
        },
        "period": {
            "start_date": "2024-01-01",
            "end_date": "2024-01-31",
            "total_days": 22,
            "working_days": 20
        },
        "summary": {
            "present_days": 18,
            "absent_days": 2,
            "late_days": 0,
            "attendance_percentage": 90.0
        },
        "daily_attendance": [
            {
                "date": "2024-01-01",
                "status": "present"
            },
            {
                "date": "2024-01-02",
                "status": "absent"
            }
        ]
    }
}
```

### Biometric Integration

#### Device Management
```http
GET /v1/biometric/devices
Authorization: Bearer {token}
```

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Main Entrance Scanner",
            "ip_address": "192.168.1.100",
            "port": 4370,
            "device_type": "fingerprint",
            "location": "Main Entrance",
            "status": "online",
            "last_sync": "2024-01-15T10:00:00Z",
            "enrolled_users": 150,
            "total_capacity": 1000
        }
    ]
}
```

#### Sync Biometric Data
```http
POST /v1/biometric/sync
Authorization: Bearer {token}
```

Request:
```json
{
    "device_id": 1,
    "force_sync": false
}
```

#### Enroll User
```http
POST /v1/biometric/enroll
Authorization: Bearer {token}
```

Request:
```json
{
    "user_id": 1,
    "user_type": "student",
    "device_id": 1,
    "template_data": "base64_encoded_template"
}
```

#### Get Attendance Logs
```http
GET /v1/biometric/attendance-logs
Authorization: Bearer {token}
```

Query Parameters:
- `device_id` (integer): Filter by device
- `date_from` (date): Start date
- `date_to` (date): End date
- `user_id` (integer): Filter by user

### Bell Schedule Management

#### Get Schedules
```http
GET /v1/bell-schedules
Authorization: Bearer {token}
```

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Winter Schedule 2024",
            "effective_from": "2024-11-01",
            "effective_to": "2024-02-28",
            "status": "active",
            "periods": [
                {
                    "id": 1,
                    "name": "Assembly",
                    "start_time": "08:00:00",
                    "end_time": "08:40:00",
                    "bell_type": "assembly"
                },
                {
                    "id": 2,
                    "name": "Period 1",
                    "start_time": "08:40:00",
                    "end_time": "09:20:00",
                    "bell_type": "class_start"
                }
            ]
        }
    ]
}
```

#### Trigger Bell
```http
POST /v1/bell/trigger
Authorization: Bearer {token}
```

Request:
```json
{
    "bell_type": "class_start",
    "zone": "all",
    "duration": 5,
    "message": "Class starting in 2 minutes"
}
```

### Reports

#### Generate Report
```http
POST /v1/reports/generate
Authorization: Bearer {token}
```

Request:
```json
{
    "report_type": "attendance_summary",
    "parameters": {
        "class_id": 1,
        "section": "A",
        "date_from": "2024-01-01",
        "date_to": "2024-01-31"
    },
    "format": "pdf"
}
```

Response:
```json
{
    "success": true,
    "data": {
        "report_id": "rpt_123456789",
        "status": "generating",
        "download_url": null,
        "estimated_completion": "2024-01-15T10:35:00Z"
    }
}
```

#### Get Report Status
```http
GET /v1/reports/{report_id}
Authorization: Bearer {token}
```

#### Download Report
```http
GET /v1/reports/{report_id}/download
Authorization: Bearer {token}
```

### Notifications

#### Send Notification
```http
POST /v1/notifications
Authorization: Bearer {token}
```

Request:
```json
{
    "recipients": [
        {
            "type": "student",
            "id": 1
        },
        {
            "type": "parent",
            "student_id": 1
        }
    ],
    "channels": ["sms", "email"],
    "message": {
        "title": "Important Notice",
        "body": "School will remain closed tomorrow due to weather conditions.",
        "priority": "high"
    },
    "schedule_at": "2024-01-15T18:00:00Z"
}
```

#### Get Notification History
```http
GET /v1/notifications/history
Authorization: Bearer {token}
```

### System Status

#### Health Check
```http
GET /v1/status
```

Response:
```json
{
    "success": true,
    "data": {
        "status": "online",
        "version": "1.0.0",
        "timestamp": "2024-01-15T10:30:00Z",
        "services": {
            "database": "healthy",
            "cache": "healthy",
            "queue": "healthy",
            "storage": "healthy"
        },
        "metrics": {
            "uptime": "99.9%",
            "response_time": "120ms",
            "active_users": 45
        }
    }
}
```

## Data Models

### Student Model
```json
{
    "id": "integer",
    "admission_number": "string",
    "name": "string",
    "class_id": "integer",
    "section": "string",
    "roll_number": "string",
    "date_of_birth": "date",
    "gender": "enum[male,female,other]",
    "blood_group": "string",
    "address": "text",
    "phone": "string",
    "email": "string",
    "status": "enum[active,inactive,graduated,transferred]",
    "admission_date": "date",
    "parent_info": {
        "father_name": "string",
        "mother_name": "string",
        "guardian_name": "string",
        "guardian_phone": "string",
        "emergency_contact": "string"
    },
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Teacher Model
```json
{
    "id": "integer",
    "employee_id": "string",
    "name": "string",
    "email": "string",
    "phone": "string",
    "department": "string",
    "designation": "string",
    "qualifications": "array",
    "experience_years": "integer",
    "joining_date": "date",
    "status": "enum[active,inactive,on_leave]",
    "subjects": "array",
    "classes": "array",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Attendance Model
```json
{
    "id": "integer",
    "student_id": "integer",
    "class_id": "integer",
    "section": "string",
    "date": "date",
    "period": "integer",
    "status": "enum[present,absent,late,excused]",
    "marked_by": "integer",
    "marked_at": "datetime",
    "remarks": "text",
    "biometric_verified": "boolean",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

## Error Handling

### Error Response Format
```json
{
    "success": false,
    "message": "Error description",
    "error": {
        "code": "ERROR_CODE",
        "details": "Detailed error information",
        "field": "field_name" // For validation errors
    },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "request_id": "req_123456789"
    }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Invalid request data |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation errors |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

### Common Error Codes

| Code | Description |
|------|-------------|
| `INVALID_CREDENTIALS` | Invalid login credentials |
| `TOKEN_EXPIRED` | API token has expired |
| `INSUFFICIENT_PERMISSIONS` | User lacks required permissions |
| `VALIDATION_FAILED` | Request validation failed |
| `RESOURCE_NOT_FOUND` | Requested resource not found |
| `DUPLICATE_ENTRY` | Duplicate data entry |
| `RATE_LIMIT_EXCEEDED` | API rate limit exceeded |
| `DEVICE_OFFLINE` | Biometric device is offline |
| `SYNC_FAILED` | Data synchronization failed |

### Validation Errors
```json
{
    "success": false,
    "message": "Validation failed",
    "error": {
        "code": "VALIDATION_FAILED",
        "details": {
            "name": ["The name field is required."],
            "email": ["The email must be a valid email address."],
            "phone": ["The phone format is invalid."]
        }
    }
}
```

## Rate Limiting

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642248000
```

### Rate Limits by Endpoint

| Endpoint Category | Limit | Window |
|-------------------|-------|--------|
| Authentication | 5 requests | 1 minute |
| General API | 60 requests | 1 minute |
| Reports | 10 requests | 1 minute |
| Biometric Sync | 30 requests | 1 minute |
| Bulk Operations | 5 requests | 1 minute |

### Rate Limit Exceeded Response
```json
{
    "success": false,
    "message": "Rate limit exceeded",
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "details": "Too many requests. Please try again in 60 seconds.",
        "retry_after": 60
    }
}
```

## Webhooks

### Webhook Events

#### Student Events
- `student.created`
- `student.updated`
- `student.deleted`
- `student.enrolled` (biometric)

#### Attendance Events
- `attendance.marked`
- `attendance.updated`
- `attendance.summary_generated`

#### System Events
- `system.backup_completed`
- `system.maintenance_started`
- `device.status_changed`

### Webhook Configuration
```http
POST /v1/webhooks
Authorization: Bearer {token}
```

Request:
```json
{
    "url": "https://your-app.com/webhooks/pns-dhampur",
    "events": ["student.created", "attendance.marked"],
    "secret": "your-webhook-secret",
    "active": true
}
```

### Webhook Payload
```json
{
    "event": "student.created",
    "timestamp": "2024-01-15T10:30:00Z",
    "data": {
        "student": {
            "id": 1,
            "name": "Alice Johnson",
            "admission_number": "ADM001"
        }
    },
    "webhook_id": "wh_123456789"
}
```

### Webhook Security
Webhooks are signed using HMAC-SHA256:

```php
$signature = hash_hmac('sha256', $payload, $webhook_secret);
$expected = 'sha256=' . $signature;

// Compare with X-Signature header
if (hash_equals($expected, $_SERVER['HTTP_X_SIGNATURE'])) {
    // Webhook is authentic
}
```

## SDK and Libraries

### Official SDKs

#### PHP SDK
```bash
composer require pns-dhampur/php-sdk
```

```php
use PNSDhampur\SDK\Client;

$client = new Client([
    'base_url' => 'https://pns-dhampur.edu.in/api',
    'token' => 'your-api-token'
]);

// Get students
$students = $client->students()->list([
    'class_id' => 1,
    'section' => 'A'
]);

// Mark attendance
$client->attendance()->mark([
    'class_id' => 1,
    'section' => 'A',
    'date' => '2024-01-15',
    'attendance_data' => [
        ['student_id' => 1, 'status' => 'present'],
        ['student_id' => 2, 'status' => 'absent']
    ]
]);
```

#### JavaScript SDK
```bash
npm install @pns-dhampur/js-sdk
```

```javascript
import { PNSDhampurClient } from '@pns-dhampur/js-sdk';

const client = new PNSDhampurClient({
    baseUrl: 'https://pns-dhampur.edu.in/api',
    token: 'your-api-token'
});

// Get students
const students = await client.students.list({
    class_id: 1,
    section: 'A'
});

// Mark attendance
await client.attendance.mark({
    class_id: 1,
    section: 'A',
    date: '2024-01-15',
    attendance_data: [
        { student_id: 1, status: 'present' },
        { student_id: 2, status: 'absent' }
    ]
});
```

#### Python SDK
```bash
pip install pns-dhampur-sdk
```

```python
from pns_dhampur import Client

client = Client(
    base_url='https://pns-dhampur.edu.in/api',
    token='your-api-token'
)

# Get students
students = client.students.list(class_id=1, section='A')

# Mark attendance
client.attendance.mark({
    'class_id': 1,
    'section': 'A',
    'date': '2024-01-15',
    'attendance_data': [
        {'student_id': 1, 'status': 'present'},
        {'student_id': 2, 'status': 'absent'}
    ]
})
```

## Examples

### Mobile App Integration

#### Student Attendance App
```javascript
// Login and get token
const loginResponse = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'teacher@pns-dhampur.edu.in',
        password: 'password'
    })
});

const { data } = await loginResponse.json();
const token = data.token;

// Get class students
const studentsResponse = await fetch('/api/v1/students?class_id=1&section=A', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});

const students = await studentsResponse.json();

// Mark attendance
const attendanceData = students.data.map(student => ({
    student_id: student.id,
    status: student.present ? 'present' : 'absent'
}));

await fetch('/api/v1/attendance', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        class_id: 1,
        section: 'A',
        date: '2024-01-15',
        period: 1,
        attendance_data: attendanceData
    })
});
```

### Parent Portal Integration

#### Get Child's Attendance
```php
<?php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://pns-dhampur.edu.in/api/v1/',
    'headers' => [
        'Authorization' => 'Bearer ' . $parentToken,
        'Accept' => 'application/json'
    ]
]);

// Get child's attendance summary
$response = $client->get('attendance/summary', [
    'query' => [
        'student_id' => $childId,
        'month' => '2024-01'
    ]
]);

$attendanceData = json_decode($response->getBody(), true);

echo "Attendance Percentage: " . $attendanceData['data']['summary']['attendance_percentage'] . "%";
?>
```

### Biometric Integration

#### Real-time Attendance Sync
```python
import requests
import time
from datetime import datetime

class BiometricSync:
    def __init__(self, api_token, device_id):
        self.api_token = api_token
        self.device_id = device_id
        self.base_url = 'https://pns-dhampur.edu.in/api/v1'
        
    def sync_attendance(self):
        headers = {
            'Authorization': f'Bearer {self.api_token}',
            'Content-Type': 'application/json'
        }
        
        # Get latest attendance logs from device
        response = requests.get(
            f'{self.base_url}/biometric/attendance-logs',
            headers=headers,
            params={
                'device_id': self.device_id,
                'date_from': datetime.now().strftime('%Y-%m-%d')
            }
        )
        
        if response.status_code == 200:
            logs = response.json()['data']
            print(f"Synced {len(logs)} attendance records")
        else:
            print(f"Sync failed: {response.text}")

# Run sync every 5 minutes
sync = BiometricSync('your-api-token', 1)
while True:
    sync.sync_attendance()
    time.sleep(300)  # 5 minutes
```

## Testing

### API Testing with Postman

#### Environment Variables
```json
{
    "base_url": "https://pns-dhampur.edu.in/api/v1",
    "token": "{{auth_token}}",
    "student_id": "1",
    "class_id": "1"
}
```

#### Pre-request Script (Authentication)
```javascript
// Auto-login and set token
if (!pm.environment.get("auth_token")) {
    pm.sendRequest({
        url: pm.environment.get("base_url") + "/auth/login",
        method: 'POST',
        header: {
            'Content-Type': 'application/json'
        },
        body: {
            mode: 'raw',
            raw: JSON.stringify({
                email: "admin@pns-dhampur.edu.in",
                password: "password"
            })
        }
    }, function (err, response) {
        if (!err && response.code === 200) {
            const data = response.json();
            pm.environment.set("auth_token", data.data.token);
        }
    });
}
```

### Unit Testing

#### PHPUnit Test Example
```php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class StudentApiTest extends TestCase
{
    public function test_can_list_students()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($user, ['read']);

        $response = $this->getJson('/api/v1/students');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'admission_number',
                            'class',
                            'status'
                        ]
                    ],
                    'meta'
                ]);
    }

    public function test_can_create_student()
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['write']);

        $studentData = [
            'admission_number' => 'ADM001',
            'name' => 'Test Student',
            'class_id' => 1,
            'section' => 'A',
            'date_of_birth' => '2008-01-01',
            'gender' => 'male'
        ];

        $response = $this->postJson('/api/v1/students', $studentData);

        $response->assertStatus(201)
                ->assertJsonFragment(['name' => 'Test Student']);
    }
}
```

### Load Testing

#### Artillery.js Configuration
```yaml
config:
  target: 'https://pns-dhampur.edu.in/api/v1'
  phases:
    - duration: 60
      arrivalRate: 10
  headers:
    Authorization: 'Bearer your-test-token'
    Content-Type: 'application/json'

scenarios:
  - name: 'Student API Load Test'
    requests:
      - get:
          url: '/students'
          capture:
            - json: '$.data[0].id'
              as: 'student_id'
      - get:
          url: '/students/{{ student_id }}'
      - get:
          url: '/attendance/summary?student_id={{ student_id }}&month=2024-01'
```

---

## Support and Resources

### Developer Support
- **Documentation**: https://docs.pns-dhampur.edu.in/api
- **GitHub Repository**: https://github.com/pns-dhampur/api
- **Issue Tracker**: https://github.com/pns-dhampur/api/issues
- **Developer Forum**: https://developers.pns-dhampur.edu.in

### API Status
- **Status Page**: https://status.pns-dhampur.edu.in
- **Uptime Monitoring**: 99.9% SLA
- **Maintenance Windows**: Sundays 2:00-4:00 AM IST

### Rate Limits and Quotas
- **Free Tier**: 1,000 requests/day
- **Basic Plan**: 10,000 requests/day
- **Premium Plan**: 100,000 requests/day
- **Enterprise**: Custom limits

### Contact Information
- **API Support**: api-support@pns-dhampur.edu.in
- **Technical Issues**: tech-support@pns-dhampur.edu.in
- **Business Inquiries**: business@pns-dhampur.edu.in

---

*This API documentation is regularly updated. Please check the version number and subscribe to our developer newsletter for updates.*