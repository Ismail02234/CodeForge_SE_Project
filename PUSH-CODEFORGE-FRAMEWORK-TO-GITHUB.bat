@echo off
setlocal EnableExtensions EnableDelayedExpansion
title CodeForge GitHub Push - Framework Branch

rem ============================================================
rem CodeForge Framework GitHub Push
rem Repository: https://github.com/Ismail02234/CodeForge_SE_Project
rem Target branch: nafiz/framework-migration
rem
rem Safe behavior:
rem - does NOT delete or overwrite existing branches
rem - does NOT force-push
rem - ensures generated/secrets folders are ignored
rem - refuses to continue if sensitive/generated paths are staged
rem - commits only when there are staged changes
rem ============================================================

set "PROJECT=D:\xampp\htdocs\codeforge"
set "TARGET_BRANCH=nafiz/framework-migration"
set "EXPECTED_REMOTE=https://github.com/Ismail02234/CodeForge_SE_Project"
set "COMMIT_MESSAGE=Migrate CodeForge to Laravel 11 and SvelteKit"

echo.
echo ============================================================
echo        CODEFORGE FRAMEWORK - GITHUB PUSH LAUNCHER
echo ============================================================
echo.

if not exist "%PROJECT%\.git" (
    echo [ERROR] Git repository not found:
    echo         %PROJECT%
    echo.
    echo This launcher expects the existing CodeForge Git repository.
    pause
    exit /b 1
)

cd /d "%PROJECT%" || (
    echo [ERROR] Could not open project folder.
    pause
    exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Git is not installed or is not available in PATH.
    echo.
    pause
    exit /b 1
)

echo [1/9] Checking repository...
git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 (
    echo [ERROR] This folder is not a valid Git work tree.
    pause
    exit /b 1
)
echo [OK] Git repository detected.

echo.
echo [2/9] Checking GitHub remote...
set "REMOTE_URL="
for /f "delims=" %%R in ('git remote get-url origin 2^>nul') do set "REMOTE_URL=%%R"

if not defined REMOTE_URL (
    echo [ERROR] No 'origin' remote is configured.
    echo Expected repository:
    echo %EXPECTED_REMOTE%
    echo.
    pause
    exit /b 1
)

echo Current origin:
echo   !REMOTE_URL!

echo !REMOTE_URL! | findstr /I "Ismail02234/CodeForge_SE_Project" >nul
if errorlevel 1 (
    echo.
    echo [ERROR] The current 'origin' does not look like the expected repository.
    echo Expected:
    echo   %EXPECTED_REMOTE%
    echo.
    echo Nothing has been pushed.
    pause
    exit /b 1
)
echo [OK] Correct GitHub repository detected.

echo.
echo [3/9] Updating .gitignore safeguards...

if not exist ".gitignore" type nul > ".gitignore"

call :EnsureIgnore "backend/.env"
call :EnsureIgnore "frontend/.env"
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
call :EnsureIgnore ".idea/"
call :EnsureIgnore ".vscode/"
call :EnsureIgnore ".DS_Store"
call :EnsureIgnore "Thumbs.db"

echo [OK] .gitignore safeguards are present.

echo.
echo [4/9] Checking for sensitive files already tracked...

set "TRACKED_RISK=0"

for %%P in (
    "backend/.env"
    "frontend/.env"
) do (
    git ls-files --error-unmatch "%%~P" >nul 2>&1
    if not errorlevel 1 (
        echo [WARN] %%~P is already tracked by Git.
        set "TRACKED_RISK=1"
    )
)

if "!TRACKED_RISK!"=="1" (
    echo.
    echo [SAFETY STOP] A .env file is already tracked.
    echo This launcher will not automatically remove tracked secrets.
    echo Fix that first before pushing.
    echo.
    pause
    exit /b 1
)
echo [OK] No tracked .env files detected.

echo.
echo [5/9] Preparing target branch...

for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"
echo Current branch: !CURRENT_BRANCH!

git show-ref --verify --quiet "refs/heads/%TARGET_BRANCH%"
if errorlevel 1 (
    echo Creating local branch:
    echo   %TARGET_BRANCH%
    git switch -c "%TARGET_BRANCH%"
    if errorlevel 1 (
        echo [ERROR] Could not create the framework branch.
        pause
        exit /b 1
    )
) else (
    if /I not "!CURRENT_BRANCH!"=="%TARGET_BRANCH%" (
        echo Switching to existing local branch:
        echo   %TARGET_BRANCH%
        git switch "%TARGET_BRANCH%"
        if errorlevel 1 (
            echo.
            echo [ERROR] Could not switch branches.
            echo You may have conflicting uncommitted changes.
            echo Nothing has been pushed.
            pause
            exit /b 1
        )
    ) else (
        echo [OK] Already on target branch.
    )
)

for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"
if /I not "!CURRENT_BRANCH!"=="%TARGET_BRANCH%" (
    echo [ERROR] Safety check failed: wrong active branch.
    pause
    exit /b 1
)
echo [OK] Active branch: !CURRENT_BRANCH!

echo.
echo [6/9] Staging current framework project...
git add -A
if errorlevel 1 (
    echo [ERROR] git add failed.
    pause
    exit /b 1
)

echo.
echo [7/9] Running staged-file safety scan...

set "BAD_STAGE=0"

for /f "delims=" %%F in ('git diff --cached --name-only') do (
    set "F=%%F"

    if /I "!F!"=="backend/.env" set "BAD_STAGE=1"
    if /I "!F!"=="frontend/.env" set "BAD_STAGE=1"

    echo !F! | findstr /I /B /C:"backend/vendor/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"frontend/node_modules/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"frontend/.svelte-kit/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"patch-backups/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"tools/composer/" >nul && set "BAD_STAGE=1"
    echo !F! | findstr /I /B /C:"legacy-plain-php-" >nul && set "BAD_STAGE=1"
)

if "!BAD_STAGE!"=="1" (
    echo.
    echo [SAFETY STOP] Sensitive/generated files are staged.
    echo.
    echo Staged files:
    git diff --cached --name-only
    echo.
    echo Nothing has been committed or pushed.
    pause
    exit /b 1
)

echo [OK] Staged files passed safety scan.

echo.
echo Changes ready for GitHub:
echo ------------------------------------------------------------
git status --short
echo ------------------------------------------------------------

git diff --cached --quiet
if not errorlevel 1 (
    echo.
    echo [INFO] There are no new changes to commit.
    echo The branch may already contain the current framework version.
) else (
    echo.
    echo [8/9] Creating commit...
    git commit -m "%COMMIT_MESSAGE%"
    if errorlevel 1 (
        echo [ERROR] Commit failed.
        echo Nothing has been pushed.
        pause
        exit /b 1
    )
    echo [OK] Commit created.
)

echo.
echo [9/9] Pushing framework branch to GitHub...
echo Repository:
echo   %EXPECTED_REMOTE%
echo Branch:
echo   %TARGET_BRANCH%
echo.

git push -u origin "%TARGET_BRANCH%"
if errorlevel 1 (
    echo.
    echo [ERROR] GitHub push failed.
    echo.
    echo Your local commit is safe. Common causes:
    echo   - GitHub authentication is required
    echo   - internet connection problem
    echo   - repository permission issue
    echo.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo                 PUSH COMPLETED SUCCESSFULLY
echo ============================================================
echo.
echo Repository:
echo   %EXPECTED_REMOTE%
echo.
echo Branch:
echo   %TARGET_BRANCH%
echo.
echo Your previous branch was not force-pushed or deleted.
echo.
echo Future updates on this branch can be pushed with:
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
