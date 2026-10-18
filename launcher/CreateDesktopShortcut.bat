@echo off
set "TARGET=%~dp0StartLibrarySystem.bat"
set "DESKTOP=%USERPROFILE%\Desktop"
powershell -NoProfile -ExecutionPolicy Bypass -Command "$s=(New-Object -ComObject WScript.Shell).CreateShortcut('%DESKTOP%\Library Laptop Management System.lnk');$s.TargetPath='%TARGET%';$s.WorkingDirectory='%~dp0';$s.IconLocation='%~dp0..\assets\icon.ico';$s.Save()"
echo Desktop shortcut created.
pause
