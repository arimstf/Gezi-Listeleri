<?php
// Ayarlar dosyasının yolu
$settingsFile = 'settings.json'; // Yönetim panelindeki ile aynı olmalı

// Varsayılan ayarlar
$defaultSettings = [
    'sheetyLink'           => '',
    'pageTitle'            => 'Katılımcı Listesi',
    'asilCount'            => 2,
    'totalParticipantsToShow' => 1,
    'explanation'          => 'Bu tablo resmi bir belge niteliği taşımaz. Sadece bilgilendirme amaçlıdır.',
    'logoUrl'              => 'logolar.png'
];

// Ayarları yükle
$settings = $defaultSettings;
if (file_exists($settingsFile)) {
    $loadedSettings = json_decode(file_get_contents($settingsFile), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($loadedSettings)) {
        $settings = array_merge($defaultSettings, $loadedSettings);
    }
}

$sheetyData = []; // Artık bu değişken Sheetbest verilerini de tutacak
$errorMessage = '';

// API linki ayarlanmışsa verileri çek
if (!empty($settings['sheetyLink'])) {
    // cURL kullanarak API isteği yapma
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $settings['sheetyLink']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Yanıtı string olarak al
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 saniye zaman aşımı

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $errorMessage = "API bağlantı hatası: " . curl_error($ch);
    } elseif ($httpCode !== 200) {
        $errorMessage = "HTTP hatası! Durum: " . $httpCode . " - Lütfen API linkinizi kontrol edin.";
    } else {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = "API yanıtı geçersiz JSON formatında. Yanıt yapısını kontrol edin.";
        } elseif (!is_array($data)) { // Yanıtın doğrudan bir dizi olup olmadığını kontrol et (Sheetbest için)
            $errorMessage = "API yanıtı beklenen liste (Array) formatında değil. Yanıt yapısını kontrol edin. (Sheety kullanıyorsanız ve sayfa adınız yoksa bu hatayı alabilirsiniz.)";
            // Sheety'den gelen yanıt objesini işlemek için alternatif kontrol (eski Sheety uyumluluğu için)
            if (is_array($data) && count($data) > 0 && isset(array_values($data)[0]) && is_array(array_values($data)[0])) {
                $sheetyData = array_values($data)[0]; // İlk objenin değerini al (Sheety'de sayfa adı anahtarının değeri)
                $errorMessage = ''; // Hata yok
            }
        } elseif (empty($data)) {
            $errorMessage = "API yanıtı boş veya listelenecek katılımcı bulunamadı.";
        }
        else {
            $sheetyData = $data; // Sheetbest doğrudan bir dizi döndürdüğü için $data'yı doğrudan kullan
        }
    }
    curl_close($ch);
} else {
    $errorMessage = "API linki ayarlanmamış. Yönetim panelinden ayarlayın.";
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
 <meta charset="UTF-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
 <link rel="icon" type="image/x-icon" href="favicon.png">
 <title><?= htmlspecialchars($settings['pageTitle']) ?></title>
 <style>
  html, body {
   height: 100%;
   margin: 0;
   padding: 0;
  }
  body {
   font-family: 'Segoe UI', sans-serif;
   background: #f4f6f9;
   padding: 1rem;
   color: #333;
   display: flex;
   flex-direction: column;
   min-height: 100vh;
   box-sizing: border-box;
  }
  h1 {
   text-align: center;
   color: #2c3e50;
  }
  table {
   width: 100%;
   border-collapse: collapse;
   margin-top: 1.5rem;
   background: white;
   box-shadow: 0 0 10px rgba(0,0,0,0.05);
  }
  th, td {
   padding: 0.8rem;
   text-align: left;
   border-bottom: 1px solid #eee;
  }
  th {
   background-color: #2c3e50;
   color: white;
  }
  .asil {
   background-color: #d4edda;
   color: #155724;
   font-weight: bold;
  }
  .yedek {
   background-color: #fff3cd;
   color: #856404;
   font-weight: bold;
  }
  #aciklama {
   margin-top: 2rem;
   padding: 1rem;
   background: #e9ecef;
   border-radius: 5px;
   color: #495057;
   text-align: center;
  }
  #logolar {
   display: block;
   margin: 0 auto 2rem auto;
   max-width: 400px;
   height: auto;
  }
  footer {
   margin-top: auto;
   padding: 10px;
   text-align: center;
   background-color: #34495e;
   color: #ecf0f1;
   font-size: 14px;
   border-top: 1px solid #2c3e50;
  }
  footer p {
   margin: 0;
  }
  #sayac {
   position: fixed;
   top: 10px;
   right: 10px;
   background-color: #2c3e50;
   color: white;
   padding: 3px 4px;
   border-radius: 5px;
   font-size: 14px;
   z-index: 1000;
   box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  }
  @media (max-width: 500px) {
    #sayac{
      padding: 2px 4px;
      font-size: 10px;
    }
   #logolar {
    max-width: 300px;
   }
  }
  @media (max-width: 200px) {
   table, thead, tbody, th, td, tr {
    display: block;
   }
   tr {
    margin-bottom: 1rem;
   }
   th {
    display: none;
   }
   td {
    padding: 0.5rem;
    border: none;
    position: relative;
   }
   td::before {
    content: attr(data-label);
    font-weight: bold;
    display: block;
    margin-bottom: 0.2rem;
   }
   #sayac {
    top: 5px;
    right: 5px;
    font-size: 12px;
    padding: 3px 6px;
   }
  }
 </style>
</head>
<body>
 
 <img id="logolar" src="<?= htmlspecialchars($settings['logoUrl']) ?>">
 
 <h1 id="mainTitle"><?= htmlspecialchars($settings['pageTitle']) ?></h1>
 
 <div id="sayac">Yenileniyor...</div>

 <table>
  <thead>
   <tr>
    <th>#</th>
    <th>Ad Soyad</th>
    <th>Durum</th>
   </tr>
  </thead>
  <tbody id="katilimciListesi">
    <?php if (!empty($errorMessage)): ?>
        <tr><td colspan='3'><?= $errorMessage ?></td></tr>
    <?php elseif (empty($sheetyData)): ?>
        <tr><td colspan='3'>Listelenecek katılımcı bulunamadı.</td></tr>
    <?php else: ?>
        <?php
        $counter = 0;
        foreach ($sheetyData as $index => $kisi):
            if ($counter >= $settings['totalParticipantsToShow']) break; // Toplam gösterilecek sayıyı aşma

            $durumMetni = '';
            $durumClass = '';

            if ($index < $settings['asilCount']) {
                $durumMetni = "Asil";
                $durumClass = "asil";
            } else {
                $durumMetni = ($index - $settings['asilCount'] + 1) . ".Yedek";
                $durumClass = "yedek";
            }

            // Kolon adlarını Sheetbest'ten gelen formatla uyumlu hale getirin.
            // Genellikle Sheetbest'te kolon adları e-tabloda göründüğü gibidir,
            // Sheety'deki gibi ':' karakteri olmayabilir.
            $kisiAdi = '-'; // Varsayılan değer
            $possibleNameKeys = [
                'Ad Soyad:', // En son tespit ettiğimiz
                'Ad Soyad',  // Boşluklu ama iki nokta üst üste olmayan
                'adSoyad',   // Bitişik küçük harf
                'Adsoyad',   // Bitişik ilk harf büyük
                'ADSOYAD',   // Tamamı büyük harf
                'Adı Soyadı', // Farklı ifade
                'FullName', // İngilizce olası isim
                'fullname' // İngilizce olası isim
            ];

            // Gelen $kisi (satır) içindeki anahtarları dolaş
            foreach ($kisi as $key => $value) {
                // Her anahtarı küçük harfe çevirerek olası isim anahtarlarıyla karşılaştır
                if (in_array(mb_strtolower($key, 'UTF-8'), array_map('mb_strtolower', $possibleNameKeys, array_fill(0, count($possibleNameKeys), 'UTF-8')))) {
                    $kisiAdi = $value;
                    break; // Eşleşen ilk anahtarı bulduktan sonra döngüyü kır
                }
            }
        ?>
            <tr class="<?= htmlspecialchars($durumClass) ?>">
                <td data-label="#"><?= $index + 1 ?></td>
                <td data-label="Ad Soyad"><?= htmlspecialchars($kisiAdi) ?></td>
                <td data-label="Durum"><?= htmlspecialchars($durumMetni) ?></td>
            </tr>
        <?php
            $counter++; // Sadece gösterilen kişileri say
        endforeach;
        ?>
    <?php endif; ?>
  </tbody>
 </table>
 
 <div id="aciklama">
    <?= nl2br(htmlspecialchars($settings['explanation'])) ?>
 </div>
 
 <footer>
  <p id="footerText">Bu tablo resmi bir belge niteliği taşımaz. Sadece bilgilendirme amaçlıdır.</p>
 </footer>
 
 <script>
    // Geri sayım sayacı için değişkenler ve fonksiyon
    const sayacElement = document.getElementById("sayac");
    const reloadInterval = 60; // 60 saniye
    let remainingTime = reloadInterval;

    function updatesayac() {
      remainingTime--;
      sayacElement.textContent = `Güncelleme: ${remainingTime}s`;

      if (remainingTime <= 0) {
        remainingTime = reloadInterval; // Sayacı sıfırla
        location.reload(); // Sayfayı yenile
      }
    }
    // Her saniye geri sayımı güncelle
    setInterval(updatesayac, 1000);
 </script>
</body>
</html>