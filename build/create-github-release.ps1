param (
    [string]$newVersion
 )

$body = @{
    "tag_name" = "v$newVersion"
    "target_commitish" = "master"
    "name" = "v$newVersion"
    "body" = "v$newVersion"
    "draft" = $False
    "prerelease"= $False
}

$credential = Get-Credential

$Ptr = [System.Runtime.InteropServices.Marshal]::SecureStringToCoTaskMemUnicode($credential.Password)
$password = [System.Runtime.InteropServices.Marshal]::PtrToStringUni($Ptr)
[System.Runtime.InteropServices.Marshal]::ZeroFreeCoTaskMemUnicode($Ptr)

$pair = "$($credential.UserName):$($password)"
$encodedCreds = [System.Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$basicAuthValue = "Basic $encodedCreds"

$headers = @{
    Authorization = $basicAuthValue
}

$result = Invoke-RestMethod -Method Post -Uri "https://api.github.com/repos/szelpe/woocommerce-barion/releases" -Body (ConvertTo-Json $body) -Headers $headers

$zipFile = [System.IO.File]::ReadAllBytes("woocommerce-barion.zip")

$headers = @{
    Authorization = $basicAuthValue
    "Content-Type" = "application/zip"
}

$uploadUrl = $result.upload_url -replace "\{\?name,label}", "?name=woocommerce-barion.zip"
Invoke-RestMethod -Method Post -Uri $uploadUrl -Body $zipFile -Headers $headers
