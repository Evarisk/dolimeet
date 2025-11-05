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

class pdf_attendancesheetdocument {
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
        $object           = new Trainingsession ($this->db);
        $contract         = new Contrat($this->db);
        $saturneSignature = new SaturneSignature($this->db);

        $object->fetch(GETPOST('id'));
        $contract->fetch($object->fk_contrat);
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

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Industrie standard');
        $pdf->SetTitle('Feuille de présence');
        $pdf->AddPage();

        $titleColor  = [102, 153, 153];
        $borderColor = [120, 170, 170];
        $fillColor   = [230, 245, 240];

        $pdf->SetTextColor($titleColor[0], $titleColor[1], $titleColor[2]);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'FEUILLE DE PRÉSENCE ÉTABLIE PAR', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, $mysoc->name, 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Organisme de Formation (OF)', 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);

        $cellHeight = 10;
        $labelWidth = 40;
        $valueWidth = 150;

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($labelWidth, $cellHeight, 'Adresse', 1, 0, 'C');
        $pdf->Cell($valueWidth, $cellHeight, $mysoc->address . ' ' . $mysoc->zip . ' ' . $mysoc->town, 1, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($labelWidth, $cellHeight, 'N° de déclaration (NDA)', 1, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(55, $cellHeight, !empty($conf->global->MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER) ? $conf->global->MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER : $langs->transnoentities('NA'), 1, 0, 'C', true);

        $pdf->Cell($labelWidth, $cellHeight, 'Siret', 1, 0, 'C');
        $pdf->Cell(55, $cellHeight, $mysoc->idprof2, 1, 1, 'C', true);

        $pdf->Cell($labelWidth, $cellHeight, 'Tel', 1, 0, 'C');
        $pdf->Cell(55, $cellHeight, $mysoc->phone, 1, 0, 'C', true);

        $pdf->Cell($labelWidth, $cellHeight, 'E-mail', 1, 0, 'C');
        $pdf->Cell(55, $cellHeight, $mysoc->email, 1, 1, 'C', true);

        $pdf->Cell($labelWidth, $cellHeight, 'Website', 1, 0, 'C');
        $pdf->SetTextColor($titleColor[0], $titleColor[1], $titleColor[2]);
        $pdf->Cell($valueWidth, $cellHeight, 'www.societedemosite.fr', 1, 1, 'C', true);

        $cellHeight = 10;
        $col1       = 40;
        $col2       = 60;
        $col3       = 40;
        $col4       = 50;
        $tableWidth = $col1 + $col2 + $col3 + $col4;

        $pageWidth   = $pdf->GetPageWidth();
        $margins     = $pdf->GetMargins();
        $usableWidth = $pageWidth - $margins['left'] - $margins['right'];
        $startX      = $margins['left'] + (($usableWidth - $tableWidth) / 2);
        $pdf->SetX($startX);

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Ln(5);
        $pdf->Cell($tableWidth, 12, 'Formation', 0, 1, 'L');
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetX($startX);
        $pdf->Cell($col1, $cellHeight, 'Réf Convention', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell($col2, $cellHeight, $contract->ref, 1, 0, 'C', true);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($col3, $cellHeight, 'Formation', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($col4, $cellHeight, $object->label, 1, 1, 'C', true);

        $pdf->SetX($startX);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($col1, $cellHeight, 'Session', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($col2, $cellHeight, dol_print_date($object->date_start, 'dayhour') . ' - ' . dol_print_date($object->date_end, 'dayhour'), 1, 0, 'C', true);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($col3, $cellHeight, 'Durée', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell($col4, $cellHeight, convertSecondToTime($object->duration), 1, 1, 'C', true);

        $pdf->SetX($startX);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($col1, $cellHeight, 'Lieu', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell($col2 + $col3 + $col4, $cellHeight, 'N/A', 1, 1, 'C', true);

        $pdf->SetTextColor($titleColor[0], $titleColor[1], $titleColor[2]);
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Liste des formateurs', 0, 1, 'L');
        $pdf->Ln(3);

        $headerColor = [80, 147, 138];
        $borderColor = [120, 170, 170];
        $fillColor   = [230, 245, 240];

        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->SetTextColor(0, 70, 60);
        $pdf->SetLineWidth(0.3);

        $colNum      = 15;
        $colNom      = 50;
        $colPrenom   = 50;
        $colPresence = 30;
        $colSign     = 45;
        $totalWidth  = $colNum + $colNom + $colPrenom + $colPresence + $colSign;

        $pageWidth = $pdf->GetPageWidth();
        $margins   = $pdf->GetMargins();

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($colNum, 10, 'N°', 1, 0, 'C', true);
        $pdf->Cell($colNom, 10, 'Nom', 1, 0, 'C', true);
        $pdf->Cell($colPrenom, 10, 'Prénom', 1, 0, 'C', true);
        $pdf->Cell($colPresence, 10, 'Présence', 1, 0, 'C', true);
        $pdf->Cell($colSign, 10, 'Signature', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);

        if (!empty($signatures)) {
            $index = 1;
            foreach ($signatures as $signature) {
                if ($signature->role == 'SessionTrainer') {
                    $y = $pdf->GetY();

                    $pdf->Cell($colNum, 10, $index, 1, 0, 'C', false);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell($colNom, 10, strtoupper($signature->lastname), 1, 0, 'C', true);
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->Cell($colPrenom, 10, ucfirst($signature->firstname), 1, 0, 'C', true);

                    $presence = $signature->attendance == 1 | $signature->attendance == 0 ? 'présent' : 'absent';
                    $pdf->Cell($colPresence, 10, $presence, 1, 0, 'C', true);

                    if (!empty($signature->signature)) {
                        $encoded_image  = explode(",", $signature->signature)[1];
                        $signatureImage = base64_decode($encoded_image);
                        $pdf->Image('@' . $signatureImage, $pdf->GetX(), $y, $colSign, 10, 'PNG');
                        $pdf->Cell($colSign, 10, '', 1, 1, 'C');
                    } else {
                        $pdf->Cell($colSign, 10, 'N/A', 1, 1, 'C', true);
                    }
                    $index++;
                }
            }
        } else {
            $pdf->SetX($startX);
            $pdf->Cell($totalWidth, 10, 'Aucun formateur associé', 1, 1, 'C');
        }

        $pdf->SetTextColor($titleColor[0], $titleColor[1], $titleColor[2]);
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Liste des stagiaires', 0, 1, 'L');
        $pdf->Ln(3);

        $headerColor = [80, 147, 138];
        $borderColor = [120, 170, 170];
        $fillColor   = [230, 245, 240];

        $pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->SetTextColor(0, 70, 60);
        $pdf->SetLineWidth(0.3);

        $colNum      = 15;
        $colNom      = 50;
        $colPrenom   = 50;
        $colPresence = 30;
        $colSign     = 45;
        $totalWidth  = $colNum + $colNom + $colPrenom + $colPresence + $colSign;

        $pageWidth = $pdf->GetPageWidth();
        $margins   = $pdf->GetMargins();

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($colNum, 10, 'N°', 1, 0, 'C', true);
        $pdf->Cell($colNom, 10, 'Nom', 1, 0, 'C', true);
        $pdf->Cell($colPrenom, 10, 'Prénom', 1, 0, 'C', true);
        $pdf->Cell($colPresence, 10, 'Présence', 1, 0, 'C', true);
        $pdf->Cell($colSign, 10, 'Signature', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);

        if (!empty($signatures)) {
            $index = 1;
            foreach ($signatures as $signature) {
                if ($signature->role == 'Trainee') {
                    $y = $pdf->GetY();

                    $pdf->Cell($colNum, 10, $index, 1, 0, 'C', false);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell($colNom, 10, strtoupper($signature->lastname), 1, 0, 'C', true);
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->Cell($colPrenom, 10, ucfirst($signature->firstname), 1, 0, 'C', true);

                    $presence = $signature->attendance == 1 | $signature->attendance == 0 ? 'présent' : 'absent';
                    $pdf->Cell($colPresence, 10, $presence, 1, 0, 'C', true);

                    if (!empty($signature->signature)) {
                        $encoded_image  = explode(",", $signature->signature)[1];
                        $signatureImage = base64_decode($encoded_image);
                        $pdf->Image('@' . $signatureImage, $pdf->GetX(), $y, $colSign, 10, 'PNG');
                        $pdf->Cell($colSign, 10, '', 1, 1, 'C');
                    } else {
                        $pdf->Cell($colSign, 10, 'N/A', 1, 1, 'C', true);
                    }
                    $index++;
                }
            }
        } else {
            $pdf->SetX($startX);
            $pdf->Cell($totalWidth, 10, 'Aucun formateur associé', 1, 1, 'C');
        }

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
