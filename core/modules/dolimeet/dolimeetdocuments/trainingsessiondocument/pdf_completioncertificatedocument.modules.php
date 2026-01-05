<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file    class/dolimeetdocuments/trainingsessiondocument/pdf_completioncertificatedocument.modules.php
 * \ingroup dolimeet
 * \brief   Completion certificate pdf model
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/lib/saturne_functions.lib.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../lib/dolimeet_function.lib.php';

/**
 * Class pdf_completioncertificatedocument.
 */
class pdf_completioncertificatedocument extends SaturneDocumentModel
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short text)
     */
    public $description;

    /**
     * @var string Module
     */
    public string $module = 'dolimeet';

    /**
     * @var string Document type
     */
    public string $document_type = 'completioncertificatedocument';

    public function __construct($db)
    {
        global $langs;

        $this->db           = $db;
        $this->name         = 'completioncertificatedocument';
        $this->description  = $langs->trans("AttendanceSheetDocumentPDFDescription");
        $this->type         = 'pdf';
        $this->marge_gauche = 15;
        $this->marge_droite = 15;
        $this->marge_haute  = 15;
        $this->marge_basse  = 15;
    }

    /**
     * Show footer signature of page
     *
     * @param TCPDF $pdf Object PDF
     * @param int $tab_top tab height position
     * @param float $tab_height tab height
     * @param Translate $outputlangs Object language for output
     * @param SaturneSignature $signatory Object signatory
     * @param User $userTmp Object user
     * @return void
     */
    protected function tabSignature(&$pdf, $tab_top, $tab_height, $outputlangs, $signatory, $userTmp)
    {
        global $mysoc, $langs;

        $pdf->SetDrawColor(128, 128, 128);
        $posmiddle = $this->marge_gauche + round(($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 2);
        $posy = $tab_top + $tab_height;
        $posX = $pdf->getPageWidth() - $this->marge_droite - 100;

        $pdf->SetXY($this->marge_droite - 5, $posy);
        $pdf->Cell(0, 0, '', 0, 1);
        $pdf->Cell(0, 6, $langs->transnoentities('MadeAt') . ' : ' . $mysoc->town, 0, 1);
        $pdf->Cell(0, 6, $langs->transnoentities('The') . ' : ' . dol_print_date(dol_now(), 'dayhour', 'tzuser'), 0, 1);

        $pdf->SetXY($posX, $posy + 5);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $langs->transnoentities('FormationSignature'), 0, 1);
        $pdf->SetX($posX);
        $pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 6, $signatory->firstname . ' ' . $signatory->lastname . ' ' . $userTmp->job . ', ' . $outputlangs->trans('The') . ' ' . dol_print_date($signatory->signature_date), 0, 'L', 0);
        if (!empty($signatory->signature)) {
            $img = base64_decode(explode(',', $signatory->signature)[1]);
            $pdf->Image('@' . $img, $posX, $pdf->GetY(), 60, 8, 'PNG');
            $pdf->SetX($posX);
            $pdf->Cell(60, 8, '', 1, 1);
        } else {
            $pdf->Cell(60, 8, 'N/A', 1, 1, 'C');
        }

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Ln(10);
        $pdf->Cell(0, 6, $langs->transnoentities('FirstRealisationCertificateFooter'), 0, 1);
        $pdf->Cell(0, 6, $langs->transnoentities('SecondRealisationCertificateFooter'), 0, 1);
    }

    public function write_file($objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf, $langs, $mysoc, $user;

        require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

        $pdf    = new TCPDF();
        $object = $moreParam['object'];

        $userTmp   = new User($this->db);
        $signatory = new SaturneSignature($this->db, 'dolimeet');
        $signatory = $signatory->fetchSignatory('UserSignature', $conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE, 'user');
        $signatory = array_shift($signatory);
        $userTmp->fetch($signatory->element_id);

        $trainingSessionDict = saturne_fetch_dictionary('c_trainingsession_type');

        // Certificate variables
        $attendantFullname = dol_strtoupper($moreParam['attendant']->lastname) . ' ' . dol_ucfirst($moreParam['attendant']->firstname);
        $companyName       = $mysoc->name;
        $formationLabel    = $object->formationLabel;
        $contractRef       = $object->ref;
        $trainingStart     = dol_print_date($object->date_start, 'day', 'tzuser');
        $trainingEnd       = dol_print_date($object->date_end, 'day', 'tzuser');
        $totalHours        = convertSecondToTime($object->duration, 'allhourmin');
        $actionName        = $langs->trans($trainingSessionDict[$object->array_options['options_trainingsession_type']]->label);
        $issuerName        = $userTmp->firstname . ' ' . $userTmp->lastname;
        $logoPath          = DOL_DOCUMENT_ROOT . '/custom/dolimeet/img/ministere_du_travail.png';

        if ($moreParam['attendant']->element_type == 'user') {
            $attendantCompany = $companyName;
        } else {
            $thirdparty = new Societe($this->db);
            $thirdparty->fetch($moreParam['attendant']->socid);
            $attendantCompany = $thirdparty->name;
        }
        if (!empty($moreParam['attendant'])) {
            $moreParam['documentName'] = strtoupper($moreParam['attendant']->lastname) . '_' . ucfirst($moreParam['attendant']->firstname) . '_';
        } else {
            $moreParam['documentName'] = '';
        }

        // PDF view page
        $pdf->AddPage();
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 10, 40);
        }
        $pdf->Ln(30);

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Cell(0, 10, strtoupper($langs->transnoentities('RealisationCertificate')), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 11);

        $pdf->Write(6, $langs->transnoentities('IntroductionRealisationCertificate') . ' ');
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('FirstnameLastname') . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $langs->transnoentities('LegalRepresentativePresentation', $issuerName) . ' ');
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('FormationRepresentativeExplaination'));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $langs->transnoentities('AttestsThat', $companyName) . ' ');
        $pdf->Ln(8);

        $pdf->Write(6, $langs->transnoentities('CivilityMMEShort') . '/' . $langs->transnoentities('CivilityMRShort') . ' ');
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('BeneficiaryNameExplaination') . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $langs->transnoentities('BeneficiaryName', $attendantFullname) . ' ');
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('AttendantCompanyExplaination') . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $langs->transnoentities('AttendantCompany', $attendantCompany) . ' ');
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('Labelled', $contractRef . ' - ' . $formationLabel));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('NatureActionType'));
        $pdf->Ln(8);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 5, $actionName, 0, 1);

        $pdf->Ln(6);

        $pdf->Write(6, $langs->transnoentities('LastFrom') . ' ');
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Write(6, $langs->transnoentities('FormationDateRange') . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $langs->transnoentities('TrainingStart', $trainingStart) . ' ' . $langs->transnoentities('TrainingEnd', $trainingEnd) . ' ' . $langs->transnoentities('LastHours', $totalHours));
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Ln(5);
        $pdf->Write(6, $langs->transnoentities('FormationRealizedHours'));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(6);
        $pdf->Write(6, $langs->transnoentities('FormationLegalText'));
        $pdf->Ln(10);


        // SIGNATURE
        $tab_top           = $pdf->GetY();
        $heightforinfotot  = 50;
        $heightforfooter   = $this->marge_basse + 8;
        $this->tabSignature($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter, $langs, $signatory, $userTmp);

        $uploadDir = getMultidirOutput($object, $this->module);
        if (!$uploadDir) {
            $this->error = $langs->transnoentities('ErrorCanNotCreateDir');
            return -1;
        }

        $moreParam['hideTemplateName'] = 1;
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $uploadDir, $moreParam);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = "Erreur lors de la création du PDF : " . $exception->getMessage();
            return -1;
        }

        $this->result = ['fullpath' => $file];

        return 1;
    }
}
