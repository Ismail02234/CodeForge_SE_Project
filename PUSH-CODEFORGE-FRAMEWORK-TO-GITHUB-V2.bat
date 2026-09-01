@echo off
setlocal EnableExtensions EnableDelayedExpansion
title CodeForge GitHub Push - Framework Branch

rem ============================================================
rem CodeForge Framework GitHub Push - V2
rem
rem Project:
rem   D:\xampp\htdocs\codeforge
rem
rem GitHub repository:
rem   https://github.com/Ismail02234/CodeForge_SE_Project
rem
rem Target branch:
rem   nafiz/framework-migration
rem
rem This version automatically adds the missing "origin" remote.
rem It never force-pushes and never deletes existing branches.
rem ============================================================

set "PROJECT=D:\xampp\htdocs\codeforge"
set "TARGET_BRANCH=nafiz/framework-migration"
set "REMOTE_URL=https://github.com/Ismail02234/CodeForge_SE_Project.git"
set "COMMIT_MESSAGE=Migrate CodeForge to Laravel 11 and SvelteKit"

echo.
echo ============================================================
echo       CODEFORGE FRAMEWORK - GITHUB PUSH LAUNCHER V2
echo ============================================================
echo.

if not exist "%PROJECT%\.git" (
    echo [ERROR] Git repository not found:
    echo         %PROJECT%
    echo.
    pause
    exit /b 1
)

cd /d "%PROJECT%" || (
    echo [ERROR] Could not open CodeForge project directory.
    pause
    exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Git is not installed or not available in PATH.
    pause
    exit /b 1
)

echo [1/10] Checking repository...
git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 (
    echo [ERROR] D:\xampp\htdocs\codeforge is not a valid Git work tree.
    pause
    exit /b 1
)
echo [OK] Git repository detected.

echo.
echo [2/10] Configuring GitHub remote...

set "CURRENT_ORIGIN="
for /f "delims=" %%R in ('git remote get-url origin 2^>nul') do set "CURRENT_ORIGIN=%%R"

if not defined CURRENT_ORIGIN (
    echo [INFO] No origin remote exists.
    echo [INFO] Adding:
    echo        %REMOTE_URL%

    git remote add origin "%REMOTE_URL%"
    if errorlevel 1 (
        echo [ERROR] Could not add the GitHub origin remote.
        pause
        exit /b 1
    )

    set "CURRENT_ORIGIN=%REMOTE_URL%"
    echo [OK] GitHub origin added.
) else (
    echo Current origin:
    echo   !CURRENT_ORIGIN!

    echo !CURRENT_ORIGIN! | findstr /I "Ismail02234/CodeForge_SE_Project" >nul
    if errorlevel 1 (
        echo.
        echo [SAFETY STOP] An origin exists, but it points somewhere else.
        echo Existing:
        echo   !CURRENT_ORIGIN!
        echo Expected:
        echo   %REMOTE_URL%
        echo.
        echo Nothing has been changed or pushed.
        pause
        exit /b 1
    )

    echo [OK] Correct GitHub origin already configured.
)

echo.
echo Git remotes:
git remote -v

echo.
echo [3/10] Updating .gitignore safeguards...

if not exist ".gitignore" type nul > ".gitignore"

call :EnsureIgnore "backend/.env"
call :EnsureIgnore "frontend/.env"
call :EnsureIgnore ".env"
call :EnsureIgnore ".env.*"
call :EnsureIgnore "backend/vendor/"
call :EnsureIgnore "frontend/node_modules/"
call :EnsureIgnore "frontend/.svelte-kit/"
call :EnsureIgnore "frontend/build/"
call :EnsureIgnore "frontend/dist/"
call :EnsureIgnore "patch-backups/"
call :EnsureIgnore "legacy-plain-php-*/"
call :EnsureIgnore "tools/composer/"
call :EnsureIgnore "*.phar"
call :EnsureIgnore "*.log"
call :EnsureIgnore "*.tmp"
call :EnsureIgnore ".idea/"
call :EnsureIgnore ".vscode/"
call :EnsureIgnore ".DS_Store"
call :EnsureIgnore "Thumbs.db"

echo [OK] Git ignore safeguards are present.

echo.
echo [4/10] Checking for tracked secrets...

set "TRACKED_RISK=0"

for %%P in (
    "backend/.env"
    "frontend/.env"
    ".env"
) do (
    git ls-files --error-unmatch "%%~P" >nul 2>&1
    if not errorlevel 1 (
        echo [WARN] %%~P is already tracked by Git.
        set "TRACKED_RISK=1"
    )
)

if "!TRACKED_RISK!"=="1" (
    echo.
    echo [SAFETY STOP] A real .env file is already tracked.
    echo The launcher will not push secrets.
    echo.
    echo Remove the tracked .env from Git first, then rerun this file.
    pause
    exit /b 1
)

echo [OK] No tracked .env files detected.

echo.
echo [5/10] Checking current branch and work tree...

set "CURRENT_BRANCH="
for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"

echo Current branch:
echo   !CURRENT_BRANCH!

echo.
git status --short

echo.
echo [6/10] Creating or switching to framework branch...

git show-ref --verify --quiet "refs/heads/%TARGET_BRANCH%"
if errorlevel 1 (
    echo Creating:
    echo   %TARGET_BRANCH%

    git switch -c "%TARGET_BRANCH%"
    if errorlevel 1 (
        echo.
        echo [ERROR] Could not create the framework branch.
        echo Your current files were not pushed.
        pause
        exit /b 1
    )
) else (
    for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"

    if /I "!CURRENT_BRANCH!"=="%TARGET_BRANCH%" (
        echo [OK] Already on framework branch.
    ) else (
        echo Switching to existing:
        echo   %TARGET_BRANCH%

        git switch "%TARGET_BRANCH%"
        if errorlevel 1 (
            echo.
            echo [ERROR] Could not switch to the framework branch.
            echo This can happen if local changes conflict with that branch.
            echo Nothing was pushed.
            pause
            exit /b 1
        )
    )
)

for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"

if /I not "!CURRENT_BRANCH!"=="%TARGET_BRANCH%" (
    echo [ERROR] Branch safety check failed.
    echo Active branch: !CURRENT_BRANCH!
    pause
    exit /b 1
)

echo [OK] Active branch:
echo      !CURRENT_BRANCH!

echo.
echo [7/10] Staging current CodeForge framework files...

git add -A
if errorlevel 1 (
    echo [ERROR] git add failed.
    pause
    exit /b 1
)

echo.
echo [8/10] Scanning staged files for generated/secrets content...

set "BAD_STAGE=0"

for /f "delims=" %%F in ('git diff --cached --name-only') do (
    set "F=%%F"

    if /I "!F!"=="backend/.env" set "BAD_STAGE=1"
    if /I "!F!"=="frontend/.env" set "BAD_STAGE=1"
    if /I "!F!"==".env" set "BAD_STAGE=1"

    echo !F! | findstr /I /B /C:"backend/vendor/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"frontend/node_modules/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"frontend/.svelte-kit/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"patch-backups/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"tools/composer/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"legacy-plain-php-" >nul && set "BAD_STAGE=1"
)

if "!BAD_STAGE!"=="1" (
    echo.
    echo [SAFETY STOP] One or more sensitive/generated files are staged.
    echo.
    echo Staged files:
    git diff --cached --name-only
    echo.
    echo Nothing was committed or pushed.
    pause
    exit /b 1
)

echo [OK] Staged files passed safety scan.

echo.
echo Files ready for the framework commit:
echo ------------------------------------------------------------
git status --short
echo ------------------------------------------------------------

echo.
echo [9/10] Creating commit if needed...

git diff --cached --quiet
if not errorlevel 1 (
    echo [INFO] No new staged changes were found.
    echo [INFO] The current framework state may already be committed.
) else (
    git commit -m "%COMMIT_MESSAGE%"
    if errorlevel 1 (
        echo.
        echo [ERROR] Git commit failed.
        echo Nothing was pushed.
        pause
        exit /b 1
    )

    echo [OK] Framework commit created.
)

echo.
echo [10/10] Pushing framework branch to GitHub...
echo.
echo Repository:
echo   %REMOTE_URL%
echo.
echo Branch:
echo   %TARGET_BRANCH%
echo.

git push -u origin "%TARGET_BRANCH%"
if errorlevel 1 (
    echo.
    echo [ERROR] GitHub push failed.
    echo.
    echo Your local branch and commit are safe.
    echo Common causes:
    echo   - GitHub authentication is required
    echo   - your GitHub account lacks repository write permission
    echo   - internet connection problem
    echo   - remote branch has unrelated newer commits
    echo.
    echo No force-push was attempted.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo              GITHUB PUSH COMPLETED SUCCESSFULLY
echo ============================================================
echo.
echo Repository:
echo   https://github.com/Ismail02234/CodeForge_SE_Project
echo.
echo Branch:
echo   %TARGET_BRANCH%
echo.
echo Your older branches remain untouched.
echo No force-push was used.
echo.
echo Future updates:
echo   git add -A
echo   git commit -m "Update CodeForge"
echo   git push
echo.
pause
exit /b 0


:EnsureIgnore
set "RULE=%~1"
findstr /X /L /C:"%RULE%" ".gitignore" >nul 2>&1
if errorlevel 1 (
    >>".gitignore" echo %RULE%
)
exit /b 0
