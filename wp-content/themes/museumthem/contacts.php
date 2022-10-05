<?php
/*
Template Name: contacts
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
      <li class="burger-nav-item">
        <a href="/prices" class="burger-nav-link">Экскурсии и цены</a>
      </li>
      <li class="burger-nav-item active">
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
            <li class="nav-item">
              <a href="/prices" class="nav-link" role="link">Экскурсии и цены</a>
            </li>
            <li class="nav-item active">
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
    <section class="contacts">
      <div class="wrapper">
        <div class="breadcrumb">
          <ul class="bc-list">
            <li class="bc-item">
              <a href="/" class="bc-link">Главная</a>
            </li>
            <li class="bc-item">
              <a href="/expo" class="bc-link">Контакты</a>
            </li>
          </ul>
        </div>
        <h2 class="title">Контакты</h2>
        <div class="contact">
          <?php
          global $post;

          $myposts = get_posts([
            'numberposts' => 1,
            'category_name' => 'con-phone'
          ]);

          if ($myposts) {
            foreach ($myposts as $post) {
              setup_postdata($post);
          ?>
              <div class="telnumber">
                <span class="icon tel-icon"></span>
                <a href="tel:74812380917"> <?php the_content() ?></a>
                </span>
                <br>
              </div>
          <?php }
          }
          wp_reset_postdata(); ?>


          <?php
          global $post;

          $myposts = get_posts([
            'numberposts' => 1,
            'category_name' => 'con-mail'
          ]);

          if ($myposts) {
            foreach ($myposts as $post) {
              setup_postdata($post);
          ?>
              <div class="mail rf">
                <span class="icon mail-icon"></span>
                <a href="mailto:<?php the_content() ?>"><?php the_content() ?></a>
              </div>
          <?php }
          }
          wp_reset_postdata(); ?>

        </div>
        <h2 class="title title--litle">Схема проезда</h2>
        <div class="content">
          <iframe src="https://yandex.ru/map-widget/v1/?um=constructor%3Aab7957cb5ffe8c99dead693709bde212dd0d0359ea38ec3c618aaee7bf3df14d&amp;source=constructor" frameborder="0" class="map"></iframe>
          <div class="info">
            <h3 class="subtitle">Адрес и часы работы</h3>
            <?php
            global $post;

            $myposts = get_posts([
              'numberposts' => 1,
              'category_name' => 'con-address'
            ]);

            if ($myposts) {
              foreach ($myposts as $post) {
                setup_postdata($post);
            ?>
                <address>
                  <span class="icon map-icon"></span>
                  <?php the_content() ?>
                </address>
            <?php }
            }
            wp_reset_postdata(); ?>

            <br>

            <?php
            global $post;

            $myposts = get_posts([
              'numberposts' => 1,
              'category_name' => 'con-timetable'
            ]);

            if ($myposts) {
              foreach ($myposts as $post) {
                setup_postdata($post);
            ?>
                <div class="timedate">
                  <span class="icon time-icon"></span>
                  <?php the_content() ?>
                </div>
            <?php }
            }
            wp_reset_postdata(); ?>
            <br>

            <a href="/prices" class="buy-btn">
              Купить билет
            </a>
          </div>
        </div>
    </section>
  </main>

  <?php get_footer(); ?>