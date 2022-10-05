<?php
/*
Template Name: photo
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
      <li class="burger-nav-item active">
        <a href="/photo" class="burger-nav-link">Фотогалерея</a>
      </li>
      <li class="burger-nav-item">
        <a href="/expo" class="burger-nav-link">Экспозиция</a>
      </li>
      <li class="burger-nav-item">
        <a href="/history" class="burger-nav-link">Исторический блок</a>
      </li>
      <li class="burger-nav-item">
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
            <li class="nav-item active">
              <a href="/photo" class="nav-link" role="link">Фотогалерея</a>
            </li>
            <li class="nav-item">
              <a href="/expo" class="nav-link" role="link">Экспозиция</a>
            </li>
            <li class="nav-item">
              <a href="/history" class="nav-link" role="link">Исторический блок</a>
            </li>
            <li class="nav-item">
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

  <main>
    <section class="gallery">
      <div class="wrapper">
        <div class="breadcrumb">
          <ul class="bc-list">
            <li class="bc-item">
              <a href="/" class="bc-link">Главная</a>
            </li>
            <li class="bc-item">
              <a href="/photo" class="bc-link">Фотогалерея</a>
            </li>
          </ul>
        </div>
        <h2 class="title">Фотогалерея</h2>
        <ul class="photo-list">
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo1.jpg" alt="Наши мероприятия" class="photo">
            </a>
            <a href="#" class="cat-link" role="link">
              <h4 class="cat-name">Наши мероприятия</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo2.jpg" alt="Конкурс Космонавты XXI века" class="photo">
            </a>
            <a href="#" class="cat-link" role="link">
              <h4 class="cat-name">Конкурс Космонавты XXI века</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo3.jpg" alt="Всероссийский Нахимовский праздник. Хмелита" class="photo">
            </a>
            <a class="see-all">
              Посмотреть все фото
              <span class="icon next-icon">
                <svg width="46" height="20" viewBox="0 0 46 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="10" cy="10" r="9.5" stroke="" />
                  <path d="M45.3536 10.3536C45.5488 10.1583 45.5488 9.84171 45.3536 9.64645L42.1716 6.46447C41.9763 6.26921 41.6597 6.26921 41.4645 6.46447C41.2692 6.65973 41.2692 6.97631 41.4645 7.17158L44.2929 10L41.4645 12.8284C41.2692 13.0237 41.2692 13.3403 41.4645 13.5355C41.6597 13.7308 41.9763 13.7308 42.1716 13.5355L45.3536 10.3536ZM10 10.5L45 10.5L45 9.5L10 9.5L10 10.5Z" fill="#999999" />
                </svg>

              </span>

            </a>
            <a href="#" class="cat-link">
              <h4 class="cat-name">Всероссийский Нахимовский праздник. Хмелита</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo4.jpg" alt="День Военно-Морского Флота" class="photo">
            </a>
            <a href="#" class="cat-link">
              <h4 class="cat-name">День Военно-Морского Флота</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo5.jpg" alt="Морское братство - нерушимо! Смоленск - Витебск" class="photo">
            </a>
            <a class="see-all">
              Посмотреть все фото
              <span class="icon next-icon">
                <svg width="46" height="20" viewBox="0 0 46 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="10" cy="10" r="9.5" stroke="" />
                  <path d="M45.3536 10.3536C45.5488 10.1583 45.5488 9.84171 45.3536 9.64645L42.1716 6.46447C41.9763 6.26921 41.6597 6.26921 41.4645 6.46447C41.2692 6.65973 41.2692 6.97631 41.4645 7.17158L44.2929 10L41.4645 12.8284C41.2692 13.0237 41.2692 13.3403 41.4645 13.5355C41.6597 13.7308 41.9763 13.7308 42.1716 13.5355L45.3536 10.3536ZM10 10.5L45 10.5L45 9.5L10 9.5L10 10.5Z" fill="#999999" />
                </svg>

              </span>
            </a>
            <a href="#" class="cat-link">
              <h4 class="cat-name">Морское братство - нерушимо! Смоленск - Витебск</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo6.jpg" alt="Видеоконференция 'Стань моряком!'" class="photo">
            </a>
            <a href="#" class="cat-link">
              <h4 class="cat-name">Видеоконференция "Стань моряком!"</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo7.jpg" alt="Смоленские купола" class="photo">
            </a>
            <div class="see-all">
              Посмотреть все фото
              <span class="icon next-icon">
                <svg width="46" height="20" viewBox="0 0 46 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="10" cy="10" r="9.5" stroke="" />
                  <path d="M45.3536 10.3536C45.5488 10.1583 45.5488 9.84171 45.3536 9.64645L42.1716 6.46447C41.9763 6.26921 41.6597 6.26921 41.4645 6.46447C41.2692 6.65973 41.2692 6.97631 41.4645 7.17158L44.2929 10L41.4645 12.8284C41.2692 13.0237 41.2692 13.3403 41.4645 13.5355C41.6597 13.7308 41.9763 13.7308 42.1716 13.5355L45.3536 10.3536ZM10 10.5L45 10.5L45 9.5L10 9.5L10 10.5Z" fill="#999999" />
                </svg>

              </span>
            </div>
            <a href="#" class="cat-link">
              <h4 class="cat-name">Смоленские купола</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo8.jpg" alt="Отечественная война глазами детей" class="photo">
            </a>
            <a href="#" class="cat-link">
              <h4 class="cat-name">Отечественная война глазами детей</h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
          <li class="photo-item">
            <a href="#" class="img-link">
              <img src="<?php bloginfo('template_url'); ?>/assets/content/img/photo9.jpg" alt="215 лет со дня рождения А.И. Казарского. Дубровно" class="photo">
            </a>
            <a href="#" class="cat-link">
              <h4 class="cat-name">215 лет со дня рождения А.И. Казарского. Дубровно </h4>
            </a>
            <div class="date">
              <span class="icon calendar-icon"></span>
              28 июля 2022г.
            </div>
          </li>
        </ul>
        <button class="load-btn">Смотреть еще</button>
      </div>
    </section>
  </main>

  <?php get_footer(); ?>