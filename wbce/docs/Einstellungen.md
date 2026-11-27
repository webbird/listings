# Einstellungen

Der Reiter **Einstellungen** gilt für dieses Verzeichnis. Ein Klick auf **Speichern** sichert alle Kästen.

## Liste

| Einstellung | Bedeutung |
|---|---|
| Einträge pro Seite | 1 bis 200 |
| Reihenfolge | Neueste zuerst, Zuletzt veröffentlichte zuerst, Zuletzt geänderte zuerst, Titel A–Z |
| Tags | Aus; An, Inhaber wählen aus den vorhandenen Tags; An, Inhaber dürfen neue Tags anlegen (siehe [Kategorien und Tags](Kategorien-und-Tags.md)) |
| Suche und Filter | Suchformular über der Liste |
| RSS | Feed der 20 neuesten sichtbaren Einträge; die Adresse steht unter dem Schalter |

## Einträge von Besuchern

| Einstellung | Bedeutung |
|---|---|
| Frontend | Besucher dürfen Einträge anlegen und bearbeiten |
| Inhaber | Ohne Konto (Links per E-Mail) oder mit Frontend-Konto der Website |
| Freigabe | Neue Einträge warten auf Freigabe durch den Admin; dazu: Änderungen an veröffentlichten Einträgen werden erneut geprüft |
| Spamschutz | Captcha in den Formularen |
| Benachrichtigen | Adresse, die von neuen bestätigten Einträgen erfährt |

Näheres auf der Seite [Einträge von Besuchern](Besucher.md).

## Laufzeit

| Einstellung | Bedeutung |
|---|---|
| Laufzeit in Tagen | Einträge von Inhabern laufen so viele Tage nach der ersten Veröffentlichung ab; 0: nie, höchstens 3650 |
| Erinnerung (Tage vorher) | Mail mit Verlängerungslink; 0: keine Erinnerung, verlängern erst nach dem Ablauf |
| Einträge pro Inhaber | Höchstzahl bestätigter Einträge je Adresse oder Konto; 0: beliebig |

## Kontakt

**Kontaktformular**: Besucher schreiben dem Inhaber von der Detailseite aus, ohne seine Adresse zu erfahren.

## Import und Vorlagen

Liegen Daten von wbProfiles in der Datenbank, steht unten der Kasten **Import aus wbProfiles**. Vorlagen erreicht man über **Felder → Vorlagen**. Beides beschreibt [Vorlagen und Import](Vorlagen-und-Import.md).

## Ohne Cron

WBCE hat keinen Cron. Unbestätigte Einträge, abgelaufene Links und Erinnerungen erledigt das Modul deshalb bei Aufrufen der Seite und des Backends, Erinnerungen höchstens einmal pro Stunde. Eine selten besuchte Seite verschickt Erinnerungen entsprechend später.
