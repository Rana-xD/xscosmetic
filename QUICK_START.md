# Quick Start Guide - Performance Optimization

## 🚀 One-Command Setup

```bash
./setup-performance-optimization.sh
```

That's it! The script will guide you through the complete setup.

---

## ✨ What You Get

- ⚡ **Fast Caching** - Redis or file-based (automatic detection)
- 🔄 **Background Jobs** - Asynchronous order processing
- 👷 **2 Queue Workers** - Managed by Supervisor
- 🔁 **Auto-Restart** - Workers restart on failure
- ⏰ **Auto-Refresh** - Cache updates every 15 minutes
- 📊 **Performance Testing** - Built-in benchmarking
- 🛡️ **Error Recovery** - Automatic fallback options

---

## 📋 Prerequisites

- macOS with Homebrew
- Laravel project (artisan file present)
- PHP installed
- Terminal access

---

## 🎯 Quick Commands

### Check Status
```bash
# Check workers
supervisorctl status

# Check Redis (if installed)
redis-cli ping

# Check queue
redis-cli LLEN queues:default
```

### View Logs
```bash
# Worker logs
tail -f storage/logs/worker.log

# Application logs
tail -f storage/logs/laravel.log
```

### Manage Workers
```bash
# Restart workers
supervisorctl restart laravel-worker:*

# Stop workers
supervisorctl stop laravel-worker:*

# Start workers
supervisorctl start laravel-worker:*
```

### Manage Cache
```bash
# Clear cache
php artisan cache:clear

# Warm cache
php artisan cache:warm-products

# Clear config
php artisan config:clear
```

### Manage Queue
```bash
# View failed jobs
php artisan queue:failed

# Retry all failed
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

---

## 🔧 Troubleshooting

### Workers Not Running?
```bash
supervisorctl restart laravel-worker:*
tail -f storage/logs/worker.log
```

### Redis Not Working?
```bash
brew services restart redis
php artisan config:clear
```

### Cache Not Working?
```bash
php artisan cache:clear
php artisan config:clear
php artisan cache:warm-products
```

---

## 📚 Full Documentation

- **Complete Guide**: `PERFORMANCE_OPTIMIZATION_GUIDE.md`
- **Script Comparison**: `SCRIPTS_COMPARISON.md`
- **This Quick Start**: `QUICK_START.md`

---

## 🎉 Expected Results

- **Page Load**: < 2 seconds
- **Cache Hit**: < 100ms
- **Order Processing**: Background (non-blocking)
- **Worker Uptime**: 99%+

---

## 💡 Tips

1. **Development**: File driver is fine
2. **Production**: Use Redis for best performance
3. **Monitor**: Check logs regularly
4. **Maintain**: Restart workers weekly

---

## 🆘 Need Help?

1. Check logs: `tail -f storage/logs/worker.log`
2. Run diagnostics: `./diagnose-queue-worker.sh`
3. Test performance: `./test-performance.sh`
4. Review docs: `PERFORMANCE_OPTIMIZATION_GUIDE.md`

---

**Made with ❤️ for XS Cosmetic**
