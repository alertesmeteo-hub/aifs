<?php
/**
 * Plugin Name: AIFS / ECMWF France — Tableaux et cartes
 * Plugin URI: https://github.com/alertesmeteo-hub/aifs
 * Description: Cartes interactives et prévisions du modèle IA déterministe AIFS/ECMWF pour la France métropolitaine et la Corse.
 * Version: 1.0.0
 * Author: Alertes Météo Hub
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AIFS_VERSION', '1.0.0');
define('AIFS_RELEASE_DATE', '29/08/2026');
define('AIFS_OPTION_BASE_URL', 'aifs_national_data_base_url');
define(
    'AIFS_DEFAULT_BASE_URL',
    'https://raw.githubusercontent.com/alertesmeteo-hub/aifs/data'
);

add_action('wp_enqueue_scripts', 'aifs_register_assets');
add_action('admin_init', 'aifs_register_settings');
add_action('admin_menu', 'aifs_add_settings_page');
add_shortcode('aifs_meteo', 'aifs_render_shortcode');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'aifs_plugin_action_links');

function aifs_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=aifs-ecmwf')),
        esc_html__('Réglages', 'aifs-ecmwf-france')
    );
    array_unshift($links, $settings_link);

    $help_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('options-general.php?page=aifs-ecmwf')),
        esc_html__('Shortcodes / Aide', 'aifs-ecmwf-france')
    );
    array_unshift($links, $help_link);

    return $links;
}

function aifs_register_assets() {
    wp_register_style(
        'aifs-table',
        plugin_dir_url(__FILE__) . 'assets/aifs-meteo.css',
        array(),
        AIFS_VERSION
    );
    wp_register_script(
        'aifs-table',
        plugin_dir_url(__FILE__) . 'assets/aifs-meteo.js',
        array(),
        AIFS_VERSION,
        true
    );
    wp_register_style(
        'aifs-map',
        plugin_dir_url(__FILE__) . 'assets/aifs-map.css',
        array('aifs-table'),
        AIFS_VERSION
    );
    wp_register_script(
        'aifs-map',
        plugin_dir_url(__FILE__) . 'assets/aifs-map.js',
        array(),
        AIFS_VERSION,
        true
    );
}

function aifs_register_settings() {
    register_setting(
        'aifs_settings',
        AIFS_OPTION_BASE_URL,
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => AIFS_DEFAULT_BASE_URL,
        )
    );

    add_settings_section(
        'aifs_main_section',
        'Source des données nationales',
        '__return_false',
        'aifs-ecmwf'
    );

    add_settings_field(
        'aifs_data_base_url_field',
        'Adresse du dossier de données',
        'aifs_render_url_field',
        'aifs-ecmwf',
        'aifs_main_section'
    );
}

function aifs_render_url_field() {
    $value = get_option(AIFS_OPTION_BASE_URL, AIFS_DEFAULT_BASE_URL);
    printf(
        '<input type="url" class="regular-text code" name="%1$s" value="%2$s" autocomplete="off">',
        esc_attr(AIFS_OPTION_BASE_URL),
        esc_attr($value)
    );
    echo '<p class="description">Conservez l’adresse proposée : elle pointe vers la branche nationale « data » du dépôt.</p>';
}

function aifs_add_settings_page() {
    add_options_page(
        'Tableau AIFS / ECMWF France',
        'AIFS / ECMWF',
        'manage_options',
        'aifs-ecmwf',
        'aifs_render_settings_page'
    );
}

function aifs_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>AIFS / ECMWF France</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('aifs_settings');
            do_settings_sections('aifs-ecmwf');
            submit_button();
            ?>
        </form>
        <p><strong>Version du module : <?php echo esc_html(AIFS_VERSION); ?> (<?php echo esc_html(AIFS_RELEASE_DATE); ?>)</strong></p>
        <h2>Shortcode unique</h2>
        <p><code>[aifs_meteo]</code> : cartes interactives, prévisions générales et neige.</p>
        <p><code>[aifs_meteo code="75056" departement="75" ville="Paris" heures="360"]</code></p>
        <p><code>[aifs_meteo code="66136" departement="66" ville="Perpignan" selecteur="non"]</code> : une seule ville, sans recherche.</p>
        <p>Le visiteur peut ensuite rechercher n’importe quelle commune ou saisir un code postal.</p>
        <p><strong>Limite du modèle :</strong> AIFS ne publie ni rafales, ni CAPE, ni réflectivité radar. Ce module n’affiche donc ni carte de vent en rafales, ni tableau orages — contrairement au module CEP/IFS.</p>
    </div>
    <?php
}

function aifs_base_url() {
    $url = get_option(AIFS_OPTION_BASE_URL, AIFS_DEFAULT_BASE_URL);
    return untrailingslashit(apply_filters('aifs_national_data_base_url', $url));
}

function aifs_department_code($value) {
    $code = strtoupper(trim((string) $value));
    return preg_match('/^(?:\d{2}|2A|2B)$/', $code) ? $code : '66';
}

function aifs_commune_code($value) {
    $code = strtoupper(trim((string) $value));
    return preg_match('/^[0-9A-Z]{5}$/', $code) ? $code : '66136';
}

function aifs_unique_identifier() {
    if (function_exists('wp_unique_id')) {
        return wp_unique_id('aifs-city-');
    }
    return 'aifs-city-' . wp_rand(1000, 999999);
}

function aifs_map_variable($value) {
    $variable = strtolower(trim(sanitize_key((string) $value)));
    $allowed = array(
        'temperature',
        'temperature_ressentie',
        'point_rosee',
        'humidex',
        'pluie_1h',
        'pluie_cumul',
        'neige',
        'neige_au_sol',
        'equivalent_eau_neige',
        'vent',
        'pression',
        'pression_surface',
        'nebulosite',
        'nuages_bas',
        'nuages_moyens',
        'nuages_eleves',
        'humidite',
        'altitude',
    );
    return in_array($variable, $allowed, true) ? $variable : 'temperature';
}

function aifs_render_map_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'variable' => 'temperature',
            'hauteur' => '700',
            'titre' => 'Cartes AIFS France',
            'animation' => 'oui',
        ),
        $atts,
        'aifs_meteo'
    );

    $variable = aifs_map_variable($atts['variable']);
    $height = max(440, min(900, absint($atts['hauteur'])));
    $title = trim(sanitize_text_field($atts['titre']));
    if ($title === '') {
        $title = 'Cartes AIFS France';
    }
    $animation_value = strtolower(trim(sanitize_text_field($atts['animation'])));
    $animation = !in_array($animation_value, array('non', '0', 'false', 'off'), true);
    $map_id = function_exists('wp_unique_id')
        ? wp_unique_id('aifs-map-')
        : 'aifs-map-' . wp_rand(1000, 999999);

    wp_enqueue_style('aifs-map');
    wp_enqueue_script('aifs-map');

    ob_start();
    ?>
    <section
        id="<?php echo esc_attr($map_id); ?>"
        class="aifs-card aifsm-card"
        data-aifsm-app
        data-base-url="<?php echo esc_url(aifs_base_url()); ?>"
        data-variable="<?php echo esc_attr($variable); ?>"
        data-timezone="<?php echo esc_attr(wp_timezone_string()); ?>"
        data-animation="<?php echo $animation ? '1' : '0'; ?>"
        data-module-version="<?php echo esc_attr(AIFS_VERSION); ?>"
        style="--aifsm-height: <?php echo esc_attr($height); ?>px"
    >
        <header class="aifs-header aifsm-header">
            <div>
                <p class="aifs-kicker">MODÈLE IA DÉTERMINISTE • JUSQU’À +360 H</p>
                <h2><?php echo esc_html($title); ?></h2>
                <p class="aifs-meta" data-aifsm-run>Chargement du dernier run AIFS…</p>
            </div>
            <div class="aifs-badge"><span>AIFS</span><strong>0,25°</strong></div>
        </header>

        <div class="aifsm-toolbar">
            <div class="aifsm-field aifsm-layer-picker">
                <span>Paramètre</span>
                <button
                    type="button"
                    class="aifsm-layer-trigger"
                    data-aifsm-menu-toggle
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($map_id . '-layers'); ?>"
                >
                    <span data-aifsm-current-layer>Température à 2 m</span>
                </button>
            </div>
            <div class="aifsm-tools" aria-label="Outils de la carte">
                <button
                    type="button"
                    class="aifsm-tool-toggle"
                    data-aifsm-tool="zoom"
                    aria-pressed="false"
                    title="Afficher les outils de capture et de copie"
                >🔍 Zoom interactif</button>
                <button
                    type="button"
                    class="aifsm-tool-toggle"
                    data-aifsm-tool="diagram"
                    aria-pressed="false"
                    title="Cliquer sur la carte pour afficher le diagramme d’un point"
                >📈 Diagramme</button>
            </div>
            <div class="aifsm-time-controls" aria-label="Navigation dans les échéances">
                <button type="button" data-aifsm-previous title="Échéance précédente" aria-label="Échéance précédente">◀</button>
                <button type="button" data-aifsm-play title="Lancer l’animation" aria-label="Lancer l’animation">▶</button>
                <button type="button" data-aifsm-next title="Échéance suivante" aria-label="Échéance suivante">▶</button>
            </div>
            <div class="aifsm-validity-actions">
                <button
                    type="button"
                    class="aifsm-menu-close"
                    data-aifsm-menu-close
                    aria-label="Déplier le menu des cartes"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($map_id . '-layers'); ?>"
                >
                    <span data-aifsm-menu-label>Déplier</span><span class="aifsm-menu-close-icon" data-aifsm-menu-icon aria-hidden="true">⌄</span>
                </button>
                <div class="aifsm-validity">
                    <span>Prévision valable</span>
                    <strong data-aifsm-validity>—</strong>
                    <small data-aifsm-lead>—</small>
                </div>
            </div>
        </div>

        <p class="aifsm-tool-hint" data-aifsm-tool-hint hidden></p>

        <div
            id="<?php echo esc_attr($map_id . '-layers'); ?>"
            class="aifsm-layer-menu"
            data-aifsm-layer-menu
            hidden
        >
            <div class="aifsm-layer-menu-head">
                <div>
                    <strong>Choisir une carte AIFS</strong>
                    <small>Paramètres disponibles dans la production ouverte ECMWF AIFS</small>
                </div>
            </div>
            <div class="aifsm-layer-grid" data-aifsm-layer-grid></div>
        </div>

        <div class="aifsm-period-selector" data-aifsm-period hidden>
            <div class="aifsm-period-head">
                <div>
                    <strong data-aifsm-period-title>Période personnalisée</strong>
                    <small>Déplacez les deux curseurs pour choisir précisément le début et la fin.</small>
                </div>
                <span data-aifsm-period-summary>—</span>
            </div>
            <div class="aifsm-dual-range" data-aifsm-dual-range>
                <div class="aifsm-dual-range-track" aria-hidden="true"></div>
                <input data-aifsm-period-start type="range" min="0" max="1" value="0" step="1" aria-label="Début de la période">
                <input data-aifsm-period-end type="range" min="0" max="1" value="1" step="1" aria-label="Fin de la période">
            </div>
            <div class="aifsm-period-values">
                <span><small>Du</small><strong data-aifsm-period-start-label>—</strong></span>
                <span><small>Au</small><strong data-aifsm-period-end-label>—</strong></span>
            </div>
        </div>

        <p class="aifs-stale" data-aifsm-stale role="status" hidden>
            Attention : la dernière production disponible a plus de 8 heures.
        </p>

        <div class="aifsm-viewport" data-aifsm-viewport role="img" aria-label="Carte météo AIFS interactive">
            <div class="aifsm-scene" data-aifsm-scene>
                <canvas class="aifsm-weather-canvas" data-aifsm-weather aria-hidden="true"></canvas>
                <canvas class="aifsm-vector-canvas" data-aifsm-vectors aria-hidden="true"></canvas>
            </div>
            <canvas class="aifsm-label-canvas" data-aifsm-labels aria-hidden="true"></canvas>
            <div class="aifsm-probe" data-aifsm-probe hidden>
                <strong data-aifsm-probe-value>—</strong>
                <span data-aifsm-probe-label>Valeur AIFS</span>
            </div>
            <div class="aifsm-map-titlebar">
                <strong data-aifsm-map-title>Carte AIFS</strong>
                <span data-aifsm-map-run>Run AIFS —</span>
            </div>
            <div class="aifsm-map-date" data-aifsm-map-date>Échéance —</div>
            <div class="aifsm-map-buttons" aria-label="Commandes de zoom">
                <span class="aifsm-zoom-level" data-aifsm-zoom-level>100 %</span>
                <button type="button" data-aifsm-zoom-in title="Agrandir" aria-label="Agrandir">+</button>
                <button type="button" data-aifsm-zoom-out title="Réduire" aria-label="Réduire">−</button>
                <button type="button" data-aifsm-reset title="Recentrer" aria-label="Recentrer">⌂</button>
                <button type="button" data-aifsm-fullscreen title="Plein écran" aria-label="Plein écran">⛶</button>
            </div>
            <div class="aifsm-advanced-tools" data-aifsm-advanced-tools hidden aria-label="Outils avancés">
                <button type="button" data-aifsm-copy title="Copier la carte pour la coller dans un message ou un document" aria-label="Copier la carte dans le presse-papiers">📋 Copier l’image</button>
                <button type="button" data-aifsm-capture title="Télécharger la carte au format PNG" aria-label="Télécharger la carte au format PNG">📷 Télécharger PNG</button>
            </div>
            <div class="aifsm-diagram-popup" data-aifsm-diagram-popup hidden>
                <header>
                    <strong data-aifsm-diagram-title>—</strong>
                    <button type="button" data-aifsm-diagram-close aria-label="Fermer le diagramme">×</button>
                </header>
                <div class="aifsm-diagram-body" data-aifsm-diagram-body>
                    <p class="aifsm-diagram-status" data-aifsm-diagram-status>Chargement…</p>
                </div>
            </div>
            <div class="aifsm-legend" data-aifsm-legend aria-label="Légende de la carte"></div>
            <a class="aifsm-map-brand" href="https://www.alertes-meteo.com/" target="_blank" rel="noopener noreferrer">
                www.alertes-meteo.com • Module v<?php echo esc_html(AIFS_VERSION); ?> (<?php echo esc_html(AIFS_RELEASE_DATE); ?>)
            </a>
            <div class="aifsm-loading" data-aifsm-loading role="status">Chargement de la carte…</div>
            <div class="aifsm-error" data-aifsm-error role="alert" hidden></div>
        </div>

        <div class="aifsm-timeline" data-aifsm-timeline>
            <div data-aifsm-single-timeline>
                <input data-aifsm-slider type="range" min="0" max="0" value="0" step="1" aria-label="Échéance de prévision">
                <div class="aifsm-timeline-labels"><span>Run</span><span>Échéance maximale</span></div>
            </div>
        </div>

        <footer class="aifs-footer">
            <span data-aifsm-generated>Mise à jour en cours de lecture…</span>
            <span>
                Données météo directes :
                <a href="https://www.ecmwf.int/en/forecasts/datasets/open-data" target="_blank" rel="noopener noreferrer">AIFS 0,25° — ECMWF Open Data</a>
                • <a href="https://www.alertes-meteo.com/" target="_blank" rel="noopener noreferrer">www.alertes-meteo.com</a>
                • Module cartes v<?php echo esc_html(AIFS_VERSION); ?> (<?php echo esc_html(AIFS_RELEASE_DATE); ?>)
            </span>
        </footer>

        <noscript>
            <p class="aifs-message aifs-error">JavaScript doit être activé pour afficher les cartes.</p>
        </noscript>
    </section>
    <?php
    return ob_get_clean();
}

function aifs_render_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'ville' => 'Perpignan',
            'code' => '66136',
            'departement' => '66',
            'heures' => '360',
            'titre' => '',
            'selecteur' => 'oui',
        ),
        $atts,
        'aifs_meteo'
    );

    $hours = max(6, min(360, absint($atts['heures'])));
    $city_name = sanitize_text_field($atts['ville']);
    if ($city_name === '') {
        $city_name = 'Perpignan';
    }
    $city_code = aifs_commune_code($atts['code']);
    $department = aifs_department_code($atts['departement']);
    $title_prefix = trim(sanitize_text_field($atts['titre']));
    if ($title_prefix === '') {
        $title_prefix = 'Prévisions AIFS';
    }
    $selector_value = strtolower(trim(sanitize_text_field($atts['selecteur'])));
    $show_selector = !in_array($selector_value, array('non', '0', 'false', 'off'), true);

    $input_id = aifs_unique_identifier();
    $results_id = $input_id . '-results';
    $status_id = $input_id . '-status';

    wp_enqueue_style('aifs-table');
    wp_enqueue_script('aifs-table');
    wp_enqueue_style('aifs-map');
    wp_enqueue_script('aifs-map');

    ob_start();
    ?>
    <section
        class="aifs-card aifs-national"
        data-aifs-app
        data-base-url="<?php echo esc_url(aifs_base_url()); ?>"
        data-default-code="<?php echo esc_attr($city_code); ?>"
        data-default-department="<?php echo esc_attr($department); ?>"
        data-default-name="<?php echo esc_attr($city_name); ?>"
        data-hours="<?php echo esc_attr($hours); ?>"
        data-timezone="<?php echo esc_attr(wp_timezone_string()); ?>"
        data-title-prefix="<?php echo esc_attr($title_prefix); ?>"
        data-selector="<?php echo $show_selector ? '1' : '0'; ?>"
    >
        <header class="aifs-header">
            <div>
                <p class="aifs-kicker">MODÈLE IA DÉTERMINISTE • FRANCE MÉTROPOLITAINE</p>
                <h2 data-aifs-title><?php echo esc_html($title_prefix . ' — ' . $city_name); ?></h2>
                <div class="aifs-header-details">
                    <p class="aifs-city-altitude" data-aifs-altitude>Altitude de <?php echo esc_html($city_name); ?> : chargement…</p>
                    <p class="aifs-meta" data-aifs-meta>Chargement du dernier run AIFS…</p>
                </div>
            </div>
            <div class="aifs-badge"><span>AIFS</span><strong>0,25°</strong></div>
        </header>

        <div class="aifs-toolbar" <?php if (!$show_selector) : ?>hidden<?php endif; ?>>
            <div class="aifs-search">
                <div class="aifs-search-mainline">
                    <label for="<?php echo esc_attr($input_id); ?>">Choisissez votre commune</label>
                    <div class="aifs-search-control">
                        <span class="aifs-search-icon" aria-hidden="true">⌕</span>
                        <input
                            id="<?php echo esc_attr($input_id); ?>"
                            class="aifs-city-input"
                            type="search"
                            value="<?php echo esc_attr($city_name); ?>"
                            placeholder="Nom de commune ou code postal"
                            autocomplete="off"
                            spellcheck="false"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="<?php echo esc_attr($results_id); ?>"
                            aria-describedby="<?php echo esc_attr($status_id); ?>"
                        >
                    </div>
                    <button type="button" class="aifs-locate-button" data-aifs-locate>📍 Détecter ma ville</button>
                    <p
                        id="<?php echo esc_attr($status_id); ?>"
                        class="aifs-search-status"
                        role="status"
                        aria-live="polite"
                    >Saisissez au moins deux lettres ou un code postal.</p>
                </div>
                <div
                    id="<?php echo esc_attr($results_id); ?>"
                    class="aifs-search-results"
                    role="listbox"
                    hidden
                ></div>
            </div>
            <div class="aifs-coverage">
                <strong>34 746 communes</strong>
                <span>Métropole et Corse</span>
            </div>
        </div>

        <p class="aifs-stale" data-aifs-stale role="status" hidden>
            Attention : la dernière mise à jour disponible a plus de 8 heures.
        </p>

        <div class="aifs-tabs" role="tablist" aria-label="Type de prévision AIFS">
            <button
                type="button"
                class="aifs-tab aifs-tab-map is-active"
                role="tab"
                aria-selected="true"
                data-aifs-tab="map"
            >🗺️ Cartes météo</button>
            <button
                type="button"
                class="aifs-tab"
                role="tab"
                aria-selected="false"
                data-aifs-tab="general"
            >🌤️ Prévisions générales</button>
            <button
                type="button"
                class="aifs-tab aifs-tab-snow"
                role="tab"
                aria-selected="false"
                data-aifs-tab="snow"
            >❄️ Risque de neige</button>
        </div>

        <div class="aifs-panel aifs-map-panel" data-aifs-panel="map">
            <?php
            echo aifs_render_map_shortcode(
                array(
                    'variable' => 'temperature',
                    'hauteur' => '760',
                    'titre' => 'Cartes AIFS / ECMWF — résolution 0,25°',
                    'animation' => 'oui',
                )
            );
            ?>
        </div>

        <div class="aifs-panel" data-aifs-panel="general" hidden>
            <div class="aifs-table-wrap aifs-general-wrap" role="region" aria-label="Prévisions générales par échéance" tabindex="0">
                <table class="aifs-table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Heure</th>
                            <th scope="col">Temps</th>
                            <th scope="col">T°</th>
                            <th scope="col">Hum.</th>
                            <th scope="col">Pluie</th>
                            <th scope="col">Nuages</th>
                            <th scope="col">Vent</th>
                            <th scope="col">Pression</th>
                        </tr>
                    </thead>
                    <tbody data-aifs-body-general>
                        <tr>
                            <td colspan="9" class="aifs-loading">Chargement des prévisions…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <section class="aifs-charts" data-aifs-charts aria-label="Diagrammes AIFS">
                <article class="aifs-chart-card">
                    <h3 data-aifs-chart-title-temperature>Diagramme températures (°C)</h3>
                    <div class="aifs-chart" data-aifs-chart-temperature></div>
                </article>
                <article class="aifs-chart-card">
                    <h3 data-aifs-chart-title-pressure>Diagramme pression ramenée au niveau de la mer (hPa)</h3>
                    <div class="aifs-chart" data-aifs-chart-pressure></div>
                </article>
                <article class="aifs-chart-card">
                    <h3 data-aifs-chart-title-rain>Diagramme précipitations (mm)</h3>
                    <p class="aifs-chart-total" data-aifs-rain-total>Précipitations cumulées : —</p>
                    <div class="aifs-chart" data-aifs-chart-rain></div>
                </article>
                <article class="aifs-chart-card">
                    <h3 data-aifs-chart-title-wind>Diagramme vent moyen</h3>
                    <div class="aifs-chart" data-aifs-chart-wind></div>
                </article>
            </section>
        </div>

        <div class="aifs-panel" data-aifs-panel="snow" hidden>
            <p class="aifs-snow-summary" data-aifs-snow-summary>
                Diagnostic neige AIFS : chargement…
            </p>
            <div class="aifs-top-scroll" data-aifs-top-scroll="snow" aria-label="Navigation horizontale du tableau neige" hidden><div></div></div>
            <div class="aifs-table-wrap aifs-snow-wrap" data-aifs-scroll-wrap="snow" role="region" aria-label="Risque de neige par échéance" tabindex="0">
                <table class="aifs-table aifs-snow-table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Heure</th>
                            <th scope="col">Risque neige</th>
                            <th scope="col">Phase</th>
                            <th scope="col">Neige / pas</th>
                            <th scope="col">Neige 3 h</th>
                            <th scope="col">Neige 6 h</th>
                            <th scope="col">Tenue</th>
                            <th scope="col">Pres. hPa</th>
                            <th scope="col">Hum.</th>
                            <th scope="col">Vent moyen</th>
                            <th scope="col">Cumul neige fraîche</th>
                            <th scope="col">Détails</th>
                        </tr>
                    </thead>
                    <tbody data-aifs-body-snow>
                        <tr>
                            <td colspan="13" class="aifs-loading">Chargement du risque de neige…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="aifs-snow-note">
                <strong>Lecture neige :</strong> les cumuls de neige sont des sorties directes AIFS. La neige fraîche et la tenue sont estimées à partir du cumul en eau, de la température à 2 m et de l’altitude du point de grille.
            </p>
        </div>

        <footer class="aifs-footer">
            <span data-aifs-generated>Mise à jour en cours de lecture…</span>
            <span>
                Données météo directes :
                <a href="https://www.ecmwf.int/en/forecasts/datasets/open-data" target="_blank" rel="noopener noreferrer">AIFS 0,25° — ECMWF Open Data</a>
                • Recherche des communes :
                <a href="https://geo.api.gouv.fr/decoupage-administratif/communes" target="_blank" rel="noopener noreferrer">API officielle française</a>
                • <a href="https://www.alertes-meteo.com/" target="_blank" rel="noopener noreferrer">www.alertes-meteo.com</a>
            </span>
            <span class="aifs-plugin-version">Module AIFS v<?php echo esc_html(AIFS_VERSION); ?> (<?php echo esc_html(AIFS_RELEASE_DATE); ?>)</span>
        </footer>

        <noscript>
            <p class="aifs-message aifs-error">JavaScript doit être activé pour rechercher une commune.</p>
        </noscript>
    </section>
    <?php
    return ob_get_clean();
}
