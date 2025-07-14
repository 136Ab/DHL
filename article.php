<?php
// Start session
session_start();

// Include database connection
require_once 'db.php';

// Initialize error messages
$error_message = '';
$comment_error = '';

// Validate article ID
$article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($article_id === false || $article_id <= 0) {
    error_log("Invalid article ID: " . ($_GET['id'] ?? 'missing'));
    $_SESSION['error_message'] = "Invalid article ID. Please select a valid article.";
    header("Location: index.php");
    exit;
}

// Fetch article
try {
    $article_sql = "SELECT id, title, content, category, author, image_url, created_at 
                    FROM news 
                    WHERE id = ?";
    $stmt = $conn->prepare($article_sql);
    if (!$stmt) {
        throw new Exception("Article query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $article_result = $stmt->get_result();
    $article = $article_result->fetch_assoc();
    $stmt->close();

    if (!$article) {
        error_log("Article not found for ID: $article_id");
        $_SESSION['error_message'] = "Article not found.";
        header("Location: index.php");
        exit;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $error_message = "Sorry, we're experiencing technical difficulties. Please try again later.";
}

// Fetch related news
$related_result = null;
if (empty($error_message)) {
    try {
        $related_sql = "SELECT id, title, image_url 
                        FROM news 
                        WHERE category = ? AND id != ? 
                        ORDER BY created_at DESC 
                        LIMIT 3";
        $stmt = $conn->prepare($related_sql);
        if (!$stmt) {
            throw new Exception("Related news query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("si", $article['category'], $article_id);
        $stmt->execute();
        $related_result = $stmt->get_result();
        $stmt->close();
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

// Fetch comments
$comments_result = null;
if (empty($error_message)) {
    try {
        $comments_sql = "SELECT author, comment, created_at 
                         FROM comments 
                         WHERE article_id = ? 
                         ORDER BY created_at DESC";
        $stmt = $conn->prepare($comments_sql);
        if (!$stmt) {
            throw new Exception("Comments query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $comments_result = $stmt->get_result();
        $stmt->close();
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
        $comment_error = "Please login to post a comment.";
        error_log("Comment submission failed: User not logged in.");
    } else {
        $comment = trim(filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING));
        if (empty($comment)) {
            $comment_error = "Comment cannot be empty.";
            error_log("Comment submission failed: Empty comment.");
        } else {
            try {
                $insert_sql = "INSERT INTO comments (article_id, user_id, author, comment, created_at) 
                               VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($insert_sql);
                if (!$stmt) {
                    throw new Exception("Comment insert query preparation failed: " . $conn->error);
                }
                $user_id = $_SESSION['user_id'];
                $author = $_SESSION['user_name'];
                $stmt->bind_param("iiss", $article_id, $user_id, $author, $comment);
                if (!$stmt->execute()) {
                    throw new Exception("Comment insertion failed: " . $stmt->error);
                }
                $stmt->close();
                header("Location: article.php?id=$article_id");
                exit;
            } catch (Exception $e) {
                error_log($e->getMessage());
                $comment_error = "Failed to post comment. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title'] ?? 'NewsHub'); ?> - NewsHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }

        .header {
            background-color: #cc0000;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: bold;
        }

        .search-box {
            display: flex;
        }

        .search-box input {
            padding: 0.5rem;
            font-size: 1rem;
            border: none;
            border-radius: 4px 0 0 4px;
        }

        .search-box button {
            padding: 0.5rem 1rem;
            background-color: #333333;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 1rem;
        }

        .search-box button:hover {
            background-color: #555555;
        }

        .article-section {
            margin: 2rem;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .article-section img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .article-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #222222;
        }

        .article-section .meta {
            font-size: 1rem;
            color: #666666;
            margin-bottom: 1.5rem;
        }

        .article-section p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333333;
            margin-bottom: 1rem;
        }

        .related-section {
            margin: 2rem;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .related-section h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #222222;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .related-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .related-card:hover {
            transform: translateY(-5px);
        }

        .related-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .related-card h4 {
            font-size: 1.2rem;
            padding: 0.8rem;
            color: #222222;
        }

        .comments-section {
            margin: 2rem;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .comments-section h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #222222;
        }

        .comment-form {
            margin-bottom: 1.5rem;
        }

        .comment-form textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 1rem;
        }

        .comment-form button {
            padding: 0.8rem 1.5rem;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }

        .comment-form button:hover {
            background-color: #aa0000;
        }

        .comment-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eeeeee;
        }

        .comment-item .author {
            font-weight: bold;
            color: #222222;
            margin-bottom: 0.5rem;
        }

        .comment-item p {
            color: #333333;
            font-size: 1rem;
        }

        .error-message {
            color: #cc0000;
            text-align: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .login-message {
            text-align: center;
            color: #cc0000;
            font-size: 1rem;
        }

        .login-message a {
            color: #cc0000;
            text-decoration: none;
        }

        .login-message a:hover {
            text-decoration: underline;
        }

        .no-comments {
            text-align: center;
            color: #666666;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .article-section img {
                max-height: 300px;
            }

            .related-card img {
                height: 120px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .article-section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>NewsHub</h1>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search articles...">
            <button onclick="searchArticles()">Search</button>
        </div>
    </header>

    <?php if (!empty($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php else: ?>
        <section class="article-section">
            <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="Article Image">
            <h2><?php echo htmlspecialchars($article['title']); ?></h2>
            <div class="meta">
                By <?php echo htmlspecialchars($article['author']); ?> | 
                <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
            </div>
            <p><?php echo nl2br(htmlspecialchars($article['content'])); ?></p>
        </section>

        <section class="related-section">
            <h3>Related News</h3>
            <div class="related-grid">
                <?php if ($related_result && $related_result->num_rows > 0): ?>
                    <?php while ($news = $related_result->fetch_assoc()): ?>
                        <div class="related-card" onclick="redirectTo('article.php?id=<?php echo $news['id']; ?>')">
                            <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="Related News">
                            <h4><?php echo htmlspecialchars($news['title']); ?></h4>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No related articles found.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="comments-section">
            <h3>Comments</h3>
            <?php if (!empty($comment_error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($comment_error); ?></p>
            <?php endif; ?>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <p class="login-message">
                    Please <a href="#" onclick="redirectTo('login.php')">login</a> to post a comment.
                </p>
            <?php else: ?>
                <div class="comment-form">
                    <form method="POST">
                        <textarea name="comment" placeholder="Write your comment..." rows="5" required></textarea>
                        <button type="submit">Submit Comment</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php if ($comments_result && $comments_result->num_rows > 0): ?>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment-item">
                        <div class="author">
                            <?php echo htmlspecialchars($comment['author']); ?> - 
                            <?php echo date('F j, Y, H:i', strtotime($comment['created_at'])); ?>
                        </div>
                        <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comments">No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <script>
        function redirectTo(page) {
            window.location.href = page;
        }

        function searchArticles() {
            const query = document.getElementById('searchInput').value;
            if (query.trim()) {
                redirectTo(`search.php?q=${encodeURIComponent(query)}`);
            }
        }
    </script>
</body>
</html>
