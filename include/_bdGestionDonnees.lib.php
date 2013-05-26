<?php
/** 
 * Regroupe les fonctions d'accès aux données.
 * @package default
 * @author Arthur Martin
 * @todo Fonctions retournant plusieurs lignes sont à réécrire.
 */

/** 
 * Se connecte au serveur de données MySql.                      
 * Se connecte au serveur de données MySql à partir de valeurs
 * prédéfinies de connexion (hôte, compte utilisateur et mot de passe). 
 * Retourne l'identifiant de connexion si succès obtenu, le booléen false 
 * si problème de connexion.
 * @return resource identifiant de connexion
 */
function connecterServeurBD() {
    $hote = "localhost";
    $login = "userGsb";
    $mdp = "secret";
    return mysql_connect($hote, $login, $mdp);
}

/**
 * Sélectionne (rend active) la base de données.
 * Sélectionne (rend active) la BD prédéfinie gsb_frais sur la connexion
 * identifiée par $idCnx. Retourne true si succès, false sinon.
 * @param resource $idCnx identifiant de connexion
 * @return boolean succès ou échec de sélection BD 
 */
function activerBD($idCnx) {
    $bd = "gsb_frais";
    $query = "SET CHARACTER SET utf8";
    // Modification du jeu de caractères de la connexion
    $res = mysql_query($query, $idCnx); 
    $ok = mysql_select_db($bd, $idCnx);
    return $ok;
}

/** 
 * Ferme la connexion au serveur de données.
 * Ferme la connexion au serveur de données identifiée par l'identifiant de 
 * connexion $idCnx.
 * @param resource $idCnx identifiant de connexion
 * @return void  
 */
function deconnecterServeurBD($idCnx) {
    mysql_close($idCnx);
}

/**
 * Echappe les caractères spéciaux d'une chaîne.
 * Envoie la chaîne $str échappée, càd avec les caractères considérés spéciaux
 * par MySql (tq la quote simple) précédés d'un \, ce qui annule leur effet spécial
 * @param string $str chaîne à échapper
 * @return string chaîne échappée 
 */    
function filtrerChainePourBD($str) {
    if ( ! get_magic_quotes_gpc() ) { 
        // si la directive de configuration magic_quotes_gpc est activée dans php.ini,
        // toute chaîne reçue par get, post ou cookie est déjà échappée 
        // par conséquent, il ne faut pas échapper la chaîne une seconde fois                              
        $str = mysql_real_escape_string($str);
    }
    return $str;
}

/** 
 * Fournit les informations sur un visiteur demandé. 
 * Retourne les informations du visiteur d'id $unId sous la forme d'un tableau
 * associatif dont les clés sont les noms des colonnes(id, nom, prenom).
 * @param resource $idCnx identifiant de connexion
 * @param string $unId id de l'utilisateur
 * @return array  tableau associatif du visiteur
 */
function obtenirDetailVisiteur($idCnx, $unId) {
    $id = filtrerChainePourBD($unId);
    $requete = "select id, nom, prenom, metier from Utilisateur where id='" . $unId . "'";
    $idJeuRes = mysql_query($requete, $idCnx);  
    $ligne = false;     
    if ( $idJeuRes ) {
        $ligne = mysql_fetch_assoc($idJeuRes);
        mysql_free_result($idJeuRes);
    }
    return $ligne ;
}

/** 
 * Fournit les informations d'une fiche de frais. 
 * Retourne les informations de la fiche de frais du mois de $unMois (MMAAAA)
 * sous la forme d'un tableau associatif dont les clés sont les noms des colonnes
 * (nbJustitificatifs, idEtat, libelleEtat, dateModif, montantValide).
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return array tableau associatif de la fiche de frais
 */
function obtenirDetailFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $ligne = false;
    $requete="select IFNULL(nbJustificatifs,0) as nbJustificatifs, Etat.id as idEtat, 
              libelle as libelleEtat, dateModif, montantValide 
    from FicheFrais inner join Etat on idEtat = Etat.id 
    where idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";
    $idJeuRes = mysql_query($requete, $idCnx);  
    if ( $idJeuRes ) {
        $ligne = mysql_fetch_assoc($idJeuRes);
    }        
    mysql_free_result($idJeuRes);
    
    return $ligne ;
}
              
/** 
 * Vérifie si une fiche de frais existe ou non. 
 * Retourne true si la fiche de frais du mois de $unMois (MMAAAA) du visiteur 
 * $idVisiteur existe, false sinon. 
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return booléen existence ou non de la fiche de frais
 */
function existeFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $requete = "select idVisiteur from FicheFrais where idVisiteur='" . $unIdVisiteur . 
              "' and mois='" . $unMois . "'";
    $idJeuRes = mysql_query($requete, $idCnx);  
    $ligne = false ;
    if ( $idJeuRes ) {
        $ligne = mysql_fetch_assoc($idJeuRes);
        mysql_free_result($idJeuRes);
    }        
    
    // si $ligne est un tableau, la fiche de frais existe, sinon elle n'exsite pas
    return is_array($ligne) ;
}

/** 
 * Fournit le mois de la dernière fiche de frais d'un visiteur.
 * Retourne le mois de la dernière fiche de frais du visiteur d'id $unIdVisiteur.
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdVisiteur id visiteur  
 * @return string dernier mois sous la forme AAAAMM
 */
function obtenirDernierMoisSaisi($idCnx, $unIdVisiteur) {
	$requete = "select max(mois) as dernierMois from FicheFrais where idVisiteur='" .
            $unIdVisiteur . "'";
	$idJeuRes = mysql_query($requete, $idCnx);
    $dernierMois = false ;
    if ( $idJeuRes ) {
        $ligne = mysql_fetch_assoc($idJeuRes);
        $dernierMois = $ligne["dernierMois"];
        mysql_free_result($idJeuRes);
    }        
	return $dernierMois;
}

/** 
 * Ajoute une nouvelle fiche de frais et les éléments forfaitisés associés, 
 * Ajoute la fiche de frais du mois de $unMois (MMAAAA) du visiteur 
 * $idVisiteur, avec les éléments forfaitisés associés dont la quantité initiale
 * est affectée à 0. Clôt éventuellement la fiche de frais précédente du visiteur. 
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return void
 */
function ajouterFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    // modification de la dernière fiche de frais du visiteur
    $dernierMois = obtenirDernierMoisSaisi($idCnx, $unIdVisiteur);
	$laDerniereFiche = obtenirDetailFicheFrais($idCnx, $dernierMois, $unIdVisiteur);
	if ( is_array($laDerniereFiche) && $laDerniereFiche['idEtat']=='CR'){
		modifierEtatFicheFrais($idCnx, $dernierMois, $unIdVisiteur, 'CL');
	}
    
    // ajout de la fiche de frais à l'état Créé
    $requete = "insert into FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, idEtat, dateModif) values ('" 
              . $unIdVisiteur 
              . "','" . $unMois . "',0,NULL, 'CR', '" . date("Y-m-d") . "')";
    mysql_query($requete, $idCnx);
    
    // ajout des éléments forfaitisés
    $requete = "select id from FraisForfait";
    $idJeuRes = mysql_query($requete, $idCnx);
    if ( $idJeuRes ) {
        $ligne = mysql_fetch_assoc($idJeuRes);
        while ( is_array($ligne) ) {
            $idFraisForfait = $ligne["id"];
            // insertion d'une ligne frais forfait dans la base
            $requete = "insert into LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite)
                        values ('" . $unIdVisiteur . "','" . $unMois . "','" . $idFraisForfait . "',0)";
            mysql_query($requete, $idCnx);
            // passage au frais forfait suivant
            $ligne = mysql_fetch_assoc ($idJeuRes);
        }
        mysql_free_result($idJeuRes);       
    }        
}

/**
 * Retourne le texte de la requête select concernant les mois pour lesquels un 
 * visiteur a une fiche de frais. 
 * 
 * La requête de sélection fournie permettra d'obtenir les mois (AAAAMM) pour 
 * lesquels le visiteur $unIdVisiteur a une fiche de frais. 
 * @param string $unIdVisiteur id visiteur  
 * @return string texte de la requête select
 */                                                 
function obtenirReqMoisFicheFrais($unIdVisiteur) {
    $req = "select fichefrais.mois as mois from  fichefrais where fichefrais.idvisiteur ='"
            . $unIdVisiteur . "' order by fichefrais.mois desc ";
    return $req ;
}  
                  
/**
 * Retourne le texte de la requête select concernant les éléments forfaitisés 
 * d'un visiteur pour un mois donnés. 
 * 
 * La requête de sélection fournie permettra d'obtenir l'id, le libellé et la
 * quantité des élèments forfaitisés de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois    
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return string texte de la requête select
 */                                                 
function obtenirReqEltsForfaitFicheFrais($unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $requete = "select idFraisForfait, libelle, quantite, montant from LigneFraisForfait
              inner join FraisForfait on FraisForfait.id = LigneFraisForfait.idFraisForfait
              where idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";
    return $requete;
}

/**
 * Retourne le texte de la requête select concernant les éléments hors forfait 
 * d'un visiteur pour un mois donnés. 
 * 
 * La requête de sélection fournie permettra d'obtenir l'id, la date, le libellé 
 * et le montant des éléments hors forfait de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois    
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return string texte de la requête select
 */                                                 
function obtenirReqEltsHorsForfaitFicheFrais($unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $requete = "select id, date, libelle, montant from LigneFraisHorsForfait
              where idVisiteur='" . $unIdVisiteur 
              . "' and mois='" . $unMois . "'";
    return $requete;
}

/**
 * Supprime une ligne hors forfait.
 * Supprime dans la BD la ligne hors forfait d'id $unIdLigneHF
 * @param resource $idCnx identifiant de connexion
 * @param string $idLigneHF id de la ligne hors forfait
 * @return void
 */
function supprimerLigneHF($idCnx, $unIdLigneHF) {
    $requete = "delete from LigneFraisHorsForfait where id = " . $unIdLigneHF;
    mysql_query($requete, $idCnx);
}

/**
 * Ajoute une nouvelle ligne hors forfait.
 * Insère dans la BD la ligne hors forfait de libellé $unLibelleHF du montant 
 * $unMontantHF ayant eu lieu à la date $uneDateHF pour la fiche de frais du mois
 * $unMois du visiteur d'id $unIdVisiteur
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (AAMMMM)
 * @param string $unIdVisiteur id du visiteur
 * @param string $uneDateHF date du frais hors forfait
 * @param string $unLibelleHF libellé du frais hors forfait 
 * @param double $unMontantHF montant du frais hors forfait
 * @return void
 */
function ajouterLigneHF($idCnx, $unMois, $unIdVisiteur, $uneDateHF, $unLibelleHF, $unMontantHF) {
    $unLibelleHF = filtrerChainePourBD($unLibelleHF);
    $uneDateHF = filtrerChainePourBD(convertirDateFrancaisVersAnglais($uneDateHF));
    $unMois = filtrerChainePourBD($unMois);
    $requete = "insert into LigneFraisHorsForfait(idVisiteur, mois, date, libelle, montant) 
                values ('" . $unIdVisiteur . "','" . $unMois . "','" . $uneDateHF . "','" . 
                $unLibelleHF . "'," . $unMontantHF .")";
    mysql_query($requete, $idCnx);
}

/**
 * Modifie les quantités des éléments forfaitisés d'une fiche de frais. 
 * Met à jour les éléments forfaitisés contenus  
 * dans $desEltsForfaits pour le visiteur $unIdVisiteur et
 * le mois $unMois dans la table LigneFraisForfait, après avoir filtré 
 * (annulé l'effet de certains caractères considérés comme spéciaux par 
 *  MySql) chaque donnée   
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA) 
 * @param string $unIdVisiteur  id visiteur
 * @param array $desEltsForfait tableau des quantités des éléments hors forfait
 * avec pour clés les identifiants des frais forfaitisés 
 * @return void  
 */
function modifierEltsForfait($idCnx, $unMois, $unIdVisiteur, $desEltsForfait) {
    $unMois=filtrerChainePourBD($unMois);
    $unIdVisiteur=filtrerChainePourBD($unIdVisiteur);
    foreach ($desEltsForfait as $idFraisForfait => $quantite) {
        $requete = "update LigneFraisForfait set quantite = " . $quantite 
                    . " where idVisiteur = '" . $unIdVisiteur . "' and mois = '"
                    . $unMois . "' and idFraisForfait='" . $idFraisForfait . "'";
      mysql_query($requete, $idCnx);
    }
}

/**
 * Contrôle les informations de connexionn d'un utilisateur.
 * Vérifie si les informations de connexion $unLogin, $unMdp sont ou non valides.
 * Retourne les informations de l'utilisateur sous forme de tableau associatif 
 * dont les clés sont les noms des colonnes (id, nom, prenom, login, mdp)
 * si login et mot de passe existent, le booléen false sinon. 
 * @param resource $idCnx identifiant de connexion
 * @param string $unLogin login 
 * @param string $unMdp mot de passe 
 * @return array tableau associatif ou booléen false 
 */
function verifierInfosConnexion($idCnx, $unLogin, $unMdp) {
    $unLogin = filtrerChainePourBD($unLogin);
    $unMdp = filtrerChainePourBD($unMdp);
    // le mot de passe est crypté dans la base avec la fonction de hachage md5
    $req = "select id, nom, prenom, login, mdp from Utilisateur where login='".
            $unLogin."' and mdp='" . $unMdp . "'";
    $idJeuRes = mysql_query($req, $idCnx);
    $ligne = false;
    if ( $idJeuRes ) {
        $ligne = mysql_fetch_assoc($idJeuRes);
        mysql_free_result($idJeuRes);
    }
    return $ligne;
}

/**
 * Modifie létat et la date de modification d'une fiche de frais
 
 * Met à jour l'état de la fiche de frais du visiteur $unIdVisiteur pour
 * le mois $unMois à la nouvelle valeur $unEtat et passe la date de modif à 
 * la date d'aujourd'hui
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdVisiteur 
 * @param string $unMois mois sous la forme aaaamm
 * @return void 
 */
function modifierEtatFicheFrais($idCnx, $unMois, $unIdVisiteur, $unEtat) {
    $requete = "update FicheFrais set idEtat = '" . $unEtat . 
               "', dateModif = now() where idVisiteur ='" .
               $unIdVisiteur . "' and mois = '". $unMois . "'";
    mysql_query($requete, $idCnx);
}

/**
 * Retourne une requête donnant les identités des visiteurs médicaux par ordre alphabétique
 * 
 * @return string
 */
function obtenirReqIdentiteVisiteurs() {
    $req = "select id, nom, prenom from Utilisateur where metier='V' order by nom asc";
    return $req;
}

/**
 * Retourne une requête avec les mois des fiches de frais remboursées pour le visiteur passé
 * en paramètre
 * 
 * @param string $idVisiteur id visiteur
 * @return string
 */
function obtenirReqMoisFicheFraisEnCours($idVisiteur) {
    $req = "select distinct FicheFrais.mois as mois from  FicheFrais 
           where idVisiteur = '".$idVisiteur."' and idEtat != 'RB'
           order by fichefrais.mois desc";
    return $req ;
}

/**
 * Change le libelle de la ligne hors forfait dont l'id est passé en paramètre en y ajoutant
 * REFUSE au début
 * 
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdLigneHF id de la ligne hors forfait
 */
function modifierRefuserHorsForfait($idCnx, $unIdLigneHF) {
    $reqLibelle = "select LigneFraisHorsForfait.libelle from LigneFraisHorsForfait where id='".
                  $unIdLigneHF."'";
    $idLibelle = mysql_query($reqLibelle, $idCnx);
    $ligne = false;
    if ( $idLibelle ) {
        $ligne = mysql_fetch_assoc($idLibelle);
        mysql_free_result($idLibelle);
    }
    $libelle = "REFUSE : ".$ligne["libelle"];
    $req = "update LigneFraisHorsForfait set libelle='".$libelle."' where id='".$unIdLigneHF."'";
    mysql_query($req, $idCnx);
}

/**
 * Change le nombre de justificatifs de la fiche de frais du visiteur et du mois passés en
 * paramètres, avec celui fourni en paramètre
 * 
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois sous la forme aaaamm
 * @param string $unIdVisiteur id visiteur
 * @param string $nbJustificatifs nouveau nombre de justificatifs
 */
function modifierJustificatifsFicheFrais($idCnx, $unMois, $unIdVisiteur, $nbJustificatifs) {
    $requete = "update FicheFrais set nbJustificatifs = '" . $nbJustificatifs . 
               "' where idVisiteur ='" .$unIdVisiteur . "' and mois = '". $unMois . "'";
    mysql_query($requete, $idCnx);
}

/**
 * Passe les fiches de frais créées en cloturées avec changement de la date de modification
 * 
 * @param resource $idCnx identifiant de connexion
 */
function modifierEtatClotureFicheFrais($idCnx) {
    $requete = "update FicheFrais set idEtat = 'CL', dateModif = now() where idEtat='CR';";
    mysql_query($requete, $idCnx);
}

/**
 * Retourne une requête contenant les informations sur les fiches de frais cloturées de 
 * l'utilisateur loggé
 * 
 * @return string
 */
function obtenirReqFicheFraisCloturees () {
    $requete = "select distinct FicheFrais.idVisiteur as idVisiteur, mois, montantValide, nom, prenom
                from FicheFrais
                join Utilisateur on FicheFrais.idVisiteur = Utilisateur.id
                where idEtat='CL';";
    return $requete;        
}

/**
 * Retourne le total des frais forfaitisés pour le visiteur et le mois passés en paramètre
 * 
 * @param string $idVisiteur id visiteur
 * @param string $mois mois sous la forme aaaamm
 * @param resource $idCnx identifiant de connexion
 * @return float total des frais forfaitisés
 */
function obtenirMontantForfait ($idVisiteur, $mois, $idCnx) {
    $total = 0;
    $req = "select FraisForfait.id as typeFrais, montant, quantite, coefficient
           from LigneFraisForfait inner join FraisForfait on LigneFraisForfait.idFraisForfait = FraisForfait.id 
           inner join Utilisateur on LigneFraisForfait.idVisiteur = Utilisateur.id 
           inner join Vehicule on Utilisateur.idVehicule = Vehicule.id
           where idVisiteur = '".$idVisiteur."' and mois = '".$mois."';";
    $idJeuForfait = mysql_query($req, $idCnx);
    while ($lgForfait = mysql_fetch_assoc($idJeuForfait)) {
        if ($lgForfait["typeFrais"] == 'KM') {
            $montant = $lgForfait["montant"]*$lgForfait["coefficient"];
        } else {
            $montant = $lgForfait["montant"];
        }
        $quantite = $lgForfait["quantite"];
        $total += $montant*$quantite;
    }
    return $total;
}

/**
 * Retourne le total des frais hors forfait pour le visiteur et le mois passés en paramètre
 * 
 * @param string $idVisiteur id visiteur
 * @param string $mois mois sous la forme aaaamm
 * @param resource $idCnx identifiant de connexion
 * @return float total des frais hors forfait
 */
function obtenirMontantHorsForfait ($idVisiteur, $mois, $idCnx) {
    $total = 0;
    $req = "select montant, libelle from LigneFraisHorsForfait
           where idVisiteur = '".$idVisiteur."' and mois = '".$mois."';";
    $idJeuHorsForfait = mysql_query($req, $idCnx);
    while ($lgHorsForfait = mysql_fetch_assoc($idJeuHorsForfait)) {
        $refus = substr($lgHorsForfait["libelle"], 0, 6);
        if ($refus != "REFUSE") {
            $total += $lgHorsForfait["montant"];
        }
    }
    return $total;
}

/**
 * Retourne un booléen vrai si une fiche de frais avec l'état CR est déjà créée
 * 
 * @param resource $idCnx identifiant de connexion
 * @return boolean
 */
function obtenirSiFicheFraisCreee ($idCnx) {
    $req = "select count(*) as quantite from FicheFrais where idEtat = 'CR'";
    $idJeuRep = mysql_query($req, $idCnx);
    $lgRep = mysql_fetch_assoc($idJeuRep);
    $rep = false;
    if ($lgRep["quantite"] != "0") {
        $rep = true;
    }
    return $rep;
}

/**
 * Modifie le montant de la fiche de frais concernant l'id visiteur et le mois passés en paramètres
 * par le montant passé en paramètre
 * 
 * @param resource $idCnx identifiant de connexion
 * @param string $unVisiteur id visiteur
 * @param string $unMois mois sous la forme aaaamm
 * @param string $unMontant nouveau montant
 */
function modifierMontantFicheFrais ($idCnx, $unVisiteur, $unMois, $unMontant) {
    $requete = "update FicheFrais set montantValide = '" . $unMontant . 
               "' where idVisiteur ='" .$unVisiteur . "' and mois = '". $unMois . "'";
    mysql_query($requete, $idCnx);
}

/**
 * Modifie la date de la ligne hors forfait passée en paramètre avec la date entrée en paramètre
 * 
 * @param resource $idCnx identifiant de connexion
 * @param string $idLigne id de la ligne hors forfait
 * @param string $unMois mois sous la forme aaaamm
 */
function modifierDateLigneHorsForfait ($idCnx, $idLigne, $unMois){
    $req = "UPDATE LigneFraisHorsForfait SET mois = '". $unMois."' WHERE id = '".$idLigne."'";
    mysql_query($req, $idCnx);
}
?>

