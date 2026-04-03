<?php
session_start();

// POSTメソッドでなければリダイレクト
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../contact.html');
    exit;
}

// Honeypotチェック
if (!empty($_POST['website'])) {
    header('Location: ../contact.html');
    exit;
}

// 送信速度チェック（3秒以内ならbot判定）
if (isset($_POST['form_loaded_at'])) {
    $elapsed = time() - intval($_POST['form_loaded_at']);
    if ($elapsed < 3) {
        header('Location: ../contact.html');
        exit;
    }
}

// CSRFトークン生成
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 入力値サニタイズ
$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$company = htmlspecialchars(trim($_POST['company'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$phone   = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// サーバーサイドバリデーション
$errors = [];

if ($name === '') {
    $errors[] = 'お名前を入力してください。';
}

if ($email === '') {
    $errors[] = 'メールアドレスを入力してください。';
} elseif (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $errors[] = '正しいメールアドレスを入力してください。';
}

if ($message === '') {
    $errors[] = 'お問い合わせ内容を入力してください。';
}

$hasErrors = !empty($errors);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>入力内容の確認 | ホープスコンサルティング</title>
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

/* ===== Confirm Card ===== */
.confirm-card {
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
.confirm-card:hover {
  border-color: var(--color-accent);
  box-shadow: 8px 8px 0 rgba(217,119,87,0.18);
}

/* Scanlines */
.confirm-card::before {
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
.confirm-card > * {
  position: relative;
  z-index: 2;
}

/* Error card */
.confirm-card.has-errors {
  border-color: #e74c3c;
}
.confirm-card.has-errors:hover {
  border-color: #e74c3c;
  box-shadow: 8px 8px 0 rgba(231,76,60,0.18);
}

/* ===== Confirm Table ===== */
.confirm-table {
  width: 100%;
  border-collapse: collapse;
}
.confirm-table th {
  font-family: var(--font-pixel);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  text-align: left;
  padding: 0.75rem;
  background: var(--color-bg-alt);
  border-bottom: 2px solid var(--color-border);
  width: 160px;
  vertical-align: top;
}
.confirm-table td {
  font-family: var(--font-dot);
  font-size: 0.85rem;
  padding: 0.75rem;
  border-bottom: 1px solid var(--color-border);
  white-space: pre-wrap;
  word-break: break-word;
}

/* ===== Error List ===== */
.error-list {
  list-style: none;
  padding: 0;
}
.error-list li {
  font-family: var(--font-dot);
  color: #e74c3c;
  font-size: 0.85rem;
  padding: 0.3rem 0;
}
.error-list li::before {
  content: '> ';
  font-family: var(--font-pixel);
}

/* ===== Buttons ===== */
.btn-group {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn {
  flex: 1;
  padding: 0.85rem 1.5rem;
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

.btn-back {
  background: transparent;
  border: 2px solid var(--color-text);
  color: var(--color-text);
}
.btn-back:hover {
  background: var(--color-bg-alt);
}

.btn-submit {
  background: var(--color-accent);
  color: #fff;
}
.btn-submit:hover {
  background: var(--color-cta-hover);
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
  .confirm-card { padding: 1.2rem 1rem 1.5rem; }
  .page-title { font-size: 1.1rem; }
  .page-subtitle { font-size: 0.8rem; }
  .confirm-table th {
    width: 100px;
    font-size: 0.6rem;
    padding: 0.5rem;
  }
  .confirm-table td {
    font-size: 0.8rem;
    padding: 0.5rem;
  }
  .btn-group { gap: 0.75rem; }
  .btn { font-size: 0.75rem; padding: 0.75rem 1rem; }
}
</style>
</head>
<body>

<header class="site-header">
  <a href="../" class="logo">Hopes Consulting</a>
  <a href="../" class="back-link">Back to Top</a>
</header>

<main class="main">
  <h1 class="page-title">- CONFIRM -</h1>

<?php if ($hasErrors): ?>
  <p class="page-subtitle">入力内容にエラーがあります。修正してください。</p>

  <div class="confirm-card has-errors">
    <ul class="error-list">
      <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="btn-group">
    <button type="button" class="btn btn-back" onclick="history.back();">&#9664; BACK</button>
  </div>

<?php else: ?>
  <p class="page-subtitle">以下の内容でお間違いなければ「送信する」ボタンを押してください。</p>

  <div class="confirm-card">
    <table class="confirm-table">
      <tr>
        <th>Name</th>
        <td><?php echo $name; ?></td>
      </tr>
      <tr>
        <th>Company</th>
        <td><?php echo $company !== '' ? $company : '-'; ?></td>
      </tr>
      <tr>
        <th>Email</th>
        <td><?php echo $email; ?></td>
      </tr>
      <tr>
        <th>Phone</th>
        <td><?php echo $phone !== '' ? $phone : '-'; ?></td>
      </tr>
      <tr>
        <th>Message</th>
        <td><?php echo nl2br($message); ?></td>
      </tr>
    </table>
  </div>

  <form action="contact-send.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="name" value="<?php echo $name; ?>">
    <input type="hidden" name="company" value="<?php echo $company; ?>">
    <input type="hidden" name="email" value="<?php echo $email; ?>">
    <input type="hidden" name="phone" value="<?php echo $phone; ?>">
    <input type="hidden" name="message" value="<?php echo $message; ?>">

    <div class="btn-group">
      <button type="button" class="btn btn-back" onclick="history.back();">&#9664; BACK</button>
      <button type="submit" class="btn btn-submit">&#9654; SUBMIT</button>
    </div>
  </form>

<?php endif; ?>
</main>

<footer class="site-footer">
  <a href="../">Hopes Consulting Inc.</a> &copy; 2026
</footer>

</body>
</html>
