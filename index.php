<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AI Lab OISHI - 小規模事業者から大企業まで、AI導入を戦略から実装までワンストップで支援します。">
  <?php wp_head(); ?>
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="#hero" class="site-logo"><?php echo oishi_ai_get_logo_image_html(); ?>AI Lab OISHI</a>
    <button class="mobile-toggle" aria-label="Menu">&#9776;</button>
    <nav>
      <ul class="header-nav">
        <li><a href="#services"><span class="nav-ja">サービス</span><span class="nav-en">Services</span></a></li>
        <li><a href="#strengths"><span class="nav-ja">選ばれる理由</span><span class="nav-en">Why Us</span></a></li>
        <li><a href="#about"><span class="nav-ja">会社概要</span><span class="nav-en">Company</span></a></li>
        <li><a href="<?php echo home_url('/portfolio/'); ?>"><span class="nav-ja">実績</span><span class="nav-en">Portfolio</span></a></li>
        <li><a href="<?php echo home_url('/blog/'); ?>"><span class="nav-ja">ブログ</span><span class="nav-en">Blog</span></a></li>
        <li><a href="<?php echo home_url('/contact/'); ?>"><span class="nav-ja">お問い合わせ</span><span class="nav-en">Contact</span></a></li>
      </ul>
    </nav>
  </div>
</header>

<section class="hero" id="hero">
  <div class="hero-content">
    <span class="hero-label">AI Consulting</span>
    <h1>AIの力で、ビジネスを次のステージへ</h1>
    <p>規模を問わず、御社に最適なAIソリューションを<br>戦略から実装までワンストップで提供します。</p>
    <a href="<?php echo home_url('/contact/'); ?>" class="btn btn-primary">無料相談はこちら</a>
  </div>
</section>

<section class="usecases" id="usecases">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Use Cases</span>
      <h2 class="section-title">こんなお悩みありませんか？</h2>
    </div>
    <ul class="usecases-list">
      <li>
        <div class="q">「AIを導入したいが、何から始めればいいか分からない」</div>
        <div class="a">業務分析から最適なAI活用ポイントを特定し、導入ロードマップを策定します。</div>
      </li>
      <li>
        <div class="q">「日々の事務作業に時間を取られ、本業に集中できない」</div>
        <div class="a">生成AIによる業務自動化で、レポート作成やデータ入力の工数を大幅に削減します。</div>
      </li>
      <li>
        <div class="q">「社員にAIを使わせたいが、社内にノウハウがない」</div>
        <div class="a">実務に直結する研修プログラムで、チーム全体のAIリテラシーを底上げします。</div>
      </li>
    </ul>
  </div>
</section>

<section class="services" id="services">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Services</span>
      <h2 class="section-title">サービス</h2>
      <p class="section-sub">事業規模やフェーズに合わせて、最適なAI活用をご提案します。</p>
    </div>
    <div class="services-grid services-grid--seven">
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-01-strategy-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-01-strategy-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-01-strategy.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="AI戦略コンサルティングの実行ロードマップ図" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>AI戦略コンサルティング</h3>
        <p>ビジネス課題のヒアリングからAI導入ロードマップの策定まで。御社の状況に合った最適な導入計画を設計します。</p>
      </div>
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-02-custom-dev-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-02-custom-dev-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-02-custom-dev.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="業務特化のカスタムAI開発イメージ" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>カスタムAI開発</h3>
        <p>チャットボット・文書解析・画像認識・予測モデルなど、業務に特化したAIシステムをオーダーメイドで開発します。</p>
      </div>
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-03-agent-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-03-agent-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-03-agent.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="AIエージェントの設計と運用フロー図" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>AIエージェント開発</h3>
        <p>現場業務に合わせたAIエージェントを設計・実装。情報収集・判断補助・タスク実行までを一連のフローで自動化し、日々の業務スピードを高めます。</p>
      </div>
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-04-automation-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-04-automation-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-04-automation.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="生成AIによる業務プロセス自動化の概念図" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>業務プロセス自動化</h3>
        <p>生成AIを活用し、レポート作成・データ入力・メール対応などの定型業務を自動化。人的リソースをコア業務に集中できます。</p>
      </div>
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-05-training-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-05-training-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-05-training.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="AI研修と内製化支援の学習イメージ" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>AI研修・内製化支援</h3>
        <p>ChatGPTやClaude等の生成AIツールの活用研修から、社内AIチームの立ち上げまで。自走できる組織づくりを伴走支援します。</p>
      </div>
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-06-diagnosis-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-06-diagnosis-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-06-diagnosis.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="AI導入診断で課題を可視化するイメージ" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>AI導入診断</h3>
        <p>既存の業務フローを分析し、AI導入で効果が見込める領域を特定。投資対効果の試算とともにレポートとしてご提供します。</p>
      </div>
      <div class="service-card">
        <div class="service-media" aria-hidden="true">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/services/service-07-integration-800.webp'); ?>" srcset="<?php echo esc_attr(get_template_directory_uri() . '/assets/services/service-07-integration-800.webp 800w, ' . get_template_directory_uri() . '/assets/services/service-07-integration.webp 1600w'); ?>" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 46vw, 30vw" alt="既存システムへAI機能を統合する連携イメージ" loading="lazy" decoding="async" width="1600" height="900">
        </div>
        <h3>既存システムへのAI統合</h3>
        <p>お使いの業務システムやWebサービスにAI機能を組み込み。API連携やプラグイン開発で、既存環境を活かしたまま高度化します。</p>
      </div>
    </div>
  </div>
</section>

<section class="strengths" id="strengths">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Why Us</span>
      <h2 class="section-title">選ばれる理由</h2>
      <p class="section-sub">規模を問わず、御社に合ったAI活用を実現します。</p>
    </div>
    <div class="strengths-grid">
      <div class="strength-item">
        <div class="strength-num">01</div>
        <h3>規模を問わない柔軟な対応</h3>
        <p>個人事業主のちょっとした業務改善から、大企業の全社的なDX推進まで。予算や体制に合わせたプランをご提案します。</p>
      </div>
      <div class="strength-item">
        <div class="strength-num">02</div>
        <h3>戦略から実装まで一気通貫</h3>
        <p>「何をすべきか」の戦略立案から、実際のシステム開発・導入・運用まで。複数の会社に頼む必要はありません。</p>
      </div>
      <div class="strength-item">
        <div class="strength-num">03</div>
        <h3>最新技術への迅速なキャッチアップ</h3>
        <p>GPT・Claude・オープンソースモデルなど、日進月歩のAI技術を常にフォロー。最適な技術選定で無駄なコストを防ぎます。</p>
      </div>
    </div>
  </div>
</section>

<section class="about" id="about">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Company</span>
      <h2 class="section-title">会社概要</h2>
    </div>
    <div class="about-message">
      <p>AIは大企業だけのものではありません。テクノロジーの力は、事業の規模に関係なく活用できるものだと私たちは考えています。</p>
      <p>「何ができるか分からない」という段階から一緒に考え、御社のビジネスに本当に役立つAI活用を見つけ出す。それが私たちの仕事です。</p>
      <div class="sig">— AI Lab OISHI 代表</div>
    </div>
    <div class="about-table-wrap">
      <table class="about-table">
        <tr><th>社名</th><td>AI Lab OISHI</td></tr>
        <tr><th>所在地</th><td>神奈川県川崎市</td></tr>
        <tr><th>事業内容</th><td>AIコンサルティング / AI開発 / DX推進支援</td></tr>
        <tr><th>URL</th><td>https://oishillc.jp</td></tr>
      </table>
    </div>
  </div>
</section>

<section class="flow">
  <div class="container">
    <div class="text-center">
      <span class="section-label">Flow</span>
      <h2 class="section-title">ご相談の流れ</h2>
      <p class="section-sub">お問い合わせから最短3営業日でご提案が可能です。</p>
    </div>
    <div class="flow-track">
      <div class="flow-step">
        <div class="flow-dot">1</div>
        <h3>お問い合わせ</h3>
        <p>メールにてご連絡ください。現状の課題やご要望を簡単にお聞かせください。</p>
      </div>
      <div class="flow-step">
        <div class="flow-dot">2</div>
        <h3>無料ヒアリング</h3>
        <p>オンラインにて30〜60分程度、業務内容や課題を詳しくお伺いします。</p>
      </div>
      <div class="flow-step">
        <div class="flow-dot">3</div>
        <h3>ご提案</h3>
        <p>ヒアリング内容をもとに、最適なAI活用プランとお見積りをご提示します。</p>
      </div>
    </div>
  </div>
</section>

<section class="cta" id="contact">
  <div class="container cta-content">
    <span class="section-label">Contact</span>
    <h2>まずはお気軽にご相談ください</h2>
    <p>「AIで何ができるか分からない」という段階からでもOK。<br>初回のご相談は無料で承ります。</p>

    <a href="<?php echo home_url('/contact/'); ?>" class="btn btn-primary">お問い合わせはこちら</a>
  </div>
</section>

<footer class="site-footer">
  <div class="container">
    <div class="footer-links">
      <a href="<?php echo home_url('/portfolio/'); ?>">Portfolio</a>
    </div>
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
