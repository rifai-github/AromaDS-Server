$ServerInstance = "localhost\SQLEXPRESS"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$OutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\users_export.csv"

$outputDir = Split-Path -Parent $OutputPath
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$query = @"
SET NOCOUNT ON;

SELECT
    LTRIM(RTRIM(e.EmpNumb)) AS EmpNumb,
    LTRIM(RTRIM(e.EmpName)) AS EmpName,
    LTRIM(RTRIM(e.Branch)) AS EmployeeBranch,
    LTRIM(RTRIM(e.Department)) AS EmployeeDepartment,
    LTRIM(RTRIM(e.JobTitle)) AS JobTitle,
    LTRIM(RTRIM(jt.JobTtlName)) AS JobTitleName,
    LTRIM(RTRIM(e.Email)) AS Email,
    LTRIM(RTRIM(e.ResPhone)) AS ResPhone,
    LTRIM(RTRIM(e.HandPhone)) AS HandPhone,
    LTRIM(RTRIM(e.ResAddr)) AS ResAddr,
    LTRIM(RTRIM(e.OriAddr)) AS OriAddr,
    LTRIM(RTRIM(e.IDCard)) AS IDCard,
    LTRIM(RTRIM(e.NPWPNo)) AS NPWPNo,
    LTRIM(RTRIM(e.FgJamsosTek)) AS FgJamsosTek,
    e.JamSosTekDate AS JamSosTekDate,
    LTRIM(RTRIM(e.JamSosTekNo)) AS JamSosTekNo,
    LTRIM(RTRIM(e.FgActive)) AS EmployeeActive,
    e.HireDate AS HireDate,
    LTRIM(RTRIM(sa.UserId)) AS LoginUserId,
    LTRIM(RTRIM(sa.UserName)) AS UserName,
    LTRIM(RTRIM(sa.BranchCode)) AS LoginBranchCode,
    LTRIM(RTRIM(sa.FgActive)) AS LoginActive,
    ISNULL(branches.AssignedBranchCodes, '') AS AssignedBranchCodes,
    ISNULL(departments.AssignedDepartmentCodes, '') AS AssignedDepartmentCodes
FROM dbo.MsEmployee e
LEFT JOIN dbo.MsJobTitle jt
    ON jt.JobTtlCode = e.JobTitle
OUTER APPLY (
    SELECT TOP 1
        su.UserId,
        su.UserName,
        su.BranchCode,
        su.FgActive
    FROM dbo.SAUsers su
    WHERE LTRIM(RTRIM(ISNULL(su.EmpNumb, ''))) = LTRIM(RTRIM(ISNULL(e.EmpNumb, '')))
    ORDER BY
        CASE WHEN ISNULL(su.FgActive, 'Y') = 'Y' THEN 0 ELSE 1 END,
        su.CreateDate DESC,
        su.UserId ASC
) sa
OUTER APPLY (
    SELECT
        STUFF((
            SELECT '|' + x.BranchCode
            FROM (
                SELECT DISTINCT LTRIM(RTRIM(bu.BranchCode)) AS BranchCode
                FROM dbo.MsBranchUser bu
                WHERE bu.UserId = sa.UserId
                  AND LTRIM(RTRIM(ISNULL(bu.BranchCode, ''))) <> ''
                  AND ISNULL(bu.FgActive, 'Y') <> 'N'
            ) x
            ORDER BY x.BranchCode
            FOR XML PATH(''), TYPE
        ).value('.', 'nvarchar(max)'), 1, 1, '') AS AssignedBranchCodes
) branches
OUTER APPLY (
    SELECT
        STUFF((
            SELECT '|' + x.DepartmentCode
            FROM (
                SELECT DISTINCT LTRIM(RTRIM(du.Department)) AS DepartmentCode
                FROM dbo.MsDeptUser du
                WHERE du.UserId = sa.UserId
                  AND LTRIM(RTRIM(ISNULL(du.Department, ''))) <> ''
                  AND ISNULL(du.FgActive, 'Y') <> 'N'
            ) x
            ORDER BY x.DepartmentCode
            FOR XML PATH(''), TYPE
        ).value('.', 'nvarchar(max)'), 1, 1, '') AS AssignedDepartmentCodes
) departments
WHERE LTRIM(RTRIM(ISNULL(e.EmpNumb, ''))) <> ''
  AND LTRIM(RTRIM(ISNULL(e.EmpName, ''))) <> ''
ORDER BY e.EmpNumb;
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
