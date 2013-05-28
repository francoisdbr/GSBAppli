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

// acquisition des entrées lors des validations de formulaire
$etape=lireDonneePost("etape","");
// aquisition pour l'étape validerMois et validerConsult
$visiteurSaisi = lireDonneePost("lstVisiteurs", "");
// acquisition pour l'étape validerConsult
$moisSaisi=lireDonneePost("lstMois", "");
// acquisition pour l'étape validerForfait
$tabQteEltsForfait=lireDonneePost("txtEltsForfait", "");
// acquisition pour l'étape validerRefusLigneHF
$idLigneHF = lireDonneePost("idLigneHF", "");
// acquisition pour l'étape validerDetails
$justificatifs = lireDonneePost("txtJustificatifsFicheFrais", "");
//var_dump($_POST);

if ($etape != "validerVisiteur" && $etape != "validerMois" && $etape != "validerConsult" 
    && $etape!="validerForfait" && $etape!="validerRefusLigneHF" 
    && $etape != "validerReportLigneHF" && $etape != "validerFicheFrais") {
    // si autre valeur, on considère que c'est le début du traitement
    $etape = "validerVisiteur";        
} 
  
// structure de décision sur les différentes étapes du cas d'utilisation
if ( $etape != "validerVisiteur" && $etape != "validerMois" ) {              
    if ($etape == "validerConsult") {
        $tabFicheFrais = obtenirDetailFicheFrais($idConnexion, $moisSaisi, $visiteurSaisi);
    }
    elseif ($etape == "validerForfait") {
        // l'utilisateur valide les éléments forfaitisés         
        // vérification des quantités des éléments forfaitisés
        $ok = verifierEntiersPositifs($tabQteEltsForfait);      
        if (!$ok) {
            ajouterErreur($tabErreurs, "Chaque quantité doit être renseignée et numérique positive.");
        } 
        else { // mise à jour des quantités des éléments forfaitisés
            modifierEltsForfait($idConnexion, $moisSaisi, $visiteurSaisi, $tabQteEltsForfait);
            modifierJustificatifsFicheFrais($idConnexion, $moisSaisi, $visiteurSaisi, $justificatifs);
        }
    }
    elseif ($etape == "validerRefusLigneHF") {
        modifierRefuserHorsForfait($idConnexion, $idLigneHF);
    }
    elseif ($etape == "validerReportLigneHF") {
        $moisSuivant = convertirMoisSuivant($moisSaisi);
        echo $moisSuivant;
        if ( ! existeFicheFrais($idConnexion, $moisSuivant, $visiteurSaisi)) {
            ajouterFicheFrais($idConnexion, $moisSuivant, $visiteurSaisi);
        }       
        modifierDateLigneHorsForfait($idConnexion, $idLigneHF, $moisSuivant);  
    }
    elseif ($etape == "validerFicheFrais") {
        modifierEtatFicheFrais($idConnexion, $moisSaisi, $visiteurSaisi, "VA");
        $etape = "validerVisiteur";
    }
    $montantForfait = obtenirMontantForfait($visiteurSaisi, $moisSaisi, $idConnexion);
    $montantHorsForfait = obtenirMontantHorsForfait($visiteurSaisi, $moisSaisi, $idConnexion);
    $montantValide = $montantForfait + $montantHorsForfait;
    modifierMontantFicheFrais($idConnexion, $visiteurSaisi, $moisSaisi, $montantValide);
}
else { // on ne fait rien, étape non prévue 
}

?>
<script language="javascript">
    function Submit($idForm) {
        document.getElementById($idForm).submit();
    }
    function SubmitAlert($idForm, $alertMessage) {
        if (confirm($alertMessage)) {
            document.getElementById($idForm).submit();
        }
    }
</script>

<!-- Division principale -->
<div id="contenu">
    <h2>Valider fiches de frais</h2>
    
<?php
if (nbErreurs($tabErreurs) > 0) {
    echo toStringErreurs($tabErreurs);
    } 
    elseif ($etape == "validerForfait" || $etape == "validerFicheFrais") {
?>
    <p class="info">Les modifications de la fiche de frais ont bien été enregistrées</p>        
<?php        
}
    // cloture de toutes les fiches de frais du mois en cours s'il existe des fiches non cloturees
if (obtenirSiFicheFraisCreee($idConnexion)) {
    modifierEtatClotureFicheFrais($idConnexion);
}
?>  
    <div class="corpsForm">
        <form id ="name" action="" method="post">        
            <input type="hidden" name="etape" value="validerMois" />            
            <p>
                <label for="lstVisiteurs">Visiteur : </label>
                <select id="lstVisiteurs" name="lstVisiteurs" onChange="Submit('name')" 
                        title="Sélectionnez le visiteur souhaité pour la fiche de frais">
                    <option disabled <?php if ($visiteurSaisi == "") { echo ("selected"); } ?>>
                        Choix du visiteur</option>
                <?php
                    $req = obtenirReqIdentiteVisiteurs();
                    $idJeuVisiteurs = mysql_query($req, $idConnexion);
                    $lgVisiteur = mysql_fetch_assoc($idJeuVisiteurs);
                    while ( is_array($lgVisiteur) ) {
                        $idVisiteur = $lgVisiteur["id"];
                        $nomVisiteur = $lgVisiteur["nom"];
                        $prenomVisiteur = $lgVisiteur["prenom"];
                        ?>   
                    <option value="<?php echo($idVisiteur); ?>" <?php if ($idVisiteur == $visiteurSaisi) 
                        { echo ("selected"); } ?>><?php echo ($nomVisiteur." ".$prenomVisiteur); ?></option>
                        <?php
                        $lgVisiteur = mysql_fetch_assoc($idJeuVisiteurs);        
                    }
                    mysql_free_result($idJeuVisiteurs);

                ?>
                </select>
            </p>

        </form>
<?php
if ($etape != "validerVisiteur") {
?>
        <form id="month" action="" method="post">
            <input type="hidden" name="etape" value="validerConsult" />
            <input type="hidden" name="lstVisiteurs" value="<?php echo $visiteurSaisi; ?>" />
            <p>
                <label for="lstMois">Mois : </label>
                <select id="lstMois" name="lstMois" onChange="Submit('month')" 
                        title="Sélectionnez le mois souhaité pour la fiche de frais">
                    <option disabled <?php if ($moisSaisi == "") { echo ("selected"); } ?>>Choisir le mois</option>
                    <?php
                // on propose tous les mois pour lesquels le visiteur séléctionné a une fiche non remboursée
                $req = obtenirReqMoisFicheFraisEnCours($visiteurSaisi);
                $idJeuMois = mysql_query($req, $idConnexion);
                $lgMois = mysql_fetch_assoc($idJeuMois);
                while ( is_array($lgMois) ) {
                    $mois = $lgMois["mois"];
                    $noMois = intval(substr($mois, 4, 2));
                    $annee = intval(substr($mois, 0, 4));
                ?>    
                    <option value="<?php echo $mois; ?>" <?php if ($mois == $moisSaisi) 
                        { echo ("selected"); } ?>><?php echo obtenirLibelleMois($noMois) . " " . $annee; ?></option>
                <?php
                    $lgMois = mysql_fetch_assoc($idJeuMois);        
                }
                mysql_free_result($idJeuMois);
                ?>
                </select>
            </p>
        </form>
<?php
}
?>
    </div>
    <p></p>
        
<?php 

// demande et affichage des différents éléments (forfaitisés et non forfaitisés)
// de la fiche de frais demandée
if ( $etape != "validerVisiteur" && $etape != "validerMois") {
?>

    <div class="corpsForm">
        <form action="" method="post">
            <input type="hidden" name="etape" value="validerForfait" />
            <input type="hidden" name="lstVisiteurs" value="<?php echo $visiteurSaisi; ?>" />
            <input type="hidden" name="lstMois" value="<?php echo $moisSaisi; ?>" />
            <fieldset>
                <legend>Détails de la fiche de frais</legend>
                <?php 
                // demande de la requête pour obtenir la liste des éléments 
                // sur la fiche de frais du visiteur pour le mois concerné
                $lgJeuRes = obtenirDetailFicheFrais($idConnexion, $moisSaisi, $visiteurSaisi);
                ?>
                <p>
                    <label for="montantValide">* Montant validé : </label>
                        <input disabled type="text" size="10" id="montantValide" 
                               title="Montant total des frais" value="<?php echo ($lgJeuRes["montantValide"]); ?>" />
                </p>
                <p>
                    <label for="nbJustificatifs">* Nombre de justificatifs reçus : </label>
                    <input type="text" id="nbJusitifcatifs" 
                           name="txtJustificatifsFicheFrais" 
                           size="10" maxlength="5"
                           title="Entrez le nombre de justificatifs reçus" 
                           value="<?php echo ($lgJeuRes["nbJustificatifs"]); ?>" />
                </p>
            </fieldset>
            <fieldset>
                <legend>Eléments forfaitisés</legend>
                <?php 
                // demande de la requête pour obtenir la liste des éléments 
                // forfaitisés du visiteur connecté pour le mois demandé
                $req = obtenirReqEltsForfaitFicheFrais($moisSaisi, $visiteurSaisi);
                $idJeuEltsFraisForfait = mysql_query($req, $idConnexion);
                echo mysql_error($idConnexion);
                $lgEltForfait = mysql_fetch_assoc($idJeuEltsFraisForfait);
                while ( is_array($lgEltForfait) ) {
                    $idFraisForfait = $lgEltForfait["idFraisForfait"];
                    $libelle = $lgEltForfait["libelle"];
                    $quantite = $lgEltForfait["quantite"];
                ?>
                <p>
                    <label for="<?php echo $idFraisForfait ?>">* <?php echo $libelle; ?> : </label>
                    <input type="text" id="<?php echo $idFraisForfait ?>" 
                           name="txtEltsForfait[<?php echo $idFraisForfait ?>]" 
                           size="10" maxlength="5"
                           title="Entrez la quantité de l'élément forfaitisé" 
                           value="<?php echo $quantite; ?>" />
                </p>
                <?php        
                $lgEltForfait = mysql_fetch_assoc($idJeuEltsFraisForfait);   
                }
                mysql_free_result($idJeuEltsFraisForfait);
                ?>
                <p>
                <input id="ok" type="submit" value="Valider" size="20" 
                       title="Enregistrer les nouvelles valeurs des éléments forfaitisés" />
                </p> 
            </fieldset>
        </form>
            <fieldset>
                <legend>Eléments hors forfait</legend>
                <table class="listeLegere">
                    <tr>
                        <th class="date">Date</th>
                        <th class="libelle">Libellé</th>
                        <th class="montant">Montant</th>  
                        <th class="action">&nbsp;</th> 
                        <th class="action">&nbsp;</th>
                    </tr>
           
<?php
    // demande de la requête pour obtenir la liste des éléments hors
    // forfait du visiteur connecté pour le mois demandé
    $req = obtenirReqEltsHorsForfaitFicheFrais($moisSaisi, $visiteurSaisi);
    $idJeuEltsHorsForfait = mysql_query($req, $idConnexion);
    $lgEltHorsForfait = mysql_fetch_assoc($idJeuEltsHorsForfait);
          
    // parcours des frais hors forfait du visiteur connecté
    while ( is_array($lgEltHorsForfait) ) {
        $idForm = $lgEltHorsForfait["id"];
?>
                    <tr>
                        <td><?php echo convertirDateAnglaisVersFrancais($lgEltHorsForfait["date"]) ; ?></td>
                        <td><?php echo filtrerChainePourNavig($lgEltHorsForfait["libelle"]) ; ?></td>
                        <td><?php echo $lgEltHorsForfait["montant"] ; ?></td>
                        <td>
                            <form action="" method="post" id="supprimer<?php echo $idForm; ?>">
                                <a href ="#" onClick="SubmitAlert('supprimer<?php echo $idForm; ?>', 'Voulez-vous vraiment refuser cette ligne de frais hors forfait au mois suivant ?')"
                                   title="Refuser la ligne de frais hors forfait">Refuser</a>
                                <input type="hidden" name="idLigneHF" value="<?php echo $idForm; ?>" />
                                <input type="hidden" name="lstMois" value="<?php echo $moisSaisi; ?>" />
                                <input type="hidden" name="etape" value="validerRefusLigneHF" />
                                <input type="hidden" name="lstVisiteurs" value="<?php echo $visiteurSaisi; ?>" />
                            </form>
                        </td>
                        <td>
                            <form action="" method="post" id="reporter<?php echo $idForm; ?>">
                                <a href ="#" onClick="SubmitAlert('reporter<?php echo $idForm; ?>', 'Voulez-vous vraiment reporter cette ligne de frais hors forfait ?')"
                                   title="Reporter la ligne de frais hors forfait">Reporter</a>
                                <input type="hidden" name="idLigneHF" value="<?php echo $idForm; ?>" />
                                <input type="hidden" name="lstMois" value="<?php echo $moisSaisi; ?>" />
                                <input type="hidden" name="etape" value="validerReportLigneHF" />
                                <input type="hidden" name="lstVisiteurs" value="<?php echo $visiteurSaisi; ?>" />
                            </form>
                        </td>
                    </tr>
<?php
    $lgEltHorsForfait = mysql_fetch_assoc($idJeuEltsHorsForfait);
    }
    mysql_free_result($idJeuEltsHorsForfait);
?>
            </table>  
        </fieldset>
        <form action="" method="post">
            <p>
                <input type="hidden" name="lstMois" value="<?php echo $moisSaisi; ?>" />
                <input type="hidden" name="lstVisiteurs" value="<?php echo $visiteurSaisi; ?>" />
                <input type="hidden" name="etape" value="validerFicheFrais" />
                <input id="ok" type="submit" value="Validation de la fiche" size="20" 
                       title="Validation des informations de la fiche de frais" />
            </p>
        </form>
    </div>
            
        
<?php
}
?>    
</div>
<?php        
require($repInclude . "_pied.inc.html");
require($repInclude . "_fin.inc.php");
?> 
          
