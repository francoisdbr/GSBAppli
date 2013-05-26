<?php
/** 
 * Script de contrôle et d'affichage du cas d'utilisation "Consulter une fiche de frais"
 * @package default
 * @todo  RAS
 */
$repInclude = './include/';
require($repInclude . "_init.inc.php");

// page inaccessible si visiteur non connecté
if ( ! estVisiteurConnecte() ) {
    header("Location: cSeConnecter.php");  
}
require($repInclude . "_entete.inc.html");
require($repInclude . "_sommaire.inc.php");

$etape = lireDonneePost("etape", "");
$moisSaisi = substr(lireDonneePost("lstMoisVisiteur", ""), 0, 6);
$visiteurSaisi = substr(lireDonneePost("lstMoisVisiteur", ""), 7);

if ($etape == "validerPaiement") {
    modifierEtatFicheFrais($idConnexion, $moisSaisi, $visiteurSaisi,"RB");
    ?>
    <p class="info">Les modifications de la fiche de frais ont bien été enregistrées</p>
    <?php
}
?>
<!-- script pour envoyer le formulaire lien avec toutes les inforamtions POST nécessaires -->
<script language="javascript">
    function Go($idForm) {
        document.getElementById($idForm).submit();
    }
</script>

<!-- Division principale -->
<div id="contenu">
    <h2>Suivi des paiements</h2>
    <div>
        <form id="paiement" action="" method="post">
            <input type="hidden" name="etape" value="validerPaiement">
            <fieldset>
                <legend>Fiches de frais validées à mettre en paiement</legend>
                <table class="listeLegere">
                    <thead>
                        <tr>
                            <th class="center" rowspan="2" val>Détails</th>
                            <th class="center" rowspan="2">Nom/prenom du visiteur médical</th>
                            <th class="center" rowspan="2">Mois/année</th>
                            <th class="center" colspan="3">Résumé de la fiche de frais</th>    
                            <th class="center" rowspan="2">Mise en paiement</th>
                        </tr>
                        <tr>
                            <th class="center">Forfait</th>
                            <th class="center">Hors forfait</th>
                            <th class="center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // récupération des fiches de frais validées à mettre en paiement
                        $req = obtenirReqFicheFraisCloturees();
                        $idJeuFicheFraisCloturees = mysql_query($req, $idConnexion);
                        while ($lgFicheFraiscloturees = mysql_fetch_assoc($idJeuFicheFraisCloturees)) {                                                   
                            $idVisiteur = $lgFicheFraiscloturees["idVisiteur"];
                            $mois = $lgFicheFraiscloturees["mois"];
                            $total = $lgFicheFraiscloturees["montantValide"];
                            $nomVisiteur = $lgFicheFraiscloturees["nom"];
                            $prenomVisiteur = $lgFicheFraiscloturees["prenom"];
                            $montantForfait = obtenirMontantForfait($idVisiteur, $mois, $idConnexion);
                            $montantHorsForfait = obtenirMontantHorsForfait($idVisiteur, $mois, $idConnexion);
                            if ($total == 0) {
                                $montantValide = $montantForfait + $montantHorsForfait;
                                modifierMontantFicheFrais($idConnexion, $idVisiteur, $mois, $montantValide);
                            }
                            ?>
                        <tr>
                            <form id="lien<?php echo ($mois.$idVisiteur); ?>" method="post" 
                                  action="cConsultFichesFrais.php">
                                <td><a href="#" onClick="Go('lien<?php echo ($mois.$idVisiteur); ?>')">Lien</a></td>
                                <input type="hidden" name="etape" value="validerConsult">
                                <input type="hidden" name="lstMois" value="<?php echo $mois; ?>">
                                <input type="hidden" name="lstVisiteur" value="<?php echo $idVisiteur; ?>">
                            </form>                            
                            <td><?php echo ($nomVisiteur." ".$prenomVisiteur); ?></td>
                            <td><?php echo (substr($mois, 4)." / ".substr($mois, 0, 4)); ?></td>
                            <td><?php echo $montantForfait; ?></td>
                            <td><?php echo $montantHorsForfait; ?></td>
                            <td><?php echo $total; ?></td>
                            <td><button class="large"type="submit" form="paiement" name ="lstMoisVisiteur" 
                                        value="<?php echo ($mois." ".$idVisiteur); ?>">Mise en paiement</button></td>
                        </tr>
                            <?php
                        }
                        mysql_free_result($idJeuFicheFraisCloturees);
                        ?>
                    </tbody>
                </table>
            </fieldset>
        </form>
    </div>
</div>
<?php        
require($repInclude . "_pied.inc.html");
require($repInclude . "_fin.inc.php");
?> 

