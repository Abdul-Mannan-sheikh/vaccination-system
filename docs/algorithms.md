# Algorithms

## 1. Generate a child's vaccination schedule
Function: generate_schedule($childId, $dob) in includes/functions.php

1. Read every vaccine (id, age_days) from the vaccines table.
2. For each vaccine, calculate due_date = date of birth + age_days.
3. Insert one row into child_vaccinations with status "pending".
4. A UNIQUE key on (child_id, vaccine_id) together with INSERT IGNORE prevents duplicates.
5. If the date of birth is edited later, update due_date for all doses that are still "pending".

## 2. Dose status shown to the parent
Function: dose_state($status, $dueDate)

1. If status is "vaccinated" -> show "Done".
2. Else if status is "booked" -> show "Booked".
3. Else compute days = due_date - today.
4. If days < 0 -> "Overdue".
5. Else if days <= 14 -> "Due soon".
6. Else -> "Upcoming".

## 3. Parent booking request
File: parent/book.php

1. Parent chooses a child, then only that child's pending, available doses are listed.
2. Parent chooses a hospital, a date and a time slot.
3. Server checks: the dose belongs to this parent, its status is "pending", the vaccine is available,
   no active (pending or approved) booking exists for the same dose, the hospital is active,
   the date is not before today or before the due date, and the time is a valid slot.
4. If every check passes, insert a booking with status "pending" and notify all admins.
5. Otherwise show the errors and insert nothing.

## 4. Admin approval
File: admin/bookings.php

1. Start a database transaction and lock the pending booking row (SELECT ... FOR UPDATE).
2. If it is no longer pending, stop (another admin already decided).
3. Approve: booking status = "approved", the dose becomes "booked" at that hospital,
   notify the parent and the hospital.
4. Reject: a reason is required; booking status = "rejected"; notify the parent with the reason.
5. Commit the transaction.

## 5. Hospital updates the result
File: hospital/appointments.php

1. Lock the booking, and check it belongs to this hospital and is "approved".
2. Vaccinated: booking = "completed"; dose = "vaccinated" with today's date and this hospital; notify the parent.
3. Not vaccinated: booking = "cancelled"; dose goes back to "pending"; notify the parent to book again.
4. Commit the transaction.

## 6. Login
File: login.php

1. Verify the CSRF token.
2. Find the user by email.
3. Check the password with password_verify() against the stored bcrypt hash.
4. Reject blocked accounts.
5. Regenerate the session id, store id, name and role, and redirect to the role's dashboard.

## 7. Access control
Function: require_role($role)

1. If nobody is logged in -> redirect to login.
2. If the logged in role is different from the page's role -> HTTP 403.
3. Parent and hospital pages also check ownership (a parent can only open their own child).