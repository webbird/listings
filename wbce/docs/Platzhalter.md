# Platzhalter

## Jeder Eintrag (`ITEM`)

In der Liste innerhalb von `{foreach ITEMS as ITEM}`, auf der Detailseite direkt.

| Platzhalter | Inhalt |
|---|---|
| `[ITEM.TITLE]`, `[ITEM.URL]`, `[ITEM.ID]`, `[ITEM.SLUG]` | Titel, Link zur Detailseite, Nummer, Adressteil |
| `[ITEM.CREATED]`, `[ITEM.UPDATED]`, `[ITEM.PUBLISHED]`, `[ITEM.EXPIRES]` | Zeitstempel; ausgeben mit `\|datetime` oder `\|datetime:"d.m.Y H:i"` |
| `[ITEM.<NAME>]` | jedes öffentliche Feld unter seinem Namen in Großbuchstaben, z. B. `[ITEM.PHONE]`; bei Auswahlfeldern die Beschriftung |
| `[ITEM.<NAME>_HTML]` | formatierter Text als HTML |
| `[ITEM.RAW.<NAME>]` | der gespeicherte Wert, bei Auswahlfeldern der Wert statt der Beschriftung |
| `[ITEM.IMAGE]`, `[ITEM.THUMB]`, `[ITEM.IMAGE_NAME]` | erstes Bild; in der Liste nur aus Bildfeldern mit „In der Liste zeigen“ |
| `{foreach ITEM.<BILDFELD> as P}` | alle Bilder oder Dateien eines Feldes: `P.URL`, `P.THUMB`, `P.NAME`, `P.SIZE`, `P.DOWNLOADS`, `P.WIDTH`, `P.HEIGHT` |
| `{foreach ITEM.FIELDS as F}` | alle Felder mit Wert, siehe unten |
| `{foreach ITEM.CATEGORIES as C}` | `C.NAME`, `C.URL` (gefilterte Liste); nur sichtbare Kategorien |
| `{foreach ITEM.TAGS as T}` | `T.NAME`, `T.URL`; nur wenn Tags eingeschaltet sind |

### Die Schleife `ITEM.FIELDS`

| Platzhalter | Inhalt |
|---|---|
| `F.LABEL`, `F.NAME`, `F.TYPE` | Beschriftung, Name, Typ (`text`, `textarea`, `html`, `email`, `url`, `date`, `image`, …) |
| `F.VALUE` | der Wert als Text; bei formatiertem Text ohne Auszeichnung |
| `F.HTML` | bei formatiertem Text das HTML, sonst leer |
| `F.FILES` | bei Bild-, Galerie- und Dateifeldern die Dateien wie oben |

In der Liste enthält die Schleife nur Felder mit „In der Liste zeigen“ und keine Bild- und Dateifelder; dafür gibt es `ITEM.THUMB`. Das Standard-Layout unterscheidet die Typen mit `{if F.TYPE == "email"}…{elseif …}`.

## Liste

| Platzhalter | Inhalt |
|---|---|
| `{foreach ITEMS as ITEM}…{else}…{/foreach}` | die Einträge der Seite; `{else}` ohne Treffer |
| `[COUNT]`, `[PAGE]`, `[PAGES]`, `[PREV_URL]`, `[NEXT_URL]` | Anzahl und Blättern |
| `[FILTER]` | das Suchformular (leer, wenn ausgeschaltet) |
| `[FILTERED]`, `[CATEGORY]`, `[CATEGORY_DESCRIPTION]`, `[TAG]`, `[RESET_URL]` | aktiver Filter |
| `[NOT_FOUND]` | 1, wenn ein aufgerufener Eintrag nicht (mehr) sichtbar ist |
| `[RSS_URL]`, `[NEW_URL]`, `[LINK_URL]`, `[LIST_URL]` | Feed, neuer Eintrag, eigener Eintrag bzw. Meine Einträge, die Liste |

## Detailseite

| Platzhalter | Inhalt |
|---|---|
| `[CONTACT]` | das Kontaktformular an den Inhaber (leer, wenn ausgeschaltet oder ohne Inhaber) |
| `[LIST_URL]` | zurück zur Liste |

## Texte

`[MOD_LISTINGS.SCHLUESSEL]` setzt einen Text der Sprachdateien ein, z. B. `[MOD_LISTINGS.MORE]` oder `[MOD_LISTINGS.BACK_TO_LIST]`.

## Filter des LayoutParsers

`upper`, `lower`, `trim`, `truncate:80`, `excerpt:120`, `nl2br`, `striphtml`, `date:"d.m.Y"`, `datetime`, `default:"–"`, `length`, `join:", "` und weitere; Bedingungen mit `==`, `>`, `contains`, `is empty`, `and`, `or`, `not`. Die vollständige Liste steht in der Entwickler-Doku von WBCE (`docs/reference/LayoutParser.md`).

## Platzhalter der Mails

| Platzhalter | Inhalt | in den Mails |
|---|---|---|
| `{PAGE}`, `{SITE}` | Titel der Seite, Name der Website | alle |
| `{TITLE}` | Titel des Eintrags | alle außer Bestätigen und Bearbeitungslinks |
| `{LINK}` | Bestätigungs-, Eintrags-, Verlängerungs- oder Backend-Link | Bestätigen, Veröffentlicht, Erinnerung, an den Admin |
| `{LINKS}` | Titel und Bearbeitungslink je Eintrag | Bearbeitungslinks |
| `{HOURS}`, `{MINUTES}` | Gültigkeit der Links | Bestätigen, Bearbeitungslinks |
| `{DATE}` | Ablaufdatum | Erinnerung |
| `{NAME}`, `{EMAIL}`, `{MESSAGE}` | Absender und Nachricht | Nachricht an den Inhaber |
| `{OWNER_EMAIL}` | Adresse des Inhabers | an den Admin |
