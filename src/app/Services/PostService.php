<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Comment;
use App\Core\Database;

class PostService
{
    private $postModel;
    private $commentModel;

    public function __construct()
    {
        $this->postModel = new Post();
        $this->commentModel = new Comment();
    }

    public function getPosts(int $page, int $limit, ?string $search, ?string $sort): array
    {
        $offset = ($page - 1) * $limit;
        $posts = $this->postModel->getPaginatedPosts($limit, $offset, $search, $sort);
        $totalPosts = $this->postModel->getTotalCount($search);
        $totalPages = ceil($totalPosts / $limit);

        return [
            'posts' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ];
    }

    public function createPost(int $userId, string $rawContent, ?array $files): void
    {
        $cleanContent = strip_tags($rawContent, '<p><br><b><i><u><strong><em><a><ul><ol><li>');

        if (!empty(trim(strip_tags($cleanContent)))) {
            $postId = $this->postModel->create($userId, $cleanContent);

            if (!empty($files['name'][0])) {
                $this->handleImageUploads($postId, $files);
            }
        }
    }

    public function deletePost(int $postId, int $userId): bool
    {
        $post = $this->postModel->getById($postId);
        if (!$post || $post['user_id'] !== $userId) {
            return false;
        }

        $images = $this->postModel->getImagesByPostId($postId);
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        foreach ($images as $imgPath) {
            $fullPath = $documentRoot . $imgPath;
            if (file_exists($fullPath) && is_file($fullPath)) {
                unlink($fullPath);
            }
        }
        return $this->postModel->delete($postId, $userId);
    }

    public function toggleLike(int $postId, int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if ($post && $post['user_id'] != $userId) {
            $stmt = $db->prepare("SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);

            if ($stmt->fetch()) {
                $db->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")
                    ->execute([$userId, $postId]);
            } else {
                $db->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)")
                    ->execute([$userId, $postId]);
                $this->postModel->updateActivity($postId);
            }
        }
    }

    public function addComment(int $postId, int $userId, string $content): void
    {
        $cleanContent = trim(strip_tags($content));
        if (!empty($cleanContent)) {
            $this->commentModel->add($postId, $userId, $cleanContent);
            $this->postModel->updateActivity($postId);
        }
    }

    public function updatePost(int $postId, int $userId, string $rawContent): void
    {
        $cleanContent = strip_tags($rawContent, '<p><br><b><i><u><strong><em><a><ul><ol><li><s><strike><span>');

        if (!empty(trim(strip_tags($cleanContent)))) {
            $this->postModel->updateContent($postId, $userId, $cleanContent);
        }
    }

    private function handleImageUploads(int $postId, array $files): void
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/posts/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        foreach ($files['tmp_name'] as $key => $tmpName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = mime_content_type($tmpName);
                if (in_array($fileType, $allowedTypes)) {
                    $ext = pathinfo($files['name'][$key], PATHINFO_EXTENSION);
                    $fileName = uniqid('post_' . $postId . '_') . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                        $this->postModel->addImage($postId, '/uploads/posts/' . $fileName);
                    }
                }
            }
        }
    }
}