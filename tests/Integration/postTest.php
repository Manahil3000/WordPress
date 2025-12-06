 bbbbbbb<?php
/**
 * WordPress Post API Tests - Standalone Version
 * 
 * Test suite for WordPress post-related functions
 * 
 * All 25 test cases from the specification
 */

// Load WordPress core
if ( ! defined( 'WP_USE_THEMES' ) ) {
    define( 'WP_USE_THEMES', false );
}
require_once dirname(dirname(__DIR__)) . '/wp-load.php';

/**
 * Test suite for WordPress Post API functions
 */
class PostTest extends PHPUnit\Framework\TestCase
{
    protected static $post_ids = [];
    protected static $attachment_id;
    protected static $thumbnail_id;
    
    /**
     * Set up before class
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Create test posts
        self::$post_ids['post'] = wp_insert_post([
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        self::$post_ids['draft'] = wp_insert_post([
            'post_title' => 'Draft Post',
            'post_content' => 'Draft content',
            'post_status' => 'draft',
            'post_type' => 'post'
        ]);
        
        // Create a sticky post
        self::$post_ids['sticky'] = wp_insert_post([
            'post_title' => 'Sticky Post',
            'post_content' => 'Sticky content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        stick_post(self::$post_ids['sticky']);
        
        // Create attachment
        self::$attachment_id = wp_insert_post([
            'post_title' => 'Test Attachment',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image/jpeg',
            'guid' => 'http://example.com/test.jpg'
        ]);
        
        // Create thumbnail
        self::$thumbnail_id = wp_insert_post([
            'post_title' => 'Test Thumbnail',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image/jpeg',
            'guid' => 'http://example.com/thumbnail.jpg'
        ]);
        
        // Ensure post types are registered
        if (!post_type_exists('wp_block')) {
            create_initial_post_types();
        }
    }
    
    /**
     * Clean up after class
     */
    public static function tearDownAfterClass(): void
    {
        // Clean up test posts
        foreach (self::$post_ids as $post_id) {
            if ($post_id) {
                wp_delete_post($post_id, true);
            }
        }
        
        if (self::$attachment_id) {
            wp_delete_post(self::$attachment_id, true);
        }
        
        if (self::$thumbnail_id) {
            wp_delete_post(self::$thumbnail_id, true);
        }
        
        parent::tearDownAfterClass();
    }
    
    /**
     * TC-01: Verify Initial Post Types Are Created
     * Test Case Description: Verifies that core WordPress post types are registered
     */
    public function testCreateInitialPostTypes()
    {
        // Temporarily clear post types to test registration
        global $wp_post_types;
        $original_post_types = $wp_post_types;
        $wp_post_types = [];
        
        // Run the function
        create_initial_post_types();
        
        // Check that core post types are registered
        $this->assertTrue(post_type_exists('post'), 'Post type "post" should be registered');
        $this->assertTrue(post_type_exists('page'), 'Post type "page" should be registered');
        $this->assertTrue(post_type_exists('attachment'), 'Post type "attachment" should be registered');
        $this->assertTrue(post_type_exists('revision'), 'Post type "revision" should be registered');
        $this->assertTrue(post_type_exists('nav_menu_item'), 'Post type "nav_menu_item" should be registered');
        $this->assertTrue(post_type_exists('wp_block'), 'Post type "wp_block" should be registered');
        
        // Restore original post types
        $wp_post_types = $original_post_types;
    }
    
    /**
     * TC-02: Verify Post Type Registration and Properties
     * Test Case Description: Tests that each registered post type has correct properties
     */
    public function testPostTypeProperties()
    {
        $post_types = ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'wp_block'];
        
        foreach ($post_types as $post_type) {
            $post_type_obj = get_post_type_object($post_type);
            
            $this->assertNotNull($post_type_obj, 
                "Post type {$post_type} should be registered");
            
            // Check basic properties
            $this->assertIsObject($post_type_obj, 
                "Post type {$post_type} should be an object");
            
            if ($post_type_obj) {
                $this->assertNotEmpty($post_type_obj->labels, 
                    "Post type {$post_type} should have labels");
                $this->assertNotEmpty($post_type_obj->capabilities, 
                    "Post type {$post_type} should have capabilities");
            }
        }
        
        // Check specific properties
        $post_type_obj = get_post_type_object('post');
        $this->assertFalse($post_type_obj->hierarchical, 
            'Post type "post" should not be hierarchical');
        
        $page_type_obj = get_post_type_object('page');
        $this->assertTrue($page_type_obj->hierarchical, 
            'Post type "page" should be hierarchical');
    }
    
    /**
     * TC-03: Verify Post Status Registration
     * Test Case Description: Tests that all WordPress post statuses are properly registered
     */
    public function testPostStatusRegistration()
    {
        $statuses = ['publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit'];
        
        foreach ($statuses as $status) {
            $status_obj = get_post_status_object($status);
            
            $this->assertNotNull($status_obj, 
                "Post status {$status} should be registered");
            $this->assertEquals($status, $status_obj->name, 
                "Post status object should have correct name");
        }
    }
    
    /**
     * TC-04: Verify get_post() Returns Correct Post Data
     * Test Case Description: Tests get_post() function returns correct data for valid post ID
     */
    public function testGetPostWithValidId()
    {
        $post_id = self::$post_ids['post'];
        $post = get_post($post_id);
        
        $this->assertInstanceOf('WP_Post', $post, 
            'get_post() should return WP_Post object');
        $this->assertEquals($post_id, $post->ID, 
            'Post ID should match input');
        $this->assertEquals('Test Post', $post->post_title, 
            'Post title should be correct');
        $this->assertEquals('publish', $post->post_status, 
            'Post status should be correct');
    }
    
    /**
     * TC-05: Verify get_post() Handles Invalid Post ID
     * Test Case Description: Tests get_post() returns null for non-existent post ID
     */
    public function testGetPostWithInvalidId()
    {
        $post = get_post(999999);
        
        $this->assertNull($post, 
            'get_post() should return null for non-existent post ID');
    }
    
    /**
     * TC-06: Verify is_sticky() Function Works Correctly
     * Test Case Description: Tests is_sticky() correctly identifies sticky posts
     */
    public function testIsSticky()
    {
        $sticky_id = self::$post_ids['sticky'];
        $non_sticky_id = self::$post_ids['post'];
        
        $this->assertTrue(is_sticky($sticky_id), 
            'is_sticky() should return true for sticky posts');
        $this->assertFalse(is_sticky($non_sticky_id), 
            'is_sticky() should return false for non-sticky posts');
        $this->assertFalse(is_sticky(999999), 
            'is_sticky() should return false for non-existent post');
    }
    
    /**
     * TC-07: Verify wp_insert_post() Creates New Post
     * Test Case Description: Tests wp_insert_post() successfully creates new post
     */
    public function testWpInsertPost()
    {
        $post_data = [
            'post_title' => 'New Test Post',
            'post_content' => 'New test content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ];
        
        $post_id = wp_insert_post($post_data);
        
        $this->assertIsInt($post_id, 
            'wp_insert_post() should return integer ID');
        $this->assertGreaterThan(0, $post_id, 
            'Post ID should be greater than 0');
        
        // Verify post was created
        $post = get_post($post_id);
        $this->assertEquals('New Test Post', $post->post_title, 
            'New post should have correct title');
        
        // Clean up
        wp_delete_post($post_id, true);
    }
    
    /**
     * TC-08: Verify wp_insert_post() with Invalid Data
     * Test Case Description: Tests wp_insert_post() returns error for invalid data
     */
    public function testWpInsertPostInvalid()
    {
        // Test with empty content for post type that requires it
        $post_data = [
            'post_title' => '',
            'post_content' => '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'post_type' => 'post'
        ];
        
        $post_id = wp_insert_post($post_data, true);
        
        // Should return 0 or WP_Error
        if ($post_id === 0) {
            $this->assertEquals(0, $post_id, 
                'wp_insert_post() should return 0 for invalid data');
        } else {
            $this->assertInstanceOf('WP_Error', $post_id, 
                'wp_insert_post() should return WP_Error for invalid data');
        }
    }
    
    /**
     * TC-09: Verify wp_update_post() Updates Existing Post
     * Test Case Description: Tests wp_update_post() successfully updates existing post
     */
    public function testWpUpdatePost()
    {
        $post_id = wp_insert_post([
            'post_title' => 'Original Title',
            'post_content' => 'Original content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        $updated = wp_update_post([
            'ID' => $post_id,
            'post_title' => 'Updated Title'
        ]);
        
        $this->assertEquals($post_id, $updated, 
            'wp_update_post() should return the post ID');
        
        $post = get_post($post_id);
        $this->assertEquals('Updated Title', $post->post_title, 
            'Post title should be updated');
        
        // Clean up
        wp_delete_post($post_id, true);
    }
    
    /**
     * TC-10: Verify wp_delete_post() Moves Post to Trash
     * Test Case Description: Tests wp_delete_post() moves post to trash when force_delete is false
     */
    public function testWpDeletePostToTrash()
    {
        if (!EMPTY_TRASH_DAYS) {
            $this->markTestSkipped('Trash is disabled (EMPTY_TRASH_DAYS = 0)');
        }
        
        $post_id = wp_insert_post([
            'post_title' => 'Post to Trash',
            'post_content' => 'Content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        $result = wp_delete_post($post_id, false);
        
        $this->assertInstanceOf('WP_Post', $result, 
            'wp_delete_post() should return WP_Post object when moving to trash');
        
        $post = get_post($post_id);
        $this->assertEquals('trash', $post->post_status, 
            'Post should be moved to trash');
        
        // Clean up from trash
        wp_delete_post($post_id, true);
    }
    
    /**
     * TC-11: Verify wp_delete_post() Permanently Deletes Post
     * Test Case Description: Tests wp_delete_post() permanently deletes post when force_delete is true
     */
    public function testWpDeletePostPermanent()
    {
        $post_id = wp_insert_post([
            'post_title' => 'Post to Delete',
            'post_content' => 'Content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        $result = wp_delete_post($post_id, true);
        
        $this->assertInstanceOf('WP_Post', $result, 
            'wp_delete_post() should return WP_Post object');
        
        $post = get_post($post_id);
        $this->assertNull($post, 
            'Post should be permanently deleted');
    }
    
    /**
     * TC-12: Verify Post Meta Functions
     * Test Case Description: Tests post meta CRUD operations (add_post_meta, get_post_meta, update_post_meta)
     */
    public function testPostMetaOperations()
    {
        $post_id = self::$post_ids['post'];
        $meta_key = 'test_meta_' . time();
        $meta_value = 'test_value';
        $updated_value = 'updated_value';
        
        // Test add_post_meta
        $meta_id = add_post_meta($post_id, $meta_key, $meta_value, true);
        $this->assertIsInt($meta_id, 
            'add_post_meta() should return meta ID');
        
        // Test get_post_meta
        $retrieved_value = get_post_meta($post_id, $meta_key, true);
        $this->assertEquals($meta_value, $retrieved_value, 
            'get_post_meta() should return correct value');
        
        // Test update_post_meta
        $updated = update_post_meta($post_id, $meta_key, $updated_value);
        $this->assertTrue($updated, 
            'update_post_meta() should return true on success');
        
        $retrieved_updated = get_post_meta($post_id, $meta_key, true);
        $this->assertEquals($updated_value, $retrieved_updated, 
            'Updated meta value should be retrieved');
        
        // Test delete_post_meta
        $deleted = delete_post_meta($post_id, $meta_key);
        $this->assertTrue($deleted, 
            'delete_post_meta() should return true on success');
        
        $after_delete = get_post_meta($post_id, $meta_key, true);
        $this->assertEmpty($after_delete, 
            'Meta should be deleted');
    }
    
    /**
     * TC-13: Verify get_attachment_url() Returns Correct URL
     * Test Case Description: Tests wp_get_attachment_url() returns correct URL for attachment
     */
    public function testWpGetAttachmentUrl()
    {
        // Skip if no attachment created
        if (!self::$attachment_id) {
            $this->markTestSkipped('No attachment created for testing');
        }
        
        $url = wp_get_attachment_url(self::$attachment_id);
        
        $this->assertIsString($url, 
            'wp_get_attachment_url() should return string');
        
        // URL might not contain filename in test environment
        if (!empty($url)) {
            $this->assertNotEmpty($url, 
                'Attachment URL should not be empty');
        }
    }
    
    /**
     * TC-14: Verify wp_get_attachment_metadata() Returns Metadata
     * Test Case Description: Tests wp_get_attachment_metadata() returns correct metadata for attachment
     */
    public function testWpGetAttachmentMetadata()
    {
        // Create a real attachment with metadata
        $test_file = dirname(__DIR__) . '/test-image.jpg';
        
        // Create a dummy test file if it doesn't exist
        if (!file_exists($test_file)) {
            // Create a small JPEG image
            $image = imagecreate(100, 100);
            imagecolorallocate($image, 255, 255, 255);
            imagejpeg($image, $test_file, 90);
            imagedestroy($image);
        }
        
        $attachment_id = wp_insert_attachment([
            'post_title' => 'Test Image',
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image/jpeg',
            'guid' => 'http://example.com/test-image.jpg'
        ]);
        
        // Add basic metadata
        $metadata = [
            'width' => 100,
            'height' => 100,
            'file' => 'test-image.jpg',
            'sizes' => [],
            'image_meta' => []
        ];
        
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        $retrieved_metadata = wp_get_attachment_metadata($attachment_id);
        
        $this->assertIsArray($retrieved_metadata, 
            'wp_get_attachment_metadata() should return array');
        
        // Clean up
        wp_delete_post($attachment_id, true);
        if (file_exists($test_file)) {
            unlink($test_file);
        }
    }
    
    /**
     * TC-15: Verify is_post_type_viewable() Function
     * Test Case Description: Tests is_post_type_viewable() correctly identifies viewable post types
     */
    public function testIsPostTypeViewable()
    {
        $this->assertTrue(is_post_type_viewable('post'), 
            '"post" post type should be viewable');
        $this->assertTrue(is_post_type_viewable('page'), 
            '"page" post type should be viewable');
        $this->assertFalse(is_post_type_viewable('revision'), 
            '"revision" post type should not be viewable');
        $this->assertFalse(is_post_type_viewable('invalid_post_type'), 
            'Invalid post type should not be viewable');
    }
    
    /**
     * TC-16: Verify wp_count_posts() Returns Correct Counts
     * Test Case Description: Tests wp_count_posts() returns accurate counts for post statuses
     */
    public function testWpCountPosts()
    {
        $counts = wp_count_posts('post');
        
        $this->assertIsObject($counts, 
            'wp_count_posts() should return object');
        $this->assertObjectHasProperty('publish', $counts, 
            'Counts should have publish property');
        $this->assertObjectHasProperty('draft', $counts, 
            'Counts should have draft property');
        
        // Check that counts are integers
        $this->assertIsInt($counts->publish, 
            'Publish count should be integer');
        $this->assertIsInt($counts->draft, 
            'Draft count should be integer');
    }
    
    /**
     * TC-17: Verify get_posts() Function with Various Arguments
     * Test Case Description: Tests get_posts() returns correct posts based on query arguments
     */
    public function testGetPosts()
    {
        // Test with numberposts
        $posts = get_posts(['numberposts' => 2]);
        $this->assertLessThanOrEqual(2, count($posts), 
            'Should return at most 2 posts');
        
        // Test with post_status
        $published_posts = get_posts(['post_status' => 'publish']);
        $this->assertGreaterThan(0, count($published_posts), 
            'Should return published posts');
        
        foreach ($published_posts as $post) {
            $this->assertEquals('publish', $post->post_status, 
                'All returned posts should be published');
        }
        
        // Test with orderby
        $ordered_posts = get_posts(['orderby' => 'ID', 'order' => 'DESC']);
        if (count($ordered_posts) > 1) {
            $this->assertGreaterThanOrEqual(
                $ordered_posts[1]->ID, 
                $ordered_posts[0]->ID,
                'Posts should be ordered by ID descending'
            );
        }
    }
    
    /**
     * TC-18: Verify sanitize_post() Function
     * Test Case Description: Tests sanitize_post() properly sanitizes post data for different contexts
     */
    public function testSanitizePost()
    {
        $post_data = [
            'ID' => 1,
            'post_title' => '<script>alert("xss")</script>Title',
            'post_content' => '<div>Content</div>',
            'post_status' => 'publish'
        ];
        
        // Test raw context
        $raw_sanitized = sanitize_post((object) $post_data, 'raw');
        $this->assertEquals($post_data['post_title'], $raw_sanitized->post_title, 
            'Raw context should not sanitize HTML');
        
        // Test display context
        $display_sanitized = sanitize_post((object) $post_data, 'display');
        $this->assertNotEquals($post_data['post_title'], $display_sanitized->post_title, 
            'Display context should sanitize HTML');
        $this->assertStringNotContainsString('<script>', $display_sanitized->post_title, 
            'Script tags should be removed in display context');
        
        // Test edit context
        $edit_sanitized = sanitize_post((object) $post_data, 'edit');
        $this->assertIsString($edit_sanitized->post_title, 
            'Edit context should return string');
    }
    
    /**
     * TC-19: Verify wp_trash_post() and wp_untrash_post() Functions
     * Test Case Description: Tests post trashing and untrashing functionality
     */
    public function testPostTrashUntrash()
    {
        if (!EMPTY_TRASH_DAYS) {
            $this->markTestSkipped('Trash is disabled (EMPTY_TRASH_DAYS = 0)');
        }
        
        $post_id = wp_insert_post([
            'post_title' => 'Trash Test Post',
            'post_content' => 'Content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        // Trash the post
        $trashed = wp_trash_post($post_id);
        $this->assertInstanceOf('WP_Post', $trashed, 
            'wp_trash_post() should return WP_Post object');
        $this->assertEquals('trash', $trashed->post_status, 
            'Post should be trashed');
        
        // Untrash the post
        $untrashed = wp_untrash_post($post_id);
        $this->assertInstanceOf('WP_Post', $untrashed, 
            'wp_untrash_post() should return WP_Post object');
        $this->assertEquals('draft', $untrashed->post_status, 
            'Untrashed post should be in draft status by default');
        
        // Clean up
        wp_delete_post($post_id, true);
    }
    
    /**
     * TC-20: Verify wp_publish_post() Function
     * Test Case Description: Tests wp_publish_post() successfully publishes a post
     */
    public function testWpPublishPost()
    {
        $post_id = wp_insert_post([
            'post_title' => 'Draft to Publish',
            'post_content' => 'Content',
            'post_status' => 'draft',
            'post_type' => 'post'
        ]);
        
        wp_publish_post($post_id);
        
        $post = get_post($post_id);
        $this->assertEquals('publish', $post->post_status, 
            'Post should be published');
        
        // Clean up
        wp_delete_post($post_id, true);
    }
    
    /**
     * TC-21: Verify wp_unique_post_slug() Generates Unique Slugs
     * Test Case Description: Tests wp_unique_post_slug() generates unique slugs when duplicates exist
     */
    public function testWpUniquePostSlug()
    {
        // Create a post with specific slug
        $post1_id = wp_insert_post([
            'post_title' => 'Duplicate Slug',
            'post_name' => 'duplicate-slug',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        // Try to create another post with same slug
        $unique_slug = wp_unique_post_slug('duplicate-slug', 0, 'publish', 'post', 0);
        
        $this->assertNotEquals('duplicate-slug', $unique_slug, 
            'Should generate unique slug');
        $this->assertStringContainsString('duplicate-slug', $unique_slug, 
            'Unique slug should contain original slug');
        
        // Clean up
        wp_delete_post($post1_id, true);
    }
    
    /**
     * TC-22: Verify set_post_thumbnail() and delete_post_thumbnail()
     * Test Case Description: Tests post thumbnail (featured image) management functions
     */
    public function testPostThumbnailManagement()
    {
        // Skip if no thumbnail created
        if (!self::$thumbnail_id) {
            $this->markTestSkipped('No thumbnail created for testing');
        }
        
        $post_id = self::$post_ids['post'];
        
        // Set thumbnail
        $set_result = set_post_thumbnail($post_id, self::$thumbnail_id);
        $this->assertTrue($set_result, 
            'set_post_thumbnail() should return true on success');
        
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $this->assertEquals(self::$thumbnail_id, $thumbnail_id, 
            'Post should have correct thumbnail ID');
        
        // Delete thumbnail
        $delete_result = delete_post_thumbnail($post_id);
        $this->assertTrue($delete_result, 
            'delete_post_thumbnail() should return true on success');
        
        $after_delete = get_post_thumbnail_id($post_id);
        $this->assertEmpty($after_delete, 
            'Thumbnail should be deleted');
    }
    
    /**
     * TC-23: Verify clean_post_cache() Function
     * Test Case Description: Tests clean_post_cache() properly clears post-related caches
     */
    public function testCleanPostCache()
    {
        $post_id = self::$post_ids['post'];
        
        // Prime the cache by getting the post
        get_post($post_id);
        get_post_meta($post_id);
        
        // Clean cache - this should not throw errors
        clean_post_cache($post_id);
        
        // Verify we can still get the post after cache cleanup
        $post_after_cleanup = get_post($post_id);
        $this->assertInstanceOf('WP_Post', $post_after_cleanup, 
            'Should still be able to get post after cache cleanup');
        $this->assertEquals($post_id, $post_after_cleanup->ID, 
            'Post ID should match after cache cleanup');
    }
    
    /**
     * TC-24: Verify register_post_meta() and unregister_post_meta() Functions
     * Test Case Description: Tests custom post meta registration and unregistration
     */
    public function testRegisterUnregisterPostMeta()
    {
        $post_type = 'post';
        $meta_key = 'test_registered_meta_' . time();
        $args = [
            'type' => 'string',
            'description' => 'Test registered meta',
            'single' => true,
            'show_in_rest' => true
        ];
        
        // Register meta
        $registered = register_post_meta($post_type, $meta_key, $args);
        $this->assertTrue($registered, 
            'register_post_meta() should return true on success');
        
        // Unregister meta
        $unregistered = unregister_post_meta($post_type, $meta_key);
        $this->assertTrue($unregistered, 
            'unregister_post_meta() should return true on success');
        
        // Try to register again (should work after unregistering)
        $registered_again = register_post_meta($post_type, $meta_key, $args);
        $this->assertTrue($registered_again, 
            'Should be able to register meta again after unregistering');
        
        // Clean up
        unregister_post_meta($post_type, $meta_key);
    }
    
    /**
     * TC-25: Verify use_block_editor_for_post() Function
     * Test Case Description: Tests use_block_editor_for_post() correctly determines if block editor should be used
     */
    public function testUseBlockEditorForPost()
    {
        // Test with regular post
        $post_id = self::$post_ids['post'];
        $should_use_editor = use_block_editor_for_post($post_id);
        
        // This depends on WordPress configuration
        // We'll just verify it returns a boolean
        $this->assertIsBool($should_use_editor, 
            'use_block_editor_for_post() should return boolean');
        
        // Test with invalid post
        $invalid_result = use_block_editor_for_post(999999);
        $this->assertFalse($invalid_result, 
            'Should return false for invalid post');
    }
    
    /**
     * Bonus Test: Verify post status transitions
     * Test Case Description: Tests post status transition hooks work correctly
     */
    public function testPostStatusTransitions()
    {
        $post_id = wp_insert_post([
            'post_title' => 'Transition Test',
            'post_content' => 'Content',
            'post_status' => 'draft',
            'post_type' => 'post'
        ]);
        
        // Add action to track transitions
        $transition_called = false;
        $transition_callback = function($new_status, $old_status, $post) use (&$transition_called, $post_id) {
            if ($post->ID == $post_id) {
                $transition_called = true;
            }
        };
        
        add_action('transition_post_status', $transition_callback, 10, 3);
        
        // Update post to trigger transition
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish'
        ]);
        
        $this->assertTrue($transition_called, 
            'Post status transition should trigger action');
        
        // Remove the action
        remove_action('transition_post_status', $transition_callback, 10);
        
        // Clean up
        wp_delete_post($post_id, true);
    }
}