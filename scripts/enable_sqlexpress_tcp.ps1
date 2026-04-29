$instanceKey = 'HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\MSSQL16.SQLEXPRESS\MSSQLServer\SuperSocketNetLib'
$tcpRoot = Join-Path $instanceKey 'Tcp'
$ipAll = Join-Path $tcpRoot 'IPAll'
$loopback = Join-Path $tcpRoot 'IP12'
$serviceName = 'MSSQL$SQLEXPRESS'

Write-Host 'Enabling TCP/IP for SQLEXPRESS...' -ForegroundColor Cyan

Set-ItemProperty -Path $instanceKey -Name ForceEncryption -Value 0
Set-ItemProperty -Path $instanceKey -Name ForceStrict -Value 0
Set-ItemProperty -Path $instanceKey -Name ExtendedProtection -Value 0
Set-ItemProperty -Path $tcpRoot -Name Enabled -Value 1
Set-ItemProperty -Path $tcpRoot -Name ListenOnAllIPs -Value 1
Set-ItemProperty -Path $loopback -Name Enabled -Value 1
Set-ItemProperty -Path $ipAll -Name TcpDynamicPorts -Value ''
Set-ItemProperty -Path $ipAll -Name TcpPort -Value '1433'

Write-Host 'Restarting SQL Server service...' -ForegroundColor Cyan
Restart-Service -Name $serviceName -Force
Start-Sleep -Seconds 5

Write-Host 'Verifying registry values...' -ForegroundColor Cyan
Get-ItemProperty $instanceKey | Select-Object ForceEncryption, ForceStrict, ExtendedProtection
Get-ItemProperty $tcpRoot | Select-Object Enabled, ListenOnAllIPs
Get-ItemProperty $ipAll | Select-Object TcpDynamicPorts, TcpPort
Get-Service -Name $serviceName | Select-Object Name, Status

Write-Host ''
Write-Host 'Next steps:' -ForegroundColor Green
Write-Host '1. php artisan catalyst:import-masters --step=product_categories'
Write-Host '2. php artisan catalyst:import-masters'
Write-Host '3. php artisan catalyst:import-masters --apply'
