<?php

function form6_template_path(): string {
    return __DIR__ . '/../templates/form6_template.xlsx';
}

function form6_safe_filename(string $value): string {
    $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($value));
    $value = trim((string) $value, '._-');

    return $value !== '' ? $value : 'form6';
}

function form6_full_name(array $row): string {
    $parts = [
        trim((string) ($row['last_name'] ?? '')),
        trim((string) ($row['first_name'] ?? '')),
        trim((string) ($row['middle_name'] ?? '')),
    ];

    return trim(implode(', ', array_filter($parts, static fn($part) => $part !== '')));
}

function form6_position(array $row): string {
    $position = trim((string) ($row['position_title'] ?? ''));

    return $position !== '' ? $position : trim((string) ($row['position_id'] ?? ''));
}

function form6_salary(array $row): string {
    $salaryGrade = trim((string) ($row['salary_grade'] ?? ''));

    return $salaryGrade !== '' ? 'SG ' . $salaryGrade : '';
}

function form6_office(array $row): string {
    foreach (['office_name', 'schoolname', 'school_id'] as $field) {
        $value = trim((string) ($row[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return 'SDO I Pangasinan';
}

function form6_date(?string $value): string {
    if (!$value) {
        return '';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('F j, Y', $timestamp) : $value;
}

function form6_number(float $value): string {
    return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
}

function form6_person_name(array $row): string {
    $middle = trim((string) ($row['middle_name'] ?? ''));
    $parts = [
        trim((string) ($row['first_name'] ?? '')),
        $middle !== '' ? substr($middle, 0, 1) . '.' : '',
        trim((string) ($row['last_name'] ?? '')),
        trim((string) ($row['name_extension'] ?? '')),
    ];

    return trim(implode(' ', array_filter($parts, static fn($part) => $part !== '')));
}

function form6_fetch_user(PDO $pdo, ?int $userId): ?array {
    if (!$userId) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT u.first_name, u.middle_name, u.last_name, u.name_extension, p.position_title
        FROM sdopang1_user u
        LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
        WHERE u.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function form6_school_level_group(array $row): string {
    $schoolType = (int) ($row['school_type'] ?? 0);
    if ($schoolType === 1) {
        return 'elementary';
    }

    if (in_array($schoolType, [2, 3], true)) {
        return 'high';
    }

    $schoolName = strtolower((string) ($row['schoolname'] ?? ''));
    if (strpos($schoolName, 'elementary') !== false || preg_match('/\bes\b/', $schoolName)) {
        return 'elementary';
    }

    return 'high';
}

function form6_is_division_employee(array $row): bool {
    $divisionUnit = trim((string) ($row['division_unit_code'] ?? ($row['division_unit'] ?? '')));
    if ($divisionUnit !== '' && strcasecmp($divisionUnit, 'School') !== 0) {
        return true;
    }

    if (($row['office_category'] ?? '') === 'Division Office') {
        return true;
    }

    return empty($row['school_id']) || empty($row['schoolname']) || (string) ($row['school_id'] ?? '') === '0';
}

function form6_designated_school_ao(PDO $pdo, array $row): array {
    $stmt = $pdo->prepare("
        SELECT u.first_name, u.middle_name, u.last_name, u.name_extension, p.position_title
        FROM sdopang1_user u
        JOIN sdopang1_position p ON p.position_id = u.position_id
        WHERE u.school_id = ?
          AND p.position_title LIKE '%Administrative Officer II%'
          AND COALESCE(u.status, 'active') = 'active'
        ORDER BY u.last_name, u.first_name
        LIMIT 1
    ");
    $stmt->execute([$row['school_id'] ?? '']);
    $ao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ao) {
        return ['name' => 'Designated AO II', 'position' => 'Administrative Officer II'];
    }

    return [
        'name' => form6_person_name($ao),
        'position' => $ao['position_title'] ?: 'Administrative Officer II',
    ];
}

function form6_principal_signatory(PDO $pdo, array $row): array {
    $principal = form6_fetch_user($pdo, (int) ($row['principalID'] ?? 0));

    if (!$principal && !empty($row['school_id'])) {
        $stmt = $pdo->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, u.name_extension, p.position_title
            FROM sdopang1_user u
            JOIN sdopang1_position p ON p.position_id = u.position_id
            WHERE u.school_id = ?
              AND COALESCE(u.status, 'active') = 'active'
              AND (
                  p.position_title LIKE '%Principal%'
                  OR p.position_title LIKE '%School Head%'
                  OR p.position_title LIKE '%Head Teacher%'
              )
            ORDER BY
                CASE
                    WHEN p.position_title LIKE '%Principal%' THEN 1
                    WHEN p.position_title LIKE '%School Head%' THEN 2
                    ELSE 3
                END,
                p.salary_grade DESC,
                u.last_name,
                u.first_name
            LIMIT 1
        ");
        $stmt->execute([$row['school_id']]);
        $principal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if (!$principal) {
        return ['name' => 'Principal', 'position' => 'School Principal'];
    }

    return [
        'name' => form6_person_name($principal),
        'position' => $principal['position_title'] ?: 'School Principal',
    ];
}

function form6_division_unit_label(array $row): string {
    $divisionUnit = strtoupper(trim((string) ($row['division_unit_code'] ?? ($row['division_unit'] ?? ''))));
    if (in_array($divisionUnit, ['CID', 'SGOD'], true)) {
        return $divisionUnit;
    }

    if (in_array($divisionUnit, ['OSDS', 'DISTRICT', 'AOV'], true)) {
        return 'AOV';
    }

    $officeText = strtolower(trim(implode(' ', [
        (string) ($row['office_unit_name'] ?? ''),
        (string) ($row['office_unit_code'] ?? ''),
        (string) ($row['office_name'] ?? ''),
        (string) ($row['office_type'] ?? ''),
        (string) ($row['parent_office_name'] ?? ''),
        (string) ($row['parent_office_type'] ?? ''),
    ])));

    if (strpos($officeText, 'cid') !== false || strpos($officeText, 'curriculum') !== false) {
        return 'CID';
    }

    if (strpos($officeText, 'sgod') !== false || strpos($officeText, 'governance') !== false) {
        return 'SGOD';
    }

    return 'AOV';
}

function form6_division_unit_head(PDO $pdo, array $row): array {
    $officeIds = array_filter([
        (int) ($row['office_id'] ?? 0),
        (int) ($row['parent_office_id'] ?? 0),
    ]);

    foreach ($officeIds as $officeId) {
        $stmt = $pdo->prepare("
            SELECT o.office_head, o.office_name, o.office_type
            FROM sdopang1_offices o
            WHERE o.office_id = ?
            LIMIT 1
        ");
        $stmt->execute([$officeId]);
        $office = $stmt->fetch(PDO::FETCH_ASSOC);
        $head = form6_fetch_user($pdo, (int) ($office['office_head'] ?? 0));

        if ($head) {
            return [
                'name' => form6_person_name($head),
                'position' => $head['position_title'] ?: ($office['office_name'] ?? 'Division Unit Head'),
            ];
        }
    }

    $unit = form6_division_unit_label($row);
    $fallbackPositions = [
        'SGOD' => 'SGOD Chief',
        'CID' => 'CID Chief',
        'AOV' => 'Administrative Officer V',
    ];

    return [
        'name' => $unit . ' Signatory',
        'position' => $fallbackPositions[$unit] ?? 'Division Unit Head',
    ];
}

function form6_asds_signatory(PDO $pdo, array $row): array {
    $level = form6_school_level_group($row);
    $keyword = $level === 'elementary' ? 'elem' : 'high';

    $stmt = $pdo->prepare("
        SELECT u.first_name, u.middle_name, u.last_name, u.name_extension, p.position_title, o.office_name
        FROM sdopang1_user u
        JOIN sdopang1_position p ON p.position_id = u.position_id
        LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
        WHERE p.position_title LIKE '%Assistant Schools Division Superintendent%'
          AND (
              LOWER(COALESCE(o.office_name, '')) LIKE ?
              OR LOWER(COALESCE(u.grade_level_taught, '')) LIKE ?
              OR LOWER(COALESCE(u.specialization, '')) LIKE ?
          )
        ORDER BY u.last_name, u.first_name
        LIMIT 1
    ");
    $stmt->execute(['%' . $keyword . '%', '%' . $keyword . '%', '%' . $keyword . '%']);
    $asds = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asds) {
        $stmt = $pdo->query("
            SELECT u.first_name, u.middle_name, u.last_name, u.name_extension, p.position_title
            FROM sdopang1_user u
            JOIN sdopang1_position p ON p.position_id = u.position_id
            WHERE p.position_title LIKE '%Assistant Schools Division Superintendent%'
            ORDER BY u.last_name, u.first_name
            LIMIT 1
        ");
        $asds = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$asds) {
        return ['name' => 'ASDS - ' . ($level === 'elementary' ? 'Elementary' : 'High School'), 'position' => 'Assistant Schools Division Superintendent'];
    }

    return [
        'name' => form6_person_name($asds),
        'position' => $asds['position_title'] ?: 'Assistant Schools Division Superintendent',
    ];
}

function form6_signatories(PDO $pdo, array $row): array {
    if (form6_is_division_employee($row)) {
        return [
            'certifier' => ['name' => 'MARCOS P. DOMASIN JR.', 'position' => 'Administrative Officer IV - Personnel'],
            'recommender' => form6_division_unit_head($pdo, $row),
            'approver' => form6_asds_signatory($pdo, $row),
        ];
    }

    return [
        'certifier' => form6_designated_school_ao($pdo, $row),
        'recommender' => form6_principal_signatory($pdo, $row),
        'approver' => form6_asds_signatory($pdo, $row),
    ];
}

function form6_leave_credit_summary(PDO $pdo, array $row): array {
    $applicationId = (int) ($row['application_id'] ?? 0);
    $userId = (int) ($row['user_id'] ?? 0);
    $asOf = (string) (($row['approved_at'] ?? '') ?: ($row['created_at'] ?? date('Y-m-d H:i:s')));

    $typeStmt = $pdo->query("
        SELECT leave_type_id, leave_code
        FROM leave_types
        WHERE leave_code IN ('VL', 'SL')
    ");
    $typeIds = [];
    foreach ($typeStmt as $type) {
        $typeIds[strtoupper($type['leave_code'])] = (int) $type['leave_type_id'];
    }

    $summary = [
        'VL' => ['earned' => 0.0, 'less' => 0.0, 'balance' => 0.0],
        'SL' => ['earned' => 0.0, 'less' => 0.0, 'balance' => 0.0],
    ];

    foreach ($summary as $code => $_) {
        $typeId = $typeIds[$code] ?? 0;
        if (!$userId || !$typeId) {
            continue;
        }

        $beforeStmt = $pdo->prepare("
            SELECT COALESCE(SUM(
                CASE
                    WHEN transaction_type IN ('earn', 'adjust') THEN days
                    ELSE -days
                END
            ), 0)
            FROM leave_transactions
            WHERE user_id = ?
              AND leave_type_id = ?
              AND created_at <= ?
              AND NOT (COALESCE(source, '') = 'application' AND reference_id = ?)
        ");
        $beforeStmt->execute([$userId, $typeId, $asOf, $applicationId]);
        $earned = (float) $beforeStmt->fetchColumn();

        $lessStmt = $pdo->prepare("
            SELECT COALESCE(SUM(days), 0)
            FROM leave_transactions
            WHERE user_id = ?
              AND leave_type_id = ?
              AND source = 'application'
              AND reference_id = ?
              AND transaction_type NOT IN ('earn', 'adjust')
        ");
        $lessStmt->execute([$userId, $typeId, $applicationId]);
        $less = abs((float) $lessStmt->fetchColumn());

        if ($less <= 0 && strtoupper((string) ($row['leave_code'] ?? '')) === $code) {
            $less = (float) ($row['days'] ?? 0);
        }

        $summary[$code] = [
            'earned' => $earned,
            'less' => $less,
            'balance' => $earned - $less,
        ];
    }

    return $summary;
}

function form6_leave_type_cell(string $code): ?string {
    $map = [
        'VL' => 'C12',
        'FL' => 'C14',
        'SL' => 'C16',
        'MAT' => 'C18',
        'MAT60' => 'C18',
        'EML' => 'C18',
        'MLA' => 'C18',
        'PAT' => 'C20',
        'SPL' => 'C22',
        'SOLO' => 'C24',
        'STUDY' => 'C26',
        'VAWC' => 'C28',
        'REHAB' => 'C30',
        'SWL' => 'C32',
        'SEL' => 'C34',
        'ADOPT' => 'C36',
        'TERMINAL' => 'O42',
        'REGMON' => 'O40',
        'SPMON' => 'O40',
    ];

    return $map[strtoupper($code)] ?? null;
}

function form6_detail_cell(string $code): ?string {
    $code = strtoupper($code);

    if (in_array($code, ['VL', 'FL', 'SPL'], true)) {
        return 'P14';
    }

    if ($code === 'SL') {
        return 'P20';
    }

    if ($code === 'SWL') {
        return 'O28';
    }

    if ($code === 'STUDY') {
        return 'O34';
    }

    return null;
}

function form6_cell_col(string $cell): int {
    preg_match('/^([A-Z]+)/', $cell, $matches);
    $col = 0;

    foreach (str_split($matches[1] ?? 'A') as $char) {
        $col = ($col * 26) + (ord($char) - 64);
    }

    return $col;
}

function form6_cell_row(string $cell): int {
    preg_match('/(\d+)$/', $cell, $matches);

    return (int) ($matches[1] ?? 1);
}

function form6_set_cell(DOMDocument $dom, string $cell, string $value): void {
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $cellNode = $xpath->query('//x:c[@r="' . $cell . '"]')->item(0);
    if (!$cellNode instanceof DOMElement) {
        $rowNumber = form6_cell_row($cell);
        $rowNode = $xpath->query('//x:row[@r="' . $rowNumber . '"]')->item(0);

        if (!$rowNode instanceof DOMElement) {
            $sheetData = $xpath->query('//x:sheetData')->item(0);
            $rowNode = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row');
            $rowNode->setAttribute('r', (string) $rowNumber);
            $sheetData->appendChild($rowNode);
        }

        $cellNode = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c');
        $cellNode->setAttribute('r', $cell);

        $insertBefore = null;
        foreach ($rowNode->getElementsByTagNameNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c') as $existingCell) {
            if (form6_cell_col($existingCell->getAttribute('r')) > form6_cell_col($cell)) {
                $insertBefore = $existingCell;
                break;
            }
        }

        $rowNode->insertBefore($cellNode, $insertBefore);
    }

    while ($cellNode->firstChild) {
        $cellNode->removeChild($cellNode->firstChild);
    }

    $cellNode->setAttribute('t', 'inlineStr');
    $inline = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'is');
    $text = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't');
    $text->appendChild($dom->createTextNode($value));
    $inline->appendChild($text);
    $cellNode->appendChild($inline);
}

function form6_populate_xlsx(string $templatePath, string $outputPath, array $row, PDO $pdo): void {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('The PHP zip extension is required to generate Form 6. Enable extension=zip in the php.ini used by Apache, then restart Apache.');
    }

    if (!is_file($templatePath)) {
        throw new RuntimeException('Form 6 template is missing.');
    }

    if (!copy($templatePath, $outputPath)) {
        throw new RuntimeException('Unable to prepare Form 6 file.');
    }

    $zip = new ZipArchive();
    if ($zip->open($outputPath) !== true) {
        throw new RuntimeException('Unable to open Form 6 file.');
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        throw new RuntimeException('Form 6 worksheet is missing.');
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($sheetXml);

    $code = strtoupper((string) ($row['leave_code'] ?? ''));
    $reason = trim((string) ($row['reason'] ?? ''));
    $leaveName = trim((string) ($row['leave_name'] ?? ''));
    $detail = $reason !== '' ? $reason : $leaveName;
    $typeCell = form6_leave_type_cell($code);

    form6_set_cell($dom, 'B6', form6_office($row));
    form6_set_cell($dom, 'J6', form6_full_name($row));
    form6_set_cell($dom, 'F7', form6_date((string) ($row['created_at'] ?? date('Y-m-d'))));
    form6_set_cell($dom, 'L7', form6_position($row));
    form6_set_cell($dom, 'S7', form6_salary($row));

    if ($typeCell) {
        form6_set_cell($dom, $typeCell, 'X');
    } else {
        form6_set_cell($dom, 'C40', 'X');
    }

    form6_set_cell($dom, 'D42', $leaveName !== '' ? $leaveName : $code);

    $detailCell = form6_detail_cell($code);
    if ($detailCell && $detail !== '') {
        form6_set_cell($dom, $detailCell, $detail);
    }
    form6_set_cell($dom, 'D46', form6_number((float) ($row['days'] ?? 0)) . ' working day(s)');
    form6_set_cell($dom, 'D49', form6_date((string) ($row['date_from'] ?? '')) . ' - ' . form6_date((string) ($row['date_to'] ?? '')));
    form6_set_cell($dom, 'P50', form6_full_name($row));
    form6_set_cell($dom, 'P48', 'X');

    $signatories = form6_signatories($pdo, $row);
    form6_set_cell($dom, 'E62', $signatories['certifier']['name']);
    form6_set_cell($dom, 'E63', $signatories['certifier']['position']);
    form6_set_cell($dom, 'P62', $signatories['recommender']['name']);
    form6_set_cell($dom, 'P63', $signatories['recommender']['position']);

    $credits = form6_leave_credit_summary($pdo, $row);
    form6_set_cell($dom, 'E55', form6_date((string) (($row['approved_at'] ?? '') ?: ($row['created_at'] ?? date('Y-m-d')))));
    form6_set_cell($dom, 'G58', form6_number($credits['VL']['earned']));
    form6_set_cell($dom, 'J58', form6_number($credits['SL']['earned']));
    form6_set_cell($dom, 'G59', form6_number($credits['VL']['less']));
    form6_set_cell($dom, 'J59', form6_number($credits['SL']['less']));
    form6_set_cell($dom, 'G60', form6_number($credits['VL']['balance']));
    form6_set_cell($dom, 'J60', form6_number($credits['SL']['balance']));

    $payStatus = ($row['pay_status'] ?? 'with_pay') === 'without_pay' ? 'without_pay' : 'with_pay';
    if ($payStatus === 'without_pay') {
        form6_set_cell($dom, 'C66', form6_number((float) ($row['days'] ?? 0)));
    } else {
        form6_set_cell($dom, 'C65', form6_number((float) ($row['days'] ?? 0)));
    }

    form6_set_cell($dom, 'I71', $signatories['approver']['name']);
    form6_set_cell($dom, 'I72', $signatories['approver']['position']);

    $zip->addFromString('xl/worksheets/sheet1.xml', $dom->saveXML());
    $zip->close();
}

function form6_application_row(PDO $pdo, int $applicationId): array {
    $stmt = $pdo->prepare("
        SELECT
            la.*,
            lt.leave_code,
            lt.leave_name,
            u.last_name,
            u.first_name,
            u.middle_name,
            u.position_id,
            u.school_id,
            COALESCE(u.division_unit, 'School') AS division_unit,
            du.unit_code AS division_unit_code,
            du.unit_name AS division_unit_name,
            ou.unit_code AS office_unit_code,
            ou.unit_name AS office_unit_name,
            p.position_title,
            p.salary_grade,
            s.schoolname,
            s.principalID,
            s.school_type,
            o.office_name,
            o.office_type,
            o.office_head,
            o.office_category,
            o.parent_office_id,
            po.office_name AS parent_office_name,
            po.office_type AS parent_office_type
        FROM leave_applications la
        JOIN leave_types lt ON lt.leave_type_id = la.leave_type_id
        JOIN sdopang1_user u ON u.user_id = la.user_id
        LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
        LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
        LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
        LEFT JOIN sdopang1_offices po ON po.office_id = o.parent_office_id
        LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
        LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id
        WHERE la.application_id = ?
        LIMIT 1
    ");
    $stmt->execute([$applicationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException('Leave application not found.');
    }

    return $row;
}
