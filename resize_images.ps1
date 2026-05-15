Add-Type -AssemblyName System.Drawing

function Resize-Image {
    param (
        [string]$SourceFile,
        [string]$DestinationFile,
        [int]$Width,
        [int]$Height
    )
    
    $img = [System.Drawing.Image]::FromFile($SourceFile)
    $newImg = New-Object System.Drawing.Bitmap($Width, $Height)
    $g = [System.Drawing.Graphics]::FromImage($newImg)
    
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.DrawImage($img, 0, 0, $Width, $Height)
    
    $newImg.Save($DestinationFile, [System.Drawing.Imaging.ImageFormat]::Png)
    
    $g.Dispose()
    $newImg.Dispose()
    $img.Dispose()
    Write-Host "Resized to $($Width) x $($Height): $DestinationFile" -ForegroundColor Green
}

$assetsPath = "c:\Users\dev\Programing\wp_svn_sgoplus\assets"

Write-Host "Resizing WordPress.org Assets..." -ForegroundColor Cyan

# Resize Banners
Resize-Image -SourceFile "$assetsPath\banner-1544x500.png" -DestinationFile "$assetsPath\banner-772x250.png" -Width 772 -Height 250

# Resize Icons
Resize-Image -SourceFile "$assetsPath\icon-256x256.png" -DestinationFile "$assetsPath\icon-128x128.png" -Width 128 -Height 128

# Sync to live folder if it exists
$liveAssets = "c:\Users\dev\Programing\sgoplus_live_svn\assets"
if (Test-Path $liveAssets) {
    Write-Host "Syncing to live folder..." -ForegroundColor Cyan
    Copy-Item -Path "$assetsPath\*" -Destination $liveAssets -Force
}

Write-Host "Done!" -ForegroundColor Yellow
