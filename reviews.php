<?php
/**
 * Product Reviews Page
 * 
 * Displays all reviews for products with filtering and sorting options
 */

require_once __DIR__ . '/php/db_connect.php';
require_once __DIR__ . '/php/helpers.php';

// Get filter parameters
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$sortBy = $_GET['sort'] ?? 'recent'; // 'recent', 'rating_high', 'rating_low'
$minRating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;

// Build query
$where = "1=1";
$params = [];
$types = '';

if ($productId > 0) {
    $where .= " AND r.product_id = ?";
    $params[] = $productId;
    $types .= 'i';
}

if ($minRating > 0) {
    $where .= " AND r.rating >= ?";
    $params[] = $minRating;
    $types .= 'i';
}

$orderBy = "r.created_at DESC";
switch ($sortBy) {
    case 'rating_high':
        $orderBy = "r.rating DESC, r.created_at DESC";
        break;
    case 'rating_low':
        $orderBy = "r.rating ASC, r.created_at DESC";
        break;
    case 'recent':
    default:
        $orderBy = "r.created_at DESC";
        break;
}

// Get reviews
$sql = "SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        JOIN products p ON p.id = r.product_id
        WHERE $where
        ORDER BY $orderBy";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $reviews = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    if ($result) $result->close();
}

// Get all products for filter dropdown
$productsResult = $conn->query("SELECT id, name FROM products ORDER BY name");
$allProducts = $productsResult ? $productsResult->fetch_all(MYSQLI_ASSOC) : [];
if ($productsResult) $productsResult->close();

// Get statistics
$statsSql = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    COUNT(DISTINCT product_id) as products_reviewed,
    COUNT(DISTINCT user_id) as reviewers
    FROM reviews";
if ($productId > 0) {
    $statsSql .= " WHERE product_id = ?";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->bind_param('i', $productId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $statsStmt->close();
} else {
    $statsResult = $conn->query($statsSql);
    $stats = $statsResult ? $statsResult->fetch_assoc() : ['total_reviews' => 0, 'avg_rating' => 0, 'products_reviewed' => 0, 'reviewers' => 0];
    if ($statsResult) $statsResult->close();
}

// Get rating distribution
$ratingDistSql = "SELECT rating, COUNT(*) as count FROM reviews";
if ($productId > 0) {
    $ratingDistSql .= " WHERE product_id = ?";
}
$ratingDistSql .= " GROUP BY rating ORDER BY rating DESC";

if ($productId > 0) {
    $distStmt = $conn->prepare($ratingDistSql);
    $distStmt->bind_param('i', $productId);
    $distStmt->execute();
    $distResult = $distStmt->get_result();
    $ratingDistribution = $distResult ? $distResult->fetch_all(MYSQLI_ASSOC) : [];
    $distStmt->close();
} else {
    $distResult = $conn->query($ratingDistSql);
    $ratingDistribution = $distResult ? $distResult->fetch_all(MYSQLI_ASSOC) : [];
    if ($distResult) $distResult->close();
}

renderHead('Product Reviews | PhoneFix+');
renderNav();
renderFlashMessages([
    'review_success' => 'success',
    'review_errors' => 'error'
]);
?>

<link rel="stylesheet" href="css/orders.css">
<style>
.reviews-page {
    padding: 2rem 0;
}

.reviews-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.reviews-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
}

.reviews-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    color: #667eea;
}

.stat-card p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filters-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
}

.rating-distribution {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.rating-distribution h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.rating-label {
    min-width: 80px;
    font-weight: 500;
}

.rating-bar-fill {
    flex: 1;
    height: 24px;
    background: #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.rating-bar-progress {
    height: 100%;
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
    transition: width 0.3s ease;
}

.rating-count {
    min-width: 60px;
    text-align: right;
    color: #6b7280;
    font-size: 0.875rem;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.review-user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.product-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border-radius: 20px;
    font-size: 0.875rem;
    color: #374151;
}

.product-badge img {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    object-fit: cover;
}

.review-rating {
    display: flex;
    gap: 0.25rem;
}

.review-rating .star {
    color: #fbbf24;
    font-size: 1.25rem;
}

.review-date {
    color: #6b7280;
    font-size: 0.875rem;
}

.review-comment {
    color: #374151;
    line-height: 1.6;
    margin-top: 0.75rem;
}

.empty-reviews {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.empty-reviews-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-reviews h2 {
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.empty-reviews p {
    color: #6b7280;
    margin: 0;
}
</style>

<main class="page reviews-page">
    <section class="reviews-header">
        <div class="container">
            <h1>Product Reviews</h1>
            <p>See what our customers are saying about our products</p>
        </div>
    </section>

    <section class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo (int)$stats['total_reviews']; ?></h3>
                <p>Total Reviews</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format((float)$stats['avg_rating'], 1); ?></h3>
                <p>Average Rating</p>
            </div>
            <div class="stat-card">
                <h3><?php echo (int)$stats['products_reviewed']; ?></h3>
                <p>Products Reviewed</p>
            </div>
            <div class="stat-card">
                <h3><?php echo (int)$stats['reviewers']; ?></h3>
                <p>Reviewers</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-row">
                <div class="filter-group">
                    <label for="product_id">Filter by Product:</label>
                    <select name="product_id" id="product_id">
                        <option value="0">All Products</option>
                        <?php foreach ($allProducts as $product): ?>
                            <option value="<?php echo (int)$product['id']; ?>" <?php echo $productId === (int)$product['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="min_rating">Minimum Rating:</label>
                    <select name="min_rating" id="min_rating">
                        <option value="0">All Ratings</option>
                        <option value="5" <?php echo $minRating === 5 ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $minRating === 4 ? 'selected' : ''; ?>>4+ Stars</option>
                        <option value="3" <?php echo $minRating === 3 ? 'selected' : ''; ?>>3+ Stars</option>
                        <option value="2" <?php echo $minRating === 2 ? 'selected' : ''; ?>>2+ Stars</option>
                        <option value="1" <?php echo $minRating === 1 ? 'selected' : ''; ?>>1+ Stars</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort">
                        <option value="recent" <?php echo $sortBy === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                        <option value="rating_high" <?php echo $sortBy === 'rating_high' ? 'selected' : ''; ?>>Highest Rating</option>
                        <option value="rating_low" <?php echo $sortBy === 'rating_low' ? 'selected' : ''; ?>>Lowest Rating</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-primary" style="width: 100%;">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Rating Distribution -->
        <?php if (!empty($ratingDistribution)): ?>
            <div class="rating-distribution">
                <h3>Rating Distribution</h3>
                <?php 
                $totalForDist = array_sum(array_column($ratingDistribution, 'count'));
                foreach ($ratingDistribution as $dist): 
                    $percentage = $totalForDist > 0 ? ($dist['count'] / $totalForDist) * 100 : 0;
                ?>
                    <div class="rating-bar">
                        <div class="rating-label"><?php echo (int)$dist['rating']; ?> Stars</div>
                        <div class="rating-bar-fill">
                            <div class="rating-bar-progress" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="rating-count"><?php echo (int)$dist['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <?php if (empty($reviews)): ?>
            <div class="empty-reviews">
                <div class="empty-reviews-icon">⭐</div>
                <h2>No Reviews Found</h2>
                <p>There are no reviews matching your criteria. Try adjusting your filters.</p>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-user-info">
                                <div>
                                    <strong style="font-size: 1.1rem; display: block; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($review['user_name']); ?>
                                    </strong>
                                    <div class="product-badge">
                                        <img src="/systemFinals/<?php echo htmlspecialchars($review['product_image']); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                        <span><?php echo htmlspecialchars($review['product_name']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= (int)$review['rating']; $i++): ?>
                                        <span class="star" style="color: #fbbf24;">⭐</span>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-date">
                                    <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <div class="review-comment">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
renderFooter();
?>

