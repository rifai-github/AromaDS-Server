$ServerInstance = "localhost\SQLEXPRESS"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$OutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\rental_materials.csv"

$outputDir = Split-Path -Parent $OutputPath
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$query = @"
SET NOCOUNT ON;
SELECT
    LTRIM(RTRIM(ProductRental)) AS ProductRental,
    UPPER(LTRIM(RTRIM(MaterialType))) AS MaterialType,
    LTRIM(RTRIM(Material)) AS Material
FROM dbo.MsRentalBOMDt
WHERE LTRIM(RTRIM(ISNULL(ProductRental, ''))) <> ''
  AND LTRIM(RTRIM(ISNULL(MaterialType, ''))) <> ''
  AND LTRIM(RTRIM(ISNULL(Material, ''))) <> ''
  AND ISNULL(FgActive, 'Y') <> 'N'
ORDER BY ProductRental, MaterialType, Material;
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
