# Fluent Forms Minimalist Lightbox

Öffnet ein [Fluent Form](https://fluentforms.com/) elegant und barrierearm in einer performanten Vanilla-JS-Lightbox – per Shortcode, ohne externe Abhängigkeiten.

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

---

## Inhalt

- [Funktionen](#funktionen)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Verwendung](#verwendung)
- [Shortcode-Attribute](#shortcode-attribute)
- [Styling](#styling)
- [Barrierefreiheit](#barrierefreiheit)
- [Übersetzung](#übersetzung)
- [Changelog](#changelog)
- [Lizenz](#lizenz)

---

## Funktionen

- **Beliebiges Fluent Form in einer Lightbox** – per Button-Klick geöffnet.
- **Reines Vanilla JS** – kein jQuery, keine externen Bibliotheken.
- **WP-konformes Asset-Handling** – CSS/JS laufen über die offizielle WordPress-Pipeline (`wp_register_*` / `wp_add_inline_*`) und sind damit mit Caching- und Minify-Plugins kompatibel.
- **Konditionales Laden** – Assets werden nur auf Seiten geladen, auf denen der Shortcode tatsächlich rendert.
- **Barrierearm** – `role="dialog"`, `aria-modal`, Focus-Trap, Fokus-Rücksprung, Schließen per ESC und Klick auf den Hintergrund.
- **Respektiert `prefers-reduced-motion`.**
- **Mehrere Buttons/Formulare pro Seite** möglich.
- **Vollständig übersetzbar** (Textdomain `ff-minimalist-lightbox`).

## Voraussetzungen

| Anforderung      | Version       |
| ---------------- | ------------- |
| WordPress        | 6.3 oder höher |
| PHP              | 7.4 oder höher |
| Fluent Forms     | aktiv installiert |

> Ist Fluent Forms nicht aktiv, gibt der Shortcode für Besucher nichts aus; Administratoren sehen einen dezenten Hinweis.

## Installation

**Variante A – als ZIP über das WP-Backend**

1. Repository als ZIP herunterladen.
2. Im WordPress-Backend zu **Plugins → Installieren → Plugin hochladen** gehen.
3. ZIP auswählen, installieren und aktivieren.

**Variante B – manuell / per Git**

```bash
cd wp-content/plugins/
git clone https://github.com/<dein-account>/fluent-forms-minimalist-lightbox.git
```

Anschließend das Plugin im Menü **Plugins** aktivieren.

## Verwendung

Den Shortcode an gewünschter Stelle einfügen (Beitrag, Seite, Widget, Block-Editor):

```text
[fluentform_lightbox id="4" label="Seminarraum buchen"]
```

Beispiel mit eigener CSS-Klasse:

```text
[fluentform_lightbox id="7" label="Jetzt anfragen" class="btn btn-primary"]
```

## Shortcode-Attribute

| Attribut | Standard            | Beschreibung                                                        |
| -------- | ------------------- | ------------------------------------------------------------------- |
| `id`     | `4`                 | ID des Fluent Form. Es werden ausschließlich Ziffern akzeptiert.    |
| `label`  | `Seminarraum buchen` | Beschriftung des Buttons.                                           |
| `class`  | _(leer)_            | Optionale zusätzliche CSS-Klasse(n) für den Button.                 |

## Styling

Alle Elemente nutzen das Präfix `mk-fflb`:

| Klasse              | Element                          |
| ------------------- | -------------------------------- |
| `.mk-fflb-trigger`  | Auslöse-Button                   |
| `.mk-fflb-overlay`  | Overlay / Hintergrund            |
| `.mk-fflb-content`  | Inhaltsfenster der Lightbox      |
| `.mk-fflb-close`    | Schließen-Button                 |

Eigene Regeln im Theme oder über **Customizer → Zusätzliches CSS** überschreiben die Standardwerte, z. B.:

```css
.mk-fflb-content {
    max-width: 720px;
    border-radius: 8px;
}
```

## Barrierefreiheit

- Dialog mit `role="dialog"`, `aria-modal="true"` und `aria-labelledby`.
- **Focus-Trap**: Tab/Shift+Tab bleiben innerhalb des Dialogs.
- Fokus springt beim Öffnen in den Dialog und beim Schließen zurück auf den auslösenden Button.
- Schließen per **ESC**, per **X-Button** und per Klick auf den Hintergrund.
- Animationen werden bei `prefers-reduced-motion: reduce` deaktiviert.

## Übersetzung

Alle Strings sind über die Textdomain `ff-minimalist-lightbox` übersetzbar. Übersetzungsdateien (`.po`/`.mo`) gehören in den Ordner `/languages`.

## Changelog

### 2.0.0
- Umbau auf die offizielle WordPress-Asset-Pipeline statt direkter Ausgabe im Footer.
- Konditionales Laden der Assets nur bei aktivem Shortcode.
- Barrierefreiheit ergänzt: `role="dialog"`, `aria-modal`, `aria-labelledby`, Focus-Trap, Fokus-Rücksprung.
- `prefers-reduced-motion` wird respektiert.
- Vollständige Internationalisierung.
- Klassenbasierte Struktur mit eindeutigem Präfix und Singleton-Initialisierung.
- Graceful Fallback ohne Fluent Forms, striktere ID-Validierung, konsequentes Escaping.

### 1.0.0
- Erste Veröffentlichung.

## Lizenz

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) © Michael Kanda

----------------------------------
Developed with ❤️ by Michael Kanda
https://designare.at

