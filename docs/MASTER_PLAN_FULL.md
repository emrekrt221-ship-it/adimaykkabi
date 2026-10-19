> ARCHIVED ORIGINAL MASTER PLAN (20 October 2026). This source document was written BEFORE coding. For current progress always read docs/STATUS.md and docs/SESSION_HANDOFF.md. Legal/compliance prerequisites remain required.

---

# ADIM AYAKKABI
## Tam Kapsamlı E-Ticaret Web Sitesi - Ana Uygulama Planı v1.0

**Tarih:** 20 Ekim 2026  
**Durum:** Tasarım ve geliştirme için önerilen temel plan; uygulama başlamadı.  
**Kapsam:** Tek işletmeye ait fiziksel mağaza ile ortak stoklu, Türkiye geneline satış yapan e-ticaret sitesi.  
**İlke:** Profesyonel kalite, düşük ilk kurulum maliyeti, ölçülebilir performans, güvenlik ve sürdürülebilir bakım.

> **Kritik ön koşul:** Site yalnızca yasal olarak satışa sunulabilen ürünler için kullanılacaktır. Toptancıdan alınan ürünlerin fatura, menşe, marka ve tasarım hakları incelenecek; izinsiz taklit markalı ürünler satış kataloğuna alınmayacaktır. Bir ürüne “replika” yazılması bu riski ortadan kaldırmaz. Ticari/marka incelemesi için uzman görüşü alınmalıdır. “ADIM AYAKKABI” ismi ve alan adı da tescil ve kullanım açısından kontrol edilmelidir.

## 1. Yönetici özeti ve başarı tanımı

**Misyon:** Kullanıcı, ayakkabıyı Instagram'da veya arama motorunda keşfeder; siteye girer, modeli/renk/numarayı seçer, kartla öder, sipariş ve kargosunu izler. Mağaza sahibi aynı stoğu fiziksel satışla birlikte telefonundan yönetir.

**Hedef müşteri:** Başlangıçta Reyhanlı ve yakın çevrede mağazayı bilen müşteriler; ardından Türkiye genelinde fiyat/kalite/beden arayan bireysel müşteriler.

**İlk sürüm kapsamı (MVP):** Katalog, kategori/filtre, arama, varyantlı ürünler, numara bazlı stok, misafir ödeme, ödeme entegrasyonu, sipariş/kargo takibi, mağazadan teslim, basit fiziksel satış kaydı, mobil yönetim, güvenlik, yasal sayfalar ve temel raporlama.

**Başarı kriteri:** Gerçek satın alma akışı hatasız; aynı son stoklu ürün iki kez satılamıyor; mağaza satışı internete işleniyor; mobil deneyim ölçüm hedeflerini karşılıyor; kişisel veriler ve ödemeler güvenli işleniyor; tüm yasal ve ödeme sağlayıcısı onayları tamam.

## 2. Kararlar, kapsam ve kapsam dışı

| Alan | v1.0 kararı | Gerekçe |
| --- | --- | --- |
| Mağaza modeli | Tek satıcı / tek stok havuzu | Gereksiz çoklu satıcı karmaşıklığı yok |
| Web | WordPress + WooCommerce | Olgun e-ticaret iş akışları, hızlı teslim |
| Görsel arayüz | Hafif özel child theme veya performanslı blok tema | Marka kimliği, düşük JS yükü |
| Özel işler | Küçük, versiyonlu ADIM mağaza-işlemleri eklentisi | İş kuralları tema dosyasına gömülmez |
| Veritabanı | MySQL 8 uyumlu veya desteklenen MariaDB | WordPress/WooCommerce uyumu |
| Sipariş saklama | WooCommerce HPOS | Daha uygun sipariş veri modeli |
| Hosting | Yönetimli WooCommerce hosting, NVMe, güncel PHP | Önce işletim kolaylığı; gerçek testle kapasite |
| Cloudflare/CDN | **Yok** | İlk sürümde gereksiz dış bağımlılığı azalt |
| Önbellek | Sunucu tam sayfa önbelleği + OPcache; Redis opsiyonel | Kritik dinamik sayfalar hariç tutulur |
| Güvenlik | TLS/HTTPS, 2FA, güçlü hesaplar, yedek, güncelleme | Minimum üretim standardı |
| Ödeme | Onaylanan sağlayıcının resmi WooCommerce eklentisi | Kart bilgisi sitede tutulmaz |
| Kargo | Başlangıçta panelden manuel takip; sonra API | Düşük başlangıç maliyeti |
| Mağaza içi satış | Basit personel satış kaydı/stock-decrement | İnternetle tek stok doğruluğu |
| Redis / arama motoru | Ölçüm gerektirirse sonraki aşama | Erken optimizasyon yok |

**İlk sürümde yok:** Mobil APK/iOS uygulama; pazaryeri veya çok satıcılı model; mikroservis/Kubernetes; yapay zekâ öneri motoru; puan sistemi; gelişmiş depo/ERP; canlı sohbet botu; SMS OTP; özel arama kümesi; Cloudflare.

## 3. Marka kimliği ve tasarım sistemi

- **Marka:** ADIM AYAKKABI (adı kesinleşene kadar çalışma adı).
- **Renkler:** Kömür siyahı `#151515`, vurgu turuncu `#F57C00`, beyaz `#FFFFFF`, kırık beyaz `#F7F7F5`, açık gri `#E5E5E5`.
- **Stil:** Sade, temiz, kaliteli; büyük ürün fotoğrafı; turuncu yalnızca satın alma/indirim/aktif seçimde; aşırı animasyon ve sahte indirim baskısı yok.
- **Yazı:** Sistem fontları veya optimize tek web fontu; Türkçe karakter uyumu; yeterli renk kontrastı.
- **Görsel standardı:** Arka plan ve ayakkabının renkleri doğru; her ürüne 4-6 gerçek fotoğraf, mümkünse kısa çekim; özgün ürün ve numara açıklaması.
- **Duyarlı görünüm:** 360-430 px telefon, 768 px tablet, 1280+ px masaüstü. Dokunma alanları en az yaklaşık 44 px. Menü ve filtre mobilde açılır panel.
- **Güven unsurları:** Gerçek mağaza adresi, iletişim, açık teslimat/iade bilgisi, doğrulanabilir işletme bilgileri. Sahte değerlendirme veya yanlış orijinallik iddiası yok.

### Ana sayfa yerleşimi (mobil öncelikli)

1. Üst bant: gerçekçi teslimat/kampanya duyurusu (yalnız koşullar doğruysa).
2. Üst menü: logo, arama, sepet; erişilebilir kategori menüsü.
3. Ana alan: tek güçlü kampanya görseli + “Ürünleri İncele” CTA.
4. Kategori kutuları: Spor, Günlük, Klasik, Yeni Gelenler; hedef kitleye göre alt ayrımlar.
5. “Çok Satanlar” ve “Yeni Gelenler” ürün grid'i.
6. “Numaranı Bul” beden rehberi.
7. “Mağazamız” gerçek fotoğraf, adres, WhatsApp destek, teslim seçenekleri.
8. Alt bilgi: Şirket/KEP/iletişim, sözleşme/kvkk/iade sayfaları.

**Kategori:** Erkek/Kadın/Çocuk yalnızca gerçekten stok varsa. Marka filtresi yalnız hakları doğrulanmış, doğru tanımlı orijinal ürünlerde kullanılacak; sahte logo veya gerçek dışı marka ataması yapılmayacak.

## 4. Site haritası ve sayfa gereksinimleri

| Sayfa / yol | İşlev | v1 öncelik |
| --- | --- | --- |
| `/` | Ana sayfa, kategoriler, kampanya | P0 |
| `/magaza/` | Tüm ürünler, arama, filtre | P0 |
| `/kategori/...` | Kategoriye özel liste | P0 |
| `/urun/...` | Fotoğraf, varyasyon, stok, beden rehberi, sepete ekle | P0 |
| `/sepet/` | Adet, fiyat, kampanya, kargo özeti | P0 |
| `/odeme/` | Misafir ödeme, adres, onay, ödeme | P0 |
| `/siparis-takip/` | Misafirler için güvenli sipariş sorgusu | P0 |
| `/hesabim/` | İsteğe bağlı üyelik, sipariş geçmişi | P1 |
| `/iade-ve-degisim/` | İade koşulları ve başvuru kanalı | P0 |
| `/teslimat/` | Teslimat seçenekleri ve ücretleri | P0 |
| `/beden-rehberi/` | Numara ölçümü ve kalıp bilgisi | P0 |
| `/magazamiz/` | Gerçek mağaza, saatler, harita | P0 |
| `/iletisim/` | İletişim ve destek | P0 |
| `/sss/` | Ödeme, kargo, iade, numara soruları | P1 |
| Yasal sayfalar | Ön bilgilendirme, mesafeli satış, KVKK, çerez, gizlilik | P0 |

**Ürün sayfasının kesin alanları:** Ürün kodu (SKU), yasal ürün adı, doğru marka bilgisi (varsa), model, kategori, renk, numara varyasyonları, anlık stok, fiyat (vergiler dahil gösterim), doğru indirim bilgisi, görseller, malzeme/kalıp açıklaması, bakım, beden tablosu, teslimat/iade özeti, stok bildirimleri (opsiyonel).

## 5. Ürün verisi, varyant ve stok veri modeli

**WooCommerce yerleşik nesneleri:** Product (ürün), Product variation (ürün varyasyonu), Customer, Order, Order item, Coupon, Shipping. Siparişler için HPOS etkinliği ve eklenti uyumluluğu test edilir.

**Varyant yaklaşımı:** Bir modelin farklı renk ve numaraları ayrı satılabilir SKU'lar olarak tutulur. Örnek: `ADM-001-SYH-42`; numaraya göre stok `0,1,2...`. Aynı SKU tekrar kullanılamaz. Negatif stok yok. Backorder başlangıçta kapalı.

**İdari alanlar:** Alış maliyeti (müşteriden gizli), toptancı kodu, tedarik tarihi/fatura referansı, ölçü/kalıp notu, yer/raf notu, satışa uygunluk inceleme durumu, minimum stok eşiği.

**Örnek:** Model 001 siyah; 40:2, 41:3, 42:1, 43:0, 44:2. Müşteri 43'ü sepete ekleyemez. 42 bir kez satılınca tükenir. 42'nin fiziki mağazadaki satışı da aynı varyantı azaltır.

**Doğruluk kuralları:**

- Fiziksel ve internet satışı **tek stok kaynağını** günceller; ayrı Excel stoğu işletilmez.
- Ödeme bekleyen siparişler için WooCommerce hold-stock politikası açıkça belirlenir (başlangıç önerisi 10-15 dakika, testle doğrulanır); süre sonunda uygun biçimde serbest bırakılır.
- Ödeme “başarılı” olunca yalnızca bir sipariş kaydı oluşur; yinelenen webhook'lar iki kez düşüm yaratmaz.
- İptal/iade sonucu stoğun otomatik eklenmesi sadece ürünün gerçekten geri alınması ve satılabilir durumda olmasına bağlıdır; iade kabul/kalite kontrol adımı bulunur.
- Her stok değişikliği için işlem türü, zaman, aktör, varyant, önce/sonra miktar ve referans kaydedilir (özel eklenti veya güvenilir denetlenmiş çözüm).
- Yönetici, stok düzeltmesini gerekçe girmeden yapamaz; toplu CSV içe aktarımlarında önizleme ve yedek gerekir.

## 6. Fiziksel mağaza ile ortak stok ve hızlı satış ekranı

**Prensip:** WooCommerce stokları esas alınır. Mağazada satış yapılırken panelde “Mağaza Satışı” ekranından ürün SKU veya arama ile bulunur, renk/numara ve adet seçilir, ayrı bir mağaza satış kaydı oluşturulur ve stok azaltılır. Nakit/POS tahsilatı fiziksel tarafta yapılır; web ödeme işlemine dönüştürülmez.

**Basit mağaza satış ekranı (P0):** Barkod/arama alanı, küçük ürün fotoğrafı, numara ve stok, adet, fiyat, “Satışı Kaydet”; satışı iptal/ters işlem yetkisi, işlem geçmişi. Fiziksel kasa satışının belge/fatura işlemleri muhasebe süreciyle uyumlu tutulur.

**Teknik uygulama:** WordPress yönetimine entegre küçük bir özel eklenti; WooCommerce API/CRUD üzerinden varyasyonları ve envanteri günceller. Doğrudan SQL ile stok düşülmez. Aynı anda iki satışa karşı atomiklik ve stok sıfırdan aşağı inmeme kontrolü yapılır. İleride el tipi barkod okuyucu eklenebilir, ilk günden şart değil.

**Operasyon standardı:** Personel her fiziki satışı anında işleyemeyecekse ortak stok tam güvenilir sayılamaz. Gün sonu 5 dakikalık kontrol ve haftalık sayım rutini uygulanır.

## 7. Sipariş ve ödeme iş akışı

**Müşteri akışı:** Ürün keşif → numara seçimi → sepet → adres/kargo → ön bilgilendirme ve sözleşme erişimi → ödeme sağlayıcısına güvenli yönlendirme/gömülü ödeme → doğrulanmış ödeme bildirimi → sipariş hazırlama → kargo/teslim → tamamlandı.

**Sipariş durumları:** Ödeme bekliyor; Ödendi/Yeni; Hazırlanıyor; Kargoda; Teslim edildi; İptal; İade talebi; İade sonuçlandı. Durum eşlemesi WooCommerce temel durumları + gerekli basit özel etiketlerle yapılır; yanlış durum geçişine izin verilmez.

**Ödeme:** iyzico veya PayTR gibi başvurusu onaylanan tek sağlayıcı ilk sürüme entegre edilir; resmi WooCommerce eklentisi, test ve üretim anahtarları ayrı tutulur. İmzalı geri bildirim doğrulaması, işlem referansında idempotency, miktar/para birimi/sipariş kimliği doğrulaması yapılır. Ödeme başarı sayfasındaki kullanıcı dönüşüne tek başına güvenilmez. Kart numarası/CVV sitede veya WordPress loglarında saklanmaz.

**Kesintiler:** Ödeme başarılı ama tarayıcı kapanmışsa webhook'tan sipariş güncellenir; ödeme belirsizse otomatik “ödendi” yapılmaz. Çift bildirime rağmen yalnız bir ödeme/sipariş işlenir. İade sağlayıcı paneli ile sipariş kayıtları uzlaştırılır.

**Teslimat:** Türkiye geneline kargo (firma/ücret sözleşmesi onaylandıktan sonra), mağazadan teslim; kargo takip numarası önce manuel girilir. Ücretsiz kargo eşiği, gerçek marj hesabıyla belirlenir.

## 8. Yönetim paneli ve roller

**İlk sürümde iki rol:** Yönetici (tüm sistem, finans, fiyat, kullanıcı, ayar); Mağaza Personeli (ürün görüntüleme, stok, fiziksel satış, sipariş hazırlama; ödeme anahtarı/WordPress eklenti ayarlarına erişim yok). Gereksiz onlarca rol yok.

**Gösterge paneli:** Bugünkü sipariş sayısı, brüt ciro, henüz kargolanmayan siparişler, kritik stok, satılan numaralar, iade talebi. Satış iptalleri ve vergiler raporda ayrı gösterilir; brüt ciro ile net kâr karıştırılmaz.

**Ürün ekranı:** SKU, görseller, varyasyonlar, stok, alış maliyeti, fiyat, kategori, yayında/taslak. Ürünlerin satışa uygunluğu onaylanmadan yayına alınamaz.

**Raporlar:** Günlük/haftalık/aylık satış, kaynak kanalı (ölçülebildiği kadar), çok satan modeller, numara bazlı stok, bekleyen sipariş, iade oranı, tahmini katkı marjı. Alış fiyatına yetkisiz erişim kısıtlanır.

**Mobil kullanım:** WooCommerce yönetim ekranı ve küçük özel satış ekranı telefonda test edilir; ayrı mobil uygulama geliştirilmez.

## 9. Teknik mimari ve dağıtım

**İstek hattı:** Müşteri cihazı → HTTPS → hosting web sunucusu (Nginx / LiteSpeed, sağlayıcıya göre) → PHP-FPM / WordPress / WooCommerce → MySQL. Statik dosyalar ve görseller aynı sunucudan sunulur. Dış CDN/Cloudflare başlangıçta **yok**. Ödeme ve kargo servisleri HTTPS üzerinden entegre edilir.

**Hosting seçim kriterleri:** Güncel PHP 8.3+ uyumluluğu, desteklenen MySQL/MariaDB sürümü, OPcache, NVMe, günlük otomatik yedek, staging, SSH/SFTP/WP-CLI erişimi, ücretsiz TLS, 2FA, güvenlik katmanı, güvenilir destek, kaynak şeffaflığı, sunucu konumu ve çıkış trafiği. Donanım başlangıç varsayımı olarak yönetilen plan veya yaklaşık 2 vCPU / 4 GB RAM eşdeğeri değerlendirilebilir; nihai kapasite yük testine göre belirlenir. Bu sayı performans garantisi değildir.

**Kod organizasyonu:** WooCommerce core; minimum sayıda düzenli güncellenen eklenti; özel child theme; `adim-store-operations` özel eklentisi (mağaza satışı, stok hareketleri ve zorunlu küçük iş kuralları). Tema dosyalarında ödeme veya stok iş mantığı bulunmaz. Özel kod Git'te saklanır, staging/test/production ortamları ayrılır.

**Cache:** Kategori/ürün/kurumsal sayfalar cache'e alınır; sepet, ödeme, hesabım ve kişiye özel oturum içerikleri cache dışı bırakılır. WooCommerce sepet/session cookie kurallarına uyulur. PHP OPcache açık; Redis yalnız sunucu ve ölçümler uygun olduğunda eklenir.

**Arka plan işleri:** Gerçek WP-Cron trafik bağımlı kalmaması için hosting cron ile güvenilir periyodik çalıştırma; WooCommerce Action Scheduler kuyruklarında birikim izleme; e-posta gönderim hataları görünür.

**E-posta:** Alan adına ait kurumsal adres, SPF/DKIM/DMARC; güvenilir transactional SMTP/API sağlayıcısı; sipariş ve kargo bildirimleri. Pazarlama iletileri ayrı izin/İYS değerlendirmesine tabi tutulur.

**Geliştirme disiplini:** Versiyon kilidi, eklenti uyumluluk testi, ortam bazlı gizli anahtarlar, test siparişi, veritabanı yedeği, önce staging sonra üretim; geri dönüş planı.

## 10. Hız ve ölçeklenebilirlik hedefleri

**Ölçüm hedefleri, garanti değil:** Gerçek kullanıcı verilerinde 75. yüzdelikte mobil LCP ≤ 2,5 sn, INP ≤ 200 ms, CLS ≤ 0,1. Temsilî ürün liste, ürün detay, sepet ve ödeme akışları hem laboratuvar hem gerçek cihazla test edilir.

**Uygulama yöntemleri:** WebP/AVIF varyantlı görseller, doğru boyutlandırma, lazy-load (ilk ana görsel hariç), srcset, cache headers, azaltılmış JS/CSS, font optimizasyonu, sorgu analizleri, gereksiz eklentileri engelleme, autoload şişmesini kontrol, tarayıcı performans bütçeleri.

**Başlangıç performans bütçesi (öneri):** Bir mobil kategori sayfasının ilk yüklemesinde gereksiz büyük fotoğraf/slayt/video yok; ana görsel sıkıştırılmış; ilk ekran için kritik CSS; üçüncü taraf script mümkün olduğunca az. Kesin KB hedefi prototip ölçümünden sonra kararlaştırılır.

**Yük test senaryoları:** 10, 25 ve 50 eşzamanlı kullanıcı düzeylerinde katalog gezme, arama, filtreleme, sepet ekleme; ayrı kontrollü testte eşzamanlı stok tüketimi. Üretimde gerçek ödeme almadan test modu/staging kullanılacak. Hedef p95 yanıt süreleri başlangıç benchmark'ına göre belirlenir; hata oranı ve veritabanı yükü izlenir.

**Ölçek büyütme sırası:** (1) Görsel/tema/eklenti optimizasyonu, (2) hosting kaynak artışı, (3) Redis ve daha iyi veritabanı optimizasyonu, (4) gerekiyorsa CDN/WAF/ayrı servisler. İlk günden karmaşık mimari kurulmaz.

## 11. Güvenlik, veri koruma, yedek ve olay yönetimi

- HTTPS/TLS zorunlu; WordPress admin hesabında 2FA; kişi başına ayrı hesap; en az yetki; güçlü parola ve oturum yönetimi.
- Eklenti/tema çekirdeği düzenli güncellenir; güncellemeler önce staging'de ödeme, sepet ve stok testinden geçer.
- WAF/anti-bot özellikleri hosting üzerinde değerlendirilebilir; Cloudflare şart değil. Oturum açma denemeleri oranla sınırlandırılır; bot ve şüpheli sipariş takibi yapılır.
- Sunucu, WordPress, veri tabanı ve ödeme API anahtarları gizli tutulur; Git'e, istemci JS'ye veya loglara konmaz.
- SQL injection/XSS/CSRF risklerine karşı WordPress güvenli API'leri, input doğrulama, escape, nonce, capability check kullanılır.
- Günlük veritabanı + medya yedeği; sunucu dışı saklama; en az 14-30 günlük döngü (kapasite ve hukuki saklama yükümlülüğüne göre); ayda bir örnek geri yükleme testi. Yedekler şifreli/erişim kısıtlı.
- Günlük hata izleme: başarısız ödemeler, stok uyuşmazlıkları, SMTP hatası, cron birikmesi, sunucu hatası, yedek başarısızlığı.
- Olay planı: ödemeyi geçici kapat / siparişleri güvenli beklet, etkilenen veriyi sınıflandır, sağlayıcı ve ilgili kişilere gerekli süreçlere göre bildirim, geri yükleme, kök neden analizi.
- KVKK aydınlatma metni, veri işleme/saklama politikası, zorunlu olmayan analitik/reklam çerezleri için koşullara uygun rıza yönetimi; rıza yoksa pazarlama izleme çalışmaz.

## 12. Türkiye'de satış öncesi hukuki/ticari hazır olma kapısı

**Bu bölüm yayına çıkışın ön koşuludur; avukat/mali müşavir kontrolü önerilir.**

- Şirket/esnaf kaydı, vergi/fatura düzeni ve ödeme sağlayıcısı işyeri başvurusu tamam.
- Kendi sitesinden sipariş alan işletme için **faaliyetten önce ETBİS kaydı** ve gerekli bildirimler; KEP dâhil ilgili işletme bilgileri kontrol edilir.
- Satıcı kimlik/iletişim ve varsa KEP bilgileri, ön bilgilendirme, mesafeli satış sözleşmesi, teslimat/kargo koşulları, cayma/iade talimatları ve gizlilik sayfaları hazırlanır.
- Ayakkabı için genel mesafeli satış cayma hakkı ve istisnalar, iade süresi/ücreti, kesinti ve ücret iadesi akışı güncel mevzuata göre yazılır; müşteri hakkı yanıltıcı biçimde daraltılmaz.
- KVKK aydınlatması, çerez tercihleri, ticari ileti izinleri/İYS yükümlülüğü ve veri aktarımı değerlendirilir.
- **Ürün uygunluk kapısı:** Tedarikçi faturası, orijinallik ve marka/tasarım kullanım hakkı incelenir. Hukuka aykırı taklit markalı ürünler için satış sayfası, reklam veya ödeme akışı etkinleştirilmez. Logoyu kaldırmak/marka ismini değiştirmek tek başına tüm hak sorunlarını çözmeyebilir.
- “ADIM” adı, alan adı ve görsel kimliğin kullanımında üçüncü kişi marka hakkı çatışması olup olmadığı araştırılır.

## 13. SEO, Instagram ve satışa başlama planı

**SEO:** Türkçe ürün adları, açıklayıcı slug, tekil title/meta, kategori açıklamaları, XML sitemap, canonical, robots, ürün şemaları (gerçek veriler), kırık link ve mobil dizine eklenme kontrolü.

**Instagram → web satış:** Biyografide gerçek site bağlantısı; paylaşımlarda ürün kodu; Reels ve story üzerinden uygun ürün sayfasına yönlendirme; kampanya bağlantıları için UTM; müşteri site içinde ödeme yapar. Mevzuata uygun olmayan ürünlere trafik/ilan verilmez.

**Google İşletme Profili:** Dükkânın gerçek fotoğrafı, saatler, doğru telefon/adres, müşteri yorumlarına şeffaf yanıt. Sahte yorum yok.

**Ölçüm:** Haftalık ziyaret, ürün görüntülemeden sepete oran, ödeme başlatma, tamamlanan sipariş, sipariş başına net katkı, iade oranı, stok tükenme hızı. Çerez rızası gerektiren analitikler rıza kontrollerinden sonra etkinleştirilir.

**İlk 30 gün operasyon:** Günlük gerçek ürün içerikleri, küçük kontrollü reklam denemeleri (ürünlerin satışa uygunluğu sağlandıktan sonra), stok düzeltmeleri, paketleme süresi ve müşteri soru kayıtları. Reklam bütçesi kârlılık ve satış verisine göre artırılır.

## 14. Kalite güvence ve zorunlu test matrisi

| Test alanı | Senaryo | Kabul |
| --- | --- | --- |
| Katalog | Yanlış numara, negatif fiyat, stokta olmayan ürün | Satışa kapalı / doğrulama var |
| Arama | Türkçe karakter/boş sonuç/filtre birleşimi | Doğru sonuç, kırık ekran yok |
| Mobil | Küçük Android/iPhone ekranları | Yatay taşma, okunmaz buton yok |
| Sepet | Fiyat, indirim ve kargo tutarı güncelleme | Sipariş toplamı tutarlı |
| Ödeme | Başarılı, başarısız, iptal, tarayıcı kapanması | Yanlış ücretlendirme/sipariş yok |
| Webhook | Aynı ödeme bildirimi iki kez | Tek doğrulanmış sipariş/düşüm |
| Son stok | Web ve mağaza aynı son numarayı satmak ister | İkisi birden başarılı olamaz |
| Stok bekletme | Ödenmeyen sipariş zaman aşımı | Rezerv uygun biçimde serbest |
| İade | İade talebi, teslim ve kontrol | Onaysız yeniden satış stoku yok |
| Kargo | Takip numarası ve durum değişimi | Doğru müşteriye doğru bildirim |
| Yetki | Personel ayar veya API anahtarı görmeye çalışır | Erişim reddedilir |
| Güvenlik | XSS/CSRF, login rate limit, gizli dosyalar | Açık bulunmaz; tarama/inceleme |
| Yedek | Test ortamında geri yükleme | Tutarlı veriyle açılıyor |
| Performans | Mobil Lighthouse/Web Vitals + k6 | Hedefler raporlanmış ve kritik açık yok |
| Hukuki | ETBİS, sözleşme, izin, marka uygunluğu | Yazılı kontrol tamam |

**Go-live engelleri:** Ödeme tutarsızlığı, fazla stok satışı, kişisel veri sızıntısı, geçersiz yasal belgeler, yedek geri yüklenememesi veya satışa uygunluğu doğrulanmamış ürünler. Bu durumlarda site satışa açılmaz.

## 15. Yol haritası: dört aşamalı uygulama

### Aşama 0 - Ön hazırlık (3-7 iş günü; dış onaylara bağlı)

- İşletme ve ürün satış uygunluğunu netleştir; fatura, tedarikçi, marka ve alan adı kontrolü.
- Domain, kurumsal e-posta, hosting teklifleri ve ödeme sağlayıcısı başvuru evrakları.
- 20 örnek ürün, numaraları, maliyeti, gerçek fotoğrafları ve mevcut stok listesi.
- Tasarım için ana sayfa, kategori, ürün, sepet ve ödeme prototipi onayı.

### Aşama 1 - Temel site ve tasarım (yaklaşık 1 hafta)

- WordPress/WooCommerce kurulumu, staging/production, tema, kategori şablonları, header/footer.
- SSL/HTTPS, hızlı görsel altyapısı, cache sınırları, sipariş HPOS kontrolleri.
- Kategori/filtre/ürün sayfası, mobil test; aranabilir ürün kodu.

### Aşama 2 - Stok, sipariş, ödeme (yaklaşık 1-2 hafta)

- Varyasyon ve stok yapılandırması; CSV ürün aktarımı; mağaza satışı ekranı.
- Ödeme test ortamı, webhook doğrulama, sipariş e-postaları, kargo ve mağazadan teslim.
- Stok yarış ve çift webhook testleri; iade iş akışları.

### Aşama 3 - Uyum, kalite, canlıya geçiş (yaklaşık 1 hafta + dış onay süresi)

- ETBİS ve işletme/yasal dokümanlar, ödeme sağlayıcısı canlı onayı.
- Güvenlik testi, geri yükleme, mobil performans ve fonksiyonel regresyon.
- Canlıda düşük hacimli doğrulama siparişleri, son kontrol, satışa açılış.

**Takvim:** Teknik geliştirme yaklaşık 3-5 hafta planlanabilir; tasarım değişiklikleri, ürün hazır oluşu, banka/ödeme onayı ve mevzuat süreci bunu uzatabilir. “4 haftada kesin biter” taahhüdü yok.

## 16. İş listesi (geliştiriciye verilecek epikler)

| Kod | İş paketi | Çıktı | Öncelik |
| --- | --- | --- | --- |
| ADIM-01 | Repo, staging, ortam değişkenleri | Güvenli geliştirme ortamı | P0 |
| ADIM-02 | Marka/tasarım sistemi | Mobil ve masaüstü temel tema | P0 |
| ADIM-03 | Katalog ve filtreler | İşlevsel ürün listeleme | P0 |
| ADIM-04 | Varyantlı ürün ve SKU | Numara/renk bazlı stok | P0 |
| ADIM-05 | Fiziksel mağaza satış ekranı | Tek stok kaynağı, hareket kaydı | P0 |
| ADIM-06 | Sepet ve misafir ödeme | Mobil ödeme akışı | P0 |
| ADIM-07 | Ödeme sağlayıcı entegrasyonu | Doğrulanmış webhook ve uzlaştırma | P0 |
| ADIM-08 | Kargo/mağazadan teslim | Hazırlama, takip, teslim | P0 |
| ADIM-09 | İade/iptal ve müşteri hizmeti | İzlenebilir destek süreci | P0 |
| ADIM-10 | Roller ve yönetici raporları | Güvenli operasyon paneli | P0 |
| ADIM-11 | KVKK/ETBİS/yasal içerikler | Uyum kontrol listesi | P0 |
| ADIM-12 | Hız, güvenlik ve yedek | Test raporları, geri yükleme kanıtı | P0 |
| ADIM-13 | SEO, analitik, Instagram | Satışa hazırlık | P1 |
| ADIM-14 | Opsiyonel barkod, Redis, kargo API | İkinci aşama geliştirmeler | P2 |

## 17. Maliyet modeli ve öncelikli harcama

**Sabit maliyet kalemleri:** Alan adı yenileme, hosting, kurumsal e-posta/SMTP, off-site yedek, varsa premium tema/eklenti, danışmanlık ve bakım. **Değişkenler:** Ödeme komisyonu, kargo, paketleme, iade maliyeti, reklam, muhasebe ve vergi. Canlı fiyat alınmadan kesin bütçe verilmez.

**Maliyeti azaltma kararları:** WooCommerce çekirdeği, mümkünse ücretsiz ve iyi bakılan eklentiler; tek ödeme sağlayıcısı; stokları önce yerleşik varyasyon sistemiyle yönetme; kargo numarasını manuel girme; kendi ürün çekimini yapma; Cloudflare, VPS kümeleri, mikroservis veya mobil uygulama satın almama. Profesyonellikten ödün verilmeyecek kalemler: kaliteli hosting, SSL, yedek, güvenlik, doğru ödeme akışı ve yasal uyum.

| Kalem | İlk sürüm yaklaşımı | Teklif / takip |
| --- | --- | --- |
| Domain / kurumsal e-posta | Uygun alan adı ve temel e-posta | Gerçek yıllık teklif al |
| Yönetimli Woo hosting | Woo uyumlu, staging/yedek/SSL | 2-3 firmadan karşılaştırmalı teklif |
| Tema ve özel tasarım | Hafif tema + sınırlı özel geliştirme | Ayrı tasarım/işçilik tahmini |
| Ödeme | Resmi WooCommerce modülü | Komisyon/ödeme bloke süresi sözleşmesi |
| Kargo | Manuel takip; sözleşmeli fiyat | Reyhanlı çıkış ve iade maliyeti |
| Bakım / yedek | Aylık kontrol rutini | Kendin veya hizmet bedeli |
| Reklam | Küçük deney; kârlılık sonrası artır | Ayrı aylık üst sınır |

**Kâr hesabı:** Satış bedeli - ürün maliyeti - ödeme komisyonu - vergi etkisi - kargo katkısı - paketleme - reklam katkısı - beklenen iade maliyeti. İndirim/kargo kampanyası bu hesabın sonrasında yapılır.

## 18. İşletme operasyonu ve satış sonrası rutin

**Her gün:** Yeni ödemeleri mutabakat yap, siparişleri hazırla, stok düşük uyarılarına bak, fiziki mağaza satışlarının sisteme girildiğini kontrol et, başarısız ödeme ve e-posta bildirimlerini gözden geçir.

**Her hafta:** 10-20 rastgele SKU'yu fiziksel say, iade ve kargo teslimat sorunlarını incele, kırık link/hız raporunu izle, öne çıkan ürün fotoğraflarını güncelle.

**Her ay:** WordPress/tema/eklenti güncellemelerini staging'den geçir; yedekten örnek geri yükleme testi; ödeme-kargo faturası uzlaştırması; satış ve kâr raporu; stok eskimesi ve yavaş modellerin değerlendirmesi.

**Personel prosedürü:** Açılış kapanış stok kontrolü, ürün kabulde kayıt, fatura saklama, son numara satışı öncesi doğrulama, hasarlı/iade ürünü ayrı muhafaza, yetkisiz fiyat değişikliğini engelleme.

## 19. Risk kayıt defteri

| Risk | Olası etki | Önlem |
| --- | --- | --- |
| Taklit markalı / hak ihlalli ürün | Hukuki sorumluluk, ödeme hesabı sorunu | Ürün uygunluğu kapısı; uygun olmayan satışa açılmaz |
| Fiziksel mağaza satışının kaydedilmemesi | Stok tutarsızlığı, iptal | Hızlı satış ekranı, günlük denetim |
| Ağır tema/çok eklenti | Yavaşlık, hata, güvenlik açığı | Eklenti sınırı, ölçüm, düzenli denetim |
| Ödeme bildirimi tekrarı | Yanlış sipariş/stok | İmza kontrolü ve idempotency testleri |
| Ucuz ama yetersiz hosting | Ödeme ve stok gecikmesi | Staging yük testi, kapasite artırma opsiyonu |
| Hatalı ürün görselleri/beden bilgisi | Yüksek iade | Gerçek fotoğraf, ölçü ve kalıp açıklaması |
| Yedeklerin bozuk olması | Sipariş verisi kaybı | Otomatik off-site yedek, aylık geri yükleme |
| ETBİS/KVKK eksikliği | İdari/hukuki risk | Go-live kontrol ve profesyonel inceleme |

## 20. Son kabul ve teslim koşulları

**Teslim paketi:** Alan adı/hosting hesabı işletme adına; WordPress admin erişimi; özel kod için Git deposu; staging ortamı; veri tabanı/medya yedeği; güncelleme ve kurtarma yönergesi; ürün içe aktarma CSV şablonu; test raporu; ödeme sağlayıcısı ve kargo süreçlerinin dokümantasyonu; temel personel eğitimi; açılış kontrol listesi.

**Açılış öncesi asgari kabul:**

- [ ] Ürünlerin yasal satış uygunluğu ve işletme yükümlülükleri kontrol edildi.
- [ ] Alan adı, SSL, e-posta ve yedek başarıyla test edildi.
- [ ] Mobil ana sayfa, ürün, sepet, ödeme akışı kullanılabilir.
- [ ] 20 gerçek ürün ve tüm numara/renk stokları doğru işlendi.
- [ ] Fiziksel satış stok düşümü ve son stok yarış testi geçti.
- [ ] Ödeme testleri; başarılı, başarısız, iptal, çift webhook geçti.
- [ ] Sipariş bildirimi, kargo takip, iade prosedürü çalışıyor.
- [ ] ETBİS, sözleşmeler, satıcı kimliği, KVKK/çerez uyumu tamam.
- [ ] Staging geri yükleme ve güvenlik kontrolü geçti.
- [ ] Performans bulguları kayıt altında; kritik açık yok.
- [ ] Personel günlük satış/stok işleyişini uygulamalı öğrendi.

**Son mimari kararı:** V1'de WooCommerce + hafif özel tasarım + minimum özel stok/mağaza eklentisi + uygun hosting + SSL + güvenli ödeme + yedek. Cloudflare/Redis/mikroservis ilk günden zorunlu değil. Ölçümlere göre eklenir.

## 21. Resmî ve teknik referanslar

- WooCommerce - High Performance Order Storage: https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/enable-hpos/
- WooCommerce - Variable product inventory: https://woocommerce.com/document/variable-product/
- WooCommerce - Cache best practices: https://developer.woocommerce.com/docs/best-practices/performance/configuring-caching-plugins
- WooCommerce - PHP sürüm ve yükseltme rehberi: https://developer.woocommerce.com/2026/09/29/php-8-1-requirement/
- iyzico - WooCommerce resmî entegrasyonu: https://docs.iyzico.com/platformlar/woocommerce
- PayTR - WooCommerce resmî modülü: https://dev.paytr.com/moduller/wordpress
- Ticaret Bakanlığı - ETBİS yükümlülüğü: https://ticaret.gov.tr/ic-ticaret/sikca-sorulan-sorular/elektronik-ticaret
- Ticaret Bakanlığı - Mesafeli sözleşmeler: https://tuketici.ticaret.gov.tr/yayinlar/tuketici-bilgi-rehberi/mesafeli-sozlesmeler-hakkinda-bilgilendirme
- KVKK - Çerez uygulamaları rehberi: https://www.kvkk.gov.tr/Icerik/7353/Cerez-Uygulamalari-Hakkinda-Rehber
- KVKK - Çerez rızası karar özeti: https://www.kvkk.gov.tr/Icerik/7595/2022-1358

---

**Belge niteliği:** Bu plan uygulama ve kabul ölçütlerini tarif eder. Satın alınmış hosting, kurulmuş site, ödeme sağlayıcısı onayı, mevzuat uyumu veya performans sonucu anlamına gelmez. Satışa çıkış öncesinde güncel gereksinimler doğrulanacaktır.