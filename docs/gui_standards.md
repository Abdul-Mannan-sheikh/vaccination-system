# GUI Standards

## Layouts
- Public pages (home, login, register): MediCio template (Bootstrap 5) with a top navigation bar.
- Dashboards (admin, parent, hospital): left sidebar, top bar, content area, built with Bootstrap 5.3.

## Colours (dashboard, medical theme)
| Use | Colour |
|---|---|
| Primary | #0a84a8 |
| Secondary | #12b5a6 |
| Text | #0b2540 |
| Background | #f3f8fb |
| Warning / due soon | #f59e0b |
| Success / done | #10b981 |
| Danger / overdue | #ef4444 |

## Typography
- Dashboards: Bricolage Grotesque for headings, Nunito Sans for body text.
- Public pages: Roboto, Poppins and Raleway (from the template).

## Components
- Status tags: Done (green), Booked (blue), Due soon (amber), Overdue (red), Upcoming (grey).
- Lists have search, filters, pagination and an empty state message.
- Add and edit forms open in modal dialogs; destructive actions ask for confirmation.
- Success and error messages appear as dismissible alerts at the top of the page.
- Forms have labels, visible focus outlines and server-side validation messages.
- Responsive: the sidebar collapses on screens under 992 px.