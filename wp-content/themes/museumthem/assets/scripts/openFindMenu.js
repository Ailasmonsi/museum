window.addEventListener("DOMContentLoaded", () => {
  $(".burger").click((e) => {
    $("body").toggleClass("overflow");
    $("body").toggleClass("fixed");
    $(".burger-menu").toggleClass("open");
    $(".burger-menu").slideToggle();
  });

  const findWindow = document.querySelector(".find-container");

  document.querySelectorAll(".toggle-find-btn").forEach((b) => {
    b.addEventListener("click", () => {
      if (document.querySelector(".burger-menu").classList.contains("open")) {
        $(".burger-menu").toggleClass("open");
        findWindow.classList.toggle("hidden");
        document.querySelector(".burger").classList.toggle("opened");
        $(".burger-menu").slideToggle();
        $("body").toggleClass("overflow");
      } else {
        findWindow.classList.toggle("hidden");
        $("body").toggleClass("fixed");
      }
    });
  });

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (!findWindow.classList.contains("hidden")) {
        findWindow.classList.add("hidden");
        $("body").toggleClass("fixed");
      }
    }
  });
});
