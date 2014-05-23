<?php
function trad_message ($module,$code,$params=NULL){
	global $messages;
	if (!(isset($messages[$module][$code])) ){
		return ("$module:$code");
	}
	$v_base = $messages[$module][$code];
	if ($params!==NULL ){
		if (is_array($params)&& count ($params)>0) {
			for ($i=0;$i<count($params);$i++){
				$v_base = preg_replace("/!$i!/", $params[$i], $v_base);				
			}
		} else {
			$v_base = preg_replace("/!0!/", $params, $v_base);
		}
	}
	return ($v_base);
}
?>