param (
    [string]$type
)

$ErrorActionPreference = "Stop"
$result = & $PSScriptRoot"\version-bump.ps1" $type
Write-Output 'New version:' $result.version

$decision = $Host.UI.PromptForChoice('', 'Are you sure you want to proceed?', ('&Yes', '&No'), 1)
if ($decision -eq 1) {
    exit
}

& $PSScriptRoot"\create-release-zip.ps1"
& $PSScriptRoot"\create-github-release.ps1" $result.version
& $PSScriptRoot"\wordpress.org-helper.ps1" $result.version
& $PSScriptRoot"\clean-up.ps1"
