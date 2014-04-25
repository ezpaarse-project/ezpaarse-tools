<?php
class trace {
	private  $contenu;
	private  $fichier;
	private  $ressource;
	private  $message_erreur;
	private  $code_erreur;
	public function __construct() {
		$this->contenu = "";		
		$this->message_erreur = "";
		$this->fichier="";
		$this->ressource=false;
	}
	
	public function get_message(){
		return ($this->message_erreur);
	}
	public function get_code(){
		return ($this->code_erreur);
	}
	public function write_in_file ($file){
		if ($this->ressource!==false) return(false);
		if (is_resource($file)) {
			$this->fichier='user_defined';
			$this->ressource=$file;
			return (true);
		}
		if (($fp = fopen ($file,'a'))){
			$this->fichier=$file;
			$this->ressource=$fp;
			return (true);
		} else {
			$this->fichier="";
			$this->ressource=false;
			return (false);
		}
		
	}
	public function stop_write_in_file(){
		if (!$this->fichier) return (false);
		if (!$this->ressource) return (false);
		fclose($this->ressource);
		$this->ressource=false;
		$this->fichier="";
		return (true);
	}
	
	public function ajoute ($mess){
		if (is_array($mess))
			$mess = implode ("\n",$mess);
		$mess.="\n";
		if ($this->fichier) {
			return(fwrite ($this->ressource,$mess));
		} else {
			$this->contenu .= $mess . "\n";
			return (strlen($mess));
		}	
	}
	
	public function vide (){
		$this->contenu = "";
	}
	public function lit (){
		return ($this->contenu);
	}
	public function affiche (){
		echo ($this->contenu);
	}
	public function ecrit ($pressource){
		if (!is_resource($pressource)) {
			$this->message_erreur = "trace::ecrit : The parameter is not a resource - Le paramètre n'est pas une ressource.";
			$this->code_erreur=1;
			return (false);	
		}
		if (!strlen($this->contenu)) {
			$this->message_erreur = "trace::ecrit : Empty trace - trace vide.";
			$this->code_erreur=2;
			return (false);	
		} 
		$message = "=================\n  Log of ".date('m-d-Y to H:i:s')."- Trace du ".date('Y/m/d a H:i:s')."\n".
					$this->contenu."\n";
		$l = fprintf($pressource, $message);

		if ($l === false || $l<$message) {
			$this->message_erreur = "trace::ecrit : trace truncated - trace tronquee.";
			$this->code_erreur=3;
			return(false);	
		}
		$this->contenu="";
		return (true);
	}
}
?>