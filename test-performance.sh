#!/bin/bash

# Performance Testing Script
# Tests cache performance for the POS system

echo "=========================================="
echo "Performance Test - XS Cosmetic POS"
echo "=========================================="
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found."
    exit 1
fi

echo "Test 1: Cache Performance Test"
echo "----------------------------------------"
echo ""

# Clear cache first
echo "Clearing cache..."
php artisan cache:clear-products > /dev/null 2>&1
echo "✓ Cache cleared"
echo ""

# Test 1: First load (cache miss)
echo "Test 1a: Loading products (CACHE MISS - from database)"
START_TIME=$(date +%s.%N)
php artisan tinker --execute="app(App\Services\ProductCacheService::class)->getProducts()->count();" > /dev/null 2>&1
END_TIME=$(date +%s.%N)
FIRST_LOAD=$(echo "$END_TIME - $START_TIME" | bc)
echo "   Time: ${FIRST_LOAD} seconds"
echo ""

# Test 2: Second load (cache hit)
echo "Test 1b: Loading products (CACHE HIT - from cache)"
START_TIME=$(date +%s.%N)
php artisan tinker --execute="app(App\Services\ProductCacheService::class)->getProducts()->count();" > /dev/null 2>&1
END_TIME=$(date +%s.%N)
SECOND_LOAD=$(echo "$END_TIME - $START_TIME" | bc)
echo "   Time: ${SECOND_LOAD} seconds"
echo ""

# Calculate improvement
IMPROVEMENT=$(echo "scale=2; (($FIRST_LOAD - $SECOND_LOAD) / $FIRST_LOAD) * 100" | bc)

echo "Test 2: Cache Status"
echo "----------------------------------------"
echo ""

# Check cache directory
if [ -d "storage/framework/cache/data" ]; then
    CACHE_FILES=$(find storage/framework/cache/data -type f | wc -l)
    echo "✓ Cache directory exists"
    echo "  Files in cache: $CACHE_FILES"
else
    echo "⚠ Cache directory not found"
fi
echo ""

echo "Test 3: Product Count Verification"
echo "----------------------------------------"
echo ""
PRODUCT_COUNT=$(php artisan tinker --execute="echo App\Product::count();" 2>/dev/null | tail -n 1)
echo "Total products in database: $PRODUCT_COUNT"
echo ""

echo "=========================================="
echo "Performance Test Results"
echo "=========================================="
echo ""
echo "First Load (Database):  ${FIRST_LOAD}s"
echo "Second Load (Cache):    ${SECOND_LOAD}s"
echo "Improvement:            ${IMPROVEMENT}%"
echo ""

if (( $(echo "$SECOND_LOAD < $FIRST_LOAD" | bc -l) )); then
    echo "✓ Cache is working! Second load is faster."
else
    echo "⚠ Warning: Cache may not be working properly."
fi
echo ""

echo "Recommendations:"
echo "----------------------------------------"
if (( $(echo "$FIRST_LOAD > 3" | bc -l) )); then
    echo "⚠ First load is slow (>3s). Consider:"
    echo "  - Using Redis instead of file cache"
    echo "  - Checking database connection latency"
    echo "  - Setting up database replication"
fi

if (( $(echo "$SECOND_LOAD > 1" | bc -l) )); then
    echo "⚠ Cached load is slow (>1s). Consider:"
    echo "  - Upgrading to Redis cache"
    echo "  - Checking server resources"
fi

if (( $(echo "$SECOND_LOAD < 1" | bc -l) )); then
    echo "✓ Performance is good!"
fi
echo ""

echo "To improve performance further:"
echo "  1. Install Redis: brew install redis"
echo "  2. Set CACHE_DRIVER=redis in .env"
echo "  3. Run: php artisan cache:warm-products"
echo ""
echo "=========================================="
