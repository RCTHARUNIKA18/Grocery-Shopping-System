<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit('Not authorized');
}

// Include TCPDF
require_once('tcpdf/tcpdf.php'); // Adjust path if needed

// Database connection
$conn = new mysqli("localhost", "root", "", "grocery_shopping_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get report type from POST data
$reportType = isset($_POST['report_type']) ? $_POST['report_type'] : '';

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Grocery Shopping System Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF instance
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Grocery Shopping System');
$pdf->SetTitle(ucfirst($reportType) . ' Report');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set margins
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Function to add a table to the PDF
function addTableToPDF($pdf, $headers, $data, $title) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1, 'L');
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(200, 200, 200);
    foreach ($headers as $header) {
        $pdf->Cell(40, 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(255, 255, 255);
    foreach ($data as $row) {
        foreach ($row as $cell) {
            $pdf->Cell(40, 6, $cell, 1, 0, 'L');
        }
        $pdf->Ln();
    }
    $pdf->Ln(10);
}

// Generate report based on type
switch ($reportType) {
    case 'sales':
        // Sales Report
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Get orders and payments data
        $query = "SELECT o.order_id, o.order_date, o.total_amount, p.payment_method, p.payment_status 
                 FROM orders o 
                 JOIN payment p ON o.order_id = p.order_id 
                 ORDER BY o.order_date DESC";
        $result = $conn->query($query);

        if ($result) {
            $headers = ['Order ID', 'Date', 'Amount', 'Payment Method', 'Status'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['order_id'],
                    $row['order_date'],
                    '₹' . number_format($row['total_amount'], 2),
                    $row['payment_method'],
                    $row['payment_status']
                ];
            }
            addTableToPDF($pdf, $headers, $data, 'Recent Orders and Payments');
        }
        break;

    case 'inventory':
        // Inventory Report
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Inventory Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Get inventory data
        $query = "SELECT i.product_id, p.product_name, i.stock, p.price, p.availability 
                 FROM inventory i 
                 JOIN products p ON i.product_id = p.product_id 
                 ORDER BY p.product_name";
        $result = $conn->query($query);

        if ($result) {
            $headers = ['Product ID', 'Product Name', 'Stock', 'Price', 'Availability'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['product_id'],
                    $row['product_name'],
                    $row['stock'],
                    '₹' . number_format($row['price'], 2),
                    $row['availability']
                ];
            }
            addTableToPDF($pdf, $headers, $data, 'Current Inventory Status');
        }
        break;

    case 'users':
        // User Activity Report
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'User Activity Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Get user data
        $query = "SELECT u.user_id, u.username, u.email_id, u.phone_no, 
                        COUNT(o.order_id) as total_orders,
                        SUM(o.total_amount) as total_spent
                 FROM user u 
                 LEFT JOIN orders o ON u.user_id = o.user_id 
                 GROUP BY u.user_id";
        $result = $conn->query($query);

        if ($result) {
            $headers = ['User ID', 'Username', 'Email', 'Phone', 'Total Orders', 'Total Spent'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['user_id'],
                    $row['username'],
                    $row['email_id'],
                    $row['phone_no'],
                    $row['total_orders'],
                    '₹' . number_format($row['total_spent'] ?? 0, 2)
                ];
            }
            addTableToPDF($pdf, $headers, $data, 'User Activity Summary');
        }
        break;

    case 'complete':
        // Complete System Report
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Complete System Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Add all report sections
        // Sales Section
        $query = "SELECT o.order_id, o.order_date, o.total_amount, p.payment_method, p.payment_status 
                 FROM orders o 
                 JOIN payment p ON o.order_id = p.order_id 
                 ORDER BY o.order_date DESC";
        $result = $conn->query($query);
        if ($result) {
            $headers = ['Order ID', 'Date', 'Amount', 'Payment Method', 'Status'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['order_id'],
                    $row['order_date'],
                    '₹' . number_format($row['total_amount'], 2),
                    $row['payment_method'],
                    $row['payment_status']
                ];
            }
            addTableToPDF($pdf, $headers, $data, 'Sales Overview');
        }

        // Inventory Section
        $query = "SELECT i.product_id, p.product_name, i.stock, p.price, p.availability 
                 FROM inventory i 
                 JOIN products p ON i.product_id = p.product_id 
                 ORDER BY p.product_name";
        $result = $conn->query($query);
        if ($result) {
            $headers = ['Product ID', 'Product Name', 'Stock', 'Price', 'Availability'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['product_id'],
                    $row['product_name'],
                    $row['stock'],
                    '₹' . number_format($row['price'], 2),
                    $row['availability']
                ];
            }
            addTableToPDF($pdf, $headers, $data, 'Inventory Status');
        }

        // User Section
        $query = "SELECT u.user_id, u.username, u.email_id, u.phone_no, 
                        COUNT(o.order_id) as total_orders,
                        SUM(o.total_amount) as total_spent
                 FROM user u 
                 LEFT JOIN orders o ON u.user_id = o.user_id 
                 GROUP BY u.user_id";
        $result = $conn->query($query);
        if ($result) {
            $headers = ['User ID', 'Username', 'Email', 'Phone', 'Total Orders', 'Total Spent'];
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    $row['user_id'],
                    $row['username'],
                    $row['email_id'],
                    $row['phone_no'],
                    $row['total_orders'],
                    '₹' . number_format($row['total_spent'] ?? 0, 2)
                ];
            }
            addTableToPDF($pdf, $headers, $data, 'User Activity Summary');
        }
        break;

    default:
        header('HTTP/1.1 400 Bad Request');
        exit('Invalid report type');
}

// Close and output PDF document
$pdf->Output($reportType . '_report.pdf', 'D');
?> 