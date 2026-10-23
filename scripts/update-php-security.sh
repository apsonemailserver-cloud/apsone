#!/usr/bin/env bash
# PHP2Shell: CVE-2026-109618 and CVE-2026-109619.
# Sources: https://www.php.net/archive/2026.php
# https://ubuntu.com/blog/php2shell-vulnerability-fixes-available
set -euo pipefail

if [[ ! -r /etc/os-release ]]; then
    echo 'Script ini khusus VPS Ubuntu dengan PHP 8.3-FPM.' >&2
    exit 1
fi
. /etc/os-release
if [[ "$ID" != ubuntu ]]; then
    echo 'Script ini khusus Ubuntu.' >&2
    exit 1
fi

# Upgrade every installed PHP 8.3 package so CLI, FPM and extensions stay aligned.
mapfile -t php_packages < <(dpkg-query -W -f='${db:Status-Abbrev} ${binary:Package}\n' 'php8.3*' 2>/dev/null | awk '$1 == "ii" {print $2}')
if [[ ${#php_packages[@]} -eq 0 ]]; then
    echo 'PHP 8.3 belum terpasang; periksa runtime VPS sebelum melanjutkan.' >&2
    exit 1
fi
sudo apt-get update
sudo apt-get install --only-upgrade -y "${php_packages[@]}"

is_patched() {
    local package="$1" package_version upstream_version
    package_version=$(dpkg-query -W -f='${Version}' "$package")
    upstream_version=${package_version#*:}
    upstream_version=${upstream_version%%-*}

    if dpkg --compare-versions "$upstream_version" ge 8.3.36; then
        return 0
    fi
    # Ubuntu Noble backports the fix without changing the upstream version.
    if [[ "$VERSION_ID" == 24.04 && "$package_version" == *ubuntu0.24.04.* ]] &&
        dpkg --compare-versions "$package_version" ge '8.3.6-0ubuntu0.24.04.13'; then
        return 0
    fi
    echo "$package ($package_version) belum memenuhi patch PHP2Shell. Periksa repository APT." >&2
    return 1
}

# Restart even if the repository has not delivered a sufficient patch yet:
# workers must load any security updates that were available.
sudo /usr/sbin/php-fpm8.3 --test
sudo systemctl restart php8.3-fpm
sudo systemctl is-active --quiet php8.3-fpm
is_patched php8.3-common
is_patched php8.3-cli
is_patched php8.3-fpm
php8.3 -v
sudo /usr/sbin/php-fpm8.3 -v
echo 'PHP 8.3 CLI dan FPM sudah memenuhi versi patch PHP2Shell.'
