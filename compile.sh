#!/bin/sh

set -e

phpacker build --src=./missl.php

mv build/linux/linux-x64 build/linux/missl

exit 0
