#!/bin/bash
# Add namespace to all middleware files without one
for f in app/Http/Middleware/*.php; do
    if ! grep -q 'namespace' \; then
        sed -i '1s/^<?php/<?php\
\
namespace App\\Http\\Middleware;\
/' \
        filename=\
        echo " Fixed: \\
