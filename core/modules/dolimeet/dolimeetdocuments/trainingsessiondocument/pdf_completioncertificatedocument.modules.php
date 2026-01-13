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
        global $langs, $mysoc;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'completioncertificatedocument';
        $this->description = $langs->trans('CompletionCertificateDocumentPDF');
        $this->type        = 'pdf';

        $this->emetteur = $mysoc;
    }

    /**
     * Show footer signature of page
     *
     * @param TCPDF $pdf Object PDF
     * @param int $tab_top tab height position
     * @param float $tab_height tab height
     * @param Translate $outputLangs Object language for output
     * @param SaturneSignature $signatory Object signatory
     * @param User $userTmp Object user
     * @return void
     */
    protected function tabSignature(&$pdf, $tab_top, $tab_height, $outputLangs, $signatory, $userTmp)
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
        $pdf->MultiCell(0, 6, $langs->transnoentities('TrainingSignature') . "\n" . $langs->transnoentities('TrainingSignatureResponsible'), 0, 'C',);

        $pdf->SetX($posX);
        $pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 6, $signatory->firstname . ' ' . $signatory->lastname . ' , ' . $userTmp->job, 0, 'C', 0);
        $rectWidth = 115;
        $imageX    = $posX + 50;
        if (!empty($signatory->signature)) {
            $img  = base64_decode(explode(',', $signatory->signature)[1]);
            $imgY = $pdf->GetY() + 5;
            $pdf->Image('@' . $img, $imageX, $imgY, 60, 8, 'PNG', '', 'C');
        } else {
            $pdf->Cell(60, 8, 'N/A', 0, 1, 'C');
        }
        $pdf->SetX($posX);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetX($posX);
        $pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 6, $mysoc->nom . "\n" . $mysoc->address . "\n" . $mysoc->zip . ' ' . $mysoc->town . "\n" . 'SIRET : ' . $mysoc->idprof2 . "\n" . 'NDA : ' . getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'), 0, 'L');
        $signatureEndY = $pdf->GetY() + 5;
        $pdf->Rect($posX - 7, $signatureStartY, $rectWidth, $signatureEndY - $signatureStartY);
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->writeHTML($langs->transnoentities('FirstRealisationCertificateFooter'));
        $pdf->writeHTML($langs->transnoentities('SecondRealisationCertificateFooter'));
    }

    /**
     *  Show top header of page
     *
     *  @param	TCPDF		$pdf     		Object PDF
     *  @param  Contrat		$object     	Object to show
     *  @param  Translate	$outputlangs	Object lang for output
     *  @return	float|int                   Return topshift value
     */
    protected function _pagehead(&$pdf, $object, $outputLangs, $defaultFontSize)
    {
        $top_shift = 0;
        $width     = 40;
        $posX      = $this->marge_gauche;
        $posY      = $this->marge_haute;

        $logoPath = DOL_DOCUMENT_ROOT . '/custom/dolimeet/img/ministere_du_travail.png';
        if (file_exists($logoPath)) {
            $height = pdf_getHeightForLogo($logoPath);
            $pdf->SetXY($posX, $posY);
            $pdf->Image($logoPath, $posX, $posY, 0, $height);
        }

        // Logo
        if (!getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO')) {
            if ($this->emetteur->logo) {
                $logoDir = '';
                if (getMultidirOutput($object, 'mycompany')) {
                    $logoDir = getMultidirOutput($object, 'mycompany');
                }
                if (!getDolGlobalString('MAIN_PDF_USE_LARGE_LOGO')) {
                    $logo = $logoDir . '/logos/thumbs/' . $this->emetteur->logo_small;
                } else {
                    $logo = $logoDir . '/logos/' . $this->emetteur->logo;
                }
                if (is_readable($logo)) {
                    $height = pdf_getHeightForLogo($logo);
                    $posX   = $this->page_largeur - $this->marge_droite - $width;
                    $pdf->SetX($posX);
                    $pdf->Image($logo, $posX, $posY, 0, $height); // width=0 (auto)
                } else {
                    $width = 100;
                    $pdf->SetTextColor(200, 0, 0);
                    $pdf->SetFont('', 'B', $defaultFontSize);
                    $pdf->MultiCell($width, 3, $outputLangs->transnoentities('ErrorLogoFileNotFound', $logo), 0, 'L');
                    $pdf->MultiCell($width, 3, $outputLangs->transnoentities('ErrorGoToGlobalSetup'), 0, 'L');
                }
            }
        }

        return $top_shift;
    }

    public function write_file($objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $action, $conf, $hookmanager, $langs, $mysoc, $user;

        $object = $moreParam['object'];

        $uploadDir = getMultidirOutput($object, $this->module);
        if (!$uploadDir) {
            $this->error = $langs->transnoentities('ErrorCanNotCreateDir');
            return -1;
        }

        $attendantFullname = dol_strtoupper($moreParam['attendant']->lastname) . ' ' . dol_ucfirst($moreParam['attendant']->firstname);
        if (!empty($moreParam['attendant'])) {
            $moreParam['documentName'] = $attendantFullname . '_';
        } else {
            $moreParam['documentName'] = '';
        }

        $moreParam['hideTemplateName'] = 1;
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $uploadDir, $moreParam);
        if ($file < 0) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

        // Create pdf instance
        $pdf              = pdf_getInstance($this->format);
        $defaultFontSize  = pdf_getPDFFontSize($outputLangs); // Must be after pdf_getInstance
        $defaultFontSize += 2;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputLangs));

        $pdf->Open();
        $pdf->SetDrawColor(128, 128, 128);

        $pdf->SetTitle($outputLangs->convToOutputCharset($this->document_type));
        $pdf->SetSubject($outputLangs->transnoentities($this->document_type));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetKeyWords($outputLangs->convToOutputCharset($object->ref) . ' ' . $outputLangs->transnoentities($this->document_type));

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

        $userTmp   = new User($this->db);
        $signatory = new SaturneSignature($this->db, 'dolimeet');

        $signatory = $signatory->fetchSignatory('UserSignature', $conf->global->DOLIMEET_SESSION_TRAINER_RESPONSIBLE, 'user');
        $signatory = array_shift($signatory);
        $userTmp->fetch($signatory->element_id);

        $trainingSessionDicts = saturne_fetch_dictionary('c_trainingsession_type');

        // Certificate variables
        $companyName    = $mysoc->name;
        $formationLabel = $object->formationLabel;
        $contractRef    = $object->ref;
        $trainingStart  = dol_print_date($object->date_start, 'day', 'tzuser');
        $trainingEnd    = dol_print_date($object->date_end, 'day', 'tzuser');
        $totalHours     = convertSecondToTime($object->duration, 'allhourmin');
        $actionName     = $langs->trans($trainingSessionDicts[$object->array_options['options_trainingsession_type']]->label);
        $issuerName     = $userTmp->firstname . ' ' . $userTmp->lastname;

        if ($moreParam['attendant']->element_type == 'user') {
            $attendantCompany = $companyName;
        } else {
            $thirdparty = new Societe($this->db);
            $thirdparty->fetch($moreParam['attendant']->socid);
            $attendantCompany = $thirdparty->name;
        }

        $defaultHeightCell = 6;

        $data = [
            'row1' => [
                'fontWeight' => 'B',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('IntroductionRealisationCertificate'),
                'Ln'         => 0
            ],
            'row2' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => ' ' . $issuerName,
                'Ln'         => 6
            ],
            'row3' => [
                'fontWeight' => 'B',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('LegalRepresentativePresentation'),
                'Ln'         => 0
            ],
            'row4' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => ' ' . $companyName . ', ',
                'Ln'         => 6
            ],
            'row5' => [
                'fontWeight' => 'B',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('AttestationStatement'),
                'Ln'         => 12
            ],
            'row6' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('CivilityMMEShort') . '/' . $langs->transnoentities('CivilityMRShort') . ' ' . $attendantFullname,
                'Ln'         => 6
            ],
            'row7' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('BeneficiaryDescription') . ' ' . $attendantCompany,
                'Ln'         => 6
            ],
            'row8' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('TrainingAttendanceStatement') . ' ' . $langs->transnoentities('TrainingLabel', $contractRef . ' - ' . $formationLabel),
                'Ln'         => 10
            ],
            'row9' => [
                'fontWeight' => 'I',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('NatureActionType'),
                'Ln'         => 8
            ],
        ];

        // PDF view page
        $pdf->AddPage();

        // pdf header
        $this->_pagehead($pdf, $object, $outputLangs, $defaultFontSize);
        $pdf->Ln(30);

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 51, 153);
        $pdf->Cell(0, 10, dol_strtoupper($langs->transnoentities($this->document_type)), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetTextColor(0, 0, 0);
        foreach ($data as $row) {
            $pdf->SetFont('helvetica', $row['fontWeight'], $defaultFontSize);
            $pdf->Write($row['height'], $row['label']);
            if ($row['Ln'] > 0) {
                $pdf->Ln($row['Ln']);
            }
        }

        $pdf->SetFont('helvetica', '', $defaultFontSize);
        foreach ($trainingSessionDicts as $trainingSessionDictKey => $trainingSessionDict) {
            $checkBox = ($trainingSessionDictKey == $object->array_options['options_trainingsession_type'] ? '[X]' : '[ ]');
            $sup      = '';
            if ($trainingSessionDictKey == 1) {
                $sup = '<sup>1</sup>';
            }
            $pdf->writeHTML('&nbsp;&nbsp;&nbsp;&nbsp;' . $checkBox . ' ' . $langs->transnoentities($trainingSessionDict->label) . $sup);
        }
        $pdf->Ln(6);

        $data = [
            'row1' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('TrainingPeriodIntro') . ' ' . $langs->transnoentities('TrainingStart', $trainingStart) . ' ' . $langs->transnoentities('TrainingEnd', $trainingEnd),
                'Ln'         => 6
            ],
            'row2' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('TrainingDuration', $totalHours),
                'Ln'         => 10,
                'html'       => 1,
            ],
            'row3' => [
                'fontWeight' => '',
                'height'     => $defaultHeightCell,
                'label'      => $langs->transnoentities('TrainingLegalNotice'),
                'Ln'         => 2
            ],
        ];
        foreach ($data as $row) {
            $pdf->SetFont('helvetica', $row['fontWeight'], $defaultFontSize);
            if (!empty($row['html'])) {
                $pdf->writeHTML($row['label']);
            } else {
                $pdf->Write($row['height'], $row['label']);
            }
            if ($row['Ln'] > 0) {
                $pdf->Ln($row['Ln']);
            }
        }

        // SIGNATURE
        $tab_top           = $pdf->GetY();
        $heightforinfotot  = 50;
        $heightforfooter   = $this->marge_basse + 8;
        $this->tabSignature($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter, $langs, $signatory, $userTmp);

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
