const showMoreButton = document.querySelectorAll(".load-btn");
const galeryItems = document.querySelectorAll(".photo-item.magnig-item");

let galeryCount = 9; // количество отображаемых новостей в фотогалерее при загрузке страницы

if (showMoreButton && galeryItems.length > galeryCount) {
  showMoreButton.forEach((element) => {
    element.addEventListener("click", function (e) {
      galeryCount += 3;
      if (galeryCount >= galeryItems.length) {
        element.style.display = "none";
      }
      galeryCounter();
    });
  });
} else {
  showMoreButton.forEach((element) => {
    element.style.display = "none";
  });
}

function galeryCounter() {
  for (let i = 0; i < galeryItems.length; i++) {
    if (i >= galeryCount) galeryItems[i].style.display = "none";
    else galeryItems[i].style.display = "flex";
  }
}
galeryCounter();

window.addEventListener("DOMContentLoaded", () => {
  $(".burger").click((e) => {
    $("body").toggleClass("overflow");
    $("body").toggleClass("fixed");
    $(".burger-menu").toggleClass("open");
    $(".burger-menu").slideToggle();
  });

  const findWindow = document.querySelector(".find-container");
  const firstPageTitle = document.title;

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

function goUp() {
  var top = Math.max(
    document.body.scrollTop,
    document.documentElement.scrollTop
  );
  if (top > 0) {
    window.scrollBy(0, -100000);
    timeOut = setTimeout("goUp()", 10);
  } else clearTimeout(timeOut);
}

window.addEventListener(
  "popstate",
  function (event) {
    location.reload();
    document.title = firstPageTitle;
    goUp();
  },
  false
);

window.onload = function () {
  const newsButton = document.querySelectorAll("[data-post]");
  if (newsButton) {
    newsButton.forEach((element) => {
      element.addEventListener("click", function (e) {
        var newsNumber = element.getAttribute("data-value");

        const newsBody = document.querySelectorAll("[data-main-info]");
        const newsBodyWrapper = document.querySelectorAll("[data-main-number]");

        document.querySelector("[data-main]").style.display = "none";
        newsBodyWrapper[newsNumber].style.display = "block";
        goUp();
        var ppageTitle =
          newsBodyWrapper[newsNumber].querySelector(".title").innerHTML;
        document.title = ppageTitle;
        history.pushState(
          {
            page_title: ppageTitle,
          },
          newsBodyWrapper[newsNumber],
          ""
        );
      });
    });
  }
};

$(".magnig-item").each(function () {
  $(this).magnificPopup({
    delegate: "a",
    type: "image",
    gallery: {
      enabled: true,
    },
    removalDelay: 300,
    mainClass: "mfp-fade",
  });
});
