<?php

/**
 * @version du 23/09/2013 
  * FONCTIONNEMENT : 
 * Ce programme :
 *  - établit la localisation de l'usager par son IP de requête et lui attribue un ou
 *  	plusieurs groupe la reflétant (regles_groupage.php)
 *  
 * 
 */


function quelle_diff($canon,$der){
		$affi=false;$retour=array();
		for ($i=0;$i<16;$i+=4){
			$v = substr($canon,$i+1,3);
			$vd = substr($der,$i+1,3);
			if ($v!=$vd) $affi=true;
			$retour[]=($affi)?$v:"";
		}
		return ($retour);
}
/*
 * Choice your language file in locale 
 */
if (!function_exists('trad_message')){
	include_once ("./locale/en.php");
}

	include_once ("./regles_groupage.php");	
	include_once ("./realise_regles.php");


		
$trace_tab=array(); $trace="";		
function en_trace ($message){
	global $trace;
	$trace .= $message;	
}		
function affi_trace() {
	global $trace;
	echo $trace;	
}




en_trace("<hr /> <h2>".trad_message('principal', 'diagnostic').":</h2>\n<hr />\n") ;
$groupes='';

$recommence=false;

	include_once ("./acces.conf.php");
	include_once ("./acces.lib.php");


$__VAcces_inipag=2;
include_once ("./valid_acces.php");
$tous_les_att_LDAP = $_SESSION['_VAcces_utilisateur'];
$tab_profiles = profiles_LDAP ($tous_les_att_LDAP);
$profiles = $tab_profiles['profiles'];
if (!	(in_array('G2D--', $profiles) 
		|| in_array('G1N04', $profiles) 
		|| in_array('G1N02', $profiles)
		)
	){
  		exit (trad_message('gen','appli_interdite'));
}

	




/*
 * Groupes lié à l'IP du poste utilisé par l'usager 
 * 
 */
$groupes_IP=""; $ajout_trace = "";

include_once './canonise_IP.php';
$son_IP = $_SERVER['REMOTE_ADDR']; $cson_IP =canonise_IP($son_IP); 


$groupes_corres =test_groupes_IP($son_IP); 
if ($groupes_corres===false) {
		en_trace(trad_message('realise_regles', 'unknown IP',$son_IP ));
		$groupes_corres_bck=false;
} else {
	$public = a_groupe_public($groupes_corres); 
	$mess = ($public) ?  trad_message('realise_regles', 'pub host',$son_IP ):
			trad_message('realise_regles', 'no access IP',$son_IP );
	$son_IP_bck = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ?$_SERVER['HTTP_X_FORWARDED_FOR']:'';
	$groupes_corres_bck = array(); $public_bck=false;
	if ($son_IP_bck){
	  	$groupes_corres_bck = test_groupes_IP($son_IP_bck);
	  	$public_bck = a_groupe_public($groupes_corres_bck);
	  	$cas_mess .= ($public_bck)?'pub simp':'nonpub simp';
	  	$mess .= trad_message('realise_regles', 'IP_bck',$son_IP_bck).
	  				trad_message('realise_regles', $cas_mess);	  	
	} 
	en_trace ($mess);
	$groupes = implode('+',$groupes_corres);
	$groupes_bck = implode('+',$groupes_corres_bck);	
}


/*
 * Obtient la liste des IP publique de la structure 
 */
$poste_publics_en_conf = liste_IP_pour_public();
if ($poste_publics_en_conf){
	$tab_trie = array();
	$grp_etab = array	('ANANCY2'=>" ex Nancy2"
						,'AMEDECINE'=>" Campus medecine"
						,'AUHP'=>" ex UHP"
						,'AINPL'=>" Ex INPL "
						,'AUPVM'=>" Metz"
						);
	foreach ($poste_publics_en_conf as $strbloc=>$str_grps){
		$son_bloc = canonise_bloc($strbloc);
		$decor = " seul";
		if ($son_bloc[1]!=$son_bloc[0]) {
			$qd = implode('.', quelle_diff($son_bloc[1], $son_bloc[0]));
			$decor = " &agrave; ".ltrim($qd,'.');
		} 
		$tab_grps = explode('+', $str_grps);
		$etablissement="";
		foreach ($tab_grps as $grp) {
			if (isset ($grp_etab[$grp])){
				$etablissement=$grp_etab[$grp];
				break;
			}
		}
		if ($etablissement) $decor .= " pour ".$etablissement;
		$tab_trie[$son_bloc[0]] = $decor;
	}
	if (!ksort($tab_trie)) exit("ERR sot error/ erreur de tri de".print_r($tab_trie,true));
	en_trace("<hr /><h2>".
			trad_message('realise_regles', 'titPostespub').
			":</h2><hr />\n<table>");
	$der = "A000B000C000D000";
	foreach ($tab_trie as $canon=>$un_poste){
        $tab_aff  = quelle_diff($canon, $der); 
        $mess = "";
        foreach ($tab_aff as $octet){
        	if ($mess) $mess.="<td>."; 
        	else $mess="<td>";
        	$mess.= ($octet)? $octet : " - ";
        	$mess.="</td>"; 
        }
		$der=$canon;
		en_trace("<tr><td>$mess</td><td>$un_poste</td></tr>\n");
	}
	en_trace("</table><br /><hr />\n");
	
} else {
	en_trace("<hr /><h2>".
			trad_message('realise_regles', 'titNoPostespub').
			"</h2><hr />");
}

header( 'Cache-Control: no-cache' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );
  ?>
  <html>
  <head>

<link rel="shortcut icon" href="http://bu.univ-lorraine.fr/sites/all/themes/BU/favicon.ico" type="image/vnd.microsoft.icon" />
  <title>IP Verification</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  </head>
  <body topmargin="0" marginwidth="0" marginheight="0" <?=$fond?>>
<p> <img src="http://bu.univ-lorraine.fr/sites/all/themes/BU/logo.png" alt="Accueil" usemap="#Map"  /></p>
<map name="Map" id="Map">
<area shape="rect" coords="1,1,143,48" href="http://www.univ-lorraine.fr" />
<area shape="rect" coords="144,48,319,100" href="http://bu.univ-lorraine.fr" />
</map>
  
 <div align=center>
  <img src="bandeau_titre.jpg" alt="" />
<p>
  <?php affi_trace();?>
</p>
</div>
  </body>
  </html>
<?
  exit();
?>