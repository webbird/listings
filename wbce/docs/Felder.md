# Felder

Der Reiter **Felder** zeigt den Feldsatz in der Reihenfolge des Formulars. **Neues Feld** legt ein Feld an, **Vorlagen** lädt einen ganzen Feldsatz (siehe [Vorlagen und Import](Vorlagen-und-Import.md)).

## Angaben eines Feldes

| Angabe | Bedeutung |
|---|---|
| Beschriftung | Was Besucher und Admins sehen |
| Name | Für die Layouts (`[ITEM.NAME]`). Leer: aus der Beschriftung gebildet. Hat das Verzeichnis Einträge, lässt er sich nicht mehr ändern, weil die Werte unter ihm gespeichert sind |
| Typ | Siehe unten |
| Hilfetext | Steht unter dem Feld im Formular |
| Pflichtfeld | Ohne Wert lässt sich der Eintrag nicht speichern |
| In der Liste zeigen | Erscheint auch in der Liste, nicht nur auf der Detailseite. Bei Bildfeldern: liefert das Vorschaubild der Liste |
| Der Inhaber darf es bearbeiten | Sonst pflegt nur der Admin das Feld, etwa „Vom Team geprüft“ |
| Als Filter über der Liste anbieten | Nur bei öffentlichen Auswahlfeldern; ein Filter auf ein verborgenes Feld würde dessen Wert verraten |
| Sichtbar | **Öffentlich**, **Inhaber und Admin** (etwa die Telefonnummer) oder **Nur Admin** (interne Notizen). Felder für den Admin sehen Inhaber nie, auch nicht im eigenen Formular |

## Feldtypen

| Typ | Eingabe | Optionen |
|---|---|---|
| Text | einzeiliges Feld | Maximale Länge (Standard 255) |
| Text, mehrzeilig | Textbereich | Maximale Länge (Standard 10 000) |
| Formatierter Text | im Backend der Editor der Website, im Frontend ein kleiner Editor mit Fett, Kursiv, Listen und Links | Maximale Länge (Standard 20 000) |
| Zahl | Zahlenfeld, Komma oder Punkt | Minimum, Maximum |
| E-Mail-Adresse, Webadresse, Telefonnummer | Textfeld mit Prüfung | Webadressen nur mit `http` oder `https` |
| Datum, Datum und Uhrzeit | Datumsauswahl | |
| Auswahlliste, Eins von mehreren, Mehrere von mehreren | eine oder mehrere Optionen | Auswahlwerte als `wert|Beschriftung`, je Zeile einer |
| Ja/Nein | Kästchen | |
| Bild, Mehrere Bilder | Upload | Höchstzahl (bei Bild immer 1), Höchstgröße je Datei, längste Kante |
| Dateien zum Herunterladen | Upload | Höchstzahl, Höchstgröße je Datei, erlaubte Endungen |
| Zwischenüberschrift im Formular | gliedert das Formular | speichert nichts |

**Formatierter Text** wird beim Speichern auf Absätze, Fett, Kursiv, Unterstrichen, Listen, Links (nur `http`, `https`, `mailto`), Zwischenüberschriften und Zitate reduziert, egal was der Editor sendet. Skripte, Bilder, Farben und Stile entfallen.

Bei **Auswahlfeldern** steht der Wert (links vom `|`) in der Datenbank und in Filter-Links, die Beschriftung sieht der Besucher. Werte nachträglich zu ändern lässt Einträge mit dem alten Wert ohne Anzeige.

Bei **Dateien** nimmt das Modul Skripte, HTML und SVG nie an, auch wenn sie in den erlaubten Endungen stehen. Die Höchstgröße kann nicht über dem Wert liegen, den der Server erlaubt; er steht unter dem Feld.

## Reihenfolge

Die Zeilen der Tabelle lassen sich mit der Maus ziehen; die neue Reihenfolge wird sofort gespeichert und kurz bestätigt. Die Pfeile ▲ ▼ links tun dasselbe Schritt für Schritt, auch per Tastatur.

## Löschen

Ein gelöschtes Feld verschwindet aus Formularen und Layouts. Die Werte bleiben in den Einträgen und erscheinen wieder, wenn ein Feld mit demselben Namen angelegt wird.
