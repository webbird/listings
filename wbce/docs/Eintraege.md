# Einträge

## Die Liste

Der Reiter **Einträge** zeigt alle Einträge des Verzeichnisses. Wartende Einträge stehen oben und sind am linken Rand markiert, danach folgen die zuletzt geänderten. Die Zahl am Reiter nennt die wartenden Einträge.

Über der Liste filtert eine Leiste nach Status: **Alle**, **Entwurf**, **Wartet auf Freigabe**, **Veröffentlicht**, **Abgelehnt**, jeweils mit Anzahl.

| Status | Bedeutung |
|---|---|
| Entwurf | Vom Admin angelegt und noch nicht veröffentlicht |
| Unbestätigt | Von einem Besucher ohne Konto angelegt, der Bestätigungslink ist noch nicht geöffnet. Wird nach 48 Stunden gelöscht |
| Wartet auf Freigabe | Bestätigt oder geändert, der Admin muss freigeben |
| Veröffentlicht | Auf der Website sichtbar, solange er nicht abgelaufen ist |
| Abgelehnt | Nicht sichtbar. Der Inhaber kann ihn ändern, dann wartet er wieder |

Abgelaufene Einträge behalten ihren Status; das Ablaufdatum ist in der Spalte **Läuft ab** rot markiert.

## Freigeben und ablehnen

Bei wartenden Einträgen stehen in der Zeile **Freigeben** und **Ablehnen**. Ein Klick ändert den Status ohne Neuladen der Seite; der Inhaber bekommt eine Mail. Konnte die Mail nicht versendet werden, steht das in der Meldung.

## Massenaktionen

Die Kästchen links wählen Einträge aus, das Kästchen im Tabellenkopf alle der Seite. Unter der Liste stehen dann **veröffentlichen**, **ablehnen** und **löschen** zur Wahl. Beim Löschen fragt das Modul nach. Die Inhaber bekommen dieselben Mails wie bei einzelnen Freigaben.

## Einen Eintrag bearbeiten

**Neuer Eintrag** oder ein Klick auf den Titel öffnet das Formular mit allen Feldern, auch denen, die nur der Admin sieht (markiert mit **Nur Admin** bzw. **Nicht öffentlich**). Darunter folgen Kategorien, Tags und der Kasten **Veröffentlichung**:

- **Status**: wie in der Tabelle oben. Wechselt er auf veröffentlicht oder abgelehnt, bekommt der Inhaber eine Mail.
- **E-Mail des Inhabers**: Wer diese Adresse hat, kann den Eintrag über einen Bearbeitungslink ändern. Leer: Nur der Admin pflegt den Eintrag.
- **Läuft ab**: leer heißt nie. Bei Einträgen von Besuchern setzt das Modul das Datum bei der ersten Veröffentlichung aus der Laufzeit.
- **Adresse (Slug)**: der Teil der URL der Detailseite. Leer: aus dem Titel gebildet.

**Speichern und weiter bearbeiten** bleibt im Formular, **Speichern** kehrt zur Liste zurück.

## Bilder und Dateien

Bei Feldern vom Typ Bild, Mehrere Bilder und Dateien zum Herunterladen erscheinen die gespeicherten Dateien mit einem Kästchen **entfernen**. Darunter wählt man neue Dateien. Ein neues Bild in einem Bildfeld ersetzt das bisherige. Bei mehreren Bildern oder Dateien lässt sich die Reihenfolge durch Ziehen oder mit den Pfeilen ◀ ▶ ändern.

Die Grenzen (Anzahl, Größe, erlaubte Endungen) stehen unter dem Feld. Zu große Dateien hält schon der Browser auf. Hat ein anderes Feld einen Fehler, muss man die Dateien neu auswählen, weil der Browser sie nicht erneut mitschickt. Bilder werden beim Speichern verkleinert und ohne Kamera- und Ortsdaten neu gespeichert, siehe [Dateien, RSS, Suche](Technik.md).

## Protokoll

**Protokoll** oben rechts zeigt, was mit den Einträgen geschah: angelegt, bestätigt, geändert, Status, gelöscht, verlängert, Erinnerung verschickt, Nachricht an den Inhaber, nie bestätigt und gelöscht, Import, Vorlage geladen. Zu jeder Zeile stehen Zeitpunkt und wer es war (Admin mit Namen, Inhaber, Besucher, System).

Das Protokoll speichert keine E-Mail-Adressen und keine Nachrichtentexte und vergisst Einträge nach 730 Tagen. Die letzten 20 Einträge eines Eintrags stehen auch unter seinem Formular als **Verlauf**.
