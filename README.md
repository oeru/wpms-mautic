# wpms-mautic
Synchronise WordPress (in Multi-Site configuration) users with Course-specific Mautic Segments depending on Courses (subsites) with which they are associated.

Employs the Mautic API, including authentication via OAuth2. Assumes both endpoints (the WP Multi-Site implementation and the Mautic API) are HTTPS.

We want to add WordPress users who sign up for a specific course, to be added to that course's email list. We also want to update the user's details (like email address or name) they change their profile, honour any requests to "opt out" of the emails, or remove them from the list when the course completes.

WordPress (open source multi-site blog engine): https://codex.wordpress.org/Create_A_Network
Matic (open source email marketing engine): https://mautic.net
