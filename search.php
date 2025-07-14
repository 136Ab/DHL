<?php
session_start();
require_once 'db.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_query = "%$search_query%";
$search_sql = "SELECT id, title, content, category, author, image_url, created_at 
               FROM news 
               WHERE title LIKE ? OR content LIKE ? 
               ORDER BY created_at DESC 
               LIMIT 12"; // Limit to 12 results for performance
$stmt = $conn->prepare($search_sql);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param("ss", $search_query, $search_query);
$stmt->execute();
$search_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - NewsHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f4f4f4;
        }

        header {
            background-color: #c00;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 2em;
        }

        .search-bar {
            display: flex;
            align-items: center;
        }

        .search-bar input {
            padding: 8px;
            border: none;
            border-radius: 4px 0 0 4px;
            font-size: 1em;
        }

        .search-bar button {
            padding: 8px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }

        .search-bar button:hover {
            background-color: #555;
        }

        .search-results {
            margin: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .search-results h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
            color: #333;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .news-card {
            background-color: #fff;
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
            font-size: 1.4em;
            padding: 10px;
            color: #333;
        }

        .news-card p {
            padding: 0 10px 10px;
            color: #666;
            font-size: 1em;
        }

        .no-results {
            text-align: center;
            color: #666;
            font-size: 1.2em;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 10px;
            }

            .news-card img {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>NewsHub</h1>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search articles..." value="<?php echo htmlspecialchars($_GET['q']); ?>">
            <button onclick="searchArticles()">Search</button>
        </div>
    </header>

    <section class="search-results">
        <h2>Results for "<?php echo htmlspecialchars($_GET['q']); ?>"</h2>
        <?php if ($search_result->num_rows > 0): ?>
            <div class="news-grid">
                <?php while ($news = $search_result->fetch_assoc()): ?>
                    <div class="news-card" onclick="redirectTo('article.php?id=<?php echo $news['id']; ?>')">
                        <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="News Image">
                        <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p><?php echo substr(htmlspecialchars($news['content']), 0, 100) . '...'; ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-results">No articles found matching your search.</p>
        <?php endif; ?>
    </section>

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
