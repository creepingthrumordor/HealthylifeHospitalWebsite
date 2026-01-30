# Healthylife Website

A comprehensive hospital management website built with HTML and CSS, featuring role-based access for patients, doctors, receptionists, and administrators.

## Features

### Public Pages
- **Home Page** (`index.html`) - Welcome page with hospital information and quick access links
- **Login Page** (`login.html`) - User authentication with role selection
- **Patient Registration** (`patient-registration.html`) - New patient registration form

### Patient Portal
- **Patient Dashboard** (`patient-dashboard.html`) - Overview of appointments, bills, and reports
- **Appointment Booking** (`patient-appointment.html`) - Book and manage appointments
- **Medical Reports** (`patient-medical-report.html`) - View and download medical reports
- **Billing & Payment** (`patient-billing.html`) - View bills and make payments
- **Feedback & Inquiry** (`patient-feedback.html`) - Submit feedback and inquiries

### Doctor Portal
- **Doctor Dashboard** (`doctor-dashboard.html`) - Today's schedule and patient overview
- **Upload Medical Reports** (`doctor-upload-report.html`) - Upload test results and medical reports

### Receptionist Portal
- **Receptionist Dashboard** (`receptionist-dashboard.html`) - Manage all appointments overview
- **Appointment Management** (`receptionist-appointments.html`) - View, edit, and manage all bookings
- **Inquiry Management** (`receptionist-inquiries.html`) - View and respond to patient feedback/questions

### Admin Portal
- **Admin Dashboard** (`admin-dashboard.html`) - Hospital operations overview and statistics
- **Doctor Management** (`admin-doctor-management.html`) - Add/edit/delete doctor profiles, specializations, schedules
- **Staff Directory** (`admin-staff-directory.html`) - View/update/remove all hospital staff records
- **Billing Management** (`admin-billing-management.html`) - Update payment statuses, generate reports
- **Patient Records** (`admin-patient-records.html`) - View all registered patients

## Design Features

- Modern, clean UI with a professional medical theme
- Responsive design that works on desktop and mobile devices
- Consistent navigation across all pages
- Color-coded status indicators for easy visual reference
- Intuitive forms and data tables
- Dashboard statistics cards for quick overview

## Getting Started

1. Open `index.html` in a web browser to start
2. Navigate through different portals using the login page
3. All pages are interconnected with proper navigation

## File Structure

```
/
├── index.html                          # Home page
├── login.html                          # Login page
├── patient-registration.html           # Patient registration
├── patient-dashboard.html              # Patient dashboard
├── patient-appointment.html            # Book appointments
├── patient-medical-report.html         # View medical reports
├── patient-billing.html                # Billing and payments
├── patient-feedback.html               # Feedback and inquiries
├── doctor-dashboard.html               # Doctor dashboard
├── doctor-upload-report.html           # Upload medical reports
├── receptionist-dashboard.html         # Receptionist dashboard
├── receptionist-appointments.html      # Manage appointments
├── receptionist-inquiries.html         # Manage inquiries
├── admin-dashboard.html                # Admin dashboard
├── admin-doctor-management.html        # Manage doctors
├── admin-staff-directory.html         # Manage staff
├── admin-billing-management.html       # Manage billing
├── admin-patient-records.html          # Manage patient records
├── styles.css                          # Main stylesheet
└── README.md                           # This file
```

## Color Scheme

- Primary Pale Green: `#a7f3d0` - Main brand color
- Success Green: `#10b981` - Success states
- Danger Red: `#ef4444` - Error/warning states
- Warning Orange: `#f59e0b` - Pending states

## Notes

- This is a front-end only implementation (HTML/CSS)
- Forms are not connected to a backend - they are for UI demonstration
- All data shown is sample/mock data
- For production use, backend integration would be required
