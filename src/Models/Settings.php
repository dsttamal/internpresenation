<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Settings Model
 * 
 * Represents system configuration settings.
 * 
 * @OA\Schema(
 *     schema="Settings",
 *     type="object",
 *     title="Settings",
 *     description="System configuration settings",
 *     @OA\Property(property="id", type="integer", example=1, description="Settings ID"),
 *     @OA\Property(property="key", type="string", example="site_name", description="Setting key"),
 *     @OA\Property(property="value", type="string", example="Form Builder System", description="Setting value"),
 *     @OA\Property(property="type", type="string", example="string", description="Value type (string, boolean, integer, json)"),
 *     @OA\Property(property="description", type="string", example="The name of the website", description="Setting description"),
 *     @OA\Property(property="category", type="string", example="general", description="Setting category"),
 *     @OA\Property(property="isPublic", type="boolean", example=false, description="Whether setting is publicly accessible"),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-20T10:30:00Z"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-20T11:00:00Z")
 * )
 */
class Settings extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
        'isPublic'
    ];

    protected $casts = [
        'isPublic' => 'boolean',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime'
    ];

    // Type constants
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_JSON = 'json';

    // Category constants
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_EMAIL = 'email';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_UI = 'ui';

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = self::TYPE_STRING, $description = null, $category = self::CATEGORY_GENERAL, $isPublic = false)
    {
        $setting = static::firstOrNew(['key' => $key]);
        
        $setting->value = is_array($value) || is_object($value) ? json_encode($value) : $value;
        $setting->type = $type;
        $setting->description = $description;
        $setting->category = $category;
        $setting->isPublic = $isPublic;
        
        return $setting->save();
    }

    /**
     * Get all public settings
     */
    public static function getPublicSettings()
    {
        $settings = static::where('isPublic', true)->get();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = static::castValue($setting->value, $setting->type);
        }
        
        return $result;
    }

    /**
     * Get settings by category
     */
    public static function getByCategory($category)
    {
        $settings = static::where('category', $category)->get();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = [
                'value' => static::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
                'isPublic' => $setting->isPublic
            ];
        }
        
        return $result;
    }

    /**
     * Cast value to appropriate type
     */
    private static function castValue($value, $type)
    {
        switch ($type) {
            case self::TYPE_BOOLEAN:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_INTEGER:
                return (int) $value;
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_JSON:
                return json_decode($value, true);
            case self::TYPE_STRING:
            default:
                return $value;
        }
    }

    /**
     * Get default system settings
     */
    public static function getDefaults()
    {
        return [
            // General settings
            'site_name' => [
                'value' => 'Form Builder System',
                'type' => self::TYPE_STRING,
                'description' => 'The name of the website',
                'category' => self::CATEGORY_GENERAL,
                'isPublic' => true
            ],
            'site_description' => [
                'value' => 'Dynamic form builder and submission management system',
                'type' => self::TYPE_STRING,
                'description' => 'Description of the website',
                'category' => self::CATEGORY_GENERAL,
                'isPublic' => true
            ],
            'maintenance_mode' => [
                'value' => false,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable maintenance mode',
                'category' => self::CATEGORY_GENERAL,
                'isPublic' => false
            ],
            
            // Payment settings
            'stripe_enabled' => [
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable Stripe payments',
                'category' => self::CATEGORY_PAYMENT,
                'isPublic' => true
            ],
            'bkash_enabled' => [
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable bKash payments',
                'category' => self::CATEGORY_PAYMENT,
                'isPublic' => true
            ],
            'bank_transfer_enabled' => [
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable bank transfer payments',
                'category' => self::CATEGORY_PAYMENT,
                'isPublic' => true
            ],
            'default_currency' => [
                'value' => 'USD',
                'type' => self::TYPE_STRING,
                'description' => 'Default currency for payments',
                'category' => self::CATEGORY_PAYMENT,
                'isPublic' => true
            ],
            
            // Security settings
            'jwt_expiry' => [
                'value' => 3600,
                'type' => self::TYPE_INTEGER,
                'description' => 'JWT token expiry time in seconds',
                'category' => self::CATEGORY_SECURITY,
                'isPublic' => false
            ],
            'max_login_attempts' => [
                'value' => 5,
                'type' => self::TYPE_INTEGER,
                'description' => 'Maximum login attempts before lockout',
                'category' => self::CATEGORY_SECURITY,
                'isPublic' => false
            ],
            'password_min_length' => [
                'value' => 8,
                'type' => self::TYPE_INTEGER,
                'description' => 'Minimum password length',
                'category' => self::CATEGORY_SECURITY,
                'isPublic' => true
            ],
            
            // Email settings
            'email_notifications' => [
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable email notifications',
                'category' => self::CATEGORY_EMAIL,
                'isPublic' => false
            ],
            'admin_email' => [
                'value' => 'admin@example.com',
                'type' => self::TYPE_STRING,
                'description' => 'Administrator email address',
                'category' => self::CATEGORY_EMAIL,
                'isPublic' => false
            ],
            
            // UI settings
            'theme' => [
                'value' => 'default',
                'type' => self::TYPE_STRING,
                'description' => 'UI theme',
                'category' => self::CATEGORY_UI,
                'isPublic' => true
            ],
            'items_per_page' => [
                'value' => 25,
                'type' => self::TYPE_INTEGER,
                'description' => 'Default items per page for listings',
                'category' => self::CATEGORY_UI,
                'isPublic' => true
            ]
        ];
    }

    /**
     * Initialize default settings
     */
    public static function initializeDefaults()
    {
        $defaults = static::getDefaults();
        
        foreach ($defaults as $key => $config) {
            $existing = static::where('key', $key)->first();
            
            if (!$existing) {
                static::set(
                    $key,
                    $config['value'],
                    $config['type'],
                    $config['description'],
                    $config['category'],
                    $config['isPublic']
                );
            }
        }
    }
}
