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
