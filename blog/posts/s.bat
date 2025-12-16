@echo off
REM Use the full path to the Python executable if it's not in your PATH
REM Or just use 'python' if the Python interpreter is already in your system's PATH

REM --- 1. Define the Python Script Path ---
set SCRIPT_DIR=C:\Users\whybo\Desktop\
set SCRIPT_NAME=gitFTP.py

REM --- 2. Execute the Python Script ---
REM The command below assumes 'python' is already recognized.
python3 "%SCRIPT_DIR%%SCRIPT_NAME%"

REM --- 3. Pause for user to see the output (optional) ---
pause