@echo off
setlocal

cd /d "%~dp0"

echo ============================================
echo  CodeForge Selenium Test Suite
echo ============================================
echo.

python -m pytest -v

echo.
pause
