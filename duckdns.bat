@echo off
:loop
curl -k "https://www.duckdns.org/update?domains=rpgmumu&token=ad50a942-3868-4464-8efb-013f638c140e&ip="
timeout /t 300
goto loop
