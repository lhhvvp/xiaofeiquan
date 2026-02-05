param(
    [string]$LegacyRoot = (Join-Path $PSScriptRoot '..\..\xfq-miniapp'),
    [string]$OutDir = (Join-Path $PSScriptRoot '..')
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Resolve-FullPath([string]$path) {
    return (Resolve-Path -LiteralPath $path).Path
}

$LegacyRoot = Resolve-FullPath $LegacyRoot
$OutDir = Resolve-FullPath $OutDir

Write-Host "LegacyRoot: $LegacyRoot"
Write-Host "OutDir:     $OutDir"

# --- pages.json -> page list ---
$pagesJson = Join-Path $LegacyRoot 'pages.json'
if (-not (Test-Path -LiteralPath $pagesJson)) {
    throw "pages.json not found: $pagesJson"
}

$lines = Get-Content -LiteralPath $pagesJson
$lines = $lines | Where-Object { $_ -notmatch '^\s*//' }

$pagePaths = foreach ($line in $lines) {
    if ($line -match '"path"\s*:\s*"([^"]+)"') {
        $Matches[1]
    }
}

$pagePaths = $pagePaths | Where-Object { $_ } | Sort-Object -Unique

$pagesOutTxt = Join-Path $OutDir 'legacy-pages.txt'
$pagesOutJson = Join-Path $OutDir 'legacy-pages.json'

$pagePaths | Set-Content -LiteralPath $pagesOutTxt -Encoding UTF8
@{
    generatedAt = (Get-Date).ToString('s')
    count = $pagePaths.Count
    paths = $pagePaths
} | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $pagesOutJson -Encoding UTF8

Write-Host "Wrote: $pagesOutTxt ($($pagePaths.Count) pages)"
Write-Host "Wrote: $pagesOutJson"

# --- httpRequest endpoint scan ---
$endpointPattern = [regex]::new('httpRequest\s*\(\s*([''"`])([^''"`]+)\1')
$storagePattern = [regex]::new('(?:uni|wx)\.(get|set|remove)StorageSync\s*\(\s*([''"`])([^''"`]+)\2')
$wxApiPattern = [regex]::new('\bwx\.(\w+)\s*\(')
$uniApiPattern = [regex]::new('\buni\.(\w+)\s*\(')

$sourceFiles = Get-ChildItem -LiteralPath $LegacyRoot -Recurse -File -Include *.vue, *.js |
    Where-Object {
        $_.FullName -notmatch '\\node_modules\\' -and
        $_.FullName -notmatch '\\unpackage\\' -and
        $_.FullName -notmatch '\\\.hbuilderx\\'
    }

$endpointToFiles = @{}
$storageKeyToUsage = @{}
$wxApiToFiles = @{}
$uniApiToFiles = @{}

foreach ($file in $sourceFiles) {
    $content = Get-Content -LiteralPath $file.FullName -Raw
    $relativePath = $file.FullName.Substring($LegacyRoot.Length).TrimStart('\', '/')

    # endpoints: httpRequest('/path')
    $matches = $endpointPattern.Matches($content)
    foreach ($match in $matches) {
        $url = $match.Groups[2].Value.Trim()
        if (-not $url.StartsWith('/')) { continue }

        if (-not $endpointToFiles.ContainsKey($url)) {
            $endpointToFiles[$url] = New-Object 'System.Collections.Generic.HashSet[string]'
        }
        [void]$endpointToFiles[$url].Add($relativePath)
    }

    # storage keys: uni.getStorageSync('key') / uni.setStorageSync('key')
    $storageMatches = $storagePattern.Matches($content)
    foreach ($match in $storageMatches) {
        $op = $match.Groups[1].Value.Trim()
        $key = $match.Groups[3].Value.Trim()
        if (-not $key) { continue }

        if (-not $storageKeyToUsage.ContainsKey($key)) {
            $storageKeyToUsage[$key] = @{
                ops = New-Object 'System.Collections.Generic.HashSet[string]'
                files = New-Object 'System.Collections.Generic.HashSet[string]'
            }
        }

        [void]$storageKeyToUsage[$key].ops.Add($op)
        [void]$storageKeyToUsage[$key].files.Add($relativePath)
    }

    # wx API usage: wx.xxx(...)
    $wxMatches = $wxApiPattern.Matches($content)
    foreach ($match in $wxMatches) {
        $api = $match.Groups[1].Value.Trim()
        if (-not $api) { continue }
        if (-not $wxApiToFiles.ContainsKey($api)) {
            $wxApiToFiles[$api] = New-Object 'System.Collections.Generic.HashSet[string]'
        }
        [void]$wxApiToFiles[$api].Add($relativePath)
    }

    # uni API usage: uni.xxx(...)
    $uniMatches = $uniApiPattern.Matches($content)
    foreach ($match in $uniMatches) {
        $api = $match.Groups[1].Value.Trim()
        if (-not $api) { continue }
        if (-not $uniApiToFiles.ContainsKey($api)) {
            $uniApiToFiles[$api] = New-Object 'System.Collections.Generic.HashSet[string]'
        }
        [void]$uniApiToFiles[$api].Add($relativePath)
    }
}

$endpoints = $endpointToFiles.GetEnumerator() |
    ForEach-Object {
        [pscustomobject]@{
            url = $_.Key
            fileCount = $_.Value.Count
            files = ($_.Value | Sort-Object)
        }
    } | Sort-Object url

$endpointsOutTxt = Join-Path $OutDir 'legacy-endpoints.txt'
$endpointsOutJson = Join-Path $OutDir 'legacy-endpoints.json'

($endpoints | ForEach-Object { $_.url }) | Set-Content -LiteralPath $endpointsOutTxt -Encoding UTF8
@{
    generatedAt = (Get-Date).ToString('s')
    count = $endpoints.Count
    endpoints = $endpoints
} | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $endpointsOutJson -Encoding UTF8

Write-Host "Wrote: $endpointsOutTxt ($($endpoints.Count) endpoints)"
Write-Host "Wrote: $endpointsOutJson"

# --- storage keys ---
$storageKeys = $storageKeyToUsage.GetEnumerator() |
    ForEach-Object {
        [pscustomobject]@{
            key = $_.Key
            ops = ($_.Value.ops | Sort-Object)
            fileCount = $_.Value.files.Count
            files = ($_.Value.files | Sort-Object)
        }
    } | Sort-Object key

$storageOutTxt = Join-Path $OutDir 'legacy-storage-keys.txt'
$storageOutJson = Join-Path $OutDir 'legacy-storage-keys.json'

($storageKeys | ForEach-Object { $_.key }) | Set-Content -LiteralPath $storageOutTxt -Encoding UTF8
@{
    generatedAt = (Get-Date).ToString('s')
    count = $storageKeys.Count
    keys = $storageKeys
} | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $storageOutJson -Encoding UTF8

Write-Host "Wrote: $storageOutTxt ($($storageKeys.Count) keys)"
Write-Host "Wrote: $storageOutJson"

# --- wx APIs ---
$wxApis = $wxApiToFiles.GetEnumerator() |
    ForEach-Object {
        [pscustomobject]@{
            api = $_.Key
            fileCount = $_.Value.Count
            files = ($_.Value | Sort-Object)
        }
    } | Sort-Object api

$wxApisOutTxt = Join-Path $OutDir 'legacy-wx-apis.txt'
$wxApisOutJson = Join-Path $OutDir 'legacy-wx-apis.json'

($wxApis | ForEach-Object { $_.api }) | Set-Content -LiteralPath $wxApisOutTxt -Encoding UTF8
@{
    generatedAt = (Get-Date).ToString('s')
    count = $wxApis.Count
    apis = $wxApis
} | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $wxApisOutJson -Encoding UTF8

Write-Host "Wrote: $wxApisOutTxt ($($wxApis.Count) apis)"
Write-Host "Wrote: $wxApisOutJson"

# --- uni APIs ---
$uniApis = $uniApiToFiles.GetEnumerator() |
    ForEach-Object {
        [pscustomobject]@{
            api = $_.Key
            fileCount = $_.Value.Count
            files = ($_.Value | Sort-Object)
        }
    } | Sort-Object api

$uniApisOutTxt = Join-Path $OutDir 'legacy-uni-apis.txt'
$uniApisOutJson = Join-Path $OutDir 'legacy-uni-apis.json'

($uniApis | ForEach-Object { $_.api }) | Set-Content -LiteralPath $uniApisOutTxt -Encoding UTF8
@{
    generatedAt = (Get-Date).ToString('s')
    count = $uniApis.Count
    apis = $uniApis
} | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $uniApisOutJson -Encoding UTF8

Write-Host "Wrote: $uniApisOutTxt ($($uniApis.Count) apis)"
Write-Host "Wrote: $uniApisOutJson"
