<?php
/**
 * Plugin Name: İletişim Butonu
 * Description: İletişim butonları, Çoklu Temsilci (Premium Tasarım), Woo Etiketleri, Exit-Intent, UTM, Mesai Saatleri. Mobil ikonlar 2 tık büyütüldü.
 * Version: 9.4
 * Author: Destek Asistanı
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
 * 1. AJAX İSTATİSTİK TAKİP ALTYAPISI
 * ========================================================================== */
add_action('wp_ajax_ibp_track_click', 'ibp_track_click_callback');
add_action('wp_ajax_nopriv_ibp_track_click', 'ibp_track_click_callback');
function ibp_track_click_callback() {
    $type = isset($_POST['click_type']) ? sanitize_text_field($_POST['click_type']) : '';
    if (in_array($type, ['wa', 'tel'])) {
        $date = wp_date('Y-m-d');
        $stats = get_option('ibp_click_stats', array());
        if (!isset($stats[$date])) { $stats[$date] = array('wa' => 0, 'tel' => 0); }
        $stats[$date][$type]++;
        update_option('ibp_click_stats', $stats);
    }
    wp_die();
}

/* ==========================================================================
 * 2. YÖNETİM PANELİ VE SCRIPTLER
 * ========================================================================== */
add_action('admin_menu', 'ibp_iletisim_menu_ekle');
function ibp_iletisim_menu_ekle() {
    $page = add_menu_page('İletişim Butonu', 'İletişim Butonu', 'manage_options', 'ibp-iletisim-ayarlari', 'ibp_ayarlar_sayfasi_html', 'dashicons-chart-line', 100);
    add_action("admin_print_scripts-{$page}", 'ibp_admin_scripts');
}

function ibp_admin_scripts() {
    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
}

function ibp_sanitize_pages($val) { return is_array($val) ? implode(',', array_map('intval', $val)) : sanitize_text_field($val); }
function ibp_time_options($selected) {
    $html = '<option value="kapali" ' . selected($selected, 'kapali', false) . '>Kapalı</option>';
    for ($h = 0; $h < 24; $h++) { foreach (['00', '30'] as $m) { $time = sprintf('%02d:%s', $h, $m); $html .= '<option value="'.$time.'" ' . selected($selected, $time, false) . '>'.$time.'</option>'; } }
    return $html;
}
function ibp_get_val($key, $default) { $val = get_option($key); return ($val === false || trim($val) === '') ? $default : $val; }

add_action('admin_init', 'ibp_ayarlari_kaydet');
function ibp_ayarlari_kaydet() {
    $fields = array(
        'ibp_tel_status', 'ibp_wa_status', 'ibp_telefon_gosterim', 'ibp_tel_cc', 'ibp_tel_no', 'ibp_wa_cc', 'ibp_wa_no', 'ibp_wa_metin_mobil', 'ibp_wa_metin_masaustu',
        'ibp_color_tel', 'ibp_color_wa', 'ibp_color_title', 'ibp_color_subtitle', 'ibp_icon_tel', 'ibp_icon_wa', 'ibp_color_cookie_bg', 'ibp_color_cookie_text', 'ibp_color_cookie_btn_bg', 'ibp_color_cookie_btn_text',
        'ibp_wa_template', 'ibp_bh_status', 'ibp_bh_off_msg', 'ibp_bh_weekday_start', 'ibp_bh_weekday_end', 'ibp_bh_saturday_start', 'ibp_bh_saturday_end', 'ibp_bh_sunday_start', 'ibp_bh_sunday_end', 'ibp_woo_visibility', 
        'ibp_lazy_load', 'ibp_utm_key', 'ibp_utm_val', 'ibp_utm_no', 'ibp_track_tel', 'ibp_track_wa',
        'ibp_cerez_durum', 'ibp_cerez_metin', 'ibp_cerez_link_metin', 'ibp_cerez_sayfa_id', 'ibp_cerez_buton_metin',
        'ibp_badge_status', 'ibp_tooltip_status', 'ibp_tooltip_msg', 'ibp_exit_intent_status', 'ibp_exit_intent_msg',
        'ibp_ma_status', 'ibp_dept1_name', 'ibp_dept1_cc', 'ibp_dept1_no', 'ibp_dept2_name', 'ibp_dept2_cc', 'ibp_dept2_no',
        'ibp_camp_status', 'ibp_camp_start', 'ibp_camp_end', 'ibp_camp_cc', 'ibp_camp_no', 'ibp_camp_msg'
    );
    foreach($fields as $field) { register_setting('ibp_ayarlar_grubu', $field); }
    register_setting('ibp_ayarlar_grubu', 'ibp_hide_pages', 'ibp_sanitize_pages');
}

function ibp_ayarlar_sayfasi_html() {
    $default_tel_icon = plugin_dir_url(__FILE__) . 'img/telefon.webp';
    $default_wa_icon  = plugin_dir_url(__FILE__) . 'img/whatsapp.webp';
    
    $ulkeler = array(
        array('c'=>'90', 'en'=>'Turkey', 'tr'=>'Türkiye'),
        array('c'=>'1', 'en'=>'US/Canada', 'tr'=>'ABD/Kanada'),
        array('c'=>'44', 'en'=>'UK', 'tr'=>'İngiltere'),
        array('c'=>'49', 'en'=>'Germany', 'tr'=>'Almanya'),
        array('c'=>'33', 'en'=>'France', 'tr'=>'Fransa'),
        array('c'=>'31', 'en'=>'Netherlands', 'tr'=>'Hollanda'),
        array('c'=>'39', 'en'=>'Italy', 'tr'=>'İtalya'),
        array('c'=>'34', 'en'=>'Spain', 'tr'=>'İspanya'),
        array('c'=>'7', 'en'=>'Russia', 'tr'=>'Rusya'),
        array('c'=>'994', 'en'=>'Azerbaijan', 'tr'=>'Azerbaycan'),
        array('c'=>'971', 'en'=>'UAE', 'tr'=>'B.A.E'),
    );
    ?>
    <div class="wrap">
        <h1>İletişim Butonu 🚀</h1>
        <?php settings_errors(); ?>
        
        <h2 class="nav-tab-wrapper" id="ibp-tabs">
            <a href="#" class="nav-tab nav-tab-active" data-target="tab-temel">📱 Numaralar & Akıllı Özellikler</a>
            <a href="#" class="nav-tab" data-target="tab-etkilesim">✨ Etkileşim & Temsilci</a>
            <a href="#" class="nav-tab" data-target="tab-kampanya">⏱️ Kampanya & UTM</a>
            <a href="#" class="nav-tab" data-target="tab-renk">🎨 Tasarım & Çerez</a>
            <a href="#" class="nav-tab" data-target="tab-istatistik">📊 İstatistikler & Takip</a>
        </h2>
        
        <form method="post" action="options.php" style="background:#fff; padding:20px; border:1px solid #ccd0d4; margin-top:15px;">
            <?php settings_fields('ibp_ayarlar_grubu'); ?>
            
            <div id="tab-temel" class="ibp-tab-content">
                <h3>İletişim Numaraları ve Metinleri</h3>
    <table class="form-table">
        <tr valign="top"><th scope="row">Arama Butonu Durumu</th>
        <td>
            <select name="ibp_tel_status">
                <option value="acik" <?php selected(get_option('ibp_tel_status', 'acik'), 'acik'); ?>>Açık Göster</option>
                <option value="kapali" <?php selected(get_option('ibp_tel_status', 'acik'), 'kapali'); ?>>Gizle</option>
            </select>
        </td></tr>
        <tr valign="top"><th scope="row">WhatsApp Butonu Durumu</th>
        <td>
            <select name="ibp_wa_status">
                <option value="acik" <?php selected(get_option('ibp_wa_status', 'acik'), 'acik'); ?>>Açık Göster</option>
                <option value="kapali" <?php selected(get_option('ibp_wa_status', 'acik'), 'kapali'); ?>>Gizle</option>
            </select>
        </td></tr>

                    <tr valign="top"><th scope="row">Görünen Telefon No:</th>
                    <td><input type="text" name="ibp_telefon_gosterim" value="<?php echo esc_attr(ibp_get_val('ibp_telefon_gosterim', '0532 123 45 67')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Arama Linki İçin No:</th>
                    <td>
                        <select name="ibp_tel_cc" class="ibp-country-select" style="width:250px;">
                            <?php foreach($ulkeler as $u): ?><option value="+<?php echo $u['c']; ?>" <?php selected(ibp_get_val('ibp_tel_cc', '+90'), '+'.$u['c']); ?>><?php echo $u['tr']; ?> (+<?php echo $u['c']; ?>)</option><?php endforeach; ?>
                        </select>
                        <input type="text" name="ibp_tel_no" value="<?php echo esc_attr(ibp_get_val('ibp_tel_no', '5321234567')); ?>" class="regular-text" style="width: 200px;" />
                    </td></tr>
                    <tr valign="top"><th scope="row">WhatsApp Numarası:</th>
                    <td>
                        <select name="ibp_wa_cc" class="ibp-country-select" style="width:250px;">
                            <?php foreach($ulkeler as $u): ?><option value="<?php echo $u['c']; ?>" <?php selected(ibp_get_val('ibp_wa_cc', '90'), $u['c']); ?>><?php echo $u['tr']; ?> (+<?php echo $u['c']; ?>)</option><?php endforeach; ?>
                        </select>
                        <input type="text" name="ibp_wa_no" value="<?php echo esc_attr(ibp_get_val('ibp_wa_no', '5321234567')); ?>" class="regular-text" style="width: 200px;" />
                    </td></tr>
                    <tr valign="top"><th scope="row">WA Metni (Mobil):</th>
                    <td><input type="text" name="ibp_wa_metin_mobil" value="<?php echo esc_attr(ibp_get_val('ibp_wa_metin_mobil', 'Temsilci Bağlan')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">WA Metni (Masaüstü):</th>
                    <td><input type="text" name="ibp_wa_metin_masaustu" value="<?php echo esc_attr(ibp_get_val('ibp_wa_metin_masaustu', 'Bilgi Alın')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">WA Şablonu:<br><small>Etiketler: [sayfa_adi], [url], [kaynak], [urun_adi], [urun_fiyati], [stok_kodu]</small></th>
                    <td><textarea name="ibp_wa_template" rows="4" class="large-text"><?php echo esc_textarea(ibp_get_val('ibp_wa_template', 'Merhaba, [sayfa_adi] hakkında bilgi almak istiyorum.')); ?></textarea></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Görünürlük & Mesai Saatleri</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Gizlenecek Sayfalar:</th>
                    <td>
                        <?php 
                        $hidden_pages = get_option('ibp_hide_pages', '');
                        $hidden_pages_arr = !empty($hidden_pages) ? explode(',', $hidden_pages) : array();
                        $all_pages = get_pages();
                        ?>
                        <select name="ibp_hide_pages[]" class="ibp-page-list" multiple="multiple" style="width: 100%; max-width: 500px;">
                            <?php 
                            foreach($all_pages as $page) {
                                $selected = in_array($page->ID, $hidden_pages_arr) ? 'selected="selected"' : '';
                                echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </td></tr>
                    <tr valign="top"><th scope="row">WooCommerce Ürünleri:</th>
                    <td>
                        <select name="ibp_woo_visibility">
                            <option value="all" <?php selected(get_option('ibp_woo_visibility', 'all'), 'all'); ?>>Standart (Her Yerde Göster)</option>
                            <option value="hide_on_prod" <?php selected(get_option('ibp_woo_visibility', 'all'), 'hide_on_prod'); ?>>Ürün Detay Sayfalarında Gizle</option>
                            <option value="only_on_prod" <?php selected(get_option('ibp_woo_visibility', 'all'), 'only_on_prod'); ?>>SADECE Ürün Detay Sayfalarında Göster</option>
                        </select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Mesai Saatleri Durumu:</th>
                    <td>
                        <select name="ibp_bh_status">
                            <option value="kapali" <?php selected(get_option('ibp_bh_status', 'kapali'), 'kapali'); ?>>Kapalı (7/24 Her Zaman Aktif)</option>
                            <option value="acik" <?php selected(get_option('ibp_bh_status', 'kapali'), 'acik'); ?>>Açık (Özel Saatlerde Aktif)</option>
                        </select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Hafta İçi (Pzt - Cuma):</th>
                    <td>
                        <select name="ibp_bh_weekday_start"><?php echo ibp_time_options(get_option('ibp_bh_weekday_start', '09:00')); ?></select> - 
                        <select name="ibp_bh_weekday_end"><?php echo ibp_time_options(get_option('ibp_bh_weekday_end', '18:00')); ?></select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Cumartesi:</th>
                    <td>
                        <select name="ibp_bh_saturday_start"><?php echo ibp_time_options(get_option('ibp_bh_saturday_start', 'kapali')); ?></select> - 
                        <select name="ibp_bh_saturday_end"><?php echo ibp_time_options(get_option('ibp_bh_saturday_end', 'kapali')); ?></select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Pazar:</th>
                    <td>
                        <select name="ibp_bh_sunday_start"><?php echo ibp_time_options(get_option('ibp_bh_sunday_start', 'kapali')); ?></select> - 
                        <select name="ibp_bh_sunday_end"><?php echo ibp_time_options(get_option('ibp_bh_sunday_end', 'kapali')); ?></select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Mesai Dışı WA Mesajı:</th>
                    <td><textarea name="ibp_bh_off_msg" rows="2" class="large-text"><?php echo esc_textarea(ibp_get_val('ibp_bh_off_msg', 'Şu an mesai saatleri dışındayız, mesajınızı bırakın, en kısa sürede dönüş yapalım')); ?></textarea></td></tr>
                </table>
            </div>

            <div id="tab-etkilesim" class="ibp-tab-content" style="display:none;">
                <h3>Etkileşim (Dönüşüm Artırıcılar)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Bildirim Rozeti (Badge):</th>
                    <td><label><input type="checkbox" name="ibp_badge_status" value="yes" <?php checked(get_option('ibp_badge_status', 'no'), 'yes'); ?> /> WA İkonuna "1" okunmamış mesaj rozeti ekle</label></td></tr>
                    <tr valign="top"><th scope="row">Karşılama Balonu:</th>
                    <td><label><input type="checkbox" name="ibp_tooltip_status" value="yes" <?php checked(get_option('ibp_tooltip_status', 'no'), 'yes'); ?> /> 5 saniye sonra WA ikonunun yanında balon çıkar</label></td></tr>
                    <tr valign="top"><th scope="row">Balon Mesajı:</th>
                    <td><input type="text" name="ibp_tooltip_msg" value="<?php echo esc_attr(ibp_get_val('ibp_tooltip_msg', 'Size nasıl yardımcı olabilirim? 👋')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Çıkış Niyeti (Exit-Intent):</th>
                    <td><label><input type="checkbox" name="ibp_exit_intent_status" value="yes" <?php checked(get_option('ibp_exit_intent_status', 'no'), 'yes'); ?> /> Kullanıcı sekmeyi kapatmaya yönelirse balon çıkar</label></td></tr>
                    <tr valign="top"><th scope="row">Çıkış Niyeti Mesajı:</th>
                    <td><input type="text" name="ibp_exit_intent_msg" value="<?php echo esc_attr(ibp_get_val('ibp_exit_intent_msg', 'Siparişinizi tamamlamak için yardıma ihtiyacınız var mı? 🎁')); ?>" class="large-text" /></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Çoklu Temsilci Modu</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Çoklu Temsilci:</th>
                    <td><label><input type="checkbox" name="ibp_ma_status" value="yes" <?php checked(get_option('ibp_ma_status', 'no'), 'yes'); ?> /> Departman Seçim Kutusunu Aktif Et</label></td></tr>
                    <tr valign="top"><th scope="row">1. Departman:</th>
                    <td>
                        <input type="text" name="ibp_dept1_name" value="<?php echo esc_attr(ibp_get_val('ibp_dept1_name', 'Satış Departmanı')); ?>" placeholder="Departman Adı" />
                        <input type="text" name="ibp_dept1_cc" value="<?php echo esc_attr(ibp_get_val('ibp_dept1_cc', '90')); ?>" style="width:60px;" placeholder="Kod" />
                        <input type="text" name="ibp_dept1_no" value="<?php echo esc_attr(ibp_get_val('ibp_dept1_no', '5321111111')); ?>" placeholder="Numara" />
                    </td></tr>
                    <tr valign="top"><th scope="row">2. Departman:</th>
                    <td>
                        <input type="text" name="ibp_dept2_name" value="<?php echo esc_attr(ibp_get_val('ibp_dept2_name', 'Teknik Destek')); ?>" placeholder="Departman Adı" />
                        <input type="text" name="ibp_dept2_cc" value="<?php echo esc_attr(ibp_get_val('ibp_dept2_cc', '90')); ?>" style="width:60px;" placeholder="Kod" />
                        <input type="text" name="ibp_dept2_no" value="<?php echo esc_attr(ibp_get_val('ibp_dept2_no', '5322222222')); ?>" placeholder="Numara" />
                    </td></tr>
                </table>
            </div>

            <div id="tab-kampanya" class="ibp-tab-content" style="display:none;">
                <h3>Kampanya Modu</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Kampanya Modu:</th>
                    <td><label><input type="checkbox" name="ibp_camp_status" value="yes" <?php checked(get_option('ibp_camp_status', 'no'), 'yes'); ?> /> Aktif Et</label></td></tr>
                    <tr valign="top"><th scope="row">Tarih Aralığı:</th>
                    <td>Başlangıç: <input type="date" name="ibp_camp_start" value="<?php echo esc_attr(get_option('ibp_camp_start', wp_date('Y-m-d'))); ?>" /> 
                        Bitiş: <input type="date" name="ibp_camp_end" value="<?php echo esc_attr(get_option('ibp_camp_end', wp_date('Y-m-d', strtotime('+7 days')))); ?>" /></td></tr>
                    <tr valign="top"><th scope="row">Kampanya Numarası:</th>
                    <td>
                        <input type="text" name="ibp_camp_cc" value="<?php echo esc_attr(ibp_get_val('ibp_camp_cc', '90')); ?>" style="width:60px;" />
                        <input type="text" name="ibp_camp_no" value="<?php echo esc_attr(ibp_get_val('ibp_camp_no', '5329999999')); ?>" placeholder="Geçici numara" />
                    </td></tr>
                    <tr valign="top"><th scope="row">Kampanya Özel Mesajı:</th>
                    <td><textarea name="ibp_camp_msg" rows="3" class="large-text"><?php echo esc_textarea(ibp_get_val('ibp_camp_msg', 'Merhaba, Efsane Cuma indirimleri hakkında bilgi almak istiyorum!')); ?></textarea></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>UTM Odaklı Bölgesel Yönlendirme</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">URL Parametresi (Örn: kampanya):</th>
                    <td><input type="text" name="ibp_utm_key" value="<?php echo esc_attr(get_option('ibp_utm_key', '')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Eşleşecek Değer (Örn: maras):</th>
                    <td><input type="text" name="ibp_utm_val" value="<?php echo esc_attr(get_option('ibp_utm_val', '')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Yönlendirilecek Farklı WA No:<br><small>(Boşluksuz. Örn: 905554443322)</small></th>
                    <td><input type="text" name="ibp_utm_no" value="<?php echo esc_attr(get_option('ibp_utm_no', '')); ?>" class="regular-text" /></td></tr>
                </table>
            </div>

            <div id="tab-renk" class="ibp-tab-content" style="display:none;">
                <h3>İletişim Butonları Tasarımı</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Arama Rengi:</th><td><input type="text" name="ibp_color_tel" value="<?php echo esc_attr(ibp_get_val('ibp_color_tel', '#5d2e8f')); ?>" class="ibp-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">WA Rengi:</th><td><input type="text" name="ibp_color_wa" value="<?php echo esc_attr(ibp_get_val('ibp_color_wa', '#82bb26')); ?>" class="ibp-color-picker" /></td></tr>
                            <tr valign="top"><th scope="row">Başlık (Title) Rengi</th>
        <td><input type="text" name="ibp_color_title" value="<?php echo esc_attr(ibp_get_val('ibp_color_title', '#ffffff')); ?>" class="ibp-color-picker" /></td></tr>
        <tr valign="top"><th scope="row">Alt Başlık (Subtitle) Rengi</th>
        <td><input type="text" name="ibp_color_subtitle" value="<?php echo esc_attr(ibp_get_val('ibp_color_subtitle', '#ffffff')); ?>" class="ibp-color-picker" /></td></tr>
        <tr valign="top"><th scope="row">Arama İkonu:</th><td><input type="text" name="ibp_icon_tel" id="ibp_icon_tel" value="<?php echo esc_attr(ibp_get_val('ibp_icon_tel', $default_tel_icon)); ?>" class="regular-text" /><button type="button" class="button ibp-upload-btn" data-target="#ibp_icon_tel">Seç</button></td></tr>
                    <tr valign="top"><th scope="row">WhatsApp İkonu:</th><td><input type="text" name="ibp_icon_wa" id="ibp_icon_wa" value="<?php echo esc_attr(ibp_get_val('ibp_icon_wa', $default_wa_icon)); ?>" class="regular-text" /><button type="button" class="button ibp-upload-btn" data-target="#ibp_icon_wa">Seç</button></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Çerez Uyarısı Yönetimi</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Uyarı Durumu:</th>
                    <td><select name="ibp_cerez_durum"><option value="acik" <?php selected(get_option('ibp_cerez_durum', 'acik'), 'acik'); ?>>Açık (Gösterilsin)</option><option value="kapali" <?php selected(get_option('ibp_cerez_durum', 'acik'), 'kapali'); ?>>Kapalı (Gizlensin)</option></select></td></tr>
                    <tr valign="top"><th scope="row">Kutu Arkaplan Rengi:</th><td><input type="text" name="ibp_color_cookie_bg" value="<?php echo esc_attr(ibp_get_val('ibp_color_cookie_bg', '#ffffff')); ?>" class="ibp-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Metin Rengi:</th><td><input type="text" name="ibp_color_cookie_text" value="<?php echo esc_attr(ibp_get_val('ibp_color_cookie_text', '#333333')); ?>" class="ibp-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Buton Arkaplan Rengi:</th><td><input type="text" name="ibp_color_cookie_btn_bg" value="<?php echo esc_attr(ibp_get_val('ibp_color_cookie_btn_bg', '#82bb26')); ?>" class="ibp-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Buton Yazı Rengi:</th><td><input type="text" name="ibp_color_cookie_btn_text" value="<?php echo esc_attr(ibp_get_val('ibp_color_cookie_btn_text', '#ffffff')); ?>" class="ibp-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Ana Uyarı Metni:</th><td><input type="text" name="ibp_cerez_metin" value="<?php echo esc_attr(ibp_get_val('ibp_cerez_metin', 'Size daha iyi bir deneyim sunmak için çerezler kullanıyoruz. Sayfamızı kullanarak bunu kabul etmiş olursunuz.')); ?>" class="large-text" /></td></tr>
                    <tr valign="top"><th scope="row">Politika Linki Yazısı:</th><td><input type="text" name="ibp_cerez_link_metin" value="<?php echo esc_attr(ibp_get_val('ibp_cerez_link_metin', 'Çerez Politikası')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Politika Sayfası:</th><td><?php wp_dropdown_pages(array('name' => 'ibp_cerez_sayfa_id', 'echo' => 1, 'show_option_none' => '— Sayfa Seçin —', 'option_none_value' => '0', 'selected' => get_option('ibp_cerez_sayfa_id', 0))); ?></td></tr>
                    <tr valign="top"><th scope="row">Kabul Et Butonu Metni:</th><td><input type="text" name="ibp_cerez_buton_metin" value="<?php echo esc_attr(ibp_get_val('ibp_cerez_buton_metin', 'Tamam')); ?>" class="regular-text" /></td></tr>
                </table>
            </div>

            <div id="tab-istatistik" class="ibp-tab-content" style="display:none;">
                <h3>Google Core Web Vitals (Hız Optimizasyonu)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Lazy Load (Gecikmeli Yükleme):</th>
                    <td><label><input type="checkbox" name="ibp_lazy_load" value="yes" <?php checked(get_option('ibp_lazy_load', 'yes'), 'yes'); ?> /> Gecikmeli yüklemeyi aktif et (SEO ve Hız için önerilir)</label></td></tr>
                </table>
                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Dönüşüm Takibi (Pixel/Gtag)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Arama Butonu Takip Kodu:<br><small>Örn: gtag_report_conversion('tel:0546...');</small></th>
                    <td><textarea name="ibp_track_tel" rows="2" class="large-text"><?php echo esc_textarea(get_option('ibp_track_tel', '')); ?></textarea></td></tr>
                    <tr valign="top"><th scope="row">WhatsApp Butonu Takip Kodu:<br><small>Örn: fbq('track', 'Contact');</small></th>
                    <td><textarea name="ibp_track_wa" rows="2" class="large-text"><?php echo esc_textarea(get_option('ibp_track_wa', '')); ?></textarea></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Son 7 Günün Tıklama Analizi</h3>
                <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                    <thead><tr><th>Tarih</th><th>WhatsApp</th><th>Telefon</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php
                        $stats = get_option('ibp_click_stats', array());
                        $toplam_wa = 0; $toplam_tel = 0;
                        for($i=0; $i<7; $i++) {
                            $d = wp_date('Y-m-d', strtotime("-$i days"));
                            $wa = isset($stats[$d]['wa']) ? $stats[$d]['wa'] : 0;
                            $tel = isset($stats[$d]['tel']) ? $stats[$d]['tel'] : 0;
                            $toplam_wa += $wa; $toplam_tel += $tel;
                            echo "<tr><td><strong>" . wp_date('d M Y', strtotime($d)) . "</strong></td><td><span class='dashicons dashicons-whatsapp' style='color:#82bb26;'></span> $wa</td><td><span class='dashicons dashicons-phone' style='color:#5d2e8f;'></span> $tel</td><td>".($wa+$tel)."</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot><tr><th>7 Günlük Toplam</th><th><?php echo $toplam_wa; ?></th><th><?php echo $toplam_tel; ?></th><th><?php echo ($toplam_wa+$toplam_tel); ?></th></tr></tfoot>
                </table>
            </div>

            <br><?php submit_button('Tüm Ayarları Kaydet ve Uygula', 'primary', 'submit', true, ['style' => 'font-size: 16px; padding: 10px 30px;']); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.ibp-color-picker').wpColorPicker();
        $('.nav-tab').click(function(e) { e.preventDefault(); $('.nav-tab').removeClass('nav-tab-active'); $('.ibp-tab-content').hide(); $(this).addClass('nav-tab-active'); $('#' + $(this).data('target')).show(); });
        $('.ibp-upload-btn').click(function(e) { e.preventDefault(); var targetInput = $($(this).data('target')); var imageUploader = wp.media({ title: 'İkon Seç', button: { text: 'Bunu Kullan' }, multiple: false }).on('select', function() { targetInput.val(imageUploader.state().get('selection').first().toJSON().url); }).open(); });
        $('.ibp-country-select').select2({ placeholder: 'Ülke Arayın' });
    });
    </script>
    <?php
}

/* ==========================================================================
 * 3. CSS KODLARI
 * ========================================================================== */
add_action('wp_head', 'ibp_eklenti_css_ekle');
function ibp_eklenti_css_ekle() {
    $color_tel = esc_attr(ibp_get_val('ibp_color_tel', '#5d2e8f'));
    $color_wa  = esc_attr(ibp_get_val('ibp_color_wa', '#82bb26'));
    $color_title = esc_attr(ibp_get_val('ibp_color_title', '#ffffff'));
    $color_subtitle = esc_attr(ibp_get_val('ibp_color_subtitle', '#ffffff'));
    $color_cookie_bg       = esc_attr(ibp_get_val('ibp_color_cookie_bg', '#ffffff'));
    $color_cookie_text     = esc_attr(ibp_get_val('ibp_color_cookie_text', '#333333'));
    $color_cookie_btn_bg   = esc_attr(ibp_get_val('ibp_color_cookie_btn_bg', '#82bb26'));
    $color_cookie_btn_text = esc_attr(ibp_get_val('ibp_color_cookie_btn_text', '#ffffff'));
    ?>
    <style>
        :root { 
            --ibp-tel-color: <?php echo $color_tel; ?>; 
            --ibp-wa-color: <?php echo $color_wa; ?>;
        --ibp-title-color: <?php echo $color_title; ?>;
        --ibp-subtitle-color: <?php echo $color_subtitle; ?>; 
            --ibp-cookie-bg: <?php echo $color_cookie_bg; ?>;
            --ibp-cookie-text: <?php echo $color_cookie_text; ?>;
            --ibp-cookie-btn-bg: <?php echo $color_cookie_btn_bg; ?>;
            --ibp-cookie-btn-text: <?php echo $color_cookie_btn_text; ?>;
        }

        /* Performans (Lazy Load) Sınıfı */
        .ibp-lazy-hidden { opacity: 0 !important; pointer-events: none !important; transform: translateY(20px); }
        
        .floating-buttons{position:fixed;bottom:20px;left:0;width:100%;z-index:9999; transition: all 0.5s ease-out;}
        .cta-btn, .cta-btn-yesil {display:inline-flex;align-items:center; border-radius:50px;width:185px;height:50px;box-sizing:border-box;color:#fff;position:relative;overflow:visible; text-decoration:none !important;}
        .cta-btn {justify-content:flex-end; padding:10px 25px 10px 40px; background:var(--ibp-tel-color);}
        .cta-btn-yesil {justify-content:flex-start; padding:10px 40px 10px 25px; background:var(--ibp-wa-color);}
        .cta-icon, .cta-icon-yesil {
    width: 65px;
    height: 65px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    z-index:2;
}
        .cta-icon {left:-15px; background:var(--ibp-tel-color);} .cta-icon-yesil {right:-15px; background:var(--ibp-wa-color);}
        .cta-icon img{width:40px;height:auto;object-fit:contain;} .cta-icon-yesil img{width:50px;height:auto;object-fit:contain;}
        .cta-text {display:flex;flex-direction:column;width:100%;text-align:right;align-items:flex-end;z-index:2}
        .cta-text-yesil {display:flex;flex-direction:column;width:100%;text-align:left;align-items:flex-start;z-index:2}
        
        /* Metinlerin alt alta inmesini ve kaymasını engelle */
        .title-cta{font-size:.875rem;font-weight:700; white-space:nowrap; color: var(--ibp-title-color);} 
        .subtitle-cta{font-size:.75rem;opacity:.9;line-height:1.5; white-space:nowrap; color: var(--ibp-subtitle-color);}
        
        .nogosterme { display: none !important; }
        .nogoster { display: flex !important; }

        .cta-icon::before, .cta-icon-yesil::before {
    content:""; position:absolute; width:90px; height:90px; top:50%; left:50%; margin-top:-45px; margin-left:-45px; border-radius:50%; box-sizing:border-box; animation:nefes 2s infinite ease-in-out; opacity:0.25;
}
        .cta-icon::before { border: 5px solid var(--ibp-tel-color); } .cta-icon-yesil::before { border: 5px solid var(--ibp-wa-color); }
        .cta-icon::after, .cta-icon-yesil::after {
    content:""; position:absolute; width:65px; height:65px; top:50%; left:50%; margin-top:-35px; margin-left:-35px; border-radius:50%; box-sizing:border-box; animation:ripple 2s infinite; opacity:0;
}
        .cta-icon::after { border: 4px solid var(--ibp-tel-color); } .cta-icon-yesil::after { border: 4px solid var(--ibp-wa-color); }
        
        @keyframes ripple{ 0%{transform:scale(0.8);opacity:.4} 70%{opacity:.1} 100%{transform:scale(1.8);opacity:0} }
        @keyframes nefes{ 0%{transform:scale(0.9);opacity:.2} 50%{transform:scale(1.05);opacity:.4} 100%{transform:scale(0.9);opacity:.2} }

        .left-btn{position:absolute;left:20px;bottom:30px} .right-btn{position:absolute;right:20px;bottom:30px}
        
        /* Bildirim Rozeti (Badge) */
        .ibp-badge {position:absolute; top:5px; right:5px; background:red; color:white; font-size:12px; font-weight:bold; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border-radius:50%; border:2px solid white; z-index:10;}
        
        /* Tooltip (Balon) */
        .ibp-tooltip {position:absolute; top:-50px; right:0; background:white; color:#333; padding:10px 15px; border-radius:10px; font-size:13px; font-weight:bold; box-shadow:0 5px 15px rgba(0,0,0,0.1); white-space:nowrap; opacity:0; pointer-events:none; transition:all 0.3s; transform:translateY(10px); border:1px solid #eee;}
        .ibp-tooltip::after {content:''; position:absolute; bottom:-6px; right:35px; width:12px; height:12px; background:white; border-bottom:1px solid #eee; border-right:1px solid #eee; transform:rotate(45deg);}
        .ibp-tooltip.goster {opacity:1; transform:translateY(0); pointer-events:auto;}
        
        /* Çoklu Temsilci Popup Şık Tasarım (YENİ) */
        .ibp-modal-overlay {position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(3px); z-index:99999; display:none; align-items:center; justify-content:center;}
        .ibp-modal-overlay.goster {display:flex; animation: fadeIn 0.3s ease;}
        .ibp-modal-content {background:#fff; padding:30px 25px; border-radius:24px; width:90%; max-width:380px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.15); position:relative; transform: translateY(0); animation: slideUp 0.4s ease;}
        .ibp-modal-header-icon { width: 60px; height: 60px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--ibp-wa-color); font-size: 30px; }
        .ibp-modal-content h3 {margin:0 0 20px 0; font-size: 1.25rem; font-weight: 700; color: #1a1a1a;}
        .ibp-modal-close {position:absolute; top:15px; right:15px; width:32px; height:32px; background:#f5f5f5; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:bold; cursor:pointer; color:#666; transition:0.3s; line-height:1;}
        .ibp-modal-close:hover {background:#ffebee; color:red;}
        .ibp-dept-btn {display:flex; align-items:center; justify-content:flex-start; gap: 12px; width:100%; padding:14px 20px; margin:12px 0 0 0; background:#fff; border:2px solid #f0f0f0; border-radius:14px; color:#333; font-weight:600; font-size: 1rem; text-decoration:none; transition:all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.02);}
        .ibp-dept-btn i {font-style: normal; font-size: 1.2rem; color: var(--ibp-wa-color);}
        .ibp-dept-btn:hover {background:#f8fff9; border-color:var(--ibp-wa-color); color:var(--ibp-wa-color); transform:translateY(-2px); box-shadow: 0 6px 15px rgba(37, 211, 102, 0.15);}
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* Çerez Uyarısı CSS */
        .ibp-cookie-banner { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: var(--ibp-cookie-bg); color: var(--ibp-cookie-text); padding: 12px 25px; border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: space-between; gap: 20px; z-index: 99999; border: 1px solid #efefef; opacity: 0; visibility: hidden; transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .ibp-cookie-banner.goster { bottom: 30px; opacity: 1; visibility: visible; }
        .ibp-cookie-text { margin: 0; font-size: 14px; line-height: 1.4; font-weight:500;} .ibp-cookie-link { color: var(--ibp-tel-color); text-decoration: underline; font-weight: 700; white-space: nowrap;}
        .ibp-cookie-btn { background: var(--ibp-cookie-btn-bg); color: var(--ibp-cookie-btn-text); border: none; padding: 8px 25px; border-radius: 25px; cursor: pointer; font-weight: 700; font-size: 14px; transition: transform 0.3s, filter 0.3s; white-space: nowrap; }
        .ibp-cookie-btn:hover { filter: brightness(0.9); transform: scale(1.05); }
        
        @media (max-width: 768px){
            .left-btn{left: 10px; bottom:15px;} .right-btn{right: 10px; bottom:15px;}
            .cta-btn {padding:5px 5px 5px 35px; width:155px;} .cta-btn-yesil {padding:5px 35px 5px 5px; width:155px;}
            .cta-icon, .cta-icon-yesil {
    width: 65px;
    height: 65px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    z-index:2;
} .cta-icon {left: -12px;} .cta-icon-yesil {right: -12px;}
            
            /* MOBİLDE İKON BOYUTLARI 2 TIK BÜYÜTÜLDÜ */
            .cta-icon img {width:36px;} .cta-icon-yesil img {width:44px;} 
            
            /* MOBİLDE BİLDİRİM ROZETİ (BADGE) YERİ DÜZELTİLDİ */
            .ibp-badge {top: -2px; right: -2px;}
            
            /* SARMALAYICI BOYUTLAR GÜNCELLENDİ */
            .cta-icon::before, .cta-icon-yesil::before {
    content:""; position:absolute; width:90px; height:90px; top:50%; left:50%; margin-top:-45px; margin-left:-45px; border-radius:50%; box-sizing:border-box; animation:nefes 2s infinite ease-in-out; opacity:0.25;
} 
            .cta-icon::after, .cta-icon-yesil::after {
    content:""; position:absolute; width:65px; height:65px; top:50%; left:50%; margin-top:-35px; margin-left:-35px; border-radius:50%; box-sizing:border-box; animation:ripple 2s infinite; opacity:0;
}
            
            .cta-text, .cta-text-yesil { align-items: center; text-align: center; } 
            
            .nogosterme { display: flex !important; }
            .nogoster { display: none !important; }

            .ibp-tooltip {right:-10px;} .ibp-tooltip::after {right:25px;}
            .ibp-cookie-banner { width: 90%; flex-direction: column; border-radius: 15px; text-align: center; padding: 20px; gap: 12px; }
            .ibp-cookie-banner.goster { bottom: 100px; } .ibp-cookie-btn { width: 100%; padding: 12px; }
        }
    </style>
    <?php
}

/* ==========================================================================
 * 4. HTML ÇIKTILARI VE CLIENT-SIDE JS MANTIĞI
 * ========================================================================== */
add_action('wp_footer', 'ibp_eklenti_html_ekle');
function ibp_eklenti_html_ekle() {
    
    // Gizlenen Sayfalar Kontrolü
    $hide_pages = get_option('ibp_hide_pages', '');
    if (!empty($hide_pages) && is_page()) {
        $hide_arr = array_map('trim', explode(',', $hide_pages));
        if (in_array(get_the_ID(), $hide_arr)) { return; }
    }

    // WooCommerce Görünürlük Kontrolü & Veri Çekimi
    $woo_vis = get_option('ibp_woo_visibility', 'all');
    $urun_adi = ''; $urun_fiyat = ''; $stok_kodu = '';
    if (function_exists('is_product')) {
        if ($woo_vis === 'hide_on_prod' && is_product()) { return; }
        if ($woo_vis === 'only_on_prod' && !is_product()) { return; }
        if (is_product()) {
            global $product;
            if($product) {
                $urun_adi = $product->get_name();
                $urun_fiyat = strip_tags($product->get_price_html());
                $stok_kodu = $product->get_sku();
            }
        }
    } else {
        if ($woo_vis === 'only_on_prod') { return; }
    }

    // Değişkenler
    $whatsapp_numara = esc_js(ibp_get_val('ibp_wa_cc', '90')) . esc_js(ibp_get_val('ibp_wa_no', '5321234567'));
    $wa_template     = esc_js(ibp_get_val('ibp_wa_template', 'Merhaba, [sayfa_adi] hakkında bilgi almak istiyorum.'));
    
    // İzleme Kodları
    $track_tel       = esc_attr(get_option('ibp_track_tel', ''));
    $track_wa        = esc_attr(get_option('ibp_track_wa', ''));

    // Lazy Load
    $is_lazy_load    = get_option('ibp_lazy_load', 'yes') === 'yes' ? 'ibp-lazy-hidden' : '';

    $badge_st        = get_option('ibp_badge_status', 'no') === 'yes';
    $tt_st           = get_option('ibp_tooltip_status', 'no') === 'yes';
    $tt_msg          = esc_html(ibp_get_val('ibp_tooltip_msg', 'Size nasıl yardımcı olabilirim? 👋'));
    $ei_st           = get_option('ibp_exit_intent_status', 'no') === 'yes';
    $ei_msg          = esc_html(ibp_get_val('ibp_exit_intent_msg', 'Yardıma ihtiyacınız var mı? 🎁'));
    
    $ma_st           = get_option('ibp_ma_status', 'no') === 'yes';
    $camp_st         = get_option('ibp_camp_status', 'no') === 'yes';

    $telefon_linki   = esc_attr(ibp_get_val('ibp_tel_cc', '+90')) . esc_attr(ibp_get_val('ibp_tel_no', '5321234567'));
    $wa_html_link    = 'https://wa.me/' . esc_attr(ibp_get_val('ibp_wa_cc', '90') . ibp_get_val('ibp_wa_no', '5321234567'));
    ?>
    
    <div id="ibp-buttons-container" class="floating-buttons <?php echo $is_lazy_load; ?>">
        <?php if(get_option('ibp_tel_status', 'acik') !== 'kapali'): ?>
        <div class="left-btn">
            <a href="tel:<?php echo $telefon_linki; ?>" id="ibp-tel-link" class="cta-btn" <?php echo !empty($track_tel) ? 'onclick="' . $track_tel . '"' : ''; ?>>
                <div class="cta-icon"><img src="<?php echo esc_url(ibp_get_val('ibp_icon_tel', plugin_dir_url(__FILE__) . 'img/telefon.webp')); ?>" alt="Ara" width="40" height="40"></div>
                <div class="cta-text"><div class="title-cta">Arayın</div><div class="subtitle-cta"><?php echo esc_html(ibp_get_val('ibp_telefon_gosterim', '0532 123 45 67')); ?></div></div>
            </a>
        </div>
        <?php endif; ?>
        <?php if(get_option('ibp_wa_status', 'acik') !== 'kapali'): ?>
        <div class="right-btn">
            <?php if($tt_st || $ei_st): ?><div id="ibp-wa-tooltip" class="ibp-tooltip"><?php echo $tt_msg; ?></div><?php endif; ?>
            
            <!-- WhatsApp Butonu: href JS tarafından dinamik olarak düzenlenebilir. -->
            <a href="<?php echo $wa_html_link; ?>" id="ibp-wa-link" class="cta-btn-yesil" <?php echo !empty($track_wa) ? 'onclick="' . $track_wa . '"' : ''; ?>>
                <div class="cta-text-yesil nogosterme"><div class="title-cta">Whatsapp</div><div class="subtitle-cta"><?php echo esc_html(ibp_get_val('ibp_wa_metin_mobil', 'Temsilci Bağlan')); ?></div></div>
                <div class="cta-text-yesil nogoster"><div class="title-cta">Whatsapp</div><div class="subtitle-cta"><?php echo esc_html(ibp_get_val('ibp_wa_metin_masaustu', 'Bilgi Alın')); ?></div></div>
                <div class="cta-icon-yesil">
                    <?php if($badge_st): ?><span class="ibp-badge">1</span><?php endif; ?>
                    <img src="<?php echo esc_url(ibp_get_val('ibp_icon_wa', plugin_dir_url(__FILE__) . 'img/whatsapp.webp')); ?>" alt="WA" width="50" height="50">
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if($ma_st): ?>
    <div id="ibp-ma-modal" class="ibp-modal-overlay">
        <div class="ibp-modal-content">
            <span class="ibp-modal-close">&times;</span>
            <div class="ibp-modal-header-icon">💬</div>
            <h3>Hangi departmanla görüşmek istersiniz?</h3>
            <a href="#" class="ibp-dept-btn" data-no="<?php echo esc_attr(ibp_get_val('ibp_dept1_cc','90').ibp_get_val('ibp_dept1_no','')); ?>"><i>📞</i> <span><?php echo esc_html(ibp_get_val('ibp_dept1_name', 'Satış Departmanı')); ?></span></a>
            <a href="#" class="ibp-dept-btn" data-no="<?php echo esc_attr(ibp_get_val('ibp_dept2_cc','90').ibp_get_val('ibp_dept2_no','')); ?>"><i>🛠️</i> <span><?php echo esc_html(ibp_get_val('ibp_dept2_name', 'Teknik Destek')); ?></span></a>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    $cerez_durum = get_option('ibp_cerez_durum', 'acik');
    if ( $cerez_durum == 'acik' ) : 
        $cerez_metin       = esc_html(ibp_get_val('ibp_cerez_metin', 'Size daha iyi bir deneyim sunmak için çerezler kullanıyoruz. Sayfamızı kullanarak bunu kabul etmiş olursunuz.'));
        $cerez_link_metin  = esc_html(ibp_get_val('ibp_cerez_link_metin', 'Çerez Politikası'));
        $cerez_buton_metin = esc_html(ibp_get_val('ibp_cerez_buton_metin', 'Tamam'));
        $cerez_sayfa_id    = get_option('ibp_cerez_sayfa_id', 0);
        $cerez_link_url    = !empty($cerez_sayfa_id) ? get_permalink($cerez_sayfa_id) : '#';
    ?>
    <div id="ibp-cerez-kutu" class="ibp-cookie-banner">
        <div class="ibp-cookie-text">
            <span>🍪 <?php echo $cerez_metin; ?></span>
            <?php if ( !empty($cerez_sayfa_id) ) : ?>
                <a href="<?php echo esc_url($cerez_link_url); ?>" class="ibp-cookie-link" target="_blank"><?php echo $cerez_link_metin; ?></a>
            <?php endif; ?>
        </div>
        <button id="ibp-cerez-kabul" class="ibp-cookie-btn"><?php echo $cerez_buton_metin; ?></button>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Lazy Load Gecikmeli Yükleme
        var btnContainer = document.getElementById('ibp-buttons-container');
        if (btnContainer && btnContainer.classList.contains('ibp-lazy-hidden')) {
            var showBtns = function() {
                btnContainer.classList.remove('ibp-lazy-hidden');
                window.removeEventListener('scroll', showBtns);
            };
            window.addEventListener('scroll', showBtns);
            setTimeout(showBtns, 2500); // 2.5sn sonra otomatik göster
        }

        // 2. Çerez Uyarısı
        var cerezKutu = document.getElementById("ibp-cerez-kutu");
        var kabulBtn = document.getElementById("ibp-cerez-kabul");
        if (cerezKutu && kabulBtn) {
            if (document.cookie.indexOf("ibp_cerez_onay=1") === -1) {
                setTimeout(function() { cerezKutu.classList.add("goster"); }, 500);
            }
            kabulBtn.addEventListener("click", function() {
                var d = new Date(); d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
                document.cookie = "ibp_cerez_onay=1;expires=" + d.toUTCString() + ";path=/";
                cerezKutu.classList.remove("goster");
            });
        }

        var waLink = document.getElementById('ibp-wa-link');
        var telLink = document.getElementById('ibp-tel-link');
        var tooltip = document.getElementById('ibp-wa-tooltip');
        var isMa = <?php echo $ma_st ? 'true' : 'false'; ?>;
        var modal = document.getElementById('ibp-ma-modal');
        var finalNo = "<?php echo $whatsapp_numara; ?>";
        var rawMsg = "<?php echo $wa_template; ?>";
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

        if (waLink) {
            // 3. Mesai Saati Kontrolü (JS ile anlık)
            var isOffHours = false;
            var bhStatus = "<?php echo esc_js(get_option('ibp_bh_status', 'kapali')); ?>";
            if (bhStatus === 'acik') {
                var d = new Date();
                var day = d.getDay(); // 0: Pazar, 1-5: Hafta İçi, 6: Cumartesi
                var nowVal = d.getHours() * 60 + d.getMinutes();
                var startStr = 'kapali', endStr = 'kapali';
                
                if (day >= 1 && day <= 5) {
                    startStr = "<?php echo esc_js(get_option('ibp_bh_weekday_start', '09:00')); ?>";
                    endStr = "<?php echo esc_js(get_option('ibp_bh_weekday_end', '18:00')); ?>";
                } else if (day === 6) {
                    startStr = "<?php echo esc_js(get_option('ibp_bh_saturday_start', 'kapali')); ?>";
                    endStr = "<?php echo esc_js(get_option('ibp_bh_saturday_end', 'kapali')); ?>";
                } else {
                    startStr = "<?php echo esc_js(get_option('ibp_bh_sunday_start', 'kapali')); ?>";
                    endStr = "<?php echo esc_js(get_option('ibp_bh_sunday_end', 'kapali')); ?>";
                }

                if (startStr === 'kapali' || endStr === 'kapali') { isOffHours = true; } 
                else {
                    var sParts = startStr.split(':'); var eParts = endStr.split(':');
                    var startVal = parseInt(sParts[0]) * 60 + parseInt(sParts[1]);
                    var endVal = parseInt(eParts[0]) * 60 + parseInt(eParts[1]);
                    if (nowVal < startVal || nowVal > endVal) { isOffHours = true; }
                }
            }

            if (isOffHours) { 
                rawMsg = "<?php echo esc_js(ibp_get_val('ibp_bh_off_msg', 'Şu an mesai saatleri dışındayız, mesajınızı bırakın, en kısa sürede dönüş yapalım')); ?>"; 
                isMa = false; 
            }

            // 4. UTM Kontrolü
            var utmKey = "<?php echo esc_js(get_option('ibp_utm_key', '')); ?>";
            var utmVal = "<?php echo esc_js(get_option('ibp_utm_val', '')); ?>";
            var utmNo  = "<?php echo esc_js(get_option('ibp_utm_no', '')); ?>";
            var urlParams = new URLSearchParams(window.location.search);
            
            if (utmKey && utmVal && utmNo && urlParams.get(utmKey) === utmVal) {
                finalNo = utmNo;
                isMa = false; 
            }

            // 5. Kampanya Modu Kontrolü
            var campStatus = <?php echo $camp_st ? 'true' : 'false'; ?>;
            if(campStatus && !isOffHours) { 
                var today = new Date().toISOString().split('T')[0];
                var campStart = "<?php echo esc_js(get_option('ibp_camp_start', '')); ?>";
                var campEnd = "<?php echo esc_js(get_option('ibp_camp_end', '')); ?>";
                if (today >= campStart && today <= campEnd) {
                    finalNo = "<?php echo esc_js(ibp_get_val('ibp_camp_cc', '90') . ibp_get_val('ibp_camp_no', '')); ?>";
                    rawMsg = "<?php echo esc_js(ibp_get_val('ibp_camp_msg', '')); ?>";
                    isMa = false; 
                }
            }

            // 6. Dinamik Etiketler & WooCommerce Kaynak
            var ref = document.referrer;
            var kaynak = "Doğrudan";
            if (urlParams.has('gclid')) { kaynak = "Google Reklamları"; }
            else if (ref.includes('google')) { kaynak = "Google Arama (Organik)"; }
            else if (ref.includes('facebook') || ref.includes('fbclid')) { kaynak = "Facebook"; }
            else if (ref.includes('instagram')) { kaynak = "Instagram"; }
            else if (ref !== "") { kaynak = "Farklı Site: " + new URL(ref).hostname; }
            if (urlParams.get('utm_source')) { kaynak += " (" + urlParams.get('utm_source') + ")"; }

            var finalMsg = rawMsg.replace(/\[sayfa_adi\]/gi, document.title)
                                 .replace(/\[url\]/gi, window.location.href)
                                 .replace(/\[kaynak\]/gi, kaynak)
                                 .replace(/\[urun_adi\]/gi, "<?php echo esc_js($urun_adi); ?>")
                                 .replace(/\[urun_fiyati\]/gi, "<?php echo esc_js($urun_fiyat); ?>")
                                 .replace(/\[stok_kodu\]/gi, "<?php echo esc_js($stok_kodu); ?>");

            var encodeLink = "whatsapp://send?phone=" + finalNo + "&text=" + encodeURIComponent(finalMsg);
            
            // Eğer Çoklu Temsilci "Kapalıysa" veya "Geçersiz Kılındıysa" normal linki bas.
            if(!isMa) {
                waLink.href = encodeLink;
            }

            // 7. Çoklu Temsilci Modal İşlemleri
            if(isMa && modal) {
                // Modalı Kapatma Butonu
                document.querySelector('.ibp-modal-close').addEventListener('click', function(e){ 
                    e.preventDefault();
                    modal.classList.remove('goster'); 
                });
                
                // Modalı Boşluğa Tıklayarak Kapatma
                modal.addEventListener('click', function(e){
                    if(e.target === modal) { modal.classList.remove('goster'); }
                });

                // Departman Butonlarına Dinamik Mesajı Atama
                var deptBtns = document.querySelectorAll('.ibp-dept-btn');
                deptBtns.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var dNo = this.getAttribute('data-no');
                        window.location.href = "whatsapp://send?phone=" + dNo + "&text=" + encodeURIComponent(finalMsg);
                    });
                });
            }
        }

        // 8. AJAX İstatistik Takip Fonksiyonu
        function trackClick(type) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", ajaxurl, true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send("action=ibp_track_click&click_type=" + type);
        }

        // Tıklama Olayları Listener
        if(telLink) { telLink.addEventListener('click', function(){ trackClick('tel'); }); }
        if(waLink) {
            waLink.addEventListener('click', function(e) {
                trackClick('wa');
                if(isMa) {
                    // Çoklu Temsilci aktifse WhatsApp'a direkt gitmesini engelle ve modalı aç
                    e.preventDefault();
                    modal.classList.add('goster');
                }
            });
        }

        // 9. Etkileşim: Hoşgeldin Balonu (5 sn sonra)
        if(tooltip && <?php echo $tt_st ? 'true' : 'false'; ?>) {
            setTimeout(function(){ tooltip.classList.add('goster'); setTimeout(function(){ tooltip.classList.remove('goster'); }, 8000); }, 5000);
        }

        // 10. Etkileşim: Çıkış Niyeti (Exit-Intent)
        if(tooltip && <?php echo $ei_st ? 'true' : 'false'; ?>) {
            var exitFired = false;
            document.addEventListener("mouseout", function(e) {
                if (e.clientY < 10 && !exitFired) {
                    exitFired = true;
                    tooltip.innerHTML = "<?php echo $ei_msg; ?>";
                    tooltip.classList.add('goster');
                }
            });
        }
    });
    </script>
    <?php
}
