<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/financial_and_pedagogical_report/generate_bpf_pdf.php
 * \ingroup doliopi
 * \brief   Overlay BPF data on the empty Cerfa 10443*17 PDF template and stream it.
 */

// Load Doliopi environment
if (file_exists('../../doliopi.main.inc.php')) {
    require_once __DIR__ . '/../../doliopi.main.inc.php';
} elseif (file_exists('../../../doliopi.main.inc.php')) {
    require_once __DIR__ . '/../../../doliopi.main.inc.php';
} else {
    die('Include of doliopi main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

// Load Doliopi libraries
require_once __DIR__ . '/../../class/doliopidocuments/financialandpedagogicalreportdocument.class.php';

// Global variables definitions
global $conf, $db, $langs, $mysoc, $user;

saturne_load_langs();

// Permissions
$permissionToRead = $user->hasRight('doliopi', 'adminpage', 'read');
saturne_check_access($permissionToRead);

$templatePath = __DIR__ . '/../../documents/templates/cerfa_10443-17.pdf';
if (!file_exists($templatePath)) {
    setEventMessages('Template Cerfa introuvable : ' . $templatePath, [], 'errors');
    header('Location: ' . dol_buildpath('/doliopi/view/financial_and_pedagogical_report/financial_and_pedagogical_report.php', 1));
    exit;
}

// Load BPF data for the requested year (default = current year). An empty result
// (no invoices on the period, categories not generated, etc.) is fine — the PDF
// is still generated with zeros so the user can submit a "néant" BPF.
$report   = new FinancialAndPedagogicalReportDocument($db);
$BPFInfos = $report->loadBPFInfos();

$year                 = GETPOSTISSET('search_year') ? GETPOSTINT('search_year') : (int) date('Y');
$firstDayOfFiscalYear = $BPFInfos['firstDayOfFiscalYear'] ?? dol_get_first_day($year, getDolGlobalString('SOCIETE_FISCAL_MONTH_START'));
$lastDayOfFiscalYear  = $BPFInfos['lastDayOfFiscalYear']  ?? dol_time_plus_duree(dol_time_plus_duree($firstDayOfFiscalYear, 1, 'y'), -1, 'd');

// Helper: format a HT amount as integer string (Cerfa rounds to nearest euro).
$fmtAmount = function ($value): string {
    return (string) (int) round((float) $value);
};

// Helper: convert a duration in seconds to whole hours (Cerfa rounds to nearest hour).
$fmtHours = function ($seconds): string {
    return (string) (int) round(((int) $seconds) / 3600);
};

// Compute aggregated values
$bpfByTag = $BPFInfos['BPFInfos'] ?? [];
$amounts  = $BPFInfos['BPFPartDAmounts'] ?? ['total' => 0, 'salaries' => 0, 'purchases' => 0];
$trainers = $BPFInfos['BPFPartETrainers'] ?? [
    'internal' => ['count' => 0, 'hours' => 0],
    'external' => ['count' => 0, 'hours' => 0]
];
$byType   = $BPFInfos['BPFInfosByTrainingType'] ?? [];

// Part C totals
$c1Tags  = ['C1a', 'C1b', 'C1c', 'C1d', 'C1e', 'C1f', 'C1g', 'C1h'];
$c2Total = 0;
foreach ($c1Tags as $tag) {
    $c2Total += $bpfByTag[$tag]['totalHT'] ?? 0;
}
$cTotal = 0;
foreach (['C1', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10', 'C11'] as $tag) {
    $cTotal += $bpfByTag[$tag]['totalHT'] ?? 0;
}
$cTotal     += $c2Total;
$salesPct    = ($cTotal > 0 && ($BPFInfos['BPFInfos']['sales'] ?? 0) > 0) ? (int) ceil($cTotal / $BPFInfos['BPFInfos']['sales'] * 100) : 0;

// Part F1 (per the C-tag mapping defined in the class)
$f1Map = [
    'a' => ['C1'],
    'b' => ['C1a'],
    'c' => ['C1f'],
    'd' => ['C9'],
    'e' => ['C1b', 'C1c', 'C1d', 'C1e', 'C1g', 'C1h', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C11']
];
$f1 = ['a' => [0, 0], 'b' => [0, 0], 'c' => [0, 0], 'd' => [0, 0], 'e' => [0, 0]];
foreach ($f1Map as $key => $tags) {
    foreach ($tags as $tag) {
        $f1[$key][0] += $bpfByTag[$tag]['NbTrainees'] ?? 0;
        $f1[$key][1] += $bpfByTag[$tag]['TrainingSessionDurations'] ?? 0;
    }
}
$f1Total = [
    array_sum(array_column($f1, 0)),
    array_sum(array_column($f1, 1))
];

// Part F3 (training type)
$bilan  = $byType['BilanCompetences']             ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];
$vae    = $byType['ActionVAE']                    ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];
$other1 = $byType['ActionFormation']              ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];
$other2 = $byType['ActionFormationApprentissage'] ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];

// Part G (subcontracted in)
$c10 = $bpfByTag['C10'] ?? ['NbTrainees' => 0, 'TrainingSessionDurations' => 0];

// Build the PDF
$pdf = pdf_getInstance('A4', 'mm', 'P');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetFont(pdf_getPDFFont($langs), '', 9);
$pdf->SetTextColor(0, 0, 0);

$pageCount = $pdf->setSourceFile($templatePath);

// ---------- Coordinate map (millimetres, top-left origin) ----------
// All coordinates are first-pass approximations against cerfa_10443-17.pdf.
// Fine-tune if values land off-cell after the first generated PDF.

// Coordinates are in millimetres, top-left origin. The Cerfa is A4 (210 × 297 mm).
// Use ?debug=grid on the URL to overlay a 10 mm ruler and refine these values.

// Page 1 — Cadre A (band ≈ y=50), Cadre B (≈ y=103), Cadre C (≈ y=130), Cadre D (≈ y=255)
$xy = [
    // Row 1 of Cadre A (declaration label + boxes on the left, SIRET/NAF column headers above the boxes)
    'declaration' => [58, 60],
    // Row 2 of Cadre A (forme juridique line on the left, SIRET/NAF boxes on the right)
    'siret'       => [110, 64],
    'naf'         => [180, 64],
    'legalForm'   => [33, 66],
    'orgName'     => [65, 72],
    'address'     => [22, 84],
    'addrPubYes'  => [128, 92],
    'addrPubNo'   => [152, 92],
    'phone'       => [16, 97],
    'email'       => [108, 97],

    // Cadre B
    'fyStartD' => [82, 118], 'fyStartM' => [91, 118], 'fyStartY' => [100, 118],
    'fyEndD'   => [120, 118], 'fyEndM'  => [129, 118], 'fyEndY'  => [138, 118],
    'remoteYes' => [170, 123],
    'remoteNo'  => [194, 123],

    // Cadre C — amounts right-aligned, right edge ≈ x=200, line spacing 5 mm
    'c1'  => [200, 140],
    'c1a' => [200, 150], 'c1b' => [200, 155], 'c1c' => [200, 160], 'c1d' => [200, 165],
    'c1e' => [200, 170], 'c1f' => [200, 175], 'c1g' => [200, 180], 'c1h' => [200, 185],
    'c2'  => [200, 190],
    'c3'  => [200, 195],
    'c4'  => [200, 200], 'c5'  => [200, 205], 'c6'  => [200, 210], 'c7'  => [200, 215], 'c8'  => [200, 220],
    'c9'  => [200, 225],
    'c10' => [200, 230],
    'c11' => [200, 235],
    'cTotal'   => [200, 243],
    'salesPct' => [200, 249],

    // Cadre D
    'dTotal'     => [200, 263],
    'dSalaries'  => [165, 268],
    'dPurchases' => [165, 273]
];

// Page 2 — E (≈ y=15), F1 (≈ y=70), F2 (≈ y=110), F3 (≈ y=125), F4 (≈ y=200), G (≈ y=245), H (≈ y=260), Footer (≈ y=270)
$xy2 = [
    'eIntCount' => [148, 17], 'eIntHours' => [183, 17],
    'eExtCount' => [148, 22], 'eExtHours' => [183, 22],

    // F1
    'f1aN' => [148, 70], 'f1aH' => [183, 70],
    'f1bN' => [148, 75], 'f1bH' => [183, 75],
    'f1cN' => [148, 80], 'f1cH' => [183, 80],
    'f1dN' => [148, 85], 'f1dH' => [183, 85],
    'f1eN' => [148, 90], 'f1eH' => [183, 90],
    'f1tN' => [148, 96], 'f1tH' => [183, 96],

    // F2
    'f2N' => [148, 111], 'f2H' => [183, 111],

    // F3 — RNCP main + 6 sub-levels, then b, c, d, e, f, total
    'f3aN'   => [148, 126], 'f3aH'   => [183, 126],
    'f3a68N' => [148, 131], 'f3a68H' => [183, 131],
    'f3a5N'  => [148, 135], 'f3a5H'  => [183, 135],
    'f3a4N'  => [148, 140], 'f3a4H'  => [183, 140],
    'f3a3N'  => [148, 144], 'f3a3H'  => [183, 144],
    'f3a2N'  => [148, 148], 'f3a2H'  => [183, 148],
    'f3aCN'  => [148, 152], 'f3aCH'  => [183, 152],
    'f3bN'   => [148, 157], 'f3bH'   => [183, 157],
    'f3cN'   => [148, 162], 'f3cH'   => [183, 162],
    'f3dN'   => [148, 167], 'f3dH'   => [183, 167],
    'f3eN'   => [148, 172], 'f3eH'   => [183, 172],
    'f3fN'   => [148, 177], 'f3fH'   => [183, 177],
    'f3tN'   => [148, 185], 'f3tH'   => [183, 185],

    // F4 — only the total row is filled by the script
    'f4tN' => [148, 232], 'f4tH' => [183, 232],

    // G
    'gN' => [148, 246], 'gH' => [183, 246],

    // H
    'managerName'   => [40, 261],
    'managerStatus' => [130, 261],

    // Footer
    'town'      => [12, 269],
    'date'      => [125, 269],
    'signatory' => [50, 275],
    'email'     => [15, 281],
    'phone'     => [130, 281]
];

// ---------- Helpers ----------
$writeAt = function ($x, $y, $text) use ($pdf) {
    $pdf->SetXY((float) $x, (float) $y);
    $pdf->Cell(0, 4, (string) $text, 0, 0, 'L');
};
$writeAtRight = function ($x, $y, $text, $width = 25) use ($pdf) {
    $pdf->SetXY((float) $x - $width, (float) $y);
    $pdf->Cell($width, 4, (string) $text, 0, 0, 'R');
};
$cross = function ($x, $y) use ($pdf) {
    $pdf->SetXY((float) $x, (float) $y);
    $pdf->Cell(0, 4, 'X', 0, 0, 'L');
};

// ---------- Page 1 ----------
$tppl1 = $pdf->importPage(1);
$pdf->AddPage();
$pdf->useTemplate($tppl1);

// Cadre A
$writeAt($xy['declaration'][0], $xy['declaration'][1], getDolGlobalString('MAIN_INFO_SOCIETE_TRAINING_ORGANIZATION_NUMBER'));
$writeAt($xy['siret'][0], $xy['siret'][1], $mysoc->idprof2);
$writeAt($xy['naf'][0], $xy['naf'][1], $mysoc->idprof3);
$writeAt($xy['legalForm'][0], $xy['legalForm'][1], getFormeJuridiqueLabel($mysoc->forme_juridique_code));
$writeAt($xy['orgName'][0], $xy['orgName'][1], $mysoc->name);
$writeAt($xy['address'][0], $xy['address'][1], $mysoc->address . ' ' . $mysoc->zip . ' ' . $mysoc->town);
if (getDolGlobalInt('DOLIOPI_BPF_ADDRESS_PUBLIC')) {
    $cross($xy['addrPubYes'][0], $xy['addrPubYes'][1]);
} else {
    $cross($xy['addrPubNo'][0], $xy['addrPubNo'][1]);
}
$writeAt($xy['phone'][0], $xy['phone'][1], $mysoc->phone);
$writeAt($xy['email'][0], $xy['email'][1], $mysoc->email);

// Cadre B — fiscal year
$start = $firstDayOfFiscalYear;
$end   = $lastDayOfFiscalYear;
$writeAt($xy['fyStartD'][0], $xy['fyStartD'][1], dol_print_date($start, '%d'));
$writeAt($xy['fyStartM'][0], $xy['fyStartM'][1], dol_print_date($start, '%m'));
$writeAt($xy['fyStartY'][0], $xy['fyStartY'][1], dol_print_date($start, '%Y'));
$writeAt($xy['fyEndD'][0], $xy['fyEndD'][1], dol_print_date($end, '%d'));
$writeAt($xy['fyEndM'][0], $xy['fyEndM'][1], dol_print_date($end, '%m'));
$writeAt($xy['fyEndY'][0], $xy['fyEndY'][1], dol_print_date($end, '%Y'));
if (getDolGlobalInt('DOLIOPI_BPF_REMOTE_TRAINING')) {
    $cross($xy['remoteYes'][0], $xy['remoteYes'][1]);
} else {
    $cross($xy['remoteNo'][0], $xy['remoteNo'][1]);
}

// Cadre C — amounts (right-aligned in box, ~25 mm wide)
$writeAtRight($xy['c1'][0], $xy['c1'][1], $fmtAmount($bpfByTag['C1']['totalHT'] ?? 0));
foreach (['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'] as $sub) {
    $key = 'c1' . $sub;
    $writeAtRight($xy[$key][0], $xy[$key][1], $fmtAmount($bpfByTag['C1' . $sub]['totalHT'] ?? 0));
}
$writeAtRight($xy['c2'][0], $xy['c2'][1], $fmtAmount($c2Total));
$writeAtRight($xy['c3'][0], $xy['c3'][1], $fmtAmount($bpfByTag['C3']['totalHT'] ?? 0));
$writeAtRight($xy['c4'][0], $xy['c4'][1], $fmtAmount($bpfByTag['C4']['totalHT'] ?? 0));
$writeAtRight($xy['c5'][0], $xy['c5'][1], $fmtAmount($bpfByTag['C5']['totalHT'] ?? 0));
$writeAtRight($xy['c6'][0], $xy['c6'][1], $fmtAmount($bpfByTag['C6']['totalHT'] ?? 0));
$writeAtRight($xy['c7'][0], $xy['c7'][1], $fmtAmount($bpfByTag['C7']['totalHT'] ?? 0));
$writeAtRight($xy['c8'][0], $xy['c8'][1], $fmtAmount($bpfByTag['C8']['totalHT'] ?? 0));
$writeAtRight($xy['c9'][0], $xy['c9'][1], $fmtAmount($bpfByTag['C9']['totalHT'] ?? 0));
$writeAtRight($xy['c10'][0], $xy['c10'][1], $fmtAmount($bpfByTag['C10']['totalHT'] ?? 0));
$writeAtRight($xy['c11'][0], $xy['c11'][1], $fmtAmount($bpfByTag['C11']['totalHT'] ?? 0));
$writeAtRight($xy['cTotal'][0], $xy['cTotal'][1], $fmtAmount($cTotal));
$writeAtRight($xy['salesPct'][0], $xy['salesPct'][1], (string) $salesPct);

// Cadre D
$writeAtRight($xy['dTotal'][0], $xy['dTotal'][1], $fmtAmount($amounts['total']));
$writeAtRight($xy['dSalaries'][0], $xy['dSalaries'][1], $fmtAmount($amounts['salaries']));
$writeAtRight($xy['dPurchases'][0], $xy['dPurchases'][1], $fmtAmount($amounts['purchases']));

// ---------- Page 2 ----------
if ($pageCount >= 2) {
    $tppl2 = $pdf->importPage(2);
    $pdf->AddPage();
    $pdf->useTemplate($tppl2);

    // Cadre E
    $writeAtRight($xy2['eIntCount'][0], $xy2['eIntCount'][1], (string) $trainers['internal']['count']);
    $writeAtRight($xy2['eIntHours'][0], $xy2['eIntHours'][1], $fmtHours($trainers['internal']['hours']));
    $writeAtRight($xy2['eExtCount'][0], $xy2['eExtCount'][1], (string) $trainers['external']['count']);
    $writeAtRight($xy2['eExtHours'][0], $xy2['eExtHours'][1], $fmtHours($trainers['external']['hours']));

    // Cadre F1
    foreach (['a', 'b', 'c', 'd', 'e'] as $sub) {
        $writeAtRight($xy2['f1' . $sub . 'N'][0], $xy2['f1' . $sub . 'N'][1], (string) $f1[$sub][0]);
        $writeAtRight($xy2['f1' . $sub . 'H'][0], $xy2['f1' . $sub . 'H'][1], $fmtHours($f1[$sub][1]));
    }
    $writeAtRight($xy2['f1tN'][0], $xy2['f1tN'][1], (string) $f1Total[0]);
    $writeAtRight($xy2['f1tH'][0], $xy2['f1tH'][1], $fmtHours($f1Total[1]));

    // Cadre F2 — no signal in doliopi, leave at 0
    $writeAtRight($xy2['f2N'][0], $xy2['f2N'][1], '0');
    $writeAtRight($xy2['f2H'][0], $xy2['f2H'][1], '0');

    // Cadre F3 — RNCP/RS/CQP not detected; only "autres formations" / bilans / VAE filled
    $f3d = [
        $other1['NbTrainees'] + $other2['NbTrainees'],
        $other1['TrainingSessionDurations'] + $other2['TrainingSessionDurations']
    ];
    $writeAtRight($xy2['f3aN'][0], $xy2['f3aN'][1], '0');
    $writeAtRight($xy2['f3aH'][0], $xy2['f3aH'][1], '0');
    foreach (['68', '5', '4', '3', '2', 'C'] as $level) {
        $writeAtRight($xy2['f3a' . $level . 'N'][0], $xy2['f3a' . $level . 'N'][1], '0');
        $writeAtRight($xy2['f3a' . $level . 'H'][0], $xy2['f3a' . $level . 'H'][1], '0');
    }
    $writeAtRight($xy2['f3bN'][0], $xy2['f3bN'][1], '0');
    $writeAtRight($xy2['f3bH'][0], $xy2['f3bH'][1], '0');
    $writeAtRight($xy2['f3cN'][0], $xy2['f3cN'][1], '0');
    $writeAtRight($xy2['f3cH'][0], $xy2['f3cH'][1], '0');
    $writeAtRight($xy2['f3dN'][0], $xy2['f3dN'][1], (string) $f3d[0]);
    $writeAtRight($xy2['f3dH'][0], $xy2['f3dH'][1], $fmtHours($f3d[1]));
    $writeAtRight($xy2['f3eN'][0], $xy2['f3eN'][1], (string) $bilan['NbTrainees']);
    $writeAtRight($xy2['f3eH'][0], $xy2['f3eH'][1], $fmtHours($bilan['TrainingSessionDurations']));
    $writeAtRight($xy2['f3fN'][0], $xy2['f3fN'][1], (string) $vae['NbTrainees']);
    $writeAtRight($xy2['f3fH'][0], $xy2['f3fH'][1], $fmtHours($vae['TrainingSessionDurations']));
    $writeAtRight($xy2['f3tN'][0], $xy2['f3tN'][1], (string) $f1Total[0]);
    $writeAtRight($xy2['f3tH'][0], $xy2['f3tH'][1], $fmtHours($f1Total[1]));

    // Cadre F4 — no NSF metadata, only the total
    $writeAtRight($xy2['f4tN'][0], $xy2['f4tN'][1], (string) $f1Total[0]);
    $writeAtRight($xy2['f4tH'][0], $xy2['f4tH'][1], $fmtHours($f1Total[1]));

    // Cadre G
    $writeAtRight($xy2['gN'][0], $xy2['gN'][1], (string) $c10['NbTrainees']);
    $writeAtRight($xy2['gH'][0], $xy2['gH'][1], $fmtHours($c10['TrainingSessionDurations']));

    // Cadre H
    $writeAt($xy2['managerName'][0], $xy2['managerName'][1], $mysoc->managers);
    $writeAt($xy2['managerStatus'][0], $xy2['managerStatus'][1], getDolGlobalString('DOLIOPI_BPF_MANAGER_STATUS'));

    // Footer
    $writeAt($xy2['town'][0], $xy2['town'][1], $mysoc->town);
    $writeAt($xy2['date'][0], $xy2['date'][1], dol_print_date(dol_now('tzuser'), 'day'));
    $writeAt($xy2['signatory'][0], $xy2['signatory'][1], $user->getFullName($langs) . ' - ' . $user->job);
    $writeAt($xy2['email'][0], $xy2['email'][1], $user->email);
    $writeAt($xy2['phone'][0], $xy2['phone'][1], $user->user_mobile);
}

// Debug grid overlay — useful to fine-tune the field coordinates above.
// Trigger with ?debug=grid in the URL. Draws a 10 mm grid with X / Y rulers
// on top of every imported page.
if (GETPOST('debug') == 'grid') {
    $pdf->SetTextColor(255, 0, 0);
    $pdf->SetDrawColor(255, 0, 0);
    $pdf->SetFont(pdf_getPDFFont($langs), '', 5);
    for ($p = 1; $p <= $pdf->getNumPages(); $p++) {
        $pdf->setPage($p);
        for ($x = 0; $x <= 210; $x += 10) {
            $pdf->Line($x, 0, $x, 297);
            $pdf->SetXY($x + 0.5, 1);
            $pdf->Cell(10, 3, (string) $x, 0, 0, 'L');
        }
        for ($y = 0; $y <= 297; $y += 10) {
            $pdf->Line(0, $y, 210, $y);
            $pdf->SetXY(1, $y + 0.5);
            $pdf->Cell(10, 3, (string) $y, 0, 0, 'L');
        }
    }
}

// Filename uses the end-of-fiscal-year as label ("BPF de 2025" = FY ending in 2025).
$filenameYear = (int) dol_print_date($lastDayOfFiscalYear, '%Y');
$filename     = 'BPF_' . $filenameYear . '.pdf';
$pdf->Output($filename, 'D');
exit;
