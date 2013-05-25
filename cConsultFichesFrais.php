<?php
/** 
 * Script de contr��le et d'affichage du cas d'utilisation "Consulter une fiche de frais"
 * @package default
 * @todo  RAS
 */
  $repInclude = './include/';
  require($repInclude . "_init.inc.php");
  require($repInclude . "_creerPdf.inc.php");
  
  // redirection pour telechargement du pdf si demande faite

  // page inaccessible si visiteur non connect��
  if ( ! estVisiteurConnecte() ) {
      header("Location: cSeConnecter.php");  
  }


  
  // acquisition des donn��es entr��es, ici le num��ro de mois et l'��tape du traitement
  $moisSaisi=lireDonneePost("lstMois", "");
  $idUser = lireDonneePost("lstVisiteur", obtenirIdUserConnecte());
  $etape= lireDonneePost("etape","");
  var_dump($_POST);
  
  //$idVisiteur = obtenirIdUserConnecte(); 

  if ($etape != "demanderConsult" && $etape != "validerConsult" && $etape != "validerPdf") {
      // si autre valeur, on consid��re que c'est le d��but du traitement
      $etape = "demanderConsult";        
  } 
  if ($etape == "validerConsult" || $etape == "validerPdf") { // l'utilisateur valide ses nouvelles donn��es
      
        // si comptable connecté (lien à partir du suivi de fiches de frais), consultation de la feuille demandée 
      //$metierUtilisateur = obtenirDetailVisiteur($idConnexion, $idVisiteur);
    //  if ($metier == "C") {
   //       $idUser = $visiteurSaisi;
    //  }

      // v��rification de l'existence de la fiche de frais pour le mois demand��      
      $existeFicheFrais = existeFicheFrais($idConnexion, $moisSaisi, $idUser);
      // si elle n'existe pas, on la cr��e avec les ��lets frais forfaitis��s �� 0
      if ( !$existeFicheFrais ) {
          ajouterErreur($tabErreurs, "Le mois demand�� est invalide");
      }
      else {
          // r��cup��ration des donn��es sur la fiche de frais demand��e
          $tabFicheFrais = obtenirDetailFicheFrais($idConnexion, $moisSaisi, $idUser);
          if ($etape == "validerPdf") {
              $identiteVisiteur = obtenirDetailVisiteur($idConnexion, $idUser);
$fileName = "pdf/Fiche_de_Frais_".obtenirLibelleMois(intval(substr($moisSaisi,4,2))) . "_" . substr($moisSaisi,0,4)."_". $identiteVisiteur["nom"] ."_". $identiteVisiteur["prenom"] .".pdf";
$file = $_SERVER['DOCUMENT_ROOT']."GSB_Appli/".$fileName;
//if ( ! file_exists($file)) {
              creerPdf($idConnexion, $identiteVisiteur, $moisSaisi, $fileName, $tabFicheFrais, $idConnexion);
//}
              // Download file
              
   header('Content-Type: application/pdf');
   header('Content-Disposition: attachment; filename='.basename($file));
   readfile($file);

          } 
      }
  }  
  require($repInclude . "_entete.inc.html");
  require($repInclude . "_sommaire.inc.php");
?>
  <!-- Division principale -->
  <div id="contenu">
      <h2>Mes fiches de frais</h2>
<?php
if ($etape == "demanderConsult") {
?>
      <h3>Mois �� s��lectionner : </h3>
      <form action="" method="post">
      <div class="corpsForm">
          <input type="hidden" name="etape" value="validerConsult" />
      <p>
        <label for="lstMois">Mois : </label>
        <select id="lstMois" name="lstMois" title="S��lectionnez le mois souhait�� pour la fiche de frais">
            <?php
                // on propose tous les mois pour lesquels le visiteur a une fiche de frais
                $req = obtenirReqMoisFicheFrais($idUser);
                $idJeuMois = mysql_query($req, $idConnexion);
                $lgMois = mysql_fetch_assoc($idJeuMois);
                while ( is_array($lgMois) ) {
                    $mois = $lgMois["mois"];
                    $noMois = intval(substr($mois, 4, 2));
                    $annee = intval(substr($mois, 0, 4));
            ?>    
            <option value="<?php echo $mois; ?>"<?php if ($moisSaisi == $mois) { ?> selected="selected"<?php } ?>><?php echo obtenirLibelleMois($noMois) . " " . $annee; ?></option>
            <?php
                    $lgMois = mysql_fetch_assoc($idJeuMois);        
                }
                mysql_free_result($idJeuMois);
            ?>
        </select>
      </p>
      </div>
      <div class="piedForm">
      <p>
        <input id="ok" type="submit" value="Valider" size="20"
               title="Demandez �� consulter cette fiche de frais" />
        <input id="annuler" type="reset" value="Effacer" size="20" />
      </p> 
      </div>
        
      </form>
<?php      
}
// demande et affichage des diff��rents ��l��ments (forfaitis��s et non forfaitis��s)
// de la fiche de frais demand��e, uniquement si pas d'erreur d��tect�� au contr��le
    if ( $etape == "validerConsult" || $etape == "validerPdf") {
        if ($metier == "C") {
    ?>
      <p><a href="cSuiviFichesFrais.php">Retour au suivi des fiches de frais</a></p>
    <?php
        }
        if ( nbErreurs($tabErreurs) > 0 ) {
            echo toStringErreurs($tabErreurs) ;
        }
        else {
            
?>
    <h3>Fiche de frais du mois de <?php echo obtenirLibelleMois(intval(substr($moisSaisi,4,2))) . " " . substr($moisSaisi,0,4); ?> : 
    <em><?php echo $tabFicheFrais["libelleEtat"]; ?> </em>
    depuis le <em><?php echo $tabFicheFrais["dateModif"]; ?></em></h3>
    <div class="encadre">
    <p>Montant valid�� : <?php echo $tabFicheFrais["montantValide"] ;
        ?>              
    </p>
<?php          
            // demande de la requ��te pour obtenir la liste des ��l��ments 
            // forfaitis��s du visiteur connect�� pour le mois demand��
            $req = obtenirReqEltsForfaitFicheFrais($moisSaisi, $idUser);
            $idJeuEltsFraisForfait = mysql_query($req, $idConnexion);
            echo mysql_error($idConnexion);
            $lgEltForfait = mysql_fetch_assoc($idJeuEltsFraisForfait);
            // parcours des frais forfaitis��s du visiteur connect��
            // le stockage interm��diaire dans un tableau est n��cessaire
            // car chacune des lignes du jeu d'enregistrements doit ��tre doit ��tre
            // affich��e au sein d'une colonne du tableau HTML
            $tabEltsFraisForfait = array();
            while ( is_array($lgEltForfait) ) {
                $tabEltsFraisForfait[$lgEltForfait["libelle"]] = $lgEltForfait["quantite"];
                $lgEltForfait = mysql_fetch_assoc($idJeuEltsFraisForfait);
            }
            mysql_free_result($idJeuEltsFraisForfait);
            ?>
  	<table class="listeLegere">
  	   <caption>Quantit��s des ��l��ments forfaitis��s</caption>
        <tr>
            <?php
            // premier parcours du tableau des frais forfaitis��s du visiteur connect��
            // pour afficher la ligne des libell��s des frais forfaitis��s
            foreach ( $tabEltsFraisForfait as $unLibelle => $uneQuantite ) {
            ?>
                <th><?php echo $unLibelle ; ?></th>
            <?php
            }
            ?>
        </tr>
        <tr>
            <?php
            // second parcours du tableau des frais forfaitis��s du visiteur connect��
            // pour afficher la ligne des quantit��s des frais forfaitis��s
            foreach ( $tabEltsFraisForfait as $unLibelle => $uneQuantite ) {
            ?>
                <td class="qteForfait"><?php echo $uneQuantite ; ?></td>
            <?php
            }
            ?>
        </tr>
    </table>
  	<table class="listeLegere">
  	   <caption>Descriptif des ��l��ments hors forfait - <?php echo $tabFicheFrais["nbJustificatifs"]; ?> justificatifs re��us -
       </caption>
             <tr>
                <th class="date">Date</th>
                <th class="libelle">Libell��</th>
                <th class="montant">Montant</th>                
             </tr>
<?php          
            // demande de la requ��te pour obtenir la liste des ��l��ments hors
            // forfait du visiteur connect�� pour le mois demand��
            $req = obtenirReqEltsHorsForfaitFicheFrais($moisSaisi, $idUser);
            $idJeuEltsHorsForfait = mysql_query($req, $idConnexion);
            $lgEltHorsForfait = mysql_fetch_assoc($idJeuEltsHorsForfait);
            
            // parcours des ��l��ments hors forfait 
            while ( is_array($lgEltHorsForfait) ) {
            ?>
                <tr>
                   <td><?php echo $lgEltHorsForfait["date"] ; ?></td>
                   <td><?php echo filtrerChainePourNavig($lgEltHorsForfait["libelle"]) ; ?></td>
                   <td><?php echo $lgEltHorsForfait["montant"] ; ?></td>
                </tr>
            <?php
                $lgEltHorsForfait = mysql_fetch_assoc($idJeuEltsHorsForfait);
            }
            mysql_free_result($idJeuEltsHorsForfait);
  ?>
    </table>
<?php
        }
?>
    <p>
        <form action="" method="post">
            <input type="hidden" name="etape" value="validerPdf" />
            <input type="hidden" name="lstVisiteur" value="<?php echo $idUser; ?>" />
            <input type="hidden" name="lstMois" value="<?php echo $moisSaisi; ?>" /> 
            <input type="submit" value="Télécharger un PDF" size="20" title="Télécharger un PDF concernant la fiche de frais électionnée" />
      </form>
    </p>
<?php
    }
?>
    
  </div>
  
  </div>
<?php        
  require($repInclude . "_pied.inc.html");
  require($repInclude . "_fin.inc.php");
?> 