=== Fluent Forms Minimalist Lightbox ===
Contributors: michaelkanda
Tags: fluent forms, lightbox, modal, popup, shortcode
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Öffnet ein Fluent Form elegant und barrierearm in einer performanten Vanilla-JS Lightbox – per Shortcode, ohne Abhängigkeiten.

== Description ==

Fluent Forms Minimalist Lightbox bindet ein beliebiges Fluent Form in ein schlankes, animiertes Overlay ein, das per Button-Klick geöffnet wird. Das Plugin kommt ohne jQuery oder externe Bibliotheken aus und lädt sein CSS/JS ausschließlich auf Seiten, auf denen der Shortcode tatsächlich verwendet wird.

**Funktionen**

* Beliebiges Fluent Form per Shortcode in einer Lightbox öffnen.
* Reines Vanilla JS – keine externen Abhängigkeiten.
* Assets laufen über die offizielle WordPress-Asset-Pipeline (kompatibel mit Caching- und Minify-Plugins) und werden nur konditional geladen.
* Barrierearm: `role="dialog"`, `aria-modal`, Focus-Trap, Fokus-Rücksprung auf den auslösenden Button, Schließen per ESC und per Klick auf den Hintergrund.
* Respektiert `prefers-reduced-motion`.
* Mehrere Buttons/Formulare pro Seite möglich.
* Vollständig übersetzbar (Textdomain `ff-minimalist-lightbox`).

**Hinweis:** Dieses Plugin benötigt das separat installierte Plugin „Fluent Forms". Ist es nicht aktiv, gibt der Shortcode für Besucher nichts aus; Administratoren sehen einen dezenten Hinweis.

== Installation ==

1. Den Plugin-Ordner in das Verzeichnis `/wp-content/plugins/` hochladen oder das Plugin über **Plugins → Installieren** als ZIP einspielen.
2. Das Plugin im Menü **Plugins** aktivieren.
3. Sicherstellen, dass das Plugin „Fluent Forms" installiert und aktiv ist.
4. Den Shortcode an gewünschter Stelle einfügen (siehe „Frequently Asked Questions").

== Frequently Asked Questions ==

= Wie binde ich das Formular ein? =

Mit dem Shortcode:

`[fluentform_lightbox id="4" label="Seminarraum buchen"]`

= Welche Attribute gibt es? =

* `id` – ID des Fluent Form (Standard: `4`). Es werden nur Ziffern akzeptiert.
* `label` – Beschriftung des Buttons (Standard: „Seminarraum buchen").
* `class` – Optionale zusätzliche CSS-Klasse(n) für den Button, z. B. zur Integration in das Theme-Styling.

Beispiel mit eigener Klasse:

`[fluentform_lightbox id="7" label="Jetzt anfragen" class="btn btn-primary"]`

= Kann ich mehrere Formulare auf einer Seite verwenden? =

Ja. Jeder Shortcode erzeugt anhand der Formular-ID eine eigene Lightbox. Mehrere Buttons und Overlays funktionieren unabhängig voneinander.

= Wie passe ich das Design an? =

Alle Elemente nutzen das Präfix `mk-fflb` (z. B. `.mk-fflb-overlay`, `.mk-fflb-content`, `.mk-fflb-trigger`, `.mk-fflb-close`). Eigene Regeln im Theme oder über „Customizer → Zusätzliches CSS" überschreiben die Standardwerte.

= Lädt das Plugin Assets auf allen Seiten? =

Nein. CSS und JS werden nur dann eingebunden, wenn der Shortcode auf der jeweiligen Seite tatsächlich gerendert wird.

== Screenshots ==

1. Geöffnete Lightbox mit eingebettetem Fluent Form.
2. Auslöse-Button im Seiteninhalt.

== Changelog ==

= 2.0.0 =
* Umbau auf die offizielle WordPress-Asset-Pipeline (`wp_register_*` / `wp_add_inline_*`) statt direkter Ausgabe im Footer.
* Konditionales Laden: Assets nur noch auf Seiten mit aktivem Shortcode.
* Barrierefreiheit ergänzt: `role="dialog"`, `aria-modal`, `aria-labelledby`, Focus-Trap und Fokus-Rücksprung.
* `prefers-reduced-motion` wird respektiert.
* Vollständige Internationalisierung (Textdomain, übersetzbare Strings).
* Klassenbasierte Struktur mit eindeutigem Präfix und Singleton-Initialisierung.
* Graceful Fallback, wenn Fluent Forms nicht aktiv ist.
* Striktere Validierung der Formular-ID und konsequentes Escaping.

= 1.0.0 =
* Erste Veröffentlichung.

== Upgrade Notice ==

= 2.0.0 =
Empfohlenes Update: bessere Performance durch konditionales Laden, deutlich verbesserte Barrierefreiheit und WP-konformes Asset-Handling. Der Shortcode bleibt vollständig kompatibel.

----------------------------------
Developed with ❤️ by Michael Kanda
https://designare.at

