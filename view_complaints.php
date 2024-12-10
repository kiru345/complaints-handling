<?php
// Database connection
$conn = new mysqli("sql213.infinityfree.com", "	if0_37871491", "08eN84gcHxg", "if0_37871491_kickininn");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Handle search functionality
$searchQuery = "";
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search = $_GET['search'];
    $searchQuery = "WHERE c.email LIKE '%$search%' OR c.phone LIKE '%$search%'";
}

// Handle redeem functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['redeem'])) {
    $request_id = $_POST['request_id'];
    $redeemed = $_POST['redeemed'] == "1" ? 1 : 0;
    $redeemed_date = $redeemed ? date('Y-m-d') : NULL;

    // Check if redemption already exists
    $checkQuery = "SELECT * FROM redemptions WHERE request_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing redemption
        $updateQuery = "UPDATE redemptions SET redeemed = ?, redeemed_date = ? WHERE request_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("isi", $redeemed, $redeemed_date, $request_id);
    } else {
        // Insert new redemption
        $insertQuery = "INSERT INTO redemptions (request_id, redeemed, redeemed_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iis", $request_id, $redeemed, $redeemed_date);
    }

    $stmt->execute();
    $stmt->close();
}
// Fetch the necessary data
$redeemedQuery = "SELECT COUNT(*) AS redeemed_count FROM redemptions WHERE redeemed = 1";
$notRedeemedQuery = "SELECT COUNT(*) AS not_redeemed_count FROM redemptions WHERE redeemed = 0";

$redeemedResult = $conn->query($redeemedQuery);
$notRedeemedResult = $conn->query($notRedeemedQuery);

$redeemedCount = $redeemedResult->fetch_assoc()['redeemed_count'];
$notRedeemedCount = $notRedeemedResult->fetch_assoc()['not_redeemed_count'];

// Additional data for complaints over time (if needed)
$query = "SELECT DATE_FORMAT(date_visited, '%Y-%m') AS month, COUNT(*) AS complaints_count FROM customer_requests GROUP BY month";
$result = $conn->query($query);

$months = [];
$complaintCounts = [];
while ($row = $result->fetch_assoc()) {
    $months[] = $row['month'];
    $complaintCounts[] = $row['complaints_count'];
}

// Fetch data from customer_requests and redemptions
$query = "SELECT 
    c.id, c.name, c.email, c.phone, c.date_visited, c.reason, c.discount, 
    r.redeemed, r.redeemed_date 
FROM customer_requests c
LEFT JOIN redemptions r ON c.id = r.request_id 
$searchQuery";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaints and Redemptions</title>
    <link rel="stylesheet" href="styles.css">
</head>
                <a href="index.html" class="btn">Request Complaints</a>
        
    
<body>
    <div class="container">
        <h1>Customer Complaints and Redemptions</h1>
        <h1><a href="index.html" class="btn">Request Complaints</a></h1> 
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by Email or Phone">
            <button type="submit">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date Visited</th>
                    <th>Reason</th>
                    <th>Discount</th>
                    <th>Redeemed</th>
                    <th>Redeemed Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['date_visited']; ?></td>
                        <td><?php echo $row['reason']; ?></td>
                        <td><?php echo $row['discount']; ?></td>
                        <td><?php echo $row['redeemed'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $row['redeemed_date'] ?: '-'; ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                <select name="redeemed">
                                    <option value="1" <?php echo $row['redeemed'] ? 'selected' : ''; ?>>Yes</option>
                                    <option value="0" <?php echo !$row['redeemed'] ? 'selected' : ''; ?>>No</option>
                                </select>
                                <button type="submit" name="redeem">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div style="width: 50%; margin: 0 auto;">
    <canvas id="redeemedChart"></canvas>
</div>

<div style="width: 50%; margin: 0 auto;">
    <canvas id="redeemedbarChart"></canvas>
    <div style="width: 50%; margin: 0 auto;">
    <canvas id="trendsChart"></canvas>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sample data from the database (this should come dynamically from PHP)
    var redeemedCount = <?php echo $redeemedCount; ?>; // Redeemed complaints count
    var notRedeemedCount = <?php echo $notRedeemedCount; ?>; // Not redeemed complaints count

    // Data for the pie chart
    var data = {
        labels: ['Redeemed', 'Not Redeemed'],
        datasets: [{
            data: [redeemedCount, notRedeemedCount],
            backgroundColor: ['#4CAF50', '#FF5733'], // Colors for the chart
            hoverOffset: 4
        }]
    };

    // Options for customization (title, tooltips, etc.)
    var options = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.raw + " complaints";
                    }
                }
            }
        }
    };

    // Render the chart
    var ctx = document.getElementById('redeemedChart').getContext('2d');
    var redeemedChart = new Chart(ctx, {
        type: 'pie',
        data: data,
        options: options
    });


    
</script>
<script>
    // Sample data from the database (this should come dynamically from PHP)
    var months = <?php echo json_encode($months); ?>;  // Array of months
    var complaintCounts = <?php echo json_encode($complaintCounts); ?>;  // Array of complaint counts per month

    // Data for the bar chart
    var data = {
        labels: months,
        datasets: [{
            label: 'Complaints per Month',
            data: complaintCounts,
            backgroundColor: '#4CAF50',  // Bar color
        }]
    };

    // Render the bar chart
    var ctx = document.getElementById('redeemedbarChart').getContext('2d');
    var complaintsChart = new Chart(ctx, {
        type: 'bar',
        data: data
    });
</script>

<script>
    // Sample data for line chart
    var dates = <?php echo json_encode($dates); ?>;  // Dates array (e.g., weekly)
    var redeemed = <?php echo json_encode($redeemedData); ?>;  // Redeemed counts for each date
    var notRedeemed = <?php echo json_encode($notRedeemedData); ?>;  // Non-redeemed counts for each date

    var data = {
        labels: dates,
        datasets: [{
            label: 'Redeemed Complaints',
            data: redeemed,
            borderColor: '#4CAF50',
            fill: false,
            tension: 0.1
        },
        {
            label: 'Non-redeemed Complaints',
            data: notRedeemed,
            borderColor: '#FF5733',
            fill: false,
            tension: 0.1
        }]
    };

    var ctx = document.getElementById('trendsChart').getContext('2d');
    var trendsChart = new Chart(ctx, {
        type: 'line',
        data: data
    });
</script>



</html>
<?php
$conn->close();
?>
