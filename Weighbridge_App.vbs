Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "cmd /c C:\xampp\htdocs\weighbridge-printS\start_weighbridge.bat", 0, False
Set WshShell = Nothing