$ServerInstance = "localhost\SQLEXPRESS"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$ContactsOutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\customer_contacts_export.csv"
$AddressesOutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\customer_addresses_export.csv"

$outputDir = Split-Path -Parent $ContactsOutputPath
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$contactsQuery = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(CustCode)) AS CustCode,
    ItemNo,
    LTRIM(RTRIM(ContactType)) AS ContactType,
    LTRIM(RTRIM(ContactName)) AS ContactName,
    LTRIM(RTRIM(ContactTitle)) AS ContactTitle,
    LTRIM(RTRIM(Type)) AS ContactCategory,
    LTRIM(RTRIM(Phone)) AS Phone,
    LTRIM(RTRIM(Handphone)) AS Handphone,
    LTRIM(RTRIM(Email)) AS Email,
    LTRIM(RTRIM(FgActive)) AS FgActive
FROM dbo.MsCustContact
WHERE LTRIM(RTRIM(ISNULL(CustCode, ''))) <> ''
ORDER BY CustCode, ItemNo;
"@

$addressQuery = @"
SET NOCOUNT ON;

WITH ranked AS (
    SELECT
        LTRIM(RTRIM(addr.CustCode)) AS CustCode,
        LTRIM(RTRIM(addr.DeliveryCode)) AS DeliveryCode,
        LTRIM(RTRIM(addr.DeliveryName)) AS DeliveryName,
        LTRIM(RTRIM(addr.DeliveryType)) AS DeliveryType,
        LTRIM(RTRIM(addr.DeliveryAddr1)) AS DeliveryAddr1,
        LTRIM(RTRIM(addr.DeliveryAddr2)) AS DeliveryAddr2,
        LTRIM(RTRIM(addr.City)) AS City,
        LTRIM(RTRIM(city.CityName)) AS CityName,
        LTRIM(RTRIM(addr.ZipCode)) AS ZipCode,
        LTRIM(RTRIM(addr.PhoneNo)) AS PhoneNo,
        LTRIM(RTRIM(addr.Fax)) AS Fax,
        LTRIM(RTRIM(addr.ContactPerson)) AS ContactPerson,
        LTRIM(RTRIM(addr.FgActive)) AS FgActive,
        ROW_NUMBER() OVER (
            PARTITION BY addr.CustCode
            ORDER BY
                CASE WHEN ISNULL(addr.FgActive, 'Y') = 'Y' THEN 0 ELSE 1 END,
                CASE
                    WHEN UPPER(LTRIM(RTRIM(ISNULL(addr.DeliveryType, '')))) LIKE '%INVOICE%' THEN 0
                    WHEN UPPER(LTRIM(RTRIM(ISNULL(addr.DeliveryName, '')))) LIKE '%INVOICE%' THEN 0
                    ELSE 1
                END,
                CASE WHEN LTRIM(RTRIM(ISNULL(addr.DeliveryCode, ''))) = '01' THEN 0 ELSE 1 END,
                addr.DeliveryCode
        ) AS rn
    FROM dbo.MsCustAddress addr
    LEFT JOIN dbo.MsCity city
        ON LTRIM(RTRIM(ISNULL(city.CityCode, ''))) = LTRIM(RTRIM(ISNULL(addr.City, '')))
    WHERE LTRIM(RTRIM(ISNULL(addr.CustCode, ''))) <> ''
)
SELECT
    CustCode,
    DeliveryCode,
    DeliveryName,
    DeliveryType,
    DeliveryAddr1,
    DeliveryAddr2,
    City,
    CityName,
    ZipCode,
    PhoneNo,
    Fax,
    ContactPerson,
    FgActive
FROM ranked
WHERE rn = 1
ORDER BY CustCode;
"@

$contacts = Invoke-Sqlcmd `
    -ServerInstance $ServerInstance `
    -Database $Database `
    -Username $Username `
    -Password $Password `
    -Query $contactsQuery

$addresses = Invoke-Sqlcmd `
    -ServerInstance $ServerInstance `
    -Database $Database `
    -Username $Username `
    -Password $Password `
    -Query $addressQuery

$contacts | Export-Csv -Path $ContactsOutputPath -NoTypeInformation -Encoding UTF8
$addresses | Export-Csv -Path $AddressesOutputPath -NoTypeInformation -Encoding UTF8

Write-Host "Contacts export selesai:" $ContactsOutputPath
Write-Host "Contacts rows:" ($contacts | Measure-Object | Select-Object -ExpandProperty Count)
Write-Host "Addresses export selesai:" $AddressesOutputPath
Write-Host "Addresses rows:" ($addresses | Measure-Object | Select-Object -ExpandProperty Count)
