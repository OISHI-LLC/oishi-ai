<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Lab OISHI - AIコンサルティングで企業のDXを加速。戦略立案から実装まで一気通貫で支援します。">
  <?php wp_head(); ?>
</head>
<body>

<!-- ===== Header ===== -->
<header class="site-header">
  <div class="container header-inner">
    <a href="#hero" class="site-logo"><span>AI Lab</span> OISHI</a>
    <button class="mobile-toggle" aria-label="メニュー">&#9776;</button>
    <nav>
      <ul class="header-nav">
        <li><a href="#services">サービス</a></li>
        <li><a href="#strengths">強み</a></li>
        <li><a href="#about">会社概要</a></li>
        <li><a href="#contact">お問い合わせ</a></li>
      </ul>
    </nav>
  </div>
</header>

<!-- ===== Hero ===== -->
<section class="hero" id="hero">
  <div class="hero-content">
    <span class="hero-label">AI Consulting &amp; Implementation</span>
    <h1>AIの力で、<br>ビジネスを<em>次のステージ</em>へ</h1>
    <p>戦略立案から実装・運用まで。<br>御社に最適なAIソリューションをワンストップで提供します。</p>
    <a href="#contact" class="btn btn-primary">無料相談はこちら</a>
  </div>
</section>

<!-- ===== Services ===== -->
<section class="services" id="services">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Services</span>
      <h2 class="section-title">サービス</h2>
      <p class="section-sub">AIの導入から活用まで、あらゆるフェーズを支援します。</p>
    </div>
    <div class="services-grid">
      <div class="service-card">
        <div class="service-icon">&#x1F4CA;</div>
        <h3>AI戦略コンサルティング</h3>
        <p>ビジネス課題を分析し、AI導入のロードマップを策定。ROIを最大化する戦略を設計します。</p>
      </div>
      <div class="service-card">
        <div class="service-icon">&#x2699;</div>
        <h3>カスタムAI開発</h3>
        <p>自然言語処理・画像認識・予測モデルなど、御社専用のAIシステムを設計・開発します。</p>
      </div>
      <div class="service-card">
        <div class="service-icon">&#x1F504;</div>
        <h3>業務プロセス自動化</h3>
        <p>生成AIとRPAを組み合わせ、ルーティン業務を自動化。生産性を飛躍的に向上させます。</p>
      </div>
      <div class="service-card">
        <div class="service-icon">&#x1F393;</div>
        <h3>AI研修・内製化支援</h3>
        <p>社内チームがAIを使いこなせるよう、実践的なトレーニングと伴走支援を行います。</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== Strengths ===== -->
<section class="strengths" id="strengths">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Why Us</span>
      <h2 class="section-title">選ばれる理由</h2>
      <p class="section-sub">実績と専門性で、確かな成果をお届けします。</p>
    </div>
    <div class="strengths-grid">
      <div class="strength-item">
        <div class="num">50+</div>
        <div class="label">AI導入プロジェクト実績</div>
      </div>
      <div class="strength-item">
        <div class="num">98%</div>
        <div class="label">クライアント満足度</div>
      </div>
      <div class="strength-item">
        <div class="num">40%</div>
        <div class="label">平均コスト削減率</div>
      </div>
      <div class="strength-item">
        <div class="num">24h</div>
        <div class="label">初回レスポンス</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== About ===== -->
<section class="about" id="about">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Company</span>
      <h2 class="section-title">会社概要</h2>
    </div>
    <table class="about-table">
      <tr><th>社名</th><td>AI Lab OISHI</td></tr>
      <tr><th>所在地</th><td>東京都</td></tr>
      <tr><th>事業内容</th><td>AIコンサルティング / AI開発 / DX推進支援</td></tr>
      <tr><th>URL</th><td>https://oishillc.jp</td></tr>
    </table>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="cta" id="contact">
  <div class="container cta-content">
    <span class="section-label">Contact</span>
    <h2>まずはお気軽にご相談ください</h2>
    <p>AIの活用方法や導入プロセスについて、無料でご相談いただけます。</p>
    <a href="mailto:info@oishillc.jp" class="btn btn-primary">お問い合わせ</a>
  </div>
</section>

<!-- ===== Footer ===== -->
<footer class="site-footer">
  <div class="container">
    <p>&copy; <?php echo date('Y'); ?> AI Lab OISHI. All rights reserved.</p>
  </div>
</footer>

<?php wp_footer(); ?>

<script>
(function(){
  // Mobile menu toggle
  var toggle = document.querySelector('.mobile-toggle');
  var nav = document.querySelector('.header-nav');
  if(toggle && nav){
    toggle.addEventListener('click', function(){
      nav.classList.toggle('open');
    });
  }
  // Close menu on nav click
  document.querySelectorAll('.header-nav a').forEach(function(a){
    a.addEventListener('click', function(){
      nav.classList.remove('open');
    });
  });
})();
</script>

</body>
</html>
