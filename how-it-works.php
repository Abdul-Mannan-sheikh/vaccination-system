<?php
// Public: How it works (15 sections)
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'How it works'; $layout = 'public';
$counts = [
    ['fa-syringe',  scalar_count('SELECT COUNT(*) FROM vaccines'),                                  'Vaccines scheduled'],
    ['fa-calendar-check', scalar_count('SELECT COUNT(*) FROM bookings'),                           'Bookings made'],
    ['fa-circle-check', scalar_count("SELECT COUNT(*) FROM child_vaccinations WHERE status='vaccinated'"), 'Doses recorded'],
    ['fa-hospital', scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active'"),           'Active hospitals'],
];
require __DIR__ . '/includes/header.php';
page_banner('How it works', 'From sign-up to a completed dose in six simple steps');

sec_split('Overview', 'Simple on the surface, careful underneath',
  'A parent adds a child, the schedule appears, a hospital slot is requested, the administrator approves it and the hospital records the dose. Every step is tracked and visible to the right person.',
  ['No paperwork or phone calls', 'Every status change sends an alert', 'The full history is kept for the child'], 'about.jpg');

sec_steps('Six simple steps', 'Everything happens online, in the right order', [
  ['Register', 'Parents and hospitals create a free account with an email and password.'],
  ['Add your child', 'Enter the name and date of birth. The schedule is generated instantly.'],
  ['See what is due', 'The dashboard highlights upcoming and overdue vaccines.'],
  ['Book a hospital', 'Pick a hospital on the map or list, then a date and a free time slot.'],
  ['Admin approval', 'The administrator reviews the request and you are told the result.'],
  ['Dose recorded', 'The hospital marks the child vaccinated and your report updates.']]);

sec_flow('At a glance', 'The whole journey in four moves', [['bi-person-plus', 'Register'], ['bi-emoji-smile', 'Add child'], ['bi-calendar-check', 'Book slot'], ['bi-patch-check', 'Vaccinated']]);

sec_tabs('role', 'What each role does', 'Three accounts, one connected system', [
  ['Parents', ['Add and manage children', 'See upcoming and overdue vaccines', 'Book a hospital slot with a free time', 'Cancel a pending request', 'Print the vaccination card']],
  ['Hospitals', ['Register with address and map location', 'See only approved appointments', 'Mark each child vaccinated or not', 'Set patients per time slot', 'Keep a searchable vaccination log']],
  ['Administrators', ['Approve or reject with a reason', 'Add and manage hospitals', 'Mark vaccines available or unavailable', 'See every child and due dates', 'Date-wise reports with CSV export']]]);

sec_split('Automatic schedule', 'Enter a date of birth, get every due date',
  'Each vaccine has a recommended age in days. VaxTrack adds that age to the date of birth and creates one dose per vaccine for the child.',
  ['Due date = date of birth + recommended age', 'Edit the date of birth and pending doses move', 'Doses already given never change'], 'features.jpg', true);

sec_cards('Life of a booking', 'Every request has a clear status', [
  ['bi-hourglass-split', 'Pending', 'Sent by the parent and waiting for the administrator.'], ['bi-check-circle', 'Approved', 'Accepted. The hospital now sees the appointment.'],
  ['bi-x-circle', 'Rejected', 'Declined with a reason the parent can read.'], ['bi-patch-check', 'Completed', 'The hospital recorded the vaccination.'],
  ['bi-slash-circle', 'Cancelled', 'Cancelled by the parent, or the child did not attend.'], ['bi-arrow-repeat', 'Rebook', 'A cancelled dose goes back to pending and can be booked again.']], 3, 'mini', 'flip');

sec_split('Slot capacity', 'No overbooking, no long queues',
  'Each hospital chooses how many patients it can see in a half-hour slot. When a slot is full it is shown as full, so parents pick another time.',
  ['Free places shown next to each time', 'Full slots cannot be chosen', 'Two parents cannot take the last place at once'], 'about.jpg');

sec_stats($counts);

sec_cards('Alerts that keep everyone informed', 'The bell icon shows what is new', [
  ['bi-send', 'Request sent', 'Admins are told when a parent books.'], ['bi-patch-check', 'Approved', 'Parent and hospital are both notified.'],
  ['bi-chat-left-text', 'Rejected', 'The parent sees the reason.'], ['bi-journal-check', 'Vaccinated', 'The parent is told the dose is done.']], 4, 'mini', 'zoom');

sec_split('Reports and records', 'A clear record for every child',
  'Parents see a report of every dose with its date and hospital, and can print or save it as a PDF. Administrators filter reports by date, hospital and vaccine and export them to Excel.',
  ['Printable vaccination card', 'Date-wise admin reports', 'CSV export for spreadsheets'], 'hero-carousel/hero-carousel-1.jpg', true);

sec_dashes();

sec_dark('Safe from the first click', 'Privacy and security are built in', [
  ['bi-key', 'Hashed passwords', 'Never stored as plain text.'], ['bi-person-lock', 'Role-based access', 'Each account sees only its own pages.'],
  ['bi-shield-check', 'Request protection', 'Forms carry a secret token.'], ['bi-hourglass-split', 'Login protection', 'Wrong passwords are limited.']]);

sec_cards('What you need to start', 'Three things, and you are ready', [
  ['bi-envelope', 'An email address', 'Used to log in and reset your password.'], ['bi-calendar-heart', 'Your child\'s date of birth', 'Needed to build the schedule.'], ['bi-telephone', 'A phone number', 'So the hospital can reach you.']], 3);

sec_faq('Common questions', 'Quick answers about the process', [
  ['How long does approval take?', 'The administrator reviews requests as they arrive. You get an alert as soon as there is a decision.'],
  ['What if my time slot is full?', 'Full slots are marked, so choose another time or day.'],
  ['Can I cancel a booking?', 'Yes, while it is still pending. Open My bookings and choose Cancel.'],
  ['What if my child cannot attend?', 'The hospital marks it not vaccinated. The dose returns to pending and you can book again.']]);

sec_cta('Ready to try it?', 'It is free for parents and takes under two minutes to set up.', ['Create free account', 'register.php'], ['See the vaccines', 'vaccines.php']);
require __DIR__ . '/includes/footer.php'; ?>