<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Food101 CLIP Tester</title>
  <style>
    :root { --accent:#AAFF00; --accent-dim:#1C2210; --bg:#111; --surface:#1a1a1a; --border:#2a2a2a; --muted:#555; --text:#fff; }
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;min-height:100vh;display:flex;justify-content:center;padding:40px 20px;}
    .container{width:100%;max-width:500px;}
    h1{font-size:22px;font-weight:800;margin-bottom:6px;}
    h1 span{color:var(--accent);}
    .subtitle{font-size:13px;color:var(--muted);margin-bottom:24px;}
    .model-info{background:var(--surface);border-radius:12px;padding:12px 14px;font-size:12px;color:var(--muted);margin-bottom:16px;line-height:1.7;}
    .model-info a{color:var(--accent);text-decoration:none;}
    .model-info span{color:var(--accent);}
    .upload-area{background:var(--surface);border:2px dashed var(--border);border-radius:16px;height:200px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;cursor:pointer;margin-bottom:16px;overflow:hidden;transition:border-color .2s;}
    .upload-area:hover{border-color:var(--accent);}
    .upload-area img{width:100%;height:100%;object-fit:cover;}
    .upload-area span{font-size:12px;color:#444;}
    input[type="file"]{display:none;}
    .row{display:flex;gap:10px;margin-bottom:12px;}
    .input-field{flex:1;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:13px 14px;color:var(--text);font-size:13px;outline:none;}
    .input-field:focus{border-color:var(--accent);}
    .input-field::placeholder{color:#444;}
    .note{font-size:11px;color:#444;margin-top:-8px;margin-bottom:14px;line-height:1.5;}
    .btn{width:100%;background:var(--accent);color:#111;font-size:15px;font-weight:800;border:none;border-radius:14px;padding:16px;cursor:pointer;margin-bottom:20px;}
    .btn:disabled{background:var(--border);color:var(--muted);cursor:not-allowed;}
    .loading{display:none;text-align:center;color:var(--muted);font-size:13px;margin-bottom:16px;}
    .loading.show{display:block;}
    .result{display:none;background:var(--surface);border-radius:16px;overflow:hidden;margin-bottom:20px;}
    .result.show{display:block;}
    .result-header{background:var(--accent-dim);padding:14px 16px;font-size:11px;color:var(--accent);letter-spacing:1.5px;text-transform:uppercase;}
    .result-item{display:flex;align-items:center;padding:12px 16px;border-bottom:1px solid #222;gap:10px;}
    .result-item:last-child{border-bottom:none;}
    .result-label{font-size:14px;color:var(--text);font-weight:600;min-width:140px;}
    .bar-wrap{flex:1;background:#222;border-radius:4px;height:6px;overflow:hidden;}
    .bar-fill{height:100%;background:var(--accent);border-radius:4px;}
    .result-score{font-size:13px;color:var(--accent);font-weight:700;min-width:36px;text-align:right;}
    .error{display:none;background:#2a1a1a;border:1px solid #5a2a2a;border-radius:12px;padding:14px;color:#ff6b6b;font-size:13px;margin-bottom:16px;line-height:1.5;}
    .error.show{display:block;}
  </style>
</head>
<body>
<div class="container">
  <h1>Food<span>101</span> Classifier</h1>
  <p class="subtitle">Free food image recognition via Imagga API</p>

  <div class="model-info">
    Uses <span>Imagga</span> image tagging — free tier, no credit card needed.<br>
    Sign up at <a href="https://imagga.com" target="_blank">imagga.com</a> → Dashboard → grab your <span>API Key</span> + <span>API Secret</span>.
  </div>

  <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5">
      <rect x="3" y="3" width="18" height="18" rx="3"/>
      <circle cx="8.5" cy="8.5" r="1.5"/>
      <path d="M21 15l-5-5L5 21"/>
    </svg>
    <span>Tap to upload food photo</span>
  </div>
  <input type="file" id="fileInput" accept="image/*" onchange="previewImage(event)">

  <div class="row">
    <input type="text" class="input-field" id="apiKey" placeholder="Imagga API Key">
    <input type="text" class="input-field" id="apiSecret" placeholder="Imagga API Secret">
  </div>
  <p class="note">Find both in your Imagga dashboard after signing up (free, no card). Never stored.</p>

  <button class="btn" id="analyzeBtn" onclick="analyze()" disabled>Analyze Food</button>

  <div class="loading" id="loading">⏳ Sending to Imagga...</div>
  <div class="error" id="errorBox"></div>

  <div class="result" id="result">
    <div class="result-header">Top Tags</div>
    <div id="resultItems"></div>
  </div>
</div>

<script>
  var imageFile = null;

  function previewImage(event) {
    var file = event.target.files[0];
    if (!file) return;
    imageFile = file;
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById("uploadArea").innerHTML = '<img src="' + e.target.result + '">';
      document.getElementById("analyzeBtn").disabled = false;
    };
    reader.readAsDataURL(file);
  }

  // Resize image to max 800px on longest side, JPEG quality 0.85
  function resizeImage(file, maxPx, quality) {
    return new Promise(function(resolve) {
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function() {
        var w = img.width, h = img.height;
        if (w > maxPx || h > maxPx) {
          if (w > h) { h = Math.round(h * maxPx / w); w = maxPx; }
          else       { w = Math.round(w * maxPx / h); h = maxPx; }
        }
        var canvas = document.createElement("canvas");
        canvas.width = w; canvas.height = h;
        canvas.getContext("2d").drawImage(img, 0, 0, w, h);
        URL.revokeObjectURL(url);
        canvas.toBlob(resolve, "image/jpeg", quality);
      };
      img.src = url;
    });
  }

  async function analyze() {
    var key    = document.getElementById("apiKey").value.trim();
    var secret = document.getElementById("apiSecret").value.trim();
    if (!key || !secret) { showError("Please enter both your Imagga API Key and API Secret."); return; }
    if (!imageFile)      { showError("Please upload a food image."); return; }

    document.getElementById("loading").classList.add("show");
    document.getElementById("result").classList.remove("show");
    document.getElementById("errorBox").classList.remove("show");
    document.getElementById("analyzeBtn").disabled = true;

    try {
      // Compress before sending — fixes the timeout
      var blob = await resizeImage(imageFile, 800, 0.85);

      var formData = new FormData();
      formData.append("image", blob, "food.jpg");

      var credentials = btoa(key + ":" + secret);

      var response = await fetch("https://api.imagga.com/v2/tags", {
        method: "POST",
        headers: { "Authorization": "Basic " + credentials },
        body: formData
      });

      var data = await response.json();

      if (!response.ok || (data.status && data.status.type === "error")) {
        var msg = (data.status && data.status.text) || (data.error && data.error.message) || ("API error " + response.status);
        throw new Error(msg);
      }

      var tags = data.result && data.result.tags;
      if (!tags || tags.length === 0) throw new Error("No tags returned for this image.");

      var topScore = tags[0].confidence;
      var html = "";
      tags.slice(0, 7).forEach(function(tag) {
        var pct   = Math.round(tag.confidence);
        var width = Math.round((tag.confidence / topScore) * 100);
        var label = (tag.tag.en || tag.tag);
        label = label.charAt(0).toUpperCase() + label.slice(1);
        html += '<div class="result-item">' +
          '<div class="result-label">' + label + '</div>' +
          '<div class="bar-wrap"><div class="bar-fill" style="width:' + width + '%"></div></div>' +
          '<div class="result-score">' + pct + '%</div>' +
        '</div>';
      });

      document.getElementById("resultItems").innerHTML = html;
      document.getElementById("result").classList.add("show");

    } catch(e) {
      showError("Error: " + e.message);
    } finally {
      document.getElementById("loading").classList.remove("show");
      document.getElementById("analyzeBtn").disabled = false;
    }
  }

  function showError(msg) {
    var box = document.getElementById("errorBox");
    box.innerText = msg;
    box.classList.add("show");
    document.getElementById("loading").classList.remove("show");
    document.getElementById("analyzeBtn").disabled = false;
  }
</script>
</body>
</html>