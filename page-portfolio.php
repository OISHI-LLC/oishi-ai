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
  <?php wp_head(); ?>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?php echo home_url('/'); ?>" class="site-logo"><?php echo oishi_ai_get_logo_image_html(); ?>AI Lab OISHI</a>
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

      <article class="portfolio-card portfolio-card--featured">
        <p class="portfolio-badge">Featured</p>
        <h3>OISHI ChatBot</h3>
        <p class="portfolio-category">顧客対応・社内相談の会話支援</p>
        <p class="portfolio-desc">業務相談に特化した日本語チャットボットを実装。相談内容の整理、導入ステップの提示、費用感の目安提示までを対話形式で支援。UI/UXを含めた短期プロトタイプとして公開し、提案フェーズの意思決定を高速化。</p>
        <a class="portfolio-link" href="<?php echo esc_url(get_template_directory_uri() . '/chatbot.php'); ?>" target="_blank" rel="noopener noreferrer">デモを見る</a>
      </article>

      <div class="portfolio-card">
        <h3>AIチャットボット導入</h3>
        <p class="portfolio-category">カスタマーサポート自動化</p>
        <p class="portfolio-desc">大手EC企業のカスタマーサポートにAIチャットボットを導入。問い合わせの一次対応を自動化し、オペレーターが複雑な相談に集中できる体制を構築。FAQ更新と運用改善を継続し、安定した顧客対応を実現。</p>
      </div>

      <div class="portfolio-card">
        <h3>RAGナレッジ検索システム</h3>
        <p class="portfolio-category">社内ナレッジ検索</p>
        <p class="portfolio-desc">製造業クライアントの社内ドキュメントをベクトルDB化し、自然言語で検索できるRAGシステムを構築。部署をまたいだ情報参照をしやすくし、調査時の手戻りを抑える運用基盤として展開。</p>
      </div>

      <div class="portfolio-card">
        <h3>AIエージェントによる業務自動化</h3>
        <p class="portfolio-category">業務プロセス自動化</p>
        <p class="portfolio-desc">金融機関の審査プロセスにAIエージェントを導入。書類の読み取り・データ照合・レポート生成を一気通貫で支援し、担当者の確認フローを標準化。運用負荷を抑えながら、審査業務の品質を安定化。</p>
      </div>

      <div class="portfolio-card">
        <h3>予測分析ダッシュボード</h3>
        <p class="portfolio-category">データ分析・可視化</p>
        <p class="portfolio-desc">小売チェーンの販売データを活用し、需要予測AIとリアルタイムダッシュボードを構築。店舗ごとの在庫判断と発注計画を可視化し、現場と本部の意思決定を支援。継続的な分析を通じて運用改善を定着。</p>
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
