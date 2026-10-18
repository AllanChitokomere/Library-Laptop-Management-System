
@echo off
setlocal

set "XAMPP=C:\Library Laptop Final\xampp"
set "APP=http://127.0.0.1:8081/LibraryLaptopManagementSystem/"

echo Starting Library Laptop Management System...
echo.

echo Starting MySQL on port 3307...
start "" "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini"

timeout /t 3 /nobreak >nul

echo Starting Apache on port 8081...
start "" "%XAMPP%\apache\bin\httpd.exe" -f "%XAMPP%\apache\conf\httpd.conf"

timeout /t 3 /nobreak >nul

echo Opening Library Laptop Management System...
start "" "%APP%"

exit
```
