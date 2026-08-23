# v4.4.4
Verschiedene Fehler beim FinTS Bankimport behoben. u.a.:
* Das Absenden der Formulare auf den Seiten des Bankzugangs führte zu einer Fehlerseite. Betroffen waren das Anlegen eines Zugangs, die Auswahl des TAN-Verfahrens und jede TAN-Eingabe. 
* Die Login Daten werden nun konsequent und korrekt an die Bank übergeben.
* In der Bezeichnung eines Bankzugangs und eines Kontos sind nun auch Leerzeichen und Ziffern nutzbar.
* Der Import erkennt die bekannten Umsätze nun zuverlässig und bricht mit einer klaren Meldung ab, wenn er den Anknüpfungspunkt nicht findet, anstatt Buchungen zu verdoppeln.
* Die Formulare der Bankzugang-Seiten sind nun gegen Anfragen von fremden Seiten abgesichert (CSRF-Schutz).
* Ein Konto aus dem Bankzugang wird nun über die normale Seite „Konto anlegen“ eingerichtet.
* Bei einem Konto, das aus einem Bankzugang übernommen wird, sind die IBAN und der Schalter „Manuelles Eintragen möglich“ nun gesperrt. Manuelles Eintragen würde die automatische Synchronisation ausschließen, die für dieses Konto ja gerade eingerichtet wird.
* Der Aufruf des Umsatzimports für ein noch nicht eingerichtetes Konto führte zu einer Fehlerseite; nun wird auf das Anlegen des Kontos hingewiesen. Gehört ein Konto nicht zum gewählten Bankzugang, wird das ebenfalls verständlich gemeldet.
* Bei Konten ohne hinterlegtes Startdatum wird der Zeitraum nun der Bank überlassen, statt ein Datum zu erfinden. Zuvor führte ein fehlendes Startdatum zum Abbruch.
* Wurde ein Umsatzabruf durch die TAN-Abfrage unterbrochen und danach ein anderes Konto geöffnet, konnten die Umsätze des ursprünglich abgefragten Kontos dem falschen Konto zugeordnet werden. Ein unterbrochener Abruf wird nun nur noch für genau das Konto und den Zeitraum fortgesetzt, für den er begonnen wurde.
* Bei Banken, die für den Umsatzabruf eine TAN verlangen, wurde nach deren Eingabe erneut eine TAN angefordert, statt den Abruf abzuschließen – der Import kam dadurch nie zum Ende. Nach der TAN-Eingabe werden die Umsätze nun tatsächlich übernommen.
* Eine von der Bank abgelehnte TAN führte zu einer Fehlerseite, sodass der Vorgang abgebrochen war. Nun erscheint der Hinweis „TAN nicht akzeptiert“ und die Eingabe kann wiederholt werden. Ebenso führen gestörte Antworten der Bank beim Abrufen der TAN-Verfahren, der TAN-Medien und beim Abmelden nicht mehr zu einer Fehlerseite.
* Freigabe-Verfahren ohne TAN-Eingabe (z. B. pushTAN-Freigabe in der Banking-App) werden nun unterstützt. Statt eines TAN-Feldes erscheint ein Hinweis, die Freigabe in der Banking-App zu erteilen, und ein Knopf „Ich habe die Freigabe erteilt“. Ein Klick darauf fragt einmalig bei der Bank nach, ob die Freigabe angekommen ist; ist sie es noch nicht, kann nach kurzer Wartezeit erneut geklickt werden.
* Während einer TAN-Abfrage beim Umsatzabruf war nirgends zu sehen, für welches Konto sie eigentlich gilt. Der Pfad oben auf der Seite nennt das Konto nun beim Namen.
* War die Sitzung abgelaufen, endete jeder Klick auf den Seiten des Bankzugangs in einer Fehlerseite. Stattdessen erscheint nun ein Hinweis und die erneute Anmeldung beim Bankzugang wird angeboten.
* Der Kontostand der Bank wird nun gegen den zuletzt gespeicherten Stand geprüft.
* Beim Anlegen eines Bankzugangs lässt sich nun jede FinTS-fähige deutsche Bank auswählen (mit Suche nach Name, BLZ oder BIC). Bisher standen nur die Banken zur Auswahl, die vorher von Hand in der Datenbank eingetragen worden waren. Name und FinTS-Adresse kommen jetzt aus einer gepflegten Bankenliste: Stellt eine Bank ihren Zugang auf eine neue Adresse um – was regelmäßig vorkommt –, wird das beim nächsten Aktualisieren der Liste automatisch übernommen. Zuvor blieb ein Bankzugang auf der alten Adresse stehen, bis jemand sie direkt in der Datenbank korrigiert hat.
* Wird ein neues Konto aus einem Bankzugang übernommen, ist auf der Seite „Konto anlegen“ nicht mehr von einer „Kasse“ die Rede – ein solches Konto ist immer ein echtes Bankkonto. Der Knopf heißt dort jetzt „Speichern und weiter zum automatischen Abruf“, weil es danach mit der Einrichtung des Abrufs weitergeht.
* Fehlte in einem Formular der Name, lautete die Meldung überall „Bitte gib einen Namen für das Projekt an.“ – auch beim Anlegen eines Kontos. Sie ist nun neutral formuliert und passt zu jedem Formular.
* Ein Bankzugang lässt sich nun wieder löschen. Das Papierkorb-Symbol in der Übersicht war vorhanden, führte aber ins Leere – Zugangsdaten waren über die Oberfläche überhaupt nicht entfernbar. Vor dem Löschen wird nachgefragt und dabei erklärt, was verschwindet und was bleibt: die Konten und ihre bereits importierten Buchungen bleiben erhalten, nur der automatische Abruf endet. Das Symbol erscheint jetzt auch dann, wenn man am Bankzugang nicht angemeldet ist – der häufigste Grund zum Löschen ist ja, dass die Anmeldung nicht funktioniert.
* Beim Anlegen eines Bankzugangs war im Feld „Bank“ immer schon die erste Bank der Liste vorausgewählt. Wer das Feld nicht anfasste, legte den Zugang unbemerkt bei dieser Bank an. Das Feld steht nun auf „Bank auswählen“; wird ohne Auswahl abgesendet, weist das Formular darauf hin.
* Ist für eine Bank eine FinTS-Adresse hinterlegt, die nicht mit `https://` beginnt, wird der Abruf nun abgebrochen, statt PIN und TAN unverschlüsselt zu übertragen. Aus der Bankenliste kommen ausschließlich `https://`-Adressen; betreffen kann das nur eine von Hand eingetragene Adresse aus der Zeit vor der Bankenliste, die der erste Abgleich ohnehin korrigiert.

Buchungen:
* Der Knopf „als .zip“ in der Buchungshistorie führte zu einer Fehlerseite, statt das Archiv herunterzuladen. Der Download funktioniert nun wieder und enthält für jeden Haushaltstitel eine CSV-Datei mit den Buchungen des Haushaltsjahres.
* Unter der Buchungshistorie steht jetzt auch der DATEV-Export zur Verfügung – derselbe Knopf, der schon in der Ansicht eines Haushaltsplans sitzt. Er erscheint nur, wenn der DATEV-Export in den Einstellungen aktiviert ist.

Betrieb der Instanz:
* Die Protokolldateien (Logs) wachsen nicht mehr unbegrenzt. StuFiS schreibt nun für jeden Tag eine eigene Datei und löscht alles, was älter als 30 Tage ist. Bisher lief alles in eine einzige Datei, die nie gekürzt wurde – auf den Servern gibt es keine automatische Rotation. Außerdem werden die Logs nicht mehr in jede Sicherung mitkopiert. Für bestehende Instanzen: in der `.env` `LOG_CHANNEL=daily` setzen (ein dort noch eingetragenes `LOG_STACK=single` sticht die neue Voreinstellung) und die alte große Datei `storage/logs/laravel.log` einmalig löschen.
* Neu: die Liste der FinTS-fähigen Banken (rund 4000 Institute) in der Tabelle `fints_institutes`. `bin/stufis-update` liest sie ab jetzt bei jedem Deployment selbst ein, es ist also kein zusätzlicher Handgriff nötig. Schlägt der Abruf fehl (z. B. keine Internetverbindung), bricht das Update **nicht** ab: es erscheint eine Warnung, die bisherige Liste bleibt stehen, und der Befehl `php artisan stufis:fints-institutes-update` kann später von Hand nachgeholt werden. Wer nur selten deployt, sollte diesen Befehl zusätzlich etwa monatlich per Cron laufen lassen, weil die Banken ihre FinTS-Adressen regelmäßig umstellen. `--dry-run` zeigt vorab, was sich ändern würde; mit `--file=/pfad/blz.properties` lässt sich die Liste auch aus einer lokalen Datei einlesen. Solange die Liste leer ist, lässt sich kein *neuer* Bankzugang anlegen – die Seite weist darauf hin; bestehende Bankzugänge funktionieren weiter (siehe nächster Punkt).
* Die Tabelle `konto_bank` wurde entfernt. Sie enthielt eine von Hand gepflegte Kopie derselben Daten (Name, BLZ, FinTS-Adresse), die nun aus der Bankenliste kommen. Bestehende Bankzugänge werden bei der Migration automatisch übernommen: Sie verweisen danach über die BLZ auf die Bankenliste und behalten zunächst genau die bisher eingetragene FinTS-Adresse. Erst der erste Abgleich mit der Bankenliste korrigiert eine veraltete Adresse – und meldet die Änderung im Ausgabeprotokoll des Befehls.
* Hinweis zur Herkunft der Liste: Die offizielle FinTS-Bankenliste der Deutschen Kreditwirtschaft wird nur an registrierte FinTS-Hersteller herausgegeben und darf nicht als Teil einer Software weitergegeben werden. StuFiS verwendet daher die öffentlich gepflegte, gleichwertige Liste des Projekts hbci4java. Die Quelle ist über `FINTS_INSTITUTE_LIST_URL` in der `.env` austauschbar.
* **Empfohlen für bestehende Instanzen: in der `.env` `SESSION_ENCRYPT=true` setzen.** Während eines Bankdialogs liegen das Online-Banking-Passwort und der Sitzungszustand der Bank in der Sitzung – absichtlich, damit das Passwort nie in der Datenbank landet. Bei `SESSION_DRIVER=file` ist diese Sitzung aber eine Datei unter `storage/framework/sessions/`, und ohne diese Einstellung stehen die Daten dort im Klartext. Beim Umstellen werden alle offenen Sitzungen ungültig, d. h. alle Anmeldungen müssen einmalig erneuert werden; ein laufender Bankdialog bricht dabei ab. Neue Installationen bekommen die Einstellung aus der `.env.example` mit.

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

