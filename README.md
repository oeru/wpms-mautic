# wpms-mautic
Synchronise WordPress (in Multi-Site configuration) users with Course-specific Mautic Segments depending on Courses (network or blog subsites) with which they are associated.

Employs the Mautic API with Basic Authentication. Assumes both endpoints (the WP Multi-Site implementation and the Mautic API) are HTTPS (or on a closed network).

We want to add WordPress users who sign up for a specific course, to be added to that course's email list. We also want to update the user's details (like email address or name) they change their profile, honour any requests to "opt out" of the emails, or remove them from the list when the course completes.

More info available in docs.

This plug-in is the work of the Open Education Resource Foundation on behalf of the OERuniversitas (http://oeru.org). We are using WordPress to deliver open courses (using exclusively openly licensed (CC-By or CC-By-SA) course materials) assembled by our partners on one of our other initiatives: http://wikieducator.org You (and your institution) are also welcome to use these materials, and are invited to consider becoming OERu partners!

WordPress (open source multi-site blog engine): https://codex.wordpress.org/Create_A_Network
Matic (open source email marketing engine): https://mautic.net
