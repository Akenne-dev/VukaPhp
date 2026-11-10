<?php
// index.php
session_start();
require_once 'database.php';

// Keep the landing page in sync with the dashboard's "Latest jobs for you" list.
$latestJobs = $conn->query(
    'SELECT id, title, company, location, job_type, created_at FROM jobs ORDER BY created_at DESC LIMIT 4'
);
$jobCount = (int) ($conn->query('SELECT COUNT(*) AS total FROM jobs')->fetch_assoc()['total'] ?? 0);
$keyword = trim($_GET['keyword'] ?? '');
$location = trim($_GET['location'] ?? '');
$searchResults = null;
if ($keyword !== '' || $location !== '') {
    $filters = [];
    if ($keyword !== '') {
        $term = '%' . $conn->real_escape_string($keyword) . '%';
        $filters[] = "(title LIKE '$term' OR company LIKE '$term' OR job_type LIKE '$term' OR description LIKE '$term')";
    }
    if ($location !== '') {
        $place = '%' . $conn->real_escape_string($location) . '%';
        $filters[] = "location LIKE '$place'";
    }
    $searchResults = $conn->query('SELECT id, title, company, location, job_type, created_at FROM jobs WHERE ' . implode(' AND ', $filters) . ' ORDER BY created_at DESC');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VUKA - Find your dream job in Nigeria</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Modern typography using Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa; 
            margin: 0;
        }
        .text-black { color: #000 !important; }
        .bg-vuka-cream { background-color: #FF7C19; } /* Orange for Latest Jobs */
        .hover-shadow:hover {
            transform: translateY(-4px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
        }
        .job-listing-card { color: inherit; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; border: 1px solid transparent !important; }
        .job-listing-card:hover { transform: translateY(-5px); box-shadow: 0 1rem 2.5rem rgba(23,22,21,.14) !important; border-color: #ff6b00 !important; }
        .company-mark { display: inline-grid; place-items: center; width: 48px; height: 48px; flex: 0 0 48px; border-radius: 14px; background: #fff0e7; color: #c95300; font-size: 1.1rem; font-weight: 900; }
        .job-type-badge { display: inline-flex; width: fit-content; padding: .45rem .75rem; border-radius: 999px; background: #eef4ff; color: #245fc0; font-size: .78rem; font-weight: 800; }
        .bg-vuka-orange { background: linear-gradient(to bottom, #FF6B00, #FF8020); } /* Vibrant orange to slightly lighter orange */
        .text-vuka-orange { color: #FF6B00; }
        .bg-vuka-light { background-color: #FFF5F0; }
        
        .btn-vuka-orange { 
            background-color: #FF6B00; 
            color: #000;
            font-weight: bold;
        }
        .btn-vuka-orange:hover {
            background-color: #E56000;
            color: #000;
        }

        .navbar {
            background-color: #fff !important;
            z-index: 1050;
            position: relative;
        }

        .main-wrapper {
            min-height: calc(100vh - 74px);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .hero-section {
            flex-grow: 1;
            display: flex;
            align-items: center;
        }

        /* Floating Cards Adjusted Position */
        .cards-container {
            position: relative;
            width: 100%;
            height: 380px; 
            max-width: 480px;
            margin: 0 auto;
        }
        
        .floating-card {
            position: absolute;
            width: 200px;
            background: #fff;
            border-radius: 1.2rem;
            padding: 0.5rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,.15);
            transition: transform 0.3s ease;
            z-index: 20;
        }
        .floating-card:hover {
            transform: scale(1.05) !important;
            z-index: 40 !important;
        }
        .floating-card img {
            width: 100%;
            height: 125px;
            object-fit: cover;
            border-radius: 0.8rem;
            margin-bottom: 0.5rem;
        }
        .card-badge {
            position: absolute;
            bottom: 4.8rem;
            background: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 50rem;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.1);
        }
        
        /* 1st Card: Top Left */
        .card-1 {
            top: 10px;
            left: 0;
            transform: rotate(-6deg);
        }
        /* 2nd Card: Top Right */
        .card-2 {
            top: 10px;
            right: 0;
            transform: rotate(6deg);
        }
        /* 3rd Card: Bottom Center (Lower between the two) */
        .card-3 {
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%) rotate(0deg);
            z-index: 30;
        }

        .search-box-container {
            margin-bottom: 3vh; /* Use viewport height for responsive spacing at the bottom */
            z-index: 100;
        }
        .search-box {
            border-radius: 1.5rem;
            box-shadow: 0 0.5rem 2rem rgba(0,0,0,.08);
        }

        /* Site footer */
        .site-footer {
            background: #171615;
            color: #f5f5f5;
        }
        .footer-main { padding: 4.5rem 0 3.25rem; }
        .footer-links-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 2rem;
        }
        .footer-heading {
            color: #a7a7a7;
            font-size: .9rem;
            font-weight: 700;
            margin: 0 0 1.1rem;
        }
        .footer-links { list-style: none; margin: 0; padding: 0; }
        .footer-links li + li { margin-top: .75rem; }
        .site-footer a { color: #f2f2f2; text-decoration: none; }
        .site-footer a:hover { color: #ff6b00; }
        .footer-divider { border: 0; border-top: 1px solid #343434; margin: 3.5rem 0; opacity: 1; }
        .footer-brand-row {
            display: grid;
            grid-template-columns: minmax(130px, .75fr) minmax(260px, 1.4fr) minmax(180px, .8fr);
            align-items: center;
            gap: 2.5rem;
        }
        .footer-logo {
            color: #ff6b00 !important;
            font-size: 2.25rem;
            font-weight: 900;
            letter-spacing: -.08em;
        }
        .footer-mission { color: #c5c5c5; font-size: .95rem; line-height: 1.6; margin: 0; }
        .footer-social { justify-self: end; }
        .footer-social-title { color: #a7a7a7; font-size: .9rem; font-weight: 700; margin: 0 0 .85rem; }
        .social-links { display: flex; gap: .9rem; }
        .social-links a { display: inline-flex; align-items: center; justify-content: center; color: #f5f5f5; }
        .social-links svg { width: 19px; height: 19px; fill: currentColor; }
        .footer-bottom { background: #0a0a0a; padding: 1.35rem 0; }
        .footer-bottom-inner { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
        .footer-copyright, .footer-legal { color: #a7a7a7; font-size: .8rem; }
        .footer-legal { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 1.2rem; }
        .footer-legal a { color: #a7a7a7; }

        @media (max-width: 767.98px) {
            .footer-main { padding: 3.5rem 0 2.5rem; }
            .footer-links-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2.25rem 1.5rem; }
            .footer-divider { margin: 2.75rem 0; }
            .footer-brand-row { grid-template-columns: 1fr; gap: 1.5rem; }
            .footer-social { justify-self: start; }
            .footer-bottom-inner { align-items: flex-start; flex-direction: column; }
            .footer-legal { justify-content: flex-start; gap: .75rem 1.2rem; }
        }
    </style>
</head>
<body>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-md border-bottom py-3">
        <div class="container px-4 px-md-5">
            <a class="navbar-brand fw-black fs-3 text-black text-decoration-none" style="font-weight: 900; letter-spacing: -0.05em;" href="#">VUKA</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-md-0 gap-3 ms-md-4">
                    <li class="nav-item">
                        <a class="nav-link text-black fw-semibold d-flex align-items-center gap-1" href="#">
                            For job seekers
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" 10stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-black fw-semibold d-flex align-items-center gap-1" href="#">
                            For employers
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </a>
                    </li> 
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-black text-decoration-none fw-semibold d-flex align-items-center gap-1 p-0">
                        🇳🇬 Nigeria
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <?php if (isset($_SESSION['loggedinEmail'])): ?>
                        <span class="fw-semibold small">Hi, <?php echo htmlspecialchars($_SESSION['userName'] ?? 'there'); ?></span>
                        <a href="logout.php" class="btn btn-outline-dark text-black fw-bold rounded-pill px-4">Log out</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-dark text-black fw-bold rounded-pill px-4">Login</a>
                        <a href="signup.php" class="btn btn-dark fw-bold rounded-pill px-4">Join now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper (Flex to fill remaining height) -->
    <div class="main-wrapper bg-vuka-orange">
        
        <!-- Hero Section -->
        <div class="hero-section container px-4 px-md-5">
            <div class="row w-100 align-items-center justify-content-between">
                
                <!-- Hero Text Content -->
                <div class="col-md-6 position-relative z-1 mb-4 mb-md-0">
                    <h1 class="display-4 fw-bold text-black mb-3" style="letter-spacing: -0.05em; line-height: 1.1;">
                        <!-- Find your dream  job in Nigeria with VUKA -->
                        Find your dream  job in Nigeria 
                    </h1>
                    <p class="fs-6 text-black mb-4" style="opacity: 0.8; font-weight: 500;">
                        Built for job seekers. Trusted by career builders and with VUKAemployers. Get accurate job matches, learning pathways and career advice — for free.
                    </p>
                    <a href="signup.php" class="btn btn-dark fw-bold rounded-pill px-4 py-2 shadow-lg fs-5">
                        Join now
                    </a>
                </div>

                <!-- Hero Visual Cards (Floating avatars) -->
                <div class="col-md-6 d-none d-md-block position-relative">
                    <div class="cards-container">
                        
                        <!-- Card 1 -->
                        <div class="floating-card card-1">
                            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500&h=400&fit=crop" alt="Evelyn">
                            <div class="card-badge border text-black d-flex align-items-center gap-1" style="left: 50%; transform: translateX(-50%);">
                                <span class="bg-success rounded-circle d-inline-block" style="width: 6px; height: 6px;"></span>
                                Hired
                            </div>
                            <div class="px-1">
                                <h6 class="fw-bolder text-black mb-0">Evelyn</h6>
                                <p class="text-secondary fw-medium mb-0" style="font-size: 0.75rem;">Marketing manager</p>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="floating-card card-2">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=500&h=400&fit=crop" alt="Aaliyah">
                            <div class="card-badge border" style="right: 0.5rem; background: #fff3cd; color: #856404; border-color: #ffeeba;">
                                Top match
                            </div>
                            <div class="px-1">
                                <h6 class="fw-bolder text-black mb-0">Aaliyah</h6>
                                <p class="text-secondary fw-medium mb-0" style="font-size: 0.75rem;">Senior Data analyst</p>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="floating-card card-3">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=400&fit=crop" alt="Jacob">
                            <div class="card-badge border" style="left: 0.5rem; background: #cce5ff; color: #004085; border-color: #b8daff;">
                                Interviewing
                            </div>
                            <div class="px-1">
                                <h6 class="fw-bolder text-black mb-0">Jacob</h6>
                                <p class="text-secondary fw-medium mb-0" style="font-size: 0.75rem;">Frontend developer</p>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Box Component -->
        <div class="container search-box-container">
            <div class="bg-white search-box p-3 p-md-4">
                
                <!-- Tabs & Counter -->
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between border-bottom pb-3 mb-3">
                    <!-- Tabs -->
                    <div class="d-flex gap-4">
                        <button class="btn btn-link text-black fw-bold text-decoration-none fs-6 p-0 pb-3" style="border-bottom: 3px solid #000; border-radius: 0; margin-bottom: -1rem;">
                            Browse jobs
                        </button>
                        <button class="btn btn-link text-secondary fw-semibold text-decoration-none fs-6 p-0 pb-3" style="margin-bottom: -1rem;">
                            Hire with Vuka
                        </button>
                    </div>
                    <!-- Counter -->
                    <div class="text-secondary fw-medium small bg-light px-3 py-1 rounded-pill border mt-2 mt-sm-0">
                        <span class="fw-bold text-black"><?php echo $jobCount; ?></span> open jobs to browse
                    </div>
                </div>

                <!-- Inputs Row -->
                <form class="row g-2" method="get" action="index.php" role="search">
                    
                    <!-- Left Input: Keyword Search -->
                    <div class="col-md-5">
                        <div class="d-flex align-items-center bg-vuka-light rounded-3 px-3 py-2 h-100 border">
                            <svg width="18" height="18" class="text-secondary me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="search" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Job title, company or keyword" aria-label="Search by keyword, title or company" class="form-control bg-transparent border-0 shadow-none text-black fw-medium p-0" style="font-size: 0.9rem;">
                        </div>
                    </div>

                    <!-- Middle Input: Location -->
                    <div class="col-md-4">
                        <div class="d-flex align-items-center bg-light rounded-3 px-3 py-2 h-100 border">
                            <svg width="18" height="18" class="text-secondary me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <select name="location" aria-label="Filter by state" class="form-select bg-transparent border-0 shadow-none text-black fw-medium p-0 cursor-pointer" style="font-size: 0.9rem;">
                                <option value="">All locations</option>
                                <option>Abia</option>
                                <option>Adamawa</option>
                                <option>Akwa Ibom</option>
                                <option>Anambra</option>
                                <option>Bauchi</option>
                                <option>Bayelsa</option>
                                <option>Benue</option>
                                <option>Borno</option>
                                <option>Cross River</option>
                                <option>Delta</option>
                                <option>Ebonyi</option>
                                <option>Edo</option>
                                <option>Ekiti</option>
                                <option>Enugu</option>
                                <option>Gombe</option>
                                <option>Imo</option>
                                <option>Jigawa</option>
                                <option>Kaduna</option>
                                <option>Kano</option>
                                <option>Katsina</option>
                                <option>Kebbi</option>
                                <option>Kogi</option>
                                <option>Kwara</option>
                                <option>Lagos</option>
                                <option>Nasarawa</option>
                                <option>Niger</option>
                                <option>Ogun</option>
                                <option>Ondo</option>
                                <option>Osun</option>
                                <option>Oyo</option>
                                <option>Plateau</option>
                                <option>Rivers</option>
                                <option>Sokoto</option>
                                <option>Taraba</option>
                                <option>Yobe</option>
                                <option>Zamfara</option>
                                <option>FCT (Federal Capital Territory)</option>

                            </select>
                        </div>
                    </div>

                    <!-- Right Button: Submit -->
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-vuka-orange w-100 h-100 rounded-3 fs-6 fw-bold py-2 shadow-sm">
                            Find jobs
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <?php if ($searchResults !== null): ?>
        <section id="search-results" class="py-5 bg-light">
            <div class="container px-4 px-md-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <p class="text-vuka-orange fw-bold mb-1">Job search</p>
                        <h2 class="fw-bold text-black mb-0">Search results</h2>
                    </div>
                    <a href="index.php" class="btn btn-outline-dark rounded-3 fw-bold">Clear search</a>
                </div>
                <?php if ($searchResults && $searchResults->num_rows > 0): ?>
                    <p class="text-secondary mb-4"><?php echo $searchResults->num_rows; ?> job<?php echo $searchResults->num_rows === 1 ? '' : 's'; ?> found.</p>
                    <div class="row g-4">
                        <?php while ($job = $searchResults->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <a href="jobs.php?job=<?php echo (int) $job['id']; ?>" class="job-listing-card card h-100 border-0 shadow-sm rounded-4 p-4 text-decoration-none">
                                    <div class="d-flex justify-content-between gap-3 mb-4"><span class="company-mark"><?php echo htmlspecialchars(strtoupper(substr($job['company'], 0, 1))); ?></span><span class="small text-secondary">Posted <?php echo date('j M Y', strtotime($job['created_at'])); ?></span></div>
                                    <h3 class="h5 fw-bold text-black mb-2"><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <p class="fw-bold text-black mb-1"><?php echo htmlspecialchars($job['company']); ?></p>
                                    <p class="text-secondary small mb-4"><?php echo htmlspecialchars($job['location']); ?></p>
                                    <span class="job-type-badge mt-auto"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-4 p-5 text-center shadow-sm">
                        <h3 class="h4 fw-bold text-black">No job found</h3>
                        <p class="text-secondary mb-0">Try a different job title, company, keyword, or state.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Latest Jobs Section -->
    <div class="py-5 bg-vuka-cream">
        <div class="container px-4 px-md-5 py-4">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-black mb-0" style="letter-spacing: -0.03em;">Latest jobs</h2>
                <a href="jobs.php" class="btn btn-light fw-bold text-dark border shadow-sm px-4 py-2 rounded-3">View all jobs</a>
            </div>
            <div class="row g-4">
                <?php if ($latestJobs && $latestJobs->num_rows > 0): ?>
                    <?php while ($job = $latestJobs->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <a href="jobs.php?job=<?php echo (int) $job['id']; ?>" class="job-listing-card card h-100 border-0 shadow-sm rounded-4 p-4 text-decoration-none" style="color: inherit;">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <span class="company-mark"><?php echo htmlspecialchars(strtoupper(substr($job['company'], 0, 1))); ?></span>
                                    <span class="text-secondary small fw-medium mt-1">Posted <?php echo date('j M Y', strtotime($job['created_at'])); ?></span>
                                </div>
                                <h5 class="fw-bold text-black mb-2"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <p class="fw-bold text-black mb-1"><?php echo htmlspecialchars($job['company']); ?></p>
                                <p class="text-secondary small mb-4"><?php echo htmlspecialchars($job['location']); ?></p>
                                <div class="mt-auto"><span class="job-type-badge"><?php echo htmlspecialchars($job['job_type']); ?></span></div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12"><p class="text-black mb-0">No jobs have been posted yet. Please check back soon.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Career Guide Section -->
    <div class="py-5" style="background-color: #FFF9F2;">
        <div class="container px-4 px-md-5 py-5">
            <!-- Section Header -->
            <div class="text-center mb-5 mx-auto" style="max-width: 700px;">
                <h2 class="display-5 fw-bold text-black mb-3" style="letter-spacing: -0.03em;">Career guide</h2>
                <p class="fs-5 text-secondary">
                    Get guidance and actionable tips to move your career forward, plus the latest local
                    and international job trends from our experts.
                </p>
            </div>

            <!-- Article Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mx-auto" style="max-width: 1200px;">
                <div class="row g-0 align-items-stretch">
                    <!-- Image Column -->
                    <div class="col-md-4">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800&h=600&fit=crop" class="img-fluid w-100 h-100" style="object-fit: cover; min-height: 250px; max-height: 400px;" alt="Fractional Leadership">
                    </div>
                    <!-- Content Column -->
                    <div class="col-md-8">
                        <div class="card-body p-4 d-flex flex-column justify-content-center h-100">
                            <div>
                                <span class="badge px-3 py-2 mb-4" style="background-color: #f0f5ff; color: #3b82f6;">Job Market</span>
                            </div>
                            <h3 class="fw-bold text-black mb-4" style="line-height: 1.4; letter-spacing: -0.02em;">
                                Fractional Leadership: Why Your Next Senior Hire Might Only Need 10 Hours a Week
                            </h3>
                            <p class="text-secondary mb-0" style="line-height: 1.7; font-size: 0.95rem;">
                                The traditional C-suite model is being disrupted by a new breed of elite talent available on a part-time basis. This article explores the strategic rise of fractional leadership within Africa's most vibrant tech hubs, detailing how SMEs can leverage high-level expertise to bridge the gap between lean operations...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Stories Section -->
    <div class="py-5" style="background-color: #0F0F0F;">
        <div class="container px-4 px-md-5 py-5">
            <!-- Header -->
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-vuka-orange mb-3" style="letter-spacing: -0.03em;">Success stories from our community</h2>
                <h4 class="fw-semibold text-white">Inspiring stories from VUKA users</h4>
            </div>

            <!-- Grid -->
            <div class="row g-4 mb-4" style="max-width: 1200px; margin: 0 auto;">
                <!-- Testimonial 1 -->
                <div class="col-md-6">
                    <div class="card h-100 border-0 rounded-4 p-4 p-md-5 d-flex flex-column" style="background-color: #FEE140;">
                        <p class="fs-5 text-black mb-5" style="font-weight: 500; line-height: 1.5;">
                            The process is seamless and genuinely encouraging. I'm finally applying to jobs where I can be the best candidate — not just any candidate. Honestly, VUKA has given me real hope in my job search.
                        </p>
                        <div class="d-flex align-items-center mt-auto">
                            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100&h=100&fit=crop" class="rounded-3 me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="Beatrice">
                            <div>
                                <h6 class="fw-bold text-black mb-0">Beatrice</h6>
                                <p class="small text-black-50 mb-0" style="font-size: 0.8rem;">Data annotation specialist</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="col-md-6">
                    <div class="card h-100 border-0 rounded-4 p-4 p-md-5 d-flex flex-column" style="background-color: #93C5FD;">
                        <p class="fs-5 text-black mb-5" style="font-weight: 500; line-height: 1.5;">
                            The jobs I see on VUKA actually align with my skills and experience. The descriptions are clear & easy to understand, and the three-step application process is simple and refreshingly user-friendly.
                        </p>
                        <div class="d-flex align-items-center mt-auto">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop" class="rounded-3 me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="Ahdan">
                            <div>
                                <h6 class="fw-bold text-black mb-0">Ahdan</h6>
                                <p class="small text-black-50 mb-0" style="font-size: 0.8rem;">Regional manager</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action Card -->
            <div class="card border-0 rounded-4 p-5 text-center mx-auto mt-4" style="background-color: #FFF9F2; max-width: 1200px;">
                <h2 class="display-6 fw-bold text-black mb-4 mx-auto" style="max-width: 650px; line-height: 1.2; letter-spacing: -0.02em;">
                    Join 3M+ professionals and find the job that's made for you
                </h2>
                <div>
                    <a href="signup.php" class="btn btn-dark fw-bold rounded-3 px-4 py-2 fs-5">Create account</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Explore by Category Section -->
    <div class="py-5" style="background: linear-gradient(to bottom, #a3c4f3, #e0eafc);">
        <div class="container px-4 px-md-5 py-5">
            <!-- Header -->
            <div class="d-flex flex-wrap align-items-center mb-5">
                <h2 class="display-6 fw-bold text-black mb-0 me-3" style="letter-spacing: -0.02em;">Explore jobs by</h2>
                <div class="d-flex align-items-center rounded-pill border border-dark px-3 py-2 mt-3 mt-sm-0" style="background-color: #FF6B00; box-shadow: 2px 2px 0px rgba(0,0,0,0.1);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-list-task me-2 text-black" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5zM3 3H2v1h1z"/>
                      <path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M5.5 7a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1zm0 4a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1z"/>
                      <path fill-rule="evenodd" d="M1.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5zM2 7h1v1H2zm0 3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm1 .5H2v1h1z"/>
                    </svg>
                    <span class="fs-4 fw-bold text-black" style="line-height: 1;">category</span>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="row g-3">
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Teaching, training</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Accounting, finance, banking, insurance</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Business, strategic management</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Sales, marketing, promotion</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Medical, health</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Information technology, software development, data</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Transportation, logistics, driving</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Engineering, architecture</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Manufacturing, operations, quality</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Human resources</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Installation, maintenance, repair</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <a href="#" class="card h-100 border-0 rounded-3 p-4 text-decoration-none hover-shadow d-flex flex-row align-items-center justify-content-between" style="background-color: #ffffff; transition: all 0.3s ease;">
                        <span class="text-black fw-medium pe-3" style="font-size: 0.95rem;">Administrative, clerical</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-right text-secondary flex-shrink-0" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Site Footer -->
    <footer class="site-footer">
        <div class="container px-4 px-md-5 footer-main">
            <div class="footer-links-grid">
                <section>
                    <h2 class="footer-heading">Countries</h2>
                    <ul class="footer-links">
                        <li><a href="#">Global</a></li>
                        <li><a href="#">Kenya</a></li>
                        <li><a href="#">Uganda</a></li>
                        <li><a href="#">Nigeria</a></li>
                    </ul>
                </section>
                <section>
                    <h2 class="footer-heading">For job seekers</h2>
                    <ul class="footer-links">
                        <li><a href="#">Find jobs</a></li>
                        <li><a href="#">Career guide</a></li>
                        <li><a href="#">Learn new skills</a></li>
                        <li><a href="#">Remote jobs</a></li>
                        <li><a href="#">Entry-level jobs</a></li>
                        <li><a href="#">Mid-level jobs</a></li>
                        <li><a href="#">Senior-level jobs</a></li>
                    </ul>
                </section>
                <section>
                    <h2 class="footer-heading">For recruiters</h2>
                    <ul class="footer-links">
                        <li><a href="#">Hire with VUKA</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Hire remote talent</a></li>
                    </ul>
                </section>
                <section>
                    <h2 class="footer-heading">Resources</h2>
                    <ul class="footer-links">
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Contact us</a></li>
                        <li><a href="#">About VUKA</a></li>
                        <li><a href="#">Careers</a></li>
                    </ul>
                </section>
            </div>

            <hr class="footer-divider">

            <div class="footer-brand-row">
                <a class="footer-logo" href="index.php" aria-label="VUKA home">VUKA</a>
                <p class="footer-mission">VUKA helps you find roles that fit, learn what matters, and act on clear insights with verified opportunities you can trust.</p>
                <div class="footer-social">
                    <p class="footer-social-title">Follow us</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V3.9c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2v2H7.5v3h2.8v8h3.2Z"/></svg></a>
                        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.3 2h9.4A5.3 5.3 0 0 1 22 7.3v9.4a5.3 5.3 0 0 1-5.3 5.3H7.3A5.3 5.3 0 0 1 2 16.7V7.3A5.3 5.3 0 0 1 7.3 2Zm0 2A3.3 3.3 0 0 0 4 7.3v9.4A3.3 3.3 0 0 0 7.3 20h9.4a3.3 3.3 0 0 0 3.3-3.3V7.3A3.3 3.3 0 0 0 16.7 4H7.3Zm9.9 1.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/></svg></a>
                        <a href="#" aria-label="X (Twitter)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 2H22l-6.8 7.7L23.2 22h-6.3L12 15.6 6.5 22H3.4l7.3-8.3L3 2h6.5l4.4 5.8L18.9 2Zm-1.1 18h1.7L8.6 3.9H6.8L17.8 20Z"/></svg></a>
                        <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.1 3.4a1.9 1.9 0 1 1 0 3.8 1.9 1.9 0 0 1 0-3.8ZM3.4 8.7h3.5V20H3.4V8.7ZM9.1 8.7h3.4v1.5h.1c.5-.9 1.6-1.9 3.4-1.9 3.6 0 4.3 2.4 4.3 5.5V20h-3.5v-5.5c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9V20H9.1V8.7Z"/></svg></a>
                        <a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.6 4.6 12 4.6 12 4.6s-5.6 0-7.5.5a3 3 0 0 0-2.1 2.1C2 9.1 2 12 2 12s0 2.9.4 4.8a3 3 0 0 0 2.1 2.1c1.9.5 7.5.5 7.5.5s5.6 0 7.5-.5a3 3 0 0 0 2.1-2.1c.4-1.9.4-4.8.4-4.8s0-2.9-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z"/></svg></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container px-4 px-md-5 footer-bottom-inner">
                <span class="footer-copyright">&copy; <?php echo date('Y'); ?> VUKA Ltd. All rights reserved.</span>
                <nav class="footer-legal" aria-label="Legal links">
                    <a href="#">Terms &amp; conditions</a><a href="#">Privacy policy</a><a href="#">Cookies</a><a href="#">Legal &amp; compliance</a><a href="#">LLMs</a>
                </nav>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script>document.querySelector('select[name="location"]').value = <?php echo json_encode($location); ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
