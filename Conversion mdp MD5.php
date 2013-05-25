<?php
$repInclude = './include/';
require($repInclude . "_init.inc.php");

//$requete = "ALTER TABLE  `Utilisateur` CHANGE  `mdp`  `mdp` CHAR( 30 )"

$req = "SELECT id, mdp from Utilisateur";
$idJeuMdp = mysql_query($req, $idConnexion);
while ($lgMdp = mysql_fetch_assoc($idJeuMdp)) {
    $mdp = md5($lgMdp["mdp"]);
    $id = $lgMdp["id"];
    $requete = "UPDATE Utilisateur SET mdp = '".$mdp."' WHERE id = '".$id."'";
    mysql_query($requete, $idConnexion);
}
mysql_free_result($idJeuMdp);
?>
