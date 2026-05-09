<?php

function org_division_units(PDO $pdo): array {
    return $pdo->query("
        SELECT division_unit_id, unit_code, unit_name
        FROM division_units
        WHERE is_active = 1
        ORDER BY sort_order, unit_name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function org_office_unit_groups(PDO $pdo): array {
    $rows = $pdo->query("
        SELECT
            ou.office_unit_id,
            ou.unit_code,
            ou.unit_name,
            du.division_unit_id,
            du.unit_code AS division_code,
            du.unit_name AS division_name
        FROM office_units ou
        JOIN division_units du ON du.division_unit_id = ou.division_unit_id
        WHERE ou.is_active = 1 AND du.is_active = 1
        ORDER BY du.sort_order, ou.sort_order, ou.unit_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    foreach ($rows as $row) {
        $code = $row['division_code'];
        if (!isset($groups[$code])) {
            $groups[$code] = [
                'label' => $row['division_name'],
                'items' => [],
            ];
        }

        $groups[$code]['items'][] = $row;
    }

    return $groups;
}
