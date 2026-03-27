<?php

return [
    'name' => 'Appointments',
    'description' => 'Appointment scheduling, public booking forms, and employee leave management',

    'appointments' => 'Appointments',
    'appointment' => 'Appointment',
    'calendar' => 'Calendar',
    'forms' => 'Forms',
    'form' => 'Form',
    'leave' => 'Leave',
    'leave_request' => 'Leave Request',
    'reports' => 'Reports',
    'settings' => 'Settings',
    'appointment_history' => 'Appointment History',
    'leave_summary' => 'Leave Summary',
    'book_appointment' => 'Book Appointment',
    'public_booking' => 'Public Booking',
    'assigned_user' => 'Assigned User',
    'customer' => 'Customer',
    'reminder_sent' => 'Reminder Sent',
    'location' => 'Location',
    'notes' => 'Notes',
    'view_leave_request' => 'View Leave Request',
    'copy_link' => 'Copy Link',
    'custom_fields_help' => 'One field per line. Format: Label|type|required. Supported types: text, textarea, email, phone.',
    'date_range' => 'Date Range',
    'days' => 'Days',
    'reason' => 'Reason',
    'approver' => 'Approver',
    'allowance' => 'Allowance',
    'used' => 'Used',
    'remaining' => 'Remaining',
    'request_leave' => 'Request Leave',
    'send_reminders' => 'Send Reminders',
    'booking_link' => 'Booking Link',
    'booked' => 'Booked',
    'monthly' => 'Monthly',
    'weekly' => 'Weekly',
    'daily' => 'Daily',

    'statuses' => [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
    ],

    'leave_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'refused' => 'Refused',
    ],

    'leave_types' => [
        'vacation' => 'Vacation',
        'sick' => 'Sick',
        'personal' => 'Personal',
        'other' => 'Other',
    ],

    'messages' => [
        'reminders_sent' => ':count reminder email(s) sent.',
        'no_assignable_user' => 'No enabled user is available for public bookings.',
        'leave_locked' => 'Approved leave requests can no longer be edited.',
        'invalid_leave_status' => 'This leave request can no longer be reviewed.',
        'leave_approved' => 'Leave request approved and balance updated.',
        'leave_refused' => 'Leave request refused.',
        'appointment_booked' => 'Appointment booked successfully.',
    ],

    'notifications' => [
        'greeting' => 'Hello :name,',
        'reminder_subject' => 'Appointment reminder for :date',
        'reminder_body' => 'This is a reminder for your appointment on :date at :start_time in :location.',
        'leave_submitted_subject' => 'Leave request from :employee',
        'leave_submitted_body' => ':employee submitted a leave request for :dates.',
        'leave_status_subject' => 'Your leave request is :status',
        'leave_status_body' => 'Your leave request for :dates is now :status.',
    ],

    'refusal_reason' => 'Refusal Reason',
];
