param (
    [string]$PartToIncrease
 )

$indexFile = [System.IO.Path]::Combine($pwd, 'index.php')
$readmeFile = [System.IO.Path]::Combine($pwd, 'readme.txt')

$indexContent = Get-Content $indexFile -Encoding UTF8

$i = -1;
$versionRow = 0;
$indexContent | ? { $i++; $_ -match 'Version: (\d+).(\d+).(\d+)' } | % { $versionRow = $i }
$major = [convert]::ToInt32($Matches[1])
$minor = [convert]::ToInt32($Matches[2])
$build = [convert]::ToInt32($Matches[3])

if($partToIncrease -eq 'major') {
    $major++
    $minor = 0;
    $build = 0;
}
elseif($partToIncrease -eq 'minor') {
    $minor++
    $build = 0;
}
else {
    $build++
}

$newVersion = "$major.$minor.$build";

$indexContent[$versionRow] = "Version: $newVersion"

$encoding = New-Object System.Text.UTF8Encoding($False)
[System.IO.File]::WriteAllLines($indexFile, $indexContent, $encoding)

$readMeContent = Get-Content $readmeFile -Encoding UTF8 -Raw
$readMeContent = $readMeContent -replace 'Stable tag: \d+.\d+.\d+', "Stable tag: $newVersion"
[System.IO.File]::WriteAllText($readmeFile, $readMeContent, $encoding)

git add index.php readme.txt
git commit -m "Version number increased: $newVersion"
git push

# Returning the new version as the result of the script
new-object psobject -Property @{ version = $newVersion }
