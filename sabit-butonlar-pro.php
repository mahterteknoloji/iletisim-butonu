<?php
/**
 * Plugin Name: Sabit İletişim Butonları (V9.7 SaaS Edition)
 * Description: İletişim butonları, Çoklu Temsilci (Dinamik Departmanlar & FontAwesome İkon Seçici), Woo Etiketleri, Exit-Intent, UTM, Mesai Saatleri.
 * Version: 9.7
 * Author: Destek Asistanı
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
 * 1. AJAX İSTATİSTİK TAKİP ALTYAPISI
 * ========================================================================== */
add_action('wp_ajax_sib_track_click', 'sib_track_click_callback');
add_action('wp_ajax_nopriv_sib_track_click', 'sib_track_click_callback');
function sib_track_click_callback() {
    $type = isset($_POST['click_type']) ? sanitize_text_field($_POST['click_type']) : '';
    if (in_array($type, ['wa', 'tel'])) {
        $date = wp_date('Y-m-d');
        $stats = get_option('sib_click_stats', array());
        if (!isset($stats[$date])) { $stats[$date] = array('wa' => 0, 'tel' => 0); }
        $stats[$date][$type]++;
        update_option('sib_click_stats', $stats);
    }
    wp_die();
}

/* ==========================================================================
 * 2. YÖNETİM PANELİ VE SCRIPTLER
 * ========================================================================== */
add_action('admin_menu', 'sib_iletisim_menu_ekle');
function sib_iletisim_menu_ekle() {
    $page = add_menu_page('İletişim & Dönüşüm PRO', 'İletişim PRO', 'manage_options', 'sib-iletisim-ayarlari', 'sib_ayarlar_sayfasi_html', 'dashicons-chart-line', 100);
    add_action("admin_print_scripts-{$page}", 'sib_admin_scripts');
}

function sib_admin_scripts() {
    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
    
    // FontAwesome for Admin
    wp_enqueue_style('font-awesome-5', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}

// Frontend FontAwesome
add_action('wp_enqueue_scripts', 'sib_frontend_scripts');
function sib_frontend_scripts() {
    wp_enqueue_style('font-awesome-5', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}

function sib_sanitize_pages($val) { return is_array($val) ? implode(',', array_map('intval', $val)) : sanitize_text_field($val); }
function sib_time_options($selected) {
    $html = '<option value="kapali" ' . selected($selected, 'kapali', false) . '>Kapalı</option>';
    for ($h = 0; $h < 24; $h++) { foreach (['00', '30'] as $m) { $time = sprintf('%02d:%s', $h, $m); $html .= '<option value="'.$time.'" ' . selected($selected, $time, false) . '>'.$time.'</option>'; } }
    return $html;
}
function sib_get_val($key, $default) { $val = get_option($key); return ($val === false || trim($val) === '') ? $default : $val; }

add_action('admin_init', 'sib_ayarlari_kaydet');
function sib_ayarlari_kaydet() {
    $fields = array(
        'sib_tel_status', 'sib_wa_status', 'sib_telefon_gosterim', 'sib_tel_cc', 'sib_tel_no', 'sib_wa_cc', 'sib_wa_no', 'sib_wa_metin_mobil', 'sib_wa_metin_masaustu',
        'sib_color_tel', 'sib_color_wa', 'sib_color_title', 'sib_color_subtitle', 'sib_icon_tel', 'sib_icon_wa', 'sib_color_cookie_bg', 'sib_color_cookie_text', 'sib_color_cookie_btn_bg', 'sib_color_cookie_btn_text',
        'sib_wa_template', 'sib_bh_status', 'sib_bh_off_msg', 'sib_bh_weekday_start', 'sib_bh_weekday_end', 'sib_bh_saturday_start', 'sib_bh_saturday_end', 'sib_bh_sunday_start', 'sib_bh_sunday_end', 'sib_woo_visibility', 
        'sib_lazy_load', 'sib_utm_key', 'sib_utm_val', 'sib_utm_no', 'sib_track_tel', 'sib_track_wa',
        'sib_cerez_durum', 'sib_cerez_metin', 'sib_cerez_link_metin', 'sib_cerez_sayfa_id', 'sib_cerez_buton_metin',
        'sib_badge_status', 'sib_tooltip_status', 'sib_tooltip_msg', 'sib_exit_intent_status', 'sib_exit_intent_msg',
        'sib_ma_status', 'sib_departments',
        'sib_camp_status', 'sib_camp_start', 'sib_camp_end', 'sib_camp_cc', 'sib_camp_no', 'sib_camp_msg'
    );
    foreach($fields as $field) { register_setting('sib_ayarlar_grubu', $field); }
    register_setting('sib_ayarlar_grubu', 'sib_hide_pages', 'sib_sanitize_pages');
}

function sib_ayarlar_sayfasi_html() {
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
        <h1>Sabit İletişim & Dönüşüm Optimizasyonu (SaaS Sürüm) 🚀</h1>
        <?php settings_errors(); ?>
        
        <h2 class="nav-tab-wrapper" id="sib-tabs">
            <a href="#" class="nav-tab nav-tab-active" data-target="tab-temel">📱 Numaralar & Akıllı Özellikler</a>
            <a href="#" class="nav-tab" data-target="tab-etkilesim">✨ Etkileşim & Temsilci</a>
            <a href="#" class="nav-tab" data-target="tab-kampanya">⏱️ Kampanya & UTM</a>
            <a href="#" class="nav-tab" data-target="tab-renk">🎨 Tasarım & Çerez</a>
            <a href="#" class="nav-tab" data-target="tab-istatistik">📊 İstatistikler & Takip</a>
        </h2>
        
        <form method="post" action="options.php" style="background:#fff; padding:20px; border:1px solid #ccd0d4; margin-top:15px;">
            <?php settings_fields('sib_ayarlar_grubu'); ?>
            
            <div id="tab-temel" class="sib-tab-content">
                <h3>İletişim Numaraları ve Metinleri</h3>
    <table class="form-table">
        <tr valign="top"><th scope="row">Arama Butonu Durumu</th>
        <td>
            <select name="sib_tel_status">
                <option value="acik" <?php selected(get_option('sib_tel_status', 'acik'), 'acik'); ?>>Açık Göster</option>
                <option value="kapali" <?php selected(get_option('sib_tel_status', 'acik'), 'kapali'); ?>>Gizle</option>
            </select>
        </td></tr>
        <tr valign="top"><th scope="row">WhatsApp Butonu Durumu</th>
        <td>
            <select name="sib_wa_status">
                <option value="acik" <?php selected(get_option('sib_wa_status', 'acik'), 'acik'); ?>>Açık Göster</option>
                <option value="kapali" <?php selected(get_option('sib_wa_status', 'acik'), 'kapali'); ?>>Gizle</option>
            </select>
        </td></tr>

                    <tr valign="top"><th scope="row">Görünen Telefon No:</th>
                    <td><input type="text" name="sib_telefon_gosterim" value="<?php echo esc_attr(sib_get_val('sib_telefon_gosterim', '0532 123 45 67')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Arama Linki İçin No:</th>
                    <td>
                        <select name="sib_tel_cc" class="sib-country-select" style="width:250px;">
                            <?php foreach($ulkeler as $u): ?><option value="+<?php echo $u['c']; ?>" <?php selected(sib_get_val('sib_tel_cc', '+90'), '+'.$u['c']); ?>><?php echo $u['tr']; ?> (+<?php echo $u['c']; ?>)</option><?php endforeach; ?>
                        </select>
                        <input type="text" name="sib_tel_no" value="<?php echo esc_attr(sib_get_val('sib_tel_no', '5321234567')); ?>" class="regular-text" style="width: 200px;" />
                    </td></tr>
                    <tr valign="top"><th scope="row">WhatsApp Numarası:</th>
                    <td>
                        <select name="sib_wa_cc" class="sib-country-select" style="width:250px;">
                            <?php foreach($ulkeler as $u): ?><option value="<?php echo $u['c']; ?>" <?php selected(sib_get_val('sib_wa_cc', '90'), $u['c']); ?>><?php echo $u['tr']; ?> (+<?php echo $u['c']; ?>)</option><?php endforeach; ?>
                        </select>
                        <input type="text" name="sib_wa_no" value="<?php echo esc_attr(sib_get_val('sib_wa_no', '5321234567')); ?>" class="regular-text" style="width: 200px;" />
                    </td></tr>
                    <tr valign="top"><th scope="row">WA Metni (Mobil):</th>
                    <td><input type="text" name="sib_wa_metin_mobil" value="<?php echo esc_attr(sib_get_val('sib_wa_metin_mobil', 'Temsilci Bağlan')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">WA Metni (Masaüstü):</th>
                    <td><input type="text" name="sib_wa_metin_masaustu" value="<?php echo esc_attr(sib_get_val('sib_wa_metin_masaustu', 'Bilgi Alın')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">WA Şablonu:<br><small>Etiketler: [sayfa_adi], [url], [kaynak], [urun_adi], [urun_fiyati], [stok_kodu]</small></th>
                    <td><textarea name="sib_wa_template" rows="4" class="large-text"><?php echo esc_textarea(sib_get_val('sib_wa_template', 'Merhaba, ürünleriniz hakkında bilgi almak istiyorum.')); ?></textarea></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Görünürlük & Mesai Saatleri</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Gizlenecek Sayfalar:</th>
                    <td>
                        <?php 
                        $hidden_pages = get_option('sib_hide_pages', '');
                        $hidden_pages_arr = !empty($hidden_pages) ? explode(',', $hidden_pages) : array();
                        $all_pages = get_pages();
                        ?>
                        <select name="sib_hide_pages[]" class="sib-page-list" multiple="multiple" style="width: 100%; max-width: 500px;">
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
                        <select name="sib_woo_visibility">
                            <option value="all" <?php selected(get_option('sib_woo_visibility', 'all'), 'all'); ?>>Standart (Her Yerde Göster)</option>
                            <option value="hide_on_prod" <?php selected(get_option('sib_woo_visibility', 'all'), 'hide_on_prod'); ?>>Ürün Detay Sayfalarında Gizle</option>
                            <option value="only_on_prod" <?php selected(get_option('sib_woo_visibility', 'all'), 'only_on_prod'); ?>>SADECE Ürün Detay Sayfalarında Göster</option>
                        </select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Mesai Saatleri Durumu:</th>
                    <td>
                        <select name="sib_bh_status">
                            <option value="kapali" <?php selected(get_option('sib_bh_status', 'kapali'), 'kapali'); ?>>Kapalı (7/24 Her Zaman Aktif)</option>
                            <option value="acik" <?php selected(get_option('sib_bh_status', 'kapali'), 'acik'); ?>>Açık (Özel Saatlerde Aktif)</option>
                        </select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Hafta İçi (Pzt - Cuma):</th>
                    <td>
                        <select name="sib_bh_weekday_start"><?php echo sib_time_options(get_option('sib_bh_weekday_start', '09:00')); ?></select> - 
                        <select name="sib_bh_weekday_end"><?php echo sib_time_options(get_option('sib_bh_weekday_end', '18:00')); ?></select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Cumartesi:</th>
                    <td>
                        <select name="sib_bh_saturday_start"><?php echo sib_time_options(get_option('sib_bh_saturday_start', 'kapali')); ?></select> - 
                        <select name="sib_bh_saturday_end"><?php echo sib_time_options(get_option('sib_bh_saturday_end', 'kapali')); ?></select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Pazar:</th>
                    <td>
                        <select name="sib_bh_sunday_start"><?php echo sib_time_options(get_option('sib_bh_sunday_start', 'kapali')); ?></select> - 
                        <select name="sib_bh_sunday_end"><?php echo sib_time_options(get_option('sib_bh_sunday_end', 'kapali')); ?></select>
                    </td></tr>
                    <tr valign="top"><th scope="row">Mesai Dışı WA Mesajı:</th>
                    <td><textarea name="sib_bh_off_msg" rows="2" class="large-text"><?php echo esc_textarea(sib_get_val('sib_bh_off_msg', 'Şu an mesai saatleri dışındayız, mesajınızı bırakın, en kısa sürede dönüş yapalım')); ?></textarea></td></tr>
                </table>
            </div>

            <div id="tab-etkilesim" class="sib-tab-content" style="display:none;">
                <h3>Etkileşim (Dönüşüm Artırıcılar)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Bildirim Rozeti (Badge):</th>
                    <td><label><input type="checkbox" name="sib_badge_status" value="yes" <?php checked(get_option('sib_badge_status', 'no'), 'yes'); ?> /> WA İkonuna "1" okunmamış mesaj rozeti ekle</label></td></tr>
                    <tr valign="top"><th scope="row">Karşılama Balonu:</th>
                    <td><label><input type="checkbox" name="sib_tooltip_status" value="yes" <?php checked(get_option('sib_tooltip_status', 'no'), 'yes'); ?> /> 5 saniye sonra WA ikonunun yanında balon çıkar</label></td></tr>
                    <tr valign="top"><th scope="row">Balon Mesajı:</th>
                    <td><input type="text" name="sib_tooltip_msg" value="<?php echo esc_attr(sib_get_val('sib_tooltip_msg', 'Size nasıl yardımcı olabilirim? 👋')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Çıkış Niyeti (Exit-Intent):</th>
                    <td><label><input type="checkbox" name="sib_exit_intent_status" value="yes" <?php checked(get_option('sib_exit_intent_status', 'no'), 'yes'); ?> /> Kullanıcı sekmeyi kapatmaya yönelirse balon çıkar</label></td></tr>
                    <tr valign="top"><th scope="row">Çıkış Niyeti Mesajı:</th>
                    <td><input type="text" name="sib_exit_intent_msg" value="<?php echo esc_attr(sib_get_val('sib_exit_intent_msg', 'Siparişinizi tamamlamak için yardıma ihtiyacınız var mı? 🎁')); ?>" class="large-text" /></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Çoklu Temsilci Modu (Dinamik Departmanlar)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Çoklu Temsilci:</th>
                    <td><label><input type="checkbox" name="sib_ma_status" value="yes" <?php checked(get_option('sib_ma_status', 'no'), 'yes'); ?> /> Departman Seçim Kutusunu Aktif Et</label></td></tr>
                    <tr valign="top"><th scope="row">Departmanlar:</th>
                    <td>
                        <div id="sib-dept-container">
                            <?php 
                            $depts = get_option('sib_departments', array());
                            if(empty($depts)) {
                                $depts = array(
                                    array('name' => 'Satış Departmanı', 'cc' => '90', 'no' => '5321111111', 'icon' => 'fas fa-shopping-cart'),
                                    array('name' => 'Teknik Destek', 'cc' => '90', 'no' => '5322222222', 'icon' => 'fas fa-tools')
                                );
                            }
                            foreach($depts as $index => $dept):
                            ?>
                            <div class="sib-dept-row" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 8px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" name="sib_departments[<?php echo $index; ?>][name]" value="<?php echo esc_attr($dept['name']); ?>" placeholder="Departman Adı" style="width: 100%;" />
                                    <input type="text" name="sib_departments[<?php echo $index; ?>][cc]" value="<?php echo esc_attr($dept['cc']); ?>" placeholder="Kod" style="width: 100%;" />
                                    <input type="text" name="sib_departments[<?php echo $index; ?>][no]" value="<?php echo esc_attr($dept['no']); ?>" placeholder="Numara" style="width: 100%;" />
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="sib-icon-preview" style="width: 40px; height: 40px; background: #fff; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px; border-radius: 4px;">
                                        <i class="<?php echo esc_attr($dept['icon']); ?>"></i>
                                    </div>
                                    <input type="text" class="sib-fa-input" name="sib_departments[<?php echo $index; ?>][icon]" value="<?php echo esc_attr($dept['icon']); ?>" placeholder="Örn: fas fa-user" style="flex: 1;" />
                                    <button type="button" class="button sib-fa-picker-btn">İkon Seç</button>
                                    <button type="button" class="button sib-remove-dept" style="color: red; border-color: red;">Departmanı Sil</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="sib-add-dept" class="button button-secondary" style="margin-top: 10px;">+ Yeni Departman Ekle</button>
                    </td></tr>
                </table>
            </div>

            <!-- FontAwesome Picker Modal (Admin Only) -->
            <div id="sib-fa-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:999999; align-items:center; justify-content:center;">
                <div style="background:#fff; width:90%; max-width:600px; padding:20px; border-radius:10px; position:relative; max-height:80vh; overflow-y:auto;">
                    <span id="sib-fa-modal-close" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer;">&times;</span>
                    <h3>İkon Seçin</h3>
                    <input type="text" id="sib-fa-search" placeholder="İkon ara... (örn: phone, user, chat)" style="width:100%; padding:10px; margin-bottom:20px; border:1px solid #ddd; border-radius:5px;" />
                    <div id="sib-fa-list" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap:10px; text-align:center;">
                        <!-- Icons will be listed here via JS -->
                    </div>
                </div>
            </div>

            <div id="tab-kampanya" class="sib-tab-content" style="display:none;">
                <h3>Kampanya Modu</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Kampanya Modu:</th>
                    <td><label><input type="checkbox" name="sib_camp_status" value="yes" <?php checked(get_option('sib_camp_status', 'no'), 'yes'); ?> /> Aktif Et</label></td></tr>
                    <tr valign="top"><th scope="row">Tarih Aralığı:</th>
                    <td>Başlangıç: <input type="date" name="sib_camp_start" value="<?php echo esc_attr(get_option('sib_camp_start', wp_date('Y-m-d'))); ?>" /> 
                        Bitiş: <input type="date" name="sib_camp_end" value="<?php echo esc_attr(get_option('sib_camp_end', wp_date('Y-m-d', strtotime('+7 days')))); ?>" /></td></tr>
                    <tr valign="top"><th scope="row">Kampanya Numarası:</th>
                    <td>
                        <input type="text" name="sib_camp_cc" value="<?php echo esc_attr(sib_get_val('sib_camp_cc', '90')); ?>" style="width:60px;" />
                        <input type="text" name="sib_camp_no" value="<?php echo esc_attr(sib_get_val('sib_camp_no', '5329999999')); ?>" placeholder="Geçici numara" />
                    </td></tr>
                    <tr valign="top"><th scope="row">Kampanya Özel Mesajı:</th>
                    <td><textarea name="sib_camp_msg" rows="3" class="large-text"><?php echo esc_textarea(sib_get_val('sib_camp_msg', 'Merhaba, Efsane Cuma indirimleri hakkında bilgi almak istiyorum!')); ?></textarea></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>UTM Odaklı Bölgesel Yönlendirme</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">URL Parametresi (Örn: kampanya):</th>
                    <td><input type="text" name="sib_utm_key" value="<?php echo esc_attr(get_option('sib_utm_key', '')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Eşleşecek Değer (Örn: maras):</th>
                    <td><input type="text" name="sib_utm_val" value="<?php echo esc_attr(get_option('sib_utm_val', '')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Yönlendirilecek Farklı WA No:<br><small>(Boşluksuz. Örn: 905554443322)</small></th>
                    <td><input type="text" name="sib_utm_no" value="<?php echo esc_attr(get_option('sib_utm_no', '')); ?>" class="regular-text" /></td></tr>
                </table>
            </div>

            <div id="tab-renk" class="sib-tab-content" style="display:none;">
                <h3>İletişim Butonları Tasarımı</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Arama Rengi:</th><td><input type="text" name="sib_color_tel" value="<?php echo esc_attr(sib_get_val('sib_color_tel', '#5d2e8f')); ?>" class="sib-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">WA Rengi:</th><td><input type="text" name="sib_color_wa" value="<?php echo esc_attr(sib_get_val('sib_color_wa', '#82bb26')); ?>" class="sib-color-picker" /></td></tr>
                            <tr valign="top"><th scope="row">Başlık (Title) Rengi</th>
        <td><input type="text" name="sib_color_title" value="<?php echo esc_attr(sib_get_val('sib_color_title', '#ffffff')); ?>" class="sib-color-picker" /></td></tr>
        <tr valign="top"><th scope="row">Alt Başlık (Subtitle) Rengi</th>
        <td><input type="text" name="sib_color_subtitle" value="<?php echo esc_attr(sib_get_val('sib_color_subtitle', '#ffffff')); ?>" class="sib-color-picker" /></td></tr>
        <tr valign="top"><th scope="row">Arama İkonu:</th><td><input type="text" name="sib_icon_tel" id="sib_icon_tel" value="<?php echo esc_attr(sib_get_val('sib_icon_tel', $default_tel_icon)); ?>" class="regular-text" /><button type="button" class="button sib-upload-btn" data-target="#sib_icon_tel">Seç</button></td></tr>
                    <tr valign="top"><th scope="row">WhatsApp İkonu:</th><td><input type="text" name="sib_icon_wa" id="sib_icon_wa" value="<?php echo esc_attr(sib_get_val('sib_icon_wa', $default_wa_icon)); ?>" class="regular-text" /><button type="button" class="button sib-upload-btn" data-target="#sib_icon_wa">Seç</button></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Çerez Uyarısı Yönetimi</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Uyarı Durumu:</th>
                    <td><select name="sib_cerez_durum"><option value="acik" <?php selected(get_option('sib_cerez_durum', 'acik'), 'acik'); ?>>Açık (Gösterilsin)</option><option value="kapali" <?php selected(get_option('sib_cerez_durum', 'acik'), 'kapali'); ?>>Kapalı (Gizlensin)</option></select></td></tr>
                    <tr valign="top"><th scope="row">Kutu Arkaplan Rengi:</th><td><input type="text" name="sib_color_cookie_bg" value="<?php echo esc_attr(sib_get_val('sib_color_cookie_bg', '#ffffff')); ?>" class="sib-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Metin Rengi:</th><td><input type="text" name="sib_color_cookie_text" value="<?php echo esc_attr(sib_get_val('sib_color_cookie_text', '#333333')); ?>" class="sib-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Buton Arkaplan Rengi:</th><td><input type="text" name="sib_color_cookie_btn_bg" value="<?php echo esc_attr(sib_get_val('sib_color_cookie_btn_bg', '#82bb26')); ?>" class="sib-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Buton Yazı Rengi:</th><td><input type="text" name="sib_color_cookie_btn_text" value="<?php echo esc_attr(sib_get_val('sib_color_cookie_btn_text', '#ffffff')); ?>" class="sib-color-picker" /></td></tr>
                    <tr valign="top"><th scope="row">Ana Uyarı Metni:</th><td><input type="text" name="sib_cerez_metin" value="<?php echo esc_attr(sib_get_val('sib_cerez_metin', 'Size daha iyi bir deneyim sunmak için çerezler kullanıyoruz. Sayfamızı kullanarak bunu kabul etmiş olursunuz.')); ?>" class="large-text" /></td></tr>
                    <tr valign="top"><th scope="row">Politika Linki Yazısı:</th><td><input type="text" name="sib_cerez_link_metin" value="<?php echo esc_attr(sib_get_val('sib_cerez_link_metin', 'Çerez Politikası')); ?>" class="regular-text" /></td></tr>
                    <tr valign="top"><th scope="row">Politika Sayfası:</th><td><?php wp_dropdown_pages(array('name' => 'sib_cerez_sayfa_id', 'echo' => 1, 'show_option_none' => '— Sayfa Seçin —', 'option_none_value' => '0', 'selected' => get_option('sib_cerez_sayfa_id', 0))); ?></td></tr>
                    <tr valign="top"><th scope="row">Kabul Et Butonu Metni:</th><td><input type="text" name="sib_cerez_buton_metin" value="<?php echo esc_attr(sib_get_val('sib_cerez_buton_metin', 'Tamam')); ?>" class="regular-text" /></td></tr>
                </table>
            </div>

            <div id="tab-istatistik" class="sib-tab-content" style="display:none;">
                <h3>Google Core Web Vitals (Hız Optimizasyonu)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Lazy Load (Gecikmeli Yükleme):</th>
                    <td><label><input type="checkbox" name="sib_lazy_load" value="yes" <?php checked(get_option('sib_lazy_load', 'yes'), 'yes'); ?> /> Gecikmeli yüklemeyi aktif et (SEO ve Hız için önerilir)</label></td></tr>
                </table>
                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Dönüşüm Takibi (Pixel/Gtag)</h3>
                <table class="form-table">
                    <tr valign="top"><th scope="row">Arama Butonu Takip Kodu:<br><small>Örn: gtag_report_conversion('tel:0546...');</small></th>
                    <td><textarea name="sib_track_tel" rows="2" class="large-text"><?php echo esc_textarea(get_option('sib_track_tel', '')); ?></textarea></td></tr>
                    <tr valign="top"><th scope="row">WhatsApp Butonu Takip Kodu:<br><small>Örn: fbq('track', 'Contact');</small></th>
                    <td><textarea name="sib_track_wa" rows="2" class="large-text"><?php echo esc_textarea(get_option('sib_track_wa', '')); ?></textarea></td></tr>
                </table>

                <hr style="margin-top: 30px; margin-bottom: 20px;">
                
                <h3>Son 7 Günün Tıklama Analizi</h3>
                <table class="wp-list-table widefat fixed striped" style="max-width:600px;">
                    <thead><tr><th>Tarih</th><th>WhatsApp</th><th>Telefon</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php
                        $stats = get_option('sib_click_stats', array());
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
        $('.sib-color-picker').wpColorPicker();
        $('.nav-tab').click(function(e) { e.preventDefault(); $('.nav-tab').removeClass('nav-tab-active'); $('.sib-tab-content').hide(); $(this).addClass('nav-tab-active'); $('#' + $(this).data('target')).show(); });
        
        $(document).on('click', '.sib-upload-btn', function(e) { 
            e.preventDefault(); 
            var targetInput = $($(this).data('target')); 
            var imageUploader = wp.media({ title: 'İkon Seç', button: { text: 'Bunu Kullan' }, multiple: false }).on('select', function() { 
                targetInput.val(imageUploader.state().get('selection').first().toJSON().url); 
            }).open(); 
        });

        $('.sib-country-select').select2({ placeholder: 'Ülke Arayın' });

        // FontAwesome Picker Logic
        var currentIconTarget = null;
        var faIcons = [
            'fas fa-phone', 'fas fa-mobile-alt', 'fas fa-envelope', 'fas fa-comments', 'fas fa-headset', 
            'fas fa-user', 'fas fa-users', 'fas fa-shopping-cart', 'fas fa-info-circle', 'fas fa-question-circle',
            'fas fa-tools', 'fas fa-cog', 'fas fa-heart', 'fas fa-star', 'fas fa-gift', 'fas fa-truck',
            'fas fa-map-marker-alt', 'fas fa-clock', 'fas fa-calendar-alt', 'fas fa-exclamation-triangle',
            'fab fa-whatsapp', 'fab fa-telegram', 'fab fa-facebook-messenger', 'fab fa-instagram', 'fab fa-twitter'
        ];

        function renderFaIcons(filter = '') {
            var html = '';
            faIcons.forEach(function(icon) {
                if(icon.toLowerCase().includes(filter.toLowerCase())) {
                    html += '<div class="sib-fa-select-item" data-icon="' + icon + '" style="padding:10px; border:1px solid #eee; cursor:pointer; border-radius:5px;">' +
                            '<i class="' + icon + '" style="font-size:20px;"></i>' +
                            '</div>';
                }
            });
            $('#sib-fa-list').html(html);
        }

        $(document).on('click', '.sib-fa-picker-btn', function() {
            currentIconTarget = $(this).siblings('.sib-fa-input');
            $('#sib-fa-modal').css('display', 'flex');
            renderFaIcons();
        });

        $('#sib-fa-modal-close').click(function() { $('#sib-fa-modal').hide(); });
        $('#sib-fa-search').on('input', function() { renderFaIcons($(this).val()); });

        $(document).on('click', '.sib-fa-select-item', function() {
            var icon = $(this).data('icon');
            if(currentIconTarget) {
                currentIconTarget.val(icon);
                currentIconTarget.siblings('.sib-icon-preview').html('<i class="' + icon + '"></i>');
            }
            $('#sib-fa-modal').hide();
        });

        // Update preview on manual input
        $(document).on('input', '.sib-fa-input', function() {
            $(this).siblings('.sib-icon-preview').html('<i class="' + $(this).val() + '"></i>');
        });

        // Dinamik Departman Ekleme
        $('#sib-add-dept').click(function() {
            var index = $('.sib-dept-row').length;
            var html = '<div class="sib-dept-row" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 8px;">' +
                       '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">' +
                       '<input type="text" name="sib_departments[' + index + '][name]" value="" placeholder="Departman Adı" style="width: 100%;" /> ' +
                       '<input type="text" name="sib_departments[' + index + '][cc]" value="90" placeholder="Kod" style="width: 100%;" /> ' +
                       '<input type="text" name="sib_departments[' + index + '][no]" value="" placeholder="Numara" style="width: 100%;" /> ' +
                       '</div>' +
                       '<div style="display: flex; align-items: center; gap: 10px;">' +
                       '<div class="sib-icon-preview" style="width: 40px; height: 40px; background: #fff; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px; border-radius: 4px;">' +
                       '<i class="fas fa-comments"></i>' +
                       '</div>' +
                       '<input type="text" class="sib-fa-input" name="sib_departments[' + index + '][icon]" value="fas fa-comments" placeholder="Örn: fas fa-user" style="flex: 1;" /> ' +
                       '<button type="button" class="button sib-fa-picker-btn">İkon Seç</button>' +
                       '<button type="button" class="button sib-remove-dept" style="color: red; border-color: red;">Departmanı Sil</button>' +
                       '</div>' +
                       '</div>';
            $('#sib-dept-container').append(html);
        });

        $(document).on('click', '.sib-remove-dept', function() {
            $(this).closest('.sib-dept-row').remove();
            // İndeksleri güncelle
            $('.sib-dept-row').each(function(i) {
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if(name) $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                });
            });
        });
    });
    </script>
    <?php
}

/* ==========================================================================
 * 3. CSS KODLARI
 * ========================================================================== */
add_action('wp_head', 'sib_eklenti_css_ekle');
function sib_eklenti_css_ekle() {
    $color_tel = esc_attr(sib_get_val('sib_color_tel', '#5d2e8f'));
    $color_wa  = esc_attr(sib_get_val('sib_color_wa', '#82bb26'));
    $color_title = esc_attr(sib_get_val('sib_color_title', '#ffffff'));
    $color_subtitle = esc_attr(sib_get_val('sib_color_subtitle', '#ffffff'));
    $color_cookie_bg       = esc_attr(sib_get_val('sib_color_cookie_bg', '#ffffff'));
    $color_cookie_text     = esc_attr(sib_get_val('sib_color_cookie_text', '#333333'));
    $color_cookie_btn_bg   = esc_attr(sib_get_val('sib_color_cookie_btn_bg', '#82bb26'));
    $color_cookie_btn_text = esc_attr(sib_get_val('sib_color_cookie_btn_text', '#ffffff'));
    ?>
    <style>
        :root { 
            --sib-tel-color: <?php echo $color_tel; ?>; 
            --sib-wa-color: <?php echo $color_wa; ?>;
            --sib-title-color: <?php echo $color_title; ?>;
            --sib-subtitle-color: <?php echo $color_subtitle; ?>; 
            --sib-cookie-bg: <?php echo $color_cookie_bg; ?>;
            --sib-cookie-text: <?php echo $color_cookie_text; ?>;
            --sib-cookie-btn-bg: <?php echo $color_cookie_btn_bg; ?>;
            --sib-cookie-btn-text: <?php echo $color_cookie_btn_text; ?>;
        }

        #sib-ma-modal *, #sib-ma-modal *::before, #sib-ma-modal *::after { box-sizing: border-box; }

        /* Performans (Lazy Load) Sınıfı */
        .sib-lazy-hidden { opacity: 0 !important; pointer-events: none !important; transform: translateY(20px); }
        
        .floating-buttons{position:fixed;bottom:20px;left:0;width:100%;z-index:9999; transition: all 0.5s ease-out;}
        .cta-btn, .cta-btn-yesil {display:inline-flex;align-items:center; border-radius:50px;width:170px;height:46px;box-sizing:border-box;color:#fff;position:relative;overflow:visible; text-decoration:none !important;}
        .cta-btn {justify-content:flex-end; padding:10px 25px 10px 40px; background:var(--sib-tel-color);}
        .cta-btn-yesil {justify-content:flex-start; padding:10px 40px 10px 25px; background:var(--sib-wa-color);}
        .cta-icon, .cta-icon-yesil {
            width: 60px;
            height: 60px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            z-index:2;
        }
        .cta-icon {left:-15px; background:var(--sib-tel-color);} .cta-icon-yesil {right:-15px; background:var(--sib-wa-color);}
        .cta-icon img, .cta-icon-yesil img {width:44px; height:auto;}
        .cta-text {display:flex;flex-direction:column;width:100%;text-align:right;align-items:flex-end;z-index:2}
        .cta-text-yesil {display:flex;flex-direction:column;width:100%;text-align:left;align-items:flex-start;z-index:2}
        
        /* Metinlerin alt alta inmesini ve kaymasını engelle */
        .title-cta{font-size:.875rem;font-weight:700; white-space:nowrap; color: var(--sib-title-color);} 
        .subtitle-cta{font-size:.75rem;opacity:.9;line-height:1.5; white-space:nowrap; color: var(--sib-subtitle-color);}
        
        .nogosterme { display: none !important; }
        .nogoster { display: flex !important; }

        .cta-icon::before, .cta-icon-yesil::before {
            content:""; position:absolute; width:82px; height:82px; top:50%; left:50%; margin-top:-41px; margin-left:-41px; border-radius:50%; box-sizing:border-box; animation:nefes 2s infinite ease-in-out; opacity:0.25;
        }
        .cta-icon::before { border: 5px solid var(--sib-tel-color); } .cta-icon-yesil::before { border: 5px solid var(--sib-wa-color); }
        .cta-icon::after, .cta-icon-yesil::after {
            content:""; position:absolute; width:60px; height:60px; top:50%; left:50%; margin-top:-30px; margin-left:-30px; border-radius:50%; box-sizing:border-box; animation:ripple 2s infinite; opacity:0;
        }
        .cta-icon::after { border: 4px solid var(--sib-tel-color); } .cta-icon-yesil::after { border: 4px solid var(--sib-wa-color); }
        
        @keyframes ripple{ 0%{transform:scale(0.8);opacity:.4} 70%{opacity:.1} 100%{transform:scale(1.8);opacity:0} }
        @keyframes nefes{ 0%{transform:scale(0.9);opacity:.2} 50%{transform:scale(1.05);opacity:.4} 100%{transform:scale(0.9);opacity:.2} }

        .left-btn{position:absolute;left:20px;bottom:30px} .right-btn{position:absolute;right:20px;bottom:30px}
        
        /* Bildirim Rozeti (Badge) */
        .sib-badge {position:absolute; top:-12px; right:-5px; background:red; color:white; font-size:12px; font-weight:bold; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border-radius:50%; border:2px solid white; z-index:10;}
        
        /* Tooltip (Balon) */
        .sib-tooltip {position:absolute; top:-50px; right:0; background:white; color:#333; padding:10px 15px; border-radius:10px; font-size:13px; font-weight:bold; box-shadow:0 5px 15px rgba(0,0,0,0.1); white-space:nowrap; opacity:0; pointer-events:none; transition:all 0.3s; transform:translateY(10px); border:1px solid #eee;}
        .sib-tooltip::after {content:''; position:absolute; bottom:-6px; right:35px; width:12px; height:12px; background:white; border-bottom:1px solid #eee; border-right:1px solid #eee; transform:rotate(45deg);}
        .sib-tooltip.goster {opacity:1; transform:translateY(0); pointer-events:auto;}
        
        /* Çoklu Temsilci Popup Şık Tasarım (YENİ) */
        .sib-modal-overlay {position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(3px); z-index:99999; display:none; align-items:center; justify-content:center;}
        .sib-modal-overlay.goster {display:flex; animation: fadeIn 0.3s ease;}
        .sib-modal-content {box-sizing: border-box; background:#fff; padding:30px 25px; border-radius:24px; width:90%; max-width:380px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.15); position:relative; transform: translateY(0); animation: slideUp 0.4s ease; max-height: 90vh; overflow-y: auto;}
        .sib-modal-header-icon { width: 60px; height: 60px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: var(--sib-wa-color); font-size: 30px; }
        .sib-modal-content h3 {margin:0 0 20px 0; font-size: 1.25rem; font-weight: 700; color: #1a1a1a;}
        .sib-modal-close {position:absolute; top:15px; right:15px; width:32px; height:32px; background:#f5f5f5; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:bold; cursor:pointer; color:#666; transition:0.3s; line-height:1;}
        .sib-modal-close:hover {background:#ffebee; color:red;}
        .sib-dept-btn {box-sizing: border-box; display:flex; align-items:center; justify-content:flex-start; gap: 15px; width:100%; padding:16px 20px; margin:12px 0 0 0; background:#fff; border:2px solid #f0f0f0; border-radius:16px; color:#333; font-weight:600; font-size: 1rem; text-decoration:none; transition:all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.02); text-align: left;}
        .sib-dept-btn i {flex-shrink: 0; font-style: normal; font-size: 1.4rem; color: var(--sib-wa-color); width: 24px; display: flex; justify-content: center; align-items: center;}
        .sib-dept-btn span {flex: 1; line-height: 1.3;}
        .sib-dept-btn:hover {background:#f8fff9; border-color:var(--sib-wa-color); color:var(--sib-wa-color); transform:translateY(-2px); box-shadow: 0 6px 15px rgba(37, 211, 102, 0.15);}
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* Çerez Uyarısı CSS */
        .sib-cookie-banner { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: var(--sib-cookie-bg); color: var(--sib-cookie-text); padding: 12px 25px; border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: space-between; gap: 20px; z-index: 99999; border: 1px solid #efefef; opacity: 0; visibility: hidden; transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .sib-cookie-banner.goster { bottom: 30px; opacity: 1; visibility: visible; }
        .sib-cookie-text { margin: 0; font-size: 14px; line-height: 1.4; font-weight:500;} .sib-cookie-link { color: var(--sib-tel-color); text-decoration: underline; font-weight: 700; white-space: nowrap;}
        .sib-cookie-btn { background: var(--sib-cookie-btn-bg); color: var(--sib-cookie-btn-text); border: none; padding: 8px 25px; border-radius: 25px; cursor: pointer; font-weight: 700; font-size: 14px; transition: transform 0.3s, filter 0.3s; white-space: nowrap; }
        .sib-cookie-btn:hover { filter: brightness(0.9); transform: scale(1.05); }
        
        @media (max-width: 768px){
            .left-btn{left: 13px; bottom:15px;} .right-btn{right: 13px; bottom:15px;}
            .cta-btn {padding:5px 5px 5px 30px; width:145px; height:45px;} .cta-btn-yesil {padding:5px 30px 5px 5px; width:145px; height:45px;}
            .cta-icon, .cta-icon-yesil {
                width: 58px;
                height: 58px;
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
            .cta-icon img, .cta-icon-yesil img {width:42px;} 
            
            /* MOBİLDE BİLDİRİM ROZETİ (BADGE) YERİ DÜZELTİLDİ */
            .sib-badge {top: -15px; right: 5px;}
            
            /* SARMALAYICI BOYUTLAR GÜNCELLENDİ */
            .cta-icon::before, .cta-icon-yesil::before {
                content:""; position:absolute; width:80px; height:80px; top:50%; left:50%; margin-top:-40px; margin-left:-40px; border-radius:50%; box-sizing:border-box; animation:nefes 2s infinite ease-in-out; opacity:0.25;
            } 
            .cta-icon::after, .cta-icon-yesil::after {
                content:""; position:absolute; width:58px; height:58px; top:50%; left:50%; margin-top:-29px; margin-left:-29px; border-radius:50%; box-sizing:border-box; animation:ripple 2s infinite; opacity:0;
            }
            
            .cta-text, .cta-text-yesil { align-items: center; text-align: center; } 
            
            .nogosterme { display: flex !important; }
            .nogoster { display: none !important; }

            .sib-tooltip {right:-10px;} .sib-tooltip::after {right:25px;}
            .sib-cookie-banner { width: 90%; flex-direction: column; border-radius: 15px; text-align: center; padding: 20px; gap: 12px; }
            .sib-cookie-banner.goster { bottom: 100px; } .sib-cookie-btn { width: 100%; padding: 12px; }
        }
    </style>
    <?php
}

/* ==========================================================================
 * 4. HTML ÇIKTILARI VE CLIENT-SIDE JS MANTIĞI
 * ========================================================================== */
add_action('wp_footer', 'sib_eklenti_html_ekle');
function sib_eklenti_html_ekle() {
    
    // Gizlenen Sayfalar Kontrolü
    $hide_pages = get_option('sib_hide_pages', '');
    if (!empty($hide_pages) && is_page()) {
        $hide_arr = array_map('trim', explode(',', $hide_pages));
        if (in_array(get_the_ID(), $hide_arr)) { return; }
    }

    // WooCommerce Görünürlük Kontrolü & Veri Çekimi
    $woo_vis = get_option('sib_woo_visibility', 'all');
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
    $whatsapp_numara = esc_js(sib_get_val('sib_wa_cc', '90')) . esc_js(sib_get_val('sib_wa_no', '5321234567'));
    $wa_template     = esc_js(sib_get_val('sib_wa_template', 'Merhaba, ürünleriniz hakkında bilgi almak istiyorum.'));
    
    // İzleme Kodları
    $track_tel       = esc_attr(get_option('sib_track_tel', ''));
    $track_wa        = esc_attr(get_option('sib_track_wa', ''));

    // Lazy Load
    $is_lazy_load    = get_option('sib_lazy_load', 'yes') === 'yes' ? 'sib-lazy-hidden' : '';

    $badge_st        = get_option('sib_badge_status', 'no') === 'yes';
    $tt_st           = get_option('sib_tooltip_status', 'no') === 'yes';
    $tt_msg          = esc_html(sib_get_val('sib_tooltip_msg', 'Size nasıl yardımcı olabilirim? 👋'));
    $ei_st           = get_option('sib_exit_intent_status', 'no') === 'yes';
    $ei_msg          = esc_html(sib_get_val('sib_exit_intent_msg', 'Yardıma ihtiyacınız var mı? 🎁'));
    
    $ma_st           = get_option('sib_ma_status', 'no') === 'yes';
    $camp_st         = get_option('sib_camp_status', 'no') === 'yes';

    $telefon_linki   = esc_attr(sib_get_val('sib_tel_cc', '+90')) . esc_attr(sib_get_val('sib_tel_no', '5321234567'));
    ?>
    
    <div id="sib-buttons-container" class="floating-buttons <?php echo $is_lazy_load; ?>">
        <?php if(get_option('sib_tel_status', 'acik') !== 'kapali'): ?>
        <div class="left-btn">
            <a href="tel:<?php echo $telefon_linki; ?>" id="sib-tel-link" class="cta-btn" <?php echo !empty($track_tel) ? 'onclick="' . $track_tel . '"' : ''; ?>>
                <div class="cta-icon"><img src="<?php echo esc_url(sib_get_val('sib_icon_tel', plugin_dir_url(__FILE__) . 'img/telefon.webp')); ?>" alt="Ara"></div>
                <div class="cta-text"><div class="title-cta">Arayın</div><div class="subtitle-cta"><?php echo esc_html(sib_get_val('sib_telefon_gosterim', '0532 123 45 67')); ?></div></div>
            </a>
        </div>
        <?php endif; ?>
        <?php if(get_option('sib_wa_status', 'acik') !== 'kapali'): ?>
        <div class="right-btn">
            <?php if($tt_st || $ei_st): ?><div id="sib-wa-tooltip" class="sib-tooltip"><?php echo $tt_msg; ?></div><?php endif; ?>
            
            <a href="javascript:void(0);" id="sib-wa-link" class="cta-btn-yesil" <?php echo !empty($track_wa) ? 'onclick="' . $track_wa . '"' : ''; ?>>
                <div class="cta-text-yesil nogosterme"><div class="title-cta">Whatsapp</div><div class="subtitle-cta"><?php echo esc_html(sib_get_val('sib_wa_metin_mobil', 'Temsilci Bağlan')); ?></div></div>
                <div class="cta-text-yesil nogoster"><div class="title-cta">Whatsapp</div><div class="subtitle-cta"><?php echo esc_html(sib_get_val('sib_wa_metin_masaustu', 'Bilgi Alın')); ?></div></div>
                <div class="cta-icon-yesil">
                    <?php if($badge_st): ?><span class="sib-badge">1</span><?php endif; ?>
                    <img src="<?php echo esc_url(sib_get_val('sib_icon_wa', plugin_dir_url(__FILE__) . 'img/whatsapp.webp')); ?>" alt="WA">
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if($ma_st): ?>
    <div id="sib-ma-modal" class="sib-modal-overlay">
        <div class="sib-modal-content">
            <span class="sib-modal-close">&times;</span>
            <div class="sib-modal-header-icon">💬</div>
            <h3>Hangi departmanla görüşmek istersiniz?</h3>
            <?php 
            $depts = get_option('sib_departments', array());
            if(empty($depts)) {
                $depts = array(
                    array('name' => 'Satış Departmanı', 'cc' => '90', 'no' => '5321111111', 'icon' => 'fas fa-shopping-cart'),
                    array('name' => 'Teknik Destek', 'cc' => '90', 'no' => '5322222222', 'icon' => 'fas fa-tools')
                );
            }
            foreach($depts as $dept):
            ?>
            <a href="#" class="sib-dept-btn" data-no="<?php echo esc_attr($dept['cc'] . $dept['no']); ?>">
                <i><i class="<?php echo esc_attr($dept['icon']); ?>"></i></i> 
                <span><?php echo esc_html($dept['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php 
    $cerez_durum = get_option('sib_cerez_durum', 'acik');
    if ( $cerez_durum == 'acik' ) : 
        $cerez_metin       = esc_html(sib_get_val('sib_cerez_metin', 'Size daha iyi bir deneyim sunmak için çerezler kullanıyoruz. Sayfamızı kullanarak bunu kabul etmiş olursunuz.'));
        $cerez_link_metin  = esc_html(sib_get_val('sib_cerez_link_metin', 'Çerez Politikası'));
        $cerez_buton_metin = esc_html(sib_get_val('sib_cerez_buton_metin', 'Tamam'));
        $cerez_sayfa_id    = get_option('sib_cerez_sayfa_id', 0);
        $cerez_link_url    = !empty($cerez_sayfa_id) ? get_permalink($cerez_sayfa_id) : '#';
    ?>
    <div id="sib-cerez-kutu" class="sib-cookie-banner">
        <div class="sib-cookie-text">
            <span>🍪 <?php echo $cerez_metin; ?></span>
            <?php if ( !empty($cerez_sayfa_id) ) : ?>
                <a href="<?php echo esc_url($cerez_link_url); ?>" class="sib-cookie-link" target="_blank"><?php echo $cerez_link_metin; ?></a>
            <?php endif; ?>
        </div>
        <button id="sib-cerez-kabul" class="sib-cookie-btn"><?php echo $cerez_buton_metin; ?></button>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Lazy Load Gecikmeli Yükleme
        var btnContainer = document.getElementById('sib-buttons-container');
        if (btnContainer && btnContainer.classList.contains('sib-lazy-hidden')) {
            var showBtns = function() {
                btnContainer.classList.remove('sib-lazy-hidden');
                window.removeEventListener('scroll', showBtns);
            };
            window.addEventListener('scroll', showBtns);
            setTimeout(showBtns, 2500); // 2.5sn sonra otomatik göster
        }

        // 2. Çerez Uyarısı
        var cerezKutu = document.getElementById("sib-cerez-kutu");
        var kabulBtn = document.getElementById("sib-cerez-kabul");
        if (cerezKutu && kabulBtn) {
            if (document.cookie.indexOf("sib_cerez_onay=1") === -1) {
                setTimeout(function() { cerezKutu.classList.add("goster"); }, 500);
            }
            kabulBtn.addEventListener("click", function() {
                var d = new Date(); d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
                document.cookie = "sib_cerez_onay=1;expires=" + d.toUTCString() + ";path=/";
                cerezKutu.classList.remove("goster");
            });
        }

        var waLink = document.getElementById('sib-wa-link');
        var telLink = document.getElementById('sib-tel-link');
        var tooltip = document.getElementById('sib-wa-tooltip');
        var isMa = <?php echo $ma_st ? 'true' : 'false'; ?>;
        var modal = document.getElementById('sib-ma-modal');
        var finalNo = "<?php echo $whatsapp_numara; ?>";
        var rawMsg = "<?php echo $wa_template; ?>";
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

        if (waLink) {
            // 3. Mesai Saati Kontrolü (JS ile anlık)
            var isOffHours = false;
            var bhStatus = "<?php echo esc_js(get_option('sib_bh_status', 'kapali')); ?>";
            if (bhStatus === 'acik') {
                var d = new Date();
                var day = d.getDay(); // 0: Pazar, 1-5: Hafta İçi, 6: Cumartesi
                var nowVal = d.getHours() * 60 + d.getMinutes();
                var startStr = 'kapali', endStr = 'kapali';
                
                if (day >= 1 && day <= 5) {
                    startStr = "<?php echo esc_js(get_option('sib_bh_weekday_start', '09:00')); ?>";
                    endStr = "<?php echo esc_js(get_option('sib_bh_weekday_end', '18:00')); ?>";
                } else if (day === 6) {
                    startStr = "<?php echo esc_js(get_option('sib_bh_saturday_start', 'kapali')); ?>";
                    endStr = "<?php echo esc_js(get_option('sib_bh_saturday_end', 'kapali')); ?>";
                } else {
                    startStr = "<?php echo esc_js(get_option('sib_bh_sunday_start', 'kapali')); ?>";
                    endStr = "<?php echo esc_js(get_option('sib_bh_sunday_end', 'kapali')); ?>";
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
                rawMsg = "<?php echo esc_js(sib_get_val('sib_bh_off_msg', 'Şu an mesai saatleri dışındayız, mesajınızı bırakın, en kısa sürede dönüş yapalım')); ?>"; 
                isMa = false; 
            }

            // 4. UTM Kontrolü
            var utmKey = "<?php echo esc_js(get_option('sib_utm_key', '')); ?>";
            var utmVal = "<?php echo esc_js(get_option('sib_utm_val', '')); ?>";
            var utmNo  = "<?php echo esc_js(get_option('sib_utm_no', '')); ?>";
            var urlParams = new URLSearchParams(window.location.search);
            
            if (utmKey && utmVal && utmNo && urlParams.get(utmKey) === utmVal) {
                finalNo = utmNo;
                isMa = false; 
            }

            // 5. Kampanya Modu Kontrolü
            var campStatus = <?php echo $camp_st ? 'true' : 'false'; ?>;
            if(campStatus && !isOffHours) { 
                var today = new Date().toISOString().split('T')[0];
                var campStart = "<?php echo esc_js(get_option('sib_camp_start', '')); ?>";
                var campEnd = "<?php echo esc_js(get_option('sib_camp_end', '')); ?>";
                if (today >= campStart && today <= campEnd) {
                    finalNo = "<?php echo esc_js(sib_get_val('sib_camp_cc', '90') . sib_get_val('sib_camp_no', '')); ?>";
                    rawMsg = "<?php echo esc_js(sib_get_val('sib_camp_msg', '')); ?>";
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
            
            if(!isMa) {
                waLink.href = encodeLink;
            }

            // 7. Çoklu Temsilci Modal İşlemleri
            if(isMa && modal) {
                document.querySelector('.sib-modal-close').addEventListener('click', function(e){ 
                    e.preventDefault();
                    modal.classList.remove('goster'); 
                });
                
                modal.addEventListener('click', function(e){
                    if(e.target === modal) { modal.classList.remove('goster'); }
                });

                var deptBtns = document.querySelectorAll('.sib-dept-btn');
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
            xhr.send("action=sib_track_click&click_type=" + type);
        }

        if(telLink) { telLink.addEventListener('click', function(){ trackClick('tel'); }); }
        if(waLink) {
            waLink.addEventListener('click', function(e) {
                trackClick('wa');
                if(isMa) {
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

/* ==========================================================================
 * 5. HARİCİ GÜNCELLEME SİSTEMİ (Update Checker)
 * ========================================================================== */
/**
 * Bu bölüm eklentinin kendi sunucunuzdan veya GitHub üzerinden 
 * otomatik güncelleme almasını sağlar.
 */
class SIB_Plugin_Update_Checker {
    public $plugin_slug;
    public $version;
    public $update_url;
    public $cache_key;

    public function __construct($plugin_slug, $version, $update_url) {
        $this->plugin_slug = $plugin_slug;
        $this->version     = $version;
        $this->update_url  = $update_url;
        $this->cache_key   = 'sib_update_check_' . $plugin_slug;

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
    }

    public function check_update($transient) {
        if (empty($transient->checked)) { return $transient; }

        $remote = get_transient($this->cache_key);
        if(false === $remote) {
            $remote = wp_remote_get($this->update_url, array(
                'timeout' => 10,
                'headers' => array('Accept' => 'application/json')
            ));

            if (!is_wp_error($remote) && wp_remote_retrieve_response_code($remote) == 200) {
                $remote = json_decode(wp_remote_retrieve_body($remote));
                set_transient($this->cache_key, $remote, HOUR_IN_SECONDS);
            }
        }

        if ($remote && version_compare($this->version, $remote->version, '<')) {
            $res = new stdClass();
            $res->slug         = $this->plugin_slug;
            $res->plugin       = $this->plugin_slug . '/' . $this->plugin_slug . '.php';
            $res->new_version  = $remote->version;
            $res->tested       = $remote->tested;
            $res->package      = $remote->download_url;
            $res->icons        = array('default' => 'https://s.w.org/plugins/geopattern-icon/sabit-iletisim-butonlari.svg');
            
            $transient->response[$res->plugin] = $res;
        }

        return $transient;
    }

    public function plugin_popup($res, $action, $args) {
        if ('plugin_information' !== $action) { return $res; }
        if ($this->plugin_slug !== $args->slug) { return $res; }

        $remote = get_transient($this->cache_key);
        if(false === $remote) {
            $remote = wp_remote_get($this->update_url, array('timeout' => 10));
            if (!is_wp_error($remote) && wp_remote_retrieve_response_code($remote) == 200) {
                $remote = json_decode(wp_remote_retrieve_body($remote));
            }
        }

        if (!$remote) { return $res; }

        $res = new stdClass();
        $res->name           = 'Sabit İletişim Butonları PRO';
        $res->slug           = $this->plugin_slug;
        $res->version        = $remote->version;
        $res->tested         = $remote->tested;
        $res->last_updated   = $remote->last_updated;
        $res->sections       = array(
            'description'  => $remote->sections->description,
            'changelog'    => $remote->sections->changelog
        );
        $res->download_link  = $remote->download_url;

        return $res;
    }
}

// Güncelleme Kontrolünü Başlat
// NOT: 'https://alanadiniz.com/update.json' kısmını kendi sunucunuzdaki dosya ile değiştirin.
new SIB_Plugin_Update_Checker('pasted_content', '9.7', 'https://alanadiniz.com/update.json');
