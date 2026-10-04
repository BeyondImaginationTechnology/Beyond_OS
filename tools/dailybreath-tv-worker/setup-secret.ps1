param([Parameter(Mandatory=$true)][string]$Token,[string]$BaseUrl='https://beyondimagination.co.technology')
$secure=ConvertTo-SecureString $Token -AsPlainText -Force
[pscustomobject]@{BaseUrl=$BaseUrl;Token=$secure}|Export-Clixml (Join-Path $PSScriptRoot 'worker-secret.xml')