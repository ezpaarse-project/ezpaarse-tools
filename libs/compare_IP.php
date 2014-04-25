<?php
/*
 * EZPROXY : 
 * General utility - IP V4 Compare
 */ 
/**
 * 
 * IP V4 arithmetic comparison of an IP $l_IP with another IP or an IP interval as 
 * IPmin-IPmax,  $mini_max.   
 * The IPs data are www.xxx.yyy.zzz . 
 * Returns -1 if $l_IP is less than IPmin , +1 if greater than IPmax and 0 if between 
 * IPmin-IPmax 
 * 
 * Compare l'IP V4  $l_IP avec $mini_max  qui est soit une IP unique, soit une valeur mini 
 * suivie d'une valeur maxi les deux séparées par un tiret. Les IP sont sous la forme 
 * www.xxx.yyy.zzz .
 * Le résultat semblable à celui rendu par strcmp. 0 si l'IP est entre mini_max, 
 * -1 si elle est inférieure, +1 si elle est supérieure

 * @param string $l_IP
 * @param array $mini_max
 * @return int : 0 si l'IP est entre mini_max 
 * 				-1 si elle est inférieure, 
 * 				+1 si elle est supérieure
 */
function compare_IP($l_IP,$mini_max){
	$Tmm = explode('-', $mini_max);
	$T_mini = explode('.',$Tmm[0]);
	if (count($Tmm)==1){
		$T_max = $T_mini;
	} else {
		$T_max = explode('.',$Tmm[1]);
	}
	$T_IP = explode('.',$l_IP);
	for ($i=0; $i<4;$i++){
		if ($T_IP[$i]<$T_mini[$i]) return (-1);
		if ($T_IP[$i]>$T_max[$i]) return (1);
	}
	return (0);
}
