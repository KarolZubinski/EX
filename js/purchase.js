document.querySelectorAll("[data-buy]").forEach((button) => {
  button.addEventListener("click", (event) => {
    event.preventDefault();
    alert("Płatności podłączymy w kolejnym etapie.");
  });
});