# listings

Seitenmodul für WBCE CMS 1.7: Verzeichnisse von Einträgen mit eigenen Feldern, die Besucher selbst anlegen und pflegen. Nachfolger von wbProfiles. Die Bedienung beschreibt die [README des Moduls](wbce/README.md), die mit dem Modul ausgeliefert wird. Das Konzept liegt als Claude-Dokument vor.

## Aufbau

| Ordner | Inhalt |
|---|---|
| `wbce/` | Das Modul. Wird zum Entwickeln per Junction als `modules/listings` in eine WBCE-Testinstallation eingebunden |
| `wbce/src/` | Klassen im Namespace `Listings`, geladen über `bootstrap.php` |
| `wbce/twig/` | Backend-Ansichten (`modify_*.twig`) und Formulare im Frontend (`view_*.twig`) |
| `wbce/layouts/` | Standard-Layouts für Liste und Detail (LayoutParser, im Backend änderbar) |
| `wbce/presets/` | Mitgelieferte Vorlagen |
| `wbce/assets/` | CSS und JavaScript für Backend und Frontend (lädt der Kern) |
| `wbce/docs/` | Online-Hilfe, im Backend über den Knopf Hilfe |

## Entwicklung

```bat
cmd /c mklink /J "path\to\wbce\modules\listings" "path\to\listings\wbce"
```

Danach das Modul unter Erweiterungen → Module installieren. `manual_install.php` lehnt Module hinter einer Junction ab (Prüfung mit `realpath`); ein Upgrade nach einer neuen Version führt man dann mit einem kleinen Skript aus, das `upgrade.php` lädt und `load_module()` und `upgrade_module()` aufruft.

Regeln für den Code stehen in der `AGENTS.md` von WBCE 1.7: Platzhalter in jeder Abfrage, `{TP}` als Tabellenpräfix, portables SQL, FTAN und IDKEY vor jeder Änderung, Rechteprüfung in jeder direkt aufgerufenen Datei, `h()` beziehungsweise Twig mit Escaping für jede Ausgabe.

Lizenz: GNU General Public License.
