<?php
require('fpdf.php');

class PDF extends FPDF
{
// En-tête
function Header() {
    // Logo
    $this->Image('images/logo.jpg',75,6,60);
    // Saut de ligne
    $this->Ln(40);
}

// Tableau coloré
function TableForfait($header, $unMois, $unIdVisiteur, $idCnx){
    // Couleurs, épaisseur du trait et police grasse
    $this->SetFillColor(100,150,255);
    //$this->SetDrawColor(0,115,255);
    //$this->SetLineWidth(.3);
    $this->SetFont('','B');
    // En-tête
    $w = array(95, 30, 35, 30);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
    // Restauration des couleurs et de la police
    $this->SetFillColor(215,230,255);
    $this->SetFont('');
    // Données
    $fill = false;
    $reqEltsForfait = obtenirReqEltsForfaitFicheFrais($unMois, $unIdVisiteur);
    $idJeuRes = mysql_query($reqEltsForfait);
    while ($lgRes = mysql_fetch_assoc($idJeuRes)) {
        $total = $lgRes['quantite']*$lgRes['montant'];
        $this->Cell($w[0],6,$lgRes['libelle'],'LR',0,'L',$fill);
        $this->Cell($w[1],6,  number_format($lgRes['quantite']),'LR',0,'R',$fill);
        $this->Cell($w[2],6,number_format($lgRes['montant'],0,',',' '),'LR',0,'R',$fill);
        $this->Cell($w[3],6,number_format($total,0,',',' '),'LR',0,'R',$fill);
        $this->Ln();
        $fill = !$fill;
    }
    mysql_free_result($idJeuRes);
    // Trait de terminaison
    $this->Cell(array_sum($w),0,'','T');
}

function TableHF($header, $unMois, $unIdVisiteur, $idCnx) {
    // Couleurs, épaisseur du trait et police grasse
    $this->SetFillColor(100,150,255);
    //$this->SetDrawColor(0,115,255);
    //$this->SetLineWidth(.3);
    $this->SetFont('','B');
    // En-tête
    $w = array(40, 120, 30);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
    // Restauration des couleurs et de la police
    $this->SetFillColor(215,230,255);
    $this->SetFont('');
    // Données
    $fill = false;
    $reqEltsHorsForfait = obtenirReqEltsHorsForfaitFicheFrais($unMois, $unIdVisiteur);
    $idJeuRes = mysql_query($reqEltsHorsForfait);
    while ($lgRes = mysql_fetch_assoc($idJeuRes)) {
        $this->Cell($w[0],6,  convertirDateAnglaisVersFrancais($lgRes['date']),'LR',0,'L',$fill);
        $this->Cell($w[1],6,$lgRes['libelle'],'LR',0,'L',$fill);
        $this->Cell($w[2],6,number_format($lgRes['montant'],0,',',' '),'LR',0,'R',$fill);
        $this->Ln();
        $fill = !$fill;
    }
    mysql_free_result($idJeuRes);
    // Trait de terminaison
    $this->Cell(array_sum($w),0,'','T');
}

}

function creerPdf($idCnx, $identite, $unMois, $fileName, $tabFicheFrais, $idCnx) {
$mois = obtenirLibelleMois(substr($unMois,4)).' '.substr($unMois, 0, 4);
// Instanciation de la classe dérivée
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFillColor(215,230,255);
$pdf->SetDrawColor(0,115,255);
$pdf->SetLineWidth(.3);
// Titre fiche 
$pdf->SetFont('Times', 'B', 15); 
$pdf->Cell(0 ,10, 'Fiche de frais '.$mois.' pour '.$identite['nom'].' '.$identite['prenom'], '', 1, 'C');
$pdf->Ln(10);
// Détails fiche de frais
$pdf->SetFont('','B',12);
$pdf->Cell(0, 10, 'Montant validé : '.$tabFicheFrais['montantValide'].' euros', 'LTR', 1, '', true);
$pdf->SetFont('','',12);
$pdf->Cell(0, 10, 'Nombre de justificatifs reçus : '.$tabFicheFrais['nbJustificatifs'], 'LR', 1, '', true);
$pdf->Cell(0, 10, 'Date de la dernière modification : '.convertirDateAnglaisVersFrancais($tabFicheFrais['dateModif']), 'LR', 1, '', true);
$pdf->Cell(0, 10, 'Etat de la fiche de frais : '.$tabFicheFrais['libelleEtat'], 'LBR', 1, '', true);
$pdf->Ln(10); 
// Tableau frais forfaités
$pdf->SetFont('','B', 13); 
$pdf->Cell(0, 10, 'Elements forfaitisés', '', 1);
// Titre colonnes forfait
$header = array('Libellé','Quantité','Montant unitaire','Total');
$pdf->TableForfait($header, $unMois, $identite['id'], $idCnx);
$pdf->Ln(10);
// Tableau frais hors forfait
$pdf->SetFont('','B', 13);
$pdf->Cell(0, 10, 'Elements hors forfait', '', 1);
// Titre colonnes hors forfait
$headerHF = array('Date','Libellé','Montant');
$pdf->TableHF($headerHF, $unMois, $identite['id'], $idCnx);
$pdf->Ln(10);
// Date de création
$pdf->SetFont('Times','', 12);
$pdf->Cell(0,10,'Fiche créée le '.date("d, m, Y"),0,0,'R');
// Creation fichier pdf
$pdf->Output($fileName, 'F');
}

?>

