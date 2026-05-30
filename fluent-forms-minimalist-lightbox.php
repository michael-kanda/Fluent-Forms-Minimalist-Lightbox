<?php
/**
 * Plugin Name:       Fluent Forms Minimalist Lightbox
 * Plugin URI:        https://example.com/fluent-forms-minimalist-lightbox
 * Description:       Öffnet ein Fluent Form elegant und barrierearm in einer performanten Vanilla-JS Lightbox via Shortcode.
 * Version:           2.0.0
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
	const VERSION = '2.0.0';

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

		$args = shortcode_atts(
			array(
				'id'    => '4',
				'label' => __( 'Seminarraum buchen', 'ff-minimalist-lightbox' ),
				'class' => '',
			),
			$atts,
			'fluentform_lightbox'
		);

		// Formular-ID auf gültige Zeichen begrenzen (Fluent Forms nutzt numerische IDs).
		$form_id = preg_replace( '/[^0-9]/', '', (string) $args['id'] );
		if ( '' === $form_id ) {
			return '';
		}

		// Assets jetzt einbinden, da der Shortcode wirklich rendert.
		$this->enqueue_assets();

		// Eindeutige IDs für mehrfachen Einsatz pro Seite.
		$overlay_id = self::PREFIX . '-overlay-' . $form_id;
		$title_id   = self::PREFIX . '-title-' . $form_id;

		// Erlaubte CSS-Klassen säubern.
		$extra_class = trim( esc_attr( $args['class'] ) );
		$btn_classes = self::PREFIX . '-trigger' . ( $extra_class ? ' ' . $extra_class : '' );

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
				<?php echo do_shortcode( '[fluentform id="' . esc_attr( $form_id ) . '"]' ); ?>
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
			background:#fff;padding:40px 30px 30px;border-radius:16px;
			width:100%;max-width:600px;max-height:90vh;overflow-y:auto;
			position:relative;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);
			transform:scale(.95);transition:transform .25s ease;
		}
		.{$p}-overlay.is-active .{$p}-content{transform:scale(1);}
		.{$p}-close{
			position:absolute;top:12px;right:16px;background:none;border:none;
			font-size:28px;color:#94a3b8;cursor:pointer;line-height:1;
			transition:color .2s ease;
		}
		.{$p}-close:hover,.{$p}-close:focus-visible{color:#1e293b;}
		.{$p}-sr-only{
			position:absolute;width:1px;height:1px;padding:0;margin:-1px;
			overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
		}
		@media (prefers-reduced-motion: reduce){
			.{$p}-overlay,.{$p}-content{transition:none;}
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
