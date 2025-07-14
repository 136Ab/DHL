<?php
session_start();
require_once 'db.php';

$category = isset($_GET['cat']) ? $_GET['cat'] : '';
$category_query = "SELECT * FROM news WHERE category = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($category_query);
$stmt->bind_param("s", $category);
$stmt->execute();
$news_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category); ?> - NewsHub</title>
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
            text-align: center;
        }

        header h1 {
            font-size: 2em;
        }

        .category-section {
            margin: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .category-section h2 {
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

        @media (max-width: 768px) {
            .news-card img {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($category); ?> News</h1>
    </header>

    <section class="category-section">
        <h2><?php echo htmlspecialchars($category); ?></h2>
        <div class="news-grid">
            <?php while ($news = $news_result->fetch_assoc()): ?>
                <div class="news-card" onclick="redirectTo('article.php?id=<?php echo $news['id']; ?>')">
                    <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="News Image">
                    <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                    <p><?php echo substr(htmlspecialchars($news['content']), 0, 100) . '...'; ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <script>
        function redirectTo(page) {
            window.location.href = page;
        }
    </script>
</body>
</html>
