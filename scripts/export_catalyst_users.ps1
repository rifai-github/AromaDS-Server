$ServerInstance = "localhost\SQLEXPRESS"
$Database = "PinkAds"
$Username = "catalyst_import"
$Password = "CatalystImport#2026!"
$OutputPath = "C:\laragon\www\aroma2\storage\app\catalyst\users_export.csv"

$outputDir = Split-Path -Parent $OutputPath
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

# UAT 19 Nov 2026: data pribadi employee (gender, agama, status nikah, golongan darah, status
# karyawan, ...) dari MsEmployee / MsEmployeeDt / MsEmpStatus. Nama kolomnya belum pernah
# dikonfirmasi, jadi kolom dicari dulu di INFORMATION_SCHEMA dan hanya yang benar-benar ada yang
# ikut di-SELECT - query tidak akan gagal karena "Invalid column name".
# Daftar kandidat = CatalystEmployeeProfileMapper::SOURCE_COLUMNS (ubah keduanya bersamaan).
$profileCandidates = @(
    'Sex', 'Gender', 'JenisKelamin', 'EmpSex',
    'MaritalStatus', 'Marital', 'MaritalStat', 'StatusMarital', 'StatusNikah',
    'Religion', 'Agama',
    'IDType', 'IDCardType', 'IdentityType',
    'BloodType', 'Blood', 'BloodGroup', 'GolDarah',
    'Rhesus', 'BloodRhesus', 'RhesusType',
    'EmpStatus', 'EmployeeStatus', 'StatusEmp', 'EmpStatusCode'
)

function Get-CatalystColumns([string]$Table) {
    try {
        $result = Invoke-Sqlcmd `
            -ServerInstance $ServerInstance `
            -Database $Database `
            -Username $Username `
            -Password $Password `
            -ErrorAction Stop `
            -Query "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = '$Table'"
        return @($result | ForEach-Object { [string]$_.COLUMN_NAME })
    } catch {
        Write-Warning "Kolom $Table tidak bisa dibaca: $($_.Exception.Message)"
        return @()
    }
}

function Find-Column([string[]]$Columns, [string[]]$Candidates) {
    foreach ($candidate in $Candidates) {
        $match = $Columns | Where-Object { $_ -ieq $candidate } | Select-Object -First 1
        if ($match) { return $match }
    }
    return $null
}

$employeeColumns = Get-CatalystColumns 'MsEmployee'
$employeeDtColumns = Get-CatalystColumns 'MsEmployeeDt'
$empStatusColumns = Get-CatalystColumns 'MsEmpStatus'

$profileSelect = ''
$profileApply = ''
$selectedProfile = @{}

foreach ($candidate in $profileCandidates) {
    $column = Find-Column $employeeColumns @($candidate)
    if ($column) {
        $profileSelect += ",`n    LTRIM(RTRIM(CAST(e.[$column] AS nvarchar(100)))) AS [$candidate]"
        $selectedProfile[$candidate.ToLower()] = $true
    }
}

$dtKey = Find-Column $employeeDtColumns @('EmpNumb', 'EmpNo', 'EmployeeNo')
if ($dtKey) {
    $dtSelect = @()
    foreach ($candidate in $profileCandidates) {
        if ($selectedProfile.ContainsKey($candidate.ToLower())) { continue }
        $column = Find-Column $employeeDtColumns @($candidate)
        if ($column) {
            $dtSelect += "LTRIM(RTRIM(CAST(d.[$column] AS nvarchar(100)))) AS [$candidate]"
            $profileSelect += ",`n    dt.[$candidate]"
            $selectedProfile[$candidate.ToLower()] = $true
        }
    }
    if ($dtSelect.Count -gt 0) {
        $profileApply += "`nOUTER APPLY (`n    SELECT TOP 1 $($dtSelect -join ', ')`n    FROM dbo.MsEmployeeDt d`n    WHERE LTRIM(RTRIM(ISNULL(d.[$dtKey], ''))) = LTRIM(RTRIM(ISNULL(e.EmpNumb, '')))`n) dt"
    }
}

# Nama status karyawan dari MsEmpStatus (kalau kolom kode & namanya bisa ditemukan).
$empStatusSourceColumn = Find-Column $employeeColumns @('EmpStatus', 'EmployeeStatus', 'StatusEmp', 'EmpStatusCode')
$empStatusCode = Find-Column $empStatusColumns @('EmpStatus', 'EmpStatusCode', 'StatusCode', 'Code')
$empStatusName = Find-Column $empStatusColumns @('EmpStatusName', 'StatusName', 'EmpStatusDesc', 'Description', 'Name')
if ($empStatusSourceColumn -and $empStatusCode -and $empStatusName) {
    $profileSelect += ",`n    LTRIM(RTRIM(CAST(es.[$empStatusName] AS nvarchar(100)))) AS EmpStatusName"
    $profileApply += "`nOUTER APPLY (`n    SELECT TOP 1 s.[$empStatusName]`n    FROM dbo.MsEmpStatus s`n    WHERE LTRIM(RTRIM(ISNULL(s.[$empStatusCode], ''))) = LTRIM(RTRIM(ISNULL(e.[$empStatusSourceColumn], '')))`n) es"
}

Write-Host "Kolom profil employee yang ikut diexport:" (($selectedProfile.Keys | Sort-Object) -join ', ')

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
    ISNULL(departments.AssignedDepartmentCodes, '') AS AssignedDepartmentCodes$profileSelect
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
) departments$profileApply
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
