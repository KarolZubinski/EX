<?php

// =========================================
// USTAWIENIA
// =========================================

$to = "kontakt@powrotdoex.pl";
$from = "kontakt@powrotdoex.pl";


// =========================================
// SPRAWDZENIE METODY
// =========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kontakt.html");
    exit;
}


// =========================================
// POBRANIE DANYCH Z FORMULARZA
// =========================================

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");


// =========================================
// WALIDACJA
// =========================================

if ($name === "" || $email === "" || $message === "") {
    header("Location: kontakt.html?sent=0");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: kontakt.html?sent=0");
    exit;
}


// =========================================
// ZABEZPIECZENIE DANYCH
// =========================================

$name = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$message = htmlspecialchars($message, ENT_QUOTES, "UTF-8");


// =========================================
// TEMAT WIADOMOŚCI
// =========================================

$subject = "Nowa wiadomość z formularza - Powrót do ex";


// =========================================
// TREŚĆ WIADOMOŚCI
// =========================================

$body =
    "Nowa wiadomość z formularza kontaktowego.\n\n" .
    "Imię: " . $name . "\n" .
    "Adres e-mail: " . $email . "\n\n" .
    "Wiadomość:\n" .
    $message;


// =========================================
// NAGŁÓWKI
// =========================================

$headers =
    "From: " . $from . "\r\n" .
    "Reply-To: " . $email . "\r\n" .
    "MIME-Version: 1.0\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n";


// =========================================
// WYSŁANIE WIADOMOŚCI
// =========================================

$sent = mail(
    $to,
    $subject,
    $body,
    $headers,
    "-f" . $from
);


// =========================================
// REZULTAT
// =========================================

if ($sent) {
    header("Location: kontakt.html?sent=1");
    exit;
}

header("Location: kontakt.html?sent=0");
exit;

?>