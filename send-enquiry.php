<?php
/**
 * Green Square — Enquiry Form Handler (simple version, no SMTP login required)
 * Uses PHP's built-in mail() function via your web host's own mail server.
 *
 * ── TESTING MODE ──
 * RECIPIENT_EMAIL below is currently arif@Gamutpro.my so you can confirm
 * delivery before this touches the real support inbox. Once you've
 * checked it arrives (including the spam folder), change RECIPIENT_EMAIL
 * to support@greensquare.com.sg — that's the only line that needs to change.
 *
 * NOTE: because this doesn't log in to a real mailbox, some email
 * providers may flag or spam-filter it since it can't prove it's really
 * sent by greensquare.com.sg. If that happens, we can switch this to
 * authenticated SMTP (PHPMailer) instead — just say so.
 */

header('Content-Type: application/json');

// ============ CONFIG ============
const RECIPIENT_EMAIL = 'abdarifsaser@gmail.com'; // TODO: change to support@greensquare.com.sg once confirmed working
const SITE_NAME       = 'Green Square Singapore';
const FROM_ADDRESS    = 'noreply@greensquare.com.sg'; // should be a real/allowed address on your sending domain
const SEND_AUTOREPLY  = true;
// =================================

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Invalid request method.');
}

// ---- Honeypot check: real visitors never fill this in ----
if (!empty($_POST['website'])) {
    // Silently "succeed" so bots don't learn the honeypot exists.
    respond(true, 'Thank you.');
}

// ---- Helpers ----
function clean_line($v) {
    $v = trim($v ?? '');
    // Strip CR/LF so nothing here can be used for email header injection.
    return str_replace(["\r", "\n"], '', $v);
}

// ---- Gather + sanitize inputs ----
$name        = clean_line($_POST['name'] ?? '');
$email       = clean_line($_POST['email'] ?? '');
$phone       = clean_line($_POST['phone'] ?? '');
$enquiryType = clean_line($_POST['enquiry_type'] ?? '');
$message     = trim($_POST['message'] ?? ''); // newlines are fine here, it's the body, not a header

$allowedTypes = [
    'General Enquiry',
    'Drop-off Question',
    'Partnership / Collaboration',
    'Media / Press',
    'Donation Pickup',
    'Other',
];

// ---- Validate ----
$errors = [];
if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'name';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}
if ($enquiryType === '' || !in_array($enquiryType, $allowedTypes, true)) {
    $errors[] = 'enquiry_type';
}
if ($message === '' || mb_strlen($message) > 5000) {
    $errors[] = 'message';
}

if (!empty($errors)) {
    respond(false, 'Please fill in all required fields correctly.');
}

// ---- Build the notification email (to your team / test inbox) ----
$subject = "[Enquiry] {$enquiryType} — {$name}";

$body  = "New enquiry received via the " . SITE_NAME . " website contact form.\n\n";
$body .= "Name:            {$name}\n";
$body .= "Email:           {$email}\n";
$body .= "Phone:           " . ($phone !== '' ? $phone : '—') . "\n";
$body .= "Enquiry Type:    {$enquiryType}\n";
$body .= "Submitted:       " . date('d M Y, h:i A') . " (server time)\n";
$body .= "\nMessage:\n" . $message . "\n";

$replyToName = mb_encode_mimeheader($name, 'UTF-8');
$headers   = [];
$headers[] = "From: " . SITE_NAME . " Website <" . FROM_ADDRESS . ">";
$headers[] = "Reply-To: {$replyToName} <{$email}>";
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = mail(RECIPIENT_EMAIL, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    respond(false, 'Sorry, something went wrong sending your enquiry. Please email us directly at support@greensquare.com.sg.');
}

// ---- Auto-reply to the visitor ----
if (SEND_AUTOREPLY) {
    $autoSubject = "We've received your enquiry — " . SITE_NAME;

    $autoBody  = "Hi {$name},\n\n";
    $autoBody .= "We've received your enquiry and we'll get back to you shortly.\n\n";
    $autoBody .= "For your reference, here's what you sent us:\n";
    $autoBody .= "Enquiry Type: {$enquiryType}\n";
    $autoBody .= "Message: {$message}\n\n";
    $autoBody .= "— The Green Square Team\n";
    $autoBody .= "support@greensquare.com.sg | +65 8383 8677\n";

    $autoHeaders   = [];
    $autoHeaders[] = "From: " . SITE_NAME . " <" . FROM_ADDRESS . ">";
    $autoHeaders[] = 'Content-Type: text/plain; charset=UTF-8';

    // Best-effort — don't fail the whole request if only the auto-reply fails.
    @mail($email, $autoSubject, $autoBody, implode("\r\n", $autoHeaders));
}

respond(true, "Thank you — your enquiry has been sent. We'll be in touch soon.");
