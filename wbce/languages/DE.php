<?php
/**
 * Listings – deutsche Texte.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

$module_description = 'Verzeichnisse von Einträgen mit eigenen Feldern: Mitgliederlisten, Kleinanzeigen, Jobbörsen, Addon-Repositories.';

// Reiter und Werkzeugleiste
$MOD_LISTINGS['TAB_ITEMS']    = 'Einträge';
$MOD_LISTINGS['TAB_FIELDS']   = 'Felder';
$MOD_LISTINGS['TAB_LAYOUTS']  = 'Layouts';
$MOD_LISTINGS['TAB_SETTINGS'] = 'Einstellungen';
$MOD_LISTINGS['TABS']         = 'Bereiche';
$MOD_LISTINGS['WAITING_COUNT'] = '%d warten auf Freigabe';
$MOD_LISTINGS['NEW_ITEM']     = 'Neuer Eintrag';
$MOD_LISTINGS['EDIT_ITEM']    = '„%s“ bearbeiten';
$MOD_LISTINGS['NEW_FIELD']    = 'Neues Feld';
$MOD_LISTINGS['EDIT_FIELD']   = 'Feld „%s“ bearbeiten';
$MOD_LISTINGS['ALL']          = 'Alle';
$MOD_LISTINGS['SAVE_CONTINUE'] = 'Speichern und weiter bearbeiten';

// Einträge
$MOD_LISTINGS['TITLE']            = 'Titel';
$MOD_LISTINGS['SLUG']             = 'Adresse (Slug)';
$MOD_LISTINGS['STATUS']           = 'Status';
$MOD_LISTINGS['STATUS_DRAFT']     = 'Entwurf';
$MOD_LISTINGS['STATUS_PUBLISHED'] = 'Veröffentlicht';
$MOD_LISTINGS['CREATED']          = 'Angelegt';
$MOD_LISTINGS['UPDATED']          = 'Geändert';
$MOD_LISTINGS['EXPIRES']          = 'Läuft ab';
$MOD_LISTINGS['PUBLICATION']      = 'Veröffentlichung';
$MOD_LISTINGS['NO_ENTRIES']       = 'Noch keine Einträge.';
$MOD_LISTINGS['HELP_EXPIRES']     = 'Leer: nie. Ab dem Folgetag wird der Eintrag nicht mehr angezeigt.';
$MOD_LISTINGS['HELP_SLUG']        = 'Teil der Adresse des Eintrags. Leer: wird aus dem Titel gebildet.';
$MOD_LISTINGS['HINT_NO_FIELDS']   = 'Dieses Verzeichnis hat noch keine Felder; Einträge haben nur einen Titel.';
$MOD_LISTINGS['CONFIRM_DELETE_ITEM'] = 'Den Eintrag „%s“ löschen? Das lässt sich nicht rückgängig machen.';

// Felder
$MOD_LISTINGS['HINT_FIELDS']       = 'Die Felder, die jeder Eintrag dieses Verzeichnisses hat, in der Reihenfolge des Formulars.';
$MOD_LISTINGS['NO_FIELDS']         = 'Noch keine Felder.';
$MOD_LISTINGS['FIELD_LABEL']       = 'Beschriftung';
$MOD_LISTINGS['FIELD_NAME']        = 'Name';
$MOD_LISTINGS['FIELD_TYPE']        = 'Typ';
$MOD_LISTINGS['FIELD_HELP']        = 'Hilfetext';
$MOD_LISTINGS['FIELD_OPTIONS']     = 'Optionen';
$MOD_LISTINGS['FIELD_REQUIRED']    = 'Pflichtfeld';
$MOD_LISTINGS['FIELD_IN_LIST']     = 'In der Liste zeigen';
$MOD_LISTINGS['FIELD_VISIBILITY']  = 'Sichtbar';
$MOD_LISTINGS['FIELD_CHOICES']     = 'Auswahlwerte';
$MOD_LISTINGS['FIELD_MAXLENGTH']   = 'Maximale Länge';
$MOD_LISTINGS['FIELD_RANGE']       = 'Bereich';
$MOD_LISTINGS['HELP_NAME']         = 'Im Layout als [ITEM.NAME]. a–z, 0–9 und _, beginnt mit einem Buchstaben. Leer: wird aus der Beschriftung gebildet.';
$MOD_LISTINGS['HELP_NAME_LOCKED']  = 'Der Name lässt sich nicht mehr ändern, weil Einträge ihre Werte darunter speichern.';
$MOD_LISTINGS['HELP_CHOICES']      = 'Ein Wert pro Zeile. „wert|Beschriftung“ speichert den Wert und zeigt die Beschriftung.';
$MOD_LISTINGS['VISIBILITY_PUBLIC'] = 'Öffentlich';
$MOD_LISTINGS['VISIBILITY_ADMIN']  = 'Nur Admin';
$MOD_LISTINGS['MOVE_UP']           = 'Nach oben';
$MOD_LISTINGS['MOVE_DOWN']         = 'Nach unten';
$MOD_LISTINGS['CONFIRM_DELETE_FIELD'] = 'Das Feld „%s“ löschen? Bereits erfasste Werte bleiben erhalten, werden aber nicht mehr angezeigt.';

$MOD_LISTINGS['TYPE_TEXT']       = 'Text';
$MOD_LISTINGS['TYPE_TEXTAREA']   = 'Text, mehrzeilig';
$MOD_LISTINGS['TYPE_NUMBER']     = 'Zahl';
$MOD_LISTINGS['TYPE_EMAIL']      = 'E-Mail-Adresse';
$MOD_LISTINGS['TYPE_URL']        = 'Webadresse';
$MOD_LISTINGS['TYPE_PHONE']      = 'Telefonnummer';
$MOD_LISTINGS['TYPE_DATE']       = 'Datum';
$MOD_LISTINGS['TYPE_DATETIME']   = 'Datum und Uhrzeit';
$MOD_LISTINGS['TYPE_SELECT']     = 'Auswahlliste';
$MOD_LISTINGS['TYPE_RADIO']      = 'Eins von mehreren (Optionsfelder)';
$MOD_LISTINGS['TYPE_CHECKBOXES'] = 'Mehrere von mehreren (Kontrollkästchen)';
$MOD_LISTINGS['TYPE_BOOLEAN']    = 'Ja/Nein';
$MOD_LISTINGS['TYPE_HEADING']    = 'Zwischenüberschrift im Formular';

// Layouts und Einstellungen
$MOD_LISTINGS['HINT_LAYOUTS']      = 'Layouts verwenden die LayoutParser-Syntax: [SCHLÜSSEL], {foreach}, {if}. Werte werden automatisch maskiert. PHP lässt sich darin nicht ausführen.';
$MOD_LISTINGS['LAYOUT_FIELD_KEYS'] = 'Felder dieses Verzeichnisses:';
$MOD_LISTINGS['LAYOUT_LIST']       = 'Liste';
$MOD_LISTINGS['LAYOUT_DETAIL']     = 'Detail';
$MOD_LISTINGS['LAYOUT_DEFAULT']    = 'Standard';
$MOD_LISTINGS['LAYOUT_CUSTOM']     = 'geändert';
$MOD_LISTINGS['LAYOUT_RESET']      = 'Standard-Layout wiederherstellen';
$MOD_LISTINGS['PER_PAGE']          = 'Einträge pro Seite';
$MOD_LISTINGS['SORT']              = 'Reihenfolge';
$MOD_LISTINGS['SORT_NEWEST']       = 'Neueste zuerst';
$MOD_LISTINGS['SORT_PUBLISHED']    = 'Zuletzt veröffentlichte zuerst';
$MOD_LISTINGS['SORT_UPDATED']      = 'Zuletzt geänderte zuerst';
$MOD_LISTINGS['SORT_TITLE']        = 'Titel A–Z';

// Frontend
$MOD_LISTINGS['MORE']         = 'Details';
$MOD_LISTINGS['PREVIOUS']     = 'Zurück';
$MOD_LISTINGS['NEXT']         = 'Weiter';
$MOD_LISTINGS['BACK_TO_LIST'] = 'Zurück zur Liste';
$MOD_LISTINGS['NOT_FOUND']    = 'Diesen Eintrag gibt es nicht oder nicht mehr.';
$MOD_LISTINGS['YES']          = 'Ja';

// 0.2: Status, Inhaber, Freigabe
$MOD_LISTINGS['STATUS_PENDING']     = 'Wartet auf Freigabe';
$MOD_LISTINGS['STATUS_REJECTED']    = 'Abgelehnt';
$MOD_LISTINGS['STATUS_UNCONFIRMED'] = 'Unbestätigt';
$MOD_LISTINGS['APPROVE']            = 'Freigeben';
$MOD_LISTINGS['REJECT']             = 'Ablehnen';
$MOD_LISTINGS['OWNER_EMAIL']        = 'E-Mail-Adresse';
$MOD_LISTINGS['HELP_OWNER_EMAIL']   = 'Für die Bestätigung und spätere Änderungen. Sie wird auf der Website nicht angezeigt.';
$MOD_LISTINGS['HELP_OWNER_EMAIL_ADMIN'] = 'Inhaber des Eintrags: bekommt Bearbeitungslinks und die Entscheidung über die Freigabe. Leer: nur der Admin bearbeitet.';
$MOD_LISTINGS['VISIBILITY_OWNER']   = 'Inhaber und Admin';
$MOD_LISTINGS['FIELD_EDITABLE']     = 'Der Inhaber darf es bearbeiten';
$MOD_LISTINGS['FIELD_EDITABLE_SHORT'] = 'Inhaber';
$MOD_LISTINGS['HELP_EDITABLE']      = 'Aus: erscheint im Formular im Frontend weder beim Anlegen noch beim Ändern, z. B. für ein „geprüft“.';
$MOD_LISTINGS['MSG_APPROVED']       = 'Eintrag „%s“ freigegeben.';
$MOD_LISTINGS['MSG_REJECTED']       = 'Eintrag „%s“ abgelehnt.';
$MOD_LISTINGS['MSG_MAIL_FAILED']    = 'Die Mail an den Inhaber konnte nicht versendet werden.';

// Einstellungen 0.2
$MOD_LISTINGS['SETTINGS_LIST']      = 'Liste';
$MOD_LISTINGS['SETTINGS_FRONTEND']  = 'Einträge von Besuchern';
$MOD_LISTINGS['FRONTEND']           = 'Frontend';
$MOD_LISTINGS['FRONTEND_ON']        = 'Besucher dürfen Einträge anlegen und bearbeiten';
$MOD_LISTINGS['HELP_FRONTEND']      = 'Ohne Konto: Die E-Mail-Adresse wird über einen Link bestätigt, spätere Änderungen laufen über einen Bearbeitungslink per Mail.';
$MOD_LISTINGS['APPROVAL']           = 'Freigabe';
$MOD_LISTINGS['APPROVAL_ON']        = 'Neue Einträge warten auf Freigabe durch den Admin';
$MOD_LISTINGS['RECHECK_ON']         = 'Änderungen an veröffentlichten Einträgen werden erneut geprüft';
$MOD_LISTINGS['NOTIFY_EMAIL']       = 'Benachrichtigen';
$MOD_LISTINGS['HELP_NOTIFY_EMAIL']  = 'Diese Adresse erfährt von neuen bestätigten Einträgen. Leer: keine Benachrichtigung.';
$MOD_LISTINGS['SPAM']               = 'Spamschutz';
$MOD_LISTINGS['CAPTCHA_ON']         = 'Captcha in den Formularen';
$MOD_LISTINGS['HELP_CAPTCHA']       = 'Das Captcha aus „Captcha & Spam-Schutz“. Ein verstecktes Feld und eine Mindestzeit werden immer geprüft.';
$MOD_LISTINGS['HELP_NO_CAPTCHA']    = 'Das Modul „Captcha & Spam-Schutz“ ist nicht installiert.';

// Frontend 0.2
$MOD_LISTINGS['ADD_ENTRY']          = 'Eintrag hinzufügen';
$MOD_LISTINGS['NEW_ENTRY']          = 'Neuer Eintrag';
$MOD_LISTINGS['REQUEST_LINK']       = 'Eigenen Eintrag ändern';
$MOD_LISTINGS['INTRO_NEW']          = 'Nach dem Absenden bekommst du eine E-Mail mit einem Link. Der Eintrag erscheint, sobald du ihn bestätigt hast.';
$MOD_LISTINGS['INTRO_NEW_APPROVAL'] = 'Nach dem Absenden bekommst du eine E-Mail mit einem Link. Sobald du ihn bestätigt hast, wird der Eintrag geprüft und dann veröffentlicht.';
$MOD_LISTINGS['INTRO_REQUEST_LINK'] = 'Gib die Adresse an, mit der du den Eintrag angelegt hast. Du bekommst einen Link zum Ändern oder Löschen, eine Stunde lang gültig.';
$MOD_LISTINGS['INTRO_STATUS_DRAFT']     = 'Dieser Eintrag ist noch nicht bestätigt.';
$MOD_LISTINGS['INTRO_STATUS_PENDING']   = 'Dieser Eintrag wartet auf Freigabe.';
$MOD_LISTINGS['INTRO_STATUS_PUBLISHED'] = 'Dieser Eintrag ist veröffentlicht.';
$MOD_LISTINGS['INTRO_STATUS_REJECTED']  = 'Dieser Eintrag wurde nicht freigegeben. Nach einer Änderung wird er erneut geprüft.';
$MOD_LISTINGS['INTRO_CONFIRM']      = 'Bitte bestätige, dass du diesen Eintrag angelegt hast und deine E-Mail-Adresse dafür gespeichert werden darf.';
$MOD_LISTINGS['CONFIRM_ENTRY']      = 'Eintrag bestätigen';
$MOD_LISTINGS['CONFIRM_BUTTON']     = 'Bestätigen';
$MOD_LISTINGS['SUBMIT_NEW']         = 'Absenden';
$MOD_LISTINGS['SEND_LINK']          = 'Link senden';
$MOD_LISTINGS['DELETE_ENTRY']       = 'Diesen Eintrag löschen';
$MOD_LISTINGS['NOT_PUBLIC']         = 'nicht öffentlich';
$MOD_LISTINGS['PRIVACY_CONSENT']    = 'Ich bin einverstanden, dass meine Angaben für diesen Eintrag gespeichert werden. Ich kann den Eintrag jederzeit löschen.';
$MOD_LISTINGS['LINK_INVALID']       = 'Dieser Link ist nicht (mehr) gültig. Links aus unseren Mails lassen sich nur eine begrenzte Zeit verwenden.';
$MOD_LISTINGS['MSG_CHECK_MAIL']     = 'Danke! Bitte bestätige deinen Eintrag mit dem Link in der E-Mail, die wir dir gerade geschickt haben.';
$MOD_LISTINGS['MSG_CONFIRMED_WAITING']   = 'Danke, dein Eintrag ist bestätigt. Er wird veröffentlicht, sobald er geprüft ist.';
$MOD_LISTINGS['MSG_CONFIRMED_PUBLISHED'] = 'Danke, dein Eintrag ist bestätigt und veröffentlicht.';
$MOD_LISTINGS['MSG_LINK_SENT']      = 'Wenn es zu dieser Adresse Einträge gibt, ist ein Link zum Ändern unterwegs.';
$MOD_LISTINGS['MSG_SAVED']          = 'Deine Änderungen sind gespeichert.';
$MOD_LISTINGS['MSG_SAVED_WAITING']  = 'Deine Änderungen sind gespeichert. Der Eintrag erscheint wieder, sobald er geprüft ist.';
$MOD_LISTINGS['MSG_DELETED']        = 'Dein Eintrag wurde gelöscht.';
$MOD_LISTINGS['ERR_PRIVACY']        = 'Bitte stimme der Speicherung deiner Angaben zu.';
$MOD_LISTINGS['ERR_TOO_MANY']       = 'Zu viele Anfragen für diese Adresse. Bitte in einer Stunde noch einmal versuchen.';
$MOD_LISTINGS['ERR_REJECTED']       = 'Das Formular konnte nicht verarbeitet werden. Bitte noch einmal ausfüllen.';
$MOD_LISTINGS['ERR_CAPTCHA']        = 'Die Sicherheitsabfrage wurde nicht richtig gelöst.';

// Mails (Text)
$MOD_LISTINGS['MAIL_CONFIRM_SUBJECT'] = 'Bitte bestätige deinen Eintrag – %s';
$MOD_LISTINGS['MAIL_CONFIRM_TEXT']    = "Jemand hat mit dieser E-Mail-Adresse einen Eintrag in „%1\$s“ auf %2\$s angelegt.\n\nZum Bestätigen öffne diesen Link (%4\$d Stunden gültig):\n%3\$s";
$MOD_LISTINGS['MAIL_EDIT_SUBJECT']    = 'Eintrag ändern – %s';
$MOD_LISTINGS['MAIL_EDIT_TEXT']       = "Du hast einen Link zum Ändern deines Eintrags in „%s“ angefordert. Jeder Link ist %d Minuten gültig:";
$MOD_LISTINGS['MAIL_IGNORE']          = 'Warst du das nicht, ignoriere diese Mail einfach; ohne den Link passiert nichts.';
$MOD_LISTINGS['MAIL_FOOTER']          = '-- ' . "\n" . '%s';
$MOD_LISTINGS['MAIL_PUBLISHED_SUBJECT'] = 'Dein Eintrag „%s“ ist veröffentlicht';
$MOD_LISTINGS['MAIL_PUBLISHED_TEXT']    = 'Dein Eintrag „%s“ in „%s“ wurde freigegeben und ist jetzt online:';
$MOD_LISTINGS['MAIL_REJECTED_SUBJECT']  = 'Dein Eintrag „%s“ wurde nicht freigegeben';
$MOD_LISTINGS['MAIL_REJECTED_TEXT']     = 'Dein Eintrag „%s“ in „%s“ wurde nicht freigegeben. Du kannst ihn über einen Bearbeitungslink auf der Website ändern; danach wird er erneut geprüft.';
$MOD_LISTINGS['MAIL_ADMIN_WAITING_SUBJECT'] = 'Neuer Eintrag wartet auf Freigabe: %s';
$MOD_LISTINGS['MAIL_ADMIN_WAITING_TEXT']    = 'Ein Eintrag in „%s“ wurde bestätigt und wartet auf Freigabe:';
$MOD_LISTINGS['MAIL_ADMIN_NEW_SUBJECT']     = 'Neuer Eintrag: %s';
$MOD_LISTINGS['MAIL_ADMIN_NEW_TEXT']        = 'Ein Eintrag in „%s“ wurde bestätigt und ist online:';

// 0.3: Konten, Laufzeit, Kontakt
$MOD_LISTINGS['OWNER_MODE']         = 'Inhaber';
$MOD_LISTINGS['OWNER_MODE_EMAIL']   = 'Ohne Konto: Links per E-Mail';
$MOD_LISTINGS['OWNER_MODE_ACCOUNT'] = 'Mit Frontend-Konto der Website';
$MOD_LISTINGS['HELP_OWNER_MODE']    = 'Mit Konto melden sich Inhaber an, finden ihre Einträge unter „Meine Einträge“ und brauchen keinen Bestätigungslink.';
$MOD_LISTINGS['WARN_NO_LOGIN']      = 'Die Frontend-Anmeldung der Website ist ausgeschaltet (Grundeinstellungen). Inhaber mit Konto können sich nicht anmelden.';
$MOD_LISTINGS['SETTINGS_RUNTIME']   = 'Laufzeit';
$MOD_LISTINGS['RUNTIME_DAYS']       = 'Laufzeit in Tagen';
$MOD_LISTINGS['HELP_RUNTIME']       = 'Einträge von Inhabern laufen so viele Tage nach ihrer ersten Veröffentlichung ab. 0: nie. Eigene Einträge behalten das im Eintrag gesetzte Datum.';
$MOD_LISTINGS['REMIND_DAYS']        = 'Erinnerung (Tage vorher)';
$MOD_LISTINGS['HELP_REMIND']        = 'Der Inhaber bekommt eine Mail mit einem Link zum Verlängern. Ab dann und nach dem Ablauf lässt sich der Eintrag um eine Laufzeit verlängern. 0: keine Erinnerung, verlängern erst nach dem Ablauf.';
$MOD_LISTINGS['SETTINGS_CONTACT']   = 'Kontakt';
$MOD_LISTINGS['CONTACT']            = 'Kontaktformular';
$MOD_LISTINGS['CONTACT_ON']         = 'Besucher können dem Inhaber von der Detailseite aus schreiben';
$MOD_LISTINGS['HELP_CONTACT']       = 'Die Adresse des Inhabers bleibt verborgen; die Antwort geht direkt an den Absender. Nur für Einträge mit Inhaber-Adresse.';
$MOD_LISTINGS['ERR_RUNTIME']        = 'Die Laufzeit muss eine Zahl von 0 bis %d sein.';
$MOD_LISTINGS['ERR_REMIND']         = 'Die Erinnerung muss eine Zahl von 0 bis 365 sein.';

$MOD_LISTINGS['INTRO_NEW_ACCOUNT']          = 'Der Eintrag ist nach dem Absenden sofort veröffentlicht. Du findest ihn unter „Meine Einträge“.';
$MOD_LISTINGS['INTRO_NEW_ACCOUNT_APPROVAL'] = 'Nach dem Absenden wird der Eintrag geprüft und dann veröffentlicht. Du findest ihn unter „Meine Einträge“.';
$MOD_LISTINGS['MY_ENTRIES']        = 'Meine Einträge';
$MOD_LISTINGS['NO_OWN_ENTRIES']    = 'Du hast hier noch keine Einträge.';
$MOD_LISTINGS['ACTIONS']           = 'Aktionen';
$MOD_LISTINGS['LOGIN_REQUIRED']    = 'Bitte anmelden';
$MOD_LISTINGS['INTRO_LOGIN']       = 'Einträge gehören hier zu einem Konto dieser Website. Melde dich an, um Einträge anzulegen oder zu ändern.';
$MOD_LISTINGS['LOGIN']             = 'Anmelden';
$MOD_LISTINGS['SIGNUP']            = 'Konto anlegen';
$MOD_LISTINGS['ERR_NO_LOGIN']      = 'Die Anmeldung ist auf dieser Website ausgeschaltet, deshalb lassen sich hier keine Einträge anlegen.';
$MOD_LISTINGS['EXPIRES_ON']        = 'Er läuft bis %s.';
$MOD_LISTINGS['EXPIRED_ON']        = 'Er ist am %s abgelaufen und wird nicht mehr angezeigt.';
$MOD_LISTINGS['EXPIRED']           = 'abgelaufen';
$MOD_LISTINGS['EXTEND']            = 'Verlängern';
$MOD_LISTINGS['EXTEND_ENTRY']      = '„%s“ verlängern';
$MOD_LISTINGS['MSG_EXTENDED']      = 'Der Eintrag ist bis %s verlängert.';
$MOD_LISTINGS['MSG_ACCOUNT_WAITING']   = 'Danke! Dein Eintrag wird veröffentlicht, sobald er geprüft ist.';
$MOD_LISTINGS['MSG_ACCOUNT_PUBLISHED'] = 'Danke! Dein Eintrag ist veröffentlicht.';
$MOD_LISTINGS['ERR_NOT_YOURS']         = 'Diesen Eintrag gibt es nicht, oder er gehört nicht zu deinem Konto.';
$MOD_LISTINGS['ERR_TOO_MANY_CONTACT']  = 'Du hast in kurzer Zeit mehrere Nachrichten geschickt. Bitte in einer Stunde noch einmal versuchen.';
$MOD_LISTINGS['ERR_NOT_EXTENDABLE'] = 'Dieser Eintrag lässt sich (noch) nicht verlängern.';

$MOD_LISTINGS['CONTACT_TITLE']     = 'Kontakt';
$MOD_LISTINGS['CONTACT_NAME']      = 'Dein Name';
$MOD_LISTINGS['CONTACT_EMAIL']     = 'Deine E-Mail-Adresse';
$MOD_LISTINGS['CONTACT_MESSAGE']   = 'Nachricht';
$MOD_LISTINGS['CONTACT_CONSENT']   = 'Ich bin einverstanden, dass mein Name, meine Adresse und meine Nachricht an den Inhaber dieses Eintrags weitergegeben werden.';
$MOD_LISTINGS['CONTACT_SEND']      = 'Nachricht senden';
$MOD_LISTINGS['ERR_CONTACT_SHORT'] = 'Bitte schreib ein paar Worte mehr (mindestens 10 Zeichen).';
$MOD_LISTINGS['MSG_CONTACT_SENT']  = 'Danke, deine Nachricht ist unterwegs.';

$MOD_LISTINGS['MAIL_REMINDER_SUBJECT'] = 'Dein Eintrag „%1$s“ läuft am %2$s ab';
$MOD_LISTINGS['MAIL_REMINDER_TEXT']    = 'Dein Eintrag „%1$s“ in „%2$s“ läuft am %3$s ab und wird dann nicht mehr angezeigt. Damit er online bleibt, verlängere ihn hier:';
$MOD_LISTINGS['MAIL_CONTACT_SUBJECT']  = 'Nachricht zu deinem Eintrag „%s“';
$MOD_LISTINGS['MAIL_CONTACT_TEXT']     = 'Jemand hat dir zu deinem Eintrag „%1$s“ in „%2$s“ auf %3$s geschrieben. Deine Adresse wurde nicht angezeigt; zum Antworten einfach auf diese Mail antworten.';

// 0.4: Kategorien, Tags, Filter, Feed
$MOD_LISTINGS['TAB_CATEGORIES']       = 'Kategorien';
$MOD_LISTINGS['CATEGORIES']           = 'Kategorien';
$MOD_LISTINGS['CATEGORY']             = 'Kategorie';
$MOD_LISTINGS['NEW_CATEGORY']         = 'Neue Kategorie';
$MOD_LISTINGS['NEW_SUBCATEGORY']      = 'Unterkategorie';
$MOD_LISTINGS['EDIT_CATEGORY']        = 'Kategorie „%s“ bearbeiten';
$MOD_LISTINGS['CATEGORY_NAME']        = 'Name';
$MOD_LISTINGS['CATEGORY_PARENT']      = 'Unterhalb von';
$MOD_LISTINGS['CATEGORY_TOP']         = '(oberste Ebene)';
$MOD_LISTINGS['CATEGORY_DESCRIPTION'] = 'Beschreibung';
$MOD_LISTINGS['CATEGORY_VISIBLE']     = 'Sichtbar';
$MOD_LISTINGS['CATEGORY_VISIBLE_ON']  = 'Auf der Website angezeigt, im Filter und in Formularen angeboten';
$MOD_LISTINGS['HELP_CATEGORY_SLUG']   = 'Teil der Adresse der gefilterten Liste. Leer: wird aus dem Namen gebildet.';
$MOD_LISTINGS['HINT_NO_CATEGORIES']   = 'Noch keine Kategorien. Ohne Kategorien hat die Liste keinen Kategorie-Filter.';
$MOD_LISTINGS['CONFIRM_DELETE_CATEGORY'] = 'Die Kategorie „%s“ löschen? Ihre Unterkategorien rücken eine Ebene nach oben, die Einträge bleiben.';
$MOD_LISTINGS['ORDER']                = 'Reihenfolge';
$MOD_LISTINGS['ENTRIES']              = 'Einträge';
$MOD_LISTINGS['NO']                   = 'Nein';
$MOD_LISTINGS['HIDDEN']               = 'verborgen';
$MOD_LISTINGS['TAGS']                 = 'Tags';
$MOD_LISTINGS['TAG']                  = 'Tag';
$MOD_LISTINGS['NO_TAGS']              = 'Noch keine Tags.';
$MOD_LISTINGS['RENAME']               = 'Umbenennen';
$MOD_LISTINGS['CONFIRM_DELETE_TAG']   = 'Den Tag „%s“ löschen? Die Einträge bleiben.';
$MOD_LISTINGS['HINT_TAGS']            = 'Tags entstehen, wenn sie an einem Eintrag eingegeben werden.';
$MOD_LISTINGS['HINT_TAGS_OFF']        = 'Tags sind in den Einstellungen ausgeschaltet; sie werden weder angezeigt noch angeboten.';
$MOD_LISTINGS['HELP_TAGS']            = 'Durch Kommas getrennt.';
$MOD_LISTINGS['TAGS_MODE_OFF']        = 'Aus';
$MOD_LISTINGS['TAGS_MODE_FIXED']      = 'An, Inhaber wählen aus den vorhandenen Tags';
$MOD_LISTINGS['TAGS_MODE_OPEN']       = 'An, Inhaber dürfen neue Tags anlegen';
$MOD_LISTINGS['FIELD_FILTERABLE']     = 'Als Filter über der Liste anbieten';
$MOD_LISTINGS['HELP_FILTERABLE']      = 'Nur für öffentliche Felder.';
$MOD_LISTINGS['FILTER']               = 'Suche und Filter';
$MOD_LISTINGS['FILTER_FORM_ON']       = 'Suche und Filter über der Liste zeigen';
$MOD_LISTINGS['HELP_FILTER_FORM']     = 'Die Suche durchsucht den Titel und öffentliche Textfelder. Filter: Kategorie, Tag und die als Filter markierten Felder.';
$MOD_LISTINGS['SEARCH']               = 'Suche';
$MOD_LISTINGS['FILTER_APPLY']         = 'Anzeigen';
$MOD_LISTINGS['FILTER_RESET']         = 'Alle zeigen';
$MOD_LISTINGS['FOUND']                = 'Gefundene Einträge';
$MOD_LISTINGS['NO_MATCHES']           = 'Keine Einträge passen zu dieser Auswahl.';
$MOD_LISTINGS['RSS_ON']               = 'Feed der neuesten Einträge anbieten';
$MOD_LISTINGS['HELP_RSS']             = 'Die 20 neuesten sichtbaren Einträge mit ihren öffentlichen Feldern. Adresse:';
$MOD_LISTINGS['ERR_CATEGORY_NAME']    = 'Bitte einen Namen eingeben.';
$MOD_LISTINGS['ERR_CATEGORY_PARENT']  = 'Eine Kategorie kann nicht unter sich selbst oder einer ihrer Unterkategorien stehen.';
$MOD_LISTINGS['ERR_TAG_NAME']         = 'Bitte einen Namen für den Tag eingeben.';
$MOD_LISTINGS['ERR_TAG_TAKEN']        = 'Es gibt schon einen Tag „%s“.';
$MOD_LISTINGS['SAVED_CATEGORY']       = 'Kategorie „%s“ gespeichert.';
$MOD_LISTINGS['DELETED_CATEGORY']     = 'Kategorie „%s“ gelöscht.';
$MOD_LISTINGS['SAVED_TAG']            = 'Tag „%s“ gespeichert.';
$MOD_LISTINGS['DELETED_TAG']          = 'Tag „%s“ gelöscht.';

// Meldungen
$MOD_LISTINGS['SAVED_ITEM']     = 'Eintrag „%s“ gespeichert.';
$MOD_LISTINGS['DELETED_ITEM']   = 'Eintrag „%s“ gelöscht.';
$MOD_LISTINGS['SAVED_FIELD']    = 'Feld „%s“ gespeichert.';
$MOD_LISTINGS['DELETED_FIELD']  = 'Feld „%s“ gelöscht.';
$MOD_LISTINGS['SAVED_LAYOUTS']  = 'Layouts gespeichert.';
$MOD_LISTINGS['SAVED_SETTINGS'] = 'Einstellungen gespeichert.';

// Fehler
$MOD_LISTINGS['ERR_REQUIRED']        = '„%s“ ist ein Pflichtfeld.';
$MOD_LISTINGS['ERR_TOO_LONG']        = '„%s“ darf höchstens %d Zeichen lang sein.';
$MOD_LISTINGS['ERR_NUMBER']          = '„%s“ muss eine Zahl sein.';
$MOD_LISTINGS['ERR_MIN']             = '„%s“ muss mindestens %s sein.';
$MOD_LISTINGS['ERR_MAX']             = '„%s“ darf höchstens %s sein.';
$MOD_LISTINGS['ERR_EMAIL']           = '„%s“ ist keine gültige E-Mail-Adresse.';
$MOD_LISTINGS['ERR_URL']             = '„%s“ muss eine Webadresse mit http:// oder https:// sein.';
$MOD_LISTINGS['ERR_PHONE']           = '„%s“ ist keine gültige Telefonnummer.';
$MOD_LISTINGS['ERR_DATE']            = '„%s“ ist kein gültiges Datum.';
$MOD_LISTINGS['ERR_CHOICE']          = '„%s“ enthält einen Wert, der nicht zur Auswahl gehört.';
$MOD_LISTINGS['ERR_TITLE']           = 'Bitte einen Titel eingeben.';
$MOD_LISTINGS['ERR_FIELD_TYPE']      = 'Unbekannter Feldtyp.';
$MOD_LISTINGS['ERR_FIELD_LABEL']     = 'Bitte eine Beschriftung eingeben.';
$MOD_LISTINGS['ERR_FIELD_NAME']      = 'Der Name darf a–z, 0–9 und _ enthalten (höchstens 40) und muss mit einem Buchstaben beginnen.';
$MOD_LISTINGS['ERR_FIELD_RESERVED']  = 'Den Namen „%s“ verwendet das Modul selbst.';
$MOD_LISTINGS['ERR_FIELD_TAKEN']     = 'In diesem Verzeichnis gibt es schon ein Feld „%s“.';
$MOD_LISTINGS['ERR_FIELD_CHOICES']   = 'Bitte mindestens einen Auswahlwert eingeben.';
$MOD_LISTINGS['ERR_FIELD_MAXLENGTH'] = 'Die maximale Länge muss eine Zahl von 1 bis 65535 sein.';
$MOD_LISTINGS['ERR_FIELD_RANGE']     = 'Minimum und Maximum müssen Zahlen sein, das Minimum nicht über dem Maximum.';
$MOD_LISTINGS['ERR_PER_PAGE']        = 'Einträge pro Seite: eine Zahl von 1 bis %d.';
$MOD_LISTINGS['ERR_NOT_FOUND']       = 'Den Eintrag gibt es nicht (mehr).';
$MOD_LISTINGS['ERR_EXPIRED_FORM']    = 'Das Formular ist abgelaufen oder wurde schon abgeschickt. Bitte noch einmal versuchen.';
$MOD_LISTINGS['ERR_DATABASE']        = 'Die Daten konnten nicht gespeichert werden (Datenbankfehler).';
