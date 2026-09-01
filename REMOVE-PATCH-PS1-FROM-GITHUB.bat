@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Remove CodeForge Patch PS1 Files from GitHub

set "PROJECT=D:\xampp\htdocs\codeforge"

echo.
echo ============================================================
echo      REMOVE OLD PATCH PS1 FILES FROM GITHUB
echo ============================================================
echo.

if not exist "%PROJECT%\.git" (
    echo [ERROR] Git repository not found:
    echo         %PROJECT%
    pause
    exit /b 1
)

cd /d "%PROJECT%" || (
    echo [ERROR] Could not open project directory.
    pause
    exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Git is not available in PATH.
    pause
    exit /b 1
)

echo [1/6] Checking current Git branch...
for /f "delims=" %%B in ('git branch --show-current') do set "BRANCH=%%B"

if not defined BRANCH (
    echo [ERROR] Could not determine current Git branch.
    pause
    exit /b 1
)

echo [OK] Current branch: !BRANCH!

echo.
echo [2/6] Checking GitHub remote...
git remote get-url origin >nul 2>&1
if errorlevel 1 (
    echo [ERROR] No origin remote is configured.
    pause
    exit /b 1
)

for /f "delims=" %%R in ('git remote get-url origin') do set "REMOTE=%%R"
echo [OK] Origin: !REMOTE!

echo.
echo [3/6] Updating .gitignore...

if not exist ".gitignore" type nul > ".gitignore"

findstr /X /L /C:"PATCH-*.ps1" ".gitignore" >nul 2>&1
if errorlevel 1 (
    >>".gitignore" echo PATCH-*.ps1
    echo [OK] Added PATCH-*.ps1 to .gitignore
) else (
    echo [OK] PATCH-*.ps1 is already ignored
)

echo.
echo [4/6] Removing PATCH-*.ps1 files from Git tracking...
echo       The local files will be KEPT on your computer.
echo.

set "FOUND=0"

for /f "delims=" %%F in ('git ls-files "PATCH-*.ps1"') do (
    set "FOUND=1"
    echo Untracking: %%F
    git rm --cached -- "%%F"
    if errorlevel 1 (
        echo [ERROR] Failed to untrack %%F
        pause
        exit /b 1
    )
)

if "!FOUND!"=="0" (
    echo [INFO] No tracked PATCH-*.ps1 files were found.
)

echo.
echo [5/6] Committing cleanup...

git add .gitignore

git diff --cached --quiet
if not errorlevel 1 (
    echo [INFO] Nothing new to commit.
) else (
    git commit -m "Remove old patch scripts from repository"
    if errorlevel 1 (
        echo [ERROR] Commit failed.
        pause
        exit /b 1
    )
    echo [OK] Cleanup commit created.
)

echo.
echo [6/6] Pushing cleanup to GitHub branch:
echo       !BRANCH!
echo.

git push origin "!BRANCH!"
if errorlevel 1 (
    echo.
    echo [ERROR] Push failed.
    echo Your local cleanup commit is still safe.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo                  CLEANUP SUCCESSFUL
echo ============================================================
echo.
echo Old PATCH-*.ps1 files were removed from the CURRENT
echo GitHub branch but kept on your local computer.
echo.
echo Current branch:
echo   !BRANCH!
echo.
echo Important runtime files such as these were NOT removed:
echo   start-codeforge.ps1
echo   verify-codeforge.ps1
echo   setup-codeforge.ps1
echo.
echo NOTE:
echo This removes the files from the latest branch state.
echo It does NOT erase them from old Git commit history.
echo.
pause
exit /b 0
