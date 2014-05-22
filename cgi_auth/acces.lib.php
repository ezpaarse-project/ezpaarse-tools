<?

/*
 * EZPROXY : CGI authentication tools.
 * Multi CAS/LDAP authentication Filter. 
 * valid_acces functions library. Don't modify
 * (Not translated) 
 */
/*
    ========================================================
      Bibliothèque liée à valid_acces.php (Vérification de droits d accès).
    ========================================================
*/
if (!isset ($__VAcces_PageAppelee))
  $__VAcces_PageAppelee=$_SERVER[REQUEST_URI];
$bdd_vision=false;
if (file_exists($dirClasses."bdd_vision.class.php"))
  {include_once ($dirClasses."bdd_vision.class.php"); $bdd_vision=true;}

/**
 *  Fonction __VAcces_identif :
  Vérifie le bon login de la personne, au besoin.
  en utilisant de la configuration (et trouvée en var. globales):
  $__VA_identifieurs = les services d identification d usager (répertoires d usagers) et
                       pour chacun les services "annuaires" à interroger pour présenter
                       l identité de la personne et déterminer ses droits d accès.
  ET $__VAcces_serveurs_CAS,$__VAcces_serveurs_LDAP,$__VAcces_serveurs_BDD =
                       serveurs répertoire et(ou) annuaire avec leurs paramètres de connexion
                       et, pour les annuaires, d interrogation.

  @param out $utilisateur array = ensemble des informations d annuaires connues sur
                                  l usager identifié.
  @param out $srv_util string = répertoire duquel est tiré l identification de l usager.
  @return string = * chaîne contenant les droits de l usager séparés les uns des autres par
                   des virgules
                   * "ERR:..." = erreur blocante rencontrée lors de l interrogation d un serveur
                   * "" = aucun droit reconnu à l usager
*/

function __VAcces_identif (&$utilisateur,&$srv_util)
  {
//  global $mess_err_connect;
//  require_once('LDAP.php');
  global $__VAcces_mode,$__VAcces_Anonyme,$__VApage, $__VAcces_PageAppelee
        ,$__VAcces_pdefaut
        ,$__VAcces_info_lues
        ,$__VA_Err_UsagerInconnu
        ,$__VA_identifieurs,$__VA_identifieur
        ,$__VAcces_serveurs_CAS,$__VAcces_serveurs_LDAP,$__VAcces_serveurs_BDD,$__VAcces_serveurs_fichier
        ,$bdd_vision;

  $srv_util='';
  $utilisateur = array('valide'=> false);
  $chg_identifieur = __VA_env_recup_var ('identifieur','') ;
  if ($__VAcces_Anonyme && 
  	  (
  	    (__VA_env_recup_var('anonyme')>0)
  	  || $chg_identifieur=="AUCUN"
  	  )
  	 ) 
    {
	__VA_met_xxquidam();  	
    return ($__VAcces_pdefaut);
    }
// --- Partie  détermination de l identifieur
//   Elle est shuntée si l accès est protégé par le serveur :

  if (isset ($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER']!="")
     &&
     isset ($_SERVER['PHP_AUTH_PW']) && ($_SERVER['PHP_AUTH_PW']!="")
     )
    {
    // L utilisateur est reconnu en tant que tel
    $user_connu=1; $mdp_valid=1;
    $ident_utilisateur = $_SERVER['PHP_AUTH_USER'];
    $mdp_utilisateur = $_SERVER['PHP_AUTH_PW'];
    $type_idf='serveur';
    $nom_idf=$_SERVER['SERVER_PROTOCOL'].'://'.$_SERVER['SERVER_NAME'];
    $srv_util = $type_idf.':'.$nom_idf;
    if (isset($_SERVER['SERVER_SOFTWARE']))
      $type_idf .= '('.$_SERVER['SERVER_SOFTWARE'].')';
    if (is_array($__VA_identifieurs['serveur']) && count($__VA_identifieurs['serveur'])>0)
      {
      $_SESSION['__VAcces_identifieur'] = $__VA_identifieur='serveur';
      $idf=$__VA_identifieurs['serveur'];
      }
    else
      {
      $utilisateur = array('Nom'=>$ident_utilisateur,'Prenom'=>'','Adresse'=>'',
                       'Adrel'=>'','Tel'=>'','Fax'=>'',
                       'login'=>$ident_utilisateur,'identifieur'=>'serveur',
                       'valide'=> true);
      return ($__VAcces_pdefaut);
      }
    }
  else 
    {
    if (!is_array($__VA_identifieurs) || count($__VA_identifieurs)<1)
      return ($__VAcces_pdefaut);
    $nb_identifieurs = count ($__VA_identifieurs);
    $seul_identifieur = '';
    $chg_identifieur = __VA_env_recup_var ('identifieur','') ;
    $chgt_identifieur = __VA_env_recup_var ('chg_idtf','') ;
    if ($chgt_identifieur!='') unset ($_SESSION['__VAcces_identifieur']);
    if ($nb_identifieurs == 1)
      {
      foreach ($__VA_identifieurs  as $cle=>$def)
        {
        	$seul_identifieur= $cle ;
        }
      }
    if ($nb_identifieurs == 1 && !($__VAcces_Anonyme))
      { $__VA_identifieur = $seul_identifieur; }
    elseif (! isset($__VA_identifieur) || $__VA_identifieur=='' || !isset($__VA_identifieurs[$__VA_identifieur]))
      {
      if ($chg_identifieur != '' && isset ($__VA_identifieurs[$chg_identifieur]))
        {$__VA_identifieur = $chg_identifieur ;}
      elseif (isset ($_SESSION ['__VAcces_identifieur']) && $_SESSION ['__VAcces_identifieur'] !='')
        {$__VA_identifieur = $_SESSION ['__VAcces_identifieur'] ;}
      else
        { __VA_demande_identifieur() ; exit() ;}
      }
    $_SESSION['__VAcces_identifieur'] = $__VA_identifieur ;

       // -- Partie identification de l usager dans le répertoire choisi ou imposé.
    $ident_utilisateur='';$mdp_utilisateur='';
    $user_connu=0; $mdp_valid=0;
    $idf = $__VA_identifieurs[$__VA_identifieur];
    $type_idf = $idf['type'] ;
    $nom_idf = $idf['nom'] ;
    $srv_util = "$type_idf:$nom_idf";
    $droits = (isset($idf['droits_auto']) && $idf['droits_auto']!='') ? ','.$idf['droits_auto'] :'' ;
    switch ($type_idf)
        {
        case 'CAS' :
          $def = $__VAcces_serveurs_CAS[$nom_idf];
          phpCAS::client ($def['version']
                         ,$def['serv']
                         ,$def['port']
                         ,$def['URI']
                         ,$def['NouvSession']
                         );
          phpCAS::setNoCasServerValidation();
          phpCAS::forceAuthentication ();
          $ident_utilisateur = phpCAS::getUser(); $mdp_utilisateur='OK';
          $user_connu=1; $mdp_valid=1; $srv_util='CAS:'.$nom_idf;
          $est_source = false;
          $utilisateur['login']=$ident_utilisateur;
          break;
        case 'LDAP' :
        case 'BDD' :
        case 'fichier' :
          $ident_utilisateur = __VA_env_recup_var('ident_utilisateur','');
          $mdp_utilisateur = __VA_env_recup_var('mdp_utilisateur','');
          if ($ident_utilisateur == '' || $mdp_utilisateur=='')
            {__VAcces_demande_idt() ;exit();}
          $est_source = (isset ($idf['est_source']) && $idf['est_source']);
          if ($type_idf == 'BDD')
            {
            $un_serveur = $__VAcces_serveurs_BDD[$nom_idf];
            $passu=$mdp_utilisateur;
            if (isset($idf['fct_mdp']) && $idf['fct_mdp']!='')
              {$passu = $idf['fct_mdp']."('".$passu.')';}
            $requete = $un_serveur['attident']."='$ident_utilisateur' and ".
                       $un_serveur['attmdp']. "='$passu'";
            if ($bdd_vision)
              {
              $Obj_annuaire = new bdd_vision($un_serveur['sgbd']
                                            ,$un_serveur['serv']
                                            ,$un_serveur['base']
                                            ,$un_serveur['utilbdd']
                                            ,$un_serveur['mdpbdd']
                                            );
              if ($Obj_annuaire->rech_nbre ($un_serveur['table'],'*',$requete)<=0)
                {
                $Obj_annuaire->ferme();
                __VAcces_demande_idt($__VA_Err_UsagerInconnu) ;
                exit();
                }
              else
                {
                $utilisateur['login']=$ident_utilisateur;
                if (! $est_source)
                  $Obj_annuaire->ferme();
                else
                  {
                  $droits .= __VAcces_lit_infos_BDD ($Obj_annuaire,$un_serveur,$utilisateur);
                  return ($droits);
                  }
                }
              }
            elseif ($un_serveur['sgbd']=='mysql')
              {
              $Obj_annuaire = mysql_connect ($un_serveur['serv']
                                            ,$un_serveur['utilbdd']
                                            ,$un_serveur['mdpbdd']
                                            );
              if (!mysql_select_db ($un_serveur['base'],$Obj_annuaire))
                {mysql_close($Obj_annuaire); return('ERR:Conf');}
              $requete = "select * from ".$un_serveur['table']." where ".$requete;
              $ressource = mysql_query($requete,$Obj_annuaire);

              if ($ressource===false)
                {mysql_close($Obj_annuaire); return('ERR:Interne');}
              $user_connu = mysql_num_rows($ressource);
              mysql_free_result ($ressource);
              if ($user_connu<1 || !$est_source)
                {
                mysql_close($Obj_annuaire);
                if ($user_connu<1)
                  {__VAcces_demande_idt($__VA_Err_UsagerInconnu) ;exit();}
                }
              $utilisateur['login']=$ident_utilisateur;
              if ($est_source)
                {
                $droits .= __VAcces_lit_infos_mysql($Obj_annuaire,$un_serveur,$utilisateur);
                return ($droits);
                }
              }
            else
              return ('ERR:Internal Unknown DB Sys. Interne moteur BdD inconnu.');
            }
          elseif ($type_idf == 'LDAP')
            {
            $un_serveur = $__VAcces_serveurs_LDAP[$nom_idf];
            $connect = __VAcces_connect_LDAP($un_serveur);            
            if (!$connect)
              { return ('ERR: LDAP '.$nom_idf.' not connectable - injoignable.');}
            $ressource = ldap_search($connect,$un_serveur['dn'],"(uid=$ident_utilisateur)",array('dn'));
            if ($ressource)
              {
              $tab_res = ldap_get_entries ( $connect, $ressource);
              if ($tab_res['count']==1)
                {
                $dn_utilisateur = $tab_res[0]['dn'];
                $exist = ldap_bind($connect,$dn_utilisateur, $mdp_utilisateur);
                if ($exist)
                  {$user_connu = 1; $utilisateur['login'] = $ident_utilisateur;}
                }
              }
            if ($est_source && $user_connu>0)
              {
              $droits .= __VAcces_lit_infos_LDAP($connect,$un_serveur,$utilisateur);
              return ($droits);
              }

            ldap_unbind($connect);
            if ($user_connu <= 0)
              {__VAcces_demande_idt($__VA_Err_UsagerInconnu) ;exit();}
            }
          elseif ($type_idf=='fichier'){
          	$fichier_desc = $__VAcces_serveurs_fichier[$nom_idf];
          	$nom_fichier = $fichier_desc['fichier'];
          	$tab_utilisateurs=file($nom_fichier);
          	foreach ($tab_utilisateurs as $un_utilisateur){
          		$tab_u=explode(':',$un_utilisateur);
          		if ($tab_u[0]!=$ident_utilisateur) continue;
          	    if ($tab_u[1]!=$mdp_utilisateur) break;
          		$user_connu=1;
          	    $mdp_valid=1;
                $utilisateur['login'] = $ident_utilisateur;
                $droits.= ','.$tab_u[2];
                return ($droits);
          	}
            __VAcces_demande_idt($__VA_Err_UsagerInconnu) ;
            exit();          	
          } 
          $user_connu=1; $mdp_valid=1;
        } 
    } 
// Préétablissement des éléments concernant l utilisateur :

  $utilisateur = array ('Nom'=>'','Prenom'=>'','Adresse'=>''
                       ,'Adrel'=>'','Tel'=>'','Fax'=>''
                       ,'login'=>$ident_utilisateur
                       ,'valide'=>false
                       );
  $sources = $idf['sources'] ;
  if (is_array($sources) && count($sources) >0)
    {
    foreach ($sources as $entree=>$protocole)
      {
      switch ($protocole)
        {
        case 'LDAP' :
          $un_serveur = $__VAcces_serveurs_LDAP[$entree];
          $connect = __VAcces_connect_LDAP($un_serveur);          
          if (!$connect)
              { continue;}
          $ndroits = __VAcces_lit_infos_LDAP ($connect,$un_serveur,$utilisateur);
          break ;
        case 'BDD' :
          if ($bdd_vision)
              {
              $Obj_annuaire = new bdd_vision($un_serveur['sgbd']
                                            ,$un_serveur['serv']
                                            ,$un_serveur['base']
                                            ,$un_serveur['utilbdd']
                                            ,$un_serveur['mdpbdd']
                                            );
              $ndroits = __VAcces_lit_infos_BDD ($Obj_annuaire,$un_serveur,$utilisateur);
              }
          elseif ($un_serveur['sgbd']=='mysql')
            {
            $Obj_annuaire = mysql_connect ($un_serveur['serv']
                                          ,$un_serveur['utilbdd']
                                          ,$un_serveur['mdpbdd']
                              );
            if (!mysql_select_db ($un_serveur['base'],$Obj_annuaire))
              {mysql_close($Obj_annuaire); continue;}
            $ndroits = __VAcces_lit_infos_mysql($Obj_annuaire,$un_serveur,$utilisateur);
            }
          else
            continue;
          break ;
        }
      if ($ndroits != '')
        { $droits .= $ndroits ; $utilisateur['valide'] = true;}
      }  // fin de boucle sur les sources d'informations
    }
  else {
    $utilisateur['valide'] = true;
  }
  if (substr($droits,0,1)==',') $droits = substr($droits,1);
  return ($droits) ;
  }

function __VAcces_simul_identif (&$utilisateur,$srv_util,$login)
  {
  global $__VAcces_mode,$__VAcces_Anonyme,$__VApage, $__VAcces_PageAppelee
        ,$__VAcces_pdefaut
        ,$__VAcces_info_lues
        ,$__VA_Err_UsagerInconnu
        ,$__VA_identifieurs,$__VA_identifieur
        ,$__VAcces_serveurs_CAS,$__VAcces_serveurs_LDAP,$__VAcces_serveurs_BDD,$__VAcces_serveurs_fichier
        ,$bdd_vision;

  if ($srv_util=="AUCUN" || $login== 'xxquidam')
    {
	$utilisateur = array('Nom'=>'Inconnu','Prenom'=>'','Adresse'=>'',
                   'Adrel'=>'','Tel'=>'','Fax'=>'',
                   'login'=>'xxquidam','identifieur'=>'','valide'=> true);
    return ($__VAcces_pdefaut);
    }
// --- Partie  détermination de l identifieur
//   Elle est shuntée si l accès est protégé par le serveur :
	$utilisateur['login']=$ident_utilisateur=$login;
  if ($srv_util=='serveur')
    {
    // L utilisateur est reconnu en tant que tel
    $type_idf='serveur';
    if (is_array($__VA_identifieurs['serveur']) && count($__VA_identifieurs['serveur'])>0)
      { 
      	$idf=$__VA_identifieurs['serveur'];
      	$nom_idf='serveur'; $est_source = false;
      }
    else
      {
      $utilisateur = array('Nom'=>$login,'Prenom'=>'','Adresse'=>'',
                       'Adrel'=>'','Tel'=>'','Fax'=>'',
                       'login'=>$login,'identifieur'=>'serveur',
                       'valide'=> true);
      return ($__VAcces_pdefaut);
      }
    }
  else // Cas d utilisation d un identifieur (pas de protection serveur)
    {
    $idf = $__VA_identifieurs[$srv_util];
    $nom_idf=$idf['nom'];
    $droits = (isset($idf['droits_auto']) && $idf['droits_auto']!='') ? ','.$idf['droits_auto'] :'' ;
    switch ($type_idf)
        {
        case 'CAS' :
          $est_source = false;
          break;
        case 'LDAP' :
        case 'BDD' :
        case 'fichier' :
          $est_source = (isset ($idf['est_source']) && $idf['est_source']);
          if ($type_idf == 'BDD')
            {
            $un_serveur = $__VAcces_serveurs_BDD[$nom_idf];
            $requete = $un_serveur['attident']."='$login'";
            if ($bdd_vision)
              {
              $Obj_annuaire = new bdd_vision($un_serveur['sgbd']
                                            ,$un_serveur['serv']
                                            ,$un_serveur['base']
                                            ,$un_serveur['utilbdd']
                                            ,$un_serveur['mdpbdd']
                                            );
              if ($Obj_annuaire->rech_nbre ($un_serveur['table'],'*',$requete)<=0)
                { $utilisateur=array();return (false);}
              else
                {
                $utilisateur['login']=$login;
                if (! $est_source)
                  $Obj_annuaire->ferme();
                else
                  {
                  $droits .= __VAcces_lit_infos_BDD ($Obj_annuaire,$un_serveur,$utilisateur);
                  return ($droits);
                  }
                }
              }
            elseif ($un_serveur['sgbd']=='mysql')
              {
              $Obj_annuaire = mysql_connect ($un_serveur['serv']
                                            ,$un_serveur['utilbdd']
                                            ,$un_serveur['mdpbdd']
                                            );
              if (!mysql_select_db ($un_serveur['base'],$Obj_annuaire))
                {mysql_close($Obj_annuaire); return('ERR:Conf');}
              $requete = "select * from ".$un_serveur['table']." where ".$requete;
              $ressource = mysql_query($requete,$Obj_annuaire);

              if ($ressource===false)
                {mysql_close($Obj_annuaire); return('ERR:Interne');}
              $user_connu = mysql_num_rows($ressource);
              mysql_free_result ($ressource);
              if ($user_connu<1 || !$est_source)
                {
                mysql_close($Obj_annuaire);
                if ($user_connu<1)
                  {$utilisateur=array();return (false);}
                }
              $utilisateur['login']=$login;
              if ($est_source)
                {
                $droits .= __VAcces_lit_infos_mysql($Obj_annuaire,$un_serveur,$utilisateur);
                return ($droits);
                }
              }
            else
              return ('ERR: Internal Unknown DB Sys. - Interne moteur BdD inconnu.');
            }
          elseif ($type_idf == 'LDAP')
            {
            $un_serveur = $__VAcces_serveurs_LDAP[$nom_idf];
            $connect = __VAcces_connect_LDAP($un_serveur);            
            if (!$connect)
              { return ('ERR: LDAP '.$nom_idf.' non connectable - injoignable.');}
            $ressource = ldap_search($connect,$un_serveur['dn'],"(uid=$login)",array('dn'));
            if ($ressource)
              {
              $tab_res = ldap_get_entries ( $connect, $ressource);
              if ($tab_res['count']==1)
                {$user_connu = 1; $utilisateur['login'] = $login;}
              }
            if ($est_source && $user_connu>0)
              {
              $droits .= __VAcces_lit_infos_LDAP($connect,$un_serveur,$utilisateur);
              return ($droits);
              }
            ldap_unbind($connect);
            if ($user_connu <= 0)
              {$utilisateur=array();return (false);}
            }
          elseif ($type_idf=='fichier'){
          	$fichier_desc = $__VAcces_serveurs_fichier[$nom_idf];
          	$nom_fichier = $fichier_desc['fichier'];
          	$tab_utilisateurs=file($nom_fichier);
          	foreach ($tab_utilisateurs as $un_utilisateur){
          		$tab_u=explode(':',$un_utilisateur);
          		if ($tab_u[0]!=$login) continue;
          		$user_connu=1;
                $utilisateur['login'] = $login;
                $droits.= ','.$tab_u[2];
                return ($droits);
          	}
            $utilisateur=array();return (false);
          } // fin du cas fichier
          $user_connu=1; 
        } // fin du switch sur type d identifieur
    } // fin du cas où on utilise un identifieur car pas de protection par le serveur
// Préétablissement des éléments concernant l utilisateur :

  $utilisateur = array ('Nom'=>'','Prenom'=>'','Adresse'=>''
                       ,'Adrel'=>'','Tel'=>'','Fax'=>''
                       ,'login'=>$login,'identifieur'=>$type_idf.':'.$nom_idf
                       ,'valide'=>false
                       );
  $sources = $idf['sources'] ;
  if (is_array($sources) && count($sources) >0)
    {
    foreach ($sources as $entree=>$protocole)
      {
      switch ($protocole)
        {
        case 'LDAP' :
          $un_serveur = $__VAcces_serveurs_LDAP[$entree];
          $connect = __VAcces_connect_LDAP($un_serveur);          
          if (!$connect)
              { echo ("ERR: Access denied to LDAP service $entree - acc&egrave;s au LDAP $entree impossible.<br />\n"); continue;}
          $ndroits = __VAcces_lit_infos_LDAP ($connect,$un_serveur,$utilisateur);
          break ;
        case 'BDD' :
          if ($bdd_vision)
              {
              $Obj_annuaire = new bdd_vision($un_serveur['sgbd']
                                            ,$un_serveur['serv']
                                            ,$un_serveur['base']
                                            ,$un_serveur['utilbdd']
                                            ,$un_serveur['mdpbdd']
                                            );
              $ndroits = __VAcces_lit_infos_BDD ($Obj_annuaire,$un_serveur,$utilisateur);
              }
          elseif ($un_serveur['sgbd']=='mysql')
            {
            $Obj_annuaire = mysql_connect ($un_serveur['serv']
                                          ,$un_serveur['utilbdd']
                                          ,$un_serveur['mdpbdd']
                              );
            if (!mysql_select_db ($un_serveur['base'],$Obj_annuaire))
              {mysql_close($Obj_annuaire); continue;}
            $ndroits = __VAcces_lit_infos_mysql($Obj_annuaire,$un_serveur,$utilisateur);
            }
          else
            continue;
          break ;
        }
        if ($ndroits!='') $droits .= $ndroits ; 
      }  // fin de boucle sur les sources d'informations
    }
  else { // cas où il n'y a pas de source
    $utilisateur['valide'] = true;
  }
  if (substr($droits,0,1)==',') $droits = substr($droits,1);
  return ($droits) ;
  }
  

/*
    Fonction qui résoud le choix d un répertoire par l usager et provoque un exit().
  Plusieurs scenari possibles :
  - l application dispose d une fonction : "ma_demande_identifieur" ou "my_IdP_selector" 
    qui remplit cette fonction => elle est appelée
  - création d un formulaire de saisie du paramètre identifieur
    avec possibilité d entrée anonyme (sans identifieur).
  @param $mess : message d erreur à afficher.

*/
function __VA_demande_identifieur($mess='')
  {
  global $__VAcces_Anonyme
         , $__VAImageFond,$__VACouleurPolice,$__VACouleurFond,$__VApage, $__VA_identifieurs
         ,$__VA_mess_anonyme,$__VA_mess_identifieur,$__VA_mess_OK
         ,$__VAcces_PageAppelee,$__VA_MAP;
  if (function_exists ("ma_demande_identifieur")) {
    ma_demande_identifieur($mess);
    exit();
  } elseif (function_exists ("my_IdP_selector")) {
    my_IdP_selector($mess);
    exit();
  }  
  header( 'Cache-Control: no-cache' );
  header( 'Cache-Control: must-revalidate' );
  header( 'Pragma: no-cache' );
  header( 'Expires: 0' );
  if ($__VApage == '')
    $__VApage = $__VAcces_PageAppelee;
    $fond = "";
    if ($__VAImageFond!="")
      {$fond = ' background="'.$__VAImageFond.'"';}
    elseif ($__VACouleurFond!='')
      {$fond = ' bgcolor="'.$__VACouleurFond.'"';}
    if ($__VACouleurPolice!="")
      {$fond .= ' text="'.$__VACouleurPolice.'"';}

  ?>
  <html>
  <head>
  <title>Identifier - Identifieur</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  </head>
  <body topmargin="0" marginwidth="0" marginheight="0" <?=$fond?>>
  <font color="#FFFF00"><?=$__VA_MAP?></font>
 <form action="<?=$__VApage?>" method="post">
  <input type=hidden name=demarre value=1>
  <TABLE align=center width=100%>
     <TR>
<?
  if ($mess != "")
    echo "\n        <td>  <font COLOR=red><b>$mess</b></font></td>\n";
  if ($__VAcces_Anonyme)
    $__VA_identifieurs['AUCUN']= array ("libelle"=> "None - Aucun"); 
              
    
  if (count($__VA_identifieurs)==1)
    {
    foreach ($__VA_identifieurs as $entree=>$desc)
      {
      echo "<td> ".
           "    <input type=hidden name=identifieur value=\"\">\n".
           '    <input type=button value="'.$desc['libelle'].'" onClick="'.
                 "this.form.identifieur.value='$entree';this.form.submit();". '">'.
           "</td>\n";
      break;
      }
    }
  else
    {
    if ($__VA_mess_identifieur) $__VA_mess_identifieur.=" :";
?>
        <td><?=$__VA_mess_identifieur?>
        <select name="identifieur" onChange="this.form.submit();">
        	<option value="">Accounting organisation / &Eacute;tablissement de votre compte :</option>
<?
    foreach ($__VA_identifieurs as $entree=>$desc)
      {     echo "<option value=\"$entree\">".$desc['libelle']."</option>\n";     }
?>
        </select>
         </td>
        <!-- < td align=center > < input TYPE="submit" value="<?=$__VA_mess_OK?>" name="ok" >< /td >  -->
<?
    }
?>
     </tr>
  </table>
  </form>
  </body>
  </html>
<?
  exit();
  }

/*
    Fonction qui résoud la demande d identité de l usager et provoque un exit().
  Plusieurs scenari possibles :
  - l application dispose d une fonction : "ma_demande_idt" ou "my_login_page"
    qui remplit cette fonction, elle est appelée
  - accès limité par le serveur : on force un filtrage serveur à l aide des entêtes voulues
  - création d un formulaire de saisie des paramètres ident_utilisateur et mdp_utilisateur
    avec possibilité d entrée anonyme.
  @param $mess : message d erreur à afficher.

*/
function __VAcces_demande_idt($mess='')
  {
  global $__VAcces_mode, $__VAcces_Anonyme
         , $__VAImageFond,$__VACouleurFond,$__VACouleurPolice
         ,$__VA_mess_anonyme,$__VA_mess_identification,$__VA_mess_login,$__VA_mess_mdp
         ,$__VA_mess_OK
         ,$__VApage
         ,$__VAcces_PageAppelee,$__VA_MAP;
  if (function_exists ("ma_demande_idt")) {
    ma_demande_idt($mess);
    exit();
  } elseif (function_exists ("my_login_page")) {
    my_login_page($mess);
    exit();
  } 
  if ($__VAcces_mode == 'serveur')
    {
    header('WWW-Authenticate: Basic realm="Encycloped"');
    header('HTTP/1.0 401 Unauthorized');
    echo $mess.'<br>Identification obligatoire';
    exit();
    }
    header( 'Cache-Control: no-cache' );
    header( 'Cache-Control: must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    if ($__VApage == '')
      $__VApage = $__VAcces_PageAppelee;
    $fond = "";
    if ($__VAImageFond!="")
      {$fond = ' background="'.$__VAImageFond.'"';}
    elseif ($__VACouleurFond!='')
      {$fond = ' bgcolor="'.$__VACouleurFond.'"';}
    if ($__VACouleurPolice!="")
      {$fond .= ' text="'.$__VACouleurPolice.'"';}

                         //MAP $__VA_MAP .=   "Page = $__VApage <br>Env _SERVER = ".html_print_r($_SERVER);
  ?>
  <html>
  <head>
  <title>Identity - Identite</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  </head>
  <body topmargin="0" marginwidth="0" marginheight="0"<?=$fond?>>
  <font color="#FFFF00"><?=$__VA_MAP?></font>

 <form action="<?=$__VApage?>" method="post">
  <input type=hidden name=demarre value=1>
  <TABLE align=center width=100%>
     <TR valign=middle>
<?
    if ($mess != "")
      echo "\n        <td>  <font COLOR=red><b>$mess</b></font></td>\n";
    if ($__VAcces_Anonyme)
     echo "<td> ".
          "    <input type=hidden name=anonyme value=\"\">\n".
          "    <input type=button value='$__VA_mess_anonyme' onClick=".
                '"this.form.anonyme.value=1;this.form.submit();"'."> ou\n".
          "</td>\n";

?>
        <td> <?=$__VA_mess_identification?> :  </td>
        <TD align=right> <?=$__VA_mess_login?> :</TD>
        <TD> <input TYPE="text" name="ident_utilisateur"> </TD>
        <TD align=right">  <?=$__VA_mess_mdp?> : </TD>
        <TD> <input TYPE="password" name="mdp_utilisateur"> </TD>
        <td align=center><input TYPE="submit" value="<?=$__VA_mess_OK?>" "name="ok"></td>
     </tr>
  </table>
  </form>
  </body>
  </html>
<?
    exit();
  }

  /* ============  Interrogation d Annuaire LDAP ====================*/

/**
 * Connexion à un serveur LDAP :
 * Fonction qui prend en charge la connexion au serveur LDAP .
 * L'objet $un_serveur contient obligatoirement le DNS
 */
function __VAcces_connect_LDAP ($un_serveur) {
	global $__VA_MAP; $mess_MAP="";
	if ($__VA_MAP) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
		$serveurs = explode(' ', $un_serveur['serv']);
		foreach ($serveurs as $serveur){
			if ($__VA_MAP) $mess_MAP.="tentative avec $serveur..<br />";
	          $port = '';
	          if (isset($un_serveur['port']) && $un_serveur['port']!= '')
	            $port = $un_serveur['port'];
	          elseif ( preg_match("/\\:(\d+)\$/",$serveur,$captures) )
	            {
	            $port = $captures[1]*1;
	            $serveur = preg_replace ("/".$captures[0]."/",'',$serveur);
	            } 
	          $connect = ($port>0)? ldap_connect($serveur,$port):ldap_connect($serveur);
	          $c = ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
	        if (!$c)
	              { if ($__VA_MAP) $mess_MAP.= "Cannot create LDAP Object with version 3 - Impossible de créer la ressource en version protocole 3...<br />"; continue; }
		if ($__VA_MAP) $mess_MAP.= "LDAP resource object OK - ressource pour connexion créée...<br />";
	        if (isset($un_serveur['audn']) && $un_serveur['audn']!=""
	        	&& isset($un_serveur['aupw']) && $un_serveur['aupw']!=""
	        	) {
	        	if (!ldap_bind($connect,$un_serveur['audn'],$un_serveur['aupw']))
	        		{if ($__VA_MAP) $mess_MAP.= "Cannot bind for - Liaison impossible pour ".$un_serveur['audn']." ...<br />";continue;}
	        } else {
	        	if (!ldap_bind($connect))
	        		{if ($__VA_MAP) $mess_MAP.= "Anonymous bind denied - Liaison anonyme échouée...<br />";continue;}
	        }
		 $mess_MAP.= "Bind OK - liaison établie...<br />";
	        return ($connect);			
		}
	if ($__VA_MAP) echo $mess_MAP."None LDAP service admitance. Aucun serveur LDAP joignable !<br />";
	return (FALSE);
}




/*
  Lecture des infos et droits de l usager dans un serveur LDAP .
  @param $conver : connexion à la base de donnée obtenue par ldap_connect
  @param $un_serveur : tableau associatif donnant les attribut à utiliser pour récupérer
                       les infos dans la base. V. la description en conf
  @param $utilisateur : tableau associatif d information mémorisée pour l usager.
                        Ce tableau DOIT contenir une entrée 'login' utilisée récupérer l entrée
                        correspondant à l usager par le filtre : (uid=login).
  @return = chaîne contenant les droits acquis séparé par une virgule. La chaîne commence aussi par
            une virgule.
*/
function __VAcces_lit_infos_LDAP ($connect,$un_serveur,&$utilisateur)
  {
  	global $__VA_MAP; 
   // Etablissement de la liste des attributs de l usager à lire :
  $lattr = array('dn');
  if (isset($un_serveur['attnom']) && $un_serveur['attnom']!='')
     {$attnom = strtolower($un_serveur['attnom']); $lattr[] = $attnom;}
  else
    $attnom = false;
  if (isset($un_serveur['attprenom']) && $un_serveur['attprenom']!='')
    {$attprenom = strtolower($un_serveur['attprenom']); $lattr[] = $attprenom;}
  else
    $attprenom = false;
  if (isset($un_serveur['attadrel']) && $un_serveur['attadrel']!='')
    {$attadrel = strtolower($un_serveur['attadrel']); $lattr[] = $attadrel;}
  else
    $attadrel = false;
  if (isset($un_serveur['attadr']) && $un_serveur['attadr']!='')
    {$attadr = strtolower($un_serveur['attadr']); $lattr[] = $attadr;}
  else
    $attadr = false;
  if (isset($un_serveur['atttel']) && $un_serveur['atttel']!='')
    {$atttel = strtolower($un_serveur['atttel']); $lattr[] = $atttel;}
  else
    $atttel = false;
  if (isset($un_serveur['attfax']) && $un_serveur['attfax']!='')
    {$attfax = strtolower($un_serveur['attfax']); $lattr[] = $attfax;}
  else
    $attfax = false;
  if (isset($un_serveur['atts']) && is_array($un_serveur['atts'])){
	  foreach ($un_serveur['atts'] as $nom_att=>$valeurs)
	    { $lattr[]=strtolower($nom_att);}
  } else {
  	$un_serveur['atts']=array();
  }
  if (isset($un_serveur['atts_lus']) && is_array($un_serveur['atts_lus'])){
	  foreach ($un_serveur['atts_lus'] as $nom_att)
	    { $lattr[]=strtolower($nom_att);}
  } else {
  	$un_serveur['atts_lus']=array();
  }
  $__VAcces_info_lues = '';
  $filtre = '(uid='.$utilisateur['login'].")";
  $ressource = ldap_search($connect,$un_serveur['dn'],$filtre,$lattr);
  if ($ressource)
    {
    $tab_res = ldap_get_entries ( $connect, $ressource);
    if ($__VA_MAP) {echo "LDAP lues  pour $filtre =". str_replace("\n", " <br/>\n", print_r($tab_res,true));}
    if ($tab_res['count']==1)
      $utilisateur['valide']=true;
      $__VAcces_info_lues = __VA_LDAP_cleanUpEntry( $tab_res[0] );
    }
  ldap_unbind($connect);
  if (! is_array($__VAcces_info_lues)) return ('');
  if ($attnom)
     $utilisateur['Nom'] = $__VAcces_info_lues[$attnom];
  if ($attprenom)
     $utilisateur['Prenom']=$__VAcces_info_lues[$attprenom];
  if ($attadr)
     $utilisateur['Adresse']=$__VAcces_info_lues[$attadr];
  if ($attadrel)
     $utilisateur['Adrel']=$__VAcces_info_lues[$attadrel];
  if ($atttel)
     $utilisateur['Tel']=$__VAcces_info_lues[$atttel];
  if ($attfax)
     $utilisateur['Fax']=$__VAcces_info_lues[$attfax];
       // Boucle sur les champs qui permettent d établir des droits
  foreach ($un_serveur['atts'] as $nom_att=>$valeurs)
    {
    $nom_att_u = strtolower($nom_att);
    if (!isset($__VAcces_info_lues[$nom_att_u]) || $__VAcces_info_lues[$nom_att_u] === '')
          continue;
    $tbval = (is_array($__VAcces_info_lues[$nom_att_u])) ?
                  $__VAcces_info_lues[$nom_att_u]:
                  array($__VAcces_info_lues[$nom_att_u]);
    if (count($tbval)<=0 )
          continue;
    if (!is_array($valeurs))
      {
//          if  (count($__VAcces_info_lues[$nom_att])>0) $droit.=','.$valeurs;
      $droit.=','.$valeurs;
      continue;
      }
    
    foreach ($tbval as $v)
      {
//      if (isset($valeurs[$v]) && $valeurs[$v]!= '')        {$droit.=','.$valeurs[$v];}
	  foreach ($valeurs as $vtest=>$d) {if (strpos($v,$vtest)!==false) {$droit.=','.$d;} }
      }
    if (isset($valeurs['*']))
      {$droit.=','.$valeurs['*'];}
    } // fin de boucle sur les champs de droits.
    // Boucle sur les autres champs à transmettre
   foreach ($un_serveur['atts_lus'] as $nom_att)
    {
    $nom_att_u = strtolower($nom_att);
    $tbval = array();
    if (isset($__VAcces_info_lues[$nom_att_u]) && $__VAcces_info_lues[$nom_att_u] !== '') {
	    $tbval = (is_array($__VAcces_info_lues[$nom_att_u])) ?
	                  $__VAcces_info_lues[$nom_att_u]:
	                  array($__VAcces_info_lues[$nom_att_u]);
      }
    $utilisateur[$nom_att]=$tbval;
    } // fin de boucle sur les champs de droits.
    
  return ($droit);
  }
/*
  Fonction qui permet d exploiter les retours des serveurs LDAP :
  Elle construit un tableau associatif des attribut d une entrée LDAP lue où chaque
  valeur peut être un tableau de valeurs.
*/
function __VA_LDAP_cleanUpEntry( $entry ) {
   $retEntry = array();
   for ( $i = 0; $i < $entry['count']; $i++ ) {
       $attribute = $entry[$i];
       if ( $entry[$attribute]['count'] == 1 ) {
           $retEntry[$attribute] = $entry[$attribute][0];
       } else {
           for ( $j = 0; $j < $entry[$attribute]['count']; $j++ ) {
               $retEntry[$attribute][] = $entry[$attribute][$j];
           }
       }
   }
   return $retEntry;
}
/*
  Récupération d'un paramètre de la page (uniquement fourni en GET ou POST)
*/
function __VA_env_recup_var ($param,$v_def="",$efface=false, $vide_significatif=false)
  {
  global $__VAcces_PageAppelee;
  $tst_bool=  ( ($v_def === true) || ($v_def===false) );
  $res = $v_def; $trouve = false;
  if (isset($_POST[$param]))
    {
    if ($tst_bool)
      {$res = true; }
    elseif ($_POST[$param] != "" || $vide_significatif)
      { $res = $_POST[$param]; }
    if ($efface) unset($_POST[$param]);
    return ($res);
    }
  //var HTTP meth GET
  if (isset($_GET[$param]))
    {
    if ($tst_bool)
      {$res = true; }
    elseif ($_GET[$param] != "" || $vide_significatif)
      { $res = $_GET[$param]; }
    if ($efface)
      {
      $__VAcces_PageAppelee = preg_replace ("/((\\?|&)$param=\\w+)/","",$__VAcces_PageAppelee);
      }
    return ($res);
    }
  return $v_def;
  }
  

function __VA_print_r($utilisateur,$mem=false){
	if (!is_array($utilisateur)) {
         if (!$mem) echo $utilisateur;
	  return ($utilisateur);
	}
	$chaine= "<TABLE>";
	foreach ($utilisateur as $cle=>$val) {
		$chaine.= "<tr><td>$cle</td><td>";
		if (is_array($val)) $chaine.=__VA_print_r($val,true);
		else $chaine.= $val;
		$chaine.= "</td></tr>";
	}
	$chaine.="</TABLE>";
	if (!$mem) echo $chaine;
	return ($chaine);
} 

function __VA_nettoie_session(){
	if (isset($_SESSION['_VAcces_utilisateur']))unset ($_SESSION['_VAcces_utilisateur']);
	if (isset($_SESSION['__VAcces_identifieur']))unset ($_SESSION['__VAcces_identifieur']);
	if (isset($_SESSION['__VAcces_autoidentifieur']))unset ($_SESSION['__VAcces_autoidentifieur']);
	if (isset($_SESSION['_VAcces_droit'])) unset($_SESSION['_VAcces_droit']);
	if (isset($_SESSION['_VAcces_tmps_limite'])) unset ($_SESSION['_VAcces_tmps_limite']);	
}

function __VA_droits (){
	if (isset($_SESSION['_VAcces_droit']) && $_SESSION['_VAcces_droit']!='') {
		return($_SESSION['_VAcces_droit']);
	}
	return (false);
}
function __VA_utilisateur (){
	if (isset ($_SESSION['_VAcces_utilisateur']) 
		&& 
		is_array($_SESSION['_VAcces_utilisateur'])
		&&
		$_SESSION['_VAcces_utilisateur']['valide']
		) {
			return ($_SESSION['_VAcces_utilisateur']);
		}
	return (false);
}

function __VA_identifieur (){
	if (isset($_SESSION['__VAcces_identifieur']) && $_SESSION['__VAcces_identifieur']!= "" 
		&& ( mktime()<=$_SESSION['_VAcces_tmps_limite'] || __VA_utilisateur() )
		) {
		return  ($_SESSION['__VAcces_identifieur']);
	}
	unset ($_SESSION['__VAcces_identifieur']);
	return (false);
}

function __VA_default_identifieur(){
	global $__VA_identifieur,$__VA_identifieurs;
	if (isset($__VA_identifieur) && $__VA_identifieur) return ($__VA_identifieur);
	if (count($__VA_identifieurs)>0) {
		$noms = array_keys($__VA_identifieurs);
		return ($noms[0]);
	}
	return ("");
}

function __VA_met_xxquidam() {
	global $__VAcces_pdefaut;
	  	$_SESSION['_VAcces_utilisateur']= 
	  		array	('Nom'=>'Inconnu','Prenom'=>'Inconnu','Adresse'=>''
                   	,'Adrel'=>'','Tel'=>'','Fax'=>''
                   	,'login'=>'xxquidam','identifieur'=>'AUCUN','valide'=> true);
        $_SESSION['__VAcces_identifieur']='AUCUN';
        $_SESSION['_VAcces_droit'] = $__VAcces_pdefaut; 
	return (TRUE);
}

function __VA_state_english_profile (){
	$_SESSION['_VAcces_utilisateur']['Name']=
	 	($_SESSION['_VAcces_utilisateur']['Nom']=='Inconnu')?
	 	'Unknown':$_SESSION['_VAcces_utilisateur']['Nom'];
	$_SESSION['_VAcces_utilisateur']['FirstName']=
	 	($_SESSION['_VAcces_utilisateur']['Prenom']=='Inconnu')?
	 	'Unknown':$_SESSION['_VAcces_utilisateur']['Prenom'];
	$_SESSION['_VAcces_utilisateur']['PostalAddress']= 
		$_SESSION['_VAcces_utilisateur']['Adresse'];
	$_SESSION['_VAcces_utilisateur']['EmailBox']= 
		$_SESSION['_VAcces_utilisateur']['Adrel'];
	$_SESSION['_VAcces_utilisateur']['identifier']= 
		$_SESSION['_VAcces_utilisateur']['identifieur'];
	$_SESSION['_VAcces_utilisateur']['ValidUser']= 
		$_SESSION['_VAcces_utilisateur']['valide'];		
}

function __VA_echo_etat (){
			echo "Identifier - Identifieur : ". __VA_identifieur().
			"User - Utilisateur : ".__VA_print_r(__VA_utilisateur(),true)."<br />\n".
			"Rights - Droits : " . __VA_droits()."<br />\n";	

      }
?>