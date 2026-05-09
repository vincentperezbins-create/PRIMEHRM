<?php

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

function manual_role_options(): array
{
    return [
        'admin' => 'Admin',
        'office_head' => 'Office Head',
        'school_head' => 'School Head',
        'staff_user' => 'Staff/User',
    ];
}

function manual_role_title(string $role): string
{
    $roles = manual_role_options();
    return $roles[$role] ?? $roles['staff_user'];
}

function manual_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim((string) $slug, '-');
}

function manual_output_filename(string $role): string
{
    $safeRole = preg_replace('/[^A-Za-z0-9]+/', '_', manual_role_title($role));
    $safeRole = trim((string) $safeRole, '_');
    return 'PRIMEHR_' . $safeRole . '_User_Manual.docx';
}

function manual_role_steps(string $role): array
{
    $common = [
        [
            'title' => 'Login Page',
            'image' => 'login-page',
            'description' => 'This page allows authorized users to access PRIMEHR using their registered account.',
            'instructions' => [
                'Open the PRIMEHR login page in your browser.',
                'Enter your username and password.',
                'Click Login to continue to your dashboard.',
            ],
            'notes' => ['Do not share your account credentials with another employee.'],
        ],
        [
            'title' => 'My Submission',
            'image' => 'my-submission',
            'description' => 'This menu contains employee-required submissions such as 201 files, leave credits, OPCRF, and IPCRF.',
            'instructions' => [
                'Open the My Submission menu from the sidebar.',
                'Select the module you need to open.',
                'Review the page before adding or updating records.',
            ],
            'notes' => ['All users, including admin accounts, should maintain their own required submissions.'],
        ],
    ];

    $steps = [
        'admin' => [
            [
                'title' => 'Admin Dashboard',
                'image' => 'admin-dashboard',
                'description' => 'The dashboard shows system-wide summaries for users, schools, 201 files, and leave activities.',
                'instructions' => [
                    'Go to Home, then click Dashboard.',
                    'Review the summary cards and recent activity tables.',
                    'Use the sidebar to open the module you need to manage.',
                ],
                'notes' => ['Dashboard counts depend on the records currently saved in the database.'],
            ],
            [
                'title' => 'User List',
                'image' => 'user-list',
                'description' => 'The user list is used to add, update, filter, and manage employee accounts and roles.',
                'instructions' => [
                    'Open Settings, then click User List.',
                    'Use the filters to find users by personnel type, school, division unit, or office unit.',
                    'Click Add to create a user or Update to edit an existing account.',
                ],
                'notes' => ['Assign the correct role, division unit, office unit, and validator tasks to avoid access issues.'],
            ],
            [
                'title' => '201 File Validation',
                'image' => '201-validation',
                'description' => 'This page is used to review uploaded 201 file documents and mark them as approved or returned.',
                'instructions' => [
                    'Open 201 Files, then click Validate 201 Files.',
                    'View the uploaded document.',
                    'Approve the file if correct, or return it with remarks if correction is needed.',
                ],
                'notes' => ['Always provide clear remarks when returning a document.'],
            ],
            [
                'title' => 'Leave Management',
                'image' => 'leave-management',
                'description' => 'The leave module handles leave applications, balances, ledgers, transactions, and reports.',
                'instructions' => [
                    'Open Leave Management from the sidebar.',
                    'Choose Leave Applications, Leave Balances, Ledger, or Transactions.',
                    'Review records carefully before approving or rejecting requests.',
                ],
                'notes' => ['Leave balances and ledgers are affected by approved applications and credit processes.'],
            ],
            [
                'title' => 'Performance Forms',
                'image' => 'performance-forms',
                'description' => 'This module manages Office/Unit OPCRF and employee IPCRF submissions.',
                'instructions' => [
                    'Open Performance Forms from the sidebar.',
                    'Select Office/Unit OPCRF, Employee IPCRF, or Offices / Units.',
                    'Validate submissions or assign office/unit heads as needed.',
                ],
                'notes' => ['Office Head is used for higher-level signing, while Unit Head is used for OPCRF submission.'],
            ],
        ],
        'office_head' => [
            [
                'title' => 'Office Head Dashboard',
                'image' => 'office-head-dashboard',
                'description' => 'The dashboard provides quick access to assigned office tasks and submissions.',
                'instructions' => [
                    'Log in using your assigned office head account.',
                    'Review available menus in the sidebar.',
                    'Open the module that requires your review or signature.',
                ],
                'notes' => ['Office Head assignments are managed by the administrator.'],
            ],
            [
                'title' => 'Office/Unit OPCRF',
                'image' => 'office-unit-opcrf',
                'description' => 'This page is used to monitor or submit OPCRF records for assigned offices or units.',
                'instructions' => [
                    'Open My Submission, then click Office/Unit OPCRF.',
                    'Review existing OPCRF submissions.',
                    'Add or update records according to your assigned office/unit responsibility.',
                ],
                'notes' => ['Unit Head is normally responsible for OPCRF submission; Office Head is higher in the signing flow.'],
            ],
            [
                'title' => 'Leave Review',
                'image' => 'leave-review',
                'description' => 'Assigned heads may review leave-related records depending on system permissions.',
                'instructions' => [
                    'Open the leave-related menu available to your account.',
                    'Review application details and supporting information.',
                    'Proceed only when the information is complete and correct.',
                ],
                'notes' => ['If a menu is not visible, request the administrator to check your role or validator task assignment.'],
            ],
        ],
        'school_head' => [
            [
                'title' => 'School Dashboard',
                'image' => 'school-dashboard',
                'description' => 'The school dashboard summarizes school employee records and school-level leave monitoring.',
                'instructions' => [
                    'Go to Home, then click School Dashboard.',
                    'Review the school summary cards.',
                    'Use the school menus to inspect employees, 201 files, and leave records.',
                ],
                'notes' => ['School Head access depends on the school assignment saved in the user profile.'],
            ],
            [
                'title' => 'School Employee 201 Files',
                'image' => 'school-201-files',
                'description' => 'This page shows uploaded 201 files of employees under the assigned school.',
                'instructions' => [
                    'Click School Employee 201 Files Uploaded from the sidebar.',
                    'Search or filter the employee records.',
                    'Open the needed employee or document record for review.',
                ],
                'notes' => ['Returned documents should include remarks so employees know what to correct.'],
            ],
            [
                'title' => 'School Leave Applications',
                'image' => 'school-leave-applications',
                'description' => 'This page helps the school monitor leave applications for school personnel.',
                'instructions' => [
                    'Open School Leave, then click Leave Applications.',
                    'Review the leave date, type, purpose, and status.',
                    'Use the available action buttons based on your permission.',
                ],
                'notes' => ['The school principal is used as the school-level signatory where applicable.'],
            ],
        ],
        'staff_user' => [
            [
                'title' => 'My Dashboard',
                'image' => 'my-dashboard',
                'description' => 'The dashboard shows your 201 file status, leave balances, and recent leave activity.',
                'instructions' => [
                    'Open Home, then click My Dashboard.',
                    'Review your 201 file progress and leave summary cards.',
                    'Use the quick buttons to apply for leave or view your leave balance.',
                ],
                'notes' => ['Keep your records updated to avoid incomplete submissions.'],
            ],
            [
                'title' => 'Upload 201 Files',
                'image' => 'upload-201-files',
                'description' => 'This page lets you upload required documents for your 201 file.',
                'instructions' => [
                    'Open My Submission, then click My 201 Files.',
                    'Choose the document type that you need to upload.',
                    'Attach the correct file and submit.',
                ],
                'notes' => ['Use clear scanned copies and check that the correct document type is selected.'],
            ],
            [
                'title' => 'Apply Leave',
                'image' => 'apply-leave',
                'description' => 'This page is used to submit a new leave application.',
                'instructions' => [
                    'Open My Leave, then click Apply Leave.',
                    'Select the leave type and date range.',
                    'Enter the purpose or remarks, then submit the application.',
                ],
                'notes' => ['Credit-based leave types are checked against your available balance.'],
            ],
            [
                'title' => 'My IPCRF',
                'image' => 'my-ipcrf',
                'description' => 'This page is used to submit and monitor your individual performance form.',
                'instructions' => [
                    'Open My Submission, then click My IPCRF.',
                    'Add or view your IPCRF submission.',
                    'Check status and remarks after validation.',
                ],
                'notes' => ['Returned submissions should be updated based on the validator remarks.'],
            ],
        ],
    ];

    return array_merge($common, $steps[$role] ?? $steps['staff_user']);
}

function manual_find_screenshot(string $role, int $stepNumber, string $imageKey): ?string
{
    $baseDir = realpath(__DIR__ . '/../../manual_images/' . $role);
    if ($baseDir === false || !is_dir($baseDir)) {
        return null;
    }

    $allowed = ['png', 'jpg', 'jpeg'];
    $files = [];
    foreach ($allowed as $ext) {
        $files = array_merge($files, glob($baseDir . DIRECTORY_SEPARATOR . '*.' . $ext) ?: []);
        $files = array_merge($files, glob($baseDir . DIRECTORY_SEPARATOR . '*.' . strtoupper($ext)) ?: []);
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    $imageKey = manual_slug($imageKey);
    foreach ($files as $file) {
        $name = manual_slug(pathinfo($file, PATHINFO_FILENAME));
        if ($name === $imageKey || str_starts_with($name, sprintf('%02d-', $stepNumber) . $imageKey) || str_starts_with($name, $stepNumber . '-' . $imageKey)) {
            return $file;
        }
    }

    return $files[$stepNumber - 1] ?? null;
}

function manual_add_note_box($section, array $notes): void
{
    if (!$notes) {
        return;
    }

    $section->addText('Important notes', 'ManualNoteTitle', ['spaceBefore' => 120]);
    foreach ($notes as $note) {
        $section->addListItem($note, 0, 'ManualNoteText', 'manualBullet');
    }
}

function manual_add_footer($section): void
{
    $footer = $section->addFooter();
    $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', ['size' => 9, 'color' => '667085'], 'ManualFooter');
}

function manual_generate_docx(string $role, ?string $savePath = null): string
{
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new RuntimeException('PHPWord is not installed. Run: composer require phpoffice/phpword');
    }
    require_once $autoload;

    $roles = manual_role_options();
    if (!isset($roles[$role])) {
        $role = 'staff_user';
    }

    $roleTitle = manual_role_title($role);
    $systemTitle = 'PRIMEHR Human Resource Management System';
    $phpWord = new PhpWord();
    $phpWord->getSettings()->setThemeFontLang(new Language(Language::EN_US));
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(10.5);

    $phpWord->addTitleStyle(1, ['name' => 'Arial', 'size' => 18, 'bold' => true, 'color' => '155EEF'], ['spaceBefore' => 240, 'spaceAfter' => 140]);
    $phpWord->addTitleStyle(2, ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => '1849A9'], ['spaceBefore' => 220, 'spaceAfter' => 100]);
    $phpWord->addTitleStyle(3, ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => '344054'], ['spaceBefore' => 120, 'spaceAfter' => 80]);
    $phpWord->addParagraphStyle('ManualNormal', ['spaceAfter' => 100, 'lineHeight' => 1.15]);
    $phpWord->addParagraphStyle('ManualCaption', ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);
    $phpWord->addParagraphStyle('ManualFooter', ['alignment' => Jc::CENTER]);
    $phpWord->addFontStyle('ManualCaptionText', ['italic' => true, 'size' => 9, 'color' => '667085']);
    $phpWord->addFontStyle('ManualLabel', ['bold' => true, 'color' => '344054']);
    $phpWord->addFontStyle('ManualNoteTitle', ['bold' => true, 'color' => 'B54708']);
    $phpWord->addFontStyle('ManualNoteText', ['color' => '475467']);
    $phpWord->addNumberingStyle('manualNumber', [
        'type' => 'multilevel',
        'levels' => [
            ['format' => 'decimal', 'text' => '%1.', 'left' => 360, 'hanging' => 180],
        ],
    ]);
    $phpWord->addNumberingStyle('manualBullet', [
        'type' => 'multilevel',
        'levels' => [
            ['format' => 'bullet', 'text' => '•', 'left' => 360, 'hanging' => 180],
        ],
    ]);

    $sectionStyle = [
        'marginTop' => Converter::inchToTwip(0.65),
        'marginBottom' => Converter::inchToTwip(0.65),
        'marginLeft' => Converter::inchToTwip(0.7),
        'marginRight' => Converter::inchToTwip(0.7),
    ];

    $cover = $phpWord->addSection($sectionStyle);
    manual_add_footer($cover);
    $cover->addTextBreak(4);
    $cover->addText('Department of Education', ['size' => 13, 'bold' => true, 'color' => '1849A9'], ['alignment' => Jc::CENTER]);
    $cover->addText('Schools Division Office 1 Pangasinan', ['size' => 11, 'color' => '475467'], ['alignment' => Jc::CENTER, 'spaceAfter' => 360]);
    $cover->addText($systemTitle, ['size' => 24, 'bold' => true, 'color' => '155EEF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);
    $cover->addText('User Manual', ['size' => 18, 'bold' => true, 'color' => '344054'], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
    $cover->addText($roleTitle . ' Role', ['size' => 15, 'bold' => true, 'color' => '1849A9'], ['alignment' => Jc::CENTER]);
    $cover->addTextBreak(6);
    $cover->addText('Generated on ' . date('F d, Y'), ['size' => 10, 'color' => '667085'], ['alignment' => Jc::CENTER]);

    $toc = $phpWord->addSection($sectionStyle);
    manual_add_footer($toc);
    $toc->addTitle('Table of Contents', 1);
    $toc->addTOC(['name' => 'Arial', 'size' => 10], ['tabLeader' => 'dot']);
    $toc->addPageBreak();

    $section = $phpWord->addSection($sectionStyle);
    manual_add_footer($section);

    $section->addTitle('System Overview', 1);
    $section->addText('This manual guides the ' . $roleTitle . ' through the main PRIMEHR procedures. Screenshots are loaded automatically from the role screenshot folder and inserted beside the matching step.', [], 'ManualNormal');
    $section->addText('Screenshot folder: manual_images/' . $role . '/', ['italic' => true, 'color' => '667085'], 'ManualNormal');

    $steps = manual_role_steps($role);
    $figure = 1;
    foreach ($steps as $index => $step) {
        $stepNumber = $index + 1;
        $section->addTitle('Step ' . $stepNumber . ': ' . $step['title'], 1);
        $section->addText('Page title: ' . $step['title'], 'ManualLabel', 'ManualNormal');
        $section->addText($step['description'], [], 'ManualNormal');

        $imagePath = manual_find_screenshot($role, $stepNumber, $step['image']);
        if ($imagePath && file_exists($imagePath)) {
            $section->addImage($imagePath, [
                'width' => Converter::inchToPixel(6.3),
                'alignment' => Jc::CENTER,
            ]);
            $section->addText('Figure ' . $figure . '. ' . $step['title'] . ' Page', 'ManualCaptionText', 'ManualCaption');
            $figure++;
        } else {
            $section->addText('Screenshot placeholder: add an image named ' . $step['image'] . '.png in manual_images/' . $role . '/', ['italic' => true, 'color' => 'B42318'], 'ManualNormal');
        }

        $section->addTitle('Instructions', 3);
        foreach ($step['instructions'] as $instruction) {
            $section->addListItem($instruction, 0, null, 'manualNumber');
        }
        manual_add_note_box($section, $step['notes'] ?? []);
        if ($index < count($steps) - 1) {
            $section->addPageBreak();
        }
    }

    $savePath = $savePath ?: (__DIR__ . '/../../manual_output/' . manual_output_filename($role));
    $outputDir = dirname($savePath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0775, true);
    }

    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($savePath);

    return $savePath;
}
