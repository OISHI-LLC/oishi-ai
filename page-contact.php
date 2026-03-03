<?php
/**
 * Template Name: Contact
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Lab OISHI へのお問い合わせ。AI導入のご相談は無料で承ります。">
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
        <li><a href="<?php echo home_url('/#services'); ?>"><span class="nav-ja">サービス</span><span class="nav-en">Services</span></a></li>
        <li><a href="<?php echo home_url('/#strengths'); ?>"><span class="nav-ja">選ばれる理由</span><span class="nav-en">Why Us</span></a></li>
        <li><a href="<?php echo home_url('/#about'); ?>"><span class="nav-ja">会社概要</span><span class="nav-en">Company</span></a></li>
        <li><a href="<?php echo home_url('/portfolio/'); ?>"><span class="nav-ja">実績</span><span class="nav-en">Portfolio</span></a></li>
        <li><a href="<?php echo home_url('/blog/'); ?>"><span class="nav-ja">ブログ</span><span class="nav-en">Blog</span></a></li>
        <li><a href="<?php echo home_url('/contact/'); ?>" class="nav-active"><span class="nav-ja">お問い合わせ</span><span class="nav-en">Contact</span></a></li>
      </ul>
    </nav>
  </div>
</header>

<section class="contact-hero">
  <div class="container text-center">
    <span class="section-label">Contact</span>
    <h1 class="section-title">お問い合わせ</h1>
    <p class="section-sub">「AIで何ができるか分からない」という段階からでもOK。<br>初回のご相談は無料で承ります。</p>
  </div>
</section>

<section class="contact-section">
  <div class="container">
    <?php if (isset($_GET['contact'])): ?>
      <?php if ($_GET['contact'] === 'success'): ?>
        <div class="contact-msg contact-msg--success">お問い合わせありがとうございます。内容を確認のうえ、折り返しご連絡いたします。</div>
      <?php elseif ($_GET['contact'] === 'error' && isset($_GET['msg'])): ?>
        <div class="contact-msg contact-msg--error"><?php echo esc_html($_GET['msg']); ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="oishi_contact">
      <?php wp_nonce_field('oishi_contact_nonce', '_oishi_nonce'); ?>
      <div class="contact-form__field">
        <label for="contact-name">お名前</label>
        <input type="text" id="contact-name" name="contact_name" required>
      </div>
      <div class="contact-form__field">
        <label for="contact-email">メールアドレス</label>
        <input type="email" id="contact-email" name="contact_email" required>
      </div>
      <div class="contact-form__field">
        <label for="contact-message">お問い合わせ内容</label>
        <textarea id="contact-message" name="contact_message" rows="5" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">送信する</button>
    </form>
  </div>
</section>

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
