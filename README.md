# TYPO3 export fe_users into LDAP

This script is used for the TYPO3.org project. We are moving our single point of user management from TYPO3 CMS to a net internal LDAP Server.

You are able to import the frontend users from your TYPO3 to your LDAP Server but need to have the `objectClass` named `typo3Person` in your account or modify our files.

## Requirements

To run this two scripts you need to have a `mysql server` available and to have `LDAP` with the right schemes running. Our schemes are manually created and not yet public available so you also need some experience with LDAP and / or PHP to adjust and remove those `objectClasses`.

Of course you also need to have `php5`, `php5-mysqli` and `php5-ldap` running.

## Running Scripts

Please copy the `config.php.dist` and rename it to `config.php`. Adjust settings in the new file and run:

* `php syncGroup.php` to import groups
* `php syncUsers.php` to import the users

## Missing

As this script is currently in development there are still some things to do to finish our work:

* We need to add a few fields to our schemes. After that a reimport is needed
* We need to be able to change values for already imported Users without touching the uid or the possibly changed passwords
* Right now we did not yet deceide how we manage our group <-> user connection so there is no link between

## Contact

If you want to help us or have questions about this script feel free to contact me, Bastian Bringenberg <bastian.bringenberg@typo3.org> or feel free to share and adjust yourself.