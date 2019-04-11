(Deutsche Beschreibung weiter unten)
# Instruction Manager #

English version to follow

# Anleitungsverwaltung #

Diese Software dient der Verwaltung von Dokumenten, Bildern, Videos, ..., Links, Beschreibungen, Materiallisten, ..., Bewertungen, ... für Anleitungen (oder anderen Krams). Die Software basiert auf einem Ordner/Projekt-Prinzip mit einer Ordnerstruktur (wie von Windows, Linux, etc. gewohnt) mit Projekten in denen Dateien hochgeladen werden können, aber auch Materiallisten, Linklisten und sonstiges schnell und übersichtlich notiert werden kann.

Weitere Infos und Screenshots gibt es auf der Website zum Tool: --Zurzeit nicht verfügbar--

* Aktuelle Version: 1.1.0

## Installation ##
Für die Installation werden folgende Dinge benötigt:

* PHP-fähiger Webserver (mit PDO-Erweiterung)
* keine Datenbank (es wird SQLite und Textdateien genutzt), phpliteadmin kann verwendet werden

Schritte

* Das Verzeichnis sollte für die Anwendung beschreibbar sein (unter Linux sollte CHMOD 755 gelten)
* Zurzeit sind zwei Sprachen verfügbar: de_DE und en_GB, wobei en_GB als Standard gesetzt ist.
	* Soll die Sprache geändert werden, muss in der Datei _index.php_ die Variable _$lang_ auf _de_DE_ gesetzt werden (oder eine eigene Sprache, die im Verzeichnis locales liegt)
* Im Verzeichnis _manager_ müssen die Hauptkategorien als Ordner angelegt werden. Das Namensformat ist dabei zwingend einzuhalten:
	* C_*x*_*name*
	* hierbei muss *x* durch eine einzigartige ID ersetzt werden (beginnend mit 1) und *name* durch eine Kategoriebezeichnung (Leerzeichen erlaubt, Sonderzeichen und Umlaute sollten hier vermieden werden)
	* Beispiele: "C_1_Origami", "C_87_3D Drucke", "C_4_Max Muster Projekt"
* Zuletzt muss die Datenbank initialisiert werden, dazu die Website aufrufen und am Ende der URL den Parameter _?p=New.Install_ anhängen
	* Beispiele:
		* localhost/Anleitungen/ => localhost/Anleitungen/?p=New.Install
		* example.com => example.com?p=New.Install

## Sicherheitshinweis ##
Die Software ist für den Eigengebrauch entstanden und entspricht keinen Sicherheitsrichtlinien. Deswegen sollte die Software nur auf lokalen Servern (bspw. XAMPP, LAMP, ...) installiert werden oder mit htaccess/htpasswd zugriffsgeschützt werden. Formulardaten werden nur mäßig validiert.

Es ist aber zu erwähnen, dass selbst wenn CRF/XSS-Attacken oder SQL-Injections möglich sind, die SQLite-Datenbank keine sensiblen Informationen enthält. Sensible Daten können nur in hochgeladenen Dateien oder als Projekt/Ordnername vorliegen.

## Beteiligung am Projekt ##

* Gerne können Vorschläge für das Projekt gemacht werden, dazu einfach links auf Issues klicken und einen neuen Vorschlag erstellen - da das Projekt aber aktuell ruht, bitte keine allzugroßen Hoffnungen machen
* Pull-Request sind auch gerne gesehen
* Übersetzungen sind herzlich willkommen! Im Verzeichnis locales findet sich eine Vorlage für neue Übersetzungen (_new.php_), bitte einfach entsprechend des Sprachcodes umbenennen