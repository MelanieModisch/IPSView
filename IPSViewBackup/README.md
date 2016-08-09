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
Directory               | Verzeichnis in das die Backups abgelegt werden (Verzeichnis muss existieren). Angabe eines Verzeichnisnames relativ zum IP-Symcon Root Verzeichnis ist möglich.
AutoBackup              | Änderungen der View überwachen und automatisches Backup 
Interval                | Zeitinterval für die Überwachung der Master View
AutoPurge               | Ältere Backups automatisch löschen
PurgeDays               | Anzahl der Tage nach denen ein Backup wieder gelöscht werden kann.
Button "Backup"         | Backup der View erstellen

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name         | Typ       | Beschreibung
------------ | --------- | ----------------
LastBackup   | Integer   | Datum und Uhrzeit des letzten Backups

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. WebFront

Keine spezielle Visualisierung für das WebFront vorhanden

### 7. PHP-Befehlsreferenz

`boolean IPSView_Backup(integer $InstanzID);`  
Backup der View erstellen.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`IPSView_Backup(12345);

`boolean IPSView_Restore(integer $InstanzID, string $date, string $time);`  
View aus einem Backup wiederherstellen.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`IPSView_Restore(12345, '20160801','1600');`

