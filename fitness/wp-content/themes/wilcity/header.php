<?php
if (!headers_sent() && session_status() == PHP_SESSION_NONE) {
    session_start();
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta name="format-detection" content="telephone=no"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name="google-site-verification" content="v0yqkO-auQnGKdNPMt1YaRcnjluFhU0ZalnFb5ZQjRs" />
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
        <?php wp_head(); ?>
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9060725779717200"
     crossorigin="anonymous"></script>
     <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-78KM36MT0V"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-78KM36MT0V');
</script>
    </head>
<body <?php body_class(); ?> data-posttype="<?php echo esc_attr(apply_filters('wilcity/header/data-posttype', '')); ?>">
<?php
if (!isset($_GET['hide_body']) || $_GET['hide_body'] !== 'listing_details') {
    do_action('wilcity/after-open-body');
}
