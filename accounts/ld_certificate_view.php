<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/ld_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$certificateId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$certificate = ld_generated_certificate_by_id($pdo, $certificateId);
if (!$certificate || !ld_can_view_generated_certificate($certificate, $currentUser)) {
    http_response_code(404);
    die('Certificate not found.');
}

$h = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$issuedDate = $certificate['generated_at'] ? date('F d, Y', strtotime((string) $certificate['generated_at'])) : date('F d, Y');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificate <?= $h($certificate['certificate_no']) ?></title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: #eef2f7;
      color: #0f172a;
      font-family: Georgia, "Times New Roman", serif;
    }
    .cert-toolbar {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      padding: 16px;
      font-family: Arial, sans-serif;
    }
    .cert-toolbar a,
    .cert-toolbar button {
      border: 1px solid #2563eb;
      border-radius: 8px;
      background: #2563eb;
      color: #fff;
      padding: 9px 14px;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
    }
    .cert-toolbar a { background: #fff; color: #2563eb; }
    .certificate-wrap {
      width: min(1100px, calc(100vw - 32px));
      margin: 0 auto 32px;
      background: #fff;
      padding: 28px;
      box-shadow: 0 18px 45px rgba(15, 23, 42, .15);
    }
    .certificate {
      min-height: 720px;
      border: 10px solid #1d4ed8;
      outline: 2px solid #93c5fd;
      outline-offset: -22px;
      padding: 74px 76px 52px;
      text-align: center;
      position: relative;
    }
    .cert-kicker {
      font-family: Arial, sans-serif;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 3px;
      color: #2563eb;
      text-transform: uppercase;
    }
    .certificate h1 {
      margin: 18px 0 8px;
      font-size: 58px;
      letter-spacing: 1px;
      color: #0f172a;
      font-weight: 700;
    }
    .cert-subtitle {
      margin: 0 0 46px;
      font-family: Arial, sans-serif;
      color: #475569;
      font-size: 16px;
      letter-spacing: 2px;
      text-transform: uppercase;
    }
    .cert-line {
      margin: 0 auto 18px;
      max-width: 760px;
      font-size: 20px;
      line-height: 1.6;
      color: #334155;
    }
    .cert-name {
      margin: 16px auto 20px;
      padding-bottom: 10px;
      max-width: 780px;
      border-bottom: 2px solid #1e293b;
      font-size: 44px;
      font-weight: 700;
      color: #111827;
      line-height: 1.15;
    }
    .cert-training {
      margin: 12px auto 18px;
      max-width: 820px;
      font-size: 28px;
      font-weight: 700;
      line-height: 1.25;
      color: #1d4ed8;
    }
    .cert-meta {
      margin: 8px auto 54px;
      max-width: 760px;
      font-family: Arial, sans-serif;
      color: #475569;
      font-size: 15px;
      line-height: 1.6;
    }
    .cert-footer {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
      align-items: end;
      margin-top: 46px;
      font-family: Arial, sans-serif;
    }
    .signature {
      border-top: 2px solid #1e293b;
      padding-top: 10px;
      color: #334155;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .cert-number {
      position: absolute;
      left: 34px;
      bottom: 24px;
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #64748b;
    }
    @media print {
      body { background: #fff; }
      .cert-toolbar { display: none; }
      .certificate-wrap {
        width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
      .certificate {
        min-height: 100vh;
        border-width: 8px;
      }
      @page { size: landscape; margin: 12mm; }
    }
  </style>
</head>
<body>
  <div class="cert-toolbar">
    <a href="ld_my_trainings.php">Back</a>
    <button type="button" onclick="window.print()">Print / Save PDF</button>
  </div>
  <main class="certificate-wrap">
    <section class="certificate">
      <div class="cert-kicker">PRIMEHR Learning &amp; Development</div>
      <h1>Certificate</h1>
      <p class="cert-subtitle">of Participation</p>
      <p class="cert-line">This certificate is proudly presented to</p>
      <div class="cert-name"><?= $h($certificate['participant_name']) ?></div>
      <p class="cert-line">for successfully participating in</p>
      <div class="cert-training"><?= $h($certificate['training_title']) ?></div>
      <div class="cert-meta">
        <?= $h($certificate['inclusive_date'] ?: 'Division Training') ?>
        <?php if ($certificate['venue']): ?><br><?= $h($certificate['venue']) ?><?php endif; ?>
        <br>Issued on <?= $h($issuedDate) ?>
      </div>
      <div class="cert-footer">
        <div class="signature">Training / Program Owner</div>
        <div class="signature">Schools Division Office</div>
      </div>
      <div class="cert-number">Certificate No. <?= $h($certificate['certificate_no']) ?></div>
    </section>
  </main>
</body>
</html>
