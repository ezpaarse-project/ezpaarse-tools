<?php

//include_once ("./accesRef.lib.php");
include_once ("conf/conf.inc.php");

function reform_url ($tab_url) {
	$url = ($tab_url['scheme']) ? $tab_url['scheme']: "http";
	$url .= '://';
	if ($tab_url['user']) {
		$url.= $tab_url['user'];
		if ($tab_url['pass']) $url.= ':'.$tab_url['pass'];
		$url.='@';
	}
	$url .= $tab_url['host'];
	if ($tab_url['port']) $url .= ':'.$tab_url['port'];
	if ($tab_url['path']) $url .= $tab_url['path'];
	if ($tab_url['query']) $url .= '?'.$tab_url['query'];
	if ($tab_url['fragment']) $url .= '#'.$tab_url['fragment'];
	return ($url);
}
function traite_pmid ($chaine){
	global $resolveur_add_pmid;
	$res = $resolveur_add_pmid.urlencode($chaine);
	return ($res);
}
function traite_doi ($chaine){
	global $resolveur_add_doi;
	$res = $resolveur_add_doi.urlencode($chaine);
	return ($res);	
}
function traite_url ($url){
	global $MAP,$dir_sources,$fic_liste,$comm,$err,$reverse;
	if (strpos($url, '%')!== false) $url= urldecode($url); 
	if (strpos($url,$reverse)===FALSE){
		$tab_url = parse_url($url);
		if ($tab_url===false){
			$err = "$url invalide";
			return (false);
		}
		if (!$tab_url['host']){
			$path = $tab_url['path'];
			if ($tab_url['path'][0]=='/'){
				$err = "$url invalide";
				return (false);
			}
			$pslash = strpos($path, '/'); 
			if ($pslash !== false) {
				$tab_url['path']=substr($path,$pslash);
				$tab_url['host']=substr($path,0,$pslash);
			} else {
				$tab_url['host']=$path;
				unset ($tab_url['path']);
			}
		}
		$le_host = $tab_url['host'];
		$tab_host = file ("$dir_sources/$fic_liste");
		foreach ($tab_host as $un_host){
			$htest = trim($un_host);
			if (strpos($le_host,$htest)!==false) {
				$tab_url['host'].='.'.$reverse;
				$trouve=true;
				break;
			}		
		}
		$url = reform_url ($tab_url);
	} else {
		$trouve=true;
	}
	if (!$trouve){ 
		$comm = "Not in our resources,  maybe free - \n".
				"Cette ressource hors de nos abonnements, elle est peut-&ecirc;tre en acc&egrave;s libre.";
		$url="";
	}
	return ($url);	
}

function traite_donnee (){
	global $err,$comm,$resolveur_add_doi,$resolveur_add_pmid;	
	$err = $comm = ""; $url=""; $trouve=false;
	$chaine = $_GET['url'];
	if (!$chaine) return("");
	$pslash = strpos($chaine,'/'); 
	if ($pslash === false && $resolveur_add_pmid) {
		if (preg_match(":[a-zA-Z]:", $chaine)) {
			return (traite_url($chaine));
		} else {
		return (traite_pmid($chaine));
		}
	} elseif ($resolveur_add_doi) {
		$deb_chaine = substr($chaine, 0, $pslash);
		if (preg_match(":[a-zA-Z]:", $deb_chaine)) {
			return (traite_url($chaine));
		} else {
			return(traite_doi($chaine)) ;
		}	
	} else return (traite_url($chaine));
}
?>