# BEKCAN Institute (Student) - WP EDU Client

WordPress tabanlı öğrenci web sitelerini ana yönetim sistemine (Host LMS) bağlayan, öğrenci odaklı içerik analitiği, revizyon takibi, merkezi duyuru akışı ve otomatik güncelleme sağlayan istemci eklentisidir.

---

## 🚀 Özellikler

* **Merkezi LMS Entegrasyonu:** Öğrenci sitelerinin içerik metriklerini, revizyon geçmişini ve yayın durumlarını ana yönetim paneline iletir.
* **Kullanıcı Doğrulama & SSO:** Host LMS üzerinden güvenli API anahtarı doğrulaması ve tek oturum açma (Single Sign-On) desteği.
* **Merkezi Duyuru Akışı:** Eğitmen veya enstitü tarafından yayınlanan duyuruların doğrudan öğrencinin WordPress panosuna düşürülmesi.
* **Bağımsız GitHub Güncelleyici:** Harici kütüphane bağımlılığı olmadan çalışan, yeni GitHub sürümlerini denetleyip WordPress panelinden doğrudan güncelleme yapabilen yerel güncelleme motoru.
* **Çoklu Dil Desteği (i18n):** Türkçe (`tr_TR`) ve İngilizce (`en_US`) dil dosyalarıyla tam uyumluluk.

---

## 📁 Dizin Yapısı

```text
wp-edu-client/
├── admin/
│   ├── class-client-menu.php        # Yönetici menüsü ve ayar sayfaları
│   └── view-client-settings.php     # Ayarlar ekranı şablonu
├── includes/
│   ├── api/
│   │   ├── class-client-auth.php              # API yetkilendirme katmanı
│   │   ├── class-client-endpoint-content.php  # İçerik senkronizasyon endpoint'i
│   │   ├── class-client-endpoint-notices.php  # Duyuru senkronizasyon endpoint'i
│   │   └── class-client-endpoint-updates.php  # Güncelleme bildirim endpoint'i
│   ├── class-client-github-updater.php        # GitHub Release güncelleme motoru
│   ├── class-client-notices.php               # Pano duyuru mekanizması
│   ├── class-client-sso.php                   # SSO doğrulama mekanizması
│   └── class-client-tracking.php              # İçerik ve revizyon izleme
├── languages/
│   ├── wp-edu-client-en_US.mo
│   ├── wp-edu-client-en_US.po
│   ├── wp-edu-client-tr_TR.mo
│   └── wp-edu-client-tr_TR.po
├── LICENSE
├── README.md
├── uninstall.php
└── wp-edu-client.php                # Eklenti ana çekirdek dosyası

```

---

## 🛠️ Kurulum

### Yöntem 1: GitHub Release Üzerinden Yükleme (Tavsiye Edilen)

1. Projenin [Releases](https://www.google.com/search?q=https://github.com/canbekcan/wp-edu-client/releases) sayfasından en son sürümün ZIP arşivini indirin.
2. WordPress Yönetim Paneline gidin: **Eklentiler -> Yeni Eklenti Ekle -> Eklenti Yükle**.
3. İndirdiğiniz `.zip` dosyasını seçip **Şimdi Kur** butonuna tıklayın.
4. Eklentiyi etkinleştirin.

### Yöntem 2: Manuel Kurulum

1. Bu repoyu bilgisayarınıza indirin veya klonlayın:
```bash
git clone https://github.com/canbekcan/wp-edu-client.git

```


2. Klasörü `wp-content/plugins/wp-edu-client` dizinine taşıyın.
3. WordPress paneli üzerinden eklentiyi etkinleştirin.

---

## ⚙️ Yapılandırma

1. WordPress sol menüsünde beliren **BEKCAN EDU** (veya **WP EDU Client**) sekmesine gidin.
2. Host LMS tarafından size sağlanan:
* **Host API URL**
* **API / Client Key**
* **Student Token**
bilgilerini ilgili alanlara girip kaydedin.


3. Bağlantı durumunu kontrol ederek entegrasyonu tamamlayın.

---

## 🔄 Güncelleme Yönetimi

Eklenti, GitHub API'sini belirli aralıklarla sorgulayarak yeni bir Release yayınlandığında WordPress **Başlangıç -> Güncellemeler** ve **Eklentiler** ekranlarında standart WordPress bildirimleri üretir. Güncellemeyi doğrudan panelden tek tıkla tamamlayabilirsiniz.

---

## 📋 Gereksinimler

* **PHP:** 7.4 veya üzeri
* **WordPress:** 6.0 veya üzeri
* **PHP Eklentileri:** `cURL`, `OpenSSL`, `JSON`

---

## 📄 Lisans

Bu proje [MIT Lisansı](https://www.google.com/search?q=LICENSE) altında lisanslanmıştır.



```text
MIT License

Copyright (c) 2026 Can Bekcan / BEKCAN Institute

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

```