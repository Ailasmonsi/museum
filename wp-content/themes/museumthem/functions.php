<?php

add_filter( 'show_admin_bar', '__return_false' );

add_action('wp_enqueue_scripts', function () {

  wp_enqueue_style('magnific-css', get_template_directory_uri() . "/assets/styles/components/magnific-popup.css");
  wp_enqueue_style('swiper', "https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css");
  wp_enqueue_style('index', get_template_directory_uri() . "/assets/styles/index.css");

  wp_deregister_script('jquery');
  wp_register_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js');
  wp_enqueue_script('jquery');
  wp_enqueue_script('see-more', get_template_directory_uri() . '/assets/scripts/seeMore.js', array('jquery'), 'null', true);
  wp_enqueue_script('swiper', get_template_directory_uri() . "/assets/scripts/swiper-bundle.min.js");
  wp_enqueue_script('swiper-js', get_template_directory_uri() . '/assets/scripts/swiper.js', array('swiper'), 'null', true);
  wp_enqueue_script('magnific-js', get_template_directory_uri() . '/assets/scripts/jquery.magnific-popup.min.js', array('jquery'), 'null', true);
  wp_enqueue_script('open-find', get_template_directory_uri() . '/assets/scripts/scripts.js', array('jquery'), 'null', true);
});


add_theme_support('post-thumbnails');
add_theme_support('title-tag');
add_theme_support('custom-logo');