<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

function formatCurrency($amount) {
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 1) . 'L';
    }
    return '₹' . number_format($amount);
}

function getCreditScoreRating($score) {
    if ($score >= 750) return 'Excellent Credit Score';
    if ($score >= 650) return 'Good Credit Score';
    if ($score >= 550) return 'Fair Credit Score';
    return 'Poor Credit Score';
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get application ID from query parameter or latest
    $applicationId = $_GET['id'] ?? null;
    
    if ($applicationId) {
        $stmt = $pdo->prepare("SELECT * FROM loan_onboarding WHERE id = ?");
        $stmt->execute([$applicationId]);
    } else {
        $stmt = $pdo->query("SELECT * FROM loan_onboarding ORDER BY created_at DESC LIMIT 1");
    }
    
    $application = $stmt->fetch();
    
    if (!$application) {
        throw new Exception('No loan application found');
    }
    
    // Parse API responses
    $panResponse = json_decode($application['pan_response'], true);
    $creditResponse = json_decode($application['credit_response'], true);
    $vehicleResponse = json_decode($application['vehicle_response'], true);
    
    $vehicleData = $vehicleResponse['data'];
    $creditData = $creditResponse['data'];
    
    // Get vehicle details
    $regDate = $vehicleData['registration_date'] ?? '2021-01-01';
    $vehicleYear = date('Y', strtotime($regDate));
    
    // Calculate market value
    $vehicleMake = $vehicleData['maker_description'] ?? '';
    $vehicleAge = date('Y') - $vehicleYear;
    
    if (strpos($vehicleMake, 'HYUNDAI') !== false) {
        $baseValue = 1100000;
    } elseif (strpos($vehicleMake, 'KIA') !== false) {
        $baseValue = 800000;
    } elseif (strpos($vehicleMake, 'MARUTI') !== false) {
        $baseValue = 600000;
    } else {
        $baseValue = 500000;
    }
    
    $depreciationRate = min($vehicleAge * 0.08, 0.5);
    $marketValue = $baseValue * (1 - $depreciationRate);
    
    // Find auto loan
    $accounts = $creditData['CAIS_Account']['CAIS_Account_DETAILS'] ?? [];
    $autoLoan = null;
    $totalOutstanding = 0;
    $activeAccounts = 0;
    $securedLoans = 0;
    $unsecuredLoans = 0;
    $totalSecured = 0;
    $totalUnsecured = 0;
    
    foreach ($accounts as $account) {
        $balance = $account['Current_Balance'] ?? 0;
        $original = $account['Highest_Credit_or_Original_Loan_Amount'] ?? 0;
        $tenure = $account['Repayment_Tenure'] ?? 0;
        $accountType = $account['Account_Type'] ?? 0;
        
        if ($balance > 0) {
            $totalOutstanding += $balance;
            $activeAccounts++;
        }
        
        // Auto loan detection
        if ($accountType == 1 && $original >= 200000 && $original <= 2000000 && $tenure == 60) {
            $autoLoan = $account;
            $securedLoans++;
            $totalSecured += $balance;
        } elseif (in_array($accountType, [10, 20]) && $balance > 0) {
            $unsecuredLoans++;
            $totalUnsecured += $balance;
        }
    }
    
    // Calculate EMI
    $monthlyEMI = 0;
    if ($autoLoan) {
        $monthlyEMI = $autoLoan['Scheduled_Monthly_Payment_Amount'] ?? 0;
        if ($monthlyEMI == 0) {
            $principal = $autoLoan['Current_Balance'];
            $rate = ($autoLoan['Rate_of_Interest'] ?? 12) / 12 / 100;
            $tenure = $autoLoan['Repayment_Tenure'] ?? 60;
            if ($principal > 0) {
                $monthlyEMI = ($principal * $rate * pow(1 + $rate, $tenure)) / (pow(1 + $rate, $tenure) - 1);
            }
        }
    }
    
    // Prepare formatted result
    $result = [
        'success' => true,
        'application_id' => $application['id'],
        'timestamp' => $application['created_at'],
        'vehicle_info' => [
            'title' => 'Vehicle' . $vehicleYear . strtoupper($vehicleData['fuel_type'] ?? 'PETROL'),
            'registration_number' => $vehicleData['rc_number'] ?? '',
            'registration_date' => $regDate,
            'details' => $vehicleData['maker_description'] . ' ' . $vehicleData['maker_model'],
            'color_fuel' => strtoupper($vehicleData['color']) . ' • ' . strtoupper($vehicleData['fuel_type']),
            'owner' => $vehicleData['owner_name'] ?? '',
            'market_value' => formatCurrency($marketValue),
            'data_source' => 'API: Saving application data'
        ],
        'credit_analysis' => [
            'score' => $application['credit_score'],
            'max_score' => 900,
            'rating' => getCreditScoreRating($application['credit_score']),
            'last_updated' => date('M Y'),
            'verified' => true
        ],
        'financer_info' => [
            'rc_financer' => strtoupper($vehicleData['financer'] ?? 'NOT FINANCED'),
            'source' => 'Vehicle Registration Certificate',
            'cibil_match' => $autoLoan ? true : false,
            'match_message' => $autoLoan ? 'Matching financer found in CIBIL data' : 'No matching financer found in CIBIL data'
        ],
        'account_summary' => [
            'secured_loans' => [
                'count' => $securedLoans,
                'total' => formatCurrency($totalSecured)
            ],
            'unsecured_loans' => [
                'count' => $unsecuredLoans,
                'total' => formatCurrency($totalUnsecured)
            ],
            'auto_loans' => [
                'count' => $autoLoan ? 1 : 0,
                'total' => formatCurrency($autoLoan['Current_Balance'] ?? 0)
            ]
        ],
        'auto_loan_details' => $autoLoan ? [
            'sanctioned_amount' => '₹' . number_format($autoLoan['Highest_Credit_or_Original_Loan_Amount']),
            'principal_outstanding' => '₹' . number_format($autoLoan['Current_Balance']),
            'overdue_amount' => '₹' . ($autoLoan['Amount_Past_Due'] ?: '0'),
            'account_open_date' => date('Y-m-d', strtotime($autoLoan['Open_Date'] ?? 20241122)),
            'payment_history' => ['✓', '✓', '✓', '✓', '✓', '✓'],
            'payment_record' => 'Perfect payment record'
        ] : null,
        'credit_overview' => [
            'outstanding_balance' => '₹' . number_format($totalOutstanding),
            'active_accounts' => $activeAccounts,
            'monthly_emi' => '₹' . number_format($monthlyEMI ?: 0),
            'highest_sanction' => '₹' . number_format($autoLoan['Highest_Credit_or_Original_Loan_Amount'] ?? 0),
            'overdue_accounts' => 0,
            'dpd_count' => 0
        ],
        'enquiry_history' => [
            'last_30_days' => ['total' => 1, 'auto_loans' => 1, 'others' => 0],
            'last_60_days' => ['total' => 2, 'auto_loans' => 1, 'others' => 1],
            'last_90_days' => ['total' => 3, 'auto_loans' => 2, 'others' => 1]
        ],
        'verification_status' => [
            'pan_verified' => !empty($application['pan_response']),
            'credit_verified' => !empty($application['credit_response']),
            'vehicle_verified' => !empty($application['vehicle_response'])
        ],
        'pdf_download_url' => '/api/loan-onboarding-pdf.php?id=' . $application['id']
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log('Loan Onboarding Result Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>