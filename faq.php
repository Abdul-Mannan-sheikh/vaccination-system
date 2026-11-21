<?php
// Public: FAQ (15 sections)
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'FAQ'; $layout = 'public';
$counts = [
    ['fa-users',    scalar_count("SELECT COUNT(*) FROM users WHERE role='parent'"),       'Parents'],
    ['fa-hospital', scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active'"), 'Hospitals'],
    ['fa-child',    scalar_count('SELECT COUNT(*) FROM children'),                        'Children'],
    ['fa-syringe',  scalar_count("SELECT COUNT(*) FROM child_vaccinations WHERE status='vaccinated'"), 'Doses recorded'],
];
require __DIR__ . '/includes/header.php';
page_banner('Frequently Asked Questions', 'Quick answers for parents, hospitals and everyone else');

sec_cards('Choose a topic', 'Jump straight to what you need', [
  ['bi-rocket-takeoff', 'Getting started', 'Accounts and first steps.', '#start'], ['bi-calendar-check', 'Booking', 'Slots, approvals and cancelling.', '#booking'],
  ['bi-hospital', 'Hospitals', 'For hospital staff.', '#hospitals'], ['bi-capsule', 'Vaccines', 'Schedule and availability.', '#vaccines'],
  ['bi-person-gear', 'Account', 'Passwords and profile.', '#account'], ['bi-file-earmark-medical', 'Reports', 'Records and printing.', '#reports']], 3, 'mini', 'zoom');

sec_faq('Getting started', 'Your first few minutes', [
  ['How do I create an account?', 'Choose Register, pick Parent or Hospital, fill in your details and set a password of at least 8 characters.'],
  ['Is VaxTrack free?', 'Yes. Parents and hospitals can use VaxTrack for free.'],
  ['How is the vaccination schedule created?', 'When you add a child, each due date is calculated from the date of birth using the schedule stored in the system.'],
  ['Can I manage more than one child?', 'Yes. Add as many children as you need. Each one has their own schedule, progress and report.']], 'start');

sec_faq('Booking', 'Slots, approvals and changes', [
  ['Who approves my booking?', 'The administrator reviews each request. You get an alert as soon as it is approved or rejected, with a reason if rejected.'],
  ['What if my chosen time is full?', 'Full slots are marked as full. Choose another time or another day.'],
  ['Can I cancel a booking?', 'Yes, while it is still pending. Open My bookings and choose Cancel.'],
  ['Why can I not book a vaccine?', 'It may already be booked or completed, or marked unavailable by the administrator.']], 'booking');

sec_callout('bi-lightbulb', 'Tip: book as soon as a dose shows Due soon, so you get the time you prefer.');

sec_stats($counts);

sec_faq('For hospitals', 'Questions from hospital staff', [
  ['How does my hospital join?', 'Register as a hospital with your name, address and phone. It is free.'],
  ['How do I see my appointments?', 'The Appointments page lists only bookings the administrator approved for your hospital.'],
  ['What if a child does not come?', 'Choose Not vaccinated. The dose goes back to pending and the parent is asked to book again.'],
  ['Can I limit how many patients I see?', 'Yes. Set patients per time slot in your hospital profile.']], 'hospitals');

sec_faq('Vaccines', 'About the schedule', [
  ['Who decides the schedule?', 'The administrator manages the schedule, based on a standard childhood schedule.'],
  ['What if a vaccine is unavailable?', 'You are told before booking, so you never travel for nothing.'],
  ['Are side effects normal?', 'Mild soreness or low fever can happen and usually passes. Ask your doctor if you are worried.']], 'vaccines');

sec_faq('Account and password', 'Staying in control of your login', [
  ['I forgot my password. What now?', 'Use the Forgot password link on the login page to get a reset link that works for 30 minutes.'],
  ['How do I change my password?', 'Open My profile, then Change password. You will need your current password.'],
  ['Why was I blocked from logging in?', 'After five wrong passwords the login pauses for ten minutes. Wait, or reset your password.'],
  ['Can I change my email?', 'Your email is your login, so it cannot be changed. Register a new account if needed.']], 'account');

sec_dark('Your data is protected', 'How VaxTrack keeps records safe', [
  ['bi-key', 'Hashed passwords', 'Never stored as plain text.'], ['bi-person-lock', 'Private records', 'Parents see only their own children.'],
  ['bi-shield-check', 'Safe forms', 'Every form carries a secret token.'], ['bi-hourglass-split', 'Login limits', 'Repeated wrong passwords are blocked.']]);

sec_faq('Reports and records', 'Keeping a copy', [
  ['Can I download my child\'s record?', 'Yes. The vaccination report has a printable card that you can also save as a PDF.'],
  ['Why is a dose missing from my report?', 'A dose appears only after the hospital marks it vaccinated.'],
  ['Is the card an official certificate?', 'No. It is a record kept by VaxTrack to help you stay organised.']], 'reports');

sec_cards('Something not working?', 'Quick fixes for common problems', [
  ['bi-arrow-clockwise', 'Refresh the page', 'Press Ctrl + F5 to load the newest version.'], ['bi-box-arrow-in-right', 'Log in again', 'Your session may have expired.'],
  ['bi-wifi', 'Check your connection', 'Maps and charts need the internet.'], ['bi-envelope', 'Write to us', 'Tell us what you saw and we will help.']], 4);

sec_faq('Privacy', 'What we keep and why', [
  ['What information is stored?', 'Your account details, your children\'s names and dates of birth, and their vaccination history.'],
  ['Who can see my child\'s record?', 'You, the hospital that vaccinated your child, and the administrator.'],
  ['Do you share my details?', 'No. We never share your details with anyone outside the service.']], 'privacy');

sec_steps('Still stuck?', 'Three ways to get an answer', [
  ['Check this page', 'Use the topic cards at the top to jump to your question.'], ['Read how it works', 'The guide shows every step in order.'], ['Send us a message', 'Write to us and we reply by email.']]);

sec_cards('Get in touch', 'We are happy to help', [
  ['bi-envelope', 'Email', 'support@vaxtrack.local', url('contact.php')], ['bi-chat-dots', 'Contact form', 'Send a message from the website.', url('contact.php')],
  ['bi-book', 'Guide', 'See how everything works.', url('how-it-works.php')]], 3);

sec_cta('Still have a question?', 'Send us a message and we will get back to you by email.', ['Contact us', 'contact.php'], ['How it works', 'how-it-works.php']);
require __DIR__ . '/includes/footer.php'; ?>