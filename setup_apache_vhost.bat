@echo off
echo Setting up Apache Virtual Host for Laravel Application...

REM Create backup of original httpd.conf
copy "C:\xampp\apache\conf\httpd.conf" "C:\xampp\apache\conf\httpd.conf.backup" >nul 2>&1

REM Create virtual host configuration
echo ^<VirtualHost *:80^> > "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo     DocumentRoot "C:/xampp/htdocs/PNS-Dhampur/public" >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo     ServerName localhost >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo     ^<Directory "C:/xampp/htdocs/PNS-Dhampur/public"^> >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo         AllowOverride All >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo         Require all granted >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo         DirectoryIndex index.php >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo     ^</Directory^> >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo     ErrorLog "logs/pns-dhampur-error.log" >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo     CustomLog "logs/pns-dhampur-access.log" common >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"
echo ^</VirtualHost^> >> "C:\xampp\apache\conf\extra\pns-dhampur.conf"

REM Add include to httpd.conf if not already present
findstr /C:"Include conf/extra/pns-dhampur.conf" "C:\xampp\apache\conf\httpd.conf" >nul
if errorlevel 1 (
    echo Include conf/extra/pns-dhampur.conf >> "C:\xampp\apache\conf\httpd.conf"
    echo Added virtual host include to httpd.conf
) else (
    echo Virtual host include already exists in httpd.conf
)

echo Virtual host configuration created successfully!
echo.
echo Configuration file: C:\xampp\apache\conf\extra\pns-dhampur.conf
echo.
echo Please restart Apache to apply changes.
echo You can access the Laravel app at: http://localhost/
echo.
pause