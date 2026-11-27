# Vorlagen und Import

## Vorlagen

Eine Vorlage ist eine JSON-Datei mit Feldsatz, Einstellungen, Kategorien, Tags, geänderten Layouts, dem Text über dem Formular und geänderten Mails, aber ohne Einträge. Sie stehen unter **Felder → Vorlagen**; ein leeres Verzeichnis bietet **Mit einer Vorlage beginnen** an.

### Mitgeliefert

| Vorlage | Inhalt |
|---|---|
| Mitgliederliste | Logo, Adresse, Region und Leistungen als Filter, Kontaktformular, kein Ablauf, Freigabe |
| Kleinanzeigen | Biete/Suche, Preis, Zustand, bis zu sechs Fotos, 30 Tage Laufzeit, online nach Bestätigung |
| Jobbörse | Unternehmen, Logo, Einsatzort, Art der Anstellung als Filter, PDF zur Stelle, 60 Tage Laufzeit, Freigabe |
| Addon-Repository | Frontend-Konto, Art, Version, Kompatibilität, Lizenz, Screenshots, ZIP-Download, „Vom Team geprüft“ nur für den Admin |

### Laden

**Laden** bzw. **Vorlagendatei laden** geht nur in ein leeres Verzeichnis: ohne Felder, Einträge und Kategorien. Alles in der Vorlage durchläuft dieselben Prüfungen wie die Formulare im Backend. Ein Fehler bricht das Laden vollständig ab; die Meldung nennt das Feld. Danach lässt sich alles ändern.

### Exportieren

**Dieses Verzeichnis als Vorlage exportieren** lädt eine Datei `listings-<name>.json` herunter. Sie enthält keine Einträge, keine Bilder und nicht die Benachrichtigungsadresse und lässt sich in einem anderen Verzeichnis oder einer anderen Website laden.

### Eigene Vorlagen im Modul

Eine Datei `presets/<name>.json` im Modulordner erscheint unter den mitgelieferten. Texte dürfen Zeichenketten sein oder Objekte nach Sprache (`{"de": "…", "en": "…"}`); geladen wird die Sprache des Backends, sonst Englisch. Das Format zeigt jeder Export.

## Import aus wbProfiles

Liegen die Tabellen von wbProfiles noch in der Datenbank, bietet **Einstellungen → Import aus wbProfiles** sie an, auch wenn das Modul selbst schon entfernt ist.

1. Ein leeres Verzeichnis anlegen und den Import öffnen.
2. Das wbProfiles-Verzeichnis wählen und den **Probedurchlauf** ansehen. Er ändert nichts und zeigt, welche Felder, Einträge, Kategorien, Tags, Bilder und Dateien übernommen werden, was sich unterwegs ändert und was nicht übernommen wird.
3. **Jetzt importieren**. Der Import läuft ganz oder gar nicht; ein zweiter Import in dasselbe Verzeichnis wird abgelehnt.

Übernommen werden:

- der Feldsatz, aus der Formulardefinition des Templates, sonst aus den gespeicherten Werten. Lange Texte mit HTML werden zu Feldern mit formatiertem Text.
- Einträge, Kategorien (auch der Baum), Tags, Bilder als Galerie, Dateien als Downloads. Bilder werden dabei neu gespeichert.
- Freigabe und Tags aus den Einstellungen.

Gesperrte und unbestätigte Profile warten auf Freigabe, abgelaufene bleiben abgelaufen. **Doppelt kodierte Umlaute reparieren** macht aus „SÃ¼ddorf“ wieder „Süddorf“.

Nicht übernommen werden Passwörter (Inhaber fordern einen Bearbeitungslink an oder melden sich mit einem Konto an), Kopfbilder und die `.tpl`-Templates; Liste und Detail beginnen mit den Standard-Layouts. Bilder und Dateien, die auf der Festplatte fehlen, nennt der Bericht. Einträge von Besuchern bleiben nach dem Import ausgeschaltet. Die Daten von wbProfiles bleiben unverändert.
