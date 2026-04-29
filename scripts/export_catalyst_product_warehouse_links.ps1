$ServerInstance = "localhost\SQLEXPRESS"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$OutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\product_warehouse_links.csv"

$outputDir = Split-Path -Parent $OutputPath
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$query = @"
SET NOCOUNT ON;
SELECT
    LTRIM(RTRIM(ProductCode)) AS ProductCode,
    LTRIM(RTRIM(Warehouse)) AS Warehouse
FROM dbo.MsProduct
WHERE Warehouse IS NOT NULL
  AND LTRIM(RTRIM(Warehouse)) <> ''
ORDER BY ProductCode;
"@

$rows = Invoke-Sqlcmd `
    -ServerInstance $ServerInstance `
    -Database $Database `
    -Username $Username `
    -Password $Password `
    -Query $query

$rows | Export-Csv -Path $OutputPath -NoTypeInformation -Encoding UTF8

Write-Host "Export selesai:" $OutputPath
Write-Host "Rows:" ($rows | Measure-Object | Select-Object -ExpandProperty Count)
