$ErrorActionPreference = 'Stop'

$ServerInstance = "127.0.0.1,1433"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$OutputDir = "C:\laragon\www\aroma2\storage\app\catalyst\contracts"

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$queries = @(
    @{
        Name = 'contract_headers_export.csv'
        Sql = @"
SET NOCOUNT ON;

SELECT *
FROM dbo.MKTContractHd
WHERE LTRIM(RTRIM(ISNULL(TransNmbr, ''))) <> '';
"@
    },
    @{
        Name = 'contract_details_export.csv'
        Sql = @"
SET NOCOUNT ON;

SELECT *
FROM dbo.MKTContractDt
WHERE LTRIM(RTRIM(ISNULL(TransNmbr, ''))) <> '';
"@
    },
    @{
        Name = 'billing_groups_lookup_export.csv'
        Sql = @"
SET NOCOUNT ON;

SELECT *
FROM dbo.MsBillingGroup
WHERE LTRIM(RTRIM(ISNULL(BillingCode, ''))) <> '';
"@
    }
)

foreach ($query in $queries) {
    $outputPath = Join-Path $OutputDir $query.Name

    $rows = Invoke-Sqlcmd `
        -ServerInstance $ServerInstance `
        -Database $Database `
        -Username $Username `
        -Password $Password `
        -Query $query.Sql

    $rows | Export-Csv -Path $outputPath -NoTypeInformation -Encoding UTF8

    Write-Host "Export selesai:" $outputPath
    Write-Host "Rows:" ($rows | Measure-Object | Select-Object -ExpandProperty Count)
    Write-Host
}
