<?php
$api = 'https://bible-api.com';
$searchApi = 'https://bible-api.dws-cloud.com/v1';

function apiRequest($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'BiblePortal/2.0'
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Connection error: ' . $error);
    }

    $data = json_decode($response, true);

    if ($status !== 200) {
        $message = is_array($data) ? ($data['error'] ?? '') : '';
        throw new Exception($message ?: 'API returned HTTP ' . $status);
    }

    if (!is_array($data)) {
        throw new Exception('Invalid API response.');
    }

    return $data;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function chapterNumber($item) {
    if (isset($item['chapter'])) return (int)$item['chapter'];
    if (isset($item['number'])) return (int)$item['number'];

    if (isset($item['id']) && preg_match('~/(\d+)$~', $item['id'], $match)) {
        return (int)$match[1];
    }

    return 0;
}

$version = $_GET['version'] ?? 'KJV';
$allowedVersions = ['KJV', 'WEB', 'BSB'];

if (!in_array($version, $allowedVersions, true)) {
    $version = 'KJV';
}

$bookId = $_GET['book'] ?? '';
$chapter = $_GET['chapter'] ?? '';
$search = trim($_GET['search'] ?? '');

$books = [];
$chapters = [];
$verses = [];
$currentBook = null;
$searchResults = [];
$searchTotal = 0;
$error = '';
$dailyVerse = null;
$limit = 100;

try {
    // Load Bible book list
    $bookData = apiRequest($api . '/data/' . strtolower($version));
    $books = $bookData['books'] ?? [];

    // Load the verse of the day
    $dailyVerse = apiRequest(
        $api . '/Psalm%20118:24?translation=' . strtolower($version)
    );

    // Keyword search. This requires the search endpoint configured here
    // to be available and to return a "results" array.
    if ($search !== '') {
        $url = $searchApi . '/search?' . http_build_query([
            'q' => $search,
            'translation' => strtolower($version),
            'limit' => $limit,
            'offset' => 0
        ]);

        $searchData = apiRequest($url);
        $searchResults = $searchData['results'] ?? [];
        $searchTotal = $searchData['total'] ?? count($searchResults);
    }

    // Load selected book and chapter
    if ($bookId !== '') {
        foreach ($books as $book) {
            if (($book['id'] ?? '') === $bookId) {
                $currentBook = $book;
                break;
            }
        }

        if (!$currentBook) {
            throw new Exception('Selected book was not found.');
        }

        $bookData = apiRequest(
            $api . '/data/' . strtolower($version) . '/' . rawurlencode($bookId)
        );
        $chapters = $bookData['chapters'] ?? [];

        if ($chapter !== '') {
            if (!ctype_digit((string)$chapter) || (int)$chapter < 1) {
                throw new Exception('Invalid chapter number.');
            }

            $reference = $currentBook['name'] . ' ' . (int)$chapter;
            $chapterData = apiRequest(
                $api . '/' . rawurlencode($reference) .
                '?translation=' . strtolower($version)
            );
            $verses = $chapterData['verses'] ?? [];
        }
    }
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}

$dailyText = $dailyVerse['text'] ?? '';
$dailyReference = $dailyVerse['reference'] ?? 'Psalm 118:24';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#172b25">
<title>Bible Portal | Read, Search & Discover</title>

<style>
:root {
    --green: #173b30;
    --green-light: #285b49;
    --gold: #d7b56d;
    --cream: #f7f4ec;
    --ink: #202923;
    --muted: #718078;
    --white: #fff;
    --red: #a83d43;
    --shadow: 0 14px 40px rgba(22, 42, 33, .10);
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
    margin: 0;
    color: var(--ink);
    background: #f4f5f1;
    font-family: Inter, "Segoe UI", Arial, sans-serif;
}
a { color: inherit; text-decoration: none; }
button, input, select { font: inherit; }
button, a { -webkit-tap-highlight-color: transparent; }

/* Header */
.site-header {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 14px clamp(16px, 4vw, 58px);
    background: rgba(255,255,255,.96);
    border-bottom: 1px solid #e8e9e3;
    backdrop-filter: blur(14px);
}
.brand { display:flex; align-items:center; gap:12px; flex-shrink:0; }
.brand-mark {
    width: 48px; height: 48px; display:grid; place-items:center;
    border-radius: 14px; color:#fff; background:var(--green);
    font-family: Georgia,serif; font-size:29px; font-weight:bold;
    box-shadow: 0 6px 16px rgba(23,59,48,.2);
}
.brand-name { margin:0; color:var(--green); font-family:Georgia,serif; font-size:20px; letter-spacing:.7px; }
.brand-tag { color:#8a948d; font-size:11px; letter-spacing:1.5px; text-transform:uppercase; }
.top-links { display:flex; align-items:center; gap:22px; color:#536159; font-size:14px; }
.top-links a:hover { color:var(--red); }
.header-search { display:flex; width:min(440px, 40vw); }
.header-search input {
    min-width:0; flex:1; padding:12px 14px; border:1px solid #dfe4de;
    border-right:0; border-radius:10px 0 0 10px; outline:none; background:#fbfcfa;
}
.header-search input:focus { border-color:var(--green-light); }
.header-search button {
    border:0; border-radius:0 10px 10px 0; padding:0 17px;
    color:#fff; background:var(--green); cursor:pointer;
}
.header-search button:hover { background:var(--green-light); }

/* Hero */
.hero {
    min-height: 470px;
    display:flex;
    align-items:center;
    position:relative;
    overflow:hidden;
    color:white;
    background:
      linear-gradient(90deg, rgba(10,29,22,.88), rgba(10,29,22,.55) 55%, rgba(10,29,22,.18)),
      url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=2000&q=85') center 48%/cover;
}
.hero-inner { width:min(1180px, 100%); margin:auto; padding:75px 28px 88px; position:relative; z-index:1; }
.eyebrow { color:#f0d69e; font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase; }
.hero h1 {
    max-width:700px; margin:17px 0 16px; font: bold clamp(42px,6vw,72px)/1.05 Georgia,serif;
    letter-spacing:-1.5px;
}
.hero p { max-width:570px; color:rgba(255,255,255,.84); font-size:17px; line-height:1.8; }
.hero-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:28px; }
.primary-btn, .outline-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:9px;
    padding:14px 20px; border-radius:9px; font-weight:700; transition:.2s;
}
.primary-btn { color:#243328; background:var(--gold); border:1px solid var(--gold); }
.primary-btn:hover { transform:translateY(-3px); background:#e7c987; }
.outline-btn { color:white; border:1px solid rgba(255,255,255,.65); background:rgba(255,255,255,.08); }
.outline-btn:hover { background:rgba(255,255,255,.18); }
.hero-note {
    position:absolute; right:6%; bottom:30px; z-index:2; padding:14px 18px;
    border:1px solid rgba(255,255,255,.28); border-radius:12px;
    background:rgba(15,36,28,.45); backdrop-filter:blur(10px);
    color:#fff; font:italic 15px Georgia,serif;
}

/* Page layout */
.page-wrap { width:min(1250px, calc(100% - 40px)); margin:34px auto 70px; }
.section-heading { display:flex; align-items:end; justify-content:space-between; gap:15px; margin:38px 0 18px; }
.section-heading h2 { margin:0; color:var(--green); font: bold 30px Georgia,serif; }
.section-heading p { margin:7px 0 0; color:var(--muted); }
.panel {
    margin-bottom:25px; border:1px solid #e7e9e2; border-radius:16px;
    background:white; box-shadow:var(--shadow); overflow:hidden;
}
.panel-head { padding:18px 22px; border-bottom:1px solid #edf0e9; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.panel-head h2 { margin:0; color:var(--green); font: bold 21px Georgia,serif; }
.panel-body { padding:24px; }

/* Daily verse */
.daily-grid { display:grid; grid-template-columns: .9fr 1.1fr; gap:25px; align-items:stretch; }
.daily-art {
    min-height:280px; border-radius:13px; overflow:hidden; position:relative;
    display:flex; align-items:end; padding:24px; color:white;
    background:linear-gradient(0deg,rgba(10,30,23,.78),rgba(10,30,23,.04)),
    url('https://images.unsplash.com/photo-1470252649378-9c29740c9fa8?auto=format&fit=crop&w=1100&q=85') center/cover;
}
.daily-art span { font:italic 25px/1.35 Georgia,serif; }
.daily-copy { display:flex; flex-direction:column; justify-content:center; padding:8px 4px; }
.daily-copy .ref { color:var(--red); font-weight:800; letter-spacing:.5px; }
.daily-copy blockquote { margin:14px 0; color:#34443a; font:24px/1.65 Georgia,serif; }
.daily-copy .translation { color:var(--muted); font-size:12px; }
.button-row { display:flex; flex-wrap:wrap; gap:9px; margin-top:16px; }
.small-btn {
    border:1px solid #e2e7df; border-radius:8px; padding:10px 14px;
    background:#f8faf7; color:var(--green); cursor:pointer; font-weight:650;
}
.small-btn:hover { color:white; background:var(--green); border-color:var(--green); }

/* Topic chips */
.topic-list { display:flex; flex-wrap:wrap; gap:10px; }
.topic-chip {
    display:inline-flex; align-items:center; gap:8px; padding:11px 16px;
    border:1px solid #e4e9e1; border-radius:999px; background:#fbfcfa;
    color:#425448; font-weight:650; transition:.2s;
}
.topic-chip:hover { transform:translateY(-3px); border-color:var(--gold); background:#fff9e9; }

/* Bible scene cards */
.scene-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:17px; }
.scene-card {
    position:relative; min-height:235px; overflow:hidden; border-radius:14px;
    cursor:pointer; isolation:isolate; box-shadow:0 8px 25px rgba(20,40,30,.12);
    border:0; padding:0; text-align:left; color:white; background:#30483b;
}
.scene-card img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:-2; transition:transform .5s; }
.scene-card:after { content:""; position:absolute; inset:0; z-index:-1; background:linear-gradient(0deg,rgba(8,24,18,.88),rgba(8,24,18,.02) 75%); }
.scene-card:hover img { transform:scale(1.09); }
.scene-info { position:absolute; left:17px; right:17px; bottom:17px; }
.scene-info small { color:#f1d69d; font-size:10px; letter-spacing:1.5px; text-transform:uppercase; }
.scene-info strong { display:block; margin-top:6px; font: bold 22px Georgia,serif; }
.scene-info span { display:block; margin-top:5px; color:rgba(255,255,255,.8); font-size:12px; }

/* Books and chapters */
.book-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(145px,1fr)); gap:11px; }
.book-card {
    padding:16px; border:1px solid #e5e9e2; border-radius:10px; background:#fff;
    transition:.2s;
}
.book-card:hover { transform:translateY(-3px); border-color:#d6bd82; background:#fffaf0; color:var(--green); }
.book-card strong { display:block; }
.book-card small { display:block; margin-top:6px; color:#89938b; }
.chapter-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(54px,1fr)); gap:9px; }
.chapter-card { display:grid; place-items:center; padding:13px 5px; border:1px solid #e2e8e0; border-radius:8px; background:#fafbf9; }
.chapter-card:hover { color:white; background:var(--green); }
.verse { padding:13px 0; border-bottom:1px solid #edf0ea; font:19px/1.85 Georgia,serif; }
.verse strong { margin-right:9px; color:var(--red); font:700 13px Arial,sans-serif; }

/* Search results */
.search-summary { margin-bottom:18px; padding:17px; border-left:4px solid var(--gold); border-radius:8px; background:#fff9eb; }
.result-card { margin:13px 0; padding:18px; border:1px solid #e8ece4; border-radius:11px; background:#fff; }
.result-reference { margin-bottom:8px; color:var(--green); font-weight:800; }
.result-text { font:18px/1.8 Georgia,serif; }
.result-translation { margin-top:9px; color:#929b94; font-size:11px; }
mark { padding:2px 4px; border-radius:3px; background:#ffe59b; }
.error { padding:16px; border-radius:9px; color:#8f2525; background:#fff0f0; overflow-wrap:anywhere; }
.empty { padding:30px; text-align:center; color:var(--muted); }

/* Modal */
.modal {
    display:none; position:fixed; inset:0; z-index:100; padding:22px;
    align-items:center; justify-content:center; background:rgba(7,18,13,.78);
}
.modal.open { display:flex; }
.modal-box {
    position:relative; width:min(780px,100%); max-height:90vh; overflow:auto;
    border-radius:17px; background:#fff; box-shadow:0 25px 90px rgba(0,0,0,.3);
}
.modal-box img { display:block; width:100%; height:300px; object-fit:cover; }
.modal-content { padding:25px; }
.modal-content h2 { margin:0 0 10px; color:var(--green); font: bold 29px Georgia,serif; }
.modal-content p { color:#58665c; line-height:1.7; }
.close-modal {
    position:absolute; top:13px; right:13px; width:40px; height:40px;
    border:0; border-radius:50%; background:rgba(0,0,0,.6); color:white;
    font-size:24px; cursor:pointer;
}

/* Footer */
footer { padding:30px 20px; text-align:center; color:#dce5de; background:#142d24; }
footer strong { color:#f0d69e; }
.mobile-menu { display:none; }

@media(max-width:950px) {
    .top-links { display:none; }
    .header-search { width:min(500px,55vw); }
    .scene-grid { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:650px) {
    .site-header { flex-wrap:wrap; gap:12px; }
    .brand { flex:1; }
    .header-search { order:3; width:100%; }
    .hero { min-height:430px; }
    .hero-inner { padding:60px 22px 85px; }
    .hero-note { display:none; }
    .page-wrap { width:calc(100% - 24px); margin-top:20px; }
    .daily-grid { grid-template-columns:1fr; }
    .daily-art { min-height:210px; }
    .scene-grid { grid-template-columns:1fr 1fr; gap:10px; }
    .scene-card { min-height:190px; }
    .scene-info strong { font-size:18px; }
    .panel-body { padding:17px; }
    .section-heading h2 { font-size:25px; }
}
@media(max-width:390px) {
    .scene-grid { grid-template-columns:1fr; }
}
</style>
</head>

<body>

<header class="site-header">
    <a class="brand" href="index.php" aria-label="Bible Portal home">
        <div class="brand-mark">✝</div>
        <div>
            <h1 class="brand-name">BIBLE PORTAL</h1>
            <div class="brand-tag">SINCE 2026</div> </h3>
        </div>
    </a>

    <nav class="top-links" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="#daily">Verse of the Day</a>
        <a href="#discover">Discover</a>
        <a href="#books">Books</a>
    </nav>

    <form class="header-search" method="GET" action="index.php">
        <input type="text" name="search" placeholder="Search a Bible keyword..." value="<?= e($search) ?>" required>
        <input type="hidden" name="version" value="<?= e($version) ?>">
        <button type="submit" aria-label="Search">⌕ Search</button>
    </form>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="eyebrow">A place to explore God's word</div>
        <h1>Find wisdom.<br>Strengthen your faith.</h1>
        <p>Explore scripture, discover meaningful Bible stories, and search for the words that inspire your journey.</p>
        <div class="hero-actions">
            <a class="primary-btn" href="#books">📖 Explore the Bible</a>
            <a class="outline-btn" href="#discover">✦ Discover Bible Stories</a>
        </div>
    </div>
    <div class="hero-note">“Your word is a lamp to my feet.” — Psalm 119:105</div>
</section>

<div class="page-wrap">

<?php if ($error): ?>
    <section class="panel">
        <div class="panel-head"><h2>Something went wrong</h2></div>
        <div class="panel-body">
            <div class="error"><?= e($error) ?></div>
            <p>Please check your internet connection and API configuration, then try again.</p>
            <a class="small-btn" href="index.php">Return Home</a>
        </div>
    </section>

<?php elseif ($search !== ''): ?>
    <section class="panel">
        <div class="panel-head"><h2>🔎 Bible Keyword Search</h2></div>
        <div class="panel-body">
            <div class="search-summary">
                <strong>Search keyword:</strong> <?= e($search) ?><br>
                <strong>Translation:</strong> <?= e($version) ?><br>
                <strong>Matching verses:</strong> <?= (int)$searchTotal ?>
                <div style="color:#777;font-size:13px;margin-top:5px;">Showing up to <?= (int)$limit ?> results.</div>
            </div>

            <?php if (!empty($searchResults)): ?>
                <?php foreach ($searchResults as $result): ?>
                    <?php
                    $bookName = $result['book_name'] ?? $result['book'] ?? 'Bible';
                    $chapterNo = $result['chapter'] ?? '';
                    $verseNo = $result['verse'] ?? '';
                    $verseText = $result['text'] ?? '';
                    ?>
                    <article class="result-card">
                        <div class="result-reference">📖 <?= e($bookName) ?> <?= e($chapterNo) ?>:<?= e($verseNo) ?></div>
                        <div class="result-text"><?= nl2br(e($verseText)) ?></div>
                        <div class="result-translation">Translation: <?= e($version) ?></div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">
                    <h2>No matching verses found</h2>
                    <p>Try another keyword, such as love, faith, hope, peace, or prayer.</p>
                </div>
            <?php endif; ?>
            <a class="small-btn" href="index.php">← Back to Home</a>
        </div>
    </section>

<?php elseif ($bookId !== '' && $currentBook && $chapter !== ''): ?>
    <section class="panel">
        <div class="panel-head">
            <h2>📖 <?= e($currentBook['name']) ?> — Chapter <?= (int)$chapter ?></h2>
            <span><?= e($version) ?></span>
        </div>
        <div class="panel-body">
            <?php foreach ($verses as $verse): ?>
                <p class="verse"><strong><?= e($verse['verse'] ?? '') ?></strong><?= e(trim($verse['text'] ?? '')) ?></p>
            <?php endforeach; ?>
            <div class="button-row">
                <a class="small-btn" href="index.php?book=<?= urlencode($bookId) ?>&version=<?= urlencode($version) ?>">← Chapters</a>
                <button class="small-btn" onclick="window.print()">Print Chapter</button>
            </div>
        </div>
    </section>

<?php elseif ($bookId !== '' && $currentBook): ?>
    <section class="panel">
        <div class="panel-head"><h2>📖 <?= e($currentBook['name']) ?> — Select a Chapter</h2></div>
        <div class="panel-body">
            <div class="chapter-grid">
                <?php foreach ($chapters as $item): ?>
                    <?php $number = chapterNumber($item); ?>
                    <?php if ($number > 0): ?>
                        <a class="chapter-card" href="index.php?book=<?= urlencode($bookId) ?>&chapter=<?= $number ?>&version=<?= urlencode($version) ?>"><?= $number ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php else: ?>

    <section class="section-heading" id="daily">
        <div><h2>Today's Scripture</h2><p>A moment to pause and reflect.</p></div>
    </section>

    <section class="panel">
        <div class="panel-body">
            <div class="daily-grid">
                <div class="daily-art"><span>Be still, reflect, and let the Word guide your day.</span></div>
                <div class="daily-copy">
                    <div class="ref"><?= e($dailyReference) ?> · <?= e($version) ?></div>
                    <blockquote><?= e(trim($dailyText)) ?></blockquote>
                    <div class="translation">VERSE OF THE DAY</div>
                    <div class="button-row">
                        <a class="small-btn" href="index.php?book=PSA&chapter=118&version=<?= urlencode($version) ?>">Read Chapter</a>
                        <button class="small-btn" onclick="copyDailyVerse()">Copy Verse</button>
                        <button class="small-btn" onclick="shareDailyVerse()">Share Verse</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-heading">
        <div><h2>Explore by Topic</h2><p>Choose a topic to search the Bible.</p></div>
    </section>
    <section class="panel">
        <div class="panel-body">
            <div class="topic-list">
                <?php
                $topics = [
                    '❤️ Love' => 'love',
                    '🙏 Faith' => 'faith',
                    '🕊️ Peace' => 'peace',
                    '🌅 Hope' => 'hope',
                    '💛 Forgiveness' => 'forgiveness',
                    '💪 Strength' => 'strength',
                    '✨ Prayer' => 'prayer',
                    '🌿 Wisdom' => 'wisdom'
                ];
                foreach ($topics as $label => $keyword):
                ?>
                    <a class="topic-chip" href="index.php?search=<?= urlencode($keyword) ?>&version=<?= urlencode($version) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section-heading" id="discover">
        <div><h2>Discover Bible Stories</h2><p>Select an image to explore a Bible story.</p></div>
    </section>

    <section class="scene-grid" aria-label="Interactive Bible story gallery">
        <button class="scene-card" type="button" data-title="Noah's Ark" data-reference="Genesis 6–9" data-description="Explore the story of Noah, the ark, the flood, and God's covenant. Read Genesis chapters 6 to 9 to discover the full account.">
            <img src="https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=900&q=85" alt="Ocean and waves">
            <span class="scene-info"><small>Old Testament</small><strong>Noah's Ark</strong><span>Genesis 6–9 · Explore story →</span></span>
        </button>

        <button class="scene-card" type="button" data-title="Moses and the Red Sea" data-reference="Exodus 14" data-description="Read how Moses led the Israelites through the sea and how the people experienced deliverance from Egypt.">
            <img src="https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=900&q=85" alt="Desert landscape">
            <span class="scene-info"><small>Old Testament</small><strong>Moses & the Sea</strong><span>Exodus 14 · Explore story →</span></span>
        </button>

        <button class="scene-card" type="button" data-title="The Birth of Jesus" data-reference="Luke 2:1–20" data-description="Discover the nativity story, the announcement to the shepherds, and the birth of Jesus in Bethlehem.">
            <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=900&q=85" alt="Starry night sky">
            <span class="scene-info"><small>New Testament</small><strong>The Birth of Jesus</strong><span>Luke 2 · Explore story →</span></span>
        </button>

        <button class="scene-card" type="button" data-title="The Good Shepherd" data-reference="John 10:1–18" data-description="Jesus describes himself as the Good Shepherd who knows his sheep and cares for them. Read John 10 to explore this teaching.">
            <img src="https://images.unsplash.com/photo-1472396961693-142e6e269027?auto=format&fit=crop&w=900&q=85" alt="Sheep in a green field">
            <span class="scene-info"><small>Jesus' teachings</small><strong>The Good Shepherd</strong><span>John 10 · Explore story →</span></span>
        </button>
    </section>

    <section class="section-heading" id="books">
        <div><h2>Books of the Bible</h2><p>Choose a book and start reading.</p></div>
    </section>
    <section class="panel">
        <div class="panel-head">
            <h2>📚 Browse Scripture</h2>
            <form method="GET" action="index.php">
                <select name="version" onchange="this.form.submit()" style="padding:9px;border:1px solid #dfe5dc;border-radius:8px;">
                    <option value="KJV" <?= $version === 'KJV' ? 'selected' : '' ?>>KJV</option>
                    <option value="WEB" <?= $version === 'WEB' ? 'selected' : '' ?>>WEB</option>
                    <option value="BSB" <?= $version === 'BSB' ? 'selected' : '' ?>>BSB</option>
                </select>
            </form>
        </div>
        <div class="panel-body">
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <a class="book-card" href="index.php?book=<?= urlencode($book['id']) ?>&version=<?= urlencode($version) ?>">
                        <strong><?= e($book['name']) ?></strong>
                        <small>Open book →</small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

</div>

<footer>
    <strong>BIBLE PORTAL</strong>
    <span style="font-size:12px;opacity:.75;">Scripture data powered by Bible API · Translation: <?= e($version) ?></span>
</footer>

<!-- Bible story image modal -->
<div class="modal" id="storyModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
        <button class="close-modal" type="button" aria-label="Close" onclick="closeStoryModal()">×</button>
        <img id="modalImage" src="" alt="">
        <div class="modal-content">
            <h2 id="modalTitle"></h2>
            <div class="ref" id="modalReference"></div>
            <p id="modalDescription"></p>
            <a id="modalReadLink" class="small-btn" href="#">Read this passage →</a>
        </div>
    </div>
</div>

<script>
const dailyVerseText = <?= json_encode(trim($dailyText) . ' — ' . $dailyReference, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

async function copyDailyVerse() {
    try {
        await navigator.clipboard.writeText(dailyVerseText);
        alert('Verse copied!');
    } catch (error) {
        alert('Copy is unavailable in this browser. You can select and copy the verse manually.');
    }
}

async function shareDailyVerse() {
    if (navigator.share) {
        try {
            await navigator.share({ title: 'Bible Portal - Verse of the Day', text: dailyVerseText });
        } catch (error) {
            // User cancelled sharing; no action needed.
        }
    } else {
        copyDailyVerse();
    }
}

const storyModal = document.getElementById('storyModal');
const modalImage = document.getElementById('modalImage');

document.querySelectorAll('.scene-card').forEach(card => {
    card.addEventListener('click', () => {
        const image = card.querySelector('img');
        const title = card.dataset.title;
        const reference = card.dataset.reference;

        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalReference').textContent = reference;
        document.getElementById('modalDescription').textContent = card.dataset.description;
        modalImage.src = image.src;
        modalImage.alt = image.alt;

        // Open the first passage reference as a starting point.
        const firstReference = reference.split(/[–-]/)[0].trim();
        document.getElementById('modalReadLink').href =
            'https://www.biblegateway.com/passage/?search=' +
            encodeURIComponent(firstReference);

        storyModal.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
});

function closeStoryModal() {
    storyModal.classList.remove('open');
    document.body.style.overflow = '';
}

storyModal.addEventListener('click', event => {
    if (event.target === storyModal) closeStoryModal();
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeStoryModal();
});
</script>

</body>
</html>
