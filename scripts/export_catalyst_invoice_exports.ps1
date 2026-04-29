$ErrorActionPreference = 'Stop'

$ServerInstance = "127.0.0.1,1433"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$OutputDir = "C:\laragon\www\aroma2\storage\app\catalyst\invoices"

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$queries = @(
    @{
        Name = 'invoice_headers_export.csv'
        Sql = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(TransNmbr)) AS TransNmbr,
    TransDate,
    LTRIM(RTRIM(Status)) AS Status,
    LTRIM(RTRIM(Branch)) AS Branch,
    LTRIM(RTRIM(Customer)) AS Customer,
    LTRIM(RTRIM(BillTo)) AS BillTo,
    LTRIM(RTRIM(Attn)) AS Attn,
    LTRIM(RTRIM(Term)) AS Term,
    DueDate,
    LTRIM(RTRIM(BankReceipt)) AS BankReceipt,
    LTRIM(RTRIM(FgPriceIncludeTax)) AS FgPriceIncludeTax,
    LTRIM(RTRIM(PPnNo)) AS PPnNo,
    PPnDate,
    PPnRate,
    LTRIM(RTRIM(Currency)) AS Currency,
    ForexRate,
    BaseForex,
    DiscForex,
    CNDNForex,
    PPn,
    PPnForex,
    PPhForex,
    TotalForex,
    DPBaseForex,
    LTRIM(RTRIM(Remark)) AS Remark,
    LTRIM(RTRIM(UserPrep)) AS UserPrep,
    DatePrep,
    LTRIM(RTRIM(UserAppr)) AS UserAppr,
    DateAppr,
    LTRIM(RTRIM(FgPPn)) AS FgPPn,
    Disc,
    PPH,
    LTRIM(RTRIM(CustTaxNPWP)) AS CustTaxNPWP,
    LTRIM(RTRIM(CustTaxAddress)) AS CustTaxAddress,
    LTRIM(RTRIM(Type)) AS Type,
    LTRIM(RTRIM(VirtualAccount)) AS VirtualAccount,
    LTRIM(RTRIM(BillingGroup)) AS BillingGroup,
    LTRIM(RTRIM(TypePpn)) AS TypePpn
FROM dbo.FINCustInvHd
WHERE LTRIM(RTRIM(ISNULL(TransNmbr, ''))) <> '';
"@
    },
    @{
        Name = 'invoice_details_export.csv'
        Sql = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(TransNmbr)) AS TransNmbr,
    LTRIM(RTRIM(ReffType)) AS ReffType,
    LTRIM(RTRIM(ReffNmbr)) AS ReffNmbr,
    LTRIM(RTRIM(Product)) AS Product,
    LTRIM(RTRIM(SONo)) AS SONo,
    Qty,
    LTRIM(RTRIM(Unit)) AS Unit,
    PriceForex,
    AmountForex,
    Disc,
    DiscForex,
    NettoForex,
    LTRIM(RTRIM(Remark)) AS Remark,
    LTRIM(RTRIM(BASNo)) AS BASNo,
    PeriodInvoice,
    LTRIM(RTRIM(CSRNo)) AS CSRNo,
    ReffDate,
    QtyFree,
    BASDate,
    LTRIM(RTRIM(CustPONo)) AS CustPONo,
    LTRIM(RTRIM(Building)) AS Building,
    LTRIM(RTRIM(BillingGroup)) AS BillingGroup,
    LTRIM(RTRIM(Floor)) AS Floor,
    LTRIM(RTRIM(Room)) AS Room,
    LTRIM(RTRIM(Location)) AS Location
FROM dbo.FINCustInvDt
WHERE LTRIM(RTRIM(ISNULL(TransNmbr, ''))) <> '';
"@
    },
    @{
        Name = 'invoice_dp_export.csv'
        Sql = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(TransNmbr)) AS TransNmbr,
    LTRIM(RTRIM(DPNo)) AS DPNo,
    LTRIM(RTRIM(Currency)) AS Currency,
    ForexRate,
    PPn,
    PPnRate,
    BaseDPForex,
    BasePaidForex,
    BaseToPaidForex,
    PPnDPForex,
    PPnPaidForex,
    PPnToPaidForex,
    LTRIM(RTRIM(Remark)) AS Remark,
    LTRIM(RTRIM(SOContractNo)) AS SOContractNo
FROM dbo.FINCustInvDP
WHERE LTRIM(RTRIM(ISNULL(TransNmbr, ''))) <> '';
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
