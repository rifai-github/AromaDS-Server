$ErrorActionPreference = 'Stop'

$server = '127.0.0.1,1433'
$database = 'PinkAds'
$username = 'catalyst_import'
$password = 'CatalystImport#2026!'

$queries = @(
    @{ Name = 'MsToP'; Sql = "SELECT TOP 10 * FROM MsToP;" },
    @{ Name = 'MsCustBillto'; Sql = "SELECT TOP 10 * FROM MsCustBillto;" },
    @{ Name = 'MsCustCollect'; Sql = "SELECT TOP 10 * FROM MsCustCollect;" },
    @{ Name = 'MsPayType'; Sql = "SELECT TOP 10 * FROM MsPayType;" }
)

foreach ($query in $queries) {
    Write-Host "=== $($query.Name) columns ==="
    Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query @"
SELECT COLUMN_NAME, DATA_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = '$($query.Name)'
ORDER BY ORDINAL_POSITION;
"@ | Format-Table -AutoSize
    Write-Host

    Write-Host "=== $($query.Name) sample ==="
    Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query $query.Sql |
        Format-Table -AutoSize
    Write-Host
}

Write-Host "=== PaymentTo coverage ==="
Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query @"
SELECT
  SUM(CASE WHEN ISNULL(NULLIF(LTRIM(RTRIM(c.PaymentTo)),''),'') <> '' THEN 1 ELSE 0 END) AS direct_paymentto,
  SUM(CASE WHEN ISNULL(NULLIF(LTRIM(RTRIM(c.PaymentTo)),''),'') = '' THEN 1 ELSE 0 END) AS missing_direct_paymentto,
  COUNT(*) AS total_customers
FROM MsCustomer c;
"@ | Format-Table -AutoSize
Write-Host

Write-Host "=== Fallback via MsCustBillto ==="
Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query @"
SELECT COUNT(*) AS fallback_via_billto
FROM MsCustomer c
JOIN MsCustBillto b ON b.CustCode = c.CustCode
JOIN MsCustomer bc ON bc.CustCode = b.CustCollect
WHERE ISNULL(NULLIF(LTRIM(RTRIM(c.PaymentTo)),''),'') = ''
  AND ISNULL(NULLIF(LTRIM(RTRIM(bc.PaymentTo)),''),'') <> '';
"@ | Format-Table -AutoSize
Write-Host

Write-Host "=== Sample fallback via MsCustBillto ==="
Invoke-Sqlcmd -ServerInstance $server -Database $database -Username $username -Password $password -Query @"
SELECT TOP 20
  c.CustCode,
  c.CustName,
  c.PaymentTo AS DirectPaymentTo,
  b.CustCollect AS BillToCustomer,
  bc.PaymentTo AS BillToPaymentTo
FROM MsCustomer c
JOIN MsCustBillto b ON b.CustCode = c.CustCode
JOIN MsCustomer bc ON bc.CustCode = b.CustCollect
WHERE ISNULL(NULLIF(LTRIM(RTRIM(c.PaymentTo)),''),'') = ''
  AND ISNULL(NULLIF(LTRIM(RTRIM(bc.PaymentTo)),''),'') <> ''
ORDER BY c.CustCode;
"@ | Format-Table -AutoSize
Write-Host
