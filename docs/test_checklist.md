# Unit Testing Checklist

Run each test, then change the last column to Pass or Fail.

| ID | Module | Test case | Expected result | Result |
|---|---|---|---|---|
| T01 | Register | Register a parent with valid details | Account created, redirected to login | |
| T02 | Register | Register with an existing email | "Email already exists" error | |
| T03 | Register | Password shorter than 8 characters | Validation error | |
| T04 | Register | Register a hospital | Account and hospital profile created | |
| T05 | Login | Login with correct credentials | Role dashboard opens | |
| T06 | Login | Login with a wrong password | "Incorrect email or password" | |
| T07 | Access | Parent opens /admin/dashboard.php | 403 message | |
| T08 | Access | Open a dashboard without login | Redirected to login | |
| T09 | Admin hospitals | Add a hospital with login | Hospital listed, can log in | |
| T10 | Admin hospitals | Deactivate a hospital | Not shown in the parent's booking list | |
| T11 | Admin hospitals | Delete a hospital | Confirm box, then removed | |
| T12 | Admin vaccines | Mark a vaccine Unavailable | Parent cannot book it | |
| T13 | Admin vaccines | Add and edit a vaccine | Saved and listed in age order | |
| T14 | Children | Add a child | Schedule of every vaccine is generated | |
| T15 | Children | Add a child with a future date of birth | Validation error | |
| T16 | Children | Edit date of birth | Pending due dates move | |
| T17 | Children | Delete a child | Confirm box, child and schedule removed | |
| T18 | Booking | Book a dose with a past date | Not allowed | |
| T19 | Booking | Book the same dose twice | Second attempt blocked | |
| T20 | Booking | Cancel a pending booking | Status becomes Cancelled | |
| T21 | Approval | Admin approves a request | Dose becomes Booked, parent and hospital notified | |
| T22 | Approval | Admin rejects without a reason | Error, nothing saved | |
| T23 | Approval | Admin rejects with a reason | Parent sees the reason | |
| T24 | Hospital | Appointment list shows only own approved bookings | Correct list | |
| T25 | Hospital | Mark as Vaccinated | Dose is Done, parent notified, appears in log | |
| T26 | Hospital | Mark as Not vaccinated | Dose returns to pending, parent can book again | |
| T27 | Reports | Date range, hospital and vaccine filters | Matching rows and counts | |
| T28 | Reports | Download CSV | File opens in Excel | |
| T29 | Certificate | Print vaccination report | Only the card is printed | |
| T30 | Profile | Change password with a wrong current password | Error shown | |
| T31 | Profile | Change password correctly, then log in again | New password works | |
| T32 | Security | Submit a form without the CSRF token | Rejected | |
| T33 | Security | Parent opens another parent's child by editing the URL | "Child not found" | |
| T34 | Layout | Open the site at 375 px width | Menu collapses, no sideways scrolling | |