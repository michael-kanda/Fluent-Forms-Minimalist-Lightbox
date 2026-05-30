# Fluent Forms Minimalist Lightbox

Öffnet ein [Fluent Form](https://fluentforms.com/) elegant und barrierearm in einer performanten Vanilla-JS-Lightbox – per Shortcode, ohne externe Abhängigkeiten. Beschriftung, Formular und CSS-Klassen lassen sich zentral im WordPress-Backend pflegen.

![Version](https://img.shields.io/badge/version-2.1.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

---

## Inhalt

- [Funktionen](#funktionen)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Backend / Einstellungen](#backend--einstellungen)
- [Verwendung](#verwendung)
- [Presets (Mehrfach-Konfiguration)](#presets-mehrfach-konfiguration)
- [Shortcode-Attribute](#shortcode-attribute)
- [Styling](#styling)
- [Barrierefreiheit](#barrierefreiheit)
- [Übersetzung](#übersetzung)
- [Changelog](#changelog)
- [Lizenz](#lizenz)

---

## Funktionen

- **Beliebiges Fluent Form in einer Lightbox** – per Button-Klick geöffnet.
- **Zentrale Backend-Konfiguration** – Button-Beschriftung, Formular-ID, optionaler eigener Shortcode und CSS-Klasse(n) bequem unter **Einstellungen → FF Lightbox** pflegen.
- **Mehrfach-Konfiguration (Presets)** – beliebig viele benannte Konfigurationen anlegen und per `preset="…"` im Shortcode aufrufen.
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

## Backend / Einstellungen

Nach der Aktivierung findest du die Einstellungsseite unter **Einstellungen → FF Lightbox** (Direktlink auch über den „Einstellungen"-Link in der Plugin-Übersicht). Alle Felder sind über die offizielle WordPress Settings API umgesetzt (inkl. Nonce-Schutz und serverseitigem Sanitizing).

**Standard-Einstellungen** (greifen, wenn der Shortcode ohne entsprechende Attribute genutzt wird):

| Feld                         | Beschreibung                                                                                       |
| ---------------------------- | -------------------------------------------------------------------------------------------------- |
| Button-Beschriftung          | Standardtext auf dem Button, der die Lightbox öffnet.                                              |
| Fluent-Forms Formular-ID     | Numerische ID des einzubindenden Formulars.                                                        |
| Eigener Shortcode (optional) | Vollständiger Shortcode, der statt der Formular-ID eingebunden wird (überschreibt die ID).         |
| CSS-Klasse(n) des Buttons    | Zusätzliche Klasse(n), durch Leerzeichen getrennt; werden neben `mk-fflb-trigger` ausgegeben.      |

> Nur Nutzer mit der Berechtigung `manage_options` (Administratoren) können diese Einstellungen sehen und ändern. Beim Speichern wird HTML aus dem Shortcode-Feld entfernt; CSS-Klassen werden über `sanitize_html_class` validiert.

## Verwendung

Den Shortcode an gewünschter Stelle einfügen (Beitrag, Seite, Widget, Block-Editor):

```text
[fluentform_lightbox]
```

Ohne Attribute werden die im Backend hinterlegten Standardwerte verwendet. Attribute überschreiben diese gezielt:

```text
[fluentform_lightbox id="4" label="Seminarraum buchen"]
```

Beispiel mit eigener CSS-Klasse:

```text
[fluentform_lightbox id="7" label="Jetzt anfragen" class="btn btn-primary"]
```

Beispiel mit einem im Backend angelegten Preset:

```text
[fluentform_lightbox preset="seminar"]
```

## Presets (Mehrfach-Konfiguration)

Wenn mehrere Buttons mit unterschiedlichen Formularen genutzt werden sollen, lassen sich unter **Einstellungen → FF Lightbox → Mehrfach-Konfiguration (Presets)** beliebig viele Konfigurationen anlegen. Zeilen werden per Klick hinzugefügt oder entfernt.

Jedes Preset besteht aus:

| Feld                | Beschreibung                                                                          |
| ------------------- | ------------------------------------------------------------------------------------- |
| Key                 | Eindeutiger Bezeichner zum Aufruf im Shortcode (z. B. `seminar`). **Pflichtfeld.**    |
| Button-Beschriftung | Beschriftung des Buttons für dieses Preset.                                           |
| Formular-ID         | Numerische Fluent-Forms-ID.                                                           |
| Eigener Shortcode   | Optional – überschreibt die Formular-ID dieses Presets.                               |
| CSS-Klasse(n)       | Optionale zusätzliche Klasse(n) für den Button dieses Presets.                        |

Aufruf im Shortcode:

```text
[fluentform_lightbox preset="seminar"]
```

**Überschreib-Reihenfolge:** Ein aktives Preset bildet die Basis; im Shortcode angegebene Attribute (`id`, `label`, `class`) überschreiben die Preset-Werte zusätzlich. Ohne `preset` greifen die globalen Standardwerte.

**Hinweise zum Verhalten:**

- Leere Zeilen werden beim Speichern verworfen; doppelte Keys werden ignoriert (der erste gewinnt).
- Ein Preset ohne Key wird nicht gespeichert, da es nicht ansprechbar wäre.
- Wird ein unbekannter Preset-Key aufgerufen, sehen Besucher nichts; Administratoren erhalten einen dezenten Hinweis.

## Shortcode-Attribute

| Attribut | Standard                     | Beschreibung                                                                                          |
| -------- | ---------------------------- | ---------------------------------------------------------------------------------------------------- |
| `preset` | _(leer)_                     | Key eines im Backend angelegten Presets. Setzt Label, Formular und Klasse als Basis.                 |
| `id`     | aus Backend (Standard `4`)   | ID des Fluent Form. Es werden ausschließlich Ziffern akzeptiert. Überschreibt Preset/Standard.       |
| `label`  | aus Backend                  | Beschriftung des Buttons. Überschreibt Preset/Standard.                                               |
| `class`  | aus Backend _(ggf. leer)_    | Optionale zusätzliche CSS-Klasse(n) für den Button. Überschreibt Preset/Standard.                    |

> Die Standardwerte für `id`, `label` und `class` stammen aus den Backend-Einstellungen (bzw. aus dem gewählten Preset). Explizit gesetzte Attribute haben stets Vorrang.

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

Alle Strings sind über die Textdomain `ff-minimalist-lightbox` übersetzbar – einschließlich der Backend-Einstellungsseite und der Preset-Verwaltung. Übersetzungsdateien (`.po`/`.mo`) gehören in den Ordner `/languages`.

## Changelog

### 2.1.0
- Backend-Einstellungsseite unter **Einstellungen → FF Lightbox** (WordPress Settings API).
- Zentrale Standardwerte für Button-Beschriftung, Formular-ID und eigenen Shortcode.
- Neues Feld für Standard-CSS-Klasse(n) des Buttons.
- Mehrfach-Konfiguration über benannte Presets inkl. neuem Shortcode-Attribut `preset`.
- „Einstellungen"-Link in der Plugin-Übersicht.
- Eindeutige Overlay-IDs über internen Zähler (verhindert Kollisionen bei gleichem Formular mehrfach pro Seite).

### 2.0.2
- Fix: Flatpickr-Datepicker erscheint jetzt über dem Overlay.
- Fix: horizontales Abschneiden randständiger Felder im Formular behoben.

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
