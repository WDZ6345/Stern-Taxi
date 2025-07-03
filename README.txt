=== Auto Inserate ===
Contributors: WDZ
Tags: auto, car, listings, dealership, vehicle, inserate, fahrzeuge, autohandel
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Verwalten und verkaufen Sie Autos einfach. Erweiterte Suche, Fahrzeugdaten, Lead-Erfassung, Galerie, Karten. Ideal für Autohändler.

== Description ==

Das "Auto Inserate" Plugin für WordPress ermöglicht es Ihnen, Fahrzeuginserate einfach zu erstellen, zu verwalten und auf Ihrer Webseite anzuzeigen. Es ist ideal für Autohändler oder private Verkäufer.

**Hauptfunktionen:**

*   **Benutzerdefinierter Beitragstyp "Fahrzeuge":** Speziell für Fahrzeuginserate.
*   **Detaillierte Fahrzeuginformationen:** Fügen Sie Preis, Kilometerstand, Erstzulassung, Leistung, Getriebe, Farbe und mehr hinzu.
*   **Taxonomien:** Kategorisieren Sie Fahrzeuge nach Marke, Modell, Zustand und Kraftstoffart.
*   **Frontend-Anzeige:** Automatische Erstellung von Archiv- und Einzelansichtsseiten für Fahrzeuge.
*   **Erweiterte Suche:** Ein leistungsstarkes Suchformular (per Shortcode einfügbar), um Fahrzeuge nach verschiedenen Kriterien zu filtern.
*   **Bildergalerie:** Nutzen Sie die WordPress-Galeriefunktion, um mehrere Bilder pro Fahrzeug anzuzeigen.
*   **Lead-Erfassung:** Ein Kontaktformular auf jeder Fahrzeugdetailseite, damit Interessenten Anfragen senden können.
*   **Kartenlink:** Zeigen Sie den Fahrzeugstandort über einen Link zu Google Maps an.
*   **Shortcodes:**
    *   `[auto_inserate_suche]`: Zeigt das erweiterte Suchformular an.
    *   `[auto_inserate_fahrzeugliste anzahl="5" sortierung="datum_ab"]`: Zeigt eine Liste von Fahrzeugen an (weitere Attribute verfügbar).
*   **Einfache Admin-Verwaltung:** Einstellungsseite für zukünftige Konfigurationen.
*   **Bereit für Übersetzungen.**

== Installation ==

1.  Laden Sie den Plugin-Ordner `auto-inserate` in das Verzeichnis `/wp-content/plugins/` hoch.
2.  Aktivieren Sie das Plugin über das Menü "Plugins" in WordPress.
3.  Gehen Sie zu "Fahrzeuge" im Admin-Menü, um Ihre ersten Inserate hinzuzufügen.
4.  Fügen Sie den Shortcode `[auto_inserate_suche]` auf einer Seite ein, um das Suchformular anzuzeigen.
5.  Die Fahrzeug-Archivseite finden Sie standardmäßig unter `your-website.com/fahrzeuge/`.
6.  (Optional) Erstellen Sie Template-Dateien `single-fahrzeug.php` und `archive-fahrzeug.php` in Ihrem Theme-Ordner (oder `auto-inserate` Unterordner), um die Darstellung anzupassen. Das Plugin bringt eigene Templates mit, falls keine Theme-spezifischen vorhanden sind.

== Frequently Asked Questions ==

= Wie verwende ich den Shortcode für die Fahrzeugliste? =

Sie können den Shortcode `[auto_inserate_fahrzeugliste]` verwenden, um Fahrzeuglisten auf Seiten oder in Beiträgen anzuzeigen. Hier sind einige Attribute, die Sie verwenden können:
*   `anzahl`: Die maximale Anzahl der anzuzeigenden Fahrzeuge (Standard: 5). Beispiel: `anzahl="10"`
*   `sortierung`: Nach welchem Kriterium sortiert werden soll. Optionen:
    *   `datum_ab` (Neueste zuerst - Standard)
    *   `datum_auf` (Älteste zuerst)
    *   `preis_auf` (Preis aufsteigend)
    *   `preis_ab` (Preis absteigend)
    *   `titel_auf` (Titel A-Z)
    *   `titel_ab` (Titel Z-A)
    Beispiel: `sortierung="preis_auf"`
*   `marke`: Filtern nach Marken-Slug. Beispiel: `marke="volkswagen"`
*   `modell`: Filtern nach Modell-Slug. Beispiel: `modell="golf"`
*   `zustand`: Filtern nach Zustands-Slug. Beispiel: `zustand="gebrauchtwagen"`
*   `spalten`: Anzahl der Spalten für die Grid-Ansicht (Standard: 3). Beispiel: `spalten="2"`
*   `ids`: Zeigt spezifische Fahrzeuge anhand ihrer IDs an, kommagetrennt. Beispiel: `ids="12,34,56"`

Kombiniertes Beispiel: `[auto_inserate_fahrzeugliste anzahl="6" marke="bmw" sortierung="preis_ab" spalten="3"]`

= Wie verwende ich den Such-Shortcode? =

Fügen Sie einfach `[auto_inserate_suche]` auf einer beliebigen Seite ein. Das Suchformular wird dort angezeigt. Die Suchergebnisse erscheinen auf der Fahrzeug-Archivseite.

= Kann ich das Aussehen der Fahrzeugseiten anpassen? =

Ja. Das Plugin bringt Standard-Templates mit. Für eine tiefere Integration in Ihr Theme können Sie eigene Templates erstellen:
1.  Kopieren Sie die Dateien `single-fahrzeug.php` und `archive-fahrzeug.php` aus dem Plugin-Verzeichnis `wp-content/plugins/auto-inserate/public/templates/` in Ihren Theme-Ordner.
2.  Alternativ können Sie sie auch in einem Unterordner `auto-inserate` in Ihrem Theme-Ordner ablegen (z.B. `your-theme/auto-inserate/single-fahrzeug.php`).
3.  Passen Sie diese kopierten Dateien nach Ihren Wünschen an.

= Wie werden Fahrzeugbilder gehandhabt? =

Das erste Bild, das Sie als "Beitragsbild" für ein Fahrzeuginserat festlegen, wird als Hauptbild in Listen und im oberen Bereich der Detailseite verwendet.
Für weitere Bilder können Sie im Inhaltseditor des Fahrzeuginserats eine WordPress-Galerie einfügen. Diese wird dann in der Fahrzeugbeschreibung angezeigt.

== Screenshots ==

1.  Die Fahrzeug-Archivseite mit Suchergebnissen.
2.  Die Einzelansicht eines Fahrzeugs mit Details und Kontaktformular.
3.  Das erweiterte Suchformular.
4.  Die Admin-Ansicht zum Bearbeiten eines Fahrzeugs mit den benutzerdefinierten Feldern.
5.  Die Einstellungsseite des Plugins.

(Hier würden normalerweise Beschreibungen für Screenshots stehen, die man hochlädt)

== Changelog ==

= 1.0.0 - TT.MM.JJJJ =
* Initial release.

== Manuelle Testszenarien ==

Um die Funktionalität des Plugins zu testen, sollten folgende Szenarien überprüft werden:

1.  **Plugin Aktivierung/Deaktivierung:**
    *   Plugin aktivieren: Keine Fehler? CPT "Fahrzeuge" und Taxonomien im Menü sichtbar?
    *   Plugin deaktivieren: Keine Fehler?
2.  **Fahrzeug erstellen/bearbeiten:**
    *   Neues Fahrzeug erstellen: Alle Meta-Felder (Preis, KM, EZ, etc.), Taxonomien (Marke, Modell, etc.), Titel, Beschreibung und Beitragsbild können eingegeben und gespeichert werden?
    *   Fahrzeug bearbeiten: Werden alle Daten korrekt geladen und können geändert und gespeichert werden?
    *   WordPress-Galerie in Beschreibung einfügen und speichern.
3.  **Frontend-Anzeige (Einzelansicht):**
    *   Wird das Fahrzeug korrekt mit allen Details (Titel, Beschreibung, Meta-Daten, Taxonomien, Beitragsbild) angezeigt?
    *   Wird eine eingefügte Galerie korrekt angezeigt?
    *   Funktioniert der Kartenlink (falls Adresse eingegeben)?
    *   Wird das Kontaktformular angezeigt?
4.  **Frontend-Anzeige (Archivansicht /fahrzeuge/):**
    *   Werden alle veröffentlichten Fahrzeuge in einer Grid-Ansicht angezeigt (Bild, Titel, Kurzinformationen)?
    *   Funktioniert die Paginierung, wenn mehr Fahrzeuge vorhanden sind als pro Seite angezeigt werden?
5.  **Suche:**
    *   Suchformular (via Shortcode `[auto_inserate_suche]` auf einer Seite platziert) verwenden:
        *   Suche nach Stichwort.
        *   Filter nach Marke, Modell, Zustand, Kraftstoffart.
        *   Filter nach Preisspanne.
        *   Filter nach max. Kilometerstand.
        *   Filter nach Erstzulassungsjahr.
        *   Kombinationen von Filtern.
        *   Werden die Suchergebnisse korrekt auf der Archivseite angezeigt?
        *   Funktioniert der "Filter zurücksetzen"-Link?
6.  **Shortcode `[auto_inserate_fahrzeugliste]`:**
    *   Auf einer Seite einfügen mit verschiedenen Attributen (Anzahl, Sortierung, Filter, Spalten, IDs).
    *   Wird die Liste korrekt entsprechend der Attribute angezeigt?
7.  **Kontaktformular (Lead-Erfassung):**
    *   Formular auf der Einzelansicht ausfüllen und absenden.
    *   Pflichtfelder-Validierung testen (Fehlermeldung bei leeren Feldern?).
    *   Gültige E-Mail-Validierung testen.
    *   Erfolgsmeldung nach Absenden?
    *   Kommt die E-Mail beim Admin an? Ist der Inhalt korrekt (Bezug zum Fahrzeug, Absenderdaten)?
8.  **Internationalisierung:**
    *   Wenn WordPress auf Deutsch eingestellt ist, sind alle Plugin-Texte im Frontend und Adminbereich auf Deutsch? (Setzt voraus, dass .mo Datei vorhanden und geladen wird).
9.  **Responsiveness:**
    *   Alle Frontend-Ansichten (Archiv, Einzelansicht, Suchformular, Shortcode-Listen) auf verschiedenen Bildschirmgrößen (Desktop, Tablet, Mobil) testen. Bleibt das Layout benutzbar?

Diese Liste deckt die Kernfunktionalitäten ab. Spezifische Edge Cases oder Serverkonfigurationen könnten weitere Tests erfordern.
