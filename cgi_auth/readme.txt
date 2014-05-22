
Authentication CGI based of Chris Zagar's ticket class defined for ezproxy  (reverse proxy).
Usefull when all users are in one of the two categories :
- Having a CAS SSO registered account.
- Having a LDAP registered account. 
Complete informations on users can be in LDAP or in ODBC Database

Interest : 
- Advanced configuration based on combination (merging) of user informations and user host localisation
- Easy to log many profile informations on user 
- Has user and admin access test tools
- Can list the anonymous access hosts.

Need libs PHP modules.

Tools :
* The CGI = index.php (can be default root file for apache).
* Admin access test tool = verif.php.
* User access test tool = diagnostic.php
* Public IP lister = test_IP.php.


*** Environment adaptation and customization :

acces.conf.php : 
	Needed for CAS and/or LDAP and /or data base access configurations.
	CAS or LDAP for auth ; LDAP or databases for other informations
	Path to the libs library modules.
regles_groupage.php : (group rules)
	definition of access users groups and of profile logged informations. The rules can merge  
	LDAP/DataBase/IP host number.
page_identifieur.php :
	contains a PHP function to customise the user organization (university, ...) choice. The organizations are
	configured in acces.conf.php.
page_sans_acces.php :
	customized page used to tell to user he has no access to resources in his actually situation.
page_formulaire_verif.php :
	customized formular for the admin test tool verif.php .
	
ok.png , bandeau_titre.jpg
	submit button image and home page image.

	
Other components  :
realise_regles.php , acces.lib.php , valid_acces.php
principal.php , principal_test_IP.php

locale : fichiers pour la traduction (deux langues actuellement français/anglais)
