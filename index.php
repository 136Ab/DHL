<?php
session_start();
require_once 'db.php';

$error_message = '';
try {
    $featured_sql = "SELECT id, title, content, category, author, image_url 
                     FROM news 
                     WHERE featured = TRUE 
                     ORDER BY created_at DESC 
                     LIMIT 3";
    $featured_result = $conn->query($featured_sql);
    if (!$featured_result) {
        throw new Exception("Featured news query failed: " . $conn->error);
    }

    $categories = ['World', 'Technology', 'Sports', 'Entertainment', 'Business'];
    $category_news = [];
    foreach ($categories as $category) {
        $category_sql = "SELECT id, title, image_url 
                         FROM news 
                         WHERE category = ? 
                         ORDER BY created_at DESC 
                         LIMIT 4";
        $stmt = $conn->prepare($category_sql);
        if (!$stmt) {
            throw new Exception("Category query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $category_news[$category] = $stmt->get_result();
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching news: " . $e->getMessage());
    $error_message = "Sorry, we're experiencing technical difficulties. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NewsHub</title>
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

        .featured-section, .category-section {
            margin: 2rem;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .featured-section h2, .category-section h2 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #222222;
        }

        .featured-grid, .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .news-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
        }

        .news-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .news-card h3 {
            font-size: 1.4rem;
            padding: 0.8rem;
            color: #222222;
        }

        .news-card p {
            padding: 0 0.8rem 0.8rem;
            color: #333333;
            font-size: 1rem;
        }

        .error-message {
            color: #cc0000;
            text-align: center;
            margin: 2rem;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .news-card img {
                height: 150px;
            }

            .header h1 {
                font-size: 1.8rem;
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

    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
        <?php unset($_SESSION['error_message']); ?>
    <?php elseif (!empty($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if (empty($error_message) && $featured_result): ?>
        <section class="featured-section">
            <h2>Featured News</h2>
            <div class="featured-grid">
                <?php while ($news = $featured_result->fetch_assoc()): ?>
                    <div class="news-card" onclick="redirectTo('article.php?id=<?php echo $news['id']; ?>')">
                        <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="News Image">
                        <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p><?php echo substr(htmlspecialchars($news['content']), 0, 100) . '...'; ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <?php foreach ($categories as $category): ?>
            <?php $result = $category_news[$category]; ?>
            <?php if ($result && $result->num_rows > 0): ?>
                <section class="category-section">
                    <h2><?php echo htmlspecialchars($category); ?></h2>
                    <div class="category-grid">
                        <?php while ($news = $result->fetch_assoc()): ?>
                            <div class="news-card" onclick="redirectTo('article.php?id=<?php echo $news['id']; ?>')">
                                <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="News Image">
                                <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
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
