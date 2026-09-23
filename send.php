<?php

// =========================================================
// KONFIGURACJA
// =========================================================

$to = "kontakt@powrotdoex.pl";
$from = "kontakt@powrotdoex.pl";

$maxAttempts = 5;
$timeWindow = 15 * 60; // 15 minut

// Minimalny czas od wejścia na formularz do wysłania.
// Chroni przed częścią prostych botów.
// 4 sekundy.
$minSubmitTime = 4;


// =========================================================
// FUNKCJA PRZEKIEROWANIA
// =========================================================

function redirectToContact($status = 0, $error = "")
{
    $url = "kontakt.html?sent=" . (int)$status;

    if ($error !== "") {
        $url .= "&error=" . urlencode($error);
    }

    header("Location: " . $url);
    exit;
}


// =========================================================
// TYLKO POST
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kontakt.html");
    exit;
}


// =========================================================
// HONEYPOT ANTYSPAM
// =========================================================

// To pole powinno pozostać puste.
// Normalny użytkownik go nie widzi.

$honeypot = trim($_POST["website"] ?? "");

if ($honeypot !== "") {

    // Nie informujemy bota, że został wykryty.
    // Udajemy poprawne wysłanie.

    redirectToContact(1);
}


// =========================================================
// POBRANIE DANYCH
// =========================================================

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");


// =========================================================
// PODSTAWOWA WALIDACJA
// =========================================================

if ($name === "" || $email === "" || $message === "") {
    redirectToContact(0, "validation");
}


// =========================================================
// WALIDACJA IMIENIA
// =========================================================

// 2–40 znaków.
// Dozwolone są:
// - litery
// - polskie znaki
// - spacje
// - apostrof
// - myślnik

$nameLength = mb_strlen($name, "UTF-8");

if ($nameLength < 2 || $nameLength > 40) {
    redirectToContact(0, "name");
}

if (
    !preg_match(
        "/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u",
        $name
    )
) {
    redirectToContact(0, "name");
}


// =========================================================
// WALIDACJA E-MAILA
// =========================================================

$emailLength = mb_strlen($email, "UTF-8");

if ($emailLength < 5 || $emailLength > 254) {
    redirectToContact(0, "email");
}

// Brak spacji i znaków nowej linii.
if (preg_match("/[\r\n\s]/", $email)) {
    redirectToContact(0, "email");
}

// Podstawowa walidacja struktury adresu.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectToContact(0, "email");
}


// =========================================================
// DODATKOWA WALIDACJA E-MAILA
// =========================================================

// Rozdzielamy adres na część lokalną i domenę.

$emailParts = explode("@", $email);

if (count($emailParts) !== 2) {
    redirectToContact(0, "email");
}

$localPart = $emailParts[0];
$domain = $emailParts[1];


// Część lokalna nie może być pusta.
if ($localPart === "" || mb_strlen($localPart, "UTF-8") > 64) {
    redirectToContact(0, "email");
}


// Domena musi zawierać kropkę.
if (strpos($domain, ".") === false) {
    redirectToContact(0, "email");
}


// Domena nie może zaczynać ani kończyć się kropką/myślnikiem.
if (
    $domain[0] === "." ||
    $domain[0] === "-" ||
    substr($domain, -1) === "." ||
    substr($domain, -1) === "-"
) {
    redirectToContact(0, "email");
}


// =========================================================
// WALIDACJA WIADOMOŚCI
// =========================================================

$messageLength = mb_strlen($message, "UTF-8");

if ($messageLength < 10 || $messageLength > 3000) {
    redirectToContact(0, "message");
}


// =========================================================
// OCHRONA PRZED NAGŁÓWKAMI
// =========================================================

// Imię i e-mail nie mogą zawierać CR/LF.
// Zapobiega to header injection.

if (preg_match("/[\r\n]/", $name)) {
    redirectToContact(0, "validation");
}

if (preg_match("/[\r\n]/", $email)) {
    redirectToContact(0, "validation");
}


// =========================================================
// OCHRONA PRZED BARDZO SZYBKIMI BOTAMI
// =========================================================

// Formularz może przesłać timestamp,
// jeśli kontakt.html go posiada.

// Jeżeli timestamp istnieje, sprawdzamy,
// ile czasu minęło od jego wygenerowania.

if (isset($_POST["form_time"])) {

    $formTime = (int)$_POST["form_time"];
    $now = time();

    if ($formTime > 0) {

        $elapsed = $now - $formTime;

        // Formularz wysłany praktycznie natychmiast.
        if ($elapsed < $minSubmitTime) {
            redirectToContact(0, "spam");
        }

        // Timestamp nie może pochodzić z przyszłości.
        if ($formTime > $now + 10) {
            redirectToContact(0, "spam");
        }
    }
}


// =========================================================
// RATE LIMIT — LIMIT WIADOMOŚCI Z IP
// =========================================================

$ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";

// Nie zapisujemy IP bezpośrednio.
// Tworzymy SHA-256.

$ipHash = hash("sha256", $ip);

$rateLimitDir = sys_get_temp_dir() . "/powrotdoex_contact";


// Tworzymy katalog, jeżeli nie istnieje.

if (!is_dir($rateLimitDir)) {

    if (!mkdir($rateLimitDir, 0700, true)) {
        // Jeżeli nie można utworzyć katalogu,
        // nie przerywamy wysyłania wiadomości.
        // Formularz nadal może działać.
        $rateLimitFile = null;
    } else {
        $rateLimitFile = $rateLimitDir . "/" . $ipHash . ".json";
    }

} else {

    $rateLimitFile = $rateLimitDir . "/" . $ipHash . ".json";
}


$now = time();
$attempts = [];


// =========================================================
// ODCZYT POPRZEDNICH PRÓB
// =========================================================

if ($rateLimitFile !== null && file_exists($rateLimitFile)) {

    $storedData = file_get_contents($rateLimitFile);

    if ($storedData !== false) {

        $decodedData = json_decode($storedData, true);

        if (is_array($decodedData)) {
            $attempts = $decodedData;
        }
    }
}


// =========================================================
// USUNIĘCIE STARYCH PRÓB
// =========================================================

$attempts = array_filter(
    $attempts,
    function ($timestamp) use ($now, $timeWindow) {

        return is_numeric($timestamp)
            && ($now - (int)$timestamp) < $timeWindow;
    }
);

$attempts = array_values($attempts);


// =========================================================
// SPRAWDZENIE LIMITU
// =========================================================

if (count($attempts) >= $maxAttempts) {
    redirectToContact(0, "limit");
}


// =========================================================
// TEMAT
// =========================================================

$subject = "Nowa wiadomość z formularza - Powrót do ex";


// =========================================================
// TREŚĆ WIADOMOŚCI
// =========================================================

$body =
    "Nowa wiadomość z formularza kontaktowego.\n\n" .
    "Imię: " . $name . "\n" .
    "Adres e-mail: " . $email . "\n\n" .
    "Wiadomość:\n" .
    $message;


// =========================================================
// NAGŁÓWKI
// =========================================================

$headers =
    "From: " . $from . "\r\n" .
    "Reply-To: " . $email . "\r\n" .
    "MIME-Version: 1.0\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n";


// =========================================================
// WYSŁANIE WIADOMOŚCI
// =========================================================

$sent = mail(
    $to,
    $subject,
    $body,
    $headers,
    "-f" . $from
);


// =========================================================
// REZULTAT
// =========================================================

if ($sent) {

    // Dopiero po udanym wysłaniu
    // zapisujemy próbę do rate limitu.

    $attempts[] = $now;

    if ($rateLimitFile !== null) {

        file_put_contents(
            $rateLimitFile,
            json_encode(
                array_values($attempts),
                JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );
    }

    redirectToContact(1);
}


// =========================================================
// BŁĄD WYSYŁANIA
// =========================================================

redirectToContact(0, "mail");

?>