<?php
$racine_locale = dirname(__FILE__);
require "$racine_locale/traduction.php";

$messages = array
	('realise_regles' => array (
						 'no_user'=> "Pas d'usager"
						,'unvalidated_user'=>"Validation impossible( !0! )"
						,'chg_user'=>"Chgt pour usager !0! de !1!."
						,'hidden_att'=>"Attribut masque !0!\n" 
						,'invalid_att'=>"Attribut invalide ou absent !0!\n"
						,'grant_access'=>"Ouverture des acc&egrave;s : !0!"
						,'ss_proxy'=>" seule (sans le proxy !0! la relayant)"
						,'chged_to_IP'=>" ... chang&eacute;e pour !0! !1! \n"
						,'unknown IP'=>"IP !0! est inconnue : aucun droit ouvert par cette IP.\n"
						,'no access IP'=>"IP !0! est connue mais n'ouvre aucun droit."
						,'pub host'=>"IP !0! reconnue comme celle d'un poste public de l'UL.\n"
						,'on_campus'=>"IP !0! est reconnue de l'UL.\n"
						,'IP_bck'=>" Elle est frontale a l'IP effective !0!"
						,'pub simp'=>"reconnue comme celle d'un poste public de l'UL.\n"
						,'nonpub simp'=>"qui n'est pas celle d'un poste public de l'UL.\n"
						,'titPostespub'=>"Postes publics configur&eacute;s "
						,'titNoPostespub'=>"Pas de poste public configur&eacute;s en dur."
						)
	,'verif'=>array ('autre_verif'=>"Autre v&eacute;rification."
					,'sans_droit'=>"Vos droits=!0!<br />Vous n'avez pas les droits requis.<br />\n"
					,'titTstPers'=>"Tester l'acc&egrave;s d'une personne identifi&eacute;e/localis&eacute;e "
					,'deIdP'=>" de (&eacute;tab.) <br />\n".
							"<select name=\"forcede\">\n!0!\n</select>"
					,'depuisIP'=>'  depuis IP <input type=text size=15 maxlength=25 name=forceIP value="!0!">'
					,'ignoreAtt'=>" ".
							"<br />\n Calculer ses groupes d'acc&egrave;s en ignorant les attributs LDAP : <br />\n!0!\n"
					,'titTstEdit'=>"Tester l'acc&egrave;s &eacute;diteur/fournisseur :"
					,'URLEdit'=>"URL du fournisseur: <input type=text name=url value=\"!0!\">"
					,'selSourceIP'=>"Pour v&eacute;rifier les IP connues du fournisseur
        				<select name=\"forceIPgroupe\">
        				<option value=\"\">Forcer une IP de sortie :</option>\n!0!\n</select><br />\n"
        			,'selDroitsDIP'=>"     Pour v&eacute;rifier les usagers ayant le droit d'usage de la ressource<br />
        vous pouvez fixer les groupes d'acc&egrave;s re&ccedil;us par ezproxy pour l'usager
        selon le poste utilis&eacute; :
        <table border=0>\n <tr><th>!0!</th></tr>\n <tr><th>!1!</th></tr>\n</table>"
        			,'mode'=>"Mode de fonctionnement :"
        			,'en_test'=>"Version de dev. : <input type=checkbox name=test value=1 !0!/><br />\n"
        			,'VA__test'=>"	Traceur identification (VA_MAP) : <input type=checkbox name=VA_MAP value=1 !0!/><br />\n"
        			,'atts_ldap'=>"Voir tous les attributs LDAP : <input type=checkbox name=ldap value=1 !0!/><br />\n"
        			,'lng'=>"Langue de fonctionnement : <select name=\"lng\">\n!0!\n</select><br />\n"
					)
	,'gen'=>array	('vrai'=>'Vrai'
					,'faux'=>'FAUX!'
					,'Choix'=>'Choix'
					,'verif'=>"V&eacute;rifier ..."
					,'interdit'=>'Usage interdit'
					,'appli_interdite'=>"Vous n'avez pas le droit d'utiliser cette application"
					)
	,'principal'=>array	('diagnostic'=>"Diagnostic "
						,'Req_identifieur'=>"Nouvel identifieur requis"
						,'2ndLogin'=>"mode deuxieme login"
						,'1SeulIdP'=>"Pas d'autre identifieur !"
						,'grpsIP'=>"Groupe d&eacute;duits !0!."
						,'val_attrs'=>"\n	<li>Attributs  LDAP :!0!</li>\n<li>Groupes d&eacute;duits de ces attributs : !1! </li>\n"
						,'OnlyOnCampus'=>"<b> Attention : uniquement sur les site de l'Universit&eacute; !</b> \n"
						,'OffCampusAccessTo'=>"... acc&egrave;s  hors les murs aux ressources des groupes !0!.\n"
						,'OnOffCampusAccess'=>"... acc&egrave;s valide dans et hors les murs de l'Universit&eacute;.\n"
						,'vstats'=>"	<li> <b>Cat&eacute;gories statistiques d&eacute;duites des attributs </b>: !0!</li>\n"
						,'SsAcces'=>"<li><b>Aucun acc&egrave;s aux ressources</b></li>\n"
						,'GrpTrpLg'=>"<li>Nombre de groupe trop important donnant une chaine de !0! caracteres elle sera tronquee a !1! car.</li>\n"
						,'aAccesA'=>"<li><b>!0! </b> a acc&egrave;s aux ressources des groupes : <b><i>!1!</i></b> !2!</li>\n"
						,'URLcible'=>"<hr /><b>URL cible :</b> !0!"
						,'accesOK'=>"<li>Vous avez acc&egrave;s aux ressources de l'Universit&eacute; !0!.</li>\n"
						,'URLrecue'=>"<b>URL recue = </b>!0!<br/>\n"
						,'URLincomplete'=>"<h4>URL incomplete dans le retour</h4>\n"
						,'allerURL'=>"Aller a l'URL"
						)
	);
