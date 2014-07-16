#!/usr/bin/env php
<?php
/**
 * gcsv_extractor V1.1.1
 *
 * English version of extrait_logs.php
Extracts out of logs files usefull informations selection of lines and columns to keep in output result.
To select lines, it casts them on excluded values or choices these which contain one or more searched values.
  The exclusion or search for a value can be done in all line string or in single column string.
  However, numerical criteriae have to specify the column implied
Columns that have to appear in the result can be reordered, encoded or decoded, even URL column can be explode
  in many URL element columns (host, path, query, ...) [parse_url ::http://www.php.net/manual/fr/function.parse-url.php).]
  and special hostrv
As columns can contain the CSV column delimiter, it's possible to define characters couple to parenth their
  values. These couples are found in "-par" file named in the command line.
  Default is that these column values must be enclosed between double quotes("") or left/right bracket([]).

There is five ways to select/exclude lines :
 - Fixed string in s parameter :  +/-s string1 string2...
 - Use predefined commons suffixes (images or scripts)
 - Have the values in files (ASCII file with a string or a regExp value per line)
 - The same with EzProxy host files (containing host or domain stanzas)
 - Numerical / datational test on particular column.
If the command don't have a -src source_files parameter, standard input is used data to be filtered.
If the command don't have a -res result_file parameter, standard output is used to write out the result.
If the command don't have a -res cast_file parameter, for casted lines, these are lost.

N.B. It's possible to have an -xtrt phpfile to define prefilter and prewriter function respectively named
   string function a_lecture_ligne(string $input_ligne))
   string function a_ecriture_ligne(array $readytowrite_columns))
  *
 */

$langue = 'en';

function echo_usage (){
?>
Usage : ./gcsv_extractor.php
			(-h | -help) | (
              [(+|-)test | -v | -rapport | -status)]
              [-xtrt PHP_filename] [-max int] [(-tmax|-d) int] [-sep char] [-glu char]
              [( (+|-)hd[1] | -format format_str ]
              [-colt (colnamenum|"*") {+|-}ctriteria] [-colt ....]]
              [-cold colnamenum:format (+|-)datecriteria] [-cold ...] [-strin]
              [-{colca|colcip|colda|coldip} colnamenum [colnamenum ...]]
              [(-allcol | (+|-}col colnamenum [colnamenum ...])] [+colf colnamenum:value [colnamenum:value ...]]
              [-colurl colnamenum +colu(scheme|host|hostrv|port|path|query|fragment) [+colu(scheme|...)...]]
              [-res result_filename] [-rej reject_filename]
              [-src source_filenames [source_filename ...]]
              )
    ctriteria =
        	std|img|js
        	f|fh filename [filename [...]]
            s string [string[...]]
            eq|ne|ge|gt|le|lt rationnal (as d+[.d+] where d+ is one or more digits)
            be|oo rationnal,rationnal
    datecriteria =
            teq|tne|tge|tgt|tle|tlt datetime (as [[[YYYY-]M[M]-]D[D]][.h[h][:m[m][:s[s]]]])
            tbe|too datetime,datetime
    special values (string, numerical, filename) beginning with signs plus(+) or minus(-) must be
    preceded with a dot(.) . Ex: To search +col string, use .+col argument value.
	<?php
}

function detail_usage (){
?>
gcsv_extractor.php V0.3 :
Extracts out of logs files usefull informations selection of lines and columns to keep in output result.
To select lines, it casts them on excluded values or choices these which contain one or more searched values.
  The exclusion or search for a value can be done in all line string or in single column string.
  However, numerical criteriae have to specify the column implied
Columns that have to appear in the result can be reordered, encoded or decoded, even URL column can be explode
  in many URL element columns (host, path, query, ...) [parse_url ::http://www.php.net/manual/fr/function.parse-url.php).]
  and a special column hostrv which is reversed host's components column
As columns can contain the CSV column delimiter, it's possible to define characters couple to parenth their
  values. These couples are found in "-par" file named in the command line.
  Default is that these column values must be enclosed between double quotes("") or left/right bracket([]).

There is five ways to select/exclude lines :
 - Fixed string in s parameter :  +/-s string1 string2...
 - Use predefined commons suffixes (images or scripts)
 - Have the values in files (ASCII file with a string or a regExp value per line)
 - The same with EzProxy host files (containing host or domain stanzas)
 - Numerical / datational test on particular column.
If the command don't have a -src source_files parameter, standard input is used data to be filtered.
If the command don't have a -res result_file parameter, standard output is used to write out the result.
If the command don't have a -rej cast_file parameter, for casted lines, these are lost.

N.B. It's possible to have an -xtrt phpfile to define prefilter and prewriter function respectively named
   string function a_lecture_ligne(string $input_ligne))
   string function a_ecriture_ligne(array $readytowrite_columns))

Arguments :
    -help | -h : display this help.
	-test : show accepted arguments and at end of output, the number of readen/written lines.
	+test : more detailed report.
	-v    : ... most detailed report with cast reason of every excluded line on stderr output.
	-max n : to limit to n the number of filtered lines. The rest of file is simply ignored.
	-tmax s | -d s : limit to s seconds the time of filtering. Non filtered lines are ignored.
	-sep c : set the input delimiter char to c.
	-glu c : set the output delimiter char to c.
	-par file : file of parens couple lines. Each line contains
	     - two strings separated with space (first string represents the left parens, the second the right one)
	     - deux chars (first the left, second the right)
	     - only one char that is the left and right parens itself.
	-xtrt PHP_filename : PHP with two string functions :
		string function a_lecture_ligne(string input_line) {}
	    string a_ecriture_ligne(array result_columns) {}
	    First give a corrected line before filter, second give the line to write to result stream.


    Selection/casting Arguments for lines and columns.
       Note: (+/-) is used to select/exclude line or columns in the result.
       Every column parameter can be followed by a name or a number (place order) of the column.
       First case, the place of named columns are mapped after the first line has been read (column header line)
       behind the -format parameter. Both method is a input delimiter separated string of all the names of the
       columns (being or not used in the parameters).

       ** Result columns selection/casting :
      Could be omitted if all the input line has to be written to result.
    ((+|-)col N [N2[...]|-allcol) : to keep (+) or cast (-) columns N [N2[...].
       Only one of these 3 these is permetted: don't have a +col and a -col together or coupled with -allcol.
       Couterpart, columntreatment parameters below (col(c|d)(a|ip) and colurl colu(...) )can be used with one
       of +col, -col or -allcol
       Typicaly, -allcol has to be used with column treatment parameters ...
    -col(c|d)(a|ip) N [N2[...] :
       encode (+colc??) / decode (cold??) the columns N [N2[...] in the result.
       The method is character string (??=a) or IP string (??=ip) oriented on the name end of the parameter.
       Need usage of +col N N2 ... or -allcol to place the column in the result.
    +colf Name:val [N2:val2 ...] :
       add a column Name (resp_y N2 ...) in the header line and then a column valued with val
       (resp_y val2 ...) in the next lines.
    -colurl N : to say that N column contains URL values. This parameter is only used with one of the
       following:
    +colu(scheme|host|hostrv|port|path|query|fragment) :
        must be used in conjunction with -colurl to says what URLelements have to be written in result.
        scheme = protocol (http/ftp/...),
        host = DNS host name of server, ... (port, path, query and fragment).
        hostrv = reversed host name .
    -allcol : to add all the other after. Cannot be used with -col N  or before any of the preceding
       +col??? parameters.

    -strout : cast every line wich have no value in a selected column.

       ** Result lines selection/casting :
       * Special use of header (first) line if parameter is present:
    (+|-)hd,(+|-)hd1  : say to keep or not this header line or not on the first line of result. Followed by 1
       digit says that only the first file as a header line (only with -src parameter followed by many
       filenames or wildcarded name).
       If no hd parameter is present, first lines of file are seen as other lines.
       If column are used in the command, +/-hd / -format MUST be present.

    -format "format_str" : format_str is the string that could be the header line of sources
       and give names to columns as "ip ses us url sta byt gps" are names for 7 columns file.

       * Other lines:
    -colt n : needed before string/numerical criterias to specify the column in which it shall be applied.
       n can be "*" to specify all the line (every column).
       After this parameter, you can have many criteriae as describded below. Sign +  precedes
       searches to keep line wether minus (-) sign precedes searches to exclude line.
       Two searched value on the same column means the first OR the second.
       In the other hand, seaches in two columns retains valid lines for each column. I.e.:
        ... -colt C1 +search1 -colt C2 +search2
        keep the line if C1 value matches search1 and C2 value matches search2 and
        ... -colt C1 +search1 +search2
        will keep the line if C1 value matches search1 OR search2.
    -cold n:format : as colt before date/time criterias and join a format string to the columns
       name/nr, describded below.
    -strin : cast the line if a seached column is empty or doesn't exist in the line.

       * TEXTUAL criteriae :
    std : replace list of images and scripts formats: '.js' '.css' '.gif' '.jpg' '.png' '.ico'
    img : replace list of images formats: '.gif','.jpg','.png','.ico'
    js  : replace list of scripts formats: '.js','.css'
    s stringS : to keep/exclude lines which contain stringS (if many, read on of the strings).
       If the first and the last character are the same but non alphanumerical character , it's used as a RegExp.
    f filenameS : like s but strings are read in a file, each line of this containing one searched
       string or RegExp.
    fh filename : like f but read the file as config/included file for ezproxy and treat only lines beginning
       with d ou domain ou host ou h ou hj  ou dj without # ahead.

       * NUMERICAL and DATE/TIME criteriae (operator must begin with t -  ex: teq)
     [t](eq|ne|ge|gt|le|lt) val : (equal|not equal|great or equal|great than|less or equal|less than) the value
         val. val must verify one of these format :
       [+|-]d[.d] numerical test/value (d must be one or more digits),
       [[[YYYY-]mm-]dd][.hh[:MM[:ss]]] case date/time test.
     [t](be|oo) valmin,valmax :  means (between|out of) the two values valmin and valmax

       * Date column format
     Every date/time element is written as a letter preceded with a % sign.
     A letter without heading % means the letter itself.
     In the following list, read
     litt = name ; num = digital value / (n)  fixed length in characters of name or digital values..
     Day of week:  %a = litt(3) %A = litt ; %u = num (1=monday-7=sunday) ; %w = num (0=sunday-6=saturday)
     Day of month : %d = num(2) ; %e = num ; ... of year : %j = num(3) (001-366)
     Month : %b = %h = litt(3) ; %B = litt ; %m = num(2) (01-12)
     Year : %y : num(2) ; %Y = num(4)
     Hour : %H : 00-23 ; %k = 0-23 ; %I = 01-12 ; %p = AM/PM ; %P = am/pm
     Minute : %M = 00-59 Second %S = 00-59
     Horaire %r=%I:%M:%S %p ; %T = %H:%M:%S
     Caracteres echappes  %% = % %, = ,

	** Source and result files
     -src source_filenames : list of files to treat. If command contains no source, standard input is read
         as source.
     -res result_filename : result filename or filepath. If command doesn't contain result filepath,
         standard outpu is written with the result lines.
     -rej reject_filename : castedline filepath could be in the command. All excluded lines of result_filename
         will be copied in this file as they are read in sourcefile


** Examples:
 * Imagine that log files produced by ezproxy being headed with the LogFormat config.txt line descriptor:
 %h %{ezproxy-session}i %u %t "%r" %s %b %{ezproxy-groups}i
 you can rename the columns with easiest readible columnname, using a file format.txt, containing the solely
 line:
 IP session login date url code taille groupes
 and work in final file with this header :
./gcsv_extractor.php -src format.txt my_log* +hd >final
  * encode login and IP of users columns and keep the first line = header line :
./gcsv_extractor.php -src format.txt mes_log* -allcol +hd -colca login -colcip IP
  * suppress some columns (NB header line is ajusted):
./gcsv_extractor.php -src format.txt mes_log* +hd -col login code  session
  * Reorder and encode IP and login columns, explode URL to keep only its host:
./gcsv_extractor.php -src format.txt mes_log* +hd -colurl url -colcip IP -colca login +col date IP login +coluhost +col groupes
  * suppress lines refering host which names are in files "dom_xxx"  :
./gcsv_extractor.php -src my_logs* -hd -colt url -f dom_*
  * extract lines of a provider but not those where it's the request to my reverse proxy "my_server".
        Keep only usefull info as date, login (encoded), URL elements : path and query, and  groupes:
./gcsv_extractor.php -src format.txt my_logs* -hd -colurl url -colca login  +col date login +colupath +coluquery \\
+col groupes -colt url +f dom1 -s my_server -res fichier_dom1
   * Keep only tracks between 11h13 and 11h17, having 200 status code and don't keep the query to my reverse
   "my_server"
./gcsv_extractor.php  -hd1 -src format.txt my_logs*  -cold "date:[%Y/%m/%d %H:%M:%S]" +tbe .11:13,.11:16 \\
 -colt code +eq 200 -colt "*" -s my_server +col "*" >res1


  ...
<?php
}


function etat_mess (){
	global $maxval,$sources,$tmax;
		$echo_etat = "";
		if ($maxval>0)
			$echo_etat.= "* Cast $maxval first lines of ";
		else $echo_etat.= "* Cast content of ";
		if ($sources) $echo_etat.= implode(',',$sources);
		else $echo_etat.="standard input";
		if ($tmax > 0) $echo_etat.= " for $tmax seconds maximum";
		$echo_etat.= ".\n";
	global $valide_ligne,$extrait,$extrait_RE,$extrait_num;
		if ($valide_ligne) {
			if ($extrait || $extrait_RE) {
				$nv = 0; $nc=0;
				foreach ($extrait as $nocol=>$vals) {
					$nv += count($vals); $nc++;
				}
				foreach ($extrait_RE as $nocol=>$vals) {
					$nv += count($vals); $nc++;
				}
				$echo_etat.="* Keep only lines containing $nc value(s) among the $nv searched values.\n";
			}
			if ($extrait_num) {
				$nc = count($extrait_num);
				$echo_etat.="* Keep only lines containing  valid value(s) in the $nc columns (tested numericaly or on date/hour)\n";
			}
		}
	global $filtre_ligne,$filtres,$filtres_RE,$filtres_num;
		if ($filtre_ligne){
			if ($filtres || $filtres_RE){
				$nv = 0; $nc=0;
				foreach ($filtres as $nocol=>$vals) {
					$nv += count($vals); $nc++;
				}
				foreach ($filtres_RE as $nocol=>$vals) {
					$nv += count($vals); $nc++;
				}
				$echo_etat.="* Cast lines containing one of the $nv values searched in $nc columns.\n";
			}
			if ($filtres_num){
				$n = count($filtres_num);
				$echo_etat.="* Cast lines containing one unvalid value in the $nc columns tested numericaly or on date/hour\n";
			}
		}
	global $sepaff,$gluaff,$format,$fic_res,$fic_rej;
		if ($sepaff)
			$echo_etat .= " ... use \"$sepaff\" as column delimiter\n";
		if ($format){
			$echo_etat.= " ... and $format as line format.\n";
		}
		if ($fic_res)
			$echo_etat.= "- Create $fic_res with the result\n";
		else
			$echo_etat.= "- Only display the selected lines\n";
	global $col_exclues,$col_ret,$col_code;
		if ($col_exclues)
			$echo_etat.= " ... except the columns ".implode(', ',$col_exclues)."\n";
		elseif ($col_ret)
			$echo_etat.= " ... keep only ".implode(', ',$col_ret)." columns.\n";
		if (count($col_code)>0)
			$echo_etat.= " ... encoding/decoding ".implode(', ',array_keys($col_code))." columns\n";
		if ($gluaff)
			$echo_etat .= " ... and join results columns with \"$gluaff\"\n";
		if ($fic_rej)
			$echo_etat.= "- Finally create $fic_rej with casted lines\n";
		return ($echo_etat);
}
function traite_fichier_parentheses_mess($no_mess,$param=""){
	switch ($no_mess){
		case 'FINC' : return ("Unknown parens file $param.\n");
		case 'FINV' : return ("Unvalid parens file $param.\n");
		default:
			if (is_array($param)) $param=print_r($param,true);
			return ("Unknown error case $no_mess caused by $param .\n");
	}
}
function traite_fichier_mess ($no, $param=''){
	switch ($no){
		case 'FINV': return ("unvalid file $param.\n");
		case 'TEST': $fic =$param[0]; $nb = $param[1];
			return ("...$fic contains $nb values.\n");
		default:
			if (is_array($param)) $param=print_r($param,true);
			return ("Unknown error case $no caused by $param .\n");
	}
}
function converti_col_val_mess ($no,$param){
	switch ($no){
		case 'COLINV':
				return ("$param column not found in the header line of logs.\n");
		case 'DBLDEF': return ("Double definition for $param.\n");
		case 'LIGOR' : return ("Header line is: $param \n");
		default:
			if (is_array($param)) $param=print_r($param,true);
			return ("Unknown error case $no caused by $param .\n");
	}
}
function ajoute_val_num_mess($no,$param){
	switch ($no){
		case 'NOOP': return ("$param is not a valid operator.\n");
		case 'NOINTV':
			$val = $param[0];$op=$param[1];
			return ("$val is not a valid interval to use with $op.\n");
		case 'INVVAL':
			return("Invalid value: ".$param);
		case 'INTVINV':
			return("Invalid min/max: ".$param);
		case '1VAL':
			return("Give only one value with ".$param);
		default:
			if (is_array($param)) $param=print_r($param,true);
			return ("Unknown error case $no caused by $param .\n");
	}
}
function normalise_critere_num_mess ($no,$param){
	switch ($no){
		case 'VINV':
			return ("invalid numerical value $param.\n");
		case 'DTINV':
			return("Invalid date/time value $param. Must be YYYY-MM-DD.hh:mn:ss .\n");
		case 'ANNEEINV':
			$v=$param[0];$vt = $param[1];
			return("Invalid year $vt in date $v.\n");
		case 'MOISINV':
			$v=$param[0];$vt = $param[1];
			return("Invalid month $vt in date $v.\n");
		case 'JOURINV':
			$v=$param[0];$vt = $param[1];
			return("Invalid day $vt in date $v.\n");
		case 'HEURINV':
			$v=$param[0];$vt = $param[1];
			return("Invalid hour $vt in date $v.\n");
		case 'MININV':
			$v=$param[0];$vt = $param[1];
			return("Invalid minutes number $vt in date $v.\n");
		case 'SECINV':
			$v=$param[0];$vt = $param[1];
			return("Invalid second number $vt in date $v.\n");
		default:
			if (is_array($param)) $param=print_r($param,true);
			return ("Unknown error case $no caused by $param .\n");
	}
}
function valeur_col_valide_mess ($no,$param=''){
	switch ($no){
		case 'SSVAL':
			return ("No value for $param column");
		case 'PRESVAL':
			$nocol = $param[0]; $une_val=$param[1];
			return ("$une_val is in $nocol.");//
		case 'VALEXC':
			$nocol = $param[0]; $une_val=$param[1];
			return ("Excluded value $une_val found in $nocol.");
		case 'NOVAL'://
			$nocol = $param[0]; $une_val=$param[1];
			return ("Column $nocol: no searched value in $une_val.");
		default:
			if (is_array($param)) $param=print_r($param,true);
			return ("Unknown error case $no caused by $param .\n");

	}
}

function message ($no,$param=''){
	global $langue;
	switch ($no){
		case 'ArgINC' :
			return ("Unknown argument $param.\n");
		case 'ArgINAT' :
			$liste = $param[0]; $arg=$param[1];
			return ("$arg found while waiting for one argument among $liste.\n");
		case 'ValATT' :
			$arglu = $param[0]; $arg_en_cours = $param[1];
			return ("$arglu found while waiting for the value of $arg_en_cours.\n");
		case 'param' :
			return ("Argument $param ");
		case 'verbeux' :
			return ("Verbose mode on.\n");
		case 'test' :
			return ("Simple trace mode on.\n");
		case 'col+-' :
			return ("Use only on of +allcol,  +col and -col.");
		case 'PosCOLU' :
			$nom = $param[0];$pos=$param[1];
			return ("Keeping part URL $nom as column $pos.\n");
		case 'NoColt' :
			return ("$param found without -colt attached to.\n");
		case 'coldateINV' :
			return ("Invalid column:format : $param\n");
		case 'colfixeINV' :
			return ("Invalid constant valued column column:format : $param\n");
		case 'argSig' :
			return ("$param argument.\n");
		case 'maxINV' :
			return ("-max must be followed by an number.\n");
		case 'maxOK' :
			return ("Max lines to cast set to $param.\n ");
		case 'tmaxINV' :
			return ("-tmax must be followed by an number.\n");
		case 'tmaxOK' :
			return ("Max time to cast set to $param seconds.\n ");
		case 'sepOK' :
			return ("Column delimiter set to $param.\n");
		case 'gluOK' :
			return ("Result delimiter set to $param.\n");
		case 'resultat' :
			return ("result");
		case 'rejet' :
			return ("cast");
		case 'FicERRDiff' :
			return ("Result and cast file must be different.\n" );
		case '1Fic' :
			return ("Give only one $param file.\n");
		case 'ExistFIC' :
			$cas = $param[0];$fic=$param[1];
			return ("File $fic exists.\n");
		case 'FicRES' :
			$cas = $param[0];$un_arg=$param[1];
			return ("Write $cas file $un_arg.\n");
		case 'FicINEX' :
			return ("$param doesn't exist.\n");
		case 'source' :
			return ("source");
		case 'FicSOUR' :
			return ("Add $param to source files.\n");
		case 'par' :
			return("parens");
		case 'xtrt':
			return ("external script file");
		case '1Usage' :
			$un_arg = $param[0]; $cas = $param[1];
			return("$un_arg or $cas is twice used.\n");
		case 'FicLU' :
			$un_arg = $param[0]; $cas = $param[1];
			return("$cas set to $un_arg.\n");
		case 'fh' :
			return ("hostfile");
		case 'f' :
			return ("values");
		case 'cas+' :
			return ("to search");
		case "cas-" :
			return ("to cast out");
		case '2format' :
			return ("Two formats declared.");
		case 'PrecCOL' :
			return ("Give a column number after $param and not *\n");
		case 'colurl' :
			return ("URL column to explode $param.\n");
		case 'colca' :
			return ("Encode alpha column $param.\n");
		case 'colcip' :
			return ("Encode IP column $param.\n");
		case 'colda' :
			return ("Decode alpha column $param.\n");
		case 'coldip' :
			return ("Decode IP column $param.\n");
		case 'colmixte' :
			return ("$param to keep and to left.\n");
		case 'col+' :
			return ("Column $param to keep.\n");
		case 'col-' :
			return ("Column $param to left.\n");
		case 'NumTCOL': //
			return ("Numeric test $param must be on 1 column and not *.\n");
		case 'colTERR' :
			$col = $param[0];$crit=$param[1];$mess = $param[2];
			return ("Error in criteria $crit on column $col : $mess.\n");
		case 'colTVAL' :
			$col = $param[0];$crit=$param[1];$val = $param[2];
			return ("Criteria $crit $val on column $col.\n");
		case 'quoi' :
			return ("$param found while argument expected.\n");
		case 'NVAL' :
			return (" $param values");
		case 'FormatINV' :
			return ("Invalid format : $param.\n");
		case 'Mod_nomINV' :
			return ("Named columns used, but nor header line neither -format argument.\n");
		case 'FicResNOP' ://
			return ("ERR: Cannot open $param.\n");
		case 'FicRejNOP' ://
			return ("ERR: Cannot open casted lines file $param.\n");
		case 'FicSrcNOP' ://
			return ("ERR: Cannot open source file $param.\n");
		case 'FicSrcNOP' ://
			return ("\n===\n  Process/cast source file $param\n===\n\n");
		case 'MaxFait' :
			return ("Max lines to cast $param has been processed.\n");
		case 'TMaxFait' :
			return ("Max time to process $param s exceeded.\n");
		case 'AbsVal' :
			return ("$param not in line.\n");
		case 'PresVal' :
			return ("$param found in the line.\n");
		case 'ligIncomp' :
			return ("Incomplete line $param.\n");
		case 'BilanSrc' :
			$source=$param[0];$cptlignefic=$param[1];
			return ("\n...\n  $source finished ($cptlignefic lines) ===========\n\n");
		case 'final' :
			$cptlues=$param[0];$cptecrites=$param[1];$tmis=$param[2];
			return ("\n...\n  all sources treated ($cptlues read lines, $cptecrites written lines during $tmis s). ===========\n\n");
		default:
            $mess = "Unknown error case $no";

            if (!empty($param)) {
                if (is_array($param)) $param=print_r($param,true);
                $mess.= " caused by $param";
            }
    }
	return ($mess.".\n");
}
$ce_repertoire = dirname(__FILE__);

include_once ("$ce_repertoire/gcsv_extrait.corps.php");