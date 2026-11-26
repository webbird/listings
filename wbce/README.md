# Listings

Seitenmodul für WBCE CMS 1.7: Verzeichnisse von Einträgen mit eigenen Feldern, die Besucher selbst anlegen und pflegen können, mit Freigabe durch den Admin. Beispiele sind Mitgliederlisten, Kleinanzeigen, Jobbörsen, Branchenbücher oder ein Addon-Repository. Listings ist der Nachfolger von wbProfiles und übernimmt dessen Daten per Import.

- Feldsatz pro Verzeichnis, zusammengestellt im Backend, mit 17 Feldtypen, darunter formatierter Text, Bild, Galerie und Datei; Reihenfolge per Drag-and-drop
- Massenaktionen, Freigabe ohne Neuladen, Protokoll aller Aktionen, Höchstzahl an Einträgen pro Inhaber
- Mail-Texte und Text über dem Formular pro Verzeichnis änderbar, Vorschau für Layouts, Formular und Mails
- Einträge vom Admin oder von Besuchern, ohne Konto (Bestätigung per E-Mail) oder mit Frontend-Konto
- Freigabe, Laufzeit mit Erinnerung und Verlängerung, Kontaktformular an den Inhaber
- Kategorien, Tags, Suche und Filter, RSS-Feed, Site-Suche, Links aus dem Editor
- Layouts für Liste und Detail, im Backend änderbar
- Vorlagen (Feldsatz, Einstellungen, Kategorien, ohne Einträge) zum Laden und Exportieren; vier werden mitgeliefert

## Voraussetzungen

- WBCE CMS ab 1.7.0, PHP ab 8.1
- MySQL oder MariaDB. Alle Abfragen sind portabel geschrieben, gegen SQLite aber noch nicht getestet.
- Für Bilder die PHP-Erweiterung GD (JPEG und PNG, für WebP mit WebP-Unterstützung). Empfohlen: `fileinfo` und `exif`.
- Für Einträge mit Konto die Frontend-Anmeldung der Website (Grundeinstellungen).
- Für die Mails ein funktionierender Mailversand von WBCE.

## Erste Schritte

1. Eine Seite anlegen und einen Abschnitt vom Typ **Listings** hinzufügen.
2. Unter **Felder** eine Vorlage laden („Mit einer Vorlage beginnen“) oder Felder selbst anlegen.
3. Unter **Einstellungen** festlegen, ob Besucher Einträge anlegen dürfen, ob sie dafür ein Konto brauchen, ob der Admin freigibt und wie lange Einträge laufen.
4. Unter **Einträge** eigene Einträge anlegen und Einträge von Besuchern freigeben oder ablehnen.

Ein Abschnitt ist ein Verzeichnis: genau ein Feldsatz, beliebig viele Einträge, eigene Einstellungen. Mehrere Verzeichnisse auf einer Seite stören sich nicht.

## Felder

Jedes Feld hat eine Beschriftung, einen Namen (für die Layouts, nach den ersten Einträgen nicht mehr änderbar), einen Typ und diese Angaben:

| Angabe | Bedeutung |
|---|---|
| Pflichtfeld | ohne Wert lässt sich der Eintrag nicht speichern |
| Sichtbar | **Öffentlich**, **Inhaber und Admin** (etwa die Telefonnummer) oder **Nur Admin** (interne Notizen) |
| In der Liste zeigen | erscheint auch in der Liste, nicht nur auf der Detailseite |
| Der Inhaber darf es bearbeiten | sonst pflegt nur der Admin das Feld, etwa „geprüft“ |
| Als Filter anbieten | nur Auswahlfelder, die öffentlich sind |

| Typ | Eingabe | Optionen |
|---|---|---|
| Text; Text, mehrzeilig | Textfeld | Höchstlänge |
| Formatierter Text | im Backend der Editor der Website, im Frontend ein schlanker Editor; gespeichert werden nur Absätze, fett, kursiv, Listen, Links und Zwischenüberschriften. Layouts: `F.HTML`, `ITEM.<NAME>_HTML` | Höchstlänge |
| Zahl | Zahlenfeld | Minimum, Maximum |
| E-Mail-Adresse, Webadresse, Telefonnummer | Textfeld mit Prüfung | Webadressen nur mit `http` oder `https` |
| Datum, Datum und Uhrzeit | Datumsauswahl | |
| Auswahlliste, Optionsfelder, Kontrollkästchen | eine Option oder mehrere | Werte als `wert|Beschriftung`, je Zeile einer |
| Ja/Nein | Schalter | |
| Bild, Mehrere Bilder | Upload | Höchstzahl, Größe je Datei, längste Kante |
| Dateien zum Herunterladen | Upload | Höchstzahl, Größe je Datei, erlaubte Endungen |
| Zwischenüberschrift im Formular | gliedert das Formular | speichert nichts |

Werte gelöschter Felder bleiben in den Einträgen und erscheinen wieder, wenn ein Feld mit demselben Namen angelegt wird.

## Einträge von Besuchern

Unter **Einstellungen → Einträge von Besuchern** wird das Frontend eingeschaltet. Es gibt zwei Arten von Inhabern:

**Ohne Konto** (Links per E-Mail): Der Besucher füllt das Formular aus und bekommt einen Bestätigungslink. Unbestätigte Einträge werden nach 48 Stunden gelöscht. Später fordert der Inhaber über „Eigenen Eintrag ändern“ einen Link für eine Stunde an. Die Antwort ist dieselbe, ob die Adresse Einträge hat oder nicht, so erfährt niemand, wer eingetragen ist.

**Mit Frontend-Konto**: Der Inhaber meldet sich an, braucht keine Bestätigung und findet seine Einträge unter „Meine Einträge“. Voraussetzung ist die Frontend-Anmeldung der Website.

Weitere Schalter:

- **Freigabe**: Bestätigte Einträge warten auf den Admin. Mit **erneut prüfen** wartet auch ein geänderter veröffentlichter Eintrag wieder.
- **Benachrichtigen**: Diese Adresse erfährt von neuen Einträgen.
- **Captcha**: das Captcha von WBCE. Dazu kommen immer ein Honeypot-Feld und eine Mindestzeit zum Ausfüllen. Angemeldete Inhaber sehen kein Captcha.
- **Laufzeit**: Einträge von Inhabern laufen so viele Tage nach der ersten Veröffentlichung ab (0: nie). Vor dem Ablauf bekommt der Inhaber eine Erinnerung mit einem Link zum Verlängern. Abgelaufene Einträge verschwinden aus der Liste und bleiben im Backend.
- **Kontaktformular**: Besucher schreiben dem Inhaber von der Detailseite aus. Die Adresse des Inhabers bleibt verborgen, höchstens drei Nachrichten pro Stunde und Besucher.

WBCE hat keinen Cron. Unbestätigte Einträge, alte Links und Erinnerungen werden deshalb bei Seitenaufrufen und im Backend erledigt, Erinnerungen höchstens einmal pro Stunde.

## Bilder und Dateien

Uploads liegen unter `media/listings/<section_id>/` mit zufälligen Namen. Der Ordner ist per `.htaccess` gesperrt, ausgeliefert wird nur über `modules/listings/file.php`. Das Skript prüft vor jeder Datei:

- Der Eintrag ist veröffentlicht und nicht abgelaufen, das Feld ist öffentlich, Seite und Abschnitt sind sichtbar.
- Oder der Inhaber beziehungsweise Admin hat den Eintrag gerade im Formular geöffnet. Dann sieht er die Dateien auch vor der Freigabe, für zwei Stunden in dieser Sitzung.

Alles andere ergibt 404. Bilder werden beim Hochladen neu gespeichert: auf die eingestellte längste Kante verkleinert (Standard 1600 Pixel), mit einem Vorschaubild von 400 Pixeln, ohne Kamera- und Ortsdaten. Dateien werden immer als Download ausgeliefert, nie im Browser angezeigt, und zählen ihre Downloads. Skripte, HTML und SVG nimmt das Modul nie an, auch wenn sie in den erlaubten Endungen stehen.

**Nginx** liest keine `.htaccess`. Dort braucht der Ordner eine eigene Regel:

```nginx
location ^~ /media/listings/ {
    deny all;
}
```

Bei WBCE in einem Unterordner den Pfad davor setzen, etwa `/wbce/media/listings/`.

Die Größe je Datei begrenzt auch PHP (`upload_max_filesize`). Wird ein Formular größer als `post_max_size`, verwirft PHP alle Angaben. Das Formular prüft die Größe deshalb schon im Browser, und der Server meldet den Fall verständlich.

## Layouts

Liste und Detailseite sind Layouts im Format des LayoutParsers von WBCE. Sie lassen sich unter **Layouts** ändern und jederzeit auf den Standard zurücksetzen. Layouts führen nie PHP aus, und alle Werte werden automatisch escaped.

Jeder Eintrag (`ITEM`) bringt mit:

| Platzhalter | Inhalt |
|---|---|
| `[ITEM.TITLE]`, `[ITEM.URL]`, `[ITEM.ID]`, `[ITEM.SLUG]` | Titel, Link zur Detailseite |
| `[ITEM.CREATED]`, `[ITEM.UPDATED]`, `[ITEM.PUBLISHED]`, `[ITEM.EXPIRES]` | Zeitstempel, auszugeben mit `\|datetime` |
| `[ITEM.<FELDNAME>]` | jedes öffentliche Feld unter seinem Namen in Großbuchstaben, etwa `[ITEM.PHONE]` |
| `{foreach ITEM.FIELDS as F}` | alle Felder mit Wert: `F.LABEL`, `F.VALUE`, `F.TYPE`, `F.NAME`; bei Bildern und Dateien `F.FILES` |
| `[ITEM.IMAGE]`, `[ITEM.THUMB]`, `[ITEM.IMAGE_NAME]` | erstes Bild; in der Liste nur aus Bildfeldern mit „In der Liste zeigen“ |
| `{foreach ITEM.<BILDFELD> as P}` | Bilder und Dateien eines Feldes: `P.URL`, `P.THUMB`, `P.NAME`, `P.SIZE`, `P.DOWNLOADS`, `P.WIDTH`, `P.HEIGHT` |
| `{foreach ITEM.CATEGORIES as C}`, `{foreach ITEM.TAGS as T}` | `C.NAME`, `C.URL` zur gefilterten Liste |

Die Liste hat außerdem `ITEMS`, `COUNT`, `PAGE`, `PAGES`, `PREV_URL`, `NEXT_URL`, `FILTER` (das Suchformular), `FILTERED`, `CATEGORY`, `TAG`, `RSS_URL`, `NEW_URL` und `LINK_URL`. Die Detailseite hat `CONTACT` (das Kontaktformular) und `LIST_URL`. Die vollständige Liste steht im Kopf der Standard-Layouts unter `layouts/`.

Die Formulare für Besucher erzeugt das Modul aus dem Feldsatz. Ihr Aussehen bestimmt `frontend.css`.

## Finden

- **Kategorien** bilden einen Baum. Ein Eintrag kann in mehreren Kategorien stehen, versteckte Kategorien sieht nur der Admin.
- **Tags**: aus, nur die Tags des Admins, oder Inhaber dürfen neue anlegen.
- **Suche und Filter** über der Liste: Wörter in Titel und öffentlichen Textfeldern, Kategorie, Tag und die als Filter markierten Auswahlfelder.
- **RSS**: die 20 neuesten sichtbaren Einträge unter `modules/listings/rss.php?section_id=…`.
- **Site-Suche**: Die Suche von WBCE findet veröffentlichte Einträge mit ihren öffentlichen Feldern.
- **Links aus dem Editor**: Im Link-Dialog des WYSIWYG-Editors stehen die Einträge zur Auswahl.

## Vorlagen

Eine Vorlage ist eine JSON-Datei mit Feldsatz, Einstellungen, Kategorien, Tags und geänderten Layouts, ohne Einträge. Unter **Felder → Vorlagen**:

- **Laden**: eine mitgelieferte Vorlage oder eine Datei. Das geht nur in ein leeres Verzeichnis (ohne Felder, Einträge und Kategorien). Alles darin durchläuft dieselben Prüfungen wie die Formulare im Backend; ein Fehler bricht das Laden vollständig ab.
- **Exportieren**: das aktuelle Verzeichnis als Datei. Einträge, Bilder und die Benachrichtigungsadresse bleiben draußen.

Mitgeliefert werden **Mitgliederliste**, **Kleinanzeigen**, **Jobbörse** und **Addon-Repository** im Ordner `presets/`. Weitere Vorlagen legt man als `presets/<name>.json` daneben. Texte dürfen einfache Zeichenketten sein oder Objekte nach Sprache (`{"de": "…", "en": "…"}`); geladen wird die Sprache des Backends, sonst Englisch.

```json
{
    "format": "listings-preset",
    "version": 1,
    "name": {"de": "Branchenbuch", "en": "Business directory"},
    "settings": {"frontend": true, "approval": true, "runtime_days": 365},
    "fields": [
        {"name": "city", "type": "text", "label": {"de": "Ort", "en": "City"}, "required": true, "in_list": true},
        {"name": "sector", "type": "select", "label": "Branche", "filterable": true,
         "choices": [{"value": "trade", "label": "Handel"}, {"value": "craft", "label": "Handwerk"}]},
        {"name": "logo", "type": "image", "label": "Logo", "options": {"max_px": 600}}
    ],
    "categories": [{"name": "Innenstadt", "children": [{"name": "Altstadt"}]}],
    "tags": ["Barrierefrei"]
}
```

## Import aus wbProfiles

Wenn die Tabellen von wbProfiles noch in der Datenbank liegen, bietet **Einstellungen → Import aus wbProfiles** sie an. Der Import geht nur in ein leeres Verzeichnis und zeigt zuerst einen Probedurchlauf, der nichts ändert. Übernommen werden:

- der Feldsatz (aus der Formulardefinition des Templates, sonst aus den gespeicherten Werten)
- Einträge, Kategorien und Tags
- Bilder und Downloads, Bilder dabei neu gespeichert
- Freigabe und Tags aus den Einstellungen

Gesperrte und unbestätigte Profile warten auf Freigabe, abgelaufene bleiben abgelaufen. Doppelt kodierte Umlaute („SÃ¼ddorf“) lassen sich dabei reparieren. Nicht übernommen werden Passwörter (Inhaber fordern einen Bearbeitungslink an oder melden sich mit einem Konto an), Kopfbilder und die `.tpl`-Templates. Was fehlt oder geändert wurde, steht im Bericht. Einträge von Besuchern bleiben danach ausgeschaltet, bis der Admin sie einschaltet. Die Daten von wbProfiles bleiben unverändert.

## Sicherheit

- Jede Aktion prüft das Formular-Token von WBCE, IDs in Formularen sind signiert und an ihren Zweck gebunden.
- Links in Mails tragen ein zufälliges Token, gespeichert wird nur sein SHA-256-Hash. Bestätigungslinks gelten 24 Stunden, Bearbeitungslinks eine Stunde, Verlängerungslinks 30 Tage.
- Inhaber speichern nur Felder, die sie bearbeiten dürfen; alles andere aus dem Formular wird verworfen.
- Uploads: siehe [Bilder und Dateien](#bilder-und-dateien).

## Deinstallieren

Beim Löschen eines Abschnitts gehen dessen Einträge, Felder, Einstellungen, Kategorien, Tags und Dateien mit. Beim Deinstallieren des Moduls werden alle Tabellen von Listings und der Ordner `media/listings` entfernt.

## Lizenz

GNU General Public License. Autorin: Bianka Martinovic.
