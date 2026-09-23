document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".contact-form");

  if (!form) {
    return;
  }

  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const messageInput = document.getElementById("message");
  const formTimeInput = document.getElementById("form-time");
  const messageBox = document.getElementById("form-message");

  // =========================================================
  // TIMESTAMP ANTYBOTOWY
  // =========================================================

  if (formTimeInput) {
    formTimeInput.value = Math.floor(Date.now() / 1000);
  }


  // =========================================================
  // FUNKCJA — CZYSZCZENIE BŁĘDU
  // =========================================================

  function clearValidity(input) {
    input.setCustomValidity("");
  }


  // =========================================================
  // WALIDACJA IMIENIA
  // =========================================================

  function validateName() {
    const name = nameInput.value.trim();

    const nameRegex =
      /^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u;

    if (name.length < 2) {
      nameInput.setCustomValidity(
        "Imię powinno mieć co najmniej 2 znaki."
      );

      return false;
    }

    if (name.length > 40) {
      nameInput.setCustomValidity(
        "Imię może mieć maksymalnie 40 znaków."
      );

      return false;
    }

    if (!nameRegex.test(name)) {
      nameInput.setCustomValidity(
        "Podaj poprawne imię. Użyj tylko liter, spacji, apostrofu lub myślnika."
      );

      return false;
    }

    clearValidity(nameInput);

    return true;
  }


  // =========================================================
  // WALIDACJA E-MAILA
  // =========================================================

  function validateEmail() {
    const email = emailInput.value.trim();

    // Maksymalna długość zgodna z ograniczeniem formularza.
    if (email.length < 5) {
      emailInput.setCustomValidity(
        "Podaj poprawny adres e-mail."
      );

      return false;
    }

    if (email.length > 254) {
      emailInput.setCustomValidity(
        "Adres e-mail jest zbyt długi."
      );

      return false;
    }

    // Niedozwolone spacje i znaki nowej linii.
    if (/[\s\r\n]/.test(email)) {
      emailInput.setCustomValidity(
        "Adres e-mail nie może zawierać spacji."
      );

      return false;
    }

    // Podstawowa struktura adresu.
    const emailRegex =
      /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    if (!emailRegex.test(email)) {
      emailInput.setCustomValidity(
        "Podaj poprawny adres e-mail, np. twoj@email.pl."
      );

      return false;
    }

    // Tylko jedna małpa.
    if ((email.match(/@/g) || []).length !== 1) {
      emailInput.setCustomValidity(
        "Podaj poprawny adres e-mail."
      );

      return false;
    }

    clearValidity(emailInput);

    return true;
  }


  // =========================================================
  // WALIDACJA WIADOMOŚCI
  // =========================================================

  function validateMessage() {
    const message = messageInput.value.trim();

    if (message.length < 10) {
      messageInput.setCustomValidity(
        "Wiadomość powinna mieć co najmniej 10 znaków."
      );

      return false;
    }

    if (message.length > 3000) {
      messageInput.setCustomValidity(
        "Wiadomość może mieć maksymalnie 3000 znaków."
      );

      return false;
    }

    clearValidity(messageInput);

    return true;
  }


  // =========================================================
  // WALIDACJA NA ŻYWO
  // =========================================================

  nameInput.addEventListener("input", () => {
    clearValidity(nameInput);
  });

  emailInput.addEventListener("input", () => {
    clearValidity(emailInput);
  });

  messageInput.addEventListener("input", () => {
    clearValidity(messageInput);
  });


  // =========================================================
  // SUBMIT
  // =========================================================

  form.addEventListener("submit", (event) => {
    event.preventDefault();

    // Czyścimy wcześniejsze komunikaty.
    clearValidity(nameInput);
    clearValidity(emailInput);
    clearValidity(messageInput);


    // Pobieramy i normalizujemy dane.
    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const message = messageInput.value.trim();


    // Wstawiamy oczyszczone wartości z powrotem do formularza.
    nameInput.value = name;
    emailInput.value = email;
    messageInput.value = message;


    // =======================================================
    // WALIDACJA
    // =======================================================

    if (!validateName()) {
      nameInput.reportValidity();
      nameInput.focus();
      return;
    }

    if (!validateEmail()) {
      emailInput.reportValidity();
      emailInput.focus();
      return;
    }

    if (!validateMessage()) {
      messageInput.reportValidity();
      messageInput.focus();
      return;
    }


    // =======================================================
    // HONEYPOT
    // =======================================================

    const honeypot = document.getElementById("website");

    if (honeypot && honeypot.value.trim() !== "") {
      // Nie wysyłamy niczego.
      // Zachowanie celowo wygląda jak normalne wysłanie.
      form.submit();
      return;
    }


    // =======================================================
    // OCHRONA PRZED ZBYT SZYBKIM WYSŁANIEM
    // =======================================================

    if (formTimeInput) {
      const formTime = parseInt(formTimeInput.value, 10);
      const currentTime = Math.floor(Date.now() / 1000);

      if (
        !Number.isNaN(formTime) &&
        currentTime - formTime < 4
      ) {
        messageBox.hidden = false;
        messageBox.textContent =
          "Odczekaj chwilę przed wysłaniem wiadomości.";
        messageBox.className =
          "form-message error";

        return;
      }
    }


    // =======================================================
    // AKTYWACJA PRZYCISKU
    // =======================================================

    const submitButton = form.querySelector(
      'button[type="submit"]'
    );

    if (submitButton) {
      submitButton.disabled = true;

      submitButton.setAttribute(
        "aria-disabled",
        "true"
      );

      submitButton.innerHTML =
        'Wysyłanie... <span aria-hidden="true">→</span>';
    }


    // =======================================================
    // WYSŁANIE FORMULARZA
    // =======================================================

    form.submit();
  });


  // =========================================================
  // KOMUNIKAT PO POWROCIE Z SEND.PHP
  // =========================================================

  const params =
    new URLSearchParams(window.location.search);

  const status = params.get("sent");
  const error = params.get("error");


  if (status === "1") {

    messageBox.hidden = false;

    messageBox.textContent =
      "✓ Wiadomość została wysłana. Dziękujemy za kontakt — odpowiemy możliwie szybko.";

    messageBox.className =
      "form-message success";
  }


  if (status === "0") {

    messageBox.hidden = false;

    let errorMessage =
      "Nie udało się wysłać wiadomości. Spróbuj ponownie za chwilę.";


    // =======================================================
    // KONKRETNE KOMUNIKATY BŁĘDÓW
    // =======================================================

    if (error === "name") {
      errorMessage =
        "Podaj poprawne imię — od 2 do 40 znaków, bez cyfr i znaków specjalnych.";
    }

    if (error === "email") {
      errorMessage =
        "Podaj poprawny adres e-mail.";
    }

    if (error === "message") {
      errorMessage =
        "Wiadomość powinna mieć od 10 do 3000 znaków.";
    }

    if (error === "limit") {
      errorMessage =
        "Osiągnięto limit wysyłania wiadomości. Spróbuj ponownie później.";
    }

    if (error === "spam") {
      errorMessage =
        "Wiadomość nie została wysłana. Spróbuj ponownie za chwilę.";
    }

    if (error === "mail") {
      errorMessage =
        "Nie udało się wysłać wiadomości. Spróbuj ponownie za chwilę.";
    }

    messageBox.hidden = false;

    messageBox.textContent = errorMessage;

    messageBox.className =
      "form-message error";
  }


  // =========================================================
  // USUNIĘCIE PARAMETRÓW Z ADRESU
  // =========================================================

  if (status !== null) {

    const cleanUrl =
      window.location.pathname;

    window.history.replaceState(
      {},
      document.title,
      cleanUrl
    );
  }
});