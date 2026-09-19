const menuToggle = document.querySelector(".menu-toggle");
const mobileNav = document.querySelector(".mobile-nav");

if (menuToggle && mobileNav) {
  menuToggle.addEventListener("click", () => {
    const isOpen = mobileNav.classList.toggle("is-open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
  });

  mobileNav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      mobileNav.classList.remove("is-open");
      menuToggle.setAttribute("aria-expanded", "false");
    });
  });
}

// Placeholder for the future payment integration.
// Replace this handler with the payment provider checkout URL/API.
document.querySelectorAll("[data-buy]").forEach((button) => {
  button.addEventListener("click", (event) => {
    event.preventDefault();
    alert("Płatności podłączymy w kolejnym etapie.");
  });
});
