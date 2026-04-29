$ServerInstance = "localhost\SQLEXPRESS"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$PayTypesOutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\payment_types_export.csv"
$CustomerPaymentOutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\customer_paymentto_export.csv"

$outputDir = Split-Path -Parent $PayTypesOutputPath
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$payTypesQuery = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(PayCode)) AS PayCode,
    LTRIM(RTRIM(PayName)) AS PayName,
    LTRIM(RTRIM(Bank)) AS Bank,
    LTRIM(RTRIM(NoRekening)) AS NoRekening,
    LTRIM(RTRIM(NamaRekening)) AS NamaRekening,
    LTRIM(RTRIM(BankBranch)) AS BankBranch,
    LTRIM(RTRIM(BankAddr)) AS BankAddr,
    LTRIM(RTRIM(BankPhone)) AS BankPhone,
    LTRIM(RTRIM(BankFax)) AS BankFax,
    LTRIM(RTRIM(FgActive)) AS FgActive
FROM dbo.MsPayType
WHERE LTRIM(RTRIM(ISNULL(PayCode, ''))) <> ''
ORDER BY PayCode;
"@

$customerPaymentQuery = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(c.CustCode)) AS CustCode,
    LTRIM(RTRIM(c.PaymentTo)) AS PaymentTo,
    LTRIM(RTRIM(ISNULL(b.BillToCustomer, ''))) AS BillToCustomer,
    LTRIM(RTRIM(ISNULL(b.BillToPaymentTo, ''))) AS BillToPaymentTo,
    CASE
        WHEN LTRIM(RTRIM(ISNULL(c.PaymentTo, ''))) <> '' THEN LTRIM(RTRIM(c.PaymentTo))
        WHEN LTRIM(RTRIM(ISNULL(b.BillToPaymentTo, ''))) <> '' THEN LTRIM(RTRIM(b.BillToPaymentTo))
        ELSE ''
    END AS ResolvedPaymentTo,
    CASE
        WHEN LTRIM(RTRIM(ISNULL(c.PaymentTo, ''))) <> '' THEN 'direct'
        WHEN LTRIM(RTRIM(ISNULL(b.BillToPaymentTo, ''))) <> '' THEN 'billto'
        ELSE ''
    END AS PaymentSource
FROM dbo.MsCustomer c
OUTER APPLY (
    SELECT TOP 1
        LTRIM(RTRIM(b.CustCollect)) AS BillToCustomer,
        LTRIM(RTRIM(bc.PaymentTo)) AS BillToPaymentTo
    FROM dbo.MsCustBillto b
    LEFT JOIN dbo.MsCustomer bc ON bc.CustCode = b.CustCollect
    WHERE b.CustCode = c.CustCode
      AND LTRIM(RTRIM(ISNULL(b.CustCollect, ''))) <> ''
    ORDER BY
        CASE WHEN b.CustCollect = c.CustCode THEN 0 ELSE 1 END,
        b.UserDate DESC,
        b.CustCollect
) b
WHERE LTRIM(RTRIM(ISNULL(c.CustCode, ''))) <> ''
ORDER BY c.CustCode;
"@

$payTypes = Invoke-Sqlcmd `
    -ServerInstance $ServerInstance `
    -Database $Database `
    -Username $Username `
    -Password $Password `
    -Query $payTypesQuery

$customerPayments = Invoke-Sqlcmd `
    -ServerInstance $ServerInstance `
    -Database $Database `
    -Username $Username `
    -Password $Password `
    -Query $customerPaymentQuery

$payTypes | Export-Csv -Path $PayTypesOutputPath -NoTypeInformation -Encoding UTF8
$customerPayments | Export-Csv -Path $CustomerPaymentOutputPath -NoTypeInformation -Encoding UTF8

Write-Host "Payment types export selesai:" $PayTypesOutputPath
Write-Host "Payment types rows:" ($payTypes | Measure-Object | Select-Object -ExpandProperty Count)
Write-Host "Customer payment export selesai:" $CustomerPaymentOutputPath
Write-Host "Customer payment rows:" ($customerPayments | Measure-Object | Select-Object -ExpandProperty Count)
