# v4.3.2
* **Brotkrumen wurden hinzugefügt:** Es ist nun links oben ersichtlich, wo du dich innerhalb StuFiS befindest und kannst schneller innerhalb der Struktur die Seite wechseln. Der Projekt-Button ist nun nach rechts oben gewandert.
* **Testverbesserungen**
* **Verschiedene Fehlerbehebungen**

# v4.3.1
* Profilbilder können aus dem SSO übernommen werden
* Routen wurden repariert, die zuvor dazu geführt haben, dass der automatische Bankeinzug auf die falsche Seite weitergeleitet hat.
* Ältere nicht genutzte Dienste wurden entfernt.

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

