@echo off
setlocal EnableExtensions EnableDelayedExpansion

rem ============================================================================
rem PUSH-CODEFORGE-FEATURES-TO-MAIN.bat
rem
rem Safely pushes the CURRENT CodeForge source to GitHub main.
rem Intended after:
rem - Tamjid feature integration
rem - Ankita Live Skill Graph integration
rem - Submission Anomaly Detection integration
rem - Rectangular Skill Graph UI update
rem
rem Safety:
rem - no force push
rem - fetches origin/main first
rem - stops if remote main contains commits missing locally
rem - stages current source changes/deletions
rem - excludes one-time PATCH scripts and patch-backups from the commit
rem - excludes this helper BAT itself
rem - verifies origin/main matches local HEAD after push
rem ============================================================================

set "PROJECT=D:\xampp\htdocs\codeforge"
set "REMOTE=origin"
set "BRANCH=main"
set "COMMIT_MESSAGE=Integrate teammate features and performance profile modules"

title CodeForge - Push Latest Features to GitHub Main

echo.
echo ============================================================
echo  CODEFORGE - PUSH LATEST FEATURES TO GITHUB MAIN
echo ============================================================
echo.

cd /d "%PROJECT%" || (
    echo [ERROR] Could not open:
    echo         %PROJECT%
    pause
    exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Git is not available in PATH.
    pause
    exit /b 1
)

git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 (
    echo [ERROR] This folder is not a Git repository.
    pause
    exit /b 1
)

echo [1/9] Checking branch...

for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"

if /I not "!CURRENT_BRANCH!"=="%BRANCH%" (
    echo [ERROR] Current branch is:
    echo         !CURRENT_BRANCH!
    echo.
    echo This script only publishes from local main.
    pause
    exit /b 1
)

echo [OK] Current branch: main

echo.
echo [2/9] Checking origin...

git remote get-url "%REMOTE%" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] origin remote is missing.
    pause
    exit /b 1
)

for /f "delims=" %%R in ('git remote get-url "%REMOTE%"') do set "ORIGIN_URL=%%R"
echo [OK] origin = !ORIGIN_URL!

echo.
echo [3/9] Fetching latest GitHub main...

git fetch "%REMOTE%" "%BRANCH%"
if errorlevel 1 (
    echo [ERROR] Could not fetch origin/main.
    pause
    exit /b 1
)

git show-ref --verify --quiet "refs/remotes/%REMOTE%/%BRANCH%"
if errorlevel 1 (
    echo [ERROR] origin/main does not exist.
    pause
    exit /b 1
)

for /f "delims=" %%L in ('git rev-parse HEAD') do set "LOCAL_BEFORE=%%L"
for /f "delims=" %%R in ('git rev-parse "%REMOTE%/%BRANCH%"') do set "REMOTE_BEFORE=%%R"

echo [INFO] Local HEAD:  !LOCAL_BEFORE!
echo [INFO] Remote main: !REMOTE_BEFORE!

git merge-base --is-ancestor "%REMOTE%/%BRANCH%" HEAD >nul 2>&1
if errorlevel 1 (
    echo.
    echo [ERROR] GitHub main contains commits that are not in local main.
    echo.
    echo Nothing has been committed or pushed.
    echo Pull/merge the latest main first. Do NOT force push.
    pause
    exit /b 1
)

echo [OK] Local main contains the latest GitHub main history.

echo.
echo [4/9] Protecting local-only maintenance files...

set "SELF_NAME=%~nx0"

if exist ".git\info\exclude" (
    for %%P in (
        "PATCH-*.ps1"
        "patch-backups/"
        "%SELF_NAME%"
    ) do (
        findstr /X /C:"%%~P" ".git\info\exclude" >nul 2>&1
        if errorlevel 1 (
            >>".git\info\exclude" echo %%~P
        )
    )
)

echo [OK] Patch scripts and patch-backups are treated as local-only.

echo.
echo [5/9] Staging current project source...

git add -A
if errorlevel 1 (
    echo [ERROR] git add failed.
    pause
    exit /b 1
)

rem Keep one-time helper/patch artifacts out of the commit even if present.
git reset -q -- "%SELF_NAME%" >nul 2>&1
git reset -q -- "patch-backups" >nul 2>&1

for %%F in (PATCH-*.ps1) do (
    if exist "%%F" (
        git reset -q -- "%%F" >nul 2>&1
    )
)

echo.
echo Staged change summary:
git --no-pager diff --cached --stat

echo.
echo Staged files:
git --no-pager diff --cached --name-status

echo.
set /p "CONFIRM=Commit and push these staged source changes to GitHub main? (Y/N): "

if /I not "!CONFIRM!"=="Y" (
    echo.
    echo Push cancelled.
    echo Staged files were left staged so you can inspect them.
    pause
    exit /b 0
)

echo.
echo [6/9] Creating commit if changes exist...

git diff --cached --quiet
if not errorlevel 1 (
    echo [INFO] No new staged source changes to commit.
    goto :PUSH
)

git commit -m "%COMMIT_MESSAGE%"
if errorlevel 1 (
    echo [ERROR] Commit failed.
    pause
    exit /b 1
)

echo [OK] Feature integration commit created.

:PUSH
for /f "delims=" %%H in ('git rev-parse HEAD') do set "FINAL_LOCAL=%%H"

echo.
echo [7/9] Pushing main to GitHub...

git push "%REMOTE%" "%BRANCH%"
if errorlevel 1 (
    echo.
    echo [ERROR] Push failed.
    echo No force push was attempted.
    pause
    exit /b 1
)

echo [OK] Push completed.

echo.
echo [8/9] Verifying GitHub main...

git fetch "%REMOTE%" "%BRANCH%" >nul 2>&1

for /f "delims=" %%R in ('git rev-parse "%REMOTE%/%BRANCH%"') do set "FINAL_REMOTE=%%R"

if /I not "!FINAL_LOCAL!"=="!FINAL_REMOTE!" (
    echo [ERROR] Verification failed.
    echo Local main:  !FINAL_LOCAL!
    echo Remote main: !FINAL_REMOTE!
    pause
    exit /b 1
)

echo [OK] origin/main exactly matches local HEAD.

echo.
echo [9/9] Final repository status...
echo.

git log -1 --oneline
echo.
git status --short

echo.
echo ============================================================
echo  SUCCESS - LATEST CODEFORGE FEATURES ARE ON GITHUB MAIN
echo ============================================================
echo.
echo Included source changes should contain the current:
echo   - Tamjid personalized problem recommendation
echo   - Tamjid contest win-probability module
echo   - Ankita Live Skill Graph
echo   - Ankita Submission Anomaly Detection
echo   - Rectangular topic skill graph UI
echo.
echo One-time PATCH scripts and patch-backups were NOT staged.
echo No force push or history rewrite was used.
echo.
echo Repository:
echo   https://github.com/Ismail02234/CodeForge_SE_Project
echo.

pause
exit /b 0
