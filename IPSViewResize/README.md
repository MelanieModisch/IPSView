# IPSView Resize PHP Module for IP-Symcon
Das Modul stellt Funktionen zur Synchronisierung von Views mit Änderung der Skalierung zur Verfügung

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Synchronisierung von einer Master View auf eine View für ein weiteres Anzeige Device.
* Möglichkeit zur Änderung der Skalierung der Child View
* Timer für eine zyklische Überprüfung auf Änderung der Master View

### 2. Voraussetzungen

- IP-Symcon ab Version 4.x
- IPSStudio ab der Version 3.x

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/brownson/IPSView.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'IPSViewResize'-Modul unter dem Hersteller '(IPSView)' aufgeführt.  

__Konfigurationsseite__:

Name                    | Beschreibung
----------------------- | ---------------------------------
Orignal View            | Media Objekt der Master View
Ziel View               | Media Objekt der Child View
Skalierung X            | Verhältnis in X Richtung für die Änderung der Skalierung 
Skalierung Y            | Verhältnis in Y Richtung für die Änderung der Skalierung 
Autom Synchronisierung  | Änderungen der Master View überwachen und automatische Synchronisierung 
Interval                | Zeitinterval für die Überwachung der Master View

__Testseite__:

Name                    | Beschreibung
----------------------- | ---------------------------------
Button "Resize"         | Master View auf Child View synchronisieren

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen angelegt

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. WebFront

Keine spezielle Visualisierung für das WebFront vorhanden

### 7. PHP-Befehlsreferenz

`boolean IPSView_Resize(integer $InstanzID);`  
Synchronisiert die Views.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`IPSView_Resize(12345);`

