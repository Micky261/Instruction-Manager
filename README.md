(Deutsche Beschreibung weiter unten)
# Instruction Manager #
This software is used to manage documents, images, videos, ..., links, descriptions, material lists, ..., reviews, ... for instructions (or other stuff). The software is based on a folder/project principle with a folder structure (as used by Windows, Linux, etc.) with projects in which files can be uploaded, but also material lists, link lists and other can be noted quickly and clearly.

Further information and screenshots can be found on the tool's website: --Currently not available--

* Current version: 1.1.0

## Installation ##
The following things are required for the installation:

* PHP-enabled web server (with PDO extension)
* no database (SQLite and text files are used), phpliteadmin can be used

Steps

* The directory should be writable for the application (under Linux CHMOD 755 should apply)
* Currently two languages are available: de_DE and en_GB, with en_GB set as default.
	* If you want to change the language, you have to set the variable _$lang_ to _de_DE_ in the file _index.php_ (or use your own language, which is located in the directory _locales_).
	* In the manager directory, the main categories must be created as folders. The name format must be adhered to:
		* C_*x*_*name*
		* here *x* must be replaced by a unique ID (starting with 1) and *name* by a category name (spaces allowed, special characters should be avoided)
		* Examples: "C_1_Origami", "C_87_3D Prints", "C_4_Max Patterns Project".
* Finally, the database must be initialized by calling the website and appending the parameter _?p=New.Install_ to the end of the URL.
	* Examples:
		* localhost/Anleitungen/ => localhost/Anleitungen/?p=New.Install
		* example.com => example.com?p=New.Install

## Security notice ##
The software was created for personal use and does not comply with any security guidelines. Therefore the software should only be installed on local servers (e.g. XAMPP, LAMP, ...) or protected with htaccess/htpasswd. Form data is only moderately validated.

However, it should be mentioned that even if CRF/XSS attacks or SQL injections are possible, the SQLite database does not contain any sensitive information. Sensitive data can only be present in uploaded files or as a project/folder name.

## Participation in the project ##

* Suggestions for the project can be made by simply clicking on _Issues_ on the left and creating a new proposal - but since the project is currently on hold, please don't raise your hopes too high.
* Pull requests are quite welcome
* Translations are welcome! In the _locales_ directory you will find a template for new translations (new.php), please simply rename it according to the language code
	* Please note, that the software is based on German texts, please refer to _en_GB.php_ for English translations

---

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
	* Soll die Sprache geändert werden, muss in der Datei _index.php_ die Variable _$lang_ auf _de_DE_ gesetzt werden (oder eine eigene Sprache, die im Verzeichnis _locales_ liegt)
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

* Gerne können Vorschläge für das Projekt gemacht werden, dazu einfach links auf _Issues_ klicken und einen neuen Vorschlag erstellen - da das Projekt aber aktuell ruht, bitte keine allzugroßen Hoffnungen machen
* Pull-Request sind auch gerne gesehen
* Übersetzungen sind herzlich willkommen! Im Verzeichnis locales findet sich eine Vorlage für neue Übersetzungen (_new.php_), bitte einfach entsprechend des Sprachcodes umbenennen