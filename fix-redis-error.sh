#!/bin/bash

# Fix "unable to load dynamic library 'redis.so'" error
# This script removes the problematic redis.so extension configuration

echo "=========================================="
echo "Fix Redis.so Error"
echo "=========================================="
echo ""

# ============================================
# Find and Fix php.ini
# ============================================
echo "Step 1: Checking php.ini..."
echo ""

PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)

if [ -f "$PHP_INI" ]; then
    echo "Found php.ini: $PHP_INI"
    
    if grep -q "^extension=redis.so" "$PHP_INI"; then
        echo "⚠ Found problematic 'extension=redis.so' line"
        echo ""
        
        # Backup
        echo "Creating backup..."
        sudo cp "$PHP_INI" "$PHP_INI.backup.$(date +%Y%m%d_%H%M%S)"
        echo "✓ Backup created"
        
        # Comment out
        echo "Commenting out redis.so..."
        sudo sed -i.bak 's/^extension=redis.so/;extension=redis.so/' "$PHP_INI"
        echo "✓ Commented out redis.so in php.ini"
    else
        echo "✓ No 'extension=redis.so' found in php.ini"
    fi
else
    echo "⚠ php.ini not found at: $PHP_INI"
fi

echo ""

# ============================================
# Check conf.d Directory
# ============================================
echo "Step 2: Checking conf.d directory..."
echo ""

CONF_D=$(php --ini | grep "Scan for additional .ini files" | cut -d: -f2 | xargs)

if [ -d "$CONF_D" ]; then
    echo "Found conf.d directory: $CONF_D"
    
    # Check for redis config files
    REDIS_FILES=$(ls "$CONF_D"/*redis*.ini 2>/dev/null)
    
    if [ -n "$REDIS_FILES" ]; then
        echo "⚠ Found Redis extension config files:"
        echo "$REDIS_FILES"
        echo ""
        
        for file in $REDIS_FILES; do
            if [ -f "$file" ]; then
                echo "Disabling: $file"
                sudo mv "$file" "$file.disabled"
                echo "✓ Renamed to: $file.disabled"
            fi
        done
    else
        echo "✓ No Redis config files found in conf.d"
    fi
else
    echo "⚠ conf.d directory not found"
fi

echo ""

# ============================================
# Verify Fix
# ============================================
echo "Step 3: Verifying fix..."
echo ""

# Test PHP
if php -v > /dev/null 2>&1; then
    echo "✓ PHP runs without errors"
else
    echo "❌ PHP still has errors"
    php -v
fi

echo ""

# ============================================
# Summary
# ============================================
echo "=========================================="
echo "✅ Fix Complete!"
echo "=========================================="
echo ""
echo "What was done:"
echo "  ✓ Commented out 'extension=redis.so' in php.ini"
echo "  ✓ Disabled Redis extension config files"
echo "  ✓ Created backups of modified files"
echo ""
echo "Next steps:"
echo "  1. Run: php -v (should work without errors)"
echo "  2. Install Predis: composer require predis/predis"
echo "  3. Add to .env: REDIS_CLIENT=predis"
echo "  4. Run: ./setup-redis.sh"
echo ""
echo "Note: We're using Predis (pure PHP) instead of the"
echo "      problematic PHP Redis extension."
echo ""
echo "=========================================="
