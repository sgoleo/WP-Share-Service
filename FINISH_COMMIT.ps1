$repoUrl = "https://plugins.svn.wordpress.org/sgoplus-file-share"
$livePath = "c:\Users\dev\Programing\sgoplus_live_svn"
$preparedPath = "c:\Users\dev\Programing\wp_svn_sgoplus"
$tortoise = "C:\Program Files\TortoiseSVN\bin\TortoiseProc.exe"

Clear-Host
Write-Host "==============================================" -ForegroundColor Yellow
Write-Host "   SGOplus File Share - SVN Deployment Helper  " -ForegroundColor Yellow
Write-Host "==============================================" -ForegroundColor Yellow
Write-Host ""

# 1. Checkout
if (!(Test-Path "$livePath\.svn")) {
    Write-Host "[Step 1/3] Checking out from WordPress.org..." -ForegroundColor Cyan
    Write-Host "--> Opening Checkout window. Please make sure the URL is correct and click OK." -ForegroundColor Yellow
    
    if (!(Test-Path $livePath)) { New-Item -ItemType Directory -Path $livePath -Force }
    
    $proc = Start-Process $tortoise -ArgumentList "/command:checkout /url:`"$repoUrl`" /path:`"$livePath`"" -PassThru
    $proc.WaitForExit()
    
    # Re-check
    if (!(Test-Path "$livePath\.svn")) {
        Write-Host ""
        Write-Host "ERROR: Checkout failed or was cancelled." -ForegroundColor Red
        Write-Host "Please manually right-click the folder '$livePath' and select 'SVN Checkout'." -ForegroundColor White
        Write-Host "URL: $repoUrl" -ForegroundColor White
        pause
        exit
    }
}

# 2. Sync
Write-Host "[Step 2/3] Syncing prepared files..." -ForegroundColor Cyan
# Ensure we don't delete the .svn folder during sync
Copy-Item -Path "$preparedPath\trunk\*" -Destination "$livePath\trunk" -Recurse -Force
Copy-Item -Path "$preparedPath\tags\*" -Destination "$livePath\tags" -Recurse -Force
Copy-Item -Path "$preparedPath\assets\*" -Destination "$livePath\assets" -Recurse -Force
Write-Host "--> Files synced to $livePath" -ForegroundColor Green

# 3. Add & Commit
Write-Host "[Step 3/3] Opening Commit dialog..." -ForegroundColor Cyan
# We use /command:add first to ensure new files are tracked
Start-Process $tortoise -ArgumentList "/command:add /path:`"$livePath`"" -Wait
Start-Process $tortoise -ArgumentList "/command:commit /path:`"$livePath`" /logmsg:`"Initial release 1.2.3`""

Write-Host ""
Write-Host "Done! Please follow the TortoiseSVN GUI to enter your password and finish the commit." -ForegroundColor Yellow
