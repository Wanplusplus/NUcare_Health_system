$ErrorActionPreference = 'Stop'

$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'

& $mysql -u root -e "DROP DATABASE IF EXISTS nucaredb"
& $mysql -u root -e "CREATE DATABASE nucaredb"
Get-Content -Raw 'sql\nucaredb.sql' | & $mysql -u root nucaredb
Get-Content -Raw 'sql\rbac_seed_and_schema.sql' | & $mysql -u root nucaredb
$count = & $mysql -u root -N -B nucaredb -e "SELECT COUNT(*) FROM school_people WHERE SchoolID IN ('SCH-8001','SCH-8002','SCH-8003');"
Set-Content -Path 'temp\verify_count.txt' -Value $count
Write-Output "VERIFY_COUNT=$count"
