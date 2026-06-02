<?php
/**
 * Plugin Name:       Fluent Forms Minimalist Lightbox
 * Plugin URI:        https://example.com/fluent-forms-minimalist-lightbox
 * Description:       Öffnet ein Fluent Form elegant und barrierearm in einer performanten Vanilla-JS Lightbox via Shortcode.
 * Version:           2.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Michael Kanda
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ff-minimalist-lightbox
 * Domain Path:       /languages
 *
 * @package FF_Minimalist_Lightbox
 */

// Direktzugriff verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Haupt-Plugin-Klasse.
 *
 * Kapselt die gesamte Logik, damit keine globalen Funktionsnamen mit anderen
 * Plugins kollidieren (eindeutiges Prefix: MK_FFLB / mk_fflb).
 */
final class MK_FFLB_Lightbox {

	/**
	 * Eindeutiges Prefix für Handles, Hooks und IDs.
	 */
	const PREFIX = 'mk-fflb';

	/**
	 * Plugin-Version – dient gleichzeitig als Cache-Buster für die Assets.
	 */
	const VERSION = '2.1.0';

	/**
	 * Options-Key in der wp_options-Tabelle, unter dem die Backend-Einstellungen
	 * gespeichert werden.
	 */
	const OPTION_KEY = 'mk_fflb_settings';

	/**
	 * Zähler für eindeutige IDs, falls keine numerische Formular-ID vorliegt
	 * (z.B. wenn ein freier Shortcode hinterlegt wurde).
	 *
	 * @var int
	 */
	private static $instance_counter = 0;

	/**
	 * Merkt sich, ob der Shortcode auf der Seite verwendet wurde,
	 * damit Assets nur dann wirklich geladen werden.
	 *
	 * @var bool
	 */
	private $assets_needed = false;

	/**
	 * Singleton-Instanz.
	 *
	 * @var MK_FFLB_Lightbox|null
	 */
	private static $instance = null;

	/**
	 * Gibt die einzige Instanz zurück (verhindert Mehrfach-Initialisierung).
	 *
	 * @return MK_FFLB_Lightbox
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks registrieren.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		// Assets früh registrieren (noch nicht ausgeben) – ausgegeben wird erst,
		// wenn der Shortcode tatsächlich rendert.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		// Backend: Einstellungsseite + Settings-API.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// Schnellzugriff-Link auf der Plugin-Übersichtsseite.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Shortcode registrieren.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'fluentform_lightbox', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Liefert die gespeicherten Einstellungen mit sinnvollen Defaults.
	 *
	 * @return array{label:string, form_id:string, shortcode:string, class:string, presets:array}
	 */
	private function get_options() {
		$defaults = array(
			'label'     => __( 'Seminarraum buchen', 'ff-minimalist-lightbox' ),
			'form_id'   => '4',
			'shortcode' => '',
			'class'     => '',
			'presets'   => array(),
		);

		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$options = wp_parse_args( $saved, $defaults );

		// Presets robust normalisieren (immer ein indiziertes Array).
		if ( ! is_array( $options['presets'] ) ) {
			$options['presets'] = array();
		}

		return $options;
	}

	/**
	 * Sucht ein Preset anhand seines Keys.
	 *
	 * @param string $key     Gesuchter Preset-Key.
	 * @param array  $presets Liste aller Presets.
	 * @return array|null Das gefundene Preset oder null.
	 */
	private function find_preset( $key, $presets ) {
		$key = sanitize_key( $key );
		if ( '' === $key ) {
			return null;
		}
		foreach ( (array) $presets as $preset ) {
			if ( isset( $preset['key'] ) && sanitize_key( $preset['key'] ) === $key ) {
				return $preset;
			}
		}
		return null;
	}

	/* ---------------------------------------------------------------------
	 *  Backend / Einstellungsseite
	 * ------------------------------------------------------------------- */

	/**
	 * Einstellungsseite unterhalb von "Einstellungen" registrieren.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'FF Lightbox', 'ff-minimalist-lightbox' ),               // Seitentitel.
			__( 'FF Lightbox', 'ff-minimalist-lightbox' ),               // Menütitel.
			'manage_options',                                            // Erforderliche Rechte.
			self::PREFIX,                                                // Menü-Slug.
			array( $this, 'render_settings_page' )                       // Render-Callback.
		);
	}

	/**
	 * "Einstellungen"-Link in der Plugin-Liste ergänzen.
	 *
	 * @param array $links Vorhandene Action-Links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$url      = admin_url( 'options-general.php?page=' . self::PREFIX );
		$settings = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Einstellungen', 'ff-minimalist-lightbox' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Settings-API: Optionsgruppe, Section und Felder registrieren.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::PREFIX . '-group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			self::PREFIX . '-section',
			__( 'Standard-Einstellungen der Lightbox', 'ff-minimalist-lightbox' ),
			array( $this, 'render_section_intro' ),
			self::PREFIX
		);

		add_settings_field(
			'label',
			__( 'Button-Beschriftung', 'ff-minimalist-lightbox' ),
			array( $this, 'render_field_label' ),
			self::PREFIX,
			self::PREFIX . '-section'
		);

		add_settings_field(
			'form_id',
			__( 'Fluent-Forms Formular-ID', 'ff-minimalist-lightbox' ),
			array( $this, 'render_field_form_id' ),
			self::PREFIX,
			self::PREFIX . '-section'
		);

		add_settings_field(
			'shortcode',
			__( 'Eigener Shortcode (optional)', 'ff-minimalist-lightbox' ),
			array( $this, 'render_field_shortcode' ),
			self::PREFIX,
			self::PREFIX . '-section'
		);

		add_settings_field(
			'class',
			__( 'CSS-Klasse(n) des Buttons', 'ff-minimalist-lightbox' ),
			array( $this, 'render_field_class' ),
			self::PREFIX,
			self::PREFIX . '-section'
		);

		// Zweite Section: Mehrfach-Konfiguration (Presets).
		add_settings_section(
			self::PREFIX . '-presets-section',
			__( 'Mehrfach-Konfiguration (Presets)', 'ff-minimalist-lightbox' ),
			array( $this, 'render_presets_intro' ),
			self::PREFIX
		);

		add_settings_field(
			'presets',
			__( 'Presets', 'ff-minimalist-lightbox' ),
			array( $this, 'render_field_presets' ),
			self::PREFIX,
			self::PREFIX . '-presets-section'
		);
	}

	/**
	 * Eingaben bereinigen, bevor sie gespeichert werden.
	 *
	 * @param mixed $input Rohe Formulardaten.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		// Button-Beschriftung: einfacher Text.
		$out['label'] = isset( $input['label'] ) ? sanitize_text_field( $input['label'] ) : '';
		if ( '' === $out['label'] ) {
			$out['label'] = __( 'Seminarraum buchen', 'ff-minimalist-lightbox' );
		}

		// Formular-ID: nur Ziffern.
		$out['form_id'] = isset( $input['form_id'] ) ? preg_replace( '/[^0-9]/', '', (string) $input['form_id'] ) : '';

		// Eigener Shortcode: nur Admins dürfen das setzen; Tags/Skripte entfernen,
		// eckige Klammern aber erlauben (Shortcode-Syntax).
		$shortcode = isset( $input['shortcode'] ) ? (string) $input['shortcode'] : '';
		$shortcode = wp_kses( $shortcode, array() ); // HTML komplett strippen, Shortcode-Text bleibt.
		$out['shortcode'] = trim( $shortcode );

		// Standard-CSS-Klasse(n) des Buttons.
		$out['class'] = $this->sanitize_css_classes( isset( $input['class'] ) ? $input['class'] : '' );

		// Presets: Liste mehrerer Konfigurationen.
		$out['presets'] = array();
		if ( isset( $input['presets'] ) && is_array( $input['presets'] ) ) {
			$used_keys = array();
			foreach ( $input['presets'] as $preset ) {
				if ( ! is_array( $preset ) ) {
					continue;
				}

				$key   = isset( $preset['key'] ) ? sanitize_key( $preset['key'] ) : '';
				$label = isset( $preset['label'] ) ? sanitize_text_field( $preset['label'] ) : '';
				$fid   = isset( $preset['form_id'] ) ? preg_replace( '/[^0-9]/', '', (string) $preset['form_id'] ) : '';
				$sc    = isset( $preset['shortcode'] ) ? trim( wp_kses( (string) $preset['shortcode'], array() ) ) : '';
				$cls   = $this->sanitize_css_classes( isset( $preset['class'] ) ? $preset['class'] : '' );

				// Komplett leere Zeilen überspringen.
				if ( '' === $key && '' === $label && '' === $fid && '' === $sc && '' === $cls ) {
					continue;
				}

				// Ein Key ist zwingend nötig, damit das Preset ansprechbar ist.
				if ( '' === $key ) {
					continue;
				}

				// Doppelte Keys vermeiden – erster gewinnt.
				if ( in_array( $key, $used_keys, true ) ) {
					continue;
				}
				$used_keys[] = $key;

				$out['presets'][] = array(
					'key'       => $key,
					'label'     => $label,
					'form_id'   => $fid,
					'shortcode' => $sc,
					'class'     => $cls,
				);
			}
		}

		return $out;
	}

	/**
	 * CSS-Klassen säubern: nur erlaubte Zeichen, Mehrfach-Klassen via Leerzeichen.
	 *
	 * @param string $value Rohwert.
	 * @return string
	 */
	private function sanitize_css_classes( $value ) {
		$value   = (string) $value;
		$classes = preg_split( '/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		$clean   = array();
		foreach ( (array) $classes as $class ) {
			$sanitized = sanitize_html_class( $class );
			if ( '' !== $sanitized ) {
				$clean[] = $sanitized;
			}
		}
		return implode( ' ', $clean );
	}

	/**
	 * Kurze Einleitung der Settings-Section.
	 *
	 * @return void
	 */
	public function render_section_intro() {
		echo '<p>' . esc_html__( 'Diese Werte werden als Standard verwendet. Im Shortcode angegebene Attribute (id, label, class) haben weiterhin Vorrang.', 'ff-minimalist-lightbox' ) . '</p>';
		echo '<p><code>[fluentform_lightbox]</code> &mdash; ' . esc_html__( 'nutzt die hier gespeicherten Standardwerte.', 'ff-minimalist-lightbox' ) . '</p>';
	}

	/**
	 * Feld: Button-Beschriftung.
	 *
	 * @return void
	 */
	public function render_field_label() {
		$o = $this->get_options();
		printf(
			'<input type="text" class="regular-text" name="%1$s[label]" value="%2$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $o['label'] )
		);
		echo '<p class="description">' . esc_html__( 'Text auf dem Button, der die Lightbox öffnet.', 'ff-minimalist-lightbox' ) . '</p>';
	}

	/**
	 * Feld: Formular-ID.
	 *
	 * @return void
	 */
	public function render_field_form_id() {
		$o = $this->get_options();
		printf(
			'<input type="number" min="1" step="1" class="small-text" name="%1$s[form_id]" value="%2$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $o['form_id'] )
		);
		echo '<p class="description">' . esc_html__( 'Numerische ID des Fluent Forms, das eingebunden werden soll.', 'ff-minimalist-lightbox' ) . '</p>';
	}

	/**
	 * Feld: eigener Shortcode (Override).
	 *
	 * @return void
	 */
	public function render_field_shortcode() {
		$o = $this->get_options();
		printf(
			'<input type="text" class="large-text code" name="%1$s[shortcode]" value="%2$s" placeholder="%3$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $o['shortcode'] ),
			esc_attr( '[fluentform id="4"]' )
		);
		echo '<p class="description">' . esc_html__( 'Optional: ein vollständiger Shortcode, der statt der Formular-ID eingebunden wird (überschreibt die ID oben). Leer lassen, um die Formular-ID zu verwenden.', 'ff-minimalist-lightbox' ) . '</p>';
	}

	/**
	 * Feld: CSS-Klasse(n) des Buttons.
	 *
	 * @return void
	 */
	public function render_field_class() {
		$o = $this->get_options();
		printf(
			'<input type="text" class="regular-text" name="%1$s[class]" value="%2$s" placeholder="%3$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $o['class'] ),
			esc_attr( 'btn btn-primary' )
		);
		echo '<p class="description">' . esc_html__( 'Optional: zusätzliche CSS-Klasse(n), durch Leerzeichen getrennt. Wird zusätzlich zur Standardklasse mk-fflb-trigger ausgegeben.', 'ff-minimalist-lightbox' ) . '</p>';
	}

	/**
	 * Einleitung der Presets-Section.
	 *
	 * @return void
	 */
	public function render_presets_intro() {
		echo '<p>' . esc_html__( 'Lege beliebig viele Konfigurationen an, um mehrere Buttons mit unterschiedlichen Formularen zu nutzen. Jedes Preset erhält einen eindeutigen Key und wird im Shortcode so angesprochen:', 'ff-minimalist-lightbox' ) . '</p>';
		echo '<p><code>[fluentform_lightbox preset="seminar"]</code></p>';
		echo '<p class="description">' . esc_html__( 'Im Shortcode angegebene Attribute (id, label, class) überschreiben die Preset-Werte zusätzlich.', 'ff-minimalist-lightbox' ) . '</p>';
	}

	/**
	 * Feld: Presets-Repeater (Tabelle + Add/Remove via Vanilla-JS).
	 *
	 * @return void
	 */
	public function render_field_presets() {
		$o       = $this->get_options();
		$presets = $o['presets'];
		$key     = esc_attr( self::OPTION_KEY );
		?>
		<table class="widefat striped" id="mk-fflb-presets" style="max-width:980px;">
			<thead>
				<tr>
					<th style="width:14%;"><?php esc_html_e( 'Key', 'ff-minimalist-lightbox' ); ?></th>
					<th style="width:22%;"><?php esc_html_e( 'Button-Beschriftung', 'ff-minimalist-lightbox' ); ?></th>
					<th style="width:10%;"><?php esc_html_e( 'Formular-ID', 'ff-minimalist-lightbox' ); ?></th>
					<th style="width:24%;"><?php esc_html_e( 'Eigener Shortcode', 'ff-minimalist-lightbox' ); ?></th>
					<th style="width:18%;"><?php esc_html_e( 'CSS-Klasse(n)', 'ff-minimalist-lightbox' ); ?></th>
					<th style="width:8%;"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( empty( $presets ) ) {
					// Eine leere Startzeile zur Orientierung.
					$presets = array( array() );
				}
				$i = 0;
				foreach ( $presets as $preset ) {
					$this->render_preset_row( $i, $preset );
					$i++;
				}
				?>
			</tbody>
		</table>
		<p>
			<button type="button" class="button" id="mk-fflb-add-preset">
				<?php esc_html_e( '+ Preset hinzufügen', 'ff-minimalist-lightbox' ); ?>
			</button>
		</p>

		<?php // Template-Zeile (wird per JS geklont). ?>
		<template id="mk-fflb-preset-template">
			<?php $this->render_preset_row( '__INDEX__', array() ); ?>
		</template>

		<script>
		(function(){
			var table    = document.getElementById('mk-fflb-presets');
			var tbody    = table ? table.querySelector('tbody') : null;
			var addBtn   = document.getElementById('mk-fflb-add-preset');
			var template = document.getElementById('mk-fflb-preset-template');
			if (!tbody || !addBtn || !template) { return; }

			var nextIndex = tbody.querySelectorAll('tr').length;

			addBtn.addEventListener('click', function(){
				var html = template.innerHTML.replace(/__INDEX__/g, nextIndex);
				var tmp  = document.createElement('tbody');
				tmp.innerHTML = html.trim();
				var row = tmp.querySelector('tr');
				if (row){ tbody.appendChild(row); nextIndex++; }
			});

			tbody.addEventListener('click', function(e){
				var btn = e.target.closest('.mk-fflb-remove-preset');
				if (!btn) { return; }
				var row = btn.closest('tr');
				if (row){ row.parentNode.removeChild(row); }
			});
		})();
		</script>
		<?php
	}

	/**
	 * Eine einzelne Preset-Zeile rendern (auch als JS-Template-Vorlage genutzt).
	 *
	 * @param int|string $index  Zeilenindex (oder Platzhalter __INDEX__).
	 * @param array      $preset Vorhandene Werte.
	 * @return void
	 */
	private function render_preset_row( $index, $preset ) {
		$key   = self::OPTION_KEY;
		$base  = $key . '[presets][' . $index . ']';
		$pk    = isset( $preset['key'] ) ? $preset['key'] : '';
		$pl    = isset( $preset['label'] ) ? $preset['label'] : '';
		$pf    = isset( $preset['form_id'] ) ? $preset['form_id'] : '';
		$ps    = isset( $preset['shortcode'] ) ? $preset['shortcode'] : '';
		$pc    = isset( $preset['class'] ) ? $preset['class'] : '';
		?>
		<tr>
			<td><input type="text" class="regular-text" style="width:100%;" name="<?php echo esc_attr( $base ); ?>[key]" value="<?php echo esc_attr( $pk ); ?>" placeholder="<?php echo esc_attr__( 'z.B. seminar', 'ff-minimalist-lightbox' ); ?>" /></td>
			<td><input type="text" style="width:100%;" name="<?php echo esc_attr( $base ); ?>[label]" value="<?php echo esc_attr( $pl ); ?>" /></td>
			<td><input type="number" min="1" step="1" style="width:100%;" name="<?php echo esc_attr( $base ); ?>[form_id]" value="<?php echo esc_attr( $pf ); ?>" /></td>
			<td><input type="text" class="code" style="width:100%;" name="<?php echo esc_attr( $base ); ?>[shortcode]" value="<?php echo esc_attr( $ps ); ?>" placeholder="<?php echo esc_attr( "[fluentform id='5']" ); ?>" /></td>
			<td><input type="text" style="width:100%;" name="<?php echo esc_attr( $base ); ?>[class]" value="<?php echo esc_attr( $pc ); ?>" /></td>
			<td><button type="button" class="button button-link-delete mk-fflb-remove-preset"><?php esc_html_e( 'Entfernen', 'ff-minimalist-lightbox' ); ?></button></td>
		</tr>
		<?php
	}

	/**
	 * Die Einstellungsseite rendern.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Fluent Forms Minimalist Lightbox', 'ff-minimalist-lightbox' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PREFIX . '-group' );
				do_settings_sections( self::PREFIX );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Styles und Skript bei WordPress anmelden.
	 *
	 * Wir registrieren hier nur (kein enqueue), sodass die Assets über die
	 * offizielle WP-Pipeline laufen (Caching-/Minify-Plugins, Versionierung,
	 * Dependency-Handling). Das eigentliche Einbinden passiert konditional
	 * im Shortcode – so wird auf Seiten ohne Formular nichts geladen.
	 *
	 * @return void
	 */
	public function register_assets() {
		// Style-Handle ohne Datei registrieren, um Inline-CSS anzuhängen.
		wp_register_style( self::PREFIX . '-style', false, array(), self::VERSION );
		wp_add_inline_style( self::PREFIX . '-style', $this->get_inline_css() );

		// Skript-Handle ohne Datei registrieren, um Inline-JS anzuhängen.
		// in_footer => true sorgt für nicht-blockierendes Laden.
		wp_register_script(
			self::PREFIX . '-script',
			false,
			array(),
			self::VERSION,
			array( 'in_footer' => true )
		);
		wp_add_inline_script( self::PREFIX . '-script', $this->get_inline_js() );
	}

	/**
	 * Assets tatsächlich einbinden (nur einmal pro Seite).
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		if ( $this->assets_needed ) {
			return; // Bereits eingebunden.
		}
		$this->assets_needed = true;
		wp_enqueue_style( self::PREFIX . '-style' );
		wp_enqueue_script( self::PREFIX . '-script' );
	}

	/**
	 * Shortcode-Ausgabe: Button + Lightbox-Overlay mit eingebettetem Formular.
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe (oder leerer String, wenn Fluent Forms fehlt).
	 */
	public function render_shortcode( $atts ) {
		// Graceful Fallback: Ohne Fluent Forms ergibt das Plugin keinen Sinn.
		if ( ! function_exists( 'do_shortcode' ) || ! shortcode_exists( 'fluentform' ) ) {
			// Für Redakteure/Admins einen Hinweis ausgeben, für Besucher nichts.
			if ( current_user_can( 'manage_options' ) ) {
				return '<p>' . esc_html__( 'Fluent Forms ist nicht aktiv – die Lightbox kann kein Formular einbinden.', 'ff-minimalist-lightbox' ) . '</p>';
			}
			return '';
		}

		$atts    = (array) $atts;
		$options = $this->get_options();

		// Aktives Preset bestimmen (falls per Attribut angefragt).
		$preset = null;
		if ( isset( $atts['preset'] ) && '' !== trim( (string) $atts['preset'] ) ) {
			$preset = $this->find_preset( $atts['preset'], $options['presets'] );

			// Unbekanntes Preset: für Admins Hinweis, für Besucher stiller Fallback
			// auf die Standardwerte.
			if ( null === $preset && current_user_can( 'manage_options' ) ) {
				return '<p>' . sprintf(
					/* translators: %s: angefragter Preset-Key. */
					esc_html__( 'FF Lightbox: Unbekanntes Preset „%s" – bitte Key in den Einstellungen prüfen.', 'ff-minimalist-lightbox' ),
					esc_html( sanitize_key( $atts['preset'] ) )
				) . '</p>';
			}
		}

		// Basis-Konfiguration: aktives Preset oder globale Standardwerte.
		if ( is_array( $preset ) ) {
			$base = array(
				'label'     => '' !== $preset['label'] ? $preset['label'] : $options['label'],
				'form_id'   => $preset['form_id'],
				'shortcode' => $preset['shortcode'],
				'class'     => '' !== $preset['class'] ? $preset['class'] : $options['class'],
			);
		} else {
			$base = array(
				'label'     => $options['label'],
				'form_id'   => $options['form_id'],
				'shortcode' => $options['shortcode'],
				'class'     => $options['class'],
			);
		}

		$args = shortcode_atts(
			array(
				'id'      => $base['form_id'],
				'label'   => $base['label'],
				'class'   => $base['class'],
				'preset'  => '',
			),
			$atts,
			'fluentform_lightbox'
		);

		// Assets jetzt einbinden, da der Shortcode wirklich rendert.
		$this->enqueue_assets();

		// Eingebundenes Formular bestimmen.
		// Priorität: explizites Shortcode-Attribut "id" > eigener Shortcode der
		// Basis-Konfiguration > Formular-ID der Basis-Konfiguration.
		$form_id     = preg_replace( '/[^0-9]/', '', (string) $args['id'] );
		$form_markup = '';

		if ( isset( $atts['id'] ) && '' !== preg_replace( '/[^0-9]/', '', (string) $atts['id'] ) ) {
			// Per-Instanz angegebene ID gewinnt.
			$form_markup = '[fluentform id="' . esc_attr( $form_id ) . '"]';
		} elseif ( '' !== $base['shortcode'] ) {
			// Frei hinterlegter Shortcode (Preset oder Standard).
			$form_markup = $base['shortcode'];
		} elseif ( '' !== $form_id ) {
			$form_markup = '[fluentform id="' . esc_attr( $form_id ) . '"]';
		}

		if ( '' === $form_markup ) {
			return '';
		}

		// Eindeutiges Suffix: numerische Form-ID falls vorhanden, sonst Zähler
		// (z.B. bei freiem Shortcode oder mehreren gleichen IDs auf einer Seite).
		$uid        = ++self::$instance_counter;
		$overlay_id = self::PREFIX . '-overlay-' . $uid;
		$title_id   = self::PREFIX . '-title-' . $uid;

		// Erlaubte CSS-Klassen säubern.
		$extra_class = $this->sanitize_css_classes( $args['class'] );
		$btn_classes = self::PREFIX . '-trigger' . ( '' !== $extra_class ? ' ' . $extra_class : '' );

		ob_start();
		?>
		<button
			type="button"
			class="<?php echo esc_attr( $btn_classes ); ?>"
			data-mk-fflb-target="<?php echo esc_attr( $overlay_id ); ?>"
			aria-haspopup="dialog"
		>
			<?php echo esc_html( $args['label'] ); ?>
		</button>

		<div
			id="<?php echo esc_attr( $overlay_id ); ?>"
			class="<?php echo esc_attr( self::PREFIX ); ?>-overlay"
			role="dialog"
			aria-modal="true"
			aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
			hidden
		>
			<div class="<?php echo esc_attr( self::PREFIX ); ?>-content" role="document">
				<h2 id="<?php echo esc_attr( $title_id ); ?>" class="<?php echo esc_attr( self::PREFIX ); ?>-sr-only">
					<?php echo esc_html( $args['label'] ); ?>
				</h2>
				<button
					type="button"
					class="<?php echo esc_attr( self::PREFIX ); ?>-close"
					aria-label="<?php echo esc_attr__( 'Schließen', 'ff-minimalist-lightbox' ); ?>"
				>&times;</button>
				<?php echo do_shortcode( $form_markup ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inline-CSS der Lightbox.
	 *
	 * @return string
	 */
	private function get_inline_css() {
		$p = self::PREFIX;
		return "
		.{$p}-overlay{
			display:none;position:fixed;inset:0;width:100%;height:100%;
			background-color:rgba(15,23,42,.6);
			-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);
			z-index:999999;justify-content:center;align-items:center;
			opacity:0;transition:opacity .25s ease;
		}
		.{$p}-overlay.is-active{display:flex;opacity:1;}
		.{$p}-content{
			background:#fff;padding:52px 30px 30px;border-radius:16px;
			width:100%;max-width:600px;max-height:90vh;overflow-y:auto;
			position:relative;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);
			transform:scale(.95);transition:transform .25s ease;
		}
		.{$p}-overlay.is-active .{$p}-content{transform:scale(1);}
		/* Schließen-Button bewusst dezent halten. Theme- und Fluent-Forms-
		   Button-Styles (goldener Hintergrund, Rahmen, Padding) werden hier
		   gezielt neutralisiert, damit das X nicht als Kasten erscheint und
		   nicht ins erste Formularfeld ragt. */
		.{$p}-close{
			position:absolute;top:10px;right:10px;z-index:2;
			display:flex;align-items:center;justify-content:center;
			width:32px;height:32px;min-width:0;min-height:0;
			padding:0 !important;margin:0 !important;
			background:transparent !important;border:0 !important;
			box-shadow:none !important;outline:none;border-radius:6px;
			font-size:22px;font-weight:400;line-height:1;color:#b4bac4;
			cursor:pointer;-webkit-appearance:none;appearance:none;
			transition:color .2s ease,background-color .2s ease;
		}
		.{$p}-close:hover,.{$p}-close:focus-visible{
			color:#475569;background:rgba(15,23,42,.05) !important;
		}
		.{$p}-sr-only{
			position:absolute;width:1px;height:1px;padding:0;margin:-1px;
			overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
		}
		@media (prefers-reduced-motion: reduce){
			.{$p}-overlay,.{$p}-content{transition:none;}
		}
		/* Fix: Fluent-Forms-Datepicker (Flatpickr) hängt seinen Kalender an den
		   <body> und nutzt z-index:99999. Das liegt unter unserem Overlay
		   (z-index:999999), wodurch der Kalender hinter dem abgedunkelten
		   Hintergrund verschwindet. Wir heben ihn darüber. */
		.flatpickr-calendar{z-index:1000001 !important;}

		/* Fix: overflow-y:auto erzwingt laut CSS-Spec auch overflow-x:auto.
		   Dadurch wird der linke/rechte Rand randständiger Felder (z.B. die
		   Button-Optionen samt Rahmen/Box-Shadow) um ~1px abgeschnitten und
		   wirkt 'offen'. Etwas horizontale Innen-Luft im Formular und das
		   Neutralisieren negativer Zeilen-Margins behebt das. */
		.{$p}-content .frm-fluent-form,
		.{$p}-content .fluentform{
			padding-left:3px;padding-right:3px;box-sizing:border-box;
		}
		.{$p}-content .ff-el-group,
		.{$p}-content .ff_form_group{
			margin-left:0;margin-right:0;
		}
		";
	}

	/**
	 * Inline-JS der Lightbox (Vanilla JS, mit Focus-Trap & Fokus-Rücksprung).
	 *
	 * @return string
	 */
	private function get_inline_js() {
		return "
		(function(){
			'use strict';
			var FOCUSABLE = 'a[href],button:not([disabled]),textarea,input,select,[tabindex]:not([tabindex=\"-1\"])';
			var lastFocused = null;

			function getActiveOverlay(){
				return document.querySelector('.mk-fflb-overlay.is-active');
			}

			function open(overlay, trigger){
				lastFocused = trigger || document.activeElement;
				overlay.hidden = false;
				// Reflow erzwingen, damit die CSS-Transition greift.
				void overlay.offsetWidth;
				overlay.classList.add('is-active');
				document.body.style.overflow = 'hidden';
				var focusables = overlay.querySelectorAll(FOCUSABLE);
				if (focusables.length){ focusables[0].focus(); }
			}

			function close(overlay){
				if(!overlay){ return; }
				overlay.classList.remove('is-active');
				document.body.style.overflow = '';
				var onEnd = function(){
					overlay.hidden = true;
					overlay.removeEventListener('transitionend', onEnd);
				};
				overlay.addEventListener('transitionend', onEnd);
				// Fallback, falls keine Transition feuert.
				setTimeout(function(){ if(!overlay.classList.contains('is-active')){ overlay.hidden = true; } }, 300);
				if (lastFocused && typeof lastFocused.focus === 'function'){ lastFocused.focus(); }
			}

			function trapFocus(e, overlay){
				if (e.key !== 'Tab'){ return; }
				var f = overlay.querySelectorAll(FOCUSABLE);
				if (!f.length){ return; }
				var first = f[0], last = f[f.length - 1];
				if (e.shiftKey && document.activeElement === first){
					e.preventDefault(); last.focus();
				} else if (!e.shiftKey && document.activeElement === last){
					e.preventDefault(); first.focus();
				}
			}

			// Öffnen & Schließen per Klick (Event-Delegation).
			document.addEventListener('click', function(e){
				var trigger = e.target.closest('[data-mk-fflb-target]');
				if (trigger){
					e.preventDefault();
					var overlay = document.getElementById(trigger.getAttribute('data-mk-fflb-target'));
					if (overlay){ open(overlay, trigger); }
					return;
				}
				if (e.target.closest('.mk-fflb-close')){
					close(e.target.closest('.mk-fflb-overlay'));
					return;
				}
				// Klick auf den dunklen Hintergrund schließt.
				if (e.target.classList.contains('mk-fflb-overlay')){
					close(e.target);
				}
			});

			// Tastatur: ESC schließt, Tab wird gefangen.
			document.addEventListener('keydown', function(e){
				var overlay = getActiveOverlay();
				if (!overlay){ return; }
				if (e.key === 'Escape'){ close(overlay); }
				else { trapFocus(e, overlay); }
			});
		})();
		";
	}
}

// Plugin starten.
MK_FFLB_Lightbox::instance();
