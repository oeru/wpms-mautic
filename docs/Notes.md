=Plans=

With this module I'm trying to create a mechanism to allow synchronisation of
WordPress Users with Mautic Contacts and membership in Courses (represented by
WP network sites) corresponding to Mautic Segments.

It needs to be possible to do quick review of disparities between WP
Users/Network associations and Mautic Contacts/Segment associations,
including automatic updates based on

DONE * set access to a Mautic site API via the Basic Authentication -
DONE ** a WP user with Administration rights enters a Mautic site URL, username,
and password.
DONE ** on saving it, WP tests the connection for a valid response from the Mautic
site to verify access

DONE * create a separate page/tab for administration of Users/Contacts

DONE * provide basic statistics
DONE ** number of WP Users, number of Mautic Contacts, number matched
DONE ** number of WP Network Sites, number of Mautic Segments, number matched

* get a list of current WP Users and Mautic Contacts, matched on email.

* display those present in both system, those only in WP, and only in Mautic

* make these User/Contact operations possible:
** add WP Users to Mautic
** alter Mautic Contact email, like to match a WP User to a Mautic Contact
(reset Mautic email to WP email)
** remove Mautic Contacts

* make WP Courses (network sites)/Mautic Segment operations possible:
** create a Mautic Sync page on each Course Admin page 
** check Courses with corresponding Segments (using corresponding Course code)
** remove Mautic Segments
** create new Mautic Segment based on Course (hook network site?)

* make WP Course membership assessment operations possible:
** show courses with users in each
** show users with courses each is in
** indicate whether user is member of corresponding Segment
** allow adding a user in a Course to the corresponding Segment (create
Segment if doesn't exist, create Contact if doesn't exist)

* automatic updates to WP based on Mautic changes
** if a User requests to be taken off a Mautic Segment, mark "dnc"
(do not contact) and respect this
** if a User requests to be removed as a Mautic Contact, mark "removed"

* automatic updates to Mautic based on WP changes
** update Mautic Contact details if WP User details are changed
(hook user profile?)
** disable a Mautic Contact if WP user is disabled
** remove a Mautic Contact if WP user is removed
