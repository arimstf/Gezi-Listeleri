<?php
// Ayarlar dosyasının yolu
$settingsFile = 'settings.json'; // Güvenli bir yere taşıyabilirsiniz: '../config/settings.json'

// POST isteği ile ayarlar kaydediliyorsa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => 'Ayarlar kaydedilirken bir hata oluştu.'];

    // JSON payload'ını al
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        // Gelen verileri temizle ve varsayılan değerlerle birleştir
        $settings = [
            'sheetyLink'           => filter_var($data['sheetyLink'] ?? '', FILTER_SANITIZE_URL),
            'pageTitle'            => htmlspecialchars($data['pageTitle'] ?? ''),
            'asilCount'            => (int)($data['asilCount'] ?? 0),
            'totalParticipantsToShow' => (int)($data['totalParticipantsToShow'] ?? 0),
            'explanation'          => htmlspecialchars($data['explanation'] ?? '')
        ];

        // Ayarları JSON dosyasına kaydet
        // JSON_UNESCAPED_SLASHES bayrağı, URL'deki eğik çizgilerin ters eğik çizgi ile kaçırılmamasını sağlar.
        if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            $response = ['status' => 'success', 'message' => 'Ayarlar başarıyla kaydedildi.'];
        } else {
            $response['message'] = 'Dosyaya yazma hatası. settings.json dosyasının yazma izinlerini kontrol edin.';
        }
    } else {
        $response['message'] = 'Geçersiz JSON verisi alındı.';
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // PHP betiğini sonlandır
}

// Mevcut ayarları yükle (GET isteği veya sayfa yüklendiğinde)
$currentSettings = [];
if (file_exists($settingsFile)) {
    $currentSettings = json_decode(file_get_contents($settingsFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $currentSettings = []; // Hatalı JSON varsa sıfırla
    }
}

// Varsayılan ayarlar (eğer dosya boşsa veya yoksa kullanılacak)
$defaultSettings = [
    'sheetyLink'            => '',
    'pageTitle'             => '',
    'asilCount'             => 20,
    'totalParticipantsToShow' => 30,
    'explanation'           => ''
];

// Mevcut ayarları varsayılanlarla birleştirerek form alanlarını doldur
$settingsToDisplay = array_merge($defaultSettings, $currentSettings);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/x-icon" href="favicon.png">
  <title>Gezi Listesi Yönetim Paneli</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 1rem;
      color: #333;
    }

    .panel-container {
      max-width: 700px;
      margin: 2rem auto;
      background: #ffffff;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }

    .panel-container h2 {
        margin-top: 0;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.8rem;
        margin-bottom: 1.5rem;
    }

    .panel-container label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
      color: #555;
    }

    .panel-container input[type="text"],
    .panel-container input[type="url"],
    .panel-container input[type="number"],
    .panel-container textarea {
      width: calc(100% - 20px);
      padding: 10px;
      margin-bottom: 1rem;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1rem;
    }

    .panel-container textarea {
      min-height: 100px;
      resize: vertical;
    }

    .panel-container button {
      background-color: #28a745;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.2s ease;
      float: right;
    }

    .panel-container button:hover {
      background-color: #218838;
    }

    .panel-container::after {
      content: "";
      display: table;
      clear: both;
    }

    #message {
        display: none;
    }
  </style>
</head>
<body>

  <div class="panel-container" id="adminPanel">
    <h2>Gezi Listesi Yönetim Paneli Ayarları</h2>
    <div>
      <label for="panelSheetyLink">Sheety/Sheetbest API Linki:</label>
      <input type="url" id="panelSheetyLink" placeholder="API linkini girin (örn: https://api.sheetbest.com/...)" value="<?= htmlspecialchars($settingsToDisplay['sheetyLink'] ?? '') ?>">
    </div>
    <div>
      <label for="panelPageTitle">Sayfa Başlığı:</label>
      <input type="text" id="panelPageTitle" placeholder="Sayfa başlığını girin" value="<?= htmlspecialchars($settingsToDisplay['pageTitle'] ?? '') ?>">
    </div>
    <div>
      <label for="panelAsilCount">Asil Kişi Sayısı:</label>
      <input type="number" id="panelAsilCount" value="<?= (int)($settingsToDisplay['asilCount'] ?? 20) ?>" min="0">
    </div>
    <div>
      <label for="panelTotalParticipants">Gösterilecek Toplam Kişi Sayısı:</label>
      <input type="number" id="panelTotalParticipants" value="<?= (int)($settingsToDisplay['totalParticipantsToShow'] ?? 30) ?>" min="0">
    </div>
    <div>
      <label for="panelExplanation">Açıklama Metni:</label>
      <textarea id="panelExplanation" placeholder="Açıklama metnini buraya girin"><?= htmlspecialchars($settingsToDisplay['explanation'] ?? '') ?></textarea>
    </div>
    <button id="saveSettings" class="save-button">Ayarları Kaydet</button>
    <div id="message"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const panelSheetyLink = document.getElementById("panelSheetyLink");
    const panelPageTitle = document.getElementById("panelPageTitle");
    const panelAsilCount = document.getElementById("panelAsilCount");
    const panelTotalParticipants = document.getElementById("panelTotalParticipants");
    const panelExplanation = document.getElementById("panelExplanation");
    const saveSettingsButton = document.getElementById("saveSettings");

    function saveSettings() {
        const settingsToSave = {
            sheetyLink: panelSheetyLink.value,
            pageTitle: panelPageTitle.value,
            asilCount: parseInt(panelAsilCount.value) || 0,
            totalParticipantsToShow: parseInt(panelTotalParticipants.value) || 0,
            explanation: panelExplanation.value
        };

        fetch('yonetim.php', { // Kendi kendine POST isteği yapacak
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(settingsToSave),
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    position: "center",
                    icon: "success",
                    title: "Ayarlar başarıyla kaydedildi",
                    html: "Sayfa yenileniyor...", // Mesajı daha uygun hale getirebiliriz
                    showConfirmButton: false,
                    timer: 1500,
                }).then(() => { // SweetAlert kapandıktan sonra çalışacak
                    location.reload(); // Sayfayı yeniden yükle
                });
            } else {
                Swal.fire({
                    position: "center",
                    icon: "error",
                    title: "Hata!",
                    text: data.message || "Ayarlar kaydedilirken bir sorun oluştu. Konsolu kontrol edin.",
                    showConfirmButton: true,
                });
                console.error("Ayarlar kaydedilirken hata oluştu:", data.message);
            }
        })
        .catch(error => {
            Swal.fire({
                position: "center",
                icon: "error",
                title: "İstek Hatası!",
                text: "Sunucuya bağlanırken veya yanıt işlenirken bir sorun oluştu. Konsolu kontrol edin.",
                showConfirmButton: true,
            });
            console.error("Fetch hatası:", error);
        });
    }

    saveSettingsButton.addEventListener("click", saveSettings);

  </script>

</body>
</html>