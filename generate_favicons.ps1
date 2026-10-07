Add-Type -AssemblyName System.Drawing

$srcPath = "c:\xamp\htdocs\noir\public\uploads\settings\logo-6f5565220d062cea.jpg"
$bmp = [System.Drawing.Bitmap]::FromFile($srcPath)

# 1. Save high-res PNG for browsers
$pngPath = "c:\xamp\htdocs\noir\public\uploads\settings\favicon-logo.png"
$bmp.Save($pngPath, [System.Drawing.Imaging.ImageFormat]::Png)

# 2. Generate 32x32, 64x64, 192x192 icons
$sizes = @(16, 32, 48, 64, 180, 192, 512)
foreach ($s in $sizes) {
    $resized = New-Object System.Drawing.Bitmap($s, $s)
    $g = [System.Drawing.Graphics]::FromImage($resized)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.DrawImage($bmp, 0, 0, $s, $s)
    $g.Dispose()
    
    $outPath = "c:\xamp\htdocs\noir\public\favicon-${s}x${s}.png"
    $resized.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $resized.Dispose()
}

# 3. Create a valid binary ICO file with 16x16, 32x32, 48x48 icon frames
# In .NET, an Icon can be constructed or written using ICO format
# Standard ICO format header: 0x0000 0x0001 (1 = icon) Count: 3
$icoPath = "c:\xamp\htdocs\noir\public\favicon.ico"

# Create a 32x32 bitmap for icon handle
$icoBmp = New-Object System.Drawing.Bitmap(32, 32)
$gIco = [System.Drawing.Graphics]::FromImage($icoBmp)
$gIco.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$gIco.DrawImage($bmp, 0, 0, 32, 32)
$gIco.Dispose()

$hIcon = $icoBmp.GetHicon()
$icon = [System.Drawing.Icon]::FromHandle($hIcon)
$fs = New-Object System.IO.FileStream($icoPath, [System.IO.FileMode]::Create)
$icon.Save($fs)
$fs.Close()
$fs.Dispose()
$icon.Dispose()
$icoBmp.Dispose()
$bmp.Dispose()

Write-Output "FAVICONS_GENERATED_SUCCESSFULLY"
