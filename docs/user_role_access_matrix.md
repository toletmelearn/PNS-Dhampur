# User Role Access Matrix

This matrix provides a comprehensive overview of which features each role can access in the PNS-Dhampur School Management System.

## Access Matrix Legend
- ✅ Full Access
- 🔷 Partial Access (with limitations)
- ❌ No Access

## System Features Access Matrix

| Feature | Super Admin | Admin | Principal | Teacher | Student | Parent |
|---------|-------------|-------|-----------|---------|---------|--------|
| **System Configuration** | ✅ | 🔷 | 🔷 | ❌ | ❌ | ❌ |
| **User Management** | ✅ | 🔷 | 🔷 | ❌ | ❌ | ❌ |
| **School Management** | ✅ | ✅ | 🔷 | ❌ | ❌ | ❌ |
| **Financial Management** | ✅ | ✅ | 🔷 | ❌ | ❌ | ❌ |
| **Academic Calendar** | ✅ | ✅ | ✅ | 🔷 | 🔷 | 🔷 |
| **Class Management** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Subject Management** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Attendance Management** | ✅ | ✅ | ✅ | ✅ | 🔷 | 🔷 |
| **Grade Management** | ✅ | ✅ | ✅ | ✅ | 🔷 | 🔷 |
| **Syllabus Management** | ✅ | ✅ | ✅ | ✅ | 🔷 | 🔷 |
| **Exam Management** | ✅ | ✅ | ✅ | ✅ | 🔷 | 🔷 |
| **Fee Management** | ✅ | ✅ | ✅ | 🔷 | ✅ | ✅ |
| **Reports & Analytics** | ✅ | ✅ | 🔷 | 🔷 | 🔷 | 🔷 |
| **Communication** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Personal Profile** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

## Detailed Access Descriptions

### Super Admin
- **System Configuration**: Complete access to all system settings and configurations
- **User Management**: Can create, edit, delete any user across all schools
- **Reports & Analytics**: Access to system-wide reports and analytics across all schools

### Admin
- **System Configuration**: Limited to school-specific configurations
- **User Management**: Can manage users within assigned schools only
- **Financial Management**: Complete access to financial settings and reports for assigned schools

### Principal
- **School Management**: Limited to managing a single school
- **User Management**: Can manage teachers and students within their school
- **Reports & Analytics**: Access to reports for their school only

### Teachers
- **Class Management**: Can manage assigned classes only
- **Attendance & Grade Entry**: Can enter and update for assigned classes
- **Syllabus & Exam Management**: Can create and manage for assigned subjects

### Students
- **Personal Data**: Can view and update their own profile
- **Syllabus & Results**: View-only access to their course materials and results
- **Fee Payment**: Can view fees and make payments

### Parents
- **Child Progress**: Can monitor academic performance of their children
- **Fee Payment**: Can view and pay fees for their children
- **Attendance & Grades**: View-only access to their children's records

## Implementation Verification Checklist

- [ ] Middleware correctly restricts access based on user role
- [ ] UI elements are properly hidden/shown based on permissions
- [ ] API endpoints enforce proper authorization
- [ ] Role hierarchy is correctly implemented
- [ ] Permission inheritance works as expected