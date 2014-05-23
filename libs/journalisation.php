<?php

class journal {
	var $mon_journal;
//	var $p_journal;
	function journal($nom_journal) {
		$this->mon_journal = $nom_journal;
	}
	function enregistre ($message){
		if (!$this->mon_journal) return (true);
		if (!strlen($message)) return (true); 
//		if (! $this->p_journal) ...
		$son_IP = $_SERVER['REMOTE_ADDR'];		
		$message = '['.date('Y/m/d H:i:s')."] $son_IP \t".$message."\n";
		$l = file_put_contents($this->mon_journal, $message, FILE_APPEND | LOCK_EX);

		if ($l === false || $l<strlen($message)) return(false);
		return (true);
	}
}
/**
 */
