$result = .\version-bump.ps1
.\create-release-zip.ps1 
.\create-github-release.ps1 $result.version
.\wordpress.org-helper.ps1 $result.version
.\clean-up.ps1