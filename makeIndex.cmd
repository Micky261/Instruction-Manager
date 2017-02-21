cd "C:\XAMPP\htdocs\Anleitungsverwaltung\"
echo "Pfad gewechselt."
del index.zip
echo "Alte index.zip geloescht."
C:\Programme\7-Zip\7z.exe a -tzip index.zip "index.php"
echo "Neue index.zip generiert - Terminierung."