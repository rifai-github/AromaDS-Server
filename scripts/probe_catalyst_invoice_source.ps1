$ErrorActionPreference = 'Stop'

$server = '127.0.0.1,1433'
$database = 'PinkAds'
$username = 'catalyst_import'
$password = 'CatalystImport#2026!'

$queries = @(
    @{
        Name = 'FINCustInvHd';
    },
    @{
        Name = 'FINCustInvDt';
    },
    @{
        Name = 'FINCustInvDP';
    },
    @{
        Name = 'FINDPCustHd';
    },
    @{
        Name = 'FINDPCustDt';
    }
)

foreach ($query in $queries) {
    Write-Host "=== $($query.Name) ==="
    Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query @"
SELECT COLUMN_NAME, DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = '$($query.Name)'
ORDER BY ORDINAL_POSITION;
"@ |
        Format-Table -AutoSize
    Write-Host

    Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query @"
SELECT TOP 5 *
FROM [$($query.Name)];
"@ |
        Format-Table -AutoSize
    Write-Host
}
