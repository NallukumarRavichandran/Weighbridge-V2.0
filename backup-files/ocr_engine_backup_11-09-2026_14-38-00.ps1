param (
    [Parameter(Mandatory = $true)]
    [string]$ImagePath
)

try {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
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

    # Pass 1: 2.0x Bicubic Zoom on Full Resolution Frame (Optimal for 9-Meter Distant Plates)
    # Zooms the frame before text extraction so all 4 registration digits and series letters are large and legible
    $zoomW = [int]($srcBmp.Width * 2.0)
    $zoomH = [int]($srcBmp.Height * 2.0)
    $zoomedBmp = New-Object System.Drawing.Bitmap($zoomW, $zoomH)
    $g = [System.Drawing.Graphics]::FromImage($zoomedBmp)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.DrawImage($srcBmp, 0, 0, $zoomW, $zoomH)
    $g.Dispose()

    $resZoom = Run-OcrOnBmp $zoomedBmp
    if ($resZoom -and $resZoom.Text) {
        $allText += $resZoom.Text + " "
        foreach ($l in $resZoom.Lines) { 
            if ($l.Text) { $allLines += $l.Text }
        }
    }
    $zoomedBmp.Dispose()

    # Pass 2: Native Full Frame (Fallback for closer range vehicles)
    $resNative = Run-OcrOnBmp $srcBmp
    if ($resNative -and $resNative.Text) {
        $allText += $resNative.Text + " "
        foreach ($l in $resNative.Lines) { 
            if ($l.Text) { $allLines += $l.Text }
        }
    }

    $srcBmp.Dispose()

    # Normalize to clean printable ASCII (removes invalid UTF-8 bytes / control chars / replacement glyphs)
    $cleanLines = @()
    foreach ($l in $allLines) {
        $clean = [System.Text.RegularExpressions.Regex]::Replace($l, "[^\x20-\x7E]", " ").Trim()
        if ($clean.Length -gt 0) { $cleanLines += $clean }
    }
    $cleanText = [System.Text.RegularExpressions.Regex]::Replace($allText, "[^\x20-\x7E]", " ").Trim()

    $response = @{
        status = "success"
        text   = $cleanText
        lines  = $cleanLines
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
