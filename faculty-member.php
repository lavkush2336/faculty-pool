<?php
// all-faculty.php - Displays all faculty members in a responsive grid with search functionality

require_once 'db.php'; // must define $pdo (PDO connection)

// Safe HTML escape
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Check for search query
$searchName = filter_input(INPUT_GET, 'search_name', FILTER_SANITIZE_STRING);
$searchCondition = '';
$searchParams = [];

if ($searchName) {
    // Modify the query to filter by name (first_name or last_name)
    // Using CONCAT for full name search for a better user experience
    $searchCondition = " WHERE CONCAT(F.first_name, ' ', F.last_name) LIKE :searchName OR F.last_name LIKE :searchName";
    $searchParams = [':searchName' => '%' . $searchName . '%'];
}

// 1. Fetch Filtered Faculty Members
$sql = "
    SELECT 
        F.faculty_id, F.first_name, F.last_name, F.email, F.expertise, F.Image, F.department
    FROM Faculty F 
    {$searchCondition}
    ORDER BY F.department ASC, F.last_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($searchParams);
$facultyList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultCount = count($facultyList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=5.0, user-scalable=yes" />
  <meta name="description" content="All Faculty Members - Faculty Pool - Thapar Institute" />
  <meta name="theme-color" content="#8B0000" />
  <title>All Faculty Members</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Custom CSS (Assuming styles.css contains core nav/hero styles) -->
  <link rel="stylesheet" href="styles.css">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <!-- AOS -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    /* Global Styles for Consistency */
    .faculty-page-body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 50%, #f0f4f8 100%);
    }

    .section-title {
        color: #8B0000;
        font-size: 2.25rem;
        font-weight: 700;
        margin-bottom: 2rem;
        border-bottom: 4px solid rgba(139, 0, 0, 0.1);
        padding-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Grid Layout: 4 columns on desktop, 2 on tablet, 1 on mobile */
    .faculty-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); /* Responsive grid */
        gap: 2rem;
        margin-top: 2rem;
    }

    /* Faculty Card Styles (Small Grid Card) */
    .faculty-grid-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(139, 0, 0, 0.1);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .faculty-grid-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(139, 0, 0, 0.15);
    }

    .grid-image-container {
        position: relative;
        height: 200px;
        background: #fdf2f2;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }

    .grid-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .grid-info {
        flex-grow: 1;
        padding: 15px;
        display: flex;
        flex-direction: column;
    }

    .grid-info h3 {
        font-weight: 700;
        color: #8B0000;
        font-size: 1.25rem;
        margin-bottom: 3px;
        line-height: 1.3;
    }

    .grid-info .department-name {
        font-weight: 500;
        color: #444;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }

    .grid-info .expertise-pill {
        display: inline-block;
        background: #fde4e4;
        color: #8B0000;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-right: 4px;
        margin-top: 4px;
        white-space: nowrap;
    }

    .grid-info .expertise-section {
        margin-top: 10px;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }
    
    .grid-info .expertise-title {
        font-weight: 600;
        color: #A52A2A;
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    /* Search Bar Styling */
    .search-form-container {
        flex-grow: 1;
        display: flex;
        justify-content: flex-end;
    }

    .search-input {
        padding: 6px 12px;
        border: 1px solid #ccc;
        border-radius: 8px 0 0 8px;
        outline: none;
        width: 180px; /* Adjust width as needed */
        transition: width 0.3s ease;
        font-size: 0.9rem;
    }

    .search-input:focus {
        border-color: #8B0000;
        width: 250px;
    }

    .search-button {
        background-color: #8B0000;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 0 8px 8px 0;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-size: 0.9rem;
    }

    .search-button:hover {
        background-color: #A52A2A;
    }

    /* Responsive adjustments for navigation */
    @media (max-width: 768px) {
        .creative-nav .flex-wrap {
            flex-direction: column;
            align-items: center;
        }
        .creative-nav .flex.space-x-8 {
            justify-content: center;
            margin-bottom: 10px;
        }
        .search-form-container {
            width: 100%;
            justify-content: center;
            margin-top: 10px;
        }
        .search-input {
            width: 70%;
        }
        .search-input:focus {
            width: 70%; /* Keep width fixed on mobile */
        }
    }
    
  </style>
</head>
<body class="faculty-page-body bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

  <!-- Navigation -->
  <nav class="creative-nav">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center py-3 flex-wrap">
        <div class="flex space-x-8">
          <a href="index.php" class="nav-item">HOME</a>
          <a href="departments.php" class="nav-item">DEPARTMENTS</a>
          <a href="all-faculty.php" class="nav-item active">FACULTY</a>
        </div>
        
        <!-- Search Bar Form -->
        <div class="search-form-container">
            <form method="GET" action="all-faculty.php" class="flex">
                <input 
                    type="search" 
                    name="search_name" 
                    placeholder="Search by name..." 
                    class="search-input"
                    value="<?php echo e($searchName); ?>"
                >
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <!-- End Search Bar Form -->

      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="hero-content">
      <h1 class="hero-title">Institute Faculty</h1>
      <p class="hero-subtitle">Meet our esteemed faculty members across all departments</p>
      <div class="hero-decoration">
        <div class="decoration-line"></div>
        <div class="decoration-dot"></div>
        <div class="decoration-line"></div>
      </div>
    </div>
  </section>

  <!-- Faculty Grid Section -->
  <section class="main-content py-8">
    <div class="max-w-7xl mx-auto px-4">
      <h2 class="section-title" data-aos="fade-up">
        <i class="fas fa-users-viewfinder"></i> Directory (<?php echo $resultCount; ?> Members)
        <?php if ($searchName): ?>
            <span class="text-lg text-gray-600 font-normal ms-2">
                &mdash; Showing results for "**<?php echo e($searchName); ?>**"
            </span>
            <a href="all-faculty.php" class="text-sm text-red-600 hover:text-red-800 ms-3">
                Clear Search
            </a>
        <?php endif; ?>
      </h2>

      <div class="faculty-grid" id="facultyGrid">
        <?php if (empty($facultyList)): ?>
          <div class="text-center text-muted py-8 col-span-full">
            No faculty members were found matching your search criteria.
          </div>
        <?php else: ?>
          <?php foreach ($facultyList as $faculty): 
            $fullName = e($faculty['first_name']) . ' ' . e($faculty['last_name']);
            // Fallback image using placeholder service
            $imageUrl = $faculty['Image'] ?: 'https://placehold.co/400x300/8B0000/ffffff?text=' . urlencode('No%20Image');
          ?>
            <!-- Faculty Grid Card -->
            <a href="faculty-member.php?id=<?php echo e($faculty['faculty_id']); ?>" class="faculty-grid-card" data-aos="fade-up" data-aos-delay="100">
                <div class="grid-image-container">
                    <img src="<?php echo $imageUrl; ?>" alt="<?php echo $fullName; ?>" class="grid-image" onerror="this.onerror=null;this.src='https://placehold.co/400x300/CCCCCC/333333?text=Image%20Missing';">
                </div>
                <div class="grid-info">
                    <h3><?php echo $fullName; ?></h3>
                    <div class="department-name">
                        <i class="fas fa-building me-1"></i><?php echo e($faculty['department']); ?>
                    </div>
                    
                    <div class="expertise-section mt-auto">
                        <div class="expertise-title">Expertise:</div>
                        <div>
                            <?php 
                            $expertiseList = explode(',', $faculty['expertise']);
                            $count = 0;
                            $displayed_expertises = [];

                            foreach($expertiseList as $exp) {
                                $exp = trim($exp);
                                if ($exp && $count < 3) { // Show max 3 pills
                                    $displayed_expertises[] = e($exp);
                                    $count++;
                                }
                            }
                            
                            echo implode('', array_map(fn($exp) => '<span class="expertise-pill">' . $exp . '</span>', $displayed_expertises));

                            if (count($expertiseList) > 3) {
                                echo '<span class="expertise-pill">...</span>';
                            } elseif (empty($displayed_expertises)) {
                                echo '<span class="text-muted text-sm">N/A</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="mt-8 flex gap-4 items-center">
        <a href="index.php" class="creative-button" data-aos="fade-up">
          <i class="fas fa-arrow-left"></i><span>Back to Home</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="creative-footer">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
      <p>&copy; <?php echo date('Y'); ?> Thapar Institute of Engineering & Technology. All rights reserved.</p>
    </div>
  </footer>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      AOS && AOS.init && AOS.init({ duration: 700, easing: 'ease-in-out', once: true, offset: 100 });
    });
  </script>
</body>
</html>