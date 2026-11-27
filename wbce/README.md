# Listings

Seitenmodul für WBCE CMS 1.7: Verzeichnisse von Einträgen mit eigenen Feldern, die Besucher selbst anlegen und pflegen können, mit Freigabe durch den Admin. Beispiele sind Mitgliederlisten, Kleinanzeigen, Jobbörsen, Branchenbücher oder ein Addon-Repository. Listings ist der Nachfolger von wbProfiles und übernimmt dessen Daten per Import.

- Feldsatz pro Verzeichnis mit 17 Feldtypen, darunter formatierter Text, Bild, Galerie und Datei
- Einträge vom Admin oder von Besuchern, ohne Konto (Bestätigung per E-Mail) oder mit Frontend-Konto
- Freigabe, Massenaktionen, Protokoll, Laufzeit mit Erinnerung, Kontaktformular, Höchstzahl pro Inhaber
- Kategorien, Tags, Suche und Filter, RSS-Feed, Site-Suche, Links aus dem Editor
- Layouts, Formulartext und Mails pro Verzeichnis änderbar, mit Vorschau
- Vorlagen zum Laden und Exportieren, vier mitgeliefert; Import aus wbProfiles

Die Dokumentation steht im Ordner `docs/` und ist im Modul über den Knopf **Hilfe** erreichbar:

| Seite | Inhalt |
|---|---|
| [Übersicht](docs/README.md) | Aufbau, Sichtbarkeit, erste Schritte |
| [Einträge](docs/Eintraege.md) | Liste, Status, Freigabe, Massenaktionen, Bilder und Dateien, Protokoll |
| [Felder](docs/Felder.md) | Feldtypen, Sichtbarkeit, Filter, Reihenfolge |
| [Einträge von Besuchern](docs/Besucher.md) | Ohne und mit Konto, Laufzeit, Kontaktformular, Spamschutz |
| [Kategorien und Tags](docs/Kategorien-und-Tags.md) | Kategoriebaum, Tags, Zusammenführen, Suche und Filter |
| [Layouts und Mails](docs/Layouts.md) | Liste, Detail, Formulartext, Mail-Texte, Vorschau |
| [Platzhalter](docs/Platzhalter.md) | Platzhalter der Layouts und Mails |
| [Einstellungen](docs/Einstellungen.md) | Alle Einstellungen |
| [Vorlagen und Import](docs/Vorlagen-und-Import.md) | Vorlagen, Export, Import aus wbProfiles |
| [Dateien, RSS, Suche](docs/Technik.md) | Ablage der Uploads (mit Nginx-Regel), RSS, Suche, Deinstallieren |

## Voraussetzungen

- WBCE CMS ab 1.7.0, PHP ab 8.1
- MySQL oder MariaDB. Alle Abfragen sind portabel geschrieben, gegen SQLite aber noch nicht getestet.
- Für Bilder die PHP-Erweiterung GD; empfohlen `fileinfo` und `exif`.
- Für Einträge mit Konto die Frontend-Anmeldung der Website, für die Mails ein funktionierender Mailversand von WBCE.

Lizenz: GNU General Public License. Autorin: Bianka Martinovic.
