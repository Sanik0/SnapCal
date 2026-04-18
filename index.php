<?php
$result = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {

  $apiKey = $_POST['apiKey'] ?? '';
  $image = file_get_contents($_FILES['image']['tmp_name']);

  $ch = curl_init();

  // ✅ Use a food-specific model
  curl_setopt($ch, CURLOPT_URL, "https://router.huggingface.co/hf-inference/models/nateraw/food");
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $headers = ["Content-Type: application/octet-stream"];
  if (!empty($apiKey)) {
    $headers[] = "Authorization: Bearer $apiKey";
  }

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $image);

  $response = curl_exec($ch);

  if (curl_errno($ch)) {
    $result = "cURL Error: " . curl_error($ch);
  } else {
    $decoded = json_decode($response, true);
    if ($decoded === null) {
      $result = "RAW RESPONSE:\n" . $response;
    } else {
      $result = json_encode($decoded, JSON_PRETTY_PRINT);
    }
  }

  curl_close($ch);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Food Analyzer</title>
  <style>
    body {
      background: #0a0a0a;
      color: white;
      font-family: Arial;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }

    .container {
      background: #111;
      padding: 20px;
      border-radius: 12px;
      width: 350px;
      border: 1px solid #333;
    }

    input,
    button {
      width: 100%;
      margin-top: 10px;
      padding: 10px;
      border-radius: 8px;
      border: none;
      box-sizing: border-box;
    }

    input {
      background: #222;
      color: white;
    }

    button {
      background: orange;
      font-weight: bold;
      cursor: pointer;
    }

    img {
      width: 100%;
      margin-top: 10px;
      border-radius: 10px;
    }

    .result {
      margin-top: 15px;
      font-size: 13px;
      color: #ccc;
      white-space: pre-wrap;
      background: #000;
      padding: 10px;
      border-radius: 8px;
    }

    .label {
      color: #f90;
      font-weight: bold;
    }

    .score {
      color: #aaa;
    }
  </style>
</head>

<body>

  <div class="container">
    <h2>🍔 Food Analyzer</h2>

    <form method="POST" enctype="multipart/form-data">
      <input type="text" name="apiKey" placeholder="HuggingFace API Key (optional)">
      <input type="file" name="image" accept="image/*" required>
      <button type="submit">Analyze</button>
    </form>

    <?php if (!empty($_FILES['image']['tmp_name'])): ?>
      <img src="data:image/jpeg;base64,<?= base64_encode(file_get_contents($_FILES['image']['tmp_name'])) ?>">
    <?php endif; ?>

    <?php if ($result): ?>
      <?php $decoded = json_decode($result, true); ?>
      <?php if (is_array($decoded) && isset($decoded[0]['label'])): ?>
        <div class="result">
          <?php foreach (array_slice($decoded, 0, 5) as $item): ?>
            <div>
              <span class="label"><?= htmlspecialchars($item['label']) ?></span>
              <span class="score"> — <?= round($item['score'] * 100, 1) ?>%</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="result"><?= htmlspecialchars($result) ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</body>

</html>