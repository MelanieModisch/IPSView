# IPSView Backup PHP Module for IP-Symcon
Das Modul stellt Funktionen zum Backup von Views zur Verfügung

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Backup einer View in ein Verzeichnis von IP-Symcon.
* Timer für eine zyklische Überprüfung auf Änderung der View
* Möglichkeit zum Wiederherstellen einer View

### 2. Voraussetzungen

- IP-Symcon ab Version 4.x
- IPSStudio ab der Version 3.x

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/brownson/IPSView.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'IPSViewBackup'-Modul unter dem Hersteller '(IPSView)' aufgeführt.  

__Konfigurationsseite__:

Name                    | Beschreibung
----------------------- | ---------------------------------
View                    | Media Objekt der View die gesichert werden soll
Backup Verzeichnis      | Verzeichnis in das die Backups abgelegt werden (Verzeichnis muss existieren). Angabe eines Verzeichnisnames relativ zum IP-Symcon Root Verzeichnis ist möglich.
Autom. Backup erstellen | Änderungen der View überwachen und automatisches Backup 
Interval                | Zeitinterval für die Überwachung der Master View
Automatisches Purge     | Ältere Backups automatisch löschen
Anzahl Tage             | Anzahl der Tage nach denen ein Backup wieder gelöscht werden kann.

__Testsseite__:

Name                    | Beschreibung
----------------------- | ---------------------------------
Backup jetzt erstellen  | Backup der View erstellen
Filename                | Filename zum wiederherstellen der View
Anzahl Dateien zurück   | Anzahl der Dateien zurück zum wiederherstellen der View
View wiederherstellen   | View aus Backup wiederherstellen
Zeige Backupdateien     | Anzeige einer Liste aller vorhandenen Backupdateien

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen angelegt

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. WebFront

Keine spezielle Visualisierung für das WebFront vorhanden

### 7. PHP-Befehlsreferenz

`boolean IPSView_Backup(integer $InstanzID);`  
Backup der View erstellen.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`IPSView_Backup(12345);`

`boolean CheckViewBackup(integer $InstanzID);`  
Überprüft ob sich die spezifizierte View seit dem letzten Backup geändert hat, sollte eine Änderung festgestellt werden, wird ein Backup der View ausgelöst.  
Die Funktion liefert als Rückgabewert: `TRUE` wenn Änderung festgestellt wurde, `FALSE` wenn keine Änderung erkannt wurde.
Beispiel:  
`CheckViewBackup(12345);`

`boolean IPSView_RestoreByFileName(integer $InstanzID, string $file);`  
View aus einer Backupdatei wiederherstellen. $file spezifiziert dabei eine Backupdatei im Backupverzeichnis
Die Funktion liefert als Rückgabewert: `TRUE` wenn die Funktion erfolgreich aufgeführt, `FALSE` wenn die Backupdatei nicht gefunden wurde.  
Beispiel:  
`IPSView_RestoreByFileName(12345, '39962__20160902_2239.ipsView');`

`boolean IPSView_RestoreByFileIdx(integer $InstanzID, int $idx);`  
View aus einem Backupdatei wiederherstellen. $idx spezifiziert dabei die Anzahl der Backupdateien, die zurück gegangen werden  soll (1=letztes Backup wiederherstellen, 2=vorletztes Backup wiederherstellen, ...)
Die Funktion liefert als Rückgabewert: `TRUE` wenn die Funktion erfolgreich aufgeführt, `FALSE` wenn die Backupdatei nicht gefunden wurde.  
Beispiel:  
`IPSView_RestoreByFileIdx(12345, 2);`

`boolean IPSView_GetBackupFiles(integer $InstanzID);`  
Gibt eine Liste der vorhandenen Backupdateien zurück.  
Die Funktion liefert als Rückgabewert: array der vorhandenen Backupdateien.  
Beispiel:  
`IPSView_GetBackupFiles(12345);`

`boolean IPSView_PurgeBackupFiles(integer $InstanzID);`  
Purge der vorhanden Backupdateien ausführen.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`IPSView_PurgeBackupFiles(12345);`
