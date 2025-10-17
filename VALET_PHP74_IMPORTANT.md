# ⚠️ IMPORTANT: Valet with PHP 7.4

## Critical Information

When using **Laravel Valet with older PHP versions like PHP 7.4**, you **MUST** follow a specific sequence to avoid Composer platform check errors.

## The Problem

Simply running `valet use php@7.4` or `valet install` after installing PHP 7.4 will likely fail with errors like:

```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 7.2.9". 
You are running 7.1.33. 
in /Users/username/.composer/vendor/composer/platform_check.php on line 24
```

## The Solution

The script `setup-new-mac-valet.sh` **already includes** the proper sequence based on the Laracasts community solution:

### Correct Sequence (Already in Script)

```bash
# 1. Stop Valet
valet stop

# 2. Unlink ALL PHP versions
brew unlink php@7.0 php@7.1 php@7.2 php@7.3 php@7.4 php@8.0 php@8.1 php@8.2

# 3. Link PHP 7.4 with force
brew link --force --overwrite php@7.4

# 4. Start PHP 7.4 service
brew services start php@7.4

# 5. Update Composer global packages (CRITICAL!)
composer global update

# 6. Remove old Valet socket
rm -f ~/.config/valet/valet.sock

# 7. Install Valet
valet install
```

## Why This Sequence Matters

1. **Unlinking all versions** ensures no conflicts
2. **Force linking** makes PHP 7.4 the active version
3. **Composer global update** rebuilds the platform check with the correct PHP version
4. **Removing the socket** ensures fresh connection
5. **Valet install** configures everything with the correct PHP

## Reference

This solution is based on the Laracasts community discussion:
https://laracasts.com/discuss/channels/php/issues-with-laravel-valet-when-installing-old-php-version

And the article by Freek Van der Herten:
https://freek.dev/1185-easily-switch-php-versions-in-laravel-valet

## Manual Switching Later

If you need to switch PHP versions after initial setup:

```bash
# Use the --force flag
valet use php@7.4 --force

# Or follow the full sequence above
```

## Verification

After setup, verify everything is correct:

```bash
# Check PHP version
php -v
# Should show: PHP 7.4.x

# Check Valet is using PHP 7.4
valet --version

# Check which PHP binary
which php
# Should show: /usr/local/opt/php@7.4/bin/php

# Test Valet
valet links
```

## Common Mistakes to Avoid

❌ **DON'T** just run `valet use php@7.4` without the full sequence
❌ **DON'T** skip `composer global update`
❌ **DON'T** forget to unlink other PHP versions first
❌ **DON'T** run `valet install` before linking PHP 7.4

✅ **DO** follow the exact sequence in the script
✅ **DO** wait for PHP service to start (sleep 2)
✅ **DO** update Composer global packages
✅ **DO** remove the old socket file

## The Script Handles This

The `setup-new-mac-valet.sh` script **automatically handles all of this** in Step 6, so you don't need to worry about it during initial setup.

However, if you ever need to switch PHP versions manually later, remember to follow this sequence!

## Additional Notes

### For PHP 8.x (Newer Versions)

Newer PHP versions (8.0, 8.1, 8.2) typically work fine with just:
```bash
valet use php@8.1
```

### For PHP 7.x (Older Versions)

Older PHP versions (7.0, 7.1, 7.2, 7.3, 7.4) require the full sequence above.

### Why PHP 7.4 Specifically?

- Laravel 7.x requires PHP 7.2.5 - 8.0
- PHP 7.4 is the sweet spot for Laravel 7.x
- It's considered "old" by Composer's standards
- Requires special handling to avoid platform check errors

## Troubleshooting

If you still get errors after following the sequence:

```bash
# 1. Check PHP version
php -v

# 2. Check Composer version
composer --version

# 3. Clear Composer cache
composer clear-cache

# 4. Update Composer itself
composer self-update

# 5. Try the sequence again with --force
valet use php@7.4 --force

# 6. Check Valet logs
valet log

# 7. Restart everything
brew services restart php@7.4
brew services restart nginx
brew services restart dnsmasq
valet restart
```

## Summary

✅ **The script is already configured correctly**
✅ **You don't need to do anything extra during initial setup**
✅ **The proper sequence is built into Step 6**
✅ **Just run `./setup-new-mac-valet.sh` and it will handle everything**

If you need to switch PHP versions later, remember this sequence!
