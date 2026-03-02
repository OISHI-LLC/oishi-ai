<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Lab OISHI - AIコンサルティングに関する知見やトレンドを発信しています。">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="icon" href="<?php echo get_template_directory_uri(); ?>/favicon.ico">
  <?php wp_head(); ?>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?php echo home_url('/'); ?>" class="site-logo"><img src="<?php echo get_template_directory_uri(); ?>/logo.png" alt="" class="site-logo-img">AI Lab OISHI</a>
    <button class="mobile-toggle" aria-label="Menu">&#9776;</button>
    <nav>
      <ul class="header-nav">
        <li><a href="<?php echo home_url('/#services'); ?>">Services</a></li>
        <li><a href="<?php echo home_url('/#strengths'); ?>">Why Us</a></li>
        <li><a href="<?php echo home_url('/#about'); ?>">Company</a></li>
        <li><a href="<?php echo home_url('/portfolio/'); ?>">Portfolio</a></li>
        <li><a href="<?php echo get_permalink(get_option('page_for_posts')); ?>" class="nav-active">Blog</a></li>
        <li><a href="<?php echo home_url('/#contact'); ?>">Contact</a></li>
      </ul>
    </nav>
  </div>
</header>

<div class="container">
  <div class="blog-header">
    <h1>Blog</h1>
    <p>AIに関する知見やトレンドを発信しています。</p>
  </div>
  <div class="blog-list">
    <?php if (have_posts()) : ?>
      <?php while (have_posts()) : the_post(); ?>
        <a href="<?php the_permalink(); ?>" class="blog-item">
          <div class="blog-date"><?php echo get_the_date('Y.m.d'); ?></div>
          <h2><?php the_title(); ?></h2>
          <p><?php echo wp_trim_words(get_the_excerpt(), 80, '...'); ?></p>
        </a>
      <?php endwhile; ?>
    <?php else : ?>
      <div class="blog-empty">
        <p>記事はまだありません。</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<footer class="site-footer">
  <div class="container">
    <p>&copy; <?php echo date('Y'); ?> AI Lab OISHI</p>
  </div>
</footer>

<?php wp_footer(); ?>

<script>
(function(){
  var toggle = document.querySelector('.mobile-toggle');
  var nav = document.querySelector('.header-nav');
  if(toggle && nav){
    toggle.addEventListener('click', function(){
      nav.classList.toggle('open');
    });
  }
  document.querySelectorAll('.header-nav a').forEach(function(a){
    a.addEventListener('click', function(){
      nav.classList.remove('open');
    });
  });
})();
</script>

</body>
</html>
