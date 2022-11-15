const buttonExpo = document.querySelectorAll(".menu-item .menu-btn");
const expoBody = document.querySelectorAll(".hall-body");

$(".content-block").each(function () {
  if ($(window).width() <= 768) {
    let more = $(this).find(".show-more");
    let hide = $(this).find(".hide-content");
    hide.hide();
    more.click(function () {
      hide.slideToggle();
      more.text(more.text() == "Скрыть" ? "Показать еще >" : "Скрыть");
    });
  }
});

for (let i = 0; i < buttonExpo.length; i += 6) {
  buttonExpo[i].addEventListener("click", function (e) {
    buttonExpo.forEach((e) => {
      e.classList.remove("now");
    });
    expoBody.forEach((e) => {
      e.classList.remove("_active");
    });
    buttonExpo[i].classList.add("now");
    expoBody[0].classList.add("_active");
  });
}
for (let i = 1; i < buttonExpo.length; i += 6) {
  buttonExpo[i].addEventListener("click", function (e) {
    buttonExpo.forEach((e) => {
      e.classList.remove("now");
    });
    expoBody.forEach((e) => {
      e.classList.remove("_active");
    });
    buttonExpo[i].classList.add("now");
    expoBody[1].classList.add("_active");
  });
}
for (let i = 2; i < buttonExpo.length; i += 6) {
  buttonExpo[i].addEventListener("click", function (e) {
    buttonExpo.forEach((e) => {
      e.classList.remove("now");
    });
    expoBody.forEach((e) => {
      e.classList.remove("_active");
    });
    buttonExpo[i].classList.add("now");
    expoBody[2].classList.add("_active");
  });
}
for (let i = 3; i < buttonExpo.length; i += 6) {
  buttonExpo[i].addEventListener("click", function (e) {
    buttonExpo.forEach((e) => {
      e.classList.remove("now");
    });
    expoBody.forEach((e) => {
      e.classList.remove("_active");
    });
    buttonExpo[i].classList.add("now");
    expoBody[3].classList.add("_active");
  });
}
for (let i = 4; i < buttonExpo.length; i += 6) {
  buttonExpo[i].addEventListener("click", function (e) {
    buttonExpo.forEach((e) => {
      e.classList.remove("now");
    });
    expoBody.forEach((e) => {
      e.classList.remove("_active");
    });
    buttonExpo[i].classList.add("now");
    expoBody[4].classList.add("_active");
  });
}
for (let i = 5; i < buttonExpo.length; i += 6) {
  buttonExpo[i].addEventListener("click", function (e) {
    buttonExpo.forEach((e) => {
      e.classList.remove("now");
    });
    expoBody.forEach((e) => {
      e.classList.remove("_active");
    });
    buttonExpo[i].classList.add("now");
    expoBody[5].classList.add("_active");
  });
}

