@echo off
REM Activer l'environnement virtuel
call "%~dp0venv\Scripts\activate"

REM Aller dans le dossier du script pour éviter les erreurs de chemin
cd /d "%~dp1"

REM Exécuter le script Python avec tous les arguments
python "%~nx1" %2 %3 %4

REM Désactiver (optionnel)
REM deactivate
