<?php
/**
 * Template Name: Portfolio
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Lab OISHI の実績紹介。AIチャットボット、RAG、AIエージェントなど、多彩なAIプロジェクトの導入事例をご覧ください。">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="icon" href="<?php echo get_template_directory_uri(); ?>/favicon.png" type="image/png">
  <?php wp_head(); ?>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?php echo home_url('/'); ?>" class="site-logo"><img src="<?php echo get_template_directory_uri(); ?>/logo.png" alt="AI Lab OISHI" class="site-logo-img">AI Lab OISHI</a>
    <button class="mobile-toggle" aria-label="Menu">&#9776;</button>
    <nav>
      <ul class="header-nav">
        <li><a href="<?php echo home_url('/#services'); ?>">Services</a></li>
        <li><a href="<?php echo home_url('/#strengths'); ?>">Why Us</a></li>
        <li><a href="<?php echo home_url('/#about'); ?>">Company</a></li>
        <li><a href="<?php echo home_url('/portfolio/'); ?>" class="nav-active">Portfolio</a></li>
        <li><a href="<?php echo home_url('/blog/'); ?>">Blog</a></li>
        <li><a href="<?php echo home_url('/#contact'); ?>">Contact</a></li>
      </ul>
    </nav>
  </div>
</header>

<section class="portfolio-hero">
  <div class="container text-center">
    <span class="section-label">Portfolio</span>
    <h1 class="section-title">実績紹介</h1>
    <p class="section-sub">AI導入プロジェクトの一部をご紹介します。</p>
  </div>
</section>

<section class="portfolio-section">
  <div class="container">
    <div class="portfolio-grid">

      <div class="portfolio-card">
        <h3>AIチャットボット導入</h3>
        <p class="portfolio-category">カスタマーサポート自動化</p>
        <p class="portfolio-desc">大手EC企業のカスタマーサポートにAIチャットボットを導入。問い合わせ対応の70%を自動化し、オペレーターの負荷を大幅に削減。24時間対応を実現し、顧客満足度が15%向上。</p>
        <div class="portfolio-tech">
          <span>GPT-4</span>
          <span>LangChain</span>
          <span>Python</span>
          <span>AWS</span>
        </div>
      </div>

      <div class="portfolio-card">
        <h3>RAGナレッジ検索システム</h3>
        <p class="portfolio-category">社内ナレッジ検索</p>
        <p class="portfolio-desc">製造業クライアントの社内ドキュメント（10万件超）をベクトルDB化し、自然言語で検索可能なRAGシステムを構築。情報検索時間を平均80%短縮し、ナレッジの属人化を解消。</p>
        <div class="portfolio-tech">
          <span>OpenAI Embeddings</span>
          <span>Pinecone</span>
          <span>Next.js</span>
          <span>Azure</span>
        </div>
      </div>

      <div class="portfolio-card">
        <h3>AIエージェントによる業務自動化</h3>
        <p class="portfolio-category">業務プロセス自動化</p>
        <p class="portfolio-desc">金融機関の審査プロセスにAIエージェントを導入。書類の自動読み取り・データ照合・レポート生成を一気通貫で自動化。処理時間を1件あたり30分から3分に短縮。</p>
        <div class="portfolio-tech">
          <span>Claude API</span>
          <span>Python</span>
          <span>OCR</span>
          <span>FastAPI</span>
        </div>
      </div>

      <div class="portfolio-card">
        <h3>予測分析ダッシュボード</h3>
        <p class="portfolio-category">データ分析・可視化</p>
        <p class="portfolio-desc">小売チェーンの販売データを活用し、需要予測AIとリアルタイムダッシュボードを構築。在庫回転率が25%改善し、廃棄ロスを年間数千万円削減。経営判断のスピードが向上。</p>
        <div class="portfolio-tech">
          <span>Prophet</span>
          <span>Streamlit</span>
          <span>BigQuery</span>
          <span>GCP</span>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="cta" id="contact">
  <div class="container">
    <span class="section-label">Contact</span>
    <h2>プロジェクトのご相談はお気軽に</h2>
    <p>貴社の課題に合わせた最適なAIソリューションをご提案します。</p>
    <a href="mailto:info@oishillc.jp" class="btn btn-primary">お問い合わせ</a>
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
