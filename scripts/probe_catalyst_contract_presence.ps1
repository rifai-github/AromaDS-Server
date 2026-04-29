$ErrorActionPreference = 'Stop'

$ServerInstance = "127.0.0.1,1433"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$ContractNo = "JKT-AG/24-09/0034"

$queries = @(
    @{
        Name = 'MKTContractHd'
        Sql = @"
SELECT TOP 10 *
FROM dbo.MKTContractHd
WHERE LTRIM(RTRIM(TransNmbr)) = '$ContractNo';
"@
    },
    @{
        Name = 'MKTContractDt'
        Sql = @"
SELECT TOP 10 *
FROM dbo.MKTContractDt
WHERE LTRIM(RTRIM(TransNmbr)) = '$ContractNo';
"@
    },
    @{
        Name = 'MKTQuotationHd via SoContractNo'
        Sql = @"
SELECT TOP 10 *
FROM dbo.MKTQuotationHd
WHERE LTRIM(RTRIM(SoContractNo)) = '$ContractNo';
"@
    }
)

foreach ($query in $queries) {
    Write-Host "=== $($query.Name) ==="
    Invoke-Sqlcmd -ServerInstance $ServerInstance -Database $Database -Username $Username -Password $Password -Query $query.Sql |
        Format-Table -AutoSize
    Write-Host
}
