@echo off
setlocal EnableExtensions EnableDelayedExpansion

rem ============================================================================
rem CLEAN-GITHUB-LOCAL-TOOLS.bat
rem
rem Removes unnecessary root-level patch / push / recovery / handoff / guide
rem files from the CodeForge Git repository, while preserving important runtime
rem and setup files such as:
rem   START-CODEFORGE.bat
rem   start-codeforge.ps1
rem   SETUP-CODEFORGE.bat
rem   setup-codeforge.ps1
rem
rem Before deletion, every matched file is copied to an external backup folder
rem beside the project so nothing is lost.
rem
rem It then:
rem - updates .gitignore so these helper artifacts are not pushed again
rem - commits the cleanup
rem - pushes normally to origin/main (NO force push)
rem - verifies GitHub main equals local main
rem ============================================================================

set "PROJECT=D:\xampp\htdocs\codeforge"
set "REMOTE=origin"
set "BRANCH=main"
set "EXPECTED_REPO=https://github.com/Ismail02234/CodeForge_SE_Project.git"

title CodeForge - Remove Patch and Instruction Files from GitHub

echo.
echo ============================================================
echo  CODEFORGE - CLEAN PATCH / INSTRUCTION FILES FROM GITHUB
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

if exist ".git\MERGE_HEAD" (
    echo [ERROR] A Git merge is currently in progress.
    echo Resolve or abort it before running this cleanup.
    pause
    exit /b 1
)

echo [1/8] Verifying branch and repository...

for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"
if /I not "!CURRENT_BRANCH!"=="%BRANCH%" (
    echo [ERROR] Current branch is !CURRENT_BRANCH!, not main.
    pause
    exit /b 1
)

for /f "delims=" %%R in ('git remote get-url "%REMOTE%"') do set "ORIGIN_URL=%%R"
if /I not "!ORIGIN_URL!"=="%EXPECTED_REPO%" (
    echo [ERROR] Unexpected origin:
    echo         !ORIGIN_URL!
    echo Expected:
    echo         %EXPECTED_REPO%
    pause
    exit /b 1
)

echo [OK] main -> !ORIGIN_URL!

echo.
echo [2/8] Fetching latest GitHub main...

git fetch "%REMOTE%" "%BRANCH%"
if errorlevel 1 (
    echo [ERROR] Could not fetch origin/main.
    pause
    exit /b 1
)

git merge-base --is-ancestor "%REMOTE%/%BRANCH%" HEAD >nul 2>&1
if errorlevel 1 (
    echo [ERROR] GitHub main has commits that are not in this local main.
    echo Nothing was changed.
    pause
    exit /b 1
)

echo [OK] Local main contains current GitHub main.

echo.
echo [3/8] Finding unnecessary tracked root-level files...

set "LISTFILE=%TEMP%\codeforge-clean-%RANDOM%-%RANDOM%.txt"
if exist "!LISTFILE!" del /q "!LISTFILE!" >nul 2>&1

rem Exact known local-only instruction / handoff artifacts
for %%F in (
    "CODEFORGE_MASTER_HANDOFF_CONTEXT.md"
) do (
    git ls-files --error-unmatch -- "%%~F" >nul 2>&1
    if not errorlevel 1 echo %%~F>>"!LISTFILE!"
)

rem Root-level maintenance / patch helper patterns only.
for /f "delims=" %%F in ('git ls-files "PATCH-*.ps1" "PUSH-*.bat" "PUBLISH-*.bat" "RECOVER-*.bat" "REMOVE-*.bat" "CLEANUP-*.bat" "SYNC-*.bat" "FORCE-PUSH-*.bat" "RESOLVE-*.ps1" "*HANDOFF*.md" "*CONTRIBUTION_GUIDE*.txt" "*CONTRIBUTION_GUIDE*.md" 2^>nul') do (
    echo %%F>>"!LISTFILE!"
)

rem Never remove runtime/start/setup files.
if exist "!LISTFILE!" (
    findstr /V /I /X /C:"START-CODEFORGE.bat" /C:"start-codeforge.ps1" /C:"SETUP-CODEFORGE.bat" /C:"setup-codeforge.ps1" "!LISTFILE!" >"!LISTFILE!.filtered"
    move /Y "!LISTFILE!.filtered" "!LISTFILE!" >nul
)

rem De-duplicate list.
if exist "!LISTFILE!" (
    sort /UNIQUE "!LISTFILE!" /O "!LISTFILE!.sorted" >nul
    move /Y "!LISTFILE!.sorted" "!LISTFILE!" >nul
)

if not exist "!LISTFILE!" (
    echo [INFO] No matching tracked helper files were found.
    echo.
    echo Nothing needs to be removed.
    pause
    exit /b 0
)

for %%Z in ("!LISTFILE!") do if %%~zZ EQU 0 (
    echo [INFO] No matching tracked helper files were found.
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 0
)

echo.
echo The following tracked files will be removed from GitHub:
echo ------------------------------------------------------------
type "!LISTFILE!"
echo ------------------------------------------------------------
echo.
echo IMPORTANT:
echo   START-CODEFORGE.bat and start-codeforge.ps1 are protected.
echo   Setup/runtime files are protected.
echo   Application source code is not matched by this cleanup.
echo.

set /p "CONFIRM=Remove ALL files shown above from the repository? (Y/N): "
if /I not "!CONFIRM!"=="Y" (
    echo.
    echo Cleanup cancelled. Nothing was changed.
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 0
)

echo.
echo [4/8] Backing up the files outside the Git repository...

for /f "tokens=1-4 delims=/ " %%a in ("%date%") do (
    set "DATESTAMP=%%a-%%b-%%c-%%d"
)
set "TIMESTAMP=%time::=-%"
set "TIMESTAMP=!TIMESTAMP:.=-!"
set "TIMESTAMP=!TIMESTAMP: =0!"
set "BACKUP=D:\xampp\htdocs\codeforge-local-tools-backup-!DATESTAMP!-!TIMESTAMP!"

mkdir "!BACKUP!" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Could not create backup folder:
    echo         !BACKUP!
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 1
)

for /f "usebackq delims=" %%F in ("!LISTFILE!") do (
    if exist "%%F" (
        for %%D in ("!BACKUP!\%%F") do if not exist "%%~dpD" mkdir "%%~dpD" >nul 2>&1
        copy /Y "%%F" "!BACKUP!\%%F" >nul
    )
)

echo [OK] Backup created:
echo      !BACKUP!

echo.
echo [5/8] Removing the helper files from Git tracking and working tree...

for /f "usebackq delims=" %%F in ("!LISTFILE!") do (
    git rm -f -- "%%F"
    if errorlevel 1 (
        echo [ERROR] Could not remove:
        echo         %%F
        echo.
        echo Your external backup is safe at:
        echo   !BACKUP!
        del /q "!LISTFILE!" >nul 2>&1
        pause
        exit /b 1
    )
)

echo [OK] Unnecessary tracked helper files removed.

echo.
echo [6/8] Updating .gitignore so they are not pushed again...

if not exist ".gitignore" type nul > ".gitignore"

call :AddIgnore "/PATCH-*.ps1"
call :AddIgnore "/PUSH-*.bat"
call :AddIgnore "/PUBLISH-*.bat"
call :AddIgnore "/RECOVER-*.bat"
call :AddIgnore "/REMOVE-*.bat"
call :AddIgnore "/CLEANUP-*.bat"
call :AddIgnore "/SYNC-*.bat"
call :AddIgnore "/FORCE-PUSH-*.bat"
call :AddIgnore "/RESOLVE-*.ps1"
call :AddIgnore "/CODEFORGE_MASTER_HANDOFF_CONTEXT.md"
call :AddIgnore "/*HANDOFF*.md"
call :AddIgnore "/*CONTRIBUTION_GUIDE*.txt"
call :AddIgnore "/*CONTRIBUTION_GUIDE*.md"
call :AddIgnore "/patch-backups/"
call :AddIgnore "/merge-conflict-backups/"

git add .gitignore

echo [OK] .gitignore updated.

echo.
echo Files staged for cleanup:
git --no-pager diff --cached --name-status

echo.
set /p "COMMITOK=Commit this cleanup and remove these files from GitHub main? (Y/N): "
if /I not "!COMMITOK!"=="Y" (
    echo.
    echo Commit/push cancelled.
    echo The cleanup is currently staged locally.
    echo External backup:
    echo   !BACKUP!
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 0
)

echo.
echo [7/8] Committing cleanup...

git commit -m "Remove local patch and instruction files"
if errorlevel 1 (
    echo [ERROR] Cleanup commit failed.
    echo External backup:
    echo   !BACKUP!
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 1
)

echo [OK] Cleanup commit created.

echo.
echo [8/8] Pushing cleanup to GitHub main...

git push "%REMOTE%" "%BRANCH%"
if errorlevel 1 (
    echo [ERROR] Push failed.
    echo No force push was attempted.
    echo External backup:
    echo   !BACKUP!
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 1
)

git fetch "%REMOTE%" "%BRANCH%" >nul 2>&1

for /f "delims=" %%L in ('git rev-parse HEAD') do set "LOCAL_HEAD=%%L"
for /f "delims=" %%R in ('git rev-parse "%REMOTE%/%BRANCH%"') do set "REMOTE_HEAD=%%R"

if /I not "!LOCAL_HEAD!"=="!REMOTE_HEAD!" (
    echo [ERROR] Verification mismatch.
    echo Local:  !LOCAL_HEAD!
    echo Remote: !REMOTE_HEAD!
    del /q "!LISTFILE!" >nul 2>&1
    pause
    exit /b 1
)

del /q "!LISTFILE!" >nul 2>&1

echo.
echo ============================================================
echo  SUCCESS - UNNECESSARY FILES REMOVED FROM GITHUB MAIN
echo ============================================================
echo.
echo GitHub:
echo   %EXPECTED_REPO%
echo.
echo External backup:
echo   !BACKUP!
echo.
echo Protected project runtime files were not removed.
echo .gitignore now prevents these helper files being pushed again.
echo.

pause
exit /b 0

:AddIgnore
set "PATTERN=%~1"
findstr /X /C:"!PATTERN!" ".gitignore" >nul 2>&1
if errorlevel 1 (
    >>".gitignore" echo !PATTERN!
)
exit /b 0
