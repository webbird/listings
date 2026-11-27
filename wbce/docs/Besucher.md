# Einträge von Besuchern

Unter **Einstellungen → Einträge von Besuchern** schaltet **Besucher dürfen Einträge anlegen und bearbeiten** das Frontend ein. Über der Liste erscheinen dann die Links **Eintrag hinzufügen** und **Eigenen Eintrag ändern** (mit Konto: **Meine Einträge**).

## Ohne Konto: Links per E-Mail

1. Der Besucher füllt das Formular aus, mit seiner E-Mail-Adresse und der Zustimmung zur Speicherung.
2. Er bekommt eine Mail mit einem Bestätigungslink, 24 Stunden gültig. Die Mail enthält nichts, was er eingetippt hat, damit niemand über das Formular fremde Adressen mit eigenen Texten beschicken kann.
3. Nach der Bestätigung wartet der Eintrag auf Freigabe oder ist sofort online (Schalter **Freigabe**).
4. Später fordert der Inhaber über **Eigenen Eintrag ändern** einen Bearbeitungslink an, eine Stunde gültig. Die Antwort ist dieselbe, ob die Adresse Einträge hat oder nicht: So erfährt niemand, wer eingetragen ist.

Einträge, die nie bestätigt wurden, löscht das Modul nach 48 Stunden mit ihren Bildern.

## Mit Frontend-Konto

Mit **Inhaber: Mit Frontend-Konto der Website** meldet sich der Inhaber mit seinem WBCE-Konto an. Er braucht keine Bestätigung und findet seine Einträge unter **Meine Einträge**, wo er sie ändert, verlängert oder löscht. Mails gehen an die Adresse des Kontos, auch wenn sie sich später ändert. Voraussetzung ist, dass die Frontend-Anmeldung der Website eingeschaltet ist (Grundeinstellungen); sonst warnt die Einstellung.

## Was der Inhaber ändern darf

Der Inhaber ändert Titel, Kategorien, Tags (je nach Tag-Modus) und die Felder mit **Der Inhaber darf es bearbeiten**. Felder für den Admin und Ablaufdatum bleiben, wie sie sind. Mit **Änderungen an veröffentlichten Einträgen werden erneut geprüft** wartet ein geänderter Eintrag wieder auf Freigabe. Ein abgelehnter Eintrag wartet nach einer Änderung immer wieder.

## Laufzeit, Erinnerung, Verlängern

- **Laufzeit in Tagen**: Einträge von Inhabern laufen so viele Tage nach ihrer ersten Veröffentlichung ab. 0: nie. Einträge des Admins ohne Inhaber laufen nicht ab.
- **Erinnerung (Tage vorher)**: So viele Tage vor dem Ablauf bekommt der Inhaber eine Mail mit einem Link zum Verlängern (30 Tage gültig). Ab dann und nach dem Ablauf lässt sich der Eintrag um eine weitere Laufzeit verlängern.
- Abgelaufene Einträge verschwinden von der Website und bleiben im Backend.

## Höchstzahl pro Inhaber

**Einträge pro Inhaber** begrenzt, wie viele bestätigte Einträge eine E-Mail-Adresse oder ein Konto haben darf (0: beliebig). Das Modul prüft beim Anlegen und noch einmal beim Bestätigen. Unbestätigte Einträge zählen nicht, sonst könnte jemand mit fremder Adresse deren Platz belegen.

## Kontaktformular

Mit **Besucher können dem Inhaber von der Detailseite aus schreiben** steht unter jedem Eintrag mit Inhaber-Adresse ein Formular. Die Adresse des Inhabers bleibt verborgen; die Antwort geht direkt an den Absender. Pro Besucher gehen höchstens drei Nachrichten pro Stunde. Das Kontaktformular funktioniert auch, wenn Einträge von Besuchern ausgeschaltet sind.

## Spamschutz

Jedes Formular hat ein für Menschen unsichtbares Feld (Honeypot) und eine Mindestzeit zum Ausfüllen. Mit **Captcha in den Formularen** kommt das Captcha von WBCE dazu; angemeldete Besucher sehen es nicht. Pro Adresse und Stunde gehen höchstens einige Bestätigungs- und Bearbeitungsmails hinaus.

## Benachrichtigen

**Benachrichtigen** nimmt eine Adresse, die von neuen bestätigten Einträgen erfährt: wartend oder, ohne Freigabe, schon online. Die Mail enthält einen Link zum Eintrag im Backend.
