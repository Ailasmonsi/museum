<?php
/*
Template Name: expo
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
      <li class="burger-nav-item active">
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
            <li class="nav-item ">
              <a href="/" class="nav-link" role="link">Главная</a>
            </li>
            <li class="nav-item">
              <a href="/news" class="nav-link" role="link">Новости</a>
            </li>
            <li class="nav-item">
              <a href="/photo" class="nav-link" role="link">Фотогалерея</a>
            </li>
            <li class="nav-item active">
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
    <section class="expo">
      <div class="wrapper">
        <div class="breadcrumb">
          <ul class="bc-list">
            <li class="bc-item">
              <a href="/" class="bc-link">Главная</a>
            </li>
            <li class="bc-item">
              <a href="/expo" class="bc-link">Экспозиция</a>
            </li>
          </ul>
        </div>
        <h2 class="title">Первый зал</h2>
        <nav>
          <ul class="menu-list">
            <li class="menu-item">
              <button class="menu-btn now">Первый зал</button>
            </li>
            <li class="menu-item">
              <button class="menu-btn">Второй зал</button>
            </li>
            <li class="menu-item">
              <button class="menu-btn">Третий зал</button>
            </li>
            <li class="menu-item">
              <button class="menu-btn">Четвертый зал</button>
            </li>
            <li class="menu-item">
              <button class="menu-btn">Пятый зал</button>
            </li>
            <li class="menu-item">
              <button class="menu-btn">Шестой зал</button>
            </li>
          </ul>
        </nav>
        <div class="swiper first-swiper">
          <?php
          $photos = get_field('galery');
          echo '<div class="swiper-wrapper">';
          foreach ($photos as $photo) {
          ?>
            <div class="swiper-slide">
              <img src=" <?= $photo ?> " alt="" class="img">
            </div>
          <?php
          }
          echo '</div>'
          ?>
        </div>
        <div class="swiper-navigation">
          <div class="swp-button swiper-button-prev">
            <svg width="80" height="24" viewBox="0 0 80 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0.939339 13.0607C0.353554 12.4749 0.353554 11.5251 0.939339 10.9393L10.4853 1.3934C11.0711 0.807617 12.0208 0.807617 12.6066 1.3934C13.1924 1.97919 13.1924 2.92894 12.6066 3.51472L4.12132 12L12.6066 20.4853C13.1924 21.0711 13.1924 22.0208 12.6066 22.6066C12.0208 23.1924 11.0711 23.1924 10.4853 22.6066L0.939339 13.0607ZM80 13.5L2 13.5L2 10.5L80 10.5L80 13.5Z" fill="#333333" />
            </svg>

          </div>
          <div class="swiper-pagination"></div>
          <div class="swp-button swiper-button-next">
            <svg width="80" height="24" viewBox="0 0 80 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M79.0607 13.0607C79.6464 12.4749 79.6464 11.5251 79.0607 10.9393L69.5147 1.3934C68.9289 0.807617 67.9792 0.807617 67.3934 1.3934C66.8076 1.97919 66.8076 2.92894 67.3934 3.51472L75.8787 12L67.3934 20.4853C66.8076 21.0711 66.8076 22.0208 67.3934 22.6066C67.9792 23.1924 68.9289 23.1924 69.5147 22.6066L79.0607 13.0607ZM-1.31134e-07 13.5L78 13.5L78 10.5L1.31134e-07 10.5L-1.31134e-07 13.5Z" fill="#333333" />
            </svg>

          </div>
        </div>
        <div class="text"> <?php the_field('expo-info') ?></div>
      </div>
    </section>
  </main>

  <?php get_footer(); ?>