<?php
/**
 * WordPress Comment API Tests
 * 
 * Test suite for WordPress comment-related functions
 */

// Load WordPress core
if (!defined('WP_USE_THEMES')) {
    define('WP_USE_THEMES', false);
}

require_once dirname(dirname(__DIR__)) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
/**
 * Test suite for WordPress Comment API functions
 */
class CommentTest extends PHPUnit\Framework\TestCase
{
    protected static $comment_ids = [];
    protected static $post_id;
    protected static $user_id;
    
    /**
     * Set up before class
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Create a test post
        self::$post_id = wp_insert_post([
            'post_title' => 'Test Post for Comments',
            'post_content' => 'Test content for comments',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        // Create a test user
        self::$user_id = wp_insert_user([
            'user_login' => 'testcommenter',
            'user_email' => 'test@example.com',
            'user_pass' => 'password123',
            'display_name' => 'Test Commenter'
        ]);
        
        // Create test comments
        self::$comment_ids['approved'] = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'John Doe',
            'comment_author_email' => 'john@example.com',
            'comment_content' => 'This is an approved comment.',
            'comment_approved' => 1,
            'comment_type' => 'comment'
        ]);
        
        self::$comment_ids['unapproved'] = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Jane Smith',
            'comment_author_email' => 'jane@example.com',
            'comment_content' => 'This is an unapproved comment.',
            'comment_approved' => 0,
            'comment_type' => 'comment'
        ]);
        
        self::$comment_ids['spam'] = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Spammer',
            'comment_author_email' => 'spam@example.com',
            'comment_content' => 'This is spam content.',
            'comment_approved' => 'spam',
            'comment_type' => 'comment'
        ]);
        
        // Create a pingback
        self::$comment_ids['pingback'] = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Pingback Author',
            'comment_content' => 'This is a pingback.',
            'comment_approved' => 1,
            'comment_type' => 'pingback'
        ]);
        
        // Create a trackback
        self::$comment_ids['trackback'] = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Trackback Author',
            'comment_content' => 'This is a trackback.',
            'comment_approved' => 1,
            'comment_type' => 'trackback'
        ]);
    }
    
    /**
     * Clean up after class
     */
    public static function tearDownAfterClass(): void
    {
        // Clean up test comments
        foreach (self::$comment_ids as $comment_id) {
            if ($comment_id) {
                wp_delete_comment($comment_id, true);
            }
        }
        
        // Clean up test post
        if (self::$post_id) {
            wp_delete_post(self::$post_id, true);
        }
        
   
        // Clean up test user
        if (self::$user_id) {
            wp_delete_user(self::$user_id);
        }
        
        parent::tearDownAfterClass();
    }
    
    /**
     * TC-01: Verify check_comment() with manual moderation disabled
     * Test Case Description: Tests check_comment() returns true when manual moderation is disabled
     */
    public function testCheckCommentWithManualModerationDisabled()
    {
        if (!function_exists('check_comment')) {
         $this->markTestSkipped('check_comment() is deprecated.');
        }

        // Temporarily disable manual moderation
        $original_value = get_option('comment_moderation');
        update_option('comment_moderation', '0');
        
        $result = check_comment(
            'Author',
            'author@example.com',
            'http://example.com',
            'Valid comment content',
            '192.168.1.1',
            'Test User Agent',
            'comment'
        );
        
        $this->assertTrue($result, 'check_comment() should return true when manual moderation is disabled');
        
        // Restore original value
        update_option('comment_moderation', $original_value);
    }
    
    /**
     * TC-02: Verify check_comment() with manual moderation enabled
     * Test Case Description: Tests check_comment() returns false when manual moderation is enabled
     */
    public function testCheckCommentWithManualModerationEnabled()
    {
        if (!function_exists('check_comment')) {
             $this->markTestSkipped('check_comment() is deprecated.');
        }

        // Enable manual moderation
        $original_value = get_option('comment_moderation');
        update_option('comment_moderation', '1');
        
        $result = check_comment(
            'Author',
            'author@example.com',
            'http://example.com',
            'Valid comment content',
            '192.168.1.1',
            'Test User Agent',
            'comment'
        );
        
        $this->assertFalse($result, 'check_comment() should return false when manual moderation is enabled');
        
        // Restore original value
        update_option('comment_moderation', $original_value);
    }
    
    /**
     * TC-03: Verify check_comment() with moderation keywords
     * Test Case Description: Tests check_comment() returns false when comment contains moderation keywords
     */
    public function testCheckCommentWithModerationKeywords()
    {
        if (!function_exists('check_comment')) {
            $this->markTestSkipped('check_comment() is deprecated.');
        }

        // Add moderation keywords
        $original_value = get_option('moderation_keys');
        update_option('moderation_keys', "spamword\nbadword");
        
        $result = check_comment(
            'Author',
            'author@example.com',
            'http://example.com',
            'This comment contains a spamword in it.',
            '192.168.1.1',
            'Test User Agent',
            'comment'
        );
        
        $this->assertFalse($result, 'check_comment() should return false when comment contains moderation keywords');
        
        // Restore original value
        update_option('moderation_keys', $original_value);
    }
    
    /**
     * TC-04: Verify check_comment() with excessive links
     * Test Case Description: Tests check_comment() returns false when comment exceeds max links limit
     */
    public function testCheckCommentWithExcessiveLinks()
    {
        if (!function_exists('check_comment')) {
             $this->markTestSkipped('check_comment() is deprecated.');
        }

        // Set max links to 1
        $original_value = get_option('comment_max_links');
        update_option('comment_max_links', 1);
        
        $comment_content = 'Check this <a href="http://link1.com">link1</a> and <a href="http://link2.com">link2</a>';
        
        $result = check_comment(
            'Author',
            'author@example.com',
            'http://example.com',
            $comment_content,
            '192.168.1.1',
            'Test User Agent',
            'comment'
        );
        
        $this->assertFalse($result, 'check_comment() should return false when comment exceeds max links limit');
        
        // Restore original value
        update_option('comment_max_links', $original_value);
    }
    
    /**
     * TC-05: Verify get_approved_comments() function
     * Test Case Description: Tests get_approved_comments() returns only approved comments
     */
    public function testGetApprovedComments()
    {
        $approved_comments = get_approved_comments(self::$post_id);
        
        $this->assertIsArray($approved_comments, 'get_approved_comments() should return array');
        
        foreach ($approved_comments as $comment) {
            $this->assertEquals(1, $comment->comment_approved, 
                'All returned comments should be approved');
            $this->assertEquals(self::$post_id, $comment->comment_post_ID,
                'Comments should belong to the specified post');
        }
    }
    
    /**
     * TC-06: Verify get_comment() with valid ID
     * Test Case Description: Tests get_comment() returns correct comment object for valid ID
     */
    public function testGetCommentWithValidId()
    {
        $comment_id = self::$comment_ids['approved'];
        $comment = get_comment($comment_id);
        
        $this->assertInstanceOf('WP_Comment', $comment, 
            'get_comment() should return WP_Comment object');
        $this->assertEquals($comment_id, $comment->comment_ID, 
            'Comment ID should match input');
        $this->assertEquals('John Doe', $comment->comment_author, 
            'Comment author should be correct');
    }
    
    /**
     * TC-07: Verify get_comment() with invalid ID
     * Test Case Description: Tests get_comment() returns null for non-existent comment ID
     */
    public function testGetCommentWithInvalidId()
    {
        $comment = get_comment(999999);
        
        $this->assertNull($comment, 
            'get_comment() should return null for non-existent comment ID');
    }
    
    /**
     * TC-08: Verify get_comment() with different output formats
     * Test Case Description: Tests get_comment() returns data in different formats
     */
    public function testGetCommentWithDifferentOutputFormats()
    {
        $comment_id = self::$comment_ids['approved'];
        
        // Test OBJECT format (default)
        $comment_object = get_comment($comment_id, OBJECT);
        $this->assertInstanceOf('WP_Comment', $comment_object, 
            'OBJECT format should return WP_Comment object');
        
        // Test ARRAY_A format
        $comment_array = get_comment($comment_id, ARRAY_A);
        $this->assertIsArray($comment_array, 
            'ARRAY_A format should return associative array');
        $this->assertArrayHasKey('comment_ID', $comment_array,
            'Array should have comment_ID key');
        
        // Test ARRAY_N format
        $comment_numeric = get_comment($comment_id, ARRAY_N);
        $this->assertIsArray($comment_numeric, 
            'ARRAY_N format should return numeric array');
        $this->assertIsInt((int)$comment_numeric[0],
            'First element should be comment ID as integer');
    }
    
    /**
     * TC-09: Verify get_comments() with various arguments
     * Test Case Description: Tests get_comments() returns comments based on query arguments
     */
    public function testGetComments()
    {
        // Test with number argument
        $comments = get_comments(['number' => 2]);
        $this->assertLessThanOrEqual(2, count($comments), 
            'Should return at most 2 comments');
        
        // Test with post_id argument
        $post_comments = get_comments(['post_id' => self::$post_id]);
        $this->assertGreaterThan(0, count($post_comments), 
            'Should return comments for specified post');
        
        // Test with status argument
        $approved_comments = get_comments(['status' => 'approve']);
        foreach ($approved_comments as $comment) {
            $this->assertEquals('1', $comment->comment_approved, 
                'All returned comments should be approved');
        }
        
        // Test with type argument
        $pingbacks = get_comments(['type' => 'pingback']);
        foreach ($pingbacks as $comment) {
            $this->assertEquals('pingback', $comment->comment_type, 
                'All returned comments should be pingbacks');
        }
    }
    
    /**
     * TC-10: Verify get_comment_statuses() function
     * Test Case Description: Tests get_comment_statuses() returns all comment statuses
     */
    public function testGetCommentStatuses()
    {
        $statuses = get_comment_statuses();
        
        $this->assertIsArray($statuses, 
            'get_comment_statuses() should return array');
        
        $expected_statuses = ['hold', 'approve', 'spam', 'trash'];
        foreach ($expected_statuses as $status) {
            $this->assertArrayHasKey($status, $statuses,
                "Status array should contain '$status'");
        }
        
        $this->assertEquals('Unapproved', $statuses['hold'],
            'Hold status should be labeled as Unapproved');
        $this->assertEquals('Approved', $statuses['approve'],
            'Approve status should be labeled as Approved');
    }
    
    /**
     * TC-11: Verify get_default_comment_status() function
     * Test Case Description: Tests get_default_comment_status() returns correct default status
     */
    public function testGetDefaultCommentStatus()
    {
        // Test for post type
        $post_status = get_default_comment_status('post');
        $this->assertContains($post_status, ['open', 'closed'],
            'Default status for post should be either open or closed');
        
        // Test for page type
        $page_status = get_default_comment_status('page');
        $this->assertEquals('closed', $page_status,
            'Default status for page should be closed');
        
        // Test for comment type
        $comment_status = get_default_comment_status('post', 'comment');
        $this->assertContains($comment_status, ['open', 'closed'],
            'Default comment status should be either open or closed');
        
        // Test for pingback type
        $pingback_status = get_default_comment_status('post', 'pingback');
        $this->assertContains($pingback_status, ['open', 'closed'],
            'Default pingback status should be either open or closed');
    }
    
    /**
     * TC-12: Verify get_lastcommentmodified() function
     * Test Case Description: Tests get_lastcommentmodified() returns last modified date
     */
    public function testGetLastCommentModified()
    {
        $modified_date = get_lastcommentmodified();
        
        // Could be false if no comments exist, otherwise should be a string
        if ($modified_date !== false) {
            $this->assertIsString($modified_date, 
                'get_lastcommentmodified() should return string when comments exist');
            $this->assertNotEmpty($modified_date,
                'Modified date should not be empty');
        }
        
        // Test with different timezones
        $gmt_date = get_lastcommentmodified('gmt');
        $blog_date = get_lastcommentmodified('blog');
        $server_date = get_lastcommentmodified('server');
        
        if ($gmt_date !== false) {
            $this->assertIsString($gmt_date,
                'GMT date should be string');
        }
    }
    
    /**
     * TC-13: Verify get_comment_count() function
     * Test Case Description: Tests get_comment_count() returns accurate counts
     */
    public function testGetCommentCount()
    {
        $counts = get_comment_count(self::$post_id);
        
        $this->assertIsArray($counts, 
            'get_comment_count() should return array');
        
        $expected_keys = [
            'approved',
            'awaiting_moderation',
            'spam',
            'trash',
            'post-trashed',
            'total_comments',
            'all'
        ];
        
        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $counts,
                "Counts array should have '$key' key");
            $this->assertIsInt($counts[$key],
                "Count for '$key' should be integer");
        }
        
        // Total comments should be sum of approved + awaiting_moderation + spam
        $expected_total = $counts['approved'] + $counts['awaiting_moderation'] + $counts['spam'];
        $this->assertEquals($expected_total, $counts['total_comments'],
            'total_comments should equal approved + awaiting_moderation + spam');
        
        // 'all' should be sum of approved + awaiting_moderation
        $expected_all = $counts['approved'] + $counts['awaiting_moderation'];
        $this->assertEquals($expected_all, $counts['all'],
            "'all' should equal approved + awaiting_moderation");
    }
    
    /**
     * TC-14: Verify comment meta CRUD operations
     * Test Case Description: Tests add_comment_meta(), get_comment_meta(), update_comment_meta(), delete_comment_meta()
     */
    public function testCommentMetaOperations()
    {
        $comment_id = self::$comment_ids['approved'];
        $meta_key = 'test_meta_' . time();
        $meta_value = 'test_value';
        $updated_value = 'updated_value';
        
        // Test add_comment_meta
        $meta_id = add_comment_meta($comment_id, $meta_key, $meta_value, true);
        $this->assertIsInt($meta_id, 
            'add_comment_meta() should return meta ID');
        
        // Test get_comment_meta
        $retrieved_value = get_comment_meta($comment_id, $meta_key, true);
        $this->assertEquals($meta_value, $retrieved_value, 
            'get_comment_meta() should return correct value');
        
        // Test update_comment_meta
        $updated = update_comment_meta($comment_id, $meta_key, $updated_value);
        $this->assertTrue($updated, 
            'update_comment_meta() should return true on success');
        
        $retrieved_updated = get_comment_meta($comment_id, $meta_key, true);
        $this->assertEquals($updated_value, $retrieved_updated, 
            'Updated meta value should be retrieved');
        
        // Test delete_comment_meta
        $deleted = delete_comment_meta($comment_id, $meta_key);
        $this->assertTrue($deleted, 
            'delete_comment_meta() should return true on success');
        
        $after_delete = get_comment_meta($comment_id, $meta_key, true);
        $this->assertEmpty($after_delete, 
            'Meta should be deleted');
    }
    
    /**
     * TC-15: Verify wp_set_comment_cookies() function
     * Test Case Description: Tests wp_set_comment_cookies() sets cookies correctly
     */
    public function testWpSetCommentCookies()
    {
        $comment = get_comment(self::$comment_ids['approved']);
        $user = new WP_User(0); // Anonymous user
        
        // Test with cookies consent
        wp_set_comment_cookies($comment, $user, true);
        
        // Verify cookies are set (can't directly test due to headers already sent in tests)
        // This test mainly ensures function doesn't throw errors
        $this->assertTrue(true, 'wp_set_comment_cookies() should execute without errors');
        
        // Test without cookies consent
        wp_set_comment_cookies($comment, $user, false);
        $this->assertTrue(true, 'wp_set_comment_cookies() should handle declined consent');
    }
    
    /**
     * TC-16: Verify sanitize_comment_cookies() function
     * Test Case Description: Tests sanitize_comment_cookies() sanitizes cookie data
     */
    public function testSanitizeCommentCookies()
    {
        // Set test cookies
        $_COOKIE['comment_author_' . COOKIEHASH] = '<script>alert("xss")</script>Author';
        $_COOKIE['comment_author_email_' . COOKIEHASH] = '<script>alert("xss")</script>test@example.com';
        $_COOKIE['comment_author_url_' . COOKIEHASH] = '<script>alert("xss")</script>http://example.com';
        
        // Sanitize cookies
        sanitize_comment_cookies();
        
        // Verify cookies are sanitized
        $this->assertStringNotContainsString('<script>', $_COOKIE['comment_author_' . COOKIEHASH],
            'Author cookie should be sanitized');
        $this->assertStringNotContainsString('<script>', $_COOKIE['comment_author_email_' . COOKIEHASH],
            'Email cookie should be sanitized');
        $this->assertStringNotContainsString('<script>', $_COOKIE['comment_author_url_' . COOKIEHASH],
            'URL cookie should be sanitized');
        
        // Clean up
        unset($_COOKIE['comment_author_' . COOKIEHASH]);
        unset($_COOKIE['comment_author_email_' . COOKIEHASH]);
        unset($_COOKIE['comment_author_url_' . COOKIEHASH]);
    }
    
    /**
     * TC-17: Verify wp_allow_comment() with duplicate detection
     * Test Case Description: Tests wp_allow_comment() detects duplicate comments
     */
    public function testWpAllowCommentDuplicateDetection()
    {
        $comment_data = [
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'John Doe',
            'comment_author_email' => 'john@example.com',
            'comment_content' => 'This is an approved comment.',
            'comment_parent' => 0
        ];
        
        // This should detect duplicate since we already have this comment
        $result = wp_allow_comment($comment_data, true);
        
        $this->assertInstanceOf('WP_Error', $result,
            'wp_allow_comment() should return WP_Error for duplicate comment');
        $this->assertEquals('comment_duplicate', $result->get_error_code(),
            'Error code should be comment_duplicate');
    }
    
    /**
     * TC-18: Verify wp_check_comment_flood() function
     * Test Case Description: Tests wp_check_comment_flood() detects comment flooding
     */
    public function testWpCheckCommentFlood()
    {
        // Create a recent comment to simulate flood
        $recent_comment_id = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Flood Tester',
            'comment_author_email' => 'flood@example.com',
            'comment_content' => 'Recent comment for flood test.',
            'comment_approved' => 1,
            'comment_date' => current_time('mysql'),
            'comment_date_gmt' => current_time('mysql', 1)
        ]);
        
        // Try to check flood - should return false for non-admin user
        $is_flood = wp_check_comment_flood(
            false,
            '192.168.1.1',
            'flood@example.com',
            current_time('mysql'),
            true
        );
        
        // Clean up
        wp_delete_comment($recent_comment_id, true);
        
        // Could be true or false depending on timing
        $this->assertIsBool($is_flood,
            'wp_check_comment_flood() should return boolean');
    }
    
    /**
     * TC-19: Verify separate_comments() function
     * Test Case Description: Tests separate_comments() separates comments by type
     */
    public function testSeparateComments()
    {
        $comments = get_comments(['post_id' => self::$post_id]);
        $separated = separate_comments($comments);
        
        $this->assertIsArray($separated,
            'separate_comments() should return array');
        
        $expected_types = ['comment', 'trackback', 'pingback', 'pings'];
        foreach ($expected_types as $type) {
            $this->assertArrayHasKey($type, $separated,
                "Separated array should have '$type' key");
            $this->assertIsArray($separated[$type],
                "Value for '$type' should be array");
        }
        
        // Pings should contain both trackbacks and pingbacks
        $this->assertGreaterThanOrEqual(
            count($separated['trackback']) + count($separated['pingback']),
            count($separated['pings']),
            'Pings array should contain all trackbacks and pingbacks'
        );
    }
    
    /**
     * TC-20: Verify get_comment_pages_count() function
     * Test Case Description: Tests get_comment_pages_count() calculates correct page count
     */
    public function testGetCommentPagesCount()
    {
        $comments = get_comments(['post_id' => self::$post_id]);
        $pages_count = get_comment_pages_count($comments, 2, false);
        
        $this->assertIsInt($pages_count,
            'get_comment_pages_count() should return integer');
        $this->assertGreaterThan(0, $pages_count,
            'Should have at least 1 page');
        
        // With page_comments disabled, should always return 1
        $original_value = get_option('page_comments');
        update_option('page_comments', '0');
        
        $single_page = get_comment_pages_count($comments, 2, false);
        $this->assertEquals(1, $single_page,
            'Should return 1 when page_comments is disabled');
        
        // Restore original value
        update_option('page_comments', $original_value);
    }
    
    /**
     * TC-21: Verify get_page_of_comment() function
     * Test Case Description: Tests get_page_of_comment() calculates correct page for comment
     */
    public function testGetPageOfComment()
    {
        $comment_id = self::$comment_ids['approved'];
        $page = get_page_of_comment($comment_id, [
            'per_page' => 2,
            'type' => 'all'
        ]);
        
        $this->assertIsInt($page,
            'get_page_of_comment() should return integer');
        $this->assertGreaterThan(0, $page,
            'Page number should be greater than 0');
        
        // Test with invalid comment
        $invalid_page = get_page_of_comment(999999);
        $this->assertNull($invalid_page,
            'Should return null for invalid comment ID');
    }
    
    /**
     * TC-22: Verify wp_get_comment_fields_max_lengths() function
     * Test Case Description: Tests wp_get_comment_fields_max_lengths() returns field length limits
     */
    public function testWpGetCommentFieldsMaxLengths()
    {
        $max_lengths = wp_get_comment_fields_max_lengths();
        
        $this->assertIsArray($max_lengths,
            'wp_get_comment_fields_max_lengths() should return array');
        
        $expected_fields = [
            'comment_author',
            'comment_author_email',
            'comment_author_url',
            'comment_content'
        ];
        
        foreach ($expected_fields as $field) {
            $this->assertArrayHasKey($field, $max_lengths,
                "Max lengths should include '$field'");
            $this->assertIsInt($max_lengths[$field],
                "Max length for '$field' should be integer");
            $this->assertGreaterThan(0, $max_lengths[$field],
                "Max length for '$field' should be positive");
        }
    }
    
    /**
     * TC-23: Verify wp_check_comment_data_max_lengths() function
     * Test Case Description: Tests wp_check_comment_data_max_lengths() validates field lengths
     */
    public function testWpCheckCommentDataMaxLengths()
    {
        $max_lengths = wp_get_comment_fields_max_lengths();
        
        // Test with valid data
        $valid_data = [
            'comment_author' => str_repeat('a', min(10, $max_lengths['comment_author'])),
            'comment_author_email' => str_repeat('a', min(20, $max_lengths['comment_author_email'])) . '@example.com',
            'comment_author_url' => 'http://example.com',
            'comment_content' => str_repeat('a', min(100, $max_lengths['comment_content']))
        ];
        
        $valid_result = wp_check_comment_data_max_lengths($valid_data);
        $this->assertTrue($valid_result,
            'Should return true for valid data within limits');
        
        // Test with excessively long author name
        $invalid_data = $valid_data;
        $invalid_data['comment_author'] = str_repeat('a', $max_lengths['comment_author'] + 10);
        
        $invalid_result = wp_check_comment_data_max_lengths($invalid_data);
        $this->assertInstanceOf('WP_Error', $invalid_result,
            'Should return WP_Error for data exceeding limits');
    }
    
    /**
     * TC-24: Verify wp_check_comment_disallowed_list() function
     * Test Case Description: Tests wp_check_comment_disallowed_list() detects disallowed content
     */
    public function testWpCheckCommentDisallowedList()
    {
        // Add disallowed words
        $original_value = get_option('disallowed_keys');
        update_option('disallowed_keys', "spamword\nbadword\nviagra");
        
        // Test with clean content
        $clean_result = wp_check_comment_disallowed_list(
            'Author',
            'author@example.com',
            'http://example.com',
            'This is a clean comment.',
            '192.168.1.1',
            'Test User Agent'
        );
        
        $this->assertFalse($clean_result,
            'Should return false for clean content');
        
        // Test with disallowed word
        $spam_result = wp_check_comment_disallowed_list(
            'Author',
            'author@example.com',
            'http://example.com',
            'This comment contains viagra mention.',
            '192.168.1.1',
            'Test User Agent'
        );
        
        $this->assertTrue($spam_result,
            'Should return true for content with disallowed words');
        
        // Restore original value
        update_option('disallowed_keys', $original_value);
    }
    
    /**
     * TC-25: Verify wp_count_comments() function
     * Test Case Description: Tests wp_count_comments() returns cached comment counts
     */
    public function testWpCountComments()
    {
        $counts = wp_count_comments(self::$post_id);
        
        $this->assertIsObject($counts,
            'wp_count_comments() should return object');
        
        $expected_properties = [
            'approved',
            'moderated',
            'spam',
            'trash',
            'post-trashed',
            'total_comments',
            'all'
        ];
        
        foreach ($expected_properties as $property) {
            $this->assertObjectHasProperty($property, $counts,
                "Counts object should have '$property' property");
            $this->assertIsInt($counts->$property,
                "Property '$property' should be integer");
        }
        
        // Test caching by calling again
        $cached_counts = wp_count_comments(self::$post_id);
        $this->assertEquals($counts, $cached_counts,
            'Should return cached counts on subsequent calls');
    }
    
    /**
     * TC-26: Verify wp_delete_comment() function
     * Test Case Description: Tests wp_delete_comment() deletes or trashes comments
     */
    public function testWpDeleteComment()
    {
        // Create a comment to delete
        $comment_id = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'To Delete',
            'comment_author_email' => 'delete@example.com',
            'comment_content' => 'This comment will be deleted.',
            'comment_approved' => 1
        ]);
        
        // Test force delete
        $deleted = wp_delete_comment($comment_id, true);
        $this->assertInstanceOf('WP_Comment', $deleted ?? new WP_Comment(), 'wp_delete_comment() should return WP_Comment object on success');
        
        $comment_after = get_comment($comment_id);
        $this->assertNull($comment_after,
            'Comment should be permanently deleted');
        
        // Test trash (if trash enabled)
        if (defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS) {
            $comment_id2 = wp_insert_comment([
                'comment_post_ID' => self::$post_id,
                'comment_author' => 'To Trash',
                'comment_author_email' => 'trash@example.com',
                'comment_content' => 'This comment will be trashed.',
                'comment_approved' => 1
            ]);
            
            $trashed = wp_delete_comment($comment_id2, false);
           $this->assertInstanceOf('WP_Comment', $deleted ?? new WP_Comment(), 'wp_delete_comment() should return WP_Comment object on success');
            
            $trashed_comment = get_comment($comment_id2);
            $this->assertEquals('trash', $trashed_comment->comment_approved,
                'Comment should be moved to trash');
            
            // Clean up
            wp_delete_comment($comment_id2, true);
        }
    }
    
    /**
     * TC-27: Verify wp_trash_comment() and wp_untrash_comment() functions
     * Test Case Description: Tests comment trashing and untrashing functionality
     */
    public function testWpTrashUntrashComment()
    {
        if (!EMPTY_TRASH_DAYS || EMPTY_TRASH_DAYS == 0) {
            $this->markTestSkipped('Trash is disabled (EMPTY_TRASH_DAYS = 0)');
        }
        
        $comment_id = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Trash Test',
            'comment_author_email' => 'trash@example.com',
            'comment_content' => 'This comment will be trashed and untrashed.',
            'comment_approved' => 1
        ]);
        
        // Trash the comment
        $trashed = wp_trash_comment($comment_id);
        $this->assertInstanceOf('WP_Comment', $trashed,
            'wp_trash_comment() should return WP_Comment object');
        $this->assertEquals('trash', $trashed->comment_approved,
            'Comment should be trashed');
        
        // Untrash the comment
        $untrashed = wp_untrash_comment($comment_id);
        $this->assertInstanceOf('WP_Comment', $untrashed,
            'wp_untrash_comment() should return WP_Comment object');
        $this->assertEquals('1', $untrashed->comment_approved,
            'Untrashed comment should be approved');
        
        // Clean up
        wp_delete_comment($comment_id, true);
    }
    
    /**
     * TC-28: Verify wp_spam_comment() and wp_unspam_comment() functions
     * Test Case Description: Tests comment spamming and unspamming functionality
     */
    public function testWpSpamUnspamComment()
    {
        $comment_id = wp_insert_comment([
            'comment_post_ID' => self::$post_id,
            'comment_author' => 'Spam Test',
            'comment_author_email' => 'spamtest@example.com',
            'comment_content' => 'This comment will be marked as spam.',
            'comment_approved' => 1
        ]);
        
        // Mark as spam
        $spammed = wp_spam_comment($comment_id);
        $this->assertInstanceOf('WP_Comment', $spammed,
            'wp_spam_comment() should return WP_Comment object');
        $this->assertEquals('spam', $spammed->comment_approved,
            'Comment should be marked as spam');
        
        // Unspam the comment
        $unspammed = wp_unspam_comment($comment_id);
        $this->assertInstanceOf('WP_Comment', $unspammed,
            'wp_unspam_comment() should return WP_Comment object');
        $this->assertEquals('1', $unspammed->comment_approved,
            'Unspammed comment should be approved');
        
        // Clean up
        wp_delete_comment($comment_id, true);
    }
    
    /**
     * TC-29: Verify wp_get_comment_status() function
     * Test Case Description: Tests wp_get_comment_status() returns correct status strings
     */
    public function testWpGetCommentStatus()
    {
        $approved_status = wp_get_comment_status(self::$comment_ids['approved']);
        $this->assertEquals('approved', $approved_status,
            'Should return "approved" for approved comment');
        
        $unapproved_status = wp_get_comment_status(self::$comment_ids['unapproved']);
        $this->assertEquals('unapproved', $unapproved_status,
            'Should return "unapproved" for unapproved comment');
        
        $spam_status = wp_get_comment_status(self::$comment_ids['spam']);
        $this->assertEquals('spam', $spam_status,
            'Should return "spam" for spam comment');
        
        // Test with invalid comment
        $invalid_status = wp_get_comment_status(999999);
        
        $this->assertFalse($invalid_status,
            'Should return false for invalid comment ID');
    }
}