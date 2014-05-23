<?php
/*
 * EZPROXY : CGI authentication tools.
 * Multi CAS/LDAP authentication Filter. 
 * valid_acces functions for custumisation. TO CUSTUMISE 
 */
/**
 * 
 * Custumisation of the selection of IdP
 * Equivalent to Shibboleth Wayf
 */
function my_IdP_selector ($mess){
	ma_demande_identifieur($mess);
}
function ma_demande_identifieur($mess='')
  {
  global $__VAcces_Anonyme, $__VA_identifieurs,$__VA_mess_identifieur,$__VAcces_PageAppelee;
  header( 'Cache-Control: no-cache' );
  header( 'Cache-Control: must-revalidate' );
  header( 'Pragma: no-cache' );
  header( 'Expires: 0' );
  $__VApage = $__VAcces_PageAppelee;
  if ($__VA_mess_identifieur) $__VA_mess_identifieur.=" :"; 
    ?>
  <html>
  <head>

<link rel="shortcut icon" href="http://bu.univ-lorraine.fr/sites/all/themes/BU/favicon.ico" type="image/vnd.microsoft.icon" />
  <title>Identification</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  
  <SCRIPT TYPE="text/javascript">
<!--
function submitenter(myfield,e)
{
var keycode;
if (window.event) keycode = window.event.keyCode;
else if (e) keycode = e.which;
else return true;

if (keycode == 13)
   {
   myfield.form.submit();
   return false;
   }
else
   return true;
}
//-->
</SCRIPT>

  </head>
  <body topmargin="0" marginwidth="0" marginheight="0" <?=$fond?>>
 <div align=center>
  <img src="some_image.jpg" alt="" />

<p> </p>

<p><?=$__VA_mess_identifieur?></p>
... 
  <form action="<?=$__VApage?>" method="post">
  <input type=hidden name=demarre value=1>
	<input type=hidden name=autres_identifieurs value="">
    <input type=hidden name=identifieur value="">
    <?
  if ($mess != "")
    echo "\n  Next try      <font COLOR=red><b>$mess</b></font>\n";
  if ($__VAcces_Anonyme)
    $__VA_identifieurs['AUCUN']= array ("libelle"=> "Aucun"); 
              
    
  if (count($__VA_identifieurs)==1)    {
    foreach ($__VA_identifieurs as $entree=>$desc)
      {
      echo ' You can only use   <input type=button value="'.$desc['libelle'].'" onClick="'.
                 "this.form.identifieur.value='$entree';this.form.submit();". '">';
      break;
      }
    }
  else
    {
?>
      Select your organiszation :
        <select name="identifieur" onChange="this.form.submit();" onKeyPress="return submitenter(this,event)">
<?
	    foreach ($__VA_identifieurs as $entree=>$desc)  {
	      echo "<option value=\"$entree\">".$desc['libelle']."</option>\n";
	    }
?>
        </select>
        <input TYPE="image" src="ok.png" onClick="this.form.submit();" value="Acc&eacute;der ..." name="ok">
<?
    }
?>
</form>
  </div>
  </body>
  </html>
<?
  exit();
  }
  
/**
 * 
 * Custumisation of the login form
 * 
 */
  
  function my_login_page ($mess=''){
  	ma_demande_idt($mess);
  }
function ma_demande_idt($mess='')
  {
  global $__VAcces_Anonyme
         ,$__VAcces_PageAppelee;
    header( 'Cache-Control: no-cache' );
    header( 'Cache-Control: must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
      $__VApage = $__VAcces_PageAppelee;
  ?>
  <html>
  <head>
  <title>identification</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  </head>
  <body>
 <form action="<?=$__VApage?>" method="post">
  <input type=hidden name=demarre value=1>
<?
    if ($mess != "")
      echo "\n        <div id=mess>$mess</div>\n";
    if ($__VAcces_Anonyme)
     echo "<td> ".
          "    <input type=hidden name=anonyme value=\"\">\n".
          "    <input type=button value='$__VA_mess_anonyme' onClick=".
                '"this.form.anonyme.value=1;this.form.submit();"'."> ou\n".
          "</td>\n";

?>
        Login <input TYPE="text" name="ident_utilisateur"><br />
        Password    <input TYPE="password" name="mdp_utilisateur"> 
        <td align=center><input TYPE="submit" value="Go" "name="ok"></td>
     </tr>
  </table>
  </form>
  </body>
  </html>
<?
    exit();
  }
