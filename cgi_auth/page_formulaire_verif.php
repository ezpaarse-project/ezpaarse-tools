<?php

/**
 * Choice of group to force IP Source used by reverseP to access at providers services.
 * See in ezproxy in user.txt config file  IfMember GrpIPS1 ; IPSource xxxx   
 */
$groupes_IPSource = array	('GrpIPS1'=>"First group option"
						,'GrpIPS2'=>"Second IP group option"
						// ,...
						);
/**
 * Choice of access group to resources
 * See in ezproxy in config.txt config file  
 * Group ResGrp1
 * T DB1
 * U ...
 */						
$groupes_testes = array 	('ResGrp1'		=>"name first resources group"
							,'ResGrp1'		=>"name second resources group"
							//,...
							,'Ano' => "From anonymous access hosts"
							);
/**
 * Language files known in locale dir
 * Choice default language to use $lng_def
 */
$langues_possible = array('fr'=>'fran&ccedil;ais'
						,'en'=>'english'
						);							
$lng_def='en';														
														
  $forcequi = ($_SESSION['sv_CGI_verif']['forcequi'])?$_SESSION['sv_CGI_verif']['forcequi']:"";
  $forcede = ($_SESSION['sv_CGI_verif']['forcede'])?$_SESSION['sv_CGI_verif']['forcede']:"";
  if ($forcequi && !$forcede) $forcede=__VA_default_identifieur();
  $forceIP = ($_SESSION['sv_CGI_verif']['forceIP'])?$_SESSION['sv_CGI_verif']['forceIP']:"";
  $att_LDAP = (function_exists("LDAP_att_liste_statuts")) ? LDAP_att_liste_statuts():array();
  $forceIPgroupe = ($_SESSION['sv_CGI_verif']['forceIPgroupe'])?$_SESSION['sv_CGI_verif']['forceIPgroupe']:"";
  $forcegroupe = ($_SESSION['sv_CGI_verif']['forcegroupe'])?$_SESSION['sv_CGI_verif']['forcegroupe']:array();
  $url = ($_SESSION['sv_CGI_verif']['url'])?$_SESSION['sv_CGI_verif']['url']:"";
  
  $lng = ($_SESSION['sv_CGI_verif']['lng'])?$_SESSION['sv_CGI_verif']['lng']:$lng_def;
   $chkdev = ($_SESSION['sv_CGI_verif']['test'])?' checked':'';
   $chkldap = ($_SESSION['sv_CGI_verif']['ldap'])?' checked':'';
  $chkWAYF = ($_SESSION['sv_CGI_verif']['WAYF'])?' checked':'';
  $chkVAMAP=($_SESSION['sv_CGI_verif']['VA_MAP'])?' checked':'';

 $etablissement_force="";
$selecteur_etablissement = "";
  if (count($__VA_identifieurs)>1){
foreach ($__VA_identifieurs as $entree=>$desc){
      $estsel = ($entree==$forcede)?' selected':'';
      $selecteur_etablissement.= "<option value=\"$entree\"$estsel>".$desc['libelle']."</option>\n";
}
  } else {
  	 $etablissement_force=__VA_default_identifieur();
  }

$selecteur_langue="";
if (count($langues_possible)>1){
  	foreach ($langues_possible as $entree=>$libelle){
	      $estsel = ($entree==$lng)?' selected':'';
	      $selecteur_langue.= "<option value=\"$entree\"$estsel>$libelle</option>\n";
	}
}
$selecteur_att_LDAP = "";
foreach ($att_LDAP as $att=>$cache)
      {
      $estsel = ($cache)?' checked':'';
      $selecteur_att_LDAP.=" $att : <input type=checkbox name=\"inibit_$att\" value=\"$att\"$estsel>";
      }


$selecteur_dIP="";
foreach ($groupes_IPSource as $entree=>$libelle){
	    	if ($entree=="Flibre") continue;
	      	$estsel = ($entree== $forceIPgroupe)?' selected':'';
	      	$selecteur_dIP.="<option value=\"$entree\"$estsel>".$libelle."</option>\n";
}

	$avant = ""; $selecteur_droits_tete= $selecteur_droits_queue = ""; 
foreach ($groupes_testes as $entree=>$libelle){
      $estsel = (in_array($entree, $forcegroupe))?' checked':'';
      $selecteur_droits_tete.=$avant.$libelle;
      $selecteur_droits_queue.="$avant<input type=checkbox name=\"forcegroupe[]\" value=\"$entree\"$estsel>";
      $avant = "</th><th>|</th>\n<th>";
}

							
header( 'Cache-Control: no-cache' );
//  header( 'Cache-Control: must-revalidate' );
  header( 'Pragma: no-cache' );
  header( 'Expires: 0' );
  ?>
  <html>
  <head>

<link rel="shortcut icon" href="http://bu.univ-lorraine.fr/sites/all/themes/BU/favicon.ico" type="image/vnd.microsoft.icon" />
  <title>Verification d'acces</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  </head>
  <body topmargin="0" marginwidth="0" marginheight="0" <?=$fond?>>
 <div align=center>
  <img src="bandeau_titre.jpg" alt="" />

<p>
<?
if ($mess != "")
    	echo "\n        <font COLOR=red><b>$mess</b></font>\n";
?>    	
</p>

  <form method=GET action="verif.php">
  <input type=hidden name=tester value=1>
<p><?=$__VA_mess_identifieur?></p>
	<h3><?=trad_message('gen', 'Choix')?>  :</h3>
	<h4><?=trad_message('verif', 'titTstPers')?> :</h4>
<p>  
Login = <input type=text size=15 maxlength=25 name=forcequi value="<?=$forcequi?>"> 
<?php 
if ($selecteur_etablissement){
	echo (trad_message('verif' ,'deIdP',$selecteur_etablissement));
} elseif ($etablissement_force){
    echo ('<input type=hidden name="forcede" value="'.$etablissement_force.'">');
}
echo (trad_message('verif' ,'depuisIP',$forceIP)); 

if ($selecteur_att_LDAP){
	echo (trad_message('verif', 'ignoreAtt',$selecteur_att_LDAP));
}
?>
</p>  
	<h4><?php echo (trad_message('verif', 'titTstEdit'));?></h4>
<p>
  <?php 
echo (trad_message('verif', 'URLEdit',$url)."<br />\n");
if ($selecteur_dIP){
	echo (trad_message('verif', 'selSourceIP',$selecteur_dIP));
}	
if ($selecteur_droits_tete && $selecteur_droits_queue){
	echo (trad_message
				('verif'
				, 'selDroitsDIP'
				,array ($selecteur_droits_tete,$selecteur_droits_queue)
				)
		);
}  
?> 
</p>  
 
	<h3><?php echo (trad_message('verif', 'mode'));?></h3>
<p> 
<?php
if ($version_test) 
	echo (trad_message('verif', 'en_test',$chkdev));
if ($Valid_Acces_en_test)
	echo (trad_message('verif', 'VA__test',$chkVAMAP));	
echo (trad_message('verif', 'atts_ldap',$chkldap));
if ($selecteur_langue) 	
	echo (trad_message('verif', 'lng',$selecteur_langue));

?>	
<input TYPE="image" src="ok.png" onClick="this.form.submit();" value="<?=trad_message('gen', 'verif')?>" name="ok">
  </form>
</div>
  </body>
  </html>
<?
  exit();
?>