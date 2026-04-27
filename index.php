<?php
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $apiKey = trim($_POST['api_key'] ?? '');
  $foodDesc = trim($_POST['food_description'] ?? '');

  if (!$apiKey || !$foodDesc) {
    $error = 'API key and food description are required.';
  } else {
    $prompt = 'You are a nutrition expert. The user will describe their food or meal. Estimate the macronutrients as accurately as possible. Return ONLY valid JSON with no markdown, no backticks, no extra text. Use this exact structure: {"food_name":"Name of the dish","description":"Brief 1-sentence description","protein_g":25,"carbs_g":45,"fats_g":12,"other_g":8,"calories_kcal":390,"notes":"Any relevant notes about estimation accuracy or assumptions"}';

    $payload = json_encode([
      'model' => 'deepseek-chat',
      'max_tokens' => 500,
      'messages' => [
        ['role' => 'system', 'content' => $prompt],
        ['role' => 'user', 'content' => $foodDesc]
      ]
    ]);

    $ch = curl_init('https://api.deepseek.com/chat/completions');
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$apiKey}"
      ],
      CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
      $error = 'Failed to connect to DeepSeek API. Check your internet connection.';
    } else {
      $data = json_decode($response, true);
      if ($httpCode !== 200) {
        $error = $data['error']['message'] ?? "API error (HTTP {$httpCode})";
      } else {
        $raw = $data['choices'][0]['message']['content'] ?? '';
        $clean = trim(preg_replace('/```json|```/', '', $raw));
        $result = json_decode($clean, true);
        if (!$result) {
          $error = 'Could not parse AI response. Try again.';
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Macro Analyzer</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #0e0e0f;
      --surface: #161618;
      --surface2: #1e1e21;
      --border: rgba(255, 255, 255, 0.08);
      --border2: rgba(255, 255, 255, 0.14);
      --text: #f0efe8;
      --muted: #888784;
      --accent: #c8f064;
      --accent-dim: rgba(200, 240, 100, 0.12);
      --protein: #378ADD;
      --carbs: #EF9F27;
      --fats: #D85A30;
      --other: #888784;
      --danger: #f07070;
      --radius: 12px;
      --radius-sm: 8px;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      padding: 3rem 1rem 4rem;
    }

    .container {
      width: 100%;
      max-width: 520px;
    }

    .header {
      margin-bottom: 2.5rem;
    }

    .logo {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--accent);
      letter-spacing: 0.12em;
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    h1 {
      font-size: 32px;
      font-weight: 300;
      letter-spacing: -0.02em;
      line-height: 1.15;
    }

    h1 span {
      font-weight: 500;
    }

    .tagline {
      font-size: 13px;
      color: var(--muted);
      margin-top: 8px;
    }

    .card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
      margin-bottom: 1rem;
    }

    .field-label {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .toggle-key {
      background: none;
      border: none;
      color: var(--accent);
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      cursor: pointer;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      background: var(--surface2);
      border: 0.5px solid var(--border2);
      border-radius: var(--radius-sm);
      color: var(--text);
      font-family: 'DM Mono', monospace;
      font-size: 13px;
      padding: 10px 12px;
      outline: none;
      transition: border-color 0.15s;
    }

    input:focus {
      border-color: var(--accent);
    }

    textarea {
      width: 100%;
      background: var(--surface2);
      border: 0.5px solid var(--border2);
      border-radius: var(--radius-sm);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      padding: 10px 12px;
      outline: none;
      transition: border-color 0.15s;
      resize: vertical;
      min-height: 100px;
      line-height: 1.6;
    }

    textarea:focus {
      border-color: var(--accent);
    }

    textarea::placeholder {
      color: var(--muted);
    }

    .submit-btn {
      width: 100%;
      background: var(--accent);
      color: #0e0e0f;
      border: none;
      border-radius: var(--radius-sm);
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
      font-weight: 500;
      padding: 12px;
      cursor: pointer;
      margin-top: 0.5rem;
      transition: opacity 0.15s, transform 0.1s;
      letter-spacing: 0.01em;
    }

    .submit-btn:hover {
      opacity: 0.88;
    }

    .submit-btn:active {
      transform: scale(0.99);
    }

    .error-box {
      background: rgba(240, 112, 112, 0.1);
      border: 0.5px solid rgba(240, 112, 112, 0.3);
      border-radius: var(--radius-sm);
      color: var(--danger);
      font-size: 13px;
      padding: 10px 14px;
      margin-top: 0.75rem;
    }

    .results-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      margin-top: 1.5rem;
    }

    .result-header {
      padding: 1.25rem;
      border-bottom: 0.5px solid var(--border);
    }

    .food-name {
      font-size: 20px;
      font-weight: 500;
      margin-bottom: 4px;
    }

    .food-desc {
      font-size: 13px;
      color: var(--muted);
      line-height: 1.5;
    }

    .macro-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
    }

    .macro-cell {
      padding: 1.1rem 1.25rem;
      border-right: 0.5px solid var(--border);
      border-bottom: 0.5px solid var(--border);
    }

    .macro-cell:nth-child(2n) {
      border-right: none;
    }

    .macro-cell:nth-child(3),
    .macro-cell:nth-child(4) {
      border-bottom: none;
    }

    .macro-tag {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .macro-tag.protein { color: var(--protein); }
    .macro-tag.carbs   { color: var(--carbs); }
    .macro-tag.fats    { color: var(--fats); }
    .macro-tag.other   { color: var(--other); }

    .macro-num {
      font-size: 30px;
      font-weight: 300;
      letter-spacing: -0.02em;
    }

    .macro-num span {
      font-size: 14px;
      color: var(--muted);
      font-weight: 400;
    }

    .macro-bar-wrap {
      height: 3px;
      background: var(--surface2);
      border-radius: 2px;
      margin-top: 10px;
      overflow: hidden;
    }

    .macro-bar-fill {
      height: 100%;
      border-radius: 2px;
    }

    .calories-row {
      padding: 1rem 1.25rem;
      border-top: 0.5px solid var(--border);
      display: flex;
      align-items: baseline;
      gap: 10px;
    }

    .cal-label {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .cal-num {
      font-size: 26px;
      font-weight: 300;
      color: var(--accent);
    }

    .cal-unit {
      font-size: 12px;
      color: var(--muted);
    }

    .notes-row {
      padding: 1rem 1.25rem;
      border-top: 0.5px solid var(--border);
      font-size: 12px;
      color: var(--muted);
      line-height: 1.6;
    }

    .footer {
      text-align: center;
      margin-top: 2.5rem;
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      color: var(--muted);
      letter-spacing: 0.06em;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="header">
      <div class="logo">&#9632; DeepSeek</div>
      <h1>Macro <span>Analyzer</span></h1>
      <p class="tagline">Describe your meal. Get protein, carbs, fats & calories.</p>
    </div>

    <form method="POST" id="mainForm">
      <div class="card">
        <div class="field-label">
          DeepSeek API Key
          <button type="button" class="toggle-key" id="toggleKey">show</button>
        </div>
        <input type="password" name="api_key" id="apiKey"
          placeholder="sk-..."
          value="<?= htmlspecialchars($_POST['api_key'] ?? '') ?>"
          required />
      </div>

      <div class="card">
        <div class="field-label">Describe Your Food</div>
        <textarea
          name="food_description"
          placeholder="e.g. 1 cup white rice, 2 fried eggs cooked in 1 tbsp butter, side of steamed broccoli..."
          required
        ><?= htmlspecialchars($_POST['food_description'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="submit-btn">Analyze Macros</button>
    </form>

    <?php if ($error): ?>
      <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
      <?php
      $p = round($result['protein_g'] ?? 0);
      $c = round($result['carbs_g'] ?? 0);
      $f = round($result['fats_g'] ?? 0);
      $o = round($result['other_g'] ?? 0);
      $total = max($p + $c + $f + $o, 1);
      ?>
      <div class="results-card">
        <div class="result-header">
          <div class="food-name"><?= htmlspecialchars($result['food_name'] ?? 'Unknown food') ?></div>
          <div class="food-desc"><?= htmlspecialchars($result['description'] ?? '') ?></div>
        </div>
        <div class="macro-grid">
          <div class="macro-cell">
            <div class="macro-tag protein">Protein</div>
            <div class="macro-num"><?= $p ?><span> g</span></div>
            <div class="macro-bar-wrap">
              <div class="macro-bar-fill" style="width:<?= round($p / $total * 100) ?>%; background:var(--protein);"></div>
            </div>
          </div>
          <div class="macro-cell">
            <div class="macro-tag carbs">Carbs</div>
            <div class="macro-num"><?= $c ?><span> g</span></div>
            <div class="macro-bar-wrap">
              <div class="macro-bar-fill" style="width:<?= round($c / $total * 100) ?>%; background:var(--carbs);"></div>
            </div>
          </div>
          <div class="macro-cell">
            <div class="macro-tag fats">Fats</div>
            <div class="macro-num"><?= $f ?><span> g</span></div>
            <div class="macro-bar-wrap">
              <div class="macro-bar-fill" style="width:<?= round($f / $total * 100) ?>%; background:var(--fats);"></div>
            </div>
          </div>
          <div class="macro-cell">
            <div class="macro-tag other">Other</div>
            <div class="macro-num"><?= $o ?><span> g</span></div>
            <div class="macro-bar-wrap">
              <div class="macro-bar-fill" style="width:<?= round($o / $total * 100) ?>%; background:var(--other);"></div>
            </div>
          </div>
        </div>
        <div class="calories-row">
          <span class="cal-label">Est. Calories</span>
          <span class="cal-num"><?= round($result['calories_kcal'] ?? 0) ?></span>
          <span class="cal-unit">kcal</span>
        </div>
        <?php if (!empty($result['notes'])): ?>
          <div class="notes-row"><?= htmlspecialchars($result['notes']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="footer">macro-analyzer &middot; powered by deepseek</div>
  </div>

  <script>
    const toggleKey = document.getElementById('toggleKey');
    const apiKey = document.getElementById('apiKey');

    toggleKey.addEventListener('click', () => {
      const show = apiKey.type === 'password';
      apiKey.type = show ? 'text' : 'password';
      toggleKey.textContent = show ? 'hide' : 'show';
    });
  </script>
</body>

</html>