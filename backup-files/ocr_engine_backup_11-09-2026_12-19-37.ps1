param (
    [Parameter(Mandatory = $true)]
    [string]$ImagePath
)

try {
    if (-not (Test-Path $ImagePath)) {
        Write-Output '{"status":"error","message":"File not found"}'
        exit 0
    }

    Add-Type -AssemblyName System.Drawing
    Add-Type -AssemblyName System.Runtime.WindowsRuntime
    $asTaskGeneric = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { 
        $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1' 
    }

    function Await-WinRT($WinRtAsync, $ResultType) {
        $asTask = $asTaskGeneric.MakeGenericMethod($ResultType)
        $netTask = $asTask.Invoke($null, @($WinRtAsync))
        $netTask.Wait(-1) | Out-Null
        return $netTask.Result
    }

    [Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime] | Out-Null
    [Windows.Storage.FileAccessMode, Windows.Storage, ContentType = WindowsRuntime] | Out-Null
    [Windows.Storage.Streams.IRandomAccessStream, Windows.Storage.Streams, ContentType = WindowsRuntime] | Out-Null
    [Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics, ContentType = WindowsRuntime] | Out-Null
    [Windows.Graphics.Imaging.SoftwareBitmap, Windows.Graphics, ContentType = WindowsRuntime] | Out-Null
    [Windows.Media.Ocr.OcrEngine, Windows.Foundation, ContentType = WindowsRuntime] | Out-Null
    [Windows.Media.Ocr.OcrResult, Windows.Foundation, ContentType = WindowsRuntime] | Out-Null
    [Windows.Globalization.Language, Windows.Foundation, ContentType = WindowsRuntime] | Out-Null

    $lang = [Windows.Globalization.Language]::new('en-GB')
    $engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromLanguage($lang)
    if (-not $engine) {
        $engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
    }

    function Run-OcrOnBmp($bmp) {
        $ms = New-Object System.IO.MemoryStream
        $bmp.Save($ms, [System.Drawing.Imaging.ImageFormat]::Bmp)
        $bytes = $ms.ToArray()
        $ms.Dispose()

        $ras = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
        $writer = New-Object Windows.Storage.Streams.DataWriter($ras)
        $writer.WriteBytes($bytes)
        Await-WinRT ($writer.StoreAsync()) ([uint32]) | Out-Null
        $ras.Seek(0)
        $decoder = Await-WinRT ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($ras)) ([Windows.Graphics.Imaging.BitmapDecoder])
        $sbm = Await-WinRT ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
        $ocr = Await-WinRT ($engine.RecognizeAsync($sbm)) ([Windows.Media.Ocr.OcrResult])
        $ras.Dispose()
        return $ocr
    }

    $fullPath = (Resolve-Path $ImagePath).Path
    $srcBmp = [System.Drawing.Bitmap]::FromFile($fullPath)

    $allLines = @()
    $allText = ""

    # Pass 1: Full Frame Scan (for close vehicles 1-3 meters)
    $res1 = Run-OcrOnBmp $srcBmp
    if ($res1 -and $res1.Text) {
        $allText += $res1.Text + " "
        foreach ($l in $res1.Lines) { 
            if ($l.Text) { $allLines += $l.Text }
        }
    }

    # Pass 2: Distant Vehicle / 9-Meter Zoom Scan (Vehicle driveway zone 2.5x upscale)
    # Target central vehicle driveway zone (width: center 65%, height: middle 55%)
    if ($srcBmp.Width -ge 400 -and $srcBmp.Height -ge 300) {
        $cropW = [int]($srcBmp.Width * 0.75)
        $cropH = [int]($srcBmp.Height * 0.70)
        $cropX = [int](($srcBmp.Width - $cropW) / 2)
        $cropY = [int]($srcBmp.Height * 0.20)

        $rect = [System.Drawing.Rectangle]::new($cropX, $cropY, $cropW, $cropH)
        $zoneBmp = $srcBmp.Clone($rect, $srcBmp.PixelFormat)

        # 2.5x Super-Resolution Bicubic Upscale
        $scale = 2.5
        $targetW = [int]($zoneBmp.Width * $scale)
        $targetH = [int]($zoneBmp.Height * $scale)

        $scaledBmp = New-Object System.Drawing.Bitmap($targetW, $targetH)
        $g = [System.Drawing.Graphics]::FromImage($scaledBmp)
        $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
        $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        $g.DrawImage($zoneBmp, 0, 0, $targetW, $targetH)
        $g.Dispose()

        $res2 = Run-OcrOnBmp $scaledBmp
        if ($res2 -and $res2.Text) {
            $allText += $res2.Text + " "
            foreach ($l in $res2.Lines) {
                if ($l.Text) { $allLines += $l.Text }
            }
        }

        $zoneBmp.Dispose()
        $scaledBmp.Dispose()
    }

    $srcBmp.Dispose()

    $response = @{
        status = "success"
        text   = $allText.Trim()
        lines  = $allLines
    }

    Write-Output ($response | ConvertTo-Json -Compress)
}
catch {
    $errResponse = @{
        status  = "error"
        message = $_.Exception.Message
    }
    Write-Output ($errResponse | ConvertTo-Json -Compress)
}
