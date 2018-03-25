param (
    [string]$type
)

$ErrorActionPreference = "Stop"
$result = & $PSScriptRoot"\version-bump.ps1" $type
& $PSScriptRoot"\create-release-zip.ps1"
& $PSScriptRoot"\create-github-release.ps1" $result.version
& $PSScriptRoot"\wordpress.org-helper.ps1" $result.version
& $PSScriptRoot"\clean-up.ps1"
