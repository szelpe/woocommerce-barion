param (
    [string]$version
)

svn checkout https://plugins.svn.wordpress.org/pay-via-barion-for-woocommerce/

rm -Recurse -Force pay-via-barion-for-woocommerce\trunk\*
cp -Recurse woocommerce-barion\* pay-via-barion-for-woocommerce\trunk\

cd pay-via-barion-for-woocommerce

svn add .\trunk\* --force
svn ci -m "Added version $version"

svn cp trunk tags/$version
svn ci -m "tagging version $version"

cd ..
