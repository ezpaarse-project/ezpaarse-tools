<?php
$racine_locale = dirname(__FILE__);
require "$racine_locale/traduction.php";

$messages = array
	('realise_regles' => array (
						 'no_user'=> "No user"
						,'unvalidated_user'=>"User cannot be validate ( !0! )"
						,'chg_user'=>"Change to !0! of organisation !1!."
						,'hidden_att'=>"Hidden attribute !0!\n" 
						,'invalid_att'=>"Unknown or empty attribute !0!\n"
						,'grant_access'=>"Access granted to : !0!"
						,'ss_proxy'=>" itself (i.e. not proxied by !0!)"
						,'chged_to_IP'=>" ... changed with !0! !1! \n"
						,'unknown IP'=>"!0! is unknown on campus : none access granted.\n"
						,'no access IP'=>"IP !0! is not configured to grant user for any access."
						,'pub host'=>"IP !0! grant any people for anonymous resources.\n"
						,'on_campus'=>"On campus IP !0!.\n"
						,'IP_bck'=>" Frontal for actual IP in VLAN !0!"
						,'pub simp'=>" which is public host IP.\n"
						,'nonpub simp'=>" which is NOT public host IP.\n"
						,'titPostespub'=>"Publics hosts list"
						,'titNoPostespub'=>"None configured public host." 
						)
	,'verif'=>array ('autre_verif'=>"Other test."
					,'sans_droit'=>"You have no access to this application.<br />\n"
					,'titTstPers'=>" Access groups of an identified/localised user"
					,'deIdP'=>" IdP organisation <br />\n".
							"<select name=\"forcede\">\n!0!\n</select>"
					,'depuisIP'=>' user host IP <input type=text size=15 maxlength=25 name=forceIP value="!0!">'
					,'ignoreAtt'=>" ".
							"<br />\n Don't use these properties to parse groups or stats. rules  : <br />\n!0!\n"
					,'titTstEdit'=>"Resource provider access verification:"
					,'URLEdit'=>"DB/Resource URL: <input type=text name=url value=\"!0!\">"
					,'selSourceIP'=>"Verify that providers does know that IP 
        				<select name=\"forceIPgroupe\">
        				<option value=\"\">select the IP Source</option>\n!0!\n</select><br />\n"
        			,'selDroitsDIP'=>"Verify ezproxy users group grant to access to the resource:
        				<table border=0>\n <tr><th>!0!</th></tr>\n <tr><th>!1!</th></tr>\n</table>\n"
        			,'mode'=>"View :"
        			,'en_test'=>"Dev. version: <input type=checkbox name=test value=1 !0!/><br />\n"
        			,'VA__test'=>"V.A. track: <input type=checkbox name=VA_MAP value=1 !0!/><br />\n"
        			,'atts_ldap'=>"All user properties (attributes): <input type=checkbox name=ldap value=1 !0!/><br />\n"
        			,'lng'=>"Language: <select name=\"lng\">\n!0!\n</select><br />\n"
					)
	,'gen'=>array	('vrai'=>'True'
					,'faux'=>'FALSE!'
					,'Choix'=>'Choice'
					,'verif'=>"Verify ..."
					,'interdit'=>'Forbidden'
					,'appli_interdite'=>"You cannot use this application !"
					)
	,'principal'=>array	('diagnostic'=>"Diagnostic "
						,'Req_identifieur'=>"New  User/organisation identity is required"
						,'2ndLogin'=>"Second login required"
						,'1SeulIdP'=>"None other identifier !"
						,'grpsIP'=>"= Groups !0!."
						,'val_attrs'=>"\n	<li>With these properties/attributes:!0!</li>\n<li>You are in groups: !1! </li>\n"
						,'OnlyOnCampus'=>"<b> Pay attention you can use resources only on campus !</b> \n"
						,'OffCampusAccessTo'=>"... off campus, your are member of !0! groups.\n"
						,'OnOffCampusAccess'=>"... you are member of the same groups off campus.\n"
						,'vstats'=>"	<li> <b>Statistic properties logged</b>: !0!</li>\n"
						,'SsAcces'=>"<li><b>No access to any resources</b></li>\n"
						,'GrpTrpLg'=>"<li>To many groups to be forward to ezproxy (!0! characters). The list will be troncate to !1! chars.</li>\n"
						,'aAccesA'=>"<li><b>!0! </b> is granted to access to resources of: <b><i>!1!</i></b> !2!</li>\n"
						,'URLcible'=>"<hr /><b>test URL:</b> !0!"
						,'accesOK'=>"<li>You are granted to access to resources reserved for member of !0!.</li>\n"
						,'URLrecue'=>"<b>Providers URL=</b>!0!<br/>\n"
						,'URLincomplete'=>"<h4>Incomplete URL as return </h4>\n"
						,'allerURL'=>"Get/test provider URL"
						)
	);
