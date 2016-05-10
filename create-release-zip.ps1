
mkdir woocommerce-barion
cd woocommerce-barion

git clone --quiet git@github.com:szelpe/woocommerce-barion.git .

git submodule --quiet init
git submodule --quiet update

rm -Force -Recurse .git
rm barion-library\.git
rm .gitignore
rm .gitmodules
rm Gruntfile.js
rm create-release-zip.ps1

cd ..

$env:Path += $env:Path + ";C:\Program Files\7-Zip"
7z a woocommerce-barion.zip woocommerce-barion

rm -Force -Recurse woocommerce-barion