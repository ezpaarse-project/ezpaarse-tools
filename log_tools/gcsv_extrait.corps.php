<?php
/*
 * Implantation :
 */
$ce_repertoire = dirname(__FILE__);
$racinePhys  = preg_replace (":/traitements\$:","",$ce_repertoire);
$libs = $racinePhys."/libs";


/**
 * gcsv_extrait.php V1.2.1
 *
 *
 * Script qui sert à supprimer ou à extraire de logs complets fournis par un service Web les lignes et colonnes
 * jugées (in)utiles (exemple les récupérations d'icônes, de javascripts, ...; les colonnes
 * de session ou de referer ou ... les données concernant un site/ un utilisateur, ...)
 *
 * Usage : gcsv_extrait.php [(+|-)test | -v | -rapport | -status)]
 * 			[-xtrt PHP_filename] [-max int] [(-tmax|-d) int] [-sep char] [-glu char]
              [( (+|-)hd[1] | -format sormat_str ]
              [-colt colnamenum {+|-}ctriteria] [-colt ....]]
              [-{colca|colcip|colda|coldip} colnamenum [colnamenum ...]]
              [(+|-)col colnamenum [colnamenum ...]] [(+|-)allcol)] [+colf colnamenum:value [colnamenum:value ...]]
              [-colurl colnamenum +colu(scheme|host|hostrv|port|path|query|fragment) [+colu(scheme|...)...]]
              [-res result_filename] [-rej reject_filename]
               {src source_filenames| source_filename}
 * where ctriteria =
        	std|img|js
        	f|fh filename [filename [...]]
            s string [string[...]]
            eq|ne|ge|gt|le|lt rationnal_value
            be|oo interval (e.g. relationnal,relationnal)
            teq|tne|tge|tgt|tle|tlt datetime_value (as YYYY-MM-DD.hh.mm.ss)
            tbe|too datetime_interval (e.g. datetime,datetime)

 *  Une ligne est retenue si elle contient une des valeurs recherchées et ne contient pas de valeur exclue
 *  dans la colonne correspondant au critère .
 *  Les colonnes sélectionnées pour le résultat peuvent être réordonnées et encodé.
 *  Une colonne contenant une URL peut être éclatée en plusieurs correspondants aux éléments choisi
 *  formant l'URL d'origine (e.g. host, path, query V. parse_url :: http://www.php.net/manual/fr/function.parse-url.php
 *
 *  Le CSV peut être un fichier de logs dont les valeurs d'une colonne peuvent contenir le caractère
 *  servant à séparer par ailleurs les colonnes souvent l'espace).
 *  Ces colonnes à valeur complexes sont reconnues par le parenthésage de leurs valeurs.
 *  Par défaut, il y deux parenthèseurs (utilisé classiquement dans les logs) :
 *  la double quote("") ou le couple crochet ouvrant/fermant ([]).
 *  Pour changer les parenthèseurs, utiliser -par fichier_parentheses. Le contenu du fichier écrase les
 *  parenthéseurs par défaut.
 *  Au niveau des lignes retenues/exclues , cinq moyens permettent de les définir :
 *   - des valeurs fournies directement au niveau de la commande -s chaine1 chaine2...
 *   - des valeurs standards basée sur des logs classiques (images et scripts)
 *   - des fichiers de valeurs (ASCII contenant une valeur par ligne)
 *   - des fichiers de host EzProxy (dont on ne retient que les host domain hj ou dj)
 *   - des valeurs numériques testées sur des colonnes qui leur sont spécifiques.
 *   Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
 *   Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
 *   Si aucun fichier n'est indiqué pour les lignes non intéressante (-rej), elles sont oubliées.
 *    	Moins de 3s avec seulement la chaîne invalidante pour 17970 lignes retenues.
 *  ... V1.2 (11/07/2013)
 *  	. adjonction de l'insertion de colonnes à valeur fixe
 *  ... V1.1 (15/05/2013)
 *      . adjonction de la possibilité de définir des valeurs commençant par le signe + ou -
 *  ... V1.0 (6/05/2013)
 *  	.amélioration des performences en cas de test sur colonne
 *      .définition systématique des colonnes testées sauf pour l'ensemble de la ligne pour les
 *       paramètres s f et fh (qui peuvent être employés sans précision de colonne)
 *
 *  ... V0.3 (2013.04.09)
 *      . adjonction du paramètre format
 *   ... V0.2 (2013.03.01) :
 *     - test numériques  sur des colonnes différentes des tests textuels
 *      avec intervalle de valeurs (utiles pour les code de retour HTTP, les taille en octets des échanges)
 *     - usage possible d'expressions régulières comme chaîne d'exclusion ou de sélection des lignes
 *       soit en ligne de commande (paramètre s) soit dans les fichiers de valeurs (paramètre f)
 *     - decodage symetrique du codage. Permet de retrouver les données originales.
 *     - accélération du traitement des colonnes codées (anonymisation).
 *     - traitement avec retenue ou suppression de la ligne d'entête qui precise le contenu des colonnes.
 *   gcsv_extrait.php V0.0
 *     - introduction des tests de date/heure
 *     - tous les tests se font sur une colonne précise (et non plus liste de colonnes testées)
 *     - réelle introduction des intervalles
 *     - correction de l agestion de la ligne d'entête.
 *  ... V0.1
 *     - les critères de sélection sont cumulatifs sur des colonnes différentes :
 *       ... -colt C1 +critere1 -colt C2 +critere2 -colt C -critere3...
 *       pour être retenue, une ligne doit vérifier les deux critères critere1 dans et C1 et critere2 dans
 *       C2 et mais pas critere3 dans C (où C peut être C1 ou C2). Mais plusieurs valeurs sur une même colonne
 *       sont prises comme des alternatives.
 *       ... -colt C1 +critere1 +critere2
 *   ... V0.0
 *  	- amélioration d'analyse des chaînes à exclure ou retenir qui contiennent des caractères non alphanum.
 *  	- utilisation de l'entrée standard pour un usage en pipe possible.
 *  ... extrait_ezp_logs.php V0.1 (2013.02.06)  :
 *    performance : 55s pour traiter 46300 lignes avec  4336 valeurs validantes et 1 invalidante soit en moyenne
 *    	plus de 1160 tests par ligne.
 *      229s pour traiter 102340 lignes de log avec sélection des lignes ayant un host
 *      parmi 2858 sauf celle contenant en plus la référence à un host exclu puis
 *      explosion d'url, et encodage de login pour les 645 vérifiant les critères.
 *
 */

$parametres_possibles = array('-aide','-help','-h',"-test",'+test','-v','-rapport','-status',
							'-xtrt','-max','-tmax','-d','-sep','-par','-glu',
							'+hd','-hd','+hd1','-hd1','-format',
							'-allcol','+allcol','-col','+col'
							,'-colca','-colcip','-colda','-coldip','-colurl',
							'+coluscheme','+coluhost','+coluhostrv','+coluport','+colupath',
							'+coluquery','+colufragment','+colf',
							'-strout',
							'-colt','-cold',
							'-s','-std','-img','-js','-fh','-f',
							'+s','+std','+img','+js','+fh','+f',
							'-eq','-ne','-oo','-be','-ge','-gt','-le','-lt',
							'+eq','+ne','+oo','+be','+ge','+gt','+le','+lt',
							'-teq','-tne','-too','-tbe','-tge','-tgt','-tle','-tlt',
							'+teq','+tne','+too','+tbe','+tge','+tgt','+tle','+tlt',
							'-strin',
							'-src','-res','-rej'
							);
$operateurs = array('s','std','img','js','fh','f','eq','ne','oo','be','ge','gt','le','lt');
$operateurs_num=array('eq','ne','oo','be','ge','gt','le','lt');
$operateurs_date = array('teq','tne','too','tbe','tge','tgt','tle','tlt');



function montre_usage ($mess=''){
	global $test;
	echo_usage ();
	if ($mess) {
		echo $mess."\n";
		return(false);
	}
	detail_usage();
}

require_once "$libs/parentheses.lib.php";

set_parentheses(array(array('"','"'),array('[',']')));



$listes_formats = array();

function get_all_formats (){
	global $listes_formats;
	return($listes_formats);
}
function get_format ($type){
	global $listes_formats;
	if ($type)
		if (isset ($listes_formats[$type])) return ($listes_formats[$type]);
		else return (false);
	return (false);
}
function add_to_format ($type,$val){
	global $listes_formats;
	if (!is_array($val)) $v =array($val);
	else $v = $val;
	if (!isset($listes_formats[$type])) $listes_formats[$type]=array();
	$listes_formats[$type]= array_merge($listes_formats[$type],$v);
	return (true);
}
function set_format($type,$l){
	global $listes_formats;
	if (!is_array($l)) $v =array($l);
	else $v = $l;
	$listes_formats[$type]=$v;
	return (true);
}
set_format('img',array('/\\.gif\\b/','/\\.jpg\\b/','/\\.png\\b/','/\\.ico\\b/'));
set_format('js',array('/\\.js\\b/','/\\.css\\b/'));
set_format('std',array('/\\.js\\b/','/\\.css\\b/','/\\.gif\\b/','/\\.jpg\\b/','/\\.png\\b/','/\\.ico\\b/'));



/**
 * Traitement du contenu d'un fichier descriptif des chaines à retenir/à rejeter.
 * Les chaînes sont mémorisées dans un tableau. La chaîne $marqueur_lignes indique de ne retenir
 * que les lignes contenant cette RegExp et de retirer la chaîne testée pour obtenir la chaîne finale
 * @param $fic string = nom du fichiers
 * @param $marqueur_lignes string = RegExp validant et à suppride d'une ligne pour la retenir dans les
 * 				cas de fichiers de configuration d'ezproxy (ou autres...)
 * @return array = liste de valeur extraites
 * @uses $echo_test string =  message en trace
 * @uses $echo_err string =  message en trace
 * @uses $err  boolean = présence d'erreur (fichier inconnu)
 */
function traite_fichier ($fic, $marqueur_lignes='') {
	global $echo_test,$echo_err,$err;
	$res = array();
	if (! ($pfic = fopen ($fic,'r'))) {
			$echo_err.=traite_fichier_mess ('FINV', $fic);
			$err=true;
			return ($res);
	}
	while (($ligne = fgets($pfic))!==false) {
			if ($marqueur_lignes) {
				$le_host = preg_replace($marqueur_lignes, "",$ligne);
				if ($le_host==$ligne) continue;
				$ligne=$le_host;
			}
			$ligne=trim($ligne);
			if ($ligne)
				$res[]=$ligne;
	}
	fclose($pfic);
	$nb = count($res);
	$echo_test.=traite_fichier_mess ('TEST', array($fic,$nb));
	return($res);
}

require_once "$libs/codage_bijectif.class.php";

/* -----------------------------------
 * Gestion internes des critères
 * -----------------------------------
 * On gère dans un tableau association associant à un n° ou un nom  de colonne un critère
 * - pour les critères sur chaîne, un critère est une ou plusieurs chaînes cherchable en distingant
 *   les expressions régulières des chaînes brutes pour raison d'efficacité.
 * - pour les critères "numériques", chacun est formé d'un opérateur et d'une ou deux valeurs
 * bornes d'un intervalle
 */

/**
 * ajoute_val_tab_cri
 * ajoute un critère à la colonne $col dans la table qui lui correspond
 */
function ajoute_val_tab_cri (&$tab_cri,$col,$critere){
	global $col_testees,$a_exploser;
	$col_testees[$col]= true;
	if ($col!='*') $a_exploser=true;
	if (!isset($tab_cri[$col]) || !is_array($tab_cri[$col])) $tab_cri[$col]=array();
	$tab_cri[$col][]=$critere;
}

/**
 *
 * Pour toutes les tables indexée par un identifiant de colonne, transforme un nom identifiant
 *  en un numéro de colonne
 *  @param array $tab_indexee : tableau dont on convertit les index
 *  @param array $tab_conv : tableau les index sont ceux de l'autre et les valeurs doivent remplacer
 *      ces index.
 *  @return string = message d erreur.
 */

function converti_col_index (&$tab_indexee,$tab_conv) {
	$err = '';
	if (!$tab_indexee) return($err);
	$res = array();
	foreach ($tab_indexee as $index=>$valeur){
		if (isset($tab_conv[$index])) $res[$tab_conv[$index]]=$valeur;
		elseif (strpos($index, 'URL:')=== 0 || strpos($index, 'FIX:')=== 0 ||
				$index=='*') $res[$index]=$valeur;
		else $err .= converti_col_val_mess('COLINV', $index);
	}
	if (!$err) $tab_indexee=$res;
	return ($err);
}
/**
 * converti_col_val
 * Pour tous les identifiants nominatifs de colonne, transforme le nom en numéro de colonne.
 * Si l'argument est un tableau, traite toutes les valeurs du tableau
 *  @param array $col_p_nom : valeur ou tableau de valeurs a convertir
 *  @param array $tab_conv : tableau les index sont ceux de l'autre et les valeurs doivent remplacer
 *      ces index.
 *  @return string = message d erreur.
 */
function converti_col_val (&$col_p_nom,$tab_conv) {
	$err = '';
	if (!$col_p_nom) return($err);
	$tabu = (is_array($col_p_nom))?$col_p_nom:array($col_p_nom);
	$res = array();
	foreach ($tabu as $index=>$valeur){
		if (isset($tab_conv[$valeur])) $res[$index]=$tab_conv[$valeur];
		elseif (strpos($valeur, "URL:")===0 || strpos($valeur, "FIX:")===0 || $valeur=='*') $res[$index]=$valeur;
		else $err .= converti_col_val_mess('COLINV', $valeur);
	}
	if (!$err) {
		$col_p_nom= (is_array($col_p_nom))? $res:$res[0];
	}
	return ($err);
}
/*
 * ===============================================================================
 * Critères sur chaîne de caractère.
 * Séparation des critères bruts et utilisant une expression régulière
 * ===============================================================================
 */

/**
 *
 * Ajout de valeurs de critères chaîne dans le tableau des valeur brute ou celui des RegExp.
 * @param array $tab_valstr = liste cible sur laqsuelle se fait l'ajout des valeur brutes
 * @param array $tab_valreg = liste cible sur laqsuelle se fait l'ajout des RegExp
 * @param mixed $col = n° ou nom de la colonne.
 * @param array $vals = liste des valeurs à ajouter
 */
function ajoute_val_str (&$tab_valstr,$col,$vals){
	if (!is_array($vals)) $vals = array($vals);
	foreach ($vals as $une_val) {
		ajoute_val_tab_cri($tab_valstr,$col,$une_val);
	}
  	return (true);
}

function est_val_reguliere ($v){
	$pc = substr($v,0,1);
	if ($pc!=substr($v,-1)) return(false);
	return (!preg_match('/\\w/', $pc));
}
/*
 * ===============================================================================
 * Valeurs numériques et dates mémorisation / normalisation / test
 * ===============================================================================
 */

/**
 *
 * Ajout d'une valeur numérique de maintient ou de rejet de ligne après l'avoir normalisée.
 * La valeur val est au format préconisé pour la ligne de commande.
 * @param array $tab_val = liste cible sur laqsuelle se fait l'ajout
 * @param string $op = operateur numerique
 * @param string $col = numéro ou nom de la colonne
 * @param array $val = valeur(s) servants à définir le critère
 * @param string $format = format de date
 * @return string=message d'erreur
 */
function ajoute_val_num (&$tab_val,$op,$col,$val,$format=""){
	global $operateurs_num,$echo_err,$err;
	if (strlen($op)>2) {$type = substr($op,0,1); $optest=substr($op,1);}
	else {$type = 'n'; $optest=$op;}
	if (!in_array($optest, $operateurs_num)){
		return( ajoute_val_num_mess('NOOP',$op));
	}
	$tv = explode (',',$val);
	if ($optest=='be'|| $optest=='oo') {
		if (count($tv)!=2) {
				return (ajoute_val_num_mess('NOINTV', array($val,$op)));
		}
		$vmin = normalise_critere_num($type,$tv[0]);
		if ($vmin===false) return(ajoute_val_num_mess('INVVAL',$tv[0]));
		$vmax = normalise_critere_num($type,$tv[1]);
		if ($vmax===false ) return(ajoute_val_num_mess('INVVAL',$tv[1]));
		if (!valide_bornes_num ($type,$vmin,$vmax)) {
			return(ajoute_val_num_mess('INTVINV',$val));
		}
		$res = array('op'=>$optest,'type'=>$type,'min'=>$vmin,'max'=>$vmax);
	} else {
		if (count($tv)>1) {
				return (ajoute_val_num_mess('1VAL',$op));
		}
		$v = normalise_critere_num($type,$val);
		if ( $v===FALSE) return(ajoute_val_num_mess('INVVAL',$val));
		$res = array('op'=>$optest,'type'=>$type,'val'=>$v);
	}
	if ($format) $res['format']=$format;
	ajoute_val_tab_cri($tab_val,$col,$res);
  	return ('');
}

/**
 * compare_num
 * Mise en oeuvre des critères mémorisés. Rend vrai si la valeur trouvée dans une ligne $val_testee vérifie
 * le critère $rech de la colonne.
 * Pour les dates on normalise en reprojetant sur les
 *
 * @param array $rech : opérateur/type et valeur(s) de définition du test.
 * @param array $val_testee : valeurs à tester. Il suffit d'une valeur valide pour que le test soit positif
 * @return boolean : resultat du test
 */

function compare_num ($rech, $val_testee){
	$op = $rech['op']; $type = $rech['type']; $min=$max=$val="";
	if (isset($rech['val'])){
		$val = $rech['val'];
	}else {
		$min=$rech['min']; $max=$rech['max'];
	}
	if ($type=='t') $vt = normalise_valeur_num($type, $val_testee ,$rech['format']);
	else $vt = normalise_valeur_num($type, $val_testee);
	if ($val) $rv = compare_arith_num($type, $val, $vt);
	else {
		$rmi = compare_arith_num($type, $min, $vt);
		$rma = compare_arith_num($type, $max, $vt);
	}
	$rf = false;
	switch($op) {
		case 'eq' : $rf=  ($rv==0);break;
		case 'ne' : $rf=($rv!=0);break;
		case 'oo' : $rf=(($rmi<0)||($rma>0));break;
		case 'be' : $rf=(($rmi>=0)&&($rma<=0));break;
		case 'ge' : $rf=($rv>=0);break;
		case 'gt' : $rf=($rv>0);break;
		case 'le' : $rf=($rv<=0);break;
		case 'lt' : $rf=($rv<0);break;
			}
	return($rf);
}



$st_mes_dates = array('Y'=>array('1900','2099'),'m'=>array('01','12'),'d'=>array('01','31'),
				'H'=>array('00','23'),'i'=>array('00','59'),'s'=>array('00','59'));


/**
 * normalise_critere_num
 * Fait d'une valeur textuelle en argument, une valeur numérique à tester
 * @param string $t = type de valeur à traiter
 * @param string $v = valeur à traiter
 * @return mixed = valeur normalisée
 */
function normalise_critere_num($t,$v){
	global $echo_err,$err,$st_mes_dates;
	if ($t != 't'){ //type==n
		if (preg_match("/^(\\+|\\-)?[0-9]+(\\.\\d+)?\$/",$v)) return ($v*1.);
		$err=true;
		$echo_err .= normalise_critere_num_mess('VINV',$v);
		return (false);
	}
	// Cas des dates [[[YYYY-[mm-[dd.]]][hh[:MM[:ss]]]
	$p = strpos($v, '.');
	if ($p===false){
		if (strpos($v,':')!==FALSE) {
			$t_dt = false;
			$t_dh = ($v!=':')?explode(':', $v):false;
		} else {
			$t_dt = explode('-',$v);
			$t_dh=false;
		}
	} else {
		$t_dt = ($p>0) ? explode('-', substr($v,0,$p)) : false;
		$t_dh = (strlen($v)>$p+1) ? explode(':',substr($v, $p+1)): false;
	}
	if (! $t_dt && !$t_dh) {
				$err=true;
				$echo_err .= normalise_critere_num_mess('DTINV',$v);
				return (false);
	}
	$masque = array('Y'=>'','m'=>'','d'=>'','H'=>'','i'=>'','s'=>'');

	if ($t_dt) {
		if (count($t_dt)>2) {
			$vt = array_shift($t_dt);
			if ($vt) {
				if (!preg_match("/(19|20)[0-9]{2}/",$vt)) {
					$err=true;
					$echo_err .= normalise_critere_num_mess ('ANNEEINV',array($v,$vt));
					return (false);
				}
				$masque['Y']=$vt;
			}
		}
		if (count($t_dt)>1) {
			$vt = array_shift($t_dt);
			if ($vt) {
				if (strlen($vt)<2) $vt='0'.$vt;
				if (!preg_match("/[0-1][0-9]/",$vt)) {
					$err=true;
					$echo_err .= normalise_critere_num_mess ('MOISINV',array($v,$vt));
					return (false);
				}
				$masque['m']=$vt;
			}
		}
		$vt = array_shift($t_dt);
		if ($vt){
			if (strlen($vt)<2) $vt='0'.$vt;
			if (!preg_match("/[0-3][0-9]/",$vt)) {
				$err=true;
				$echo_err .= normalise_critere_num_mess ('JOURINV',array($v,$vt));
				return (false);
			}
			$masque['d']=$vt;
		}
	}
	if (!$t_dh) return ($masque);
	$vt = array_shift($t_dh);
	if (!$vt) $vt = '00';
	elseif (strlen($vt)<2) $vt='0'.$vt;
	if (!preg_match("/[0-2][0-9]/",$vt)) {
				$err=true;
				$echo_err .= normalise_critere_num_mess ('HEURINV',array($v,$vt));
				return (false);
	}
	$masque['H']=$vt;
	if (!$t_dh) return ($masque);
	$vt = array_shift($t_dh);
	if (!$vt) $vt = '00';
	elseif (strlen($vt)<2) $vt='0'.$vt;
	if (!preg_match("/[0-5][0-9]/",$vt)) {
				$err=true;
				$echo_err .= normalise_critere_num_mess ('MININV',array($v,$vt));
				return (false);
	}
	$masque['i']=$vt;
	if (!$t_dh) return ($masque);
	$vt = array_shift($t_dh);
	if (!$vt) $vt = '00';
	elseif (strlen($vt)<2) $vt='0'.$vt;
	if (!preg_match("/[0-5][0-9]/",$vt)) {
			$err=true;
			$echo_err .= normalise_critere_num_mess ('SECINV',array($v,$vt));
			return (false);
	}
	$masque['s']=$vt*1;
	return ($masque);
}


/**
 * normalise_valeur_num
 * Fait d'une valeur lue en log, une valeur numérique à tester
 * @param string $t = type de valeur à traiter
 * @param string $v = valeur à traiter
 * @param string $f = format de date à traiter
 * @return mixed = valeur normalisée
 */
//if (!date_default_timezone_set('Europe/London')) die ("Erreur emploi date_default_timezone_set");
function normalise_valeur_num($t,$v,$f=""){
	global $echo_err,$err,$st_mes_dates;
	if ($t != 't'){ //type==n
		if (preg_match("/^(\\+|\\-)?[0-9\\.]+\$/",$v)) return ($v*1.);
		return (0);
	}
	if (!$f) {
		$v = preg_replace("/^\\W+/","",$v);
		$v = preg_replace("/\\W+\$/","",$v);
		$res = array();
		$v = preg_replace ("/\\s*(\\+|-)[0-9:]+/","",$v);
		$tab_date = strptime ($v,"%d/%h/%Y:%H:%M:%S");
	} else {
		$tab_date = strptime ($v,$f);
	}
	$res['Y'] = $tab_date['tm_year']+1900; $res['Y'] .="";
	$res ['m']= $tab_date['tm_mon']+1;  $res ['m']= ($res ['m']<10) ? "0".$res ['m'] : $res ['m']."";
	$res ['d']= $tab_date['tm_mday']; $res ['d']= ($res ['d']<10) ? "0".$res ['d'] : $res ['d']."";
	$res ['H']= $tab_date['tm_hour']; $res ['H']= ($res ['H']<10) ? "0".$res ['H'] : $res ['H']."";
	$res ['i']= $tab_date['tm_min']; $res ['i']= ($res ['i']<10) ? "0".$res ['i'] : $res ['i']."";
	$res ['s']= $tab_date['tm_sec']; $res ['s']= ($res ['s']<10) ? "0".$res ['s'] : $res ['s']."";

	return($res);
}

/**
 * valide_bornes_num
 * Sert à corriger deux bornes permettant de définir un intervalle et vérifie la compatibilité entre la
 * valeur théorique minimum et celle maximum
 *
 * */
function valide_bornes_num($type, &$vmin,&$vmax){
	global $st_mes_dates;
	if ($type == 't'){
		$debut_def = -1;
        foreach ($st_mes_dates as $key=>$defauts){
        	$elem1 = $vmin[$key]; $elem2 = $vmax[$key];
        	if (!$elem1 && !$elem2) {
        		if ($debut_def == 0){
	        		$vmin[$key]=$defauts[0];
	        		$vmax[$key]=$defauts[1];
	        		return(TRUE);
        		}
        		if ($debut_def>0) return(true);
        		continue;
        	}
        	if ($elem1) {
        		if ($elem2){
        			if ($elem2 <$elem1) return (false);
        			if ($elem2>$elem1) return(true);
        			else $debut_def=0;
        			continue;
        		}
        		if (!$debut_def) {
        			$vmax[$key] = $defauts[1];
        			if ($elem1<$defauts[1]){return(true);}
        		} else {
        			$vmax[$key] = $defauts[0];
        		}
        		continue;
        	} else {
        		$vmin[$key]=$defauts[0];
				if ($elem2>$defauts[0]) return (true);
        	}
        }
        return(true);
	} else {
		return ($vmin<$vmax);
	}
}

/**
 * Sert à comparer deux valeurs normalisées de type t, et rendre 1 si v1 < v2 , -1 v2 < v1, 0 en cas d'égalité.
 * Elle se charge aussi de corriger au besoin l'une des valeurs pour établir des valeurs statiques
 * accélérant les processus ultérieurs de comparaison..
 * @param string $type : type de valeur code de l'ensemble ordonné discret du type
 * 		n pour rationnels t pour date/heure.
 * @param mixed $v1 : première valeur à comparer
 * @param mixed $v2 : seconde valeur à comparer
 * @return int : resultat de la comparaison
 *
 */

function compare_arith_num ($type,$v1,$v2){
	if ($type == 't'){
		global  $st_mes_dates;
		$debut_comp = false;
        foreach ($st_mes_dates as $key=>$minmax){
        	if (!$v1[$key] || !$v2[$key]) {
        		if ($debut_comp) return(0);
        		else continue;
        	}
        	$debut_comp=true;
        	$r = strcmp($v1[$key], $v2[$key]);
        	if ($r) return (-$r);
			else continue;
        }
        return (0);

	} else { // cas numérique
		if ($v1 < $v2) return (1);
		elseif ($v1>$v2) return(-1);
		else return(0);
	}

}

/**
 *
 * Fonction qui teste la validité de la valeur d'une colonne par rapport aux critères posés
 * @param unknown_type $vtestee
 * @param unknown_type $nocol
 *
 */
function valeur_col_valide ($vtestee,$nocol){
	global $valide_ligne,$filtre_ligne;
	global $filtres,$filtres_RE,$filtres_num;
	global $extrait,$extrait_RE,$extrait_num;
	global $strin;
	if (!$vtestee && $strin) {return(valeur_col_valide_mess ('SSVAL',$nocol));}
	if ($filtre_ligne && $nocol!='*') { // il y a des exclusions demandées.
		if (isset($filtres[$nocol]) ){
			$vals = $filtres[$nocol];
			foreach ($vals as $une_val) {
				if (strpos($vtestee,$une_val)!==false){
					return(valeur_col_valide_mess ('VALEXC',array($nocol,$une_val)));
				}
			}
		}
		if (isset($filtres_RE[$nocol]) ){
			$vals = $filtres_RE[$nocol];
			foreach ($vals as $une_val) {
				if (preg_match($une_val,$vtestee)!==false){
					return(valeur_col_valide_mess ('VALEXC',array($nocol,$une_val)));
				}
			}
		}
		if (isset($filtres_num[$nocol]) ){
			$vals = $filtres_num[$nocol];
			foreach ($vals as $une_val) {
				if (compare_num($une_val, $vtestee)) {
					return(valeur_col_valide_mess ('VALEXC',array($nocol,$une_val)));
				}
			}
		}
	}
	if (!$valide_ligne) {return(''); }// il n'y a pas de sélection demandée : c'est bon
	$ok = true;
	if (isset($extrait[$nocol])) {
		$vals=$extrait[$nocol];
		foreach ($vals as $une_val) {
			if (strpos($vtestee,$une_val)!==false){
				return('');
			}
		}
		$ok=false;
	}
	if (isset($extrait_RE[$nocol])){
		$crit_col=true;
		$vals=$extrait_RE[$nocol];
		foreach ($vals as $une_val) {
			if (preg_match($une_val,$vtestee)!==false){
				return('');
			}
		}
		$ok=false;
	}
	if (isset($extrait_num[$nocol])){
		$crit_col=true;
		$vals=$extrait_num[$nocol];
		foreach ($vals as $une_val) {
			if (compare_num($une_val, $vtestee))
				return ('');
		}
		$ok=false;
	}
	if (!$ok) return (valeur_col_valide_mess ('NOVAL',array($nocol,$vtestee)));
	return ('');
}

/**
 * Calcul du host renvers� pour le traitement des URL
 */
function ana_url ($str){
	$v_url = parse_url($str);
	if (isset($v_url['host'])){
		$v_url['hostrv'] = implode
								('.'
								,array_reverse
									(explode ('.',$v_url['host']))
								);
	}
	return ($v_url);
}

/**
 * Calcul de la valeur à écrire en ne conservant que les colonnes requises et en encodant les colonnes
 * désignées
 * @param $tab array = colonnes de la ligne d'origine à écrire
 * @param $col_plus array = liste des colonnes à retenir
 * @param $col_moins array = liste des colonnes à exclure du résultat
 * @param $col_codes array = table associant un nom/num de col au mode d'encodage de celle-ci
 * @param $glu char = séparateur de colonne à utiliser dans la ligne de CSV produite.
 * @param $col_url int = n° (>0) de la colonne la colonne contenant une url à éclater
 * @param $col_fixe array = table associant un nom/num de col a la valeur constante a y mettre
 * @param $est_entete boolean = precise qu'il s'agit d'une entête ou non
 * @return mixed = ligne résultat formatée ou tableau des colonnes en erreur si on est en mode strict.
 */
function ecrit_res_acode (&$tab,$col_plus,$col_moins,$col_codes,$glu=" ",$col_url="",$col_fixe=array(),$est_entete=false){
	global $strout,$allcol;

	$err_tabs = $vtab = array();
	$taille_tab_originale = count($tab);
	// Calcul des colonnes liée à l'analyse d'une URL
	if ($col_url) {
		if ($strout && !isset($tab[$col_url-1])) {$err_tabs[]=$col_url;}
		else {
			$v_url = array();
			$t_tot = explode(' ',$tab[$col_url-1]);
			foreach ($t_tot as $un_morceau){
				if (strpos($un_morceau, '://')===false) continue;
				$v_url=ana_url($un_morceau);
				break;
			}
			foreach ($v_url as $membre=>$v) {
				$tab["URL:$membre"]=$v;
			}
		}
	}
	if ($col_fixe){
		foreach ($col_fixe as $nom=>$val){
			$tab['FIX:'.$nom]=($est_entete)?$nom:$val;
		}
	}
	// Encodage / Decodage des colonnes :
	foreach ($col_codes as $num_col=>$mode_code){
		if (!isset($tab[$num_col-1])){
			if ($strout) {$err_tabs[]=$num_col;}
			else $tab[$num_col-1]  ="";
		} else $tab[$num_col-1] = codage_bijectif::encode_valeur($tab[$num_col-1],$col_codes[$num_col]);
	}


	if (! $col_plus) {
		for($i=0;$i<count($tab);$i++){
			if (in_array($i+1,$col_moins)) {continue;}
			$vtab[]=$tab[$i];
		}
	} else {
		foreach ($col_plus as $une_col){
			if (strpos($une_col, "URL:")===0) {
				$url_elem = substr($une_col,4);
				if ($est_entete) $v=$tab[$col_url-1].".$url_elem";
				elseif (isset($v_url[$url_elem])) $v = $v_url[$url_elem];
				else $v = "-$une_col";
				$vtab[]=$v;
				continue;
			}
			elseif (strpos($une_col, "FIX:")===0) {
				$vtab[]=$tab[$une_col];
				$tab[$une_col]=NULL;
				continue;
			}
			elseif ($une_col!='*') {
				if ($une_col > $taille_tab_originale) {
				  if ($strout) {
				  	$err_tabs[]=$une_col;
				  	continue;
				  }	 else $vtab[]= "";
				} else {
					$vtab[]= $tab[$une_col-1];
				}
				$tab[$une_col-1]=NULL;
			}
			else {
				for($i=0;$i<$taille_tab_originale;$i++){
					if (!in_array($i+1, $col_plus)) $vtab[]=$tab[$i];
				}
			}
		}
	}
	if ($err_tabs) return($err_tabs);
	$tab=$vtab;
	return (implode($glu,$vtab)."\n");
}

require_once "$libs/logs.lib.php";

/*
 * ================================================================================
 * Bloc de gestion des sources entrée standard ou fichier compressé.
 * ================================================================================
*/
/**
 * @var resource $source_u = surtout utile pour les fichier
 * @var char $mode_u='s' si on utilise l'entrée standard, vide sinon.
 *
 */
$source_u=''; $mode_u = '';
/**
 *
 * Permet d'ouvrir la source dans le cas d'un fichier. D'établir le mode standard dans le cas de l'entrée
 * standard.
 * @param string $unite = 'stdin' ou chemin du fichier
 * @return resource
 */
function ouvre_source ($unite){
	global $source_u,$mode_u;
	if ($source_u || $mode_u){
		if (!ferme_source()) return (false);
	}
	if ($unite=='stdin') {
		$mode_u='s';
		$source_u = STDIN;
	}else {
		$source_u =gzopen($unite, 'r'); $mode_u='';
	}
	return ($source_u);
}
/**
 *
 * Rend une ligne lue à la source ...
 * @return string = ligne lue.
 */
function lit_source (){
	global $source_u,$mode_u;
	if ($mode_u=='s') return (fgets(STDIN));
	else return gzgets($source_u,1048576);
}
/**
 *
 * Find'usage de la source...
 */
function ferme_source(){
	global $source_u,$mode_u;
	if ($mode_u!='s') {$r = gzclose($source_u); }
	else {$r=true;}
	$source_u='';$mode_u=='';
	return ($r);
}

/**
 * Traitement de la ligne de heaedr qui consiste à établir un lien entre nom de colonne et sa position.
 * Le tableau $conv_colnum est construit lors de l'analyse le commande avec toutes les colonnes désignées.
 * @param string $ligne : chaîne (ligne d'entête ) contenant le format des lignes
 * @return boolean : vrai si OK. sinon étblit la chaîne globale $echo_err.
 */
function  traite_ligne_header ($ligne){
	global $fres,$frej,$glu,$sep, $mod_col,$ligne_header,$echo_err,$cptecrites;
	global $filtres,$filtres_RE,$filtres_num,$extrait,$extrait_RE,$extrait_num,$col_testees;
	global $colurl,$col_code,$col_exclues,$col_ret,$conv_colnum,$col_fixe,$ecrit_par_col;
	global $cptlues;
	  /*
	   * Si désignation symbolique, conversion des noms de colonnes en n°
	   */
	if ($ligne_header == 'i') return (true);
	$tab_ligne = explose_ligne($ligne, $sep);
	for ($i=1;$i<=count($tab_ligne);$i++){
		$ipos=$i-1; $nom = $tab_ligne[$ipos];
		if (!isset($conv_colnum[$nom])) continue;
		if ($conv_colnum[$nom]>0) {
			$echo_err = converti_col_val_mess('DBLDEF', $nom);
		} else
			$conv_colnum[$nom]=$i;
	}
	foreach ($conv_colnum as $un_nom=>$nocol){
		if ($nocol<=0) {
			$echo_err.=converti_col_val_mess('COLINV', $un_nom);
		}
	}
	if (!$echo_err){
		$echo_err .= converti_col_index($filtres,$conv_colnum);
		$echo_err .= converti_col_index($extrait,$conv_colnum);
		$echo_err .= converti_col_index($filtres_RE,$conv_colnum);
		$echo_err .= converti_col_index($extrait_RE,$conv_colnum);
		$echo_err .= converti_col_index($filtres_num,$conv_colnum);
		$echo_err .= converti_col_index($extrait_num,$conv_colnum);
		$echo_err .= converti_col_index($col_code,$conv_colnum);
		$echo_err .= converti_col_index($col_testees,$conv_colnum);

		$echo_err .= converti_col_val ($col_exclues,$conv_colnum);
		$echo_err .= converti_col_val ($col_ret,$conv_colnum);
		$echo_err .= converti_col_val ($colurl,$conv_colnum);
	}
	if ($echo_err) {
		$echo_err .= converti_col_val_mess('LIGOR',$ligne);
			return(false);
	}

	/*
	 *  S'il s'agit d'une entete, écriture au besoin de la ligne en resultat
	 *  (sans la traiter comme les suivantes)
	 */

	if ($ligne_header=='+' || $ligne_header=='+1') {
		if ($ecrit_par_col) {
				$ligne = ecrit_res_acode ($tab_ligne,$col_ret,$col_exclues,array(),$glu,$colurl,$col_fixe,true);
				if (is_array($ligne)) {$echo_err = "Colonne en erreur dans l'entete" ; return (false);}
		}
		if(function_exists("a_ecriture_ligne") && !($ligne = a_ecriture_ligne($ligne,$tab_ligne)))
			{$echo_err = "Colonne en erreur dans l'entete" ; return (false);}
		if ($fres) gzwrite($fres, $ligne);
		else  print $ligne;
		$cptecrites++;
	}
	/*
	 * Copie systematique de cette ligne d'entete en fichier de rejet
	 */
	if ($frej) gzwrite($frej,$ligne);
	/*
	 * Ajustement de $ligne_header pour la suite du traitement :
	 * Non réplication et présence ou non de cette ligne en début des fichiers suivants à analyser.
	 */
	if ($ligne_header=='+1'||$ligne_header=='-1') $ligne_header=false;
	else $ligne_header='i';
	return(true);

}


/*
 * ==========================================================================
 * Initialisation des paramètres du traitement
 * ==========================================================================
 */



// mémorisation par colonne
//  - des éléments discriminants et sélecteur de lignes
$filtres = array();$extrait = array();
$filtres_RE = array();$extrait_RE = array();
$filtres_num = array();$extrait_num = array();
$col_testees = array();


$allcol=$strin=$strout=false;

// - des éléments choisis ou exclus du résultat
$col_exclues=array();$col_ret=array();
// - des élément à traiter avant de les produire
$colurl='';$col_code=array(); $col_fixe=array();
// mode de désignation des colonnes concernées
//  et table de conversion créé à partir de l'entête conservée ou non
$mod_col = 'num'; $conv_colnum = array(); $ligne_header=''; $format='';

// Limites de traitement : nbre de ligne, duree en secondes
$maxval = -1; $tmax=-1;

// pour le CSV lu, séparateur et parenthèseurs (en fichier) pour son analyse
$sep="";$fic_par="";
// pour le CSV produit, séparateur à utiliser.
$glu="";


// niveau de verbiage et fiche explicative :
$test=$testp=false; $emploi = false;
// ... et accumulation des messages de traitement :
$echo_test = $echo_err =""; $err = false;

// Fichiers utilisés en résultat positif/discriminé
$fic_res=$fic_rej=$fic_par="";
// ... et fichiers source nécessaire
$sources=array();

/*
 * ==========================================================================
 * Initialisation de l'analyse de la ligne de commande elle-même
 * ==========================================================================
 */

// suppression du propre nom :
$moi = array_shift($argv);
if (count($argv)<1) {
	montre_usage();
}

// mémorisation d'un argument trouvé : argument / signe utilisé / colonne impliquée
$prochain= "";$signe="";
$print_etat = false;
$col_test = '';$format_test = "";
$suivant = array(); $valeur_requise=false;
// une commande sans argument n'a pas de sens
$emploi = (count($argv)==0);

$a_exploser=false;
foreach ($argv as $un_arg){
	$vals = array();
	$fut_signe = substr($un_arg,0,1); $v_arg = substr($un_arg,1);
	if (( $fut_signe=='+'|| $fut_signe=='-') ) {
		if (!in_array($un_arg, $parametres_possibles)) {
			$err=true;
			$echo_err = message ('ArgINC',$un_arg);
			continue;
		} elseif ($suivant && (!in_array($v_arg, $suivant))) {
			$err=true;
			$params=array(implode(', ',$suivant),$un_arg);
			$echo_err = message ('ArgINAT',$params);
			$suivant=array();
		} elseif ($valeur_requise){
			$err=true;
			$params = array($un_arg,$prochain);
			$echo_err = message ('ValATT',$params);
		}
		$nettoie_col_test = true; $valeur_requise=true;
		switch ($v_arg) {
			case 'aide':
			case 'help' :
			case 'h' :
				$emploi = true;
				$echo_test.= "Pour voir l'aide.\n";
				$prochain = $signe= "";
				$valeur_requise=false;
				break;
			case 'rapport' :
			case 'status' : $print_etat = true; $prochain = $signe= ""; $valeur_requise=false; break;
			case "test" :
			case "v" :
				$echo_test.= message('param', $un_arg);
				$test = $print_etat = true;
				if ($fut_signe=='+' || $v_arg=='v') {
					$testp=true;
					$echo_test.= message('verbeux');
				}
				else $echo_test.= message('test');
				$prochain = $signe= ""; $valeur_requise=false;
				break;
			case 'allcol':
				$col_ret[]='*';
				$allcol=true;
				$prochain = $signe= "";
				$valeur_requise=false;
				break;
			case 'strin': $strin=true; $prochain = $signe= ""; $valeur_requise=false; break;
			case 'strout': $strout=true; $prochain = $signe= ""; $valeur_requise=false; break;
			case 'max':
			case 'tmax':
			case 'd' :
			case 'sep':
			case 'glu':
			case 'format' :
			case 'res':
			case 'rej':
			case 'colurl' :
			case 'src':
			case 'par' :
			case 'xtrt' :
				$prochain=$v_arg;
				$signe='';
				break;
			case 'colt' :
			case 'cold' :
				$prochain=$v_arg;
				$signe='';
				$suivant = ($v_arg=='colt')?$operateurs:$operateurs_date;
				break;
			case 'colca' :
			case 'colcip' :
			case 'colda' :
			case 'coldip' :
				$prochain=$v_arg;
				$signe='';
				break;
			case 'col' :
					$prochain=$v_arg;
					$signe=$fut_signe;
				break;
			case 'coluscheme' :
			case 'coluhost' :
			case 'coluhostrv' :
			case 'coluport' :
			case 'colupath' :
			case 'coluquery' :
			case 'colufragment' :
				$v = 'URL:'.substr($v_arg,4);
				if (!in_array($v, $col_ret)) {
					$col_ret[] = $v;
					$echo_test .= message ('PosCOLU', array($v,count($col_ret)));
				}
	  			$prochain='col';$signe='+';
				$valeur_requise=false;
	  			break;
			case 'colf' :
	  			$prochain='colf';$signe='+';
	  			break;
			case 'eq':
			case 'ne':
			case 'oo':
			case 'be':
			case 'ge':
			case 'gt':
			case 'le':
			case 'lt':
			case 'teq':
			case 'tne':
			case 'too':
			case 'tbe':
			case 'tge':
			case 'tgt':
			case 'tle':
			case 'tlt':
				if (!$col_test) {
					$err=true;
					$echo_err=message('NoColt', $v_arg);
					break;
				}
				$nettoie_col_test=false;
				$prochain=$v_arg;
				$suivant=array();
				$signe=$fut_signe;
				break;
			case 'f' :
			case 'fh' :
			case 's' :
				if (!$col_test) {
					$col_test = '*';
				}
				$nettoie_col_test=false;
				$prochain=$v_arg;
				$suivant=array();
				$signe=$fut_signe;
				break;
			case 'img' :
	  		case 'js'  :
	  		case 'std' :
				if (!$col_test) {
					//$err=true;					$echo_err=message('NoColt', $v_arg);
					$col_test = '*';
				}
	  			$vals = get_format($v_arg);
				$suivant=array();
	  			$prochain = "";$signe=$fut_signe;
				$nettoie_col_test=false;
				$valeur_requise=false;
	  			break;
			case 'hd' :
			case 'hd1':
	  			$ligne_header=$fut_signe;
	  			if ($v_arg=='hd1') $ligne_header.='1';
	  			$mod_col='nom';
				$valeur_requise=false;
	  			$prochain=$signe="";
	  			break;
		}
		if ($test) $echo_test.= message ('argSig',$signe.$v_arg);
		if ($nettoie_col_test) {$col_test=''; $format_test="";}

	} else { // Ce n'est pas un paramètre
		$valeur_requise=false;
		$entete_valeur = substr($v_arg, 0,1);
		if ($fut_signe=='.' && ($entete_valeur=='+'||$entete_valeur=='-'))
		  $un_arg=$v_arg;
		if ($prochain!=""){
//			if ($test) $echo_test.= "Valeur de $prochain = $v_arg trouve\n";
			switch ($prochain) {
				case 'max':
						if (! preg_match("/^\\d+\$/", $un_arg)) {
							$echo_err .= message('maxINV');
							$err=true;
						} else {
							$maxval=$un_arg*1;
							$echo_test.= message ('maxOK',$un_arg);
						}
						$prochain=$signe="";
						break;
				case 'tmax':
				case 'd':
					if (! preg_match("/^\\d+\$/", $un_arg)) {
						$echo_err .= message ('tmaxINV');
						$err=true;
					} else {
						$tmax=$un_arg*1;
						$echo_test.= message ('tmaxOK',$un_arg);
					}
					$prochain=$signe="";
					break;
				case 'sep':
						$sep = $un_arg;
						$prochain=$signe="";
						if ($test) $echo_test.= message ('sepOK',$un_arg);
						break;
				case 'glu':
						$glu = $un_arg;
						$prochain=$signe="";
						if ($test) $echo_test.= message ('gluOK',$un_arg);
						break;
				case 'res':
				case 'rej':
						$cas = ($prochain=='res')?
								message ('resultat'):
								message ('rejet');
						if (! (substr($un_arg,-3)==".gz")) $un_arg.='.gz';
						if ($fic_res==$un_arg|| $fic_rej==$un_arg) {
							$echo_err .= message ('FicERRDiff');
							$err=true;
						} elseif (($prochain=='res' && $fic_res) || ($prochain=='rej' && $fic_rej)) {
							$echo_err .= message('1Fic',$cas);
							$err=true;
						} elseif (file_exists($un_arg)) {
							$echo_err .= message ('ExistFIC',array($cas,$un_arg));
							$err=true;
						} else {
							if ($prochain=='res')
								$fic_res = $un_arg;
							else
								$fic_rej = $un_arg;
						}
						if ($test) $echo_test.= message ('FisRES',array($cas,$un_arg));
						$prochain=$signe="";
						break;
				case 'src':
						if (!is_file($un_arg)) {
							$echo_err .= message ('FicINEX',$un_arg);
							$err=true;
						} elseif ($un_arg==$fic_res || $un_arg==$fic_rej || $un_arg==$fic_par
							|| in_array($un_arg, $sources)
							) {
							$echo_err .= message ('1Fic',message ('source'));
							$err=true;
						} else {
							$sources[] = $un_arg;
							if ($test) $echo_test.= message ('FicSOUR',$un_arg);
						}
						break;
				case 'par':
				case 'xtrt' :
					$cas = message ($prochain);
						if (!is_file($un_arg)) {
							$echo_err .= message ('FicINEX',"$cas $un_arg");
							$err=true;
						} elseif ($un_arg==$fic_res || $un_arg==$fic_rej || $un_arg==$fic_par
							|| in_array($un_arg, $sources)
							|| ($prochain=='par' && $fic_par)
							|| ($prochain=='xtrt' && function_exists("a_lecture_ligne"))
							) {
							$echo_err .= message('1Usage',array($un_arg,$cas));
							$err=true;
						} else {
							if ($prochain=='par') {
								$fic_par = $un_arg;
								$ok = traite_fichier_parentheses($un_arg);
								if (!$ok){
									$echo_err .= traite_fichier_parentheses_mess('FINV',$fic_par);
									$err=true;;
								}
							} else {
								include_once($un_arg);
								$ok=true;
							}
							if ($ok) $echo_test.=message ('FicLU', array($un_arg,$cas));
						}
						$prochain=$signe="";
						break;

				case 'fh' :
						$cas = message('fh')." ".message('cas'.$signe);
						if (!is_file($un_arg)) {
							$echo_err .= message('FicINEX',$un_arg);
							$err=true;
						} elseif ($un_arg==$fic_res || $un_arg==$fic_rej || $un_arg==$fic_par
							|| in_array($un_arg, $sources)
							) {
							$echo_err .= message('1Usage',array($un_arg,$cas));
							$err=true;
						} else {
							if ($test) $echo_test.= message ('FicLU',array($un_arg,$cas));
							$vals = traite_fichier($un_arg,"/^(h|host|d|domain|hj|dj)\\s+/i");
						}
						break;
				case 'f' :
						$cas = message('f')." ".message('cas'.$signe);
						if (!is_file($un_arg)) {
							$echo_err .= message('FicINEX',$un_arg);
							$err=true;
						} elseif ($un_arg==$fic_res || $un_arg==$fic_rej || $un_arg==$fic_par
							|| in_array($un_arg, $sources)
							) {
							$echo_err .= message('1Usage',array($un_arg,$cas));
							$err=true;
						} else {
							$cas=message('cas'.$signe);
							if ($test) $echo_test.=message ('FicLU',array($un_arg,$cas));
							$vals = traite_fichier($un_arg);
						}
						break;
				case 'format' :
					if ($format) {
							$echo_err .= message('2format');
							$err=true;
							break;
					}
					$mod_col = 'nom';
					$format = $un_arg;
					$prochain=$signe="";
					break;
				case 'cold' :
							if (!preg_match ("/([^:]+):(.*)/", $un_arg,$matches)){
								$err=true;
								$echo_err .= message ('coldateINV',$un_arg);
								break;
							}
							$col_test = $matches[1]; $format_test=$matches[2];
							if ($un_arg=='*') {
								$err=true;
								$echo_err .= message ('PrecCOL',$prochain);

							} else {
								if (preg_match("/[^0-9]/", $col_test)) {
									$mod_col='nom';
								}
								$conv_colnum[$col_test]=-1;
							}

							// Pour limiter à une seule colonne et détecter la méprise de l'usager
							$prochain= '';
							break;
				case 'colf' :
							if (!preg_match ("/([^:]*):?([^:]+)/", $un_arg,$matches)){
								$err=true;
								$echo_err .= message ('colfixeINV',$un_arg);
								break;
							}
							if (!$matches[1]) {
								$col_id=count($col_fixe);
							} else {
								$col_id = $matches[1];
							}
							$col_fixe [$col_id]=$matches[2];
							$col_ret[]='FIX:'.$col_id;
							break;

				case 'col' :
				case 'colca' :
				case 'colcip' :
				case 'colda' :
				case 'coldip' :
				case 'colt' :
				case 'colurl' :
					if ($un_arg=='*') {
						if (!($prochain == 'col'&&!$allcol || $prochain!='colt')){
							$err=true;
							$echo_err .= message ('PrecCOL',$prochain);
						}
					} else {
						if (preg_match("/[^0-9]/", $un_arg)) { //le nom de col comporte un car. non numérique
							$mod_col='nom';
						}
						$conv_colnum[$un_arg]=-1;
					}
					$cas  = "";
					switch ($prochain){
						case 'colt':
							$col_test = $un_arg; $format_test="";
							// Pour limiter à une seule colonne et détecter la méprise de l'usager
							$prochain= '';
							break;
						case 'colurl' :
								$colurl  = $un_arg;
								$echo_test .= message ('colurl', $un_arg);
							break;
						case 'colca' :
								$col_code[$un_arg]='a';
						//		if (! (in_array($un_arg, $col_ret) || $allcol)) $col_ret[]=$un_arg;
								$echo_test .= message ('colca',$un_arg);
							break;
						case 'colcip' :
								$col_code[$un_arg]='ip';
						//		if (! (in_array($un_arg, $col_ret) || $allcol)) $col_ret[]=$un_arg;
								$echo_test .= message ('colcip',$un_arg);
								break;
						case 'colda' :
								$col_code[$un_arg]='-a';
						//		if (! (in_array($un_arg, $col_ret) || $allcol)) $col_ret[]=$un_arg;
								$echo_test .= message ('colda',$un_arg);
								break;
						case 'coldip' :
								$col_code[$un_arg]='-ip';
						//		if (! (in_array($un_arg, $col_ret) || $allcol)) $col_ret[]=$un_arg;
								$echo_test .= message ('coldip',$un_arg);
								break;
						default:
								if ($signe=="+") {
									if (in_array($un_arg,$col_exclues)) {
										$echo_err.= message ('colmixte',$un_arg);
										$err=true;
									} elseif (!in_array($un_arg, $col_ret)) {
										$col_ret[] = $un_arg;
										if ($un_arg=="*") $allcol=true;
										$echo_test .= message('col+',$un_arg);
									}
								} else  {
									if (in_array($un_arg,$col_ret)) {
										$echo_err.= message ('colmixte',$un_arg);
										$err=true;
									} else {
										$col_exclues[] = $un_arg;
										$echo_test .= message('col-',$un_arg);
									}
								}

						}
						break;
				case 'eq':
				case 'ne':
				case 'oo':
				case 'be':
				case 'ge':
				case 'gt':
				case 'le':
				case 'lt':
				case 'teq':
				case 'tne':
				case 'too':
				case 'tbe':
				case 'tge':
				case 'tgt':
				case 'tle':
				case 'tlt':
					if ($col_test=='*') {
						$err = true;
						$echo_err .= message ('NumTCOL',$prochain);
						break;
					}
					if ($signe=='+') {
						$mess = ajoute_val_num($extrait_num, $prochain,$col_test, $un_arg,$format_test);
						if ($mess){
							$echo_err .= message('colTERR',array($col_test,$signe.$prochain,$mess));
							$err= true;
						} else {
							$echo_test .= message('colTVAL',array($col_test,$signe.$prochain,$un_arg));
						}
					} else {
						$mess = ajoute_val_num($filtres_num, $prochain, $col_test,$un_arg,$format_test);
						if ($mess){
							$echo_err .= message('colTERR',array($col_test,$signe.$prochain,$mess));
							$err= true;
						}else {
							$echo_test .= message('colTVAL',array($col_test,$signe.$prochain,$un_arg));
						}
					}
					if ($prochain != 'eq' && $prochain!='teq' && $prochain!='be' && $prochain!='tbe'){
						$col_test='';
						$format_test='';
						$prochain=$signe='';
					}
					break;
				case 's' :
					$vals=array($un_arg);
					break;
			}
		} else { // il n'y a pas de paramètre préfixant l'usage de la valeur
			$err=true;
			$echo_err = message ('quoi',$un_arg);
			$prochain=$signe="";
		}

	}
	if ($vals) {
		$cas = message('cas'.$signe);
		foreach ($vals as $une_val){
			if ($signe=='+') {
				if (est_val_reguliere($une_val)){
					ajoute_val_str($extrait_RE, $col_test,$une_val) ;
				}
				else {
					ajoute_val_str($extrait, $col_test,$une_val) ;
				}
			} else {
				if (est_val_reguliere($une_val))
					ajoute_val_str($filtres_RE, $col_test,$une_val) ;
				else
					ajoute_val_str($filtres, $col_test,$une_val) ;

			}
		}
		$n = count($vals);
		$valsaff= ($test && $n<4)?implode(',', $vals):message ('NVAL',$n);
		$echo_test.= message('colTVAL',array($col_test,$cas,$valsaff));
	}
} // fin analyse ligne de commande

if ($test) error_reporting(15);


// séparateurs par défaut au besoin, en entrée et en sortie
$sepaff=$sep;$gluaff=$glucom=$glu;
if (!$sep) $sep='s';
if (!$glu) $glu= $sep;
if (preg_match ("/\\w/",$glu)) {
	switch ($glu){
		case 't': $glu="\t";break;
		case 'r': $glu="\r";break;
		case 'n': $glu="\n";break;
		default : $glu=" ";break;
	}
}
if (!$col_ret && !$col_exclues) {
	$col_ret[]='*';
	$allcol=true;
}



if ($mod_col=='nom') {
		/* Il faut un format soit dans la commande ... */
	if ($format) {
		if ( !traite_ligne_header ($format)) {
			$err=true;
			$echo_err = "Format de colonne invalide : $echo_err\n";
		}
		$ligne_header=false;
		/* ... soit en tete de fichier */
	} elseif ( !$ligne_header) {
		$err=true;
		$echo_err = message('Mod_nomINV');
	}
		/* Homogénéïsation de la forme du tableau des colonnes particulierement utiles. */
} else {
	foreach ($conv_colnum as $nom=>$nocol){
		$conv_colnum[$nom]=$nom;
	}
	$ligne_header=false;
}


// accélérateurs des filtrages et écritures :
$filtre_ligne = ($filtres || $filtres_RE || $filtres_num);
$valide_ligne = ($extrait || $extrait_RE || $extrait_num);
$ecrit_par_col = ($col_code || $col_exclues || $col_ret || $glucom || $col_fixe);



// Affichage du mode de fonctionnement si test demandé ou erreur.
if ($test || $err || $print_etat) {
		$echo_etat = etat_mess ();
}
if ($emploi) {
	montre_usage() ; exit();
}
if ($err) {
	$mess_err =  ($test) ?
				$echo_test."\n=================\n".$echo_err :
				$echo_etat."\n=================\n".$echo_err;
	fprintf(STDERR, "%s",$mess_err) ;
	exit();
}
if ($print_etat) {
	$mess = "\n".$echo_etat."\n";
	if ($test) {
		$mess = "\n".$echo_test.$mess;
	}
	fprintf(STDERR, "%s",$mess) ;
	$echo_err=$echo_etat=$echo_test=$mess="";
}

/* Construction des valeurs absolument nécessaires dans la ligne intégrale pour accélérer le rejet
 * des lignes.
 */
$pretest_ob = $pretest_RE_ob = array();
foreach ($extrait as $nocol=>$vals){
	if (count($vals)==1) $pretest_ob[]=$vals[0];
}
foreach ($extrait_RE as $nocol=>$vals){
	if (count($vals)==1) $pretest_RE_ob[]=$vals[0];
}
$pretest_exc = (isset($filtres['*']));$pretest_RE_exc = (isset($filtres_RE['*']));
// Ouvrage du résultat :
if ($fic_res!="") {
	if ( !$fres=gzopen($fic_res, 'wb')) die (message ('FicResNOP',$fic_res));
} else $fres=NULL;
if ($fic_rej!="") {
	if ( !$frej=gzopen($fic_rej, 'wb')) die (message ('FicRejNOP',$fic_rej));
} else $frej=NULL;


$a_exploser = ($a_exploser || $ecrit_par_col || function_exists("a_ecriture_ligne"));


if (!($sources)) {
	$sources[]='stdin';
}

$cptlues = 0; $cptecrites=0; $cptexclues=0;
$timemax=0; $tdebut=time();
$max_atteint=false;
if ($tmax > 0) {
	$timemax=time()+$tmax;
}
foreach ($sources as $source) {
// Ouverture des fichiers source et résultat :
	if (!ouvre_source($source)) {
		fprintf(STDERR, "%s",message ('FicSrcNOP',$source)) ;
		continue;
	} elseif ($testp) {
		fprintf(STDERR, "%s",message ('TrtSrc',$source)) ;
	}
	$cptlignefic = 0;
	$err = false;
	while (($ligne = lit_source())!==false) {
		$cptlignefic++;
		$cptlues++;
		/* cas de première ligne de fichier à entete */
		if ($cptlignefic==1 && $ligne_header){
			if (!traite_ligne_header ($ligne)) {
				fprintf(STDERR, "%s",$echo_err) ;
				$echo_err="";
				break;
			}
			continue;
		}
		if ($maxval>0 && $cptlues>=$maxval) {
			$max_atteint=true;
			fprintf(STDERR, "%s",message ('MaxFait',$maxval));
			break;
		}
		if ($timemax && time()>$timemax) {
			$max_atteint=true;
			fprintf(STDERR, "%s",message ('TMaxFait',$tmax));
			break;
		}
		if (function_exists('a_lecture_ligne')){
			$ligne = a_lecture_ligne($ligne);
			if (!$ligne) continue;
		}

	/* Test de rejet ou non de la ligne */

		$raison_rejet = ''; $ligne_valide=true;
		while ($ligne_valide) {
			if ($pretest_ob){
				foreach ($pretest_ob as $une_val) {
					if (strpos($ligne, $une_val)===false) {
						$raison_rejet = message('AbsVal',$une_val); $ligne_valide= false;break 2;
					}
				}
			}
			if ($pretest_RE_ob && $ligne_valide){
				foreach ($pretest_RE as $une_val) {
					if (!preg_match($une_val, $ligne)) {
						$raison_rejet = message('AbsVal',$une_val); $ligne_valide= false; break 2;
					}
				}
			}
			if ($pretest_exc && $ligne_valide){
				foreach ($filtres['*'] as $une_val){
					if (strpos($ligne, $une_val)!==false) {
						$raison_rejet = message('PresVal',$une_val); $ligne_valide= false;break 2;
					}
				}
			}
			if ($pretest_RE_exc && $ligne_valide){
				foreach ($filtres_RE['*'] as $une_val) {
					if (preg_match($une_val, $ligne)) {
						$raison_rejet = message('PresVal',$une_val); $ligne_valide= false;break 2;
					}
				}
			}

			if ($a_exploser && $ligne_valide) {
				$tab_ligne = explose_ligne($ligne, $sep,$col_testees);
				if (!is_array($tab_ligne)) {
					$ligne_valide= false;
					$raison_rejet = $tab_ligne;
					break;
				}
				if ($ecrit_par_col ) {
					$ligne = ecrit_res_acode ($tab_ligne,$col_ret,$col_exclues,$col_code,$glu,$colurl,$col_fixe,false);
					if (is_array ($ligne)) {
						$raison_rejet= message ('ligIncomp',$source.'::'.$cptlignefic);
						$ligne_valide= false;
					}
				}
				if (function_exists("a_ecriture_ligne")) {
					$ligne = a_ecriture_ligne($ligne,$tab_ligne);
					if (!$ligne){
						$raison_rejet="fct : a_ecriture_ligne / ".implode($glu,$tab_ligne)."\n";
						$ligne_valide= false;
					}
				}
			}
			break;
		}
		if ($ligne_valide) {
			if ($fres) gzwrite($fres, $ligne);
			else  print $ligne;
			$cptecrites++;

		} else {
			$cptexclues++;
			if ($testp) fprintf(STDERR, "%s","[$cptexclues] $source::$cptlignefic : $raison_rejet\n") ;
			if ($frej) 	gzwrite($frej, $ligne);
			continue;
		}
	}
	if ($testp) {
		fprintf(STDERR, "%s",message ('BilanSrc',array($source,$cptlignefic))) ;
	}
	ferme_source();
	if ($max_atteint) break;
}


if (isset($fres) && $fres) gzclose($fres);
if (isset($frej) && $frej) gzclose($frej);
if ($test || $print_etat) {
	$tmis = time()-$tdebut;
	fprintf(STDERR, "%s",message('final',array($cptlues,$cptecrites,$tmis)));
}
