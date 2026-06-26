#!/bin/sh

set -e

phpacker build --src=./missl.php

mv build/linux-x64/linux-x64 build/linux-x64/missl

exit 0
