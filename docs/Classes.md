=Classes=

Classes are defined in /includes in files with lowercase names and - separating
the CamelCase class names (MauticAuth becomes mautic-auth.php). These are
divided up by data contexts.

==MauticSync==

The master class that sets things up for all the others.

==MauticAuth==

Manages the configuration Mautic authentication (via Basic Auth) and testing
it against the remote Mautic instance. Provides the admin interface and the
testing capabilities.

==MauticAdmin==

This draws together information related to WordPress Users and the Networks
with which they're and their counterparts in Mautic's Contacts and Segments.



==WordPressUsers==

==MauticContacts==

==WordPressNetworks==

==MauticSegments==

==Misc==

Other functionality, like that which is supposed to work with WordPress hooks
is held in a file called Hooks.php
