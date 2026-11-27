# Dateien, RSS, Suche

## Ablage und Schutz der Uploads

Bilder und Dateien liegen unter `media/listings/<section_id>/` mit zufälligen Namen. Der Ordner ist per `.htaccess` gesperrt; ausgeliefert wird nur über `modules/listings/file.php`. Das Skript prüft bei jedem Aufruf:

- Der Eintrag ist veröffentlicht und nicht abgelaufen, das Feld ist öffentlich, Abschnitt und Seite sind für den Besucher sichtbar.
- Oder der Inhaber beziehungsweise Admin hat den Eintrag gerade im Formular geöffnet. Dann sieht er die Dateien auch vor der Freigabe, für zwei Stunden in dieser Sitzung.

Alles andere ergibt 404, auch für vorhandene Dateien. Bilder werden beim Hochladen neu gespeichert: auf die eingestellte längste Kante verkleinert (Standard 1600 Pixel), mit einem Vorschaubild von 400 Pixeln, ohne Kamera- und Ortsdaten. Dateien gehen immer als Download hinaus und zählen ihre Downloads.

**Nginx** liest keine `.htaccess`. Dort braucht der Ordner eine eigene Regel, bei WBCE in einem Unterordner mit dem Pfad davor:

```nginx
location ^~ /media/listings/ {
    deny all;
}
```

Die Größe je Datei begrenzt auch PHP (`upload_max_filesize`). Ist ein Formular größer als `post_max_size`, verwirft PHP alle Angaben; das Modul meldet das verständlich, und das Formular prüft die Größe schon im Browser.

Für Bilder braucht der Server die PHP-Erweiterung GD; ohne sie werden Bild-Uploads mit einer Meldung abgelehnt.

## RSS

Mit **RSS** in den Einstellungen liefert `modules/listings/rss.php?section_id=…` die 20 neuesten sichtbaren Einträge mit ihren öffentlichen Feldern. Der Feed ist nur erreichbar, wenn Abschnitt und Seite für Besucher sichtbar sind. Die Liste verweist im Kopf der Seite darauf.

## Site-Suche

Die Suche von WBCE findet veröffentlichte, nicht abgelaufene Einträge mit Titel und öffentlichen Feldern und verlinkt auf ihre Detailseite.

## Links aus dem Editor

Im Link-Dialog des WYSIWYG-Editors stehen die sichtbaren Einträge aller Listings-Verzeichnisse zur Auswahl.

## Löschen und Deinstallieren

Beim Löschen eines Eintrags gehen seine Bilder und Dateien mit. Beim Löschen eines Abschnitts gehen dessen Einträge, Felder, Einstellungen, Kategorien, Tags, Protokoll und Dateien mit. Beim Deinstallieren des Moduls werden alle Tabellen von Listings und der Ordner `media/listings` entfernt.

## Sicherheit in Kürze

- Jede Aktion im Backend prüft das Formular-Token von WBCE; IDs in Formularen sind signiert und an ihren Zweck gebunden.
- Links in Mails tragen ein zufälliges Token; gespeichert wird nur sein SHA-256-Hash. Bestätigungslinks gelten 24 Stunden, Bearbeitungslinks eine Stunde, Verlängerungslinks 30 Tage.
- Inhaber speichern nur Felder, die sie bearbeiten dürfen; alles andere aus dem Formular wird verworfen.
- Formatierter Text wird beim Speichern auf eine kurze Liste erlaubter Tags reduziert.
