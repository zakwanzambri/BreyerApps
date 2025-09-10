<?php
/**
 * Test Configuration for Campus Hub
 * Configuration settings for testing environment
 */

return [
    // Database settings for testing
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'test_database' => 'campus_hub_test',
        'charset' => 'utf8mb4'
    ],
    
    // API testing settings
    'api' => [
        'base_url' => 'http://localhost/BreyerApps/campus-hub',
        'timeout' => 30,
        'verify_ssl' => false // For development/testing only
    ],
    
    // Test data settings
    'test_data' => [
        'cleanup_after_tests' => true,
        'use_transactions' => true,
        'seed_data' => true
    ],
    
    // Performance testing thresholds
    'performance' => [
        'max_response_time' => 2.0, // seconds
        'max_query_time' => 0.1,    // seconds
        'max_page_load_time' => 5.0, // seconds
        'max_dom_elements' => 2000
    ],
    
    // Test reporting
    'reporting' => [
        'save_results' => true,
        'results_directory' => 'tests/results',
        'generate_html_report' => true,
        'send_email_report' => false
    ],
    
    // Test users (for authentication testing)
    'test_users' => [
        [
            'username' => 'test_admin',
            'email' => 'admin@test.example.com',
            'password' => 'TestAdmin123!',
            'user_type' => 'admin',
            'full_name' => 'Test Administrator'
        ],
        [
            'username' => 'test_student',
            'email' => 'student@test.example.com',
            'password' => 'TestStudent123!',
            'user_type' => 'student',
            'full_name' => 'Test Student'
        ],
        [
            'username' => 'test_faculty',
            'email' => 'faculty@test.example.com',
            'password' => 'TestFaculty123!',
            'user_type' => 'faculty',
            'full_name' => 'Test Faculty Member'
        ]
    ],
    
    // Test content
    'test_content' => [
        'news' => [
            [
                'title' => 'Test News Article 1',
                'content' => 'This is test news content for automated testing. It contains sufficient text to test content functionality.',
                'category' => 'general',
                'status' => 'published'
            ],
            [
                'title' => 'Test News Article 2',
                'content' => 'Another test news article with different content for testing search and filtering capabilities.',
                'category' => 'academic',
                'status' => 'published'
            ]
        ],
        'events' => [
            [
                'title' => 'Test Event 1',
                'description' => 'This is a test event for automated testing purposes.',
                'event_date' => '2025-01-15 10:00:00',
                'location' => 'Test Location 1',
                'category' => 'academic'
            ],
            [
                'title' => 'Test Event 2',
                'description' => 'Another test event with different details for comprehensive testing.',
                'event_date' => '2025-02-20 14:00:00',
                'location' => 'Test Location 2',
                'category' => 'social'
            ]
        ]
    ],
    
    // Browser testing settings (for future Selenium integration)
    'browser_testing' => [
        'enabled' => false,
        'browsers' => ['chrome', 'firefox'],
        'headless' => true,
        'screenshot_on_failure' => true
    ],
    
    // CI/CD settings
    'ci_cd' => [
        'fail_on_error' => true,
        'coverage_threshold' => 80,
        'performance_regression_threshold' => 20 // percent
    ]
];
?>
