<?
/*
 * EZPROXY : 
 * General library for mysql access 
 * (Not translated)
 */

require_once("DB.php");

  if (!defined('bdd_vision_SGBD_INCON'))
    define (bdd_vision_SGBD_INCON, "SGBD Inconnu");
   if (!defined('bdd_vision_MESS0'))
    define (bdd_vision_MESS0, "Définition de connexion invalide.");
   if (!defined('bdd_vision_NON_OUVERTE'))
    define (bdd_vision_NON_OUVERTE,"Base non ouverte.");
   if (!defined('bdd_vision_REQ_SS_FROM'))
    define (bdd_vision_REQ_SS_FROM,"Requête invalide : from absent.");
   if (!defined('bdd_vision_REQ_SS_SEL'))
    define (bdd_vision_REQ_SS_SEL,"Requête invalide : select absent.");
   if (!defined('bdd_vision_REQ_FROM_INV'))
    define (bdd_vision_REQ_FROM_INV,"Requête invalide : clause from.");
   if (!defined('bdd_vision_REQ_SEL_INV'))
    define (bdd_vision_REQ_SEL_INV,"Requête invalide : clause select.");
   if (!defined('bdd_vision_REQ_ORD_INV'))
    define (bdd_vision_REQ_ORD_INV,"Requête invalide : clause order.");
   if (!defined('bdd_vision_REQ_LIM_INV'))
    define (bdd_vision_REQ_LIM_INV,"Requête invalide : clause limite.");
   if (! is_array($bdd_vision_SGBD_CONNUS))
    $bdd_vision_SGBD_CONNUS =
      array('mysql'=>
             array('limite'=>true
                  ,'premier'=>true
                  ,'transaction'=>false
                  ,'standard_insert'=>true
                  ,'auto_insert'=>true
                  ,'txt_base_std'=>true
                  ,'limit_retrait'=>true
                  )
           );

/*
       ========================================================
          Utilitaires
       ========================================================
*/
    /**
    * Formatage d un texte pour interrogation ou insertion en base
    * @param string $chaine  : chaîne à formater
    * @return string : Chaîne formatée.
    * @access public
    */
  function txt_base($chaine,$sgbd_txt='txt_base_std')
    {
    if ($sgbd_txt=='txt_base_std')
      return (str_replace("'","''",$chaine));
    return ($chaine);
    }

  /**
  * Connexion à une base de données
  *
  * La classe BDD_common contient des fonctions permettant d exécuter des requêtes sur une base de données au travers dun frontal réalisé à l aide de la classe DB des PEAR
  *
  * @author Mathieu LARCHET <mathieu.larchet@laposte.net>
  * @access public
  * @package BDD
  */

class bdd_vision
  {
    /**
    * DB Object : identifiant de connexion
    * @var DB Object
    * @access private
    */
  var $connexion;

    /**
    * Etat de la connexion :
    * 0 : non ouvrable
    * 1 : fermée
    * 2 : ouverte
    * 3 : en transaction
    * 4 : en requête
    *
    * @var integer
    * @access private
    */
  var $etat;
    /**
    * Mémorisation de l état de la connexion :
    * @var integer
    * @access private
    */
  var $mem_etat;

    /**
    * URL pour la connexion à la base (obsolète)
    * @var string
    * @access private
    */
//    var $dsn;

    /**
    * Type de moteur de BDD permet de forger l URL pour la connexion
    * à la base
    * @var string
    * @access private
    */
  var $sgbd;


    /**
    * Possibilités du moteur utilisé tité du tableau
    * $bdd_vision_SGBD_CONNUS ci-dessus
    * @var hash $pos_sgbd
    * @access private
    */
  var $pos_sgbd;
    /**
    * Nom de la BDD permet de forger l URL pour la connexion
    * à la base
    * @var string
    * @access private
    */
  var $nombd;

    /**
    * Nom du serveur hébergeant la BDD : permet de forger l URL
    * pour la connexion à la base
    * @var string
    * @access private
    */
  var $serveurbd;

    /**
    * utilisateur vituel pour forger l URL pour la connexion
    * @var string
    * @access private
    */
  var $util;

    /**
    * mot de passe de l utilisateur virtuel (pour URL de la connexion)
    * @var string
    * @access private
    */
  var $mdp;

    /**
    * Message de la dernière requête qui a échoué
    * @var object DB_Result
    * @access private
    */
  var $mess_err_rech;

    /**
    * Libellé de la dernière requête lancée pour débogage
    * @var object string
    * @access private
    */
  var $der_requete;

    /**
    * Objet d usage interne contenant le résultat de la dernière
    * recherche utilisateur
    * @var object DB_Result
    * @access private
    */
  var $rech_ressource;

    /**
    * Constructeur
    * @param string $dsn l URL de connexion
    * @access public
    */

  function bdd_vision ($sgbd,$serveur, $base,$util,$mdp, $debug=false)
    {
    global $bdd_vision_SGBD_CONNUS;
    $this->connexion = NULL;
    $this->etat=0;
    $this->mem_etat=0;
    $this->mess_err_rech = "";
    if ($sgbd == ''
       || $serveur == ''
       || $base == ''
       || $util == ''
       || $mdp == ''
       )
      {
      $this->mess_err_rech = bdd_vision_MESS0;
      if ($debug)
        {
        echo "A la creation serveur=$serveur, base=$base, util=$util";
        echo ($mdp=='')?'mot de passe vide':'mdp fourni';
        echo "<br>\n";
        }
      }
    elseif (array_key_exists($sgbd,$bdd_vision_SGBD_CONNUS)===false)
      {
      $this->mess_err_rech = bdd_vision_SGBD_INCON;

      if ($debug)
        {
        echo   bdd_vision_SGBD_INCON .":$sgbd parmi<br>\n";
        echo (join (',',$bdd_vision_SGBD_CONNUS));
        }
      }
    else
      {
      $this->sgbd = $sgbd;
      $this->serveurbd = $serveur;
      $this->nombd = $base;
      $this->util = $util;
      $this->mdp = $mdp;
      $this->etat=1;
      $this->mem_etat=1;
      $this->pos_sgbd = $bdd_vision_SGBD_CONNUS[$sgbd];
      $this->der_requete = "Creation";
      }
    }

    /**
    * Ouvre la connexion à la base de données
    * @return int -1: pb 0: déjà connecté 1: OK
    * @access public
    */

  function ouvre($persiste=true, $debug=false)
    {
    $this->der_requete = "ouverture de ".$this->nombd."<br>\n";
    if ($this->etat==0)
      {
      $this->mess_err_rech = bdd_vision_MESS0;
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (-1);
      }
    if ($this->etat > 1)
      {
      $this->mess_err_rech = "Déjà connectée";
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (0);
      }
    $dsn = $this->sgbd."://".$this->util.":".$this->mdp."@".
           $this->serveurbd."/".$this->nombd;
    $this->connexion = DB::connect($dsn,$persiste);
    if (DB::isError($this->connexion))
      {
      $this->mess_err_rech =
        "Pb de connexion : ".$this->connexion->getMessage();
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (-1);
      }
    return (1);
    }

    /**
    * Ferme la connexion à la base de données
    * @return int -1: pb 0: déjà déconnecté 1: OK
    * @access public
    */

  function ferme($debug=false)
    {
    $this->der_requete = "fermeture de ".$this->nombd."<br>\n";
    if ($this->etat==0)
      {
      $this->mess_err_rech = bdd_vision_MESS0;
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (-1);
      }
    if ($this->etat == 1)
      {
      $this->mess_err_rech = "Déjà déconnectée";
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (0);
      }
    if ($this->connexion->disconnect())
      {
      $this->mess_err_rech="";
      $this->etat = 1;
      return(1);
      }
    else
      {
      $this->mess_err_rech =
          "Pb de déconnexion : ".$this->connexion->getMessage();
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (-1);
      }
    }

    /**
    * Récupération du dernier message d erreur
    * @return string : Chaîne contenant le dernier message.
    * @access public
    */
  function message ()
    {return $this->mess_err_rech;}

    /**
    * Récupération du dernier message d erreur
    * @return string : Chaîne contenant le dernier message.
    * @access public
    */
  function requete ()
    {return $this->der_requete;}

    /**
    * Rend la connexion de l objet DB
    * @return NULL ou la connexion
    * @access public
    */

  function DB_connexion ($debug=false)
    {
    if ($this->etat <= 0)
      {
      $this->mess_err_rech = bdd_vision_MESS0;
      if ($debug) echo $this->mess_err_rech;
      return (NULL);
      }
    if ($this->etat==1 && $this->ouvre()<=0) return (NULL) ;
    return $this->connexion;
    }

/*
       ========================================================
         Recherches :
       ========================================================
*/
/*
         -----------------------------------------------------
          Recherche avec résultat en tableau assoc.
         -----------------------------------------------------
*/
    /**
    * Envoi d une requête générale :
    * @param string $chaine  : chaîne contenant la requête
    * @return int : 1 OK / -1 erreur.
    * @access public
    */
  function requere ($requete, $debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0)
      {  return (-2); }
/* Test de validité du from : */
    $this->der_requete = $requete;
    $this->mess_err_rech = '';
    $result = $this->connexion->query($requete);
//    if (DB::isError($result) || DB::isWarning($result))
    if (DB::isError($result))
      {
      $this->mess_err_rech =
        "Requete invalide ($requete) : ".
                 $result->getMessage();
      if ($debug) echo $this->mess_err_rech;
      return (-1);
      }
    if (is_object($result))
      {
      if (preg_match("/select /i",$requete))
        {
        $this->rech_ressource = $result;
        $this->mem_etat = $this->etat;
        $this->etat = 4;
        return ($this->rech_ressource->numRows());
        }
      return (0);
      }
    return ($result);
    }
    /**
    * Récupération des résultat d une requête générale :
    * @param mixed $resultat  : variable recevant le résultat
    * @param int $mode : DB_FETCHMODE_ORDERED ou DB_FETCHMODE_ASSOC
    *                    DB_FETCHMODE_OBJECT
    * @return int : 1 OK / -1 erreur.
    * @access public
    */
  function lit_res (&$resultat,$mode=DB_FETCHMODE_ORDERED, $debug=false)
    {
    if ($this->etat != 4) return (-2);
    if ($resultat = $this->rech_ressource->fetchRow($mode))
      return (count($resultat));
    $this->etat = $this->mem_etat;
    return (-1);
    }


    /**
    * Exécute une recherche SQL et charge un tableau de résultats
    *           formés de tableaux associatifs dont chaque clé corresp.
    *           à un champ du select.
    * @param string ou array $liste  : tableau indexé recevant les
    *            résultat i.e. chaque élément est un tableau assoc.
    *            image d une ligne de select.
    * @param string ou array $from  : chaîne correspondant à la clause
    *             from d une requête SQL ou tableau (liste)
    *             d association ('table','alias')
    *             de nom de tables et de leurs alias correspond.
    * @param string ou array $select  : chaîne correspondant à la clause
    *             select d une requête SQL ou tableau (liste)
    *             d association ('table', 'champ','alias')
    *             des champs à récupérer. La chaîne mise dans 'table'
    *             doit correspondre à une table ou un alias de $from.
    * @param string $where  : chaîne correspondant à la where
    *             d une requête SQL.
    * @param mixed $ordre : correspond à la clause
    *             order d une requête SQL chaîne ou tableau (liste)
    *             d association ('table', 'champ', 'inverse') des champs servant
    *             au tri.
    *             Dans le cas de tableau, la chaîne mise dans
    *            'table' doit correspondre à une table ou un alias de $from.
    * @param int $limite : indice du dernier élément du résultat
    *             global à fournir. défaut 0 : pas de limite max .
    * @param int $premier : indice du premier élément du résultat
    *             global à fournir défaut 0 : à partir du premier résultat.
    * @return int : nombre de résultats obtenus, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access public
    */

  function rech_assoc (&$liste,$from,$select='*',$where='',$ordre='',
                         $limite=0,$premier=0, $debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0)
      {  return (-2); }
/* Test de validité du from : */
    $this->der_requete = "analyse de from:$from";
    $this->mess_err_rech = '';
    $fromu = bdd_vision::anafrom($from);
    if ($fromu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_FROM;  }
    elseif ($fromu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_FROM_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }


    $this->der_requete = "analyse de select:$select";
    $selectu = bdd_vision::anaselect($select);
    if ($selectu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_SEL;  }
    elseif ($selectu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_SEL_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }

    $this->der_requete = "analyse de ordre:$ordre";
    $ordru = bdd_vision::anaorder($ordre);
    if ($ordru=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_ORD_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }

    $this->der_requete = "théo construit $selectu grace_a $fromu tel_que $where par $ordru";
    if ($limite>0 && $premier>0 && $limite<$premier)
      {$premier = $limite;}
    $requete = "SELECT $selectu FROM $fromu";
    if ($where != "") $requete .= " WHERE $where";
    if ($ordru != "") $requete .= " ORDER BY $ordru";
    $this->der_requete = $requete;
    switch ($this->sgbd)
      {
      case 'mysql' :
        $ret = $this->mysql_rech0 ($requete,$limite,$premier);
        break;
      }
                         //printDebug ("bdd_vision.rech_assoc(brut de $requete):".$ret);
    $liste=array();
    if ($ret <= 0)
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return ($ret);
      }
    if ($this->pos_sgbd['limite'] || $limite <= 0 )
      $der=$ret;
    else
      $der = $limite;
    if ($this->pos_sgbd['premier'] || $premier<=0 )
      $prem = 0;
    else
      $prem = $premier - 1;
    $indc = 0; $ret = 0;
    while (($row = $this->rech_ressource->fetchRow(DB_FETCHMODE_ASSOC))
           && ($indc < $der))
      {
      if ($indc>=$prem) {$liste[]=$row; $ret++;}
      $indc++;
      }
    $this->rech_ressource->free();
    return($ret);
    }
    /**
    * Exécute une recherche SQL qui consiste à retrouver une liste de
    *         valeurs et charge un tableau de résultats avec ces valeurs.
    *         Le select indique l élément d information à extraire de chaque
    *         ligne résultat et à introduire dans la liste.
    * @param string or array $liste  : tableau recevant les
    *            valeurs résultat .
    * @param string ou array $from  : chaîne correspondant à la clause
    *             from d une requête SQL ou tableau (liste)
    *             d association ('table','alias')
    *             de nom de tables et de leurs alias correspond.
    * @param string $select  : chaîne correspondant à la clause
    *             select de la requête SQL .
    * @param string $where  : chaîne correspondant à la where
    *             d une requête SQL.
    * @param integer $ordre : -1=décroissant 0=sans 1 =croissant.
    * @param integer $limitmax : indice du dernier élément du résultat
    *             global à fournir. défaut 0 : pas de limite max .
    * @param integer $limitmin : indice du premier élément du résultat
    *             global à fournir défaut 0 : à partir du premier résultat.
    * @return integer : nombre de résultats obtenus, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access public
    */

  function rech_liste_de (&$liste,$from,$select,$where='',$ordre=1,
                         $limite=0,$premier=0, $debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0)
      {  return (-2); }
/* Test de validité du from : */
    $this->der_requete = "echoue sur from";
    $this->mess_err_rech = '';
    $fromu = bdd_vision::anafrom($from);
    if ($fromu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_FROM;  }
    elseif ($fromu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_FROM_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }

    $this->der_requete = "echoue sur select";
    if (is_array($select)) $selectu='XX';
    elseif (preg_match ("/[^\\w \\.]/",$select)) $selectu='XX';
    else $selectu = trim($select);
    if ($selectu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_SEL;  }
    elseif ($selectu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_SEL_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }

    $this->der_requete = "echoue sur ordre";
    if ($ordre != -1 && $ordre != 0 && $ordre != 1) $ordru=='XX';
    else
      {
      $ordru = $selectu ;
      if ($ordre<0) $ordru .= ' DESC';
      }
    if ($ordru=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_ORD_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }

    $this->der_requete = "théo construit $selectu grace_a $fromu tel_que $where par $ordru";
    if ($limite>0 && $premier>0 && $limite<$premier)
      {$premier = $limite;}
    $requete = "SELECT DISTINCT($selectu) FROM $fromu";
    if ($where != "") $requete .= " WHERE $where";
    if ($ordru != 0) $requete .= " ORDER BY $ordru";
    $this->der_requete = $requete;
    switch ($this->sgbd)
      {
      case 'mysql' :
        $ret = $this->mysql_rech0 ($requete,$limite,$premier);
        break;
      }
                         //printDebug ("bdd_vision.rech_assoc(brut de $requete):".$ret);
    $liste=array();
    if ($ret <= 0)
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return ($ret);
      }

    if ($this->pos_sgbd['limite'] || $limite <= 0 )
      $der=$ret;
    else
      $der = $limite;
    if ($this->pos_sgbd['premier'] || $premier<=0 )
      $prem = 0;
    else
      $prem = $premier - 1;
    $indc = 0; $ret = 0;
    while (($row = $this->rech_ressource->fetchRow(DB_FETCHMODE_ORDERED))
           && ($indc < $der))
      {
      if ($indc>=$prem) {$liste[]=$row[0]; $ret++; }
      $indc++;
      }
    $this->rech_ressource->free();
    return($ret);
    }
/*
         -----------------------------------------------------
          Recherche du nombre de réponse seul, à une requête.
         -----------------------------------------------------
*/
    /**
    * Exécute le comptage du nombre de ligne
    * @param string ou array $from  : chaîne correspondant à la clause
    *             from d une requête SQL ou tableau (liste)
    *             d association ('table','alias')
    *             de nom de tables et de leurs alias correspond.
    * @param string ou array $select  : chaîne correspondant à la clause
    *             select d une requête SQL ou tableau (liste)
    *             d association ('table', 'champ')
    *             des champs à récupérer. La chaîne mise dans 'table'
    *             doit correspondre à une table ou un alias de $from.
    * @param string $where  : chaîne correspondant à la where
    *             d une requête SQL.
    * @param int $limite : indice du dernier élément du résultat
    *             global à fournir. défaut 0 : pas de limite max .
    * @return int : nombre de résultats obtenus, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access public
    */

  function rech_nbre  ($from,$select='*',$where='',$limite=0, $debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0)
      {  return (-2); }
/* Test de validité du from : */
    $this->der_requete = "théo construit $select grace_a $from tel_que $where";
    $this->mess_err_rech = '';
    $fromu = bdd_vision::anafrom($from);
    if ($fromu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_FROM;  }
    elseif ($fromu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_FROM_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }


    $selectu = bdd_vision::anaselect($select);
    if ($selectu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_SEL;  }
    elseif ($selectu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_SEL_INV; }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }

    $requete = "SELECT COUNT($selectu) FROM $fromu";
    if ($where != "") $requete .= " WHERE $where";
    $this->der_requete = $requete;

    switch ($this->sgbd)
      {
      case 'mysql' :
        return ($this->mysql_rech_nbre ($requete,$limite));
        break;
      }
    }
/*
        -------------------------------------------------------------
           Manipulation des données :
        -------------------------------------------------------------
*/
    /**
    * Insère une ligne dans un tableau tout en récupérant l'identifiant
    * généré par le SGBD pour cette ligne.
    * @param string $table  : nom de la table réceptrice.
    * @param array $valeurs  : tableau assoc. faisant correspondre à
    *            chaque champ sa valeur.
    * @param int $auto : indique que le retour doit être le nouvel
    *            identifiant généré si le SGBD le permet
    * @return int : identifiant numérique généré ou négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access public
    */

  function ajoute ($table, $valeurs,$auto=0, $debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0)
      {  return (-2); }

    $requete_insert = "INSERT INTO $table ";
    $cols = ""; $vals = "";
    foreach ($valeurs as $col=>$val)
      {
      $cols .= ",".$col;
      $vals .= ",";
      if (preg_match("/^\d+\$/",$val))
         {$vals .= $val;}
      else
        {$vals .= "'".txt_base($val)."'";}
      }
    $requete_insert .= "(".substr($cols,1).") VALUES (".substr($vals,1).")";
    $this->mess_err_rech = "";
    $this->der_requete = $requete_insert;
    switch ($this->sgbd)
      {
      case 'mysql' :
        $ret = $this->mysql_ajoute($requete_insert,$auto);
        break;
      }
    return ($ret);
    }

    /**
    * Exécute le retrait de lignes des tables désignées dans $tables
    * suite à une recherche indiquée par $from $where . On peut limiter
    * artificiellement le nombre de lignes retirées en fixant un ordre
    * et une limite.
    * @param string ou array $tables  : chaîne correspondant à la liste
    *            des tables dont on retire des lignes ou tableau indexé
    *            contenant la liste de ces tables
    * @param string ou array $from  : chaîne correspondant à la clause
    *             from d une requête SQL ou tableau (liste)
    *             d association ('table','alias')
    *             de nom de tables et de leurs alias correspond.
    *            Cette liste est un sur-ensemble de la précédente et indique
    *            toutes les tables nécessaires à la sélection des lignes
    *            par $where.
    * @param string $where  : chaîne correspondant à la where
    *             d une requête SQL.
    * @param string ou array $ordre : correspond à la clause
    *             order d une requête SQL chaîne ou tableau (liste)
    *             d association ('table', 'champ', 'inverse') des champs servant
    *             au tri.
    *             Dans le cas de tableau, la chaîne mise dans
    *            'table' doit correspondre à une table ou un alias de $from.
    * @param int $limite : nombre maximum de lignes à retirer
    * @return int : nombre de lignes retirées, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access public
    */

  function retire ($tables, $from='', $where='', $ordre='', $limite=0,$debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0) return (-2);


    if (is_array($tables))
      { $tablesu = join(',',$tables); $nbtables = count($tables);}
    else
      { $tablesu=$tables; $nbtables = count(split(",",$tables));}
    $this->der_requete = "retrait de $tables\n";
    $this->mess_err_rech = '';

/* Test de validité du from : */
    $fromu = bdd_vision::anafrom($from);
    if ($fromu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_FROM_INV; }
    else
      {
      if ($this->pos_sgbd['limit_retrait'])
        { $ordru = bdd_vision::anaorder($ordre); }
      else
        {
        if ($ordre != '') $ordru='XX';
        if ($limite > 0) $limite = -1;
        }
      if ($ordru=='XX')
        { $this->mess_err_rech = bdd_vision_REQ_ORD_INV; }
      if ($limite < 0)
        { $this->mess_err_rech = bdd_vision_REQ_LIM_INV; }
      }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (-1);
      }

    $requete = 'DELETE FROM '.$tablesu; $this->der_requete=$requete;
    switch ($this->sgbd)
      {
      case 'mysql' :
        if ($nbtables>1 && $limite > 0)
          {
          $this->mess_err_rech = "Retrait: limite et multitable incompatibles.";
          return(-1);
          }
        if ($fromu!='')
          {$requete .= ' USING '.$fromu;}
        if ($where != '')
          $requete.= ' WHERE '.$where;
        if ($limite>0)
          {
          if ($ordru != '') $requete .= ' ORDER BY '.$ordru;
          $requete .= ' LIMIT '.$limite;
          }
        $this->der_requete=$requete;
        $ret = $this->requere($requete);
        if ($ret > 0) $ret = $this->rech_ressource;
        break;
      }
    return ($ret);
    }

    /**
    * Exécute la mise à jour des lignes des tables désignées dans $tables
    * suite à une recherche indiquée par $where . On peut limiter
    * artificiellement le nombre de lignes modifiées en fixant un ordre
    * et une limite. Les champs et leurs nouvelles valeurs sont spécifiés
    * dans $valeurs
    * @param string ou array $tables  : chaîne correspondant la liste
    *            des tables utiles à la sélection (de $where)ou
    *            modifiées (dans $valeurs) ou tableau indexé
    *            d association ('table','alias')
    *            des noms de tables et de leurs alias correspondants.
    * @param array $valeurs  : tableau d'assoc. faisant correspondre à
    *            chaque champ (ou table.champ) sa valeur.
    * @param string $where  : chaîne correspondant à la clause where
    *             d une requête SQL.
    * @param string ou array $ordre : correspond à la clause
    *             order d une requête SQL chaîne ou tableau (liste)
    *             d association ('table', 'champ', 'inverse') des champs servant
    *             au tri.
    *             Dans le cas de tableau, la chaîne mise dans
    *            'table' doit correspondre à une table ou un alias de $tables.
    * @param int $limite : nombre maximum de lignes à modifier
    * @return int : nombre de lignes corrigées, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access public
    */

  function corrige ($tables, $valeurs, $where='', $ordre='', $limite=0, $debug=false)
    {
/* Test d état valide : */
    if ($this->ouvre(true,$debug)<0) return (-2);
    $this->der_requete = "Corrige $tables avec $valeurs telque $where limite a $limite si ds ordre $ordre";
    $this->mess_err_rech = '';
    $tablesu = bdd_vision::anafrom ($tables);
    if($tablesu=='')
      { $this->mess_err_rech = bdd_vision_REQ_SS_FROM;  }
    elseif($tablesu=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_FROM_INV;  }
    if ($this->mess_err_rech != '')
      {
      if ($debug) echo $this->der_requete .':'.$this->mess_err_rech;
      return (-1);
      }
    $nbtables = count(split(",",$tables));
    $valu = '';
    foreach ($valeurs as $col=>$val)
      {
      if (preg_match("/^\d+\$/",$val))
         {$vals = $val;}
      else
        {$vals = "'".txt_base($val)."'";}

      $valu .= ','.$col.'='.$vals;
      }
/* Test de validité de la limite : */

    if ($limite>0 && $this->pos_sgbd['limit_retrait'])
      { $ordru = bdd_vision::anaorder($ordre); }
    else
      {
      if ($ordre != '') $ordru='XX';
      if ($limite > 0) $limite = -1;
      }
    if ($ordru=='XX')
      { $this->mess_err_rech = bdd_vision_REQ_ORD_INV; }
    if ($limite < 0)
      { $this->mess_err_rech = bdd_vision_REQ_LIM_INV; }
    if ($this->mess_err_rech!='')
      {
      if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
      return (-1);
      }

    $requete = 'UPDATE '.$tablesu . ' SET '.substr($valu,1);
    if ($where!='') $requete.= ' WHERE '.$where;
    $this->der_requete = $requete;
    switch ($this->sgbd)
      {
      case 'mysql' :
        if ($nbtables>1 && $limite > 0)
          {
          $this->mess_err_rech = "Correction: limite et multitable incompatibles.";
          if ($debug) echo $this->der_requete.':'.$this->mess_err_rech;
          return(-1);
          }
        if ($limite>0)
          {
          if ($ordru != '') $requete .= ' ORDER BY '.$ordru;
          $requete .= ' LIMIT '.$limite;
          }
        $this->der_requete = $requete;
        $ret = $this->requere($requete);
        if ($ret>0) $ret = $this->rech_ressource;
        break;
      }
    return ($ret);
    }

/*
        -------------------------------------------------------------
           Primitives d analyse pour préparer les req. SQL :
        -------------------------------------------------------------
*/
    /**
    * Analyse un tableau ou une chaîne contenant la clause from d une
    *     requête SQL.
    * @param string ou array $from  : chaîne correspondant à la clause
    *             from d une requête SQL ou tableau (liste)
    *             d association ('table','alias')
    *             de nom de tables et de leurs alias correspond.
    * @return string : chaîne correspondante, vide s'il y a un pepin.
    * @access private
    */
  function anafrom ($from)
    {
    if (is_array($from))
      {
      $froms = "";
      foreach ($from as $une_table)
        {
        $item = $une_table['table'];
        if (isset($une_table['alias']) && $une_table['alias']!='')
          $item .=  ' '.$une_table['alias'];
        if ($item=='') return ('XX');
        if ($froms != "") $froms.= ', ';
        $froms .= $item;
        }
      }
    else {$froms = $from;}
    return ($froms);
    }

    /**
    * Analyse un tableau ou une chaîne contenant la clause select d une
    *     requête SQL.
    * @param string ou array $select  : chaîne correspondant à la clause
    *             select d une requête SQL ou tableau (liste)
    *             d association ('table', 'champ')
    *             des champs à récupérer. La chaîne mise dans 'table'
    *             doit correspondre à une table ou un alias de $from.
    * @return string : chaîne correspondante, vide s'il y a un pepin.
    * @access private
    */
  function anaselect ($select)
    {
    if (is_array($select))
      {
      $selects = "";
      foreach ($select as $une_table)
        {
        $item = "";
        if (isset($une_table['table']) && $une_table['table']!='')
          $item .= $une_table['table'].'.';
        $item .= $une_table['champ'];
        if (isset($une_table['alias']) && $une_table['alias']!='')
          $item .= ' as '.$une_table['alias'];
        if ($item=='') return ('XX');
        if ($selects != "") $selects.= ', ';
        $selects .= $item;
        }
      }
    elseif ($select != '') {$selects = $select;}
    else {$selects='*';}
    return ($selects);
    }


    /**
    * Analyse un tableau ou une chaîne contenant la clause select d une
    *     requête SQL.
    * @param string ou array $ordre : correspond à la clause
    *             order d une requête SQL chaîne ou tableau (liste)
    *             d association ('table', 'champ', 'inverse') des champs servant
    *             au tri.
    *             Dans le cas de tableau, la chaîne mise dans
    *            'table' doit correspondre à une table ou un alias de $from.
    * @return string : chaîne correspondante, 'XX' s'il y a un pepin.
    * @access private
    */
  function anaorder ($ordre)
    {
    if (is_array($ordre))
      {
      $ordres = "";
      foreach ($ordre as $une_table)
        {
        $item = "";
        if (isset($une_table['inverse']) && $une_table['inverse']!='')
          $item .= 'DESC ';
        if (isset($une_table['table']) && $une_table['table']!='')
          $item .= $une_table['table'].'.';
        $item .= $une_table['champ'];
        if ($item == "") return ('XX');
        if ($ordres != "") $ordres.= ', ';
        $ordres .= $item;
        }
      }
    else {$ordres = $ordre;}
    return ($ordres);
    }

/*
        -------------------------------------------------------------
           Primitives pour MySQL :
        -------------------------------------------------------------
*/
    /**
    * Exécute une recherche dans le cadre de mysql
    * @param string $from  : chaîne correspondant à la clause
    *             from de la requête SQL .
    * @param string $select  : chaîne correspondant à la clause
    *             select de la requête SQL .
    * @param string $where  : chaîne correspondant à la where
    *             de la requête SQL .
    * @param string ou array $ordre : correspond à la clause
    *             order  de la requête SQL .
    * @param int $limitmax : indice du dernier élément du résultat
    *             global à fournir. défaut 0 : pas de limite max .
    * @param int $limitmin : indice du premier élément du résultat
    *             global à fournir défaut 0 : à partir du premier résultat.
    * @return int : nombre de résultats obtenus, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access private
    */

  function mysql_rech0 ($req,$limitmax=0,$limitmin=0)
    {
    if ($limitmax > 0)
      {
      $req .= ' limit ';
      if ($limitmin > 0 && $limitmin<=$limitmax)
        { $req .= $limitmin;}
      else {$req .= '0';}
      $req .= ','.$limitmax;
      }
    $result = $this->connexion->query($req);
    if (DB::isError($result))
      {
      $this->mess_err_rech =
        "Erreur dans la req&ecirc;te = ".$req." : ".
         $result->getMessage();
      return (-1);
      }
    $this->rech_ressource=$result;
    return ($this->rech_ressource->numRows());
    }
    /**
    * Exécute le comptage du nombre de ligne
    * @param string $from  : chaîne correspondant à la clause
    *             from de la requête SQL.
    * @param string $select  : chaîne correspondant à la clause
    *             select.
    * @param string $where  : chaîne correspondant à la where
    *             d une requête SQL.
    * @param int $limitmax : indice du dernier élément du résultat
    *             global à fournir. défaut 0 : pas de limite max .
    * @return int : nombre de résultats obtenus, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access private
    */

  function mysql_rech_nbre  ($req,$limite=0)
    {
    if ($limite > 0) $req .= ' limit 0,'.$limite;
    $ret = $this->connexion->getOne($req);
    if (DB::isError($ret))
      {
      $this->mess_err_rech = "Erreur dans la req&ecirc;te = ".$req." : ".
                        $ret->getMessage();
      return (-1);
      }
    return ($ret);
    }
    /**
    * Exécute le comptage du nombre de ligne
    * @param string $requete_insert : requête formatée plus haut
    * @return int : nouvel identifiant généré, négatif si erreur.
    *             Dans ce cas, le message erreur est positionné
    * @access private
    */
   function mysql_ajoute ($requete_insert,$auto)
     {
     $result = $this->connexion->query($requete_insert);
     if (DB::isError($result))
       {
       $this->mess_err_rech="Erreur d'insertion ($requete_insert) : ".
                 $result->getMessage();
       return (-1);
       }
     if ($auto<=0) return (1);
     return ($this->connexion->getOne("select last_insert_id();"));
     }

  }
?>