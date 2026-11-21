<?php
// Public: About page (15 sections)
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About'; $layout = 'public';
$counts = [
    ['fa-syringe',  scalar_count('SELECT COUNT(*) FROM vaccines'),                        'Vaccines in the schedule'],
    ['fa-hospital', scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active'"), 'Partner hospitals'],
    ['fa-users',    scalar_count("SELECT COUNT(*) FROM users WHERE role='parent'"),       'Registered parents'],
    ['fa-child',    scalar_count('SELECT COUNT(*) FROM children'),                        'Children protected'],
];
require __DIR__ . '/includes/header.php';
page_banner('About VaxTrack', 'Making sure no child misses a vaccine');

sec_split('Our story', 'From paper registers to one reliable record',
  'Children are often vaccinated through school visits and paper cards. When a child is absent, moves, or the card is lost, a dose can be missed. VaxTrack turns this manual process into a simple online system where every family gets another chance to catch up.',
  ['One digital record that follows the child', 'Fewer missed doses through clear reminders', 'Hours saved for hospitals and administrators'], 'about.jpg');

sec_marquee(['Privacy', 'Accuracy', 'Clarity', 'Speed', 'Trust', 'Care', 'Simplicity']);

sec_cards('The problem we set out to solve', 'Why so many children miss a dose', [
  ['bi-file-earmark-x', 'Lost or damaged cards', 'Without the card nobody knows which doses are already done.'],
  ['bi-calendar-x', 'Missed visits', 'If a child is absent on the day, the dose is skipped and nobody follows up.'],
  ['bi-hourglass-split', 'Slow and manual', 'Staff chase families place by place, which wastes hours.']], 3, 'pain');

sec_compare('Before and after', 'What changes with VaxTrack',
  ['The manual way', ['Paper cards that get lost', 'Vaccination only on school visit days', 'No reminder when a dose is due', 'Staff chase families one by one']],
  ['With VaxTrack', ['A secure online record, printable any time', 'Book a hospital slot when it suits you', 'Upcoming and overdue doses on your dashboard', 'Hospitals and admins see everything in one place']]);

sec_stats($counts);

sec_cards('Mission, vision and values', 'What guides every decision', [
  ['bi-bullseye', 'Our mission', 'Make on-time vaccination simple for every family and every hospital.'],
  ['bi-eye', 'Our vision', 'A country where no child misses a dose because of paperwork or distance.'],
  ['bi-shield-check', 'Our values', 'Privacy, accuracy and clarity. Parents see only their own children.']], 3, 'mini', 'flip');

sec_timeline('How VaxTrack was built', 'Step by step, from idea to working system', [
  ['Phase 1', 'Understanding the problem', 'Studied how vaccination tracking works today and where children get missed.'],
  ['Phase 2', 'Designing the data', 'Planned tables for users, children, vaccines, bookings and notifications.'],
  ['Phase 3', 'Parents and children', 'Registration, child profiles and the automatic vaccination schedule.'],
  ['Phase 4', 'Booking flow', 'Hospital search, time slots, admin approval and hospital updates.'],
  ['Phase 5', 'Reports and security', 'Charts, CSV reports, printable card, login protection and testing.']]);

sec_cards('Who we serve', 'Three roles, one connected system', [
  ['fa-people-roof', 'Parents', 'Add children, see due vaccines and book a hospital slot.'],
  ['fa-hospital', 'Hospitals', 'Receive approved appointments and record every dose.'],
  ['fa-user-shield', 'Administrators', 'Approve requests, manage hospitals and view reports.']], 3, 'service');

sec_dark('Principles we build by', 'Children\'s health records deserve real protection', [
  ['bi-key', 'Private by default', 'Passwords are hashed and never shown.'], ['bi-person-lock', 'Least access', 'Each role sees only what it needs.'],
  ['bi-shield-check', 'Safe forms', 'Every form is protected against forgery.'], ['bi-hourglass-split', 'Abuse limits', 'Repeated wrong logins are blocked.']]);

sec_cards('Built with modern technology', 'A secure and fast foundation', [
  ['bi-filetype-php', 'PHP 8', 'Server-side logic and role-based access'], ['bi-database', 'MySQL', 'Reliable data with transactions'],
  ['bi-bootstrap', 'Bootstrap 5', 'Responsive on phones and desktops'], ['bi-bar-chart-line', 'Chart.js', 'Live charts on the dashboards'],
  ['bi-map', 'Leaflet maps', 'Find hospitals on a map'], ['bi-lock', 'Secure design', 'Prepared statements and CSRF tokens']], 3, 'mini', 'zoom');

sec_split('Features', 'Designed around real clinics',
  'Hospitals decide how many patients they can see in each time slot, and only receive appointments that the administrator has approved.',
  ['Slot capacity control, no overbooking', 'One-tap vaccinated or not vaccinated', 'A searchable vaccination log'], 'features.jpg', true, ['See how it works', 'how-it-works.php']);

sec_cards('Built for everyone', 'Small details that make it easy to use', [
  ['bi-phone', 'Mobile friendly', 'Works on any screen size.'], ['bi-type', 'Clear language', 'Simple words, no jargon.'],
  ['bi-keyboard', 'Keyboard friendly', 'Visible focus and labelled forms.'], ['bi-brightness-high', 'Readable design', 'Good contrast and spacing.']], 4);

sec_timeline('What comes next', 'Ideas planned for future versions', [
  ['Planned', 'Email and SMS reminders', 'Reminders before a dose is due, not just on the dashboard.'],
  ['Planned', 'Urdu language support', 'Use the whole site in English or Urdu.'],
  ['Planned', 'Vaccine stock per hospital', 'Hospitals manage their own stock and availability.'],
  ['Planned', 'QR code on the vaccination card', 'Scan to verify a record instantly.']]);

sec_faq('About VaxTrack', 'Quick answers about the project', [
  ['Is VaxTrack a government service?', 'No. It is an independent project that helps families stay organised. Always follow your doctor and national guidelines.'],
  ['Who can use it?', 'Parents, hospitals and administrators each have their own account type.'],
  ['Is it free?', 'Yes. Parents and hospitals can register and use VaxTrack for free.']]);

sec_cta('Join families who never miss a dose', 'Create your free account and add your first child in under two minutes.', ['Create free account', 'register.php'], ['Contact us', 'contact.php']);
require __DIR__ . '/includes/footer.php'; ?>