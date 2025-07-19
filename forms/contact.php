<?php
header('Content-Type: application/json');
session_start();

// Cek metode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode([
    'status' => 'error',
    'message' => 'Permintaan tidak valid.'
  ]);
  exit;
}

// Konfigurasi email tujuan
$receiving_email_address = 'contact@example.com';

// Fungsi logging error
function log_error($message)
{
  file_put_contents(__DIR__ . '/error_log.txt', "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

// Ambil dan sanitasi input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validasi manual
$errors = [];

// Verifikasi CAPTCHA
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptcha_response)) {
  $errors['captcha'] = 'Verifikasi CAPTCHA wajib diisi.';
} else {
  $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
  $secret_key = '6LclmocrAAAAAMnvfxjWgRkNlsdgvzj-GlIxO1fc';

  $verify = file_get_contents($recaptcha_url . '?secret=' . $secret_key . '&response=' . $recaptcha_response);
  $recaptcha = json_decode($verify);

  if (!$recaptcha->success) {
    $errors['captcha'] = 'Verifikasi CAPTCHA gagal. Silakan coba lagi.';
  }
}

if ($name === '') {
  $errors['name'] = 'Nama wajib diisi.';
} elseif (!is_string($name)) {
  $errors['name'] = 'Nama harus berupa teks.';
} elseif (strlen($name) > 255) {
  $errors['name'] = 'Nama tidak boleh lebih dari 255 karakter.';
}

if ($email === '') {
  $errors['email'] = 'Email wajib diisi.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors['email'] = 'Silakan masukkan alamat email yang valid.';
}

if ($subject === '') {
  $errors['subject'] = 'Subjek wajib diisi.';
} elseif (!is_string($subject)) {
  $errors['subject'] = 'Subjek harus berupa teks.';
} elseif (strlen($subject) > 255) {
  $errors['subject'] = 'Subjek tidak boleh lebih dari 255 karakter.';
}

if ($message === '') {
  $errors['message'] = 'Pesan wajib diisi.';
} elseif (!is_string($message)) {
  $errors['message'] = 'Pesan harus berupa teks.';
}

if (!empty($errors)) {
  echo json_encode([
    'status' => 'validation_error',
    'message' => 'Mohon isi formulir dengan benar.',
    'errors' => $errors
  ]);
  exit;
}

// Simulasi kirim email
$email_body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
$email_headers = "From: $name <$email>\r\nReply-To: $email";

try {
  $sent = mail($receiving_email_address, $subject, $email_body, $email_headers);

  if ($sent) {
    echo json_encode([
      'status' => 'success',
      'message' => 'Pesan Anda berhasil dikirim. Terima kasih!'
    ]);
  } else {
    log_error("Gagal mengirim email dari $email dengan subjek $subject");
    echo json_encode([
      'status' => 'error',
      'message' => 'Gagal mengirim pesan Anda. Silakan coba lagi nanti.'
    ]);
  }
} catch (Exception $e) {
  log_error("Exception saat kirim email: " . $e->getMessage());
  echo json_encode([
    'status' => 'error',
    'message' => 'Terjadi kesalahan saat mengirim pesan.'
  ]);
}
