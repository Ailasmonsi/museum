<?php
/*
Template Name: prices
*/
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<? bloginfo('charset') ?>">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Молодежный центр-музей имени адмирала Нахимова</title>

  <?php wp_head(); ?>

</head>

<body>
  <div class="burger-menu" style="display:none">
    <ul class="burger-nav-list">
      <li class="burger-nav-item">
        <a href="/" class="burger-nav-link">Главная</a>
      </li>
      <li class="burger-nav-item">
        <a href="/news" class="burger-nav-link">Новости</a>
      </li>
      <li class="burger-nav-item">
        <a href="/photo" class="burger-nav-link">Фотогалерея</a>
      </li>
      <li class="burger-nav-item">
        <a href="/expo" class="burger-nav-link">Экспозиция</a>
      </li>
      <li class="burger-nav-item">
        <a href="/history" class="burger-nav-link">Исторический блок</a>
      </li>
      <li class="burger-nav-item active">
        <a href="/prices" class="burger-nav-link">Экскурсии и цены</a>
      </li>
      <li class="burger-nav-item">
        <a href="/contacts" class="burger-nav-link">Контакты</a>
      </li>
		<li class="burger-nav-item">
        <a href="/review" class="burger-nav-link">Отзывы</a>
      </li>
      <li class="burger-nav-item">
        <a href="/r-center" class="burger-nav-link">Ресурсный центр по поддержке добровольчества</a>
      </li>
    </ul>
    <btn class="toggle-find-btn">
      <span class="icon search-icon"></span>
    </btn>
  </div>
  <div class="find-container hidden">
    <div class="wrapper">
      <button class="toggle-find-btn" role="button">
        <span class="icon close-icon"></span>
      </button>
      <label class="finder" role="searchbox">
        <input role="search" type="text" id="finder-input" placeholder="Поиск по сайту">
        <span class="icon search-icon" role="button"></span>
      </label>
      <div class="rezults dspn">
        <h3 class="heading">Результаты:</h3>
        <ol class="rezults-list">
          <li class="rezult-item">
            <a href="#" class="rezult-link" role="link">Статья №1</a>
          </li>
          <li class="rezult-item">
            <a href="#" class="rezult-link" role="link">Статья №2</a>
          </li>
          <li class="rezult-item">
            <a href="#" class="rezult-link" role="link">Статья №3</a>
          </li>
          <li class="rezult-item">
            <a href="#" class="rezult-link" role="link">Статья №4</a>
          </li>
        </ol>
      </div>
    </div>
  </div>
  <header class="header">
    <div class="top-header">
      <div class="wrapper">
        <a href="/" class="logo-link" role="banner">
          <img src="<?php bloginfo('template_url'); ?>/assets/content/img/logo.svg" alt="МОЛОДеЖНЫЙ ЦЕНТР-МУЗЕЙ ИМЕНИ АДМИРАЛА НАХИМОВА" class="logo">
        </a>
        <button class="burger menu" onclick="this.classList.toggle('opened');this.setAttribute('aria-expanded', this.classList.contains('opened'))" aria-label="Main Menu">
          <svg width="40" height="35" viewBox="0 0 100 100">
            <path class="line line1" d="M 20,29.000046 H 80.000231 C 80.000231,29.000046 94.498839,28.817352 94.532987,66.711331 94.543142,77.980673 90.966081,81.670246 85.259173,81.668997 79.552261,81.667751 75.000211,74.999942 75.000211,74.999942 L 25.000021,25.000058" />
            <path class="line line2" d="M 20,50 H 80" />
            <path class="line line3" d="M 20,70.999954 H 80.000231 C 80.000231,70.999954 94.498839,71.182648 94.532987,33.288669 94.543142,22.019327 90.966081,18.329754 85.259173,18.331003 79.552261,18.332249 75.000211,25.000058 75.000211,25.000058 L 25.000021,74.999942" />
          </svg>
        </button>
        <ul class="main-nav-list">
          <li class="nav-item">
            <a href="https://widget.afisha.yandex.ru/w/sessions/MjQwNjF8MjgyODg2fDMyNjc3MHwxNjcwMjA5MjAwMDAw?clientKey=e7ac5c3a-96aa-4bb3-85c4-f74ed8b0bb73&widgetName=w1" class="nav-link" role="link" target="_blank">
              <span class="icon ticket-icon">
              </span>
              <span class="icon pushkin-icon"></span>
            </a>
          </li>

          <li class="nav-item">
            <a href="/review" class="nav-link nav-link-join" role="link">
              <span class="icon join-icon"></span>
              Оставить отзыв
            </a>
          </li>
        </ul>
        <btn class="toggle-find-btn">
          <span class="icon search-icon"></span>
        </btn>
      </div>
    </div>
    <div class="bottom-header">
      <div class="wrapper">
        <ul class="second-nav-list">
          <li class="nav-item">
            <a href="https://widget.afisha.yandex.ru/w/sessions/MjQwNjF8MjgyODg2fDMyNjc3MHwxNjcwMjA5MjAwMDAw?clientKey=e7ac5c3a-96aa-4bb3-85c4-f74ed8b0bb73&widgetName=w1" class="nav-link" role="link">
              <span class="icon ticket-icon">
              </span>
              <span class="icon pushkin-icon"></span>
            </a>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link nav-link-join" role="link">
              <span class="icon join-icon"></span>
              Оставить отзыв
            </a>
          </li>
        </ul>
        <nav class="nav" role="navigation">
          <ul class="nav-list">
            <li class="nav-item">
              <a href="/" class="nav-link" role="link">Главная</a>
            </li>
            <li class="nav-item">
              <a href="/news" class="nav-link" role="link">Новости</a>
            </li>
            <li class="nav-item">
              <a href="/photo" class="nav-link" role="link">Фотогалерея</a>
            </li>
            <li class="nav-item">
              <a href="/expo" class="nav-link" role="link">Экспозиция</a>
            </li>
            <li class="nav-item">
              <a href="/history" class="nav-link" role="link">Исторический блок</a>
            </li>
            <li class="nav-item active">
              <a href="/prices" class="nav-link" role="link">Экскурсии и цены</a>
            </li>
            <li class="nav-item">
              <a href="/contacts" class="nav-link" role="link">Контакты</a>
            </li>
			  <li class="nav-item">
              <a href="/review" class="nav-link" role="link">Отзывы</a>
            </li>
            <li class="nav-item nav-item-mo">
              <a href="/r-center" class="nav-link ">Ресурсный центр по поддержке
                добровольчества</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </header>

  <main class="main">
    <section class="prices">
      <div class="wrapper">
        <div class="breadcrumb">
          <ul class="bc-list">
            <li class="bc-item">
              <a href="/" class="bc-link">Главная</a>
            </li>
            <li class="bc-item">
              <a href="/prices" class="bc-link">Экскурсии и цены</a>
            </li>
          </ul>
        </div>
        <h2 class="title">Экскурсии и цены</h2>
        <ol class="events-list content-block">
          <li class="event">
            Смоляне в истории Российского флота и морской авиации (обзорная)
          </li>
          <li class="event">
            Они сражались за Родину (тематическая: о смолянах-моряках и летчиках морской авиации в годы
            Великой Отечественной войны).
          </li>
          <li class="event">
            Крылья над морем (тематическая: о смолянах-летчиках морской авиации).
          </li>
          <li class="event">
            Животный мир океана (тематическая: о морских обитателях, для 1 - 6 кл.).
          </li>
          <li class="event">
            Адмирал П.С. Нахимов (тематическая).
          </li>
          <li class="event">
            Смоленск на пути "из варяг в греки" (тематическая, для 1 - 6 кл.).
          </li>
          <li class="event">
            Во славу русского оружия (тематическая: о смолянах – Героях Отечества).
          </li>
          <li class="event hide-content">
            Святые покровители русских моряков (тематическая).
          </li>
          <li class="event hide-content">
            Морской язык. Флотские традиции и легенды (тематическая).
          </li>
          <li class="event hide-content">
            От кадета до адмирала. Морское образование в России (тематическая).
          </li>
          <li class="event hide-content">
            История молодежного движения Смоленщины (тематическая).
          </li>
          <button class="see-more-btn show-more">Показать еще <span>></span></button>
        </ol>
        <table class="price-table">
          <tbody>
            <tr>
              <td class="person-cat">Категория посетителей</td>
              <td class="table-cat">Входная плата</td>
              <td class="table-cat">Экскурсионное обслуживание</td>
              <td class="table-cat">Стоимость билета с экскурсионным обслуживанием</td>
            </tr>
            <tr class="cat">
              <td class="cat-value">Дети до 7</td>
              <td class="value">30 ₽</td>
              <td class="value">30 ₽</td>
              <td class="value">60 ₽</td>
            </tr>
            <tr class="cat">
              <td class="cat-value">Дети до 16</td>
              <td class="value">50 ₽</td>
              <td class="value">50 ₽</td>
              <td class="value">100 ₽</td>
            </tr>
            <tr class="cat">
              <td class="cat-value">Студенты средних и высших учебных заведений;<br>
                пенсионеры
              </td>
              <td class="value">50 ₽</td>
              <td class="value">70 ₽</td>
              <td class="value">120 ₽</td>
            </tr>
            <tr class="cat">
              <td class="cat-value">Взрослые</td>
              <td class="value">80 ₽</td>
              <td class="value">70 ₽</td>
              <td class="value">100 ₽</td>
            </tr>
          </tbody>
        </table>
        <table class="offer-table">
          <tbody>
            <tr>
              <td class="offer-title">Наименование услуги</td>
              <td class="table-cat">Стоимость</td>
            </tr>
            <tr>
              <td class="offer">Квест-экскурсия по музею</td>
              <td class="value">150 ₽</td>
            </tr>
            <tr>
              <td class="offer">Квест-экскурсия по музею</td>
              <td class="value">200 ₽</td>
            </tr>
          </tbody>
        </table>
        <p class="text">
          Экскурсии проводятся для групп 10-25 человек. <br>
          Предварительная запись по телефону: 38-09-17. <br>
          Стоимость экскурсии для группы менее 10 человек - 800 руб.
        </p>
        <h3 class="subheading">Право на бесплатное посещение музея имени адмирала Нахимова предоставляется</h3>
        <ul class="free-ticket-list">
          <li class="free-ticket">
            Героям Советского Союза, Героям России и полным кавалерам ордена Славы;
          </li>
          <li class="free-ticket">Участникам Великой Отечественной войны, узникам концлагерей;
          </li>
          <li class="free-ticket">Ветеранам войны и боевых действий;
          </li>
          <li class="free-ticket">Инвалидам I, II группы и одному сопровождающему лицу;
          </li>
          <li class="free-ticket">Детям-инвалидам и одному сопровождающему лицу;
          </li>
          <li class="free-ticket">Детям-сиротам и детям, оставшимся без попечения родителей;
          </li>
          <li class="free-ticket">Участникам ликвидации аварии на ЧАЭС;
          </li>
          <li class="free-ticket">Военнослужащим, проходящим службу по призыву;
          </li>
          <li class="free-ticket">Cотрудникам музеев РФ;
          </li>
          <li class="free-ticket">Лицам, не достигшим восемнадцати лет (в третий четверг каждого месяца);
          </li>
          <li class="free-ticket">Членам многодетных семей; лицам, обучающимся по основным профессиональным
            образовательным программам; пенсионерам (в последнюю субботу каждого месяца).
          </li>
        </ul>
      </div>
    </section>
  </main>

  <?php get_footer(); ?>