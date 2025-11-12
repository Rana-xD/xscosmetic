<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserCacheService
{
    const CACHE_KEY = 'pos_users';
    const CACHE_TTL = 3600; // 1 hour in seconds
    
    /**
     * Get all users (excluding SUPERADMIN) from cache or database
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsers()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            Log::info('Loading users from database');
            return User::where('role', '!=', 'SUPERADMIN')->get();
        });
    }
    
    /**
     * Get all users including SUPERADMIN from cache or database
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllUsers()
    {
        return Cache::remember(self::CACHE_KEY . '_all', self::CACHE_TTL, function () {
            Log::info('Loading all users from database');
            return User::all();
        });
    }
    
    /**
     * Clear the users cache
     * 
     * @return void
     */
    public function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_all');
        Log::info('Users cache cleared');
    }
    
    /**
     * Refresh the users cache
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function refreshCache()
    {
        $this->clearCache();
        return $this->getUsers();
    }
    
    /**
     * Get a single user by ID with caching
     * 
     * @param int $id
     * @return \App\User|null
     */
    public function getUserById($id)
    {
        $cacheKey = "user_{$id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return User::find($id);
        });
    }
    
    /**
     * Clear cache for a specific user
     * 
     * @param int $id
     * @return void
     */
    public function clearUserCache($id)
    {
        Cache::forget("user_{$id}");
    }
    
    /**
     * Update user and clear cache
     * 
     * @param int $userId
     * @param array $data
     * @return \App\User|null
     */
    public function updateUser($userId, array $data)
    {
        $user = User::find($userId);
        if ($user) {
            $user->fill($data);
            $user->save();
            
            // Clear individual user cache
            $this->clearUserCache($userId);
            
            // Clear main cache to reflect changes
            $this->clearCache();
            
            return $user;
        }
        
        return null;
    }
    
    /**
     * Delete user and clear cache
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteUser($userId)
    {
        $result = User::destroy($userId);
        
        if ($result) {
            // Clear individual user cache
            $this->clearUserCache($userId);
            
            // Clear main cache to reflect changes
            $this->clearCache();
        }
        
        return $result;
    }
    
    /**
     * Get user by barcode with caching (optimized for attendance scanning)
     * Uses cached users list to avoid database query on each scan
     * 
     * @param string $barcode
     * @return \App\User|null
     */
    public function getUserByBarcode($barcode)
    {
        if (empty($barcode)) {
            return null;
        }
        
        // Get all users from cache
        $users = $this->getAllUsers();
        
        // Search in cached collection - O(n) but cached in memory
        return $users->firstWhere('barcode', $barcode);
    }
}
