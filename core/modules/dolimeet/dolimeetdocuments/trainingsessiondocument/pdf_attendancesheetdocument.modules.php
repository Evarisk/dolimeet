<?php
/* Copyright (C) 2025 EVARISK <dev@evarisk.com>
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
 *	\file       core/modules/dolimeet/dolimeetdocuments/attendancesheetdocument/pdf_attendance_sheet.modules.php
 *	\ingroup    dolimeet
 *	\brief      File of class to generate attendance sheet document
 */

/**
 *	Class to manage generation of attendance document attendance_sheet
 */

require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';


class pdf_attendancesheetdocument {
    /**
     * @var DoliDB Database handler
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
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.6 = array(5, 6)
     */
    public $phpmin = array(5, 6);

    /**
     * Dolibarr version of the loaded document
     * @var string
     */
    public $version = 'dolibarr';

    /**
     * @var int page_largeur
     */
    public $page_largeur;

    /**
     * @var int page_hauteur
     */
    public $page_hauteur;

    /**
     * @var array format
     */
    public $format;

    /**
     * @var int marge_gauche
     */
    public $marge_gauche = 5;

    /**
     * @var int marge_droite
     */
    public $marge_droite = 5;

    /**
     * @var int marge_haute
     */
    public $marge_haute = 5;

    /**
     * @var int marge_basse
     */
    public $marge_basse = 5;

    /**
     * Page orientation
     * @var string 'P' or 'Portait' (default), 'L' or 'Landscape'
     */
    private $orientation = 'L';

    /**
     * Issuer
     * @var Societe Object that emits
     */
    public $emetteur;

    /**
     * @var string Module
     */
    public string $module = 'dolimeet';

    /**
     * @var string Document type
     */
    public string $document_type = 'pdf_attendancesheetdocument';

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        global $langs;

        $this->db           = $db;
        $this->name         = 'attendancesheetdocument';
        $this->description  = $langs->trans("AttendanceSheetDocumentPDFDescription");
        $this->type         = 'pdf_attendancesheetdocument';
        $this->format       = 'A4';
        $this->orientation  = 'P';
        $this->marge_gauche = 5;
        $this->marge_droite = 5;
        $this->marge_haute  = 5;
        $this->marge_basse  = 5;
    }
    function pdfSignatureTable($pdf, $title, $signatures, $role, $cellHeight)
    {
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(0, 150, 135);
        $pdf->Cell(0, $cellHeight, $title, 0, 1);

        $headers = ['N°', 'Nom', 'Prénom', 'Présence', 'Signature'];
        $widths  = [10, 45, 45, 30, 60];

        // Header
        $pdf->SetFillColor(0, 150, 135);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFont('', 'B', 9);

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], $cellHeight, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Rows
        $pdf->SetFont('', '', 9);
        $pdf->SetTextColor(0,0,0);

        $index = 1;
        foreach ($signatures as $signature) {
            if ($signature->role !== $role) continue;

            $pdf->SetFillColor(240, 253, 250);
            $pdf->Cell(10, $cellHeight, $index++, 1, 0, 'C', true);

            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(45, $cellHeight, strtoupper($signature->lastname), 1, 0, 'C');
            $pdf->Cell(45, $cellHeight, ucfirst($signature->firstname), 1, 0, 'C');
            $pdf->Cell(30, $cellHeight, 'présent', 1, 0, 'C');

            if (!empty($signature->signature)) {
                $img = base64_decode(explode(',', $signature->signature)[1]);
                $pdf->Image('@'.$img, $pdf->GetX(), $pdf->GetY(), 60, $cellHeight, 'PNG');
                $pdf->Cell(60, $cellHeight, '', 1, 1);
            } else {
                $pdf->Cell(60, $cellHeight, 'N/A', 1, 1, 'C');
            }
        }
    }

    function pdfKeyValueGrid($pdf, array $table, $cellHeight, $maxWidth = 190)
    {
        foreach ($table as $row) {

            // 1. Calculs
            $totalLabelWidth = 0;
            $totalSpan = 0;

            foreach ($row as $cell) {
                $totalLabelWidth += $cell['width'];
                $totalSpan += $cell['span'];
            }

            $remainingWidth = $maxWidth - $totalLabelWidth;
            $unitValueWidth = $remainingWidth / max(1, $totalSpan);

            // 2. Rendu
            foreach ($row as $cell) {

                // Label
                $pdf->SetFont('', 'B', 9);
                $pdf->SetFillColor(240, 253, 250);
                $pdf->SetDrawColor(98, 187, 179);
                $pdf->Cell(
                    $cell['width'],
                    $cellHeight,
                    $cell['label'],
                    1,
                    0,
                    'C',
                    true
                );

                // Valeur
                $pdf->SetFont('', '', 9);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Cell(
                    $unitValueWidth * $cell['span'],
                    $cellHeight,
                    $cell['value'],
                    1,
                    0,
                    'C',
                    true
                );
            }

            $pdf->Ln();
        }
    }

    /**
     * Function to build a document on disk using the generic pdf module.
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file($objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $action, $conf, $langs, $mysoc, $hookmanager, $user;

        require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

        $pdf              = new TCPDF();
        $object           = new Trainingsession($this->db);
        $contract         = new Contrat($this->db);
        $saturneSignature = new SaturneSignature($this->db);
        $thirdParty       = new Societe($this->db);
        $project          = new Project($this->db);

        $object->fetch(GETPOST('id'));
        $contract->fetch($object->fk_contrat);
        $thirdParty->fetch($object->fk_soc);
        $project->fetch($object->fk_project);
        $signatures = $saturneSignature->fetchSignatories($object->id, $object->element);

        $diroutput = $conf->dolimeet->dir_output ?? '';
        if (empty($diroutput)) {
            $this->error = "Configuration manquante: conf->digiquali->dir_output";
            return -1;
        }
        $dir = $diroutput . "/attendancesheetdocument/" . $object->ref;
        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = "Impossible de créer le répertoire: $dir";
                return -1;
            }
        }
        $date      = dol_print_date(dol_now(), "dayxcard");
        $sha       = rand(1000, 4000);
        $file_name = dol_sanitizeFileName($date . "-" . $object->ref . '-' . $sha . "-feuille-de-presence") . ".pdf";
        $file      = $dir . "/" . $file_name;

        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $cellHeight = 8;

        /* =========================
           HEADER
        ========================= */

        $pdf->SetXY(15, 12);
        $logo = DOL_DATA_ROOT . '/mycompany/logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');
        $pdf->Image($logo, $pdf->GetX(), $pdf->GetY(), 50, 10, 'PNG');

        // Title on the right
        $pdf->SetXY(130, 12);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(60, 6, 'FEUILLE DE PRÉSENCE', 0, 2, 'R');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(60, 5, 'Réf projet : ' . $project->ref, 0, 2, 'R');
        $pdf->Cell(60, 5, 'Date : ' . dol_print_date(dol_now(), 'day'), 0, 2, 'R');

        /* =========================
           BLOCS ADRESSES
        ========================= */

        // Company
        $pdf->SetXY(15, 30);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(80, 6, 'Émetteur');
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(80, 5,
            $mysoc->name . "\n" .
            $mysoc->address . "\n" .
            $mysoc->zip . " " . $mysoc->town . "\n" .
            "Tél : " . $mysoc->phone . "\n" .
            "Email : " . $mysoc->email . "\n" .
            "Site : ". $mysoc->url . "\n" .
            "Numéro de déclaration (NDA) : " . ($conf->global->MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER ?? 'N/A') . "\n"
        );

        // Third party
        $pdf->Rect(115, 30, 80, 40);
        $pdf->SetXY(118, 32);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(70, 6, 'Adressé à');
        $pdf->Ln(6);
        $pdf->SetX(118);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(70, 5,
            $thirdParty->name . "\n" .
            $thirdParty->address . "\n" .
            $thirdParty->zip . " " . $thirdParty->town . "\n" .
            "SIRET : " . $thirdParty->idprof2 . "\n"
        );

        $pdf->SetY(80);

        /* =========================
           BLOC FORMATION
        ========================= */
        $formationData = [
            [
                [
                    'label' => 'Réf Convention',
                    'value' => $contract->ref,
                    'span'  => 1,
                    'width' => 30,
                ],
                [
                    'label' => 'Formation',
                    'value' => $object->label,
                    'span'  => 2,
                    'width' => 30,
                ],
            ],
            [
                [
                    'label' => 'Session',
                    'value' =>
                        dol_print_date($object->date_start, 'dayhour') . ' - ' .
                        dol_print_date($object->date_end, 'dayhour'),
                    'span'  => 1,
                    'width' => 30,
                ],
                [
                    'label' => 'Durée',
                    'value' => convertSecondToTime($object->duration),
                    'span'  => 1,
                    'width' => 30,
                ],
            ],
            [
                [
                    'label' => 'Lieu',
                    'value' => $object->position ?: 'N/A',
                    'span'  => 3,
                    'width' => 30,
                ],
            ],
        ];
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(0, 150, 135);
        $pdf->Cell(0, $cellHeight, 'Formation', 0, 1);
        $pdf->SetTextColor(0, 0, 0);

        $this->pdfKeyValueGrid($pdf, $formationData, $cellHeight);

        $this->pdfSignatureTable($pdf, 'Liste des formateurs', $signatures, 'SessionTrainer', $cellHeight);
        $this->pdfSignatureTable($pdf, 'Liste des stagiaires', $signatures, 'Trainee', $cellHeight);


        $pdf->SetY(255);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->MultiCell(190, 3, "La signature des propositions commerciales, commandes, contrats, paiement partiel vaut pour acceptation des CGV ". $mysoc->name, 0, 'C', 0, 1);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(190, 3, "Siège social: " . $mysoc->name . " - " . $mysoc->address . " - " . $mysoc->zip . " " . $mysoc->town . " , " . $mysoc->country, 0, 'C', 0, 1);
        $pdf->MultiCell(190, 3, "Téléphone: " . $mysoc->phone . " - " . $mysoc->url . " - " . $mysoc->email, 0, 'C', 0, 1);
        $pdf->MultiCell(190, 3, $conf->global->SOCIETE_TYPE . " - Capital de " . $mysoc->capital . " € - SIRET: " . $mysoc->idprof2, 0, 'C', 0, 1);
        $pdf->MultiCell(190, 3, "NAF-APE: " . $mysoc->idprof3 . " - RCS/RM: " . $mysoc->idprof4 . " - Numéro TVA: " . $mysoc->tva_intra, 0, 'C', 0, 1);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(190, 3, "1 / 1", 0, 1, 'R');

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = "Erreur lors de la création du PDF : " . $exception->getMessage();
            return -1;
        }
        if (!file_exists($file)) {
            $this->error = "PDF non généré (fichier introuvable après Output) : $file";
            return -1;
        }

        if (is_object($objectDocument) && method_exists($objectDocument, "setValueFrom")) {
            $res = $objectDocument->setValueFrom("last_main_doc", $file_name, '', null, '', '', $user, '', '');
            if ($res <= 0 && !empty($objectDocument->error)) {
                $this->error = $objectDocument->error;
                return -1;
            }
        }

        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }
        $this->result = ['fullpath' => $file];
        return 1;
    }
}
