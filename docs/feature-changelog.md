# v4.5.0
* Anmeldung über StuMV: StuFis folgt nun der reorganisierten StuMV-API. Die OAuth-geschützten Endpunkte für Nutzerprofil, Gremien und Gruppen liegen jetzt unter `/api-legacy/*` (der Präfix `/api/*` beherbergt bei StuMV nun eine separate Directory-API). Ohne diese Anpassung schlug die Anmeldung fehl und Gremien-/Gruppenrechte wurden nicht mehr geladen. Der Präfix ist über die neue Variable `STUMV_API_PATH` (Standard: `api-legacy`) konfigurierbar.
* Interne Aufräumarbeiten: Die nirgends genutzte Funktion „alle verfügbaren Gremien" (`allCommittees`) wurde aus allen Auth-Providern entfernt. Für OIDC entfallen dadurch der Scope-Bestandteil `all-committees` sowie die Variable `OIDC_ATTRIBUTE_ALL_COMMITTEES`.
* Sicherheit: StuFis erzwingt nun eine strikte Content-Security-Policy (CSP). Dies erschwert Cross-Site-Scripting (XSS) Angriffe deutlich.
* Einheitliche Abmeldung (Single Logout): Ab- und Anmeldung sind nun zwischen StuFis und dem zentralen Login-Dienst (StuMV) gekoppelt. Meldest du dich beim zentralen Dienst ab, wirst du automatisch auch aus StuFis abgemeldet – und meldest du dich in StuFis ab, wirst du auch aus dem zentralen Login-Dienst abgemeldet.
* DATEV-Export: Die Auswahlliste der Haushaltspläne zeigt nun HHP-Nummer, Organisation und Haushaltsjahr, sodass Pläne derselben Organisation eindeutig unterscheidbar sind.

---

# v4.4.3
* Projekte mit mehreren Posten ließen sich nicht mehr speichern, wenn vor dem Speichern in jeder Postenzeile etwas geändert wurde – das Speichern brach mit einer Fehlerseite ab. Die Beträge gingen dabei auf dem Weg zum Server verloren; sie werden nun wieder zuverlässig als Geldbeträge erkannt.
* Fehlermeldungen beim Speichern eines Projekts erscheinen jetzt direkt an dem Feld, das sie ausgelöst hat – und zwar alle. Bisher wurde nur die erste Meldung als einzelne Zeile über dem Formular angezeigt, sodass unklar blieb, welche Zeile oder welches Feld gemeint war.
* Posten, in denen sowohl eine Einnahme als auch eine Ausgabe stand (aus Altdaten), ließen sich nicht mehr korrigieren: Beide Betragsfelder waren gesperrt, während das Speichern genau diese Kombination bemängelte. Ein Betragsfeld ist jetzt nur noch gesperrt, solange es selbst leer ist.
* Negative Beträge werden in Posten nicht mehr angenommen. Bisher konnte z. B. eine Ausgabe von "-50 €" gespeichert werden und hat die Projektsummen still verfälscht.
* Die Meldungen zu den Beträgen eines Postens benennen nun einheitlich die betroffene Postenzeile und erklären die Regel: Ein Posten ist entweder eine Einnahme oder eine Ausgabe.
* Posten lassen sich in der Projektbearbeitung per Ziehgriff in der Spalte "Nr." umsortieren. Die Reihenfolge wird gespeichert und bleibt in Ansicht und Ausdruck erhalten.
* Wurde ein Projekt zwischenzeitlich von jemand anderem geändert, erscheint nun ein verständlicher Hinweis statt eines technischen Platzhaltertexts.

---

# v4.4.2
* Projektbeschreibungen und Nachrichten werden nun mit der vollständigen Formatierung des Editors angezeigt (Aufzählungen, nummerierte Listen, Überschriften, Links, Fett-/Kursivschrift). Zuvor gingen z. B. Listen in der Ansicht verloren, obwohl sie korrekt gespeichert waren.
* Ältere, noch als reiner Text gespeicherte Projektbeschreibungen behalten beim Anzeigen und erneuten Bearbeiten ihre Zeilenumbrüche und werden automatisch ins neue Format übernommen.
* Projektanhänge können nun direkt über einen Download-Knopf je Datei heruntergeladen werden (bisher wurden sie nur im Browser geöffnet).
* Projektanhänge: Neben PDF und Tabellen (xlsx, ods) können nun auch Bilder (jpg, png), Word-/Writer-Dokumente (docx, odt) sowie Präsentationen (pptx, odp) hochgeladen werden – so muss nicht mehr alles vorab in PDF umgewandelt werden.
* Hochgeladene Projektanhänge werden strenger geprüft: Der tatsächliche Dateiinhalt muss zur Dateiendung passen, und Dateien mit eingebetteten Makros werden abgelehnt – das verhindert getarnte oder potenziell schädliche Uploads.

---

# v4.4.1
* Im Menü Buchungen werden Abrechnungen nun ebenso wie Konto- und Kassenumsätze nur im jeweiligen Jahr angezeigt, da diese durch ihre Projekte bereits an ein Haushaltsjahr gebunden sind.
* PDFs können wieder in Projekte hochgeladen werden.
* Der Freitext in der Rechtsgrundlage wird wieder gespeichert.
* Der Projektzeitraum darf jetzt auch nur einen Tag lang sein.
* Die Anzeige von Daten wurde im gesamten System nun einheitlich auf DD.MM.YYYY geändert.
* Optische Verbesserung der Kontoauszüge
* CAMT-Import: Kontoauszüge können nun auch im CAMT-Format (camt.052/053) hochgeladen werden – ohne Spaltenzuordnung und mit automatischer IBAN- und Saldo-Prüfung. CAMT wird gegenüber CSV empfohlen.
* Beim Buchen von Vorgängen erscheint nun eine verständliche Meldung, wenn kein Vorgang ausgewählt wurde, Textfelder fehlen oder ein Posten keinen gültigen Haushaltstitel hat – statt eines Fehlers.
* Die Detailansicht eines Konto- bzw. Kassenumsatzes funktioniert nun auch dann, wenn eine zugehörige Buchung keiner Abrechnung zugeordnet ist (z. B. Einnahmen oder Umbuchungen).
* Anmeldung: Eine abgelaufene oder ungültige Login-Sitzung führt nun zu einer automatischen, erneuten Anmeldung statt zu einer Fehlerseite.
* Verschiedene Stabilitätsverbesserungen, u. a. bei der Anzeige von Profilbildern ohne hinterlegte Nutzer:in.
* Beleg-PDF: Hochgeladene Beleg-Dateien werden wieder vollständig in die erzeugte Beleg-PDF eingefügt. Belege ohne hinterlegte Datei führen nicht mehr zu einem Fehler, sondern erzeugen wie gewohnt nur das Deckblatt zum Antackern des Originals.
* Einstellungen: Die systemweite Konfiguration kann nun direkt im Browser bearbeitet werden (zuvor nur lesbar). Die Seite ist über das Zahnrad-Symbol erreichbar und nur für Administrator:innen zugänglich.
* Projektbeschreibung: Die konfigurierte Mindest- und Maximallänge wird jetzt tatsächlich geprüft. Gezählt werden nur die sichtbaren Zeichen (HTML wird ignoriert); eine Maximallänge von -1 hebt die Obergrenze auf (Standard), eine Mindestlänge von 0 die Untergrenze.

---

# v4.4.0
**Projekt/Antrag**
* Vollständige Überarbeitung des Formulars und des Chats
* In Projekten können nun Dateien angehangen werden
* Wenn ein:e Nutzer:in keine Organisation zugewiesen hat, gibt es nun eine Standardorganisation
* Verbesserte Validierung der Formularfelder
* Löschung von Projektposten nur noch möglich, wenn keine Abrechnung damit verknüpft ist und es nicht der einzige Posten im Projekt ist
* Knopf zum automatischen Hinzufügen der Umsatzsteuer-Posten
* Neue Statusanzeigen
* Finanzverantwortliche können bereits vor dem Genehmigt-Status den Posten Haushaltstitel zuordnen

**Abrechnungen**
* Fehler mit dem Chrome-Browser, bei dem in der Abrechnung bei der Auswahl von Projektposten, diese doppelt angezeigt wurden.
* Abrechnung kann nun fehlerfrei bearbeitet werden, auch wenn es bereits einmal im Status "Bezahlt" war
* * Das Belegdatum ist bei Belegen in Abrechnungen nun ein Pflichtfeld

**CSV-Import**
* modernere Oberfläche, bessere Fehlermeldungen und zuverlässigeres Verhalten beim erneuten Hochladen
* nun für alle Finanzverantwortlichen möglich
* Abrechnungen werden automatisch auf bezahlt gesetzt, wenn Sie im Verwendungszweck vom Import erwähnt werden, 
    * auch wenn diese noch nicht im Status "angewiesen" sind, sondern nur erst im Status "genehmigt"
* verschiedene Fixes

**Buchungen und Haushaltsplan**
* Button "Gebucht" in der Abrechnung nicht mehr klickbar, da die Abrechnung dadurch nur im Nirvana verschwunden ist.
* Stornieren Knopf wurde aus der Buchungsliste entfernt, da nicht funktionsfähig
* Neue Haushaltstitel zu systematischen Erfassung der Umsatzsteuer hinzugefügt

**Steuern**
* Hinzufügen von standardisierten Haushaltstiteln für die Umsatzsteuer im Haushaltsplan mit einem Klick
* Hinzufügen von Umsatzsteuerposten im Projekt mit einem Klick direkt mit den entsprechenden Haushaltstitlen

**DATEV-Export**
* Neuer DATEV-Export: Buchungen eines Haushaltsplans können als DATEV-Exportdatei heruntergeladen werden
* Auswahl von Zeitraum und Stichtag (Buchungsdatum, Erstelldatum der Abrechnung, frühestes Beleg- oder Zahlungsdatum)
* Optionaler Export der zugehörigen Belege als PDF
* Vorschau, welche Auslagen exportiert werden

**Weitere Fixes**
* Ausgewähltes Haushaltsjahr wird nun tabübergreifend gespeichert
* Vereinheitlichung von Begriffen und Erweiterung der Lokalisierung
* Verschiedene technische und optische Anpassungen, Verbesserungen und Updates
* Update auf Laravel 12 und Livewire 4
---

# v4.3.2
* **Brotkrumen wurden hinzugefügt:** Es ist nun links oben ersichtlich, wo du dich innerhalb StuFiS befindest und kannst schneller innerhalb der Struktur die Seite wechseln. Der Projekt-Button ist nun nach rechts oben gewandert.
* **Testverbesserungen**
* **Verschiedene Fehlerbehebungen**

---

# v4.3.1
* Profilbilder können aus dem SSO übernommen werden
* Routen wurden repariert, die zuvor dazu geführt haben, dass der automatische Bankeinzug auf die falsche Seite weitergeleitet hat.
* Ältere nicht genutzte Dienste wurden entfernt.

---

# v4.3.0

### Bankgeschäfte und Transaktionen
* **Neues Formular für Bankkonten:** Ein brandneues Formular zum Anlegen von Bankkonten wurde hinzugefügt.
* **Detailansicht für Banktransaktionen:** Eine Detailansicht für Banktransaktionen wurde implementiert und überall verlinkt.

### Änderungsprotokoll im StuFis
* Links unten bei der Versionsnummer ist dieses Dokument zu finden
* Hinweis für Nutzer:innen über neue Updates

### Allgemeine Verbesserungen und Fehlerbehebungen
* **Neue Standardschriftart (Inter):** Die Standardschriftart der Anwendung wurde von Open Sans zu Inter geändert, mit verbesserten Schriftstärken und variabler Schriftverwendung für ein moderneres Erscheinungsbild.
* **Verbessertes Caching:** Es wurden weitere Caching-Mechanismen für eine verbesserte Leistung implementiert.
* **Verbesserte Fehlerprotokollierung:** Die Fehlerprotokollierung für HHP-Uploads wurde verbessert, um bessere Einblicke zu ermöglichen.
* **Profilverknüpfung:** Es wurde ein Link zum StuMV rechts oben hinzugefügt.
* **Verschiedene Fehlerbehebungen**
* **PHP 8.4 Upgrade**

---

# v4.2.6

* **Verbesserungen beim CSV-Kontoupload:**
    * Das Formular "CSV hochladen" füllt nun das zuvor ausgewählte Konto vorab aus.
    * Unterschiedliche Reihenfolgen innerhalb der CSV-Dateien werden nun korrekt behandelt, wenn Konten nach einem CSV-Upload geändert werden.
* **UI-Stabilität:**
    * Probleme, bei denen Teile der Benutzeroberfläche unerwartet verschwanden, wurden behoben.
    * Fälle, in denen Validierungsfehler auch nach der Änderung von `csv_order` bestehen blieben, wurden behoben.
    * Einige benutzerdefinierte UI-Komponenten wurden durch stabilere FluxUI-Komponenten ersetzt.
* **Testverbesserungen**
* **Verschiedene Fehlerbehebungen**

---

# v4.2.5

Änderung der Konfiguration für den StuRa der FH Erfurt

---

# v4.2.4

* **Abrechnung:** Das Löschen von Abrechnungen ohne PDF-Anhang ist jetzt möglich.
* **Haushaltsplan:** Grundlage für die komplette Überarbeitung gelegt.

---

# v4.2.3

* **PHP Framework Umstellung:** Aktualisierung auf Laravel 11.x.
* **Konfiguration:** Anpassungen für den StuRa der FH Erfurt und der EAH Jena
* **Testverbesserungen**
* **Verschiedene Fehlerbehebungen und Updates**

---

# v4.2.2

* **Konfiguration:** Anpassungen für den StuRa der FH Erfurt
* **Testverbesserungen**

---

# v4.2.1

Behebung eines Fehlers, der das Erstellen von Abrechnungen verhindert hat.

---

# v4.2.0

* **Externe Projekte:** Grundlage für neue Antragsformulare geschaffen
* **FluxUI:** StuFiS nutzt jetzt `fluxui.dev` (Lizenz für Hosting erforderlich). So entstehen beim Selbsthosting zwar weitere Kosten, doch kann die Entwicklung schneller vorangehen.
* **Erweiterte Konfigurationsoptionen:**
    * Mehr Optionen in der Konfiguration zur Zeichenanzahl in der Projektbeschreibung.
    * Link zum Protokoll/Entscheidung im Projekt optional (Ja/Nein) und Umbenennung möglich.
    * Verschiedene Einträge für Studierendenschaften angepasst. BU Weimar hinzugefügt
* **Optimierte Felder für Rechtsgrundlagen:** Die Felder für Rechtsgrundlagen in Projekten wurden optimiert.
* **Neue Übersicht für offene Projekte:** Eine neue Übersicht für offene Projekte wurde auf der Startseite hinzugefügt.
* **Fehlerbehebung "Automatischer Status gebucht":** Ein Fehler beim automatischen Status "gebucht" wurde behoben.
* **Testverbesserungen**
* **Verschiedene Fehlerbehebungen**

---

# v4.1.6

* **Konfiguration:** Anpassungen für den StuRa der FH Erfurt
* **Fehlerbehebung im Haushaltsplan:** Einnahmen werden wieder berücksichtigt, auch wenn in der Abrechnung sowohl Einnahmen als auch Ausgaben vorhanden sind.

---

# v4.1.5

* **Konfiguration:** Anpassungen für die StuRa der FH Erfurt und der EAH Jena
* Aktualisierung der Readme-Datei

---

# v4.1.4

Berechnung der Bargeld- und Transferkonten korrigiert.

---

# v4.1.3

Neue Validierung für die Datumssortierung im Konto-CSV-Import.

---

# v4.1.2

* **Angepasste Texte im CSV-Import:** Texte im CSV-Import wurden angepasst, um Verwirrung zu reduzieren.
* **Leere Zeilen im CSV-Import werden ignoriert** 
* **Verbesserte Datumsanzeige** 

---

# v4.1.1

Fehlerbehebungen im Konto-CSV-Import.

---

# v4.1.0

Import von Kontoumsätzen durch CSV-Upload eingeführt.

---

# v4.0.2

* **Automatisches Update mit Backup:** Ein automatisches Update-System inklusive Backup-Funktion wurde implementiert.
* **Fehler 500-Bildschirm hinzugefügt:** Ein spezieller Bildschirm für den Fehler 500 (Serverfehler) wurde hinzugefügt, um eine bessere Benutzererfahrung bei internen Serverproblemen zu gewährleisten.
* **Versionsnummer in der Benutzeroberfläche:** Die aktuelle Versionsnummer wird nun sichtbar links unten im Menü angezeigt.

---

# v4.0.1

* Bessere Fehlermeldung in Projekten bei fehlenden Haushaltstiteln
* **Konfiguration:** Anpassungen für den StuRa der TU Ilmenau
* **Verschiedene Fehlerbehebungen**

---

# v4.0.0

### Verwaltung und neue Funktionen

* **Löschen von Projekten und Konten:** Unter bestimmten Bedingungen ist es nun möglich, Projekte und Konten zu löschen. So bleibt Ihr StuFis aufgeräumt, ohne dass etwas verloren geht, was noch für den Haushaltsabschluss benötigt wird.
* **Revisionszugang:** In Kombination mit unserer neuen Mitgliederverwaltung StuMV ([https://stumv.open-administration.de/](https://stumv.open-administration.de/)) können Sie jetzt einen Revisions-/Sichtzugang für Ihre interne Revision oder Wirtschaftsprüfer erstellen.
* **Optimierte Download-Option für den Haushaltsabschluss:** Es gibt jetzt eine weitere Option, den Haushaltsabschluss herunterzuladen. Individuelle Formatierungen wurden bereits vorgenommen, um die Tabelle direkt präsentabel zu machen.
* **Modernes Design & Mobile Optimierung**

### Weiteres

* **Diverse Fehlerbehebungen** 
* **PHP 8.2 Upgrade**

