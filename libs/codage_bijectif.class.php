<?php
/**
 * =================================================================================
 * Encoding / decoding class
 * Encodage / Décodage
 * =================================================================================
 * TOUCH
 * @var $code_car array = liste servant de base de code
 */


class codage_bijectif {

private static $code_car = array	('a','b','c','d','e','f','g','h','i','j','k','l','m'
						,'n','o','p','q','r','s','t','u','v','w','x','y','z'
						,'A','B','C','D','E','F','G','H','I','J','K','L','M'
						,'N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
						,'0','1','2','3','4','5','6','7','8','9'
						);
						
private static $v_codees = array(); 
private static $v_decodees = array();

public function encode_valeur($v,$mode){
	if (substr($mode,0,1)=="-") return (self::decode_valeur($v, substr($mode,1))); 
	if (isset(self::$v_codees[$v])) return (self::$v_codees[$v]);
	$res = $v;
	switch ($mode){
		case 'a':
/* Your code to encode strings - Votre code pour encoder une chaine */  
			break;
		case 'ip':
/* Your code to encode IP number - Votre code pour encoder un n° IP */  
			break;
	}
	self::$v_codees[$v]=$res;
	return ($res);
}

public function decode_valeur($v,$mode){
	if (isset(self::$v_decodees[$v])) return (self::$v_decodees[$v]);	
	$res = $v;
	switch ($mode){
		case 'a': 
/* Your code to decode strings - Votre code pour decoder une chaine */  
						break;
		case 'ip':
<<<<<<< HEAD
/* Your code to decode IP number - Votre code pour decoder un n° IP */  
=======
/* Your code to encode IP number - Votre code pour encoder un n° IP */  
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
	}
	self::$v_decodees[$v]=$res;
	return ($res);
}


}
 						

