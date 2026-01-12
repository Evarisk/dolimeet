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
        $this->description  = $langs->trans("CompletionCertificateDocumentPDF");
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
        $posy = $tab_top + $tab_height - 20;
        $posX = $pdf->getPageWidth() - $this->marge_droite - 100;

        $pdf->SetXY($this->marge_droite - 5, $posy);
        $pdf->Cell(0, 0, '', 0, 1);
        $pdf->Cell(0, 6, $langs->transnoentities('MadeAt') . ' : ' . $mysoc->town, 0, 1);
        $pdf->Cell(0, 6, $langs->transnoentities('The') . ' : ' . dol_print_date(dol_now(), 'dayhour', 'tzuser'), 0, 1);

        $pdf->SetXY($posX, $posy + 5);
        $signatureStartY = $pdf->GetY();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $langs->transnoentities('FormationSignature'), 0, 1, 'C');

        $pdf->SetX($posX);
        $pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 6, $signatory->firstname . ' ' . $signatory->lastname . ' ' . $userTmp->job, 0, 'C', 0);
        $rectWidth = 115;
        $imageX    = $posX - 10 + ($rectWidth - 60) / 2;
        if (!empty($signatory->signature)) {
            $img  = base64_decode(explode(',', $signatory->signature)[1]);
            $imgY = $pdf->GetY();
            $pdf->Image('@' . $img, $imageX, $imgY, 60, 8, 'PNG', '', 'C');
            $pdf->SetY($imgY + 8);
        } else {
            $pdf->Cell(60, 8, 'N/A', 0, 1, 'C');
        }
        $pdf->SetX($posX);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetX($posX);
        $pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 6, $mysoc->nom . "\n" . $mysoc->address . ' ' . $mysoc->zip . ' ' . $mysoc->town . "\n" . 'SIRET : ' . $mysoc->idprof2 . "\n" . 'NDA : ' . getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'), 0, 'L');
        $signatureEndY = $pdf->GetY() + 5;
        $pdf->Rect($posX - 7, $signatureStartY, $rectWidth, $signatureEndY - $signatureStartY);
        $pdf->Ln(10);
        $pdf->writeHTML('<sup>1</sup><bold>' . $langs->transnoentities('FirstRealisationCertificateFooter') . '</bold>', true, false, true, false, '');
        $pdf->writeHTML('<sup>2</sup><bold>' . $langs->transnoentities('SecondRealisationCertificateFooter') . '</bold>', true, false, true, false, '');
    }

    public function write_file($objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf, $langs, $mysoc, $user;

        require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

        $object = $moreParam['object'];

        $pdf       = new TCPDF();
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
        $logo              = DOL_DATA_ROOT . '/mycompany/logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');

        if ($moreParam['attendant']->element_type == 'user') {
            $attendantCompany = $companyName;
        } else {
            $thirdparty = new Societe($this->db);
            $thirdparty->fetch($moreParam['attendant']->socid);
            $attendantCompany = $thirdparty->name;
        }
        if (!empty($moreParam['attendant'])) {
            $moreParam['documentName'] = $attendantFullname . '_';
        } else {
            $moreParam['documentName'] = '';
        }

        // PDF view page
        $pdf->SetTitle($outputLangs->convToOutputCharset($object->ref));
        $pdf->SetSubject($outputLangs->transnoentities("Contract"));
        $pdf->SetCreator("Dolibarr ".DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->AddPage();
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        // pdf header
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 15, 40);
        }
        if (file_exists($logo)) {
            $posX = $pdf->getPageWidth() - $this->marge_droite - 40;
            $pdf->Image($logo, $posX, 15, 40);
        }
        $pdf->Ln(30);

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Cell(0, 10, strtoupper($langs->transnoentities('RealisationCertificate')), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);

        $pdf->Write(6, $langs->transnoentities('IntroductionRealisationCertificate') . ' ');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $issuerName);
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Write(6, $langs->transnoentities('LegalRepresentativePresentation') . ' ');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(6);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $companyName . ', ');
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Write(6, $langs->transnoentities('AttestsThat') . ' ');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(12);

        $pdf->Write(6, $langs->transnoentities('CivilityMMEShort') . '/' . $langs->transnoentities('CivilityMRShort') . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $attendantFullname);
        $pdf->Ln(6);
        $pdf->Write(6, $langs->transnoentities('BeneficiaryName'));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $attendantCompany);
        $pdf->Ln(6);
        $pdf->Write(6, $langs->transnoentities('AttendantCompany'));
        $pdf->Write(6, $langs->transnoentities('Labelled', $contractRef . ' - ' . $formationLabel));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->Write(6, $langs->transnoentities('NatureActionType'));
        $pdf->Ln(8);

        $actions = [
            'action de formation',
            'bilan de compétences',
            'action de VAE',
            'action de formation par apprentissage'
        ];

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 11);

        foreach ($actions as $action) {
            $checkbox = (dol_ucfirst($action) == $actionName) ? '[X]' : '[ ]';
            if ($actionName == dol_ucfirst($action)[0]) {
                $pdf->writeHTML('<sup>1</sup><bold>' . $langs->transnoentities('FirstRealisationCertificateFooter') . '</bold>', true, false, true, false, '');
            }
            $pdf->Cell(0, 6, $checkbox . ' ' . $action, 0, 1);
        }

        $pdf->Ln(6);

        $pdf->Write(6, $langs->transnoentities('LastFrom') . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Write(6, $langs->transnoentities('TrainingStart', $trainingStart) . ' ' . $langs->transnoentities('TrainingEnd', $trainingEnd) . ' ');
        $pdf->Ln(6);
        $pdf->write(6, $langs->transnoentities('LastHours', $totalHours) . ' ');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        $pdf->Write(6, $langs->transnoentities('FormationLegalText'));
        $pdf->Ln(2);


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
