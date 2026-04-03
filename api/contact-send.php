<?php
session_start();
mb_language("ja");
mb_internal_encoding("UTF-8");

// Sanitize helper
function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$complete = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../contact.html');
  exit;
}

// CSRF check
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  $error = '不正なリクエストです。お手数ですが、はじめからやり直してください。';
} else {
  // Invalidate token
  unset($_SESSION['csrf_token']);

  // Get form values
  $name    = trim($_POST['name'] ?? '');
  $company = trim($_POST['company'] ?? '');
  $email   = trim($_POST['email'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $message = trim($_POST['message'] ?? '');

  // Server-side validation
  if ($name === '' || $email === '' || $message === '') {
    $error = '必須項目が入力されていません。お手数ですが、はじめからやり直してください。';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'メールアドレスが正しくありません。お手数ですが、はじめからやり直してください。';
  } else {

    // Mail header injection prevention
    $email = str_replace(["\r", "\n"], '', $email);

    $admin_to   = "info.hopesconsul@gmail.com";
    $from_name  = mb_encode_mimeheader("ホープスコンサルティング");
    $from_email = "info@hopesconsul.com";
    $headers    = "From: {$from_name} <{$from_email}>";
    $envelope   = "-f{$from_email}";
    $datetime   = date("Y/m/d H:i:s");

    // ---------- Admin mail ----------
    $admin_subject = "【お問い合わせ】{$name}様から新しいお問い合わせ";
    $admin_body = <<<EOM
ホープスコンサルティングのホームページからお問い合わせがありました。

お問い合わせ日時：{$datetime}

--------------------------
お名前：{$name}
会社名：{$company}
メールアドレス：{$email}
電話番号：{$phone}
お問い合わせ内容：
{$message}
--------------------------
EOM;

    $admin_sent = mb_send_mail($admin_to, $admin_subject, $admin_body, $headers, $envelope);

    // ---------- User confirmation mail ----------
    $user_subject = '【ホープスコンサルティング】お問い合わせありがとうございました';
    $user_body = <<<EOM
この度は、ホープスコンサルティングにお問い合わせいただきありがとうございます。
下記の内容で、お問い合わせを承りました。

--------------------------
お名前：{$name}
会社名：{$company}
メールアドレス：{$email}
電話番号：{$phone}
お問い合わせ内容：
{$message}
--------------------------

お問い合わせ内容につきましては、担当者より対応させていただきます。
今後とも、ホープスコンサルティングをよろしくお願いいたします。

━━━━━━━━━━━━━━━━━━━━
ホープスコンサルティング株式会社
E-mail: info.hopesconsul@gmail.com
https://www.hopesconsul.com/
━━━━━━━━━━━━━━━━━━━━
EOM;

    $user_sent = mb_send_mail($email, $user_subject, $user_body, $headers, $envelope);

    if ($admin_sent && $user_sent) {
      $complete = true;
    } else {
      $error = '送信に失敗しました。お手数ですが、はじめから再度やり直してください。';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $complete ? '送信完了' : '送信エラー'; ?> | ホープスコンサルティング</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../images/favicon.ico" sizes="any">
<link rel="icon" href="../images/favicon-48x48.png" type="image/png" sizes="48x48">
<link rel="icon" href="../images/favicon-192x192.png" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="../images/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Silkscreen:wght@400;700&family=DotGothic16&family=Noto+Sans+JP:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }

:root {
  --color-primary: #141413;
  --color-accent: #d97757;
  --color-accent-light: #e08a6d;
  --color-cta-hover: #c4674a;
  --color-text: #141413;
  --color-text-light: rgba(20, 20, 19, 0.55);
  --color-text-muted: rgba(20, 20, 19, 0.4);
  --color-bg: #e8e6dc;
  --color-bg-alt: #dfddd3;
  --color-bg-warm: #f5f4ed;
  --color-border: rgba(194, 192, 182, 0.4);
  --font-display: "Noto Sans JP", sans-serif;
  --font-body: "Noto Sans JP", sans-serif;
  --font-dot: "DotGothic16", "Noto Sans JP", sans-serif;
  --font-pixel: "Silkscreen", cursive;
  --px: 4px;
}

body {
  font-family: var(--font-body);
  font-size: 1rem;
  line-height: 1.8;
  color: var(--color-text);
  background: var(--color-bg);
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-image: radial-gradient(circle, rgba(20,20,19,0.12) 1px, transparent 1px);
  background-size: 20px 20px;
}

/* CRT vignette overlay */
body::after {
  content: '';
  position: fixed;
  inset: 0;
  background: radial-gradient(ellipse at center, transparent 50%, rgba(20,20,19,0.15) 100%);
  pointer-events: none;
  z-index: 100;
}

/* ===== Header ===== */
.site-header {
  padding: 0.6rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}
.site-header a { text-decoration: none; color: var(--color-text); }
.logo { font-family: var(--font-pixel); font-weight: 700; font-size: 1rem; letter-spacing: 0.02em; }
.back-link { font-family: var(--font-pixel); font-size: 0.8rem; color: var(--color-text-light); transition: color 0.2s; letter-spacing: 0.02em; padding: 0.4rem 0.75rem; }
.back-link:hover { color: var(--color-accent); }

/* ===== Main ===== */
.main {
  flex: 1;
  max-width: 640px;
  margin: 0 auto;
  padding: 0.5rem 1.5rem 2rem;
  width: 100%;
}

.page-title {
  font-family: var(--font-pixel);
  font-size: 1.3rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 0.25rem;
  letter-spacing: 0.05em;
}

.page-subtitle {
  color: var(--color-text-light);
  font-size: 0.85rem;
  text-align: center;
  margin-bottom: 1.5rem;
  font-family: var(--font-dot);
}

/* ===== Result Card ===== */
.result-card {
  background: var(--color-bg-warm);
  border: 3px solid var(--color-border);
  padding: 1.5rem 1.75rem 1.75rem;
  clip-path: polygon(
    0 8px, 4px 8px, 4px 4px, 8px 4px, 8px 0,
    calc(100% - 8px) 0, calc(100% - 8px) 4px, calc(100% - 4px) 4px,
    calc(100% - 4px) 8px, 100% 8px,
    100% calc(100% - 8px), calc(100% - 4px) calc(100% - 8px),
    calc(100% - 4px) calc(100% - 4px), calc(100% - 8px) calc(100% - 4px),
    calc(100% - 8px) 100%, 8px 100%, 8px calc(100% - 4px),
    4px calc(100% - 4px), 4px calc(100% - 8px), 0 calc(100% - 8px)
  );
  position: relative;
  transition: border-color 0.25s, box-shadow 0.25s;
}
.result-card:hover {
  border-color: var(--color-accent);
  box-shadow: 8px 8px 0 rgba(217,119,87,0.18);
}

/* Error variant */
.result-card.is-error {
  border-color: #e74c3c;
}
.result-card.is-error:hover {
  border-color: #e74c3c;
  box-shadow: 8px 8px 0 rgba(231,76,60,0.18);
}

/* Scanlines */
.result-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 2px,
    rgba(20,20,19,0.02) 2px,
    rgba(20,20,19,0.02) 4px
  );
  pointer-events: none;
  z-index: 1;
}
.result-card > * {
  position: relative;
  z-index: 2;
}

/* Card content */
.result-message {
  font-family: var(--font-dot);
  font-size: 0.85rem;
  line-height: 1.9;
  text-align: center;
}

.result-message p {
  margin-bottom: 0.5rem;
}
.result-message p:last-child {
  margin-bottom: 0;
}

.error-text {
  font-family: var(--font-dot);
  font-size: 0.85rem;
  color: #e74c3c;
  text-align: center;
  line-height: 1.9;
}

/* ===== Button ===== */
.btn-group {
  display: flex;
  justify-content: center;
  margin-top: 1.5rem;
}

.btn {
  display: inline-block;
  padding: 0.85rem 2.5rem;
  font-family: var(--font-pixel);
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  border: none;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
  transition: background 0.2s, transform 0.15s, color 0.2s;
  clip-path: polygon(
    0 4px, 4px 4px, 4px 0, calc(100% - 4px) 0,
    calc(100% - 4px) 4px, 100% 4px, 100% calc(100% - 4px),
    calc(100% - 4px) calc(100% - 4px), calc(100% - 4px) 100%,
    4px 100%, 4px calc(100% - 4px), 0 calc(100% - 4px)
  );
  position: relative;
  overflow: hidden;
}

.btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0);
  pointer-events: none;
}

.btn:hover::after {
  animation: crtFlash 0.4s ease-out;
}

.btn:hover {
  animation: pixelHover 0.3s ease;
}

.btn-primary {
  background: var(--color-accent);
  color: #fff;
}
.btn-primary:hover {
  background: var(--color-cta-hover);
}

.btn-back {
  background: transparent;
  border: 2px solid var(--color-text);
  color: var(--color-text);
}
.btn-back:hover {
  background: var(--color-bg-alt);
}

/* ===== Animations ===== */
@keyframes crtFlash {
  0% { background: rgba(255,255,255,0.35); }
  30% { background: rgba(255,255,255,0.08); }
  60% { background: rgba(255,255,255,0.15); }
  100% { background: rgba(255,255,255,0); }
}

@keyframes pixelHover {
  0% { transform: translate(0, 0); }
  25% { transform: translate(-1px, 1px); }
  50% { transform: translate(1px, -1px); }
  75% { transform: translate(-1px, -1px); }
  100% { transform: translate(0, 0); }
}

/* ===== Footer ===== */
.site-footer {
  text-align: center;
  padding: 0.75rem;
  font-family: var(--font-pixel);
  font-size: 0.75rem;
  color: var(--color-text-muted);
  letter-spacing: 0.05em;
}
.site-footer a { color: var(--color-text-light); text-decoration: none; transition: color 0.2s; }
.site-footer a:hover { color: var(--color-accent); }

/* ===== Reduced Motion ===== */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* ===== Mobile ===== */
@media (max-width: 600px) {
  .site-header { padding: 0.6rem 1rem; }
  .main { padding: 0.5rem 1rem 1.5rem; }
  .result-card { padding: 1.2rem 1rem 1.5rem; }
  .page-title { font-size: 1.1rem; }
  .page-subtitle { font-size: 0.8rem; }
  .btn { font-size: 0.75rem; padding: 0.75rem 2rem; }
}
</style>
</head>
<body>

<header class="site-header">
  <a href="../" class="logo">Hopes Consulting</a>
  <a href="../" class="back-link">Back to Top</a>
</header>

<main class="main">

<?php if ($complete): ?>
  <h1 class="page-title">- COMPLETE! -</h1>
  <p class="page-subtitle">お問い合わせを受け付けました。</p>

  <div class="result-card">
    <div class="result-message">
      <p>お問い合わせいただきありがとうございました。</p>
      <p>確認後、数日以内に担当者からご連絡いたします。</p>
      <p>ご入力いただいたメールアドレスに確認メールをお送りしました。</p>
    </div>
  </div>

  <div class="btn-group">
    <a href="../index.html" class="btn btn-primary">&#9654; HOME</a>
  </div>

<?php else: ?>
  <h1 class="page-title">- ERROR -</h1>
  <p class="page-subtitle">送信処理中にエラーが発生しました。</p>

  <div class="result-card is-error">
    <p class="error-text"><?php echo h($error); ?></p>
  </div>

  <div class="btn-group">
    <a href="../contact.html" class="btn btn-back">&#9664; BACK</a>
  </div>

<?php endif; ?>

</main>

<footer class="site-footer">
  <a href="../">Hopes Consulting Inc.</a> &copy; 2026
</footer>

</body>
</html>
