<?php
/** 
 * Contient la division pour le sommaire, sujet à des variations suivant la 
 * connexion ou non d'un utilisateur, et dans l'avenir, suivant le type de cet utilisateur 
 * @todo  RAS
 */

?>
    <!-- Division pour le sommaire -->
    <div id="menuGauche">
     <div id="infosUtil">
    <?php      
      if (estVisiteurConnecte() ) {
          $idUser = obtenirIdUserConnecte() ;
          $lgUser = obtenirDetailVisiteur($idConnexion, $idUser);
          $nom = $lgUser['nom'];
          $prenom = $lgUser['prenom'];
          $metier = $lgUser['metier'];
          if ($metier == 'V') {
              $profession = 'Visiteur médical';
          }else {
              $profession = 'Comptable';
          }
    ?>
        <h2>
    <?php  
            echo $nom . " " . $prenom ;
    ?>
        </h2>
        <h3>
    <?php
            echo $profession;
    ?>
        </h3>        
    <?php
       }
    ?>  
      </div>  
<?php      
    if (estVisiteurConnecte() ) {
?>
        <ul id="menuList">
           <li class="smenu">
              <a href="cAccueil.php" title="Page d'accueil">Accueil</a>
           </li>
           <li class="smenu">
              <a href="cSeDeconnecter.php" title="Se déconnecter">Se déconnecter</a>
           </li>
<?php
        if ($metier == 'V') {
?>
           <li class="smenu">
              <a href="cSaisieFicheFrais.php" title="Saisie fiche de frais du mois courant">Saisie fiche de frais</a>
           </li>
           <li class="smenu">
              <a href="cConsultFichesFrais.php" title="Consultation de mes fiches de frais">Mes fiches de frais</a>
           </li>
        </ul>
<?php
        }else {
?>            
           <li class="smenu">
              <a href="cValideFichesFrais.php" title="Valider fiches de frais">Valider fiches de frais</a>
           </li>
           <li class="smenu">
              <a href="cSuiviFichesFrais.php" title="Suivi de paiement">Suivi de Paiement</a>
           </li> 
         </ul>
        <?php
        }
          // affichage des éventuelles erreurs déjà détectées
          if ( nbErreurs($tabErreurs) > 0 ) {
              echo toStringErreurs($tabErreurs) ;
          }
  }
        ?>
    </div>
    