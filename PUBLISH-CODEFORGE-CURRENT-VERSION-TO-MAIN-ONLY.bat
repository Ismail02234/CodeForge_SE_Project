@echo off
setlocal EnableExtensions EnableDelayedExpansion

rem ============================================================================
rem PUBLISH-CODEFORGE-CURRENT-VERSION-TO-MAIN-ONLY.bat
rem
rem Purpose:
rem   1. Preserve the current CodeForge source.
rem   2. Commit the current working version.
rem   3. Push the current branch to GitHub as a safety checkpoint.
rem   4. Merge that exact version into main.
rem   5. Push main.
rem   6. Delete ONLY the source/current branch from GitHub and locally
rem      after the merge succeeds, so this version remains on main.
rem
rem Safety:
rem   - NO force push.
rem   - NO Git history rewrite.
rem   - NO deletion of unrelated branches.
rem   - Stops and aborts if a merge conflict occurs.
rem   - Keeps .env, node_modules, vendor, backups and PATCH-*.ps1 out of Git.
rem ============================================================================

set "PROJECT=D:\xampp\htdocs\codeforge"
set "EXPECTED_REPO=https://github.com/Ismail02234/CodeForge_SE_Project.git"
set "COMMIT_MESSAGE=Finalize CodeForge framework version"
set "MERGE_MESSAGE=Merge finalized CodeForge version into main"

title CodeForge - Publish Current Version to Main

echo.
echo ============================================================
echo  CODEFORGE - PUBLISH CURRENT VERSION TO MAIN ONLY
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

echo [1/12] Checking repository...
git rev-parse --is-inside-work-tree >nul 2>&1
if errorlevel 1 (
    echo [ERROR] This directory is not a Git working tree.
    pause
    exit /b 1
)
echo [OK] Git repository detected.

echo.
echo [2/12] Checking origin remote...
git remote get-url origin >nul 2>&1
if errorlevel 1 (
    echo [INFO] origin is missing. Adding:
    echo        %EXPECTED_REPO%
    git remote add origin "%EXPECTED_REPO%"
    if errorlevel 1 (
        echo [ERROR] Could not add origin.
        pause
        exit /b 1
    )
)

for /f "delims=" %%R in ('git remote get-url origin') do set "ORIGIN_URL=%%R"
echo [OK] origin = !ORIGIN_URL!

echo.
echo [3/12] Applying repository safety ignores...

call :EnsureIgnore ".env"
call :EnsureIgnore ".env.*"
call :EnsureIgnore "!.env.example"
call :EnsureIgnore "backend/.env"
call :EnsureIgnore "frontend/.env"
call :EnsureIgnore "backend/vendor/"
call :EnsureIgnore "frontend/node_modules/"
call :EnsureIgnore "patch-backups/"
call :EnsureIgnore "legacy-php/"
call :EnsureIgnore "PATCH-*.ps1"
call :EnsureIgnore "*.log"

echo [OK] Safety ignore rules checked.

echo.
echo [4/12] Detecting current branch...
for /f "delims=" %%B in ('git branch --show-current') do set "SOURCE_BRANCH=%%B"

if "!SOURCE_BRANCH!"=="" (
    echo [ERROR] Git is in detached HEAD state.
    echo         Switch to your CodeForge working branch first.
    pause
    exit /b 1
)

echo [OK] Current branch: !SOURCE_BRANCH!

echo.
echo [5/12] Checking for tracked secrets or generated dependencies...

git ls-files | findstr /R /I /C:"^backend/\.env$" /C:"^frontend/\.env$" /C:"/node_modules/" /C:"/vendor/" >nul
if not errorlevel 1 (
    echo [ERROR] A secret/dependency path is already tracked.
    echo.
    echo Tracked matches:
    git ls-files | findstr /R /I /C:"^backend/\.env$" /C:"^frontend/\.env$" /C:"/node_modules/" /C:"/vendor/"
    echo.
    echo Remove those from Git tracking before publishing.
    pause
    exit /b 1
)

echo [OK] No tracked .env/vendor/node_modules paths detected.

echo.
echo [6/12] Staging the finalized CodeForge version...
git add -A
if errorlevel 1 (
    echo [ERROR] git add failed.
    pause
    exit /b 1
)

rem Explicitly unstage patch scripts if an old Git rule caused one to be staged.
for /f "delims=" %%F in ('git diff --cached --name-only ^| findstr /R /I "^PATCH-.*\.ps1$"') do (
    git restore --staged -- "%%F" >nul 2>&1
)

echo.
echo Staged changes:
git diff --cached --stat

echo.
echo [7/12] Committing current version...

git diff --cached --quiet
if errorlevel 1 (
    git commit -m "%COMMIT_MESSAGE%"
    if errorlevel 1 (
        echo [ERROR] Commit failed.
        pause
        exit /b 1
    )
    echo [OK] Current version committed.
) else (
    echo [INFO] No new staged changes. Using the existing HEAD commit.
)

for /f "delims=" %%H in ('git rev-parse HEAD') do set "SOURCE_COMMIT=%%H"
echo [OK] Source commit: !SOURCE_COMMIT!

echo.
echo [8/12] Pushing source branch as a temporary safety checkpoint...

if /I "!SOURCE_BRANCH!"=="main" (
    echo [INFO] You are already on main. No temporary branch push is needed.
) else (
    git push -u origin "!SOURCE_BRANCH!"
    if errorlevel 1 (
        echo [ERROR] Could not push !SOURCE_BRANCH!.
        echo         Nothing has been merged into main yet.
        pause
        exit /b 1
    )
    echo [OK] Temporary source branch checkpoint pushed.
)

echo.
echo [9/12] Fetching latest GitHub state...
git fetch origin --prune
if errorlevel 1 (
    echo [ERROR] Could not fetch from GitHub.
    pause
    exit /b 1
)
echo [OK] Remote state refreshed.

echo.
echo [10/12] Updating main and merging finalized version...

if /I "!SOURCE_BRANCH!"=="main" (
    git pull --ff-only origin main
    if errorlevel 1 (
        echo [ERROR] main could not be fast-forwarded from origin.
        echo         Resolve the divergence before publishing.
        pause
        exit /b 1
    )

    git push origin main
    if errorlevel 1 (
        echo [ERROR] Could not push main.
        pause
        exit /b 1
    )

    echo [OK] Current main pushed successfully.
    goto :VERIFY_MAIN
)

git show-ref --verify --quiet refs/remotes/origin/main
if errorlevel 1 (
    echo [INFO] origin/main does not exist. Creating main from this finalized version...
    git switch -C main "!SOURCE_COMMIT!"
    if errorlevel 1 (
        echo [ERROR] Could not create local main.
        pause
        exit /b 1
    )
) else (
    git show-ref --verify --quiet refs/heads/main
    if errorlevel 1 (
        git switch -c main --track origin/main
    ) else (
        git switch main
    )

    if errorlevel 1 (
        echo [ERROR] Could not switch to main.
        pause
        exit /b 1
    )

    git pull --ff-only origin main
    if errorlevel 1 (
        echo [ERROR] Could not safely update main from GitHub.
        echo         Source branch remains untouched on GitHub.
        pause
        exit /b 1
    )

    git merge --no-ff "!SOURCE_COMMIT!" -m "%MERGE_MESSAGE%"
    if errorlevel 1 (
        echo.
        echo [ERROR] Merge conflict detected.
        echo         Aborting merge so main remains unchanged...
        git merge --abort >nul 2>&1
        echo.
        echo Your finalized source is still safe on:
        echo   !SOURCE_BRANCH!
        echo.
        pause
        exit /b 1
    )
)

git push -u origin main
if errorlevel 1 (
    echo [ERROR] Merge succeeded locally, but pushing main failed.
    echo         The source branch has NOT been deleted.
    pause
    exit /b 1
)

echo [OK] Finalized CodeForge version pushed to main.

:VERIFY_MAIN
echo.
echo [11/12] Verifying GitHub main contains the finalized source commit...

git fetch origin main >nul 2>&1

git merge-base --is-ancestor "!SOURCE_COMMIT!" origin/main
if errorlevel 1 (
    echo [ERROR] Verification failed.
    echo         The finalized commit is not reachable from origin/main.
    echo         Source branch will NOT be deleted.
    pause
    exit /b 1
)

for /f "delims=" %%M in ('git rev-parse origin/main') do set "MAIN_COMMIT=%%M"

echo [OK] origin/main contains finalized version.
echo      Source commit: !SOURCE_COMMIT!
echo      Main HEAD:     !MAIN_COMMIT!

echo.
echo [12/12] Removing only the temporary source branch...

if /I "!SOURCE_BRANCH!"=="main" (
    echo [INFO] No source branch to remove. You were already on main.
) else (
    git push origin --delete "!SOURCE_BRANCH!"
    if errorlevel 1 (
        echo [WARN] main is correct, but GitHub branch deletion failed.
        echo        You can remove "!SOURCE_BRANCH!" later.
    ) else (
        echo [OK] Remote branch "!SOURCE_BRANCH!" deleted.
    )

    git branch -D "!SOURCE_BRANCH!" >nul 2>&1
    if errorlevel 1 (
        echo [WARN] Could not delete the local source branch.
    ) else (
        echo [OK] Local source branch "!SOURCE_BRANCH!" deleted.
    )
)

echo.
echo ============================================================
echo  SUCCESS - CODEFORGE FINAL VERSION IS ON MAIN
echo ============================================================
echo.
echo Current local branch:
git branch --show-current
echo.
echo Main commit:
git log -1 --oneline
echo.
echo Remote branches:
git branch -r
echo.
echo Git status:
git status --short
echo.
echo GitHub:
echo https://github.com/Ismail02234/CodeForge_SE_Project
echo.
echo NOTE:
echo   Unrelated teammate/legacy branches are NOT deleted.
echo   Only the branch you published from is removed after verification.
echo   No force push and no history rewrite were used.
echo.
pause
exit /b 0


:EnsureIgnore
set "RULE=%~1"
findstr /X /C:"%RULE%" .gitignore >nul 2>&1
if errorlevel 1 (
    >>.gitignore echo %RULE%
)
exit /b 0
