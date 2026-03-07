<?php
declare(strict_types=1);

function buildConsultationAgentReply(string $message, string $normalized): ?array
{
    $state = getConsultationAgentState();
    if ($state !== null) {
        return continueConsultationAgent($state, $message, $normalized);
    }

    $contactDraftReply = maybeBuildContactDraftReply($message, $normalized);
    if ($contactDraftReply !== null) {
        return $contactDraftReply;
    }

    if (!shouldStartConsultationAgent($message)) {
        return null;
    }

    return [
        "role" => "assistant",
        "content" => "導入相談の整理を始めます。\n\n"
            . "4つだけ順番に確認します。\n"
            . "1. 業種・会社規模\n"
            . "2. いちばん重い業務や課題\n"
            . "3. 今使っているツールやデータ\n"
            . "4. 3か月以内に改善したいこと\n\n"
            . "まず、業種と会社規模を教えてください。例: 製造業 / 20名、士業 / 5名\n\n"
            . "中断したいときは「中断」と送ってください。",
        "agent_state" => [
            "mode" => "consultation",
            "step" => "business_profile",
            "answers" => [],
        ],
    ];
}

function continueConsultationAgent(array $state, string $message, string $normalized): array
{
    if (isConsultationAgentCancelIntent($normalized)) {
        return [
            "role" => "assistant",
            "content" => "導入相談の整理を中断しました。再開したいときは「導入診断を始めてください」と送ってください。",
            "clear_agent_state" => true,
        ];
    }

    $step = (string) ($state["step"] ?? "");
    $answers = $state["answers"] ?? [];
    if (!is_array($answers)) {
        $answers = [];
    }

    $answer = trim($message);
    if ($answer === "") {
        return [
            "role" => "assistant",
            "content" => buildConsultationStepPrompt($step),
            "agent_state" => $state,
        ];
    }

    switch ($step) {
        case "business_profile":
            $answers["business_profile"] = $answer;
            return [
                "role" => "assistant",
                "content" => "ありがとうございます。次に、今いちばん重い業務や課題を1つ教えてください。例: 問い合わせ対応に時間がかかる、日報集計が手作業",
                "agent_state" => [
                    "mode" => "consultation",
                    "step" => "pain_point",
                    "answers" => $answers,
                ],
            ];

        case "pain_point":
            $answers["pain_point"] = $answer;
            return [
                "role" => "assistant",
                "content" => "次に、今使っているツールやデータを教えてください。例: Gmail、Slack、スプレッドシート、kintone、PDF",
                "agent_state" => [
                    "mode" => "consultation",
                    "step" => "current_stack",
                    "answers" => $answers,
                ],
            ];

        case "current_stack":
            $answers["current_stack"] = $answer;
            return [
                "role" => "assistant",
                "content" => "最後に、3か月以内に改善したいことを教えてください。例: 問い合わせ一次対応を半自動化したい、週次レポート作成を半分にしたい",
                "agent_state" => [
                    "mode" => "consultation",
                    "step" => "goal",
                    "answers" => $answers,
                ],
            ];

        case "goal":
        default:
            $answers["goal"] = $answer;
            $report = buildConsultationReport($answers);
            return [
                "role" => "assistant",
                "content" => renderConsultationSummary($report),
                "clear_agent_state" => true,
                "agent_report" => $report,
                "agent_offer" => "contact_draft",
            ];
    }
}

function shouldStartConsultationAgent(string $message): bool
{
    if (preg_match("/(導入診断|相談整理|3分診断|ヒアリングを始め|相談を始め)/u", $message) === 1) {
        return true;
    }

    $hasDomainCue = preg_match("/(AI導入|生成AI|AI活用|AIエージェント|チャットボット|業務自動化|自動化|PoC|RAG|業務改善)/u", $message) === 1;
    $hasAgentCue = preg_match("/(相談したい|相談です|診断して|診断したい|整理したい|壁打ちしたい|ヒアリングして|見積もりしたい|提案して|始めてください|始めたい|検討中)/u", $message) === 1;

    return $hasDomainCue && $hasAgentCue;
}

function isConsultationAgentCancelIntent(string $normalized): bool
{
    return preg_match("/^(中断|やめる|終了|停止|キャンセル|stop)$/ui", $normalized) === 1;
}

function buildConsultationStepPrompt(string $step): string
{
    return match ($step) {
        "business_profile" => "業種と会社規模を教えてください。例: 製造業 / 20名、士業 / 5名",
        "pain_point" => "今いちばん重い業務や課題を1つ教えてください。例: 問い合わせ対応に時間がかかる、日報集計が手作業",
        "current_stack" => "今使っているツールやデータを教えてください。例: Gmail、Slack、スプレッドシート、kintone、PDF",
        default => "3か月以内に改善したいことを教えてください。例: 問い合わせ一次対応を半自動化したい",
    };
}

function buildConsultationReport(array $answers): array
{
    $assessment = assessConsultationTrack($answers);

    return [
        "answers" => [
            "business_profile" => trim((string) ($answers["business_profile"] ?? "")),
            "pain_point" => trim((string) ($answers["pain_point"] ?? "")),
            "current_stack" => trim((string) ($answers["current_stack"] ?? "")),
            "goal" => trim((string) ($answers["goal"] ?? "")),
        ],
        "track" => $assessment["track"],
        "reason" => $assessment["reason"],
        "steps" => $assessment["steps"],
        "followups" => $assessment["followups"],
    ];
}

function assessConsultationTrack(array $answers): array
{
    $combined = implode("\n", array_map(static fn ($value): string => trim((string) $value), $answers));

    if (preg_match("/(問い合わせ|問合せ|メール対応|顧客対応|一次回答|FAQ|チャット対応)/u", $combined) === 1) {
        return [
            "track" => "問い合わせ対応の自動化",
            "reason" => "問い合わせや一次回答の負荷が中心なので、FAQ整理と一次回答の自動化から始めると効果を出しやすいためです。",
            "steps" => [
                "問い合わせ種別と回答テンプレートを棚卸しする",
                "一次回答の対象範囲を決めてチャットボットまたはメール補助を試す",
            ],
            "followups" => [
                "問い合わせ件数とピーク時間帯",
                "既存FAQや過去メールの有無",
            ],
        ];
    }

    if (preg_match("/(マニュアル|手順書|社内文書|ナレッジ|検索|議事録|資料|規程|PDF)/u", $combined) === 1) {
        return [
            "track" => "社内ナレッジ検索の整備",
            "reason" => "資料探索や確認コストが主課題に見えるため、RAG型の検索導線を先に作ると現場導入しやすいためです。",
            "steps" => [
                "検索対象にする文書と更新頻度を整理する",
                "権限設計を含めて小さな検索PoCを作る",
            ],
            "followups" => [
                "参照したい文書の種類と保存場所",
                "閲覧権限の制約",
            ],
        ];
    }

    if (preg_match("/(入力|転記|集計|レポート|日報|月報|請求|定型|コピペ|メール送信)/u", $combined) === 1) {
        return [
            "track" => "定型業務の自動化",
            "reason" => "繰り返し作業の削減が主目的なので、LLMより先に自動化しやすい定型フローから着手するのが費用対効果に合うためです。",
            "steps" => [
                "1週間分の手作業フローを洗い出して時間を見積もる",
                "入力・集計・通知のどこを先に自動化するか絞る",
            ],
            "followups" => [
                "現在の作業時間と担当人数",
                "使っている業務システムやファイル形式",
            ],
        ];
    }

    if (preg_match("/(予測|需要|分類|判定|画像|検査|OCR|分析|スコアリング)/u", $combined) === 1) {
        return [
            "track" => "個別AI開発のPoC",
            "reason" => "予測や判定の精度が価値の中心なので、まず小さく精度検証できるPoCに切るのが安全なためです。",
            "steps" => [
                "評価したい指標と正解データの有無を確認する",
                "対象業務を1ケースに絞ってPoCを組む",
            ],
            "followups" => [
                "利用できる教師データの量",
                "業務で許容できる精度と失敗コスト",
            ],
        ];
    }

    return [
        "track" => "業務整理からの導入診断",
        "reason" => "現時点では課題が広めなので、先に対象業務と効果指標を絞る方が無駄な開発を避けやすいためです。",
        "steps" => [
            "AI化候補の業務を3つまでに絞る",
            "時間削減か売上改善か、評価軸を1つ決める",
        ],
        "followups" => [
            "いちばん早く成果を出したい部署",
            "3か月以内に達成したい定量目標",
        ],
    ];
}

function renderConsultationSummary(array $report): string
{
    $answers = $report["answers"] ?? [];
    $steps = $report["steps"] ?? [];
    $followups = $report["followups"] ?? [];

    $lines = [
        "相談内容を整理すると次のとおりです。",
        "",
        "- 業種・規模: " . (string) ($answers["business_profile"] ?? ""),
        "- 主要課題: " . (string) ($answers["pain_point"] ?? ""),
        "- 現在のツール/データ: " . (string) ($answers["current_stack"] ?? ""),
        "- 3か月目標: " . (string) ($answers["goal"] ?? ""),
        "",
        "現時点のおすすめは「" . (string) ($report["track"] ?? "") . "」です。",
        "- 理由: " . (string) ($report["reason"] ?? ""),
    ];

    foreach ($steps as $index => $step) {
        $lines[] = "- 最初のステップ" . ($index + 1) . ": " . $step;
    }

    foreach ($followups as $index => $followup) {
        $lines[] = "- 初回相談で確認したい点" . ($index + 1) . ": " . $followup;
    }

    $lines[] = "";
    $lines[] = "必要なら、この内容をそのまま /contact/ に送れる問い合わせ文にまとめます。";
    $lines[] = "「問い合わせ文を作って」と送ってください。";

    return implode("\n", $lines);
}

function maybeBuildContactDraftReply(string $message, string $normalized): ?array
{
    $wantsDraft = isContactDraftRequest($normalized);
    $acceptsOffer = getLastAgentOffer() === "contact_draft" && isSimpleOfferAcceptance($normalized);
    if (!$wantsDraft && !$acceptsOffer) {
        return null;
    }

    $report = getLastConsultationReport();
    if ($report === null) {
        return [
            "role" => "assistant",
            "content" => "問い合わせ文の下書きを作る前に、導入相談を短く整理すると精度が上がります。「導入診断を始めてください」と送ってください。",
            "clear_agent_offer" => true,
        ];
    }

    return [
        "role" => "assistant",
        "content" => renderContactDraft($report),
        "clear_agent_offer" => true,
    ];
}

function isContactDraftRequest(string $normalized): bool
{
    return preg_match("/(問い合わせ文|問合せ文|相談文|送信用|メール文|下書き).*(作|用意|まとめ)|^(問い合わせ文|問合せ文|下書き)(を)?(作って|作成して|お願い|お願いします|ください)$/u", $normalized) === 1;
}

function isSimpleOfferAcceptance(string $normalized): bool
{
    return preg_match("/^(はい|お願いします|お願い|ぜひ|作って|作成して|ください)$/u", $normalized) === 1;
}

function renderContactDraft(array $report): string
{
    $answers = $report["answers"] ?? [];
    $followups = $report["followups"] ?? [];
    $contactPage = HOMEPAGE_FACTS["contact"]["page"] ?? "/contact/";

    $lines = [
        "問い合わせ文の下書きです。",
        "",
        "件名: AI導入相談（" . (string) ($report["track"] ?? "導入診断") . "）",
        "",
        "本文:",
        "AI Lab OISHI ご担当者様",
        "",
        "ホームページのチャットで整理した内容をもとに、AI導入について相談したくご連絡しました。",
        "",
        "- 業種・規模: " . (string) ($answers["business_profile"] ?? ""),
        "- 主な課題: " . (string) ($answers["pain_point"] ?? ""),
        "- 現在のツール/データ: " . (string) ($answers["current_stack"] ?? ""),
        "- 3か月以内に改善したいこと: " . (string) ($answers["goal"] ?? ""),
        "- 相談したいテーマ: " . (string) ($report["track"] ?? ""),
    ];

    foreach ($followups as $index => $followup) {
        $lines[] = "- 確認したい点" . ($index + 1) . ": " . $followup;
    }

    $lines[] = "";
    $lines[] = "初回ヒアリングの進め方をご提案いただけますと幸いです。";
    $lines[] = "よろしくお願いいたします。";
    $lines[] = "";
    $lines[] = "送付先: " . $contactPage;

    return implode("\n", $lines);
}
