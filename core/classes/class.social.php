<?php

class Social {
    private $database;
    private $app;
    private $account;
    private $system;
    
    public function __construct($database, $app, $account, $system) {
        $this->database = $database;
        $this->app = $app;
        $this->account = $account;
        $this->system = $system;
    }
    
    // ============================================
    // POST MANAGEMENT
    // ============================================
    
    public function createPost($user_id, $content, $media_type = null, $media_data = null, $visibility = 'public', $parent_post_id = null, $hashtags = null) {
        $sql = "INSERT INTO bg_social_posts (user_id, content, post_type, media_type, media_urls, hashtags, visibility, parent_post_id, `status`, created_at) 
                VALUES (:user_id, :content, :post_type, :media_type, :media_urls, :hashtags, :visibility, :parent_post_id, 'active', NOW())";
        
        $params = [
            'user_id' => $user_id,
            'content' => $content,
            'post_type' => $parent_post_id ? 'reply' : 'post',
            'media_type' => $media_type,
            'media_urls' => $media_data ? json_encode($media_data) : null,
            'hashtags' => $hashtags ? json_encode($hashtags) : null,
            'visibility' => $visibility,
            'parent_post_id' => $parent_post_id
        ];
        
        $this->database->query($sql, $params);
        $post_id = $this->database->lastInsertId();
        
        $this->logActivity($user_id, 'created_post', $post_id);
        
        return $post_id;
    }
    
    public function getPost($post_id, $user_id = null) {
        $sql = "SELECT p.*, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND interaction_type = 'like' AND `status` = 'active') as like_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'comment' AND `status` = 'active') as comment_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'share' AND `status` = 'active') as share_count";
        
        if ($user_id) {
            $sql .= ", (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND user_id = :viewer_id AND interaction_type = 'like' AND `status` = 'active') as user_liked,
                      (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND user_id = :viewer_id AND interaction_type = 'bookmark' AND `status` = 'active') as user_bookmarked";
        }
        
        $sql .= " FROM bg_social_posts p
                 JOIN bg_users u ON p.user_id = u.user_id
                 WHERE p.post_id = :post_id AND p.status = 'active'";
        
        $params = ['post_id' => $post_id];
        if ($user_id) {
            $params['viewer_id'] = $user_id;
        }
        
        return $this->database->getrow($sql, $params);
    }
    
    public function getFeed($user_id, $limit = 20, $offset = 0, $feed_type = 'all') {
        $sql = "SELECT p.*, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND interaction_type = 'like' AND `status` = 'active') as like_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'comment' AND `status` = 'active') as comment_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'share' AND `status` = 'active') as share_count,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND user_id = :user_id_like AND interaction_type = 'like' AND `status` = 'active') as user_liked,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND user_id = :user_id_bookmark AND interaction_type = 'bookmark' AND `status` = 'active') as user_bookmarked
                FROM bg_social_posts p
                JOIN bg_users u ON p.user_id = u.user_id
                WHERE p.status = 'active'
                AND p.post_type = 'post'";
        
        // Build params array dynamically based on feed type
        $params = [
            'user_id_like' => $user_id,
            'user_id_bookmark' => $user_id,
            'user_id_visibility' => $user_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        if ($feed_type == 'following') {
            $sql .= " AND p.user_id IN (SELECT following_user_id FROM bg_social_follows WHERE follower_user_id = :user_id_follow AND `status` = 'active')";
            $params['user_id_follow'] = $user_id;
        } elseif ($feed_type == 'user') {
            $sql .= " AND p.user_id = :user_id_filter";
            $params['user_id_filter'] = $user_id;
        }
        
        $sql .= " AND (p.visibility = 'public' OR p.user_id = :user_id_visibility)
                 ORDER BY p.created_at DESC
                 LIMIT :limit OFFSET :offset";
        
        return $this->database->getrows($sql, $params);
    }
    
    public function updatePost($post_id, $user_id, $content) {
        $sql = "UPDATE bg_social_posts 
                SET content = :content, updated_at = NOW() 
                WHERE post_id = :post_id AND user_id = :user_id";
        
        $params = [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'content' => $content
        ];
        
        return $this->database->query($sql, $params);
    }
    
    public function deletePost($post_id, $user_id) {
        $sql = "UPDATE bg_social_posts 
                SET `status` = 'deleted', deleted_at = NOW() 
                WHERE post_id = :post_id AND user_id = :user_id";
        
        $params = [
            'post_id' => $post_id,
            'user_id' => $user_id
        ];
        
        $result = $this->database->query($sql, $params);
        
        if ($result) {
            $this->logActivity($user_id, 'deleted_post', $post_id);
        }
        
        return $result;
    }
    
    // ============================================
    // COMMENT MANAGEMENT
    // ============================================
    
    public function addComment($post_id, $user_id, $comment_text, $parent_comment_id = null) {
        $sql = "INSERT INTO bg_social_posts (parent_post_id, user_id, content, post_type, `status`, created_at) 
                VALUES (:parent_post_id, :user_id, :content, 'comment', 'active', NOW())";
        
        $params = [
            'parent_post_id' => $post_id,
            'user_id' => $user_id,
            'content' => $comment_text
        ];
        
        $this->database->query($sql, $params);
        $comment_id = $this->database->lastInsertId();
        
        $this->logActivity($user_id, 'commented', $post_id);
        
        if ($parent_comment_id) {
            $this->createNotification($this->getCommentUserId($parent_comment_id), 'reply', $comment_id, $user_id);
        } else {
            $post = $this->getPost($post_id);
            if ($post['user_id'] != $user_id) {
                $this->createNotification($post['user_id'], 'comment', $comment_id, $user_id);
            }
        }
        
        return $comment_id;
    }
    
    public function getComments($post_id, $limit = 50, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = c.post_id AND interaction_type = 'like' AND `status` = 'active') as like_count
                FROM bg_social_posts c
                JOIN bg_users u ON c.user_id = u.user_id
                WHERE c.parent_post_id = :post_id AND c.status = 'active' AND c.post_type = 'comment'
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            'post_id' => $post_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        $comments = $this->database->getrows($sql, $params);
        
        // Comments don't have nested replies in our simplified schema
        
        return $comments;
    }
    
    public function getCommentReplies($parent_comment_id) {
        // In our simplified schema, we don't support nested comment replies
        return [];
    }
    
    public function deleteComment($comment_id, $user_id) {
        $sql = "UPDATE bg_social_posts 
                SET `status` = 'deleted', deleted_at = NOW() 
                WHERE post_id = :comment_id AND user_id = :user_id AND post_type = 'comment'";
        
        $params = [
            'comment_id' => $comment_id,
            'user_id' => $user_id
        ];
        
        return $this->database->query($sql, $params);
    }
    
    // ============================================
    // INTERACTION MANAGEMENT (Likes, Bookmarks, Shares)
    // ============================================
    
    public function likePost($post_id, $user_id) {
        $sql = "SELECT interaction_id FROM bg_social_interactions 
                WHERE post_id = :post_id AND user_id = :user_id AND interaction_type = 'like'";
        
        $params = ['post_id' => $post_id, 'user_id' => $user_id];
        $existing = $this->database->getrow($sql, $params);
        
        if ($existing) {
            $sql = "UPDATE bg_social_interactions 
                    SET `status` = CASE WHEN `status` = 'active' THEN 'inactive' ELSE 'active' END,
                    updated_at = NOW()
                    WHERE interaction_id = :interaction_id";
            
            $this->database->query($sql, ['interaction_id' => $existing['interaction_id']]);
            
            $sql = "SELECT `status` FROM bg_social_interactions WHERE interaction_id = :interaction_id";
            $result = $this->database->getrow($sql, ['interaction_id' => $existing['interaction_id']]);
            $liked = ($result['status'] == 'active');
        } else {
            $sql = "INSERT INTO bg_social_interactions (post_id, user_id, interaction_type, `status`, created_at) 
                    VALUES (:post_id, :user_id, 'like', 'active', NOW())";
            
            $this->database->query($sql, $params);
            $liked = true;
            
            $post = $this->getPost($post_id);
            if ($post['user_id'] != $user_id) {
                $this->createNotification($post['user_id'], 'like', $post_id, $user_id);
            }
        }
        
        if ($liked) {
            $this->logActivity($user_id, 'liked_post', $post_id);
        }
        
        return $liked;
    }
    
    public function likeComment($comment_id, $user_id) {
        // Comments are posts too, so we can use the same like mechanism
        return $this->likePost($comment_id, $user_id);
    }
    
    public function bookmarkPost($post_id, $user_id) {
        $sql = "SELECT interaction_id FROM bg_social_interactions 
                WHERE post_id = :post_id AND user_id = :user_id AND interaction_type = 'bookmark'";
        
        $params = ['post_id' => $post_id, 'user_id' => $user_id];
        $existing = $this->database->getrow($sql, $params);
        
        if ($existing) {
            $sql = "UPDATE bg_social_interactions 
                    SET `status` = CASE WHEN `status` = 'active' THEN 'inactive' ELSE 'active' END,
                    updated_at = NOW()
                    WHERE interaction_id = :interaction_id";
            
            $this->database->query($sql, ['interaction_id' => $existing['interaction_id']]);
            
            $sql = "SELECT `status` FROM bg_social_interactions WHERE interaction_id = :interaction_id";
            $result = $this->database->getrow($sql, ['interaction_id' => $existing['interaction_id']]);
            $bookmarked = ($result['status'] == 'active');
        } else {
            $sql = "INSERT INTO bg_social_interactions (post_id, user_id, interaction_type, `status`, created_at) 
                    VALUES (:post_id, :user_id, 'bookmark', 'active', NOW())";
            
            $this->database->query($sql, $params);
            $bookmarked = true;
        }
        
        if ($bookmarked) {
            $this->logActivity($user_id, 'bookmarked_post', $post_id);
        }
        
        return $bookmarked;
    }
    
    public function sharePost($post_id, $user_id, $share_text = null) {
        $sql = "INSERT INTO bg_social_posts (parent_post_id, user_id, content, post_type, `status`, created_at) 
                VALUES (:parent_post_id, :user_id, :content, 'share', 'active', NOW())";
        
        $params = [
            'parent_post_id' => $post_id,
            'user_id' => $user_id,
            'content' => $share_text
        ];
        
        $this->database->query($sql, $params);
        $share_id = $this->database->lastInsertId();
        
        $this->logActivity($user_id, 'shared_post', $post_id);
        
        $post = $this->getPost($post_id);
        if ($post['user_id'] != $user_id) {
            $this->createNotification($post['user_id'], 'share', $post_id, $user_id);
        }
        
        return $share_id;
    }
    
    // ============================================
    // FOLLOW MANAGEMENT
    // ============================================
    
    public function followUser($follower_id, $following_id) {
        if ($follower_id == $following_id) {
            return false;
        }
        
        $sql = "SELECT follow_id, `status` FROM bg_social_follows 
                WHERE follower_user_id = :follower_id AND following_user_id = :following_id";
        
        $params = ['follower_id' => $follower_id, 'following_id' => $following_id];
        $existing = $this->database->getrow($sql, $params);
        
        if ($existing) {
            $sql = "UPDATE bg_social_follows 
                    SET `status` = CASE WHEN `status` = 'active' THEN 'inactive' ELSE 'active' END,
                    updated_at = NOW()
                    WHERE follow_id = :follow_id";
            
            $this->database->query($sql, ['follow_id' => $existing['follow_id']]);
            
            $sql = "SELECT `status` FROM bg_social_follows WHERE follow_id = :follow_id";
            $result = $this->database->getrow($sql, ['follow_id' => $existing['follow_id']]);
            $following = ($result['status'] == 'active');
        } else {
            $sql = "INSERT INTO bg_social_follows (follower_user_id, following_user_id, `status`, created_at) 
                    VALUES (:follower_id, :following_id, 'active', NOW())";
            
            $this->database->query($sql, $params);
            $following = true;
            
            $this->createNotification($following_id, 'follow', null, $follower_id);
        }
        
        return $following;
    }
    
    public function getFollowers($user_id, $limit = 50, $offset = 0) {
        $sql = "SELECT u.user_id, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url, f.created_at as followed_at
                FROM bg_social_follows f
                JOIN bg_users u ON f.follower_user_id = u.user_id
                WHERE f.following_user_id = :user_id AND f.status = 'active'
                ORDER BY f.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    public function getFollowing($user_id, $limit = 50, $offset = 0) {
        $sql = "SELECT u.user_id, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url, f.created_at as followed_at
                FROM bg_social_follows f
                JOIN bg_users u ON f.following_user_id = u.user_id
                WHERE f.follower_user_id = :user_id AND f.status = 'active'
                ORDER BY f.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    public function isFollowing($follower_id, $following_id) {
        $sql = "SELECT COUNT(*) as count FROM bg_social_follows 
                WHERE follower_user_id = :follower_id AND following_user_id = :following_id AND `status` = 'active'";
        
        $params = ['follower_id' => $follower_id, 'following_id' => $following_id];
        $result = $this->database->getrow($sql, $params);
        
        return ($result['count'] > 0);
    }
    
    // ============================================
    // MEDIA MANAGEMENT
    // ============================================
    // Note: Media is stored in JSON column in bg_social_posts table
    
    // ============================================
    // ACTIVITY & NOTIFICATIONS
    // ============================================
    
    public function logActivity($user_id, $activity_type, $related_id = null, $metadata = null) {
        $sql = "INSERT INTO bg_social_activity (user_id, activity_type, related_id, metadata, created_at) 
                VALUES (:user_id, :activity_type, :related_id, :metadata, NOW())";
        
        $params = [
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'related_id' => $related_id,
            'metadata' => $metadata ? json_encode($metadata) : null
        ];
        
        $this->database->query($sql, $params);
    }
    
    public function getUserActivity($user_id, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM bg_social_activity 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            'user_id' => $user_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    public function createNotification($user_id, $notification_type, $related_id, $from_user_id) {
        $sql = "INSERT INTO bg_social_notifications (user_id, notification_type, related_id, from_user_id, is_read, created_at) 
                VALUES (:user_id, :notification_type, :related_id, :from_user_id, 0, NOW())";
        
        $params = [
            'user_id' => $user_id,
            'notification_type' => $notification_type,
            'related_id' => $related_id,
            'from_user_id' => $from_user_id
        ];
        
        $this->database->query($sql, $params);
    }
    
    public function getNotifications($user_id, $unread_only = false, $limit = 50) {
        $sql = "SELECT n.*, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url
                FROM bg_social_notifications n
                JOIN bg_users u ON n.from_user_id = u.user_id
                WHERE n.user_id = :user_id";
        
        if ($unread_only) {
            $sql .= " AND n.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT :limit";
        
        $params = [
            'user_id' => $user_id,
            'limit' => $limit
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    public function markNotificationRead($notification_id, $user_id) {
        $sql = "UPDATE bg_social_notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE notification_id = :notification_id AND user_id = :user_id";
        
        $params = [
            'notification_id' => $notification_id,
            'user_id' => $user_id
        ];
        
        return $this->database->query($sql, $params);
    }
    
    // ============================================
    // SEARCH & DISCOVERY
    // ============================================
    
    public function searchPosts($search_term, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND `status` = 'active') as like_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE post_id = p.post_id AND `status` = 'active') as comment_count
                FROM bg_social_posts p
                JOIN bg_users u ON p.user_id = u.user_id
                WHERE p.status = 'active' 
                AND p.visibility = 'public'
                AND (p.content LIKE :search_term OR u.username LIKE :search_term_user)
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            'search_term' => '%' . $search_term . '%',
            'search_term_user' => '%' . $search_term . '%',
            'limit' => $limit,
            'offset' => $offset
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    public function searchUsers($search_term, $limit = 20, $offset = 0) {
        $sql = "SELECT u.user_id, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url, u.bio,
                (SELECT COUNT(*) FROM bg_social_follows WHERE following_user_id = u.user_id AND `status` = 'active') as follower_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE user_id = u.user_id AND `status` = 'active') as post_count
                FROM bg_users u
                WHERE u.status = 'active'
                AND (u.username LIKE :search_term 
                     OR u.first_name LIKE :search_term 
                     OR u.last_name LIKE :search_term
                     OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term_full)
                ORDER BY follower_count DESC
                LIMIT :limit OFFSET :offset";
        
        $params = [
            'search_term' => '%' . $search_term . '%',
            'search_term_full' => '%' . $search_term . '%',
            'limit' => $limit,
            'offset' => $offset
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    public function getTrendingPosts($hours = 24, $limit = 10) {
        $sql = "SELECT p.*, u.username, u.first_name, u.last_name,
                (SELECT description FROM bg_user_attributes 
                 WHERE user_id = u.user_id AND type = 'profile_image' 
                 AND name = 'avatar' AND `status` = 'active' AND category = 'primary' LIMIT 1) as avatar_url,
                (SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND interaction_type = 'like' AND `status` = 'active') as like_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'comment' AND `status` = 'active') as comment_count,
                (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'share' AND `status` = 'active') as share_count,
                ((SELECT COUNT(*) FROM bg_social_interactions WHERE post_id = p.post_id AND `status` = 'active' AND created_at > DATE_SUB(NOW(), INTERVAL :hours1 HOUR)) * 3 +
                 (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'comment' AND `status` = 'active' AND created_at > DATE_SUB(NOW(), INTERVAL :hours2 HOUR)) * 2 +
                 (SELECT COUNT(*) FROM bg_social_posts WHERE parent_post_id = p.post_id AND post_type = 'share' AND `status` = 'active' AND created_at > DATE_SUB(NOW(), INTERVAL :hours3 HOUR)) * 4) as trending_score
                FROM bg_social_posts p
                JOIN bg_users u ON p.user_id = u.user_id
                WHERE p.status = 'active' 
                AND p.visibility = 'public'
                AND p.post_type = 'post'
                AND p.created_at > DATE_SUB(NOW(), INTERVAL :hours_filter HOUR)
                ORDER BY trending_score DESC, p.created_at DESC
                LIMIT :limit";
        
        $params = [
            'hours1' => $hours,
            'hours2' => $hours,
            'hours3' => $hours,
            'hours_filter' => $hours * 7,
            'limit' => $limit
        ];
        
        return $this->database->getrows($sql, $params);
    }
    
    // ============================================
    // HELPER FUNCTIONS
    // ============================================
    
    private function getCommentUserId($comment_id) {
        $sql = "SELECT user_id FROM bg_social_posts WHERE post_id = :comment_id";
        $result = $this->database->getrow($sql, ['comment_id' => $comment_id]);
        return $result ? $result['user_id'] : null;
    }
    
    public function getUserStats($user_id) {
        $stats = [];
        
        $sql = "SELECT COUNT(*) as count FROM bg_social_posts WHERE user_id = :user_id AND `status` = 'active'";
        $result = $this->database->getrow($sql, ['user_id' => $user_id]);
        $stats['post_count'] = $result['count'];
        
        $sql = "SELECT COUNT(*) as count FROM bg_social_follows WHERE following_user_id = :user_id AND `status` = 'active'";
        $result = $this->database->getrow($sql, ['user_id' => $user_id]);
        $stats['follower_count'] = $result['count'];
        
        $sql = "SELECT COUNT(*) as count FROM bg_social_follows WHERE follower_user_id = :user_id AND `status` = 'active'";
        $result = $this->database->getrow($sql, ['user_id' => $user_id]);
        $stats['following_count'] = $result['count'];
        
        $sql = "SELECT COUNT(*) as count FROM bg_social_interactions l 
                JOIN bg_social_posts p ON l.post_id = p.post_id 
                WHERE p.user_id = :user_id AND l.interaction_type = 'like' AND l.status = 'active'";
        $result = $this->database->getrow($sql, ['user_id' => $user_id]);
        $stats['likes_received'] = $result['count'];
        
        return $stats;
    }
    
    public function formatTimeAgo($datetime) {
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }
}