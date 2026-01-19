#!/bin/bash
set -ex

echo '1. Fixing TelescopeServiceProvider...'
sed -i " 1s/<?php/<?php\\n\\nuse Illuminate\\\\Support\\\\Facades\\\\Gate
