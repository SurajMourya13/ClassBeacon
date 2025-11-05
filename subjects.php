<?php
// subjects.php - Database-driven subject fetching logic

// Helper function to extract degree and year from course name
function extract_course_info($course_name) {
    $course_name = trim($course_name);
    
    // 1. Check for specific year (e.g., 'BBA-CA First Year')
    if (preg_match('/(BBA-CA|BCOM|BCS|BBA)\s+(First|Second|Third)\s+Year/i', $course_name, $matches)) {
        $degree = strtoupper($matches[1]);
        $year_word = strtolower($matches[2]);
        $year_map = ['first' => 'FY', 'second' => 'SY', 'third' => 'TY'];
        $year = $year_map[$year_word] ?? null;
        return ['degree' => $degree, 'year' => $year];
    }
    
    // 2. Check for general degree name (e.g., 'BBA-CA' or 'BCOM') and default to FY
    if (preg_match('/(BBA-CA|BCOM|BCS|BBA)/i', $course_name, $matches)) {
        $degree = strtoupper($matches[1]);
        return ['degree' => $degree, 'year' => 'FY'];
    }
    
    return ['degree' => null, 'year' => null];
}

// Function to fetch subjects from the database for a specific degree and year
function fetch_subjects_from_db($conn, $degree_key, $year_key = null) {
    $subjects = [];
    $bind_types = "s";
    $bind_params = [$degree_key];
    // FIX: Changed 'degree_name' to 'degree'
    $sql = "SELECT subject_name FROM course_subjects WHERE degree = ?";
    
    if ($year_key) {
        // FIX: Changed 'year_level' to 'year'
        $sql .= " AND year = ?";
        $bind_types .= "s";
        $bind_params[] = $year_key;
    }
    $sql .= " ORDER BY subject_name";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { return []; }

    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach($result as $row) {
        $subjects[] = $row['subject_name'];
    }
    
    return $subjects;
}

// Function to fetch ALL subjects grouped by year (for Grade Management dropdowns)
function fetch_all_subjects_by_degree($conn, $degree_key) {
    // FIX: Changed 'year_level' to 'year' and 'degree_name' to 'degree'
    $sql = "SELECT year, subject_name FROM course_subjects WHERE degree = ? ORDER BY year, subject_name";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) return [];
    
    $stmt->bind_param("s", $degree_key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $grouped_subjects = ['FY' => [], 'SY' => [], 'TY' => []];
    foreach($result as $row) {
        // FIX: Changed array access key from 'year_level' to 'year'
        if (isset($grouped_subjects[$row['year']])) {
            $grouped_subjects[$row['year']][] = $row['subject_name'];
        }
    }
    return $grouped_subjects;
}
?>