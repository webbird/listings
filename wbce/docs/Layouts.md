# Layouts und Mails

Der Reiter **Layouts** bestimmt, wie Liste und Detailseite aussehen, welcher Text über dem Formular für neue Einträge steht und was die Mails sagen. Alles gilt nur für dieses Verzeichnis. Ein Klick auf **Speichern** am Ende sichert den ganzen Reiter.

## Liste und Detail

Beide Layouts sind Vorlagen im Format des LayoutParsers von WBCE. Platzhalter stehen in eckigen Klammern, Bedingungen und Schleifen in geschweiften:

```text
{foreach ITEMS as ITEM}
  <h3><a href="[ITEM.URL]">[ITEM.TITLE]</a></h3>
  {if ITEM.CITY}<p>[ITEM.CITY]</p>{/if}
{/foreach}
```

Alle Platzhalter stehen auf der Seite [Platzhalter](Platzhalter.md), die Namen der eigenen Felder zusätzlich über den Layouts im Reiter. Werte werden automatisch escaped; nur formatierter Text kommt als HTML (`[F.HTML]`, `[ITEM.NAME_HTML]`), bereinigt beim Speichern. Layouts führen nie PHP aus und können keine Dateien einbinden.

Ein geändertes Layout trägt das Kennzeichen **geändert**. **Standard-Layout wiederherstellen** setzt es zurück; spätere Verbesserungen des Standards kommen dann wieder an. Ein Layout, das dem Standard gleicht, wird nicht gespeichert.

Die Formulare für Besucher (Neuer Eintrag, Bearbeiten, Kontakt, Suche) erzeugt das Modul aus dem Feldsatz. Ihr Aussehen bestimmt `assets/frontend.css` bzw. das Template der Website.

## Text über dem Formular

**Text über dem Formular** ersetzt den Standardtext über dem Formular für neue Einträge. Leer: Der Standard erklärt je nach Einstellung, wie Bestätigung und Freigabe ablaufen. Reiner Text, Zeilenumbrüche bleiben.

## Mails

Unter **Mails** steht jede Mail des Verzeichnisses zum Aufklappen:

| Mail | An | Wann |
|---|---|---|
| Neuen Eintrag bestätigen | Besucher ohne Konto | nach dem Absenden |
| Bearbeitungslinks | Inhaber ohne Konto | nach „Eigenen Eintrag ändern“ |
| Eintrag veröffentlicht | Inhaber | bei der Freigabe |
| Eintrag abgelehnt | Inhaber | bei der Ablehnung |
| Erinnerung vor dem Ablauf | Inhaber | einige Tage vor dem Ablauf |
| Nachricht an den Inhaber | Inhaber | Kontaktformular, Antwort geht an den Absender |
| An den Admin: Eintrag wartet auf Freigabe | Adresse unter **Benachrichtigen** | ein Eintrag wurde bestätigt |
| An den Admin: neuer Eintrag online | Adresse unter **Benachrichtigen** | ohne Freigabe |

Betreff und Text sind reiner Text in der Sprache der Seite. Platzhalter wie `{TITLE}` oder `{LINK}` setzt das Modul beim Versand ein; welche eine Mail kennt, steht unter ihrem Text. Mit `*` markierte Platzhalter müssen im Text bleiben, sonst wäre die Mail nutzlos (etwa der Bestätigungslink). Die Bestätigungsmail kennt bewusst keinen `{TITLE}`: Sie geht an eine Adresse, die jeder eintippen kann.

Ein unveränderter Text folgt dem Standard aus den Sprachdateien. **Standard-Layout wiederherstellen** unter einer geänderten Mail setzt sie zurück.

## Vorschau

- **Vorschau** unter Liste, Detail und Formular zeigt die Seite mit den noch nicht gespeicherten Änderungen in einem Fenster über dem Backend. Gespeichert wird dabei nichts. Die Detail-Vorschau nimmt den ersten sichtbaren Eintrag.
- **Vorschau** unter einer Mail zeigt Betreff und Text mit Beispielwerten direkt darunter.
