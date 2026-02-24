<?php
// ============================================================
// ai_engine.php — Lanbridge College KPI System
// AI Pattern Detection & Insights Engine
//
// This is a PURE PHP statistical engine. No external AI API
// needed. All rules run against your own database data.
//
// HOW TO USE:
//   require_once __DIR__ . '/../includes/ai_engine.php';
//   $engine = new AIEngine(getDB());
//   $engine->runAll();           // Run all detectors
//   $insights = $engine->getActiveInsights();
// ============================================================

class AIEngine {

    private PDO $db;
    private array $generated = [];

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ── Run all detectors ────────────────────────────────────
    public function runAll(): void {
        $this->detectCopyPasteReports();
        $this->detectMissingSubmitters();
        $this->detectKPIAnomalies();
        $this->detectBudgetOverruns();
        $this->detectLowApprovalRates();
        $this->detectSLABreaches();
        $this->detectFinancialDuplicates();
        $this->detectHighOutstandingFees();
    }

    // ── 1. Copy-Paste / Identical Reports Detection ──────────
    // Flags users who submit word-for-word identical report text
    // on consecutive days — a strong indicator of false reporting.
    public function detectCopyPasteReports(): void {
        $threshold = 3; // Flag after N consecutive identical days
        $stmt = $this->db->query(
            "SELECT r1.user_id,
                    u.first_name, u.last_name,
                    u.department_id,
                    COUNT(*) AS identical_count
             FROM reports r1
             JOIN reports r2
               ON r1.user_id = r2.user_id
              AND r1.id != r2.id
              AND ABS(DATEDIFF(r1.report_date, r2.report_date)) <= 5
              AND r1.tasks_completed = r2.tasks_completed
             JOIN users u ON r1.user_id = u.id
             WHERE r1.report_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
             GROUP BY r1.user_id
             HAVING identical_count >= 2"
        );
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($findings as $f) {
            $confidence = min(95, 60 + ($f['identical_count'] * 10));
            $this->saveInsight([
                'insight_type'   => 'COPY_PASTE_REPORT',
                'department_id'  => $f['department_id'],
                'user_id'        => $f['user_id'],
                'title'          => 'Possible Copy-Paste Reports Detected',
                'description'    => "{$f['first_name']} {$f['last_name']} has submitted identical report text on {$f['identical_count']} occasions in the last 14 days. This may indicate copy-pasting rather than genuine daily reporting.",
                'severity'       => $f['identical_count'] >= 4 ? 'critical' : 'warning',
                'confidence_pct' => $confidence,
                'source_data'    => json_encode($f),
            ]);
        }
    }

    // ── 2. Missing Submitters — Staff Not Reporting ───────────
    // Flags staff who have not submitted a report in 5+ weekdays
    public function detectMissingSubmitters(): void {
        // Count weekdays in last 10 days (Mon-Fri)
        $weekdaysMissed = 5;
        $stmt = $this->db->query(
            "SELECT u.id, u.first_name, u.last_name, u.department_id,
                    d.name AS dept_name,
                    MAX(r.report_date) AS last_report,
                    DATEDIFF(CURDATE(), MAX(r.report_date)) AS days_silent
             FROM users u
             JOIN roles ro ON u.role_id = ro.id AND ro.slug = 'staff'
             LEFT JOIN departments d ON u.department_id = d.id
             LEFT JOIN reports r ON r.user_id = u.id
             WHERE u.is_active = 1
             GROUP BY u.id
             HAVING (last_report IS NULL OR days_silent >= 7)"
        );
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($findings as $f) {
            $daysSilent = $f['last_report'] ? (int)$f['days_silent'] : 999;
            $severity = $daysSilent >= 14 ? 'critical' : 'warning';
            $lastStr  = $f['last_report'] ? "Last report: {$f['last_report']}" : "No reports ever submitted";
            $this->saveInsight([
                'insight_type'   => 'MISSING_SUBMISSIONS',
                'department_id'  => $f['department_id'],
                'user_id'        => $f['id'],
                'title'          => 'Staff Not Submitting Reports',
                'description'    => "{$f['first_name']} {$f['last_name']} ({$f['dept_name']}) has not submitted a report in {$daysSilent} days. {$lastStr}. Requires follow-up.",
                'severity'       => $severity,
                'confidence_pct' => 98,
                'source_data'    => json_encode($f),
            ]);
        }
    }

    // ── 3. KPI Score Anomalies ────────────────────────────────
    // Flags users consistently reporting quality_score=100 (inflated self-scoring)
    public function detectKPIAnomalies(): void {
        $stmt = $this->db->query(
            "SELECT k.user_id, u.first_name, u.last_name, u.department_id,
                    COUNT(*) AS total_kpis,
                    AVG(k.quality_score) AS avg_quality,
                    SUM(k.quality_score = 100) AS perfect_scores,
                    ROUND(SUM(k.quality_score = 100)/COUNT(*)*100, 1) AS perfect_pct
             FROM kpi_submissions k
             JOIN users u ON k.user_id = u.id
             WHERE k.submission_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
               AND k.quality_score IS NOT NULL
             GROUP BY k.user_id
             HAVING total_kpis >= 5 AND perfect_pct >= 80"
        );
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($findings as $f) {
            $this->saveInsight([
                'insight_type'   => 'KPI_SCORE_INFLATION',
                'department_id'  => $f['department_id'],
                'user_id'        => $f['user_id'],
                'title'          => 'Potentially Inflated KPI Self-Scores',
                'description'    => "{$f['first_name']} {$f['last_name']} has given themselves a perfect score (100%) on {$f['perfect_pct']}% of their {$f['total_kpis']} KPI submissions in the last 30 days. Average quality: {$f['avg_quality']}. Consider supervisor review.",
                'severity'       => 'warning',
                'confidence_pct' => 75,
                'source_data'    => json_encode($f),
            ]);
        }
    }

    // ── 4. Budget Overrun Prediction ─────────────────────────
    // Flags departments where spending pace will exceed budget
    public function detectBudgetOverruns(): void {
        $year = date('Y');
        $dayOfYear = (int)date('z') + 1;
        $daysInYear = date('L') ? 366 : 365;
        $yearProgress = $dayOfYear / $daysInYear;

        $stmt = $this->db->query(
            "SELECT d.id AS dept_id, d.name,
                    b.allocated_amount,
                    COALESCE(SUM(e.amount), 0) AS spent
             FROM departments d
             LEFT JOIN departmental_budget b ON b.department_id=d.id AND b.fiscal_year='$year'
             LEFT JOIN expenditure e ON e.department_id=d.id AND e.fiscal_year='$year' AND e.status='approved'
             WHERE b.allocated_amount > 0
             GROUP BY d.id, b.allocated_amount
             HAVING (spent / allocated_amount) > (" . round($yearProgress, 4) . " + 0.15)"
        );
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($findings as $f) {
            $usedPct   = round(($f['spent'] / $f['allocated_amount']) * 100, 1);
            $projected = $f['allocated_amount'] > 0
                ? round($f['spent'] / max(0.01, $yearProgress), 2)
                : 0;
            $this->saveInsight([
                'insight_type'   => 'BUDGET_OVERRUN_RISK',
                'department_id'  => $f['dept_id'],
                'user_id'        => null,
                'title'          => 'Budget Overrun Risk: ' . $f['name'],
                'description'    => "{$f['name']} has used {$usedPct}% of its annual budget, but only " . round($yearProgress * 100, 1) . "% of the year has passed. At current pace, projected spend: ZMW " . number_format($projected, 2) . " vs. budget: ZMW " . number_format($f['allocated_amount'], 2) . ".",
                'severity'       => $usedPct >= 100 ? 'critical' : 'warning',
                'confidence_pct' => 82,
                'source_data'    => json_encode($f),
            ]);
        }
    }

    // ── 5. Low Approval Rates ─────────────────────────────────
    // Flags departments with consistently low approval rates
    public function detectLowApprovalRates(): void {
        $monthStart = date('Y-m-01');
        $stmt = $this->db->query(
            "SELECT d.id AS dept_id, d.name AS dept_name,
                    COUNT(r.id) AS total,
                    SUM(r.status='approved') AS approved,
                    SUM(r.status='rejected') AS rejected,
                    ROUND(SUM(r.status='approved')/NULLIF(COUNT(r.id),0)*100,1) AS approval_rate
             FROM departments d
             LEFT JOIN users u ON u.department_id=d.id
             LEFT JOIN reports r ON r.user_id=u.id AND r.report_date >= '$monthStart'
             GROUP BY d.id
             HAVING total >= 5 AND approval_rate < 40"
        );
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($findings as $f) {
            $this->saveInsight([
                'insight_type'   => 'LOW_APPROVAL_RATE',
                'department_id'  => $f['dept_id'],
                'user_id'        => null,
                'title'          => 'Low Report Approval Rate: ' . $f['dept_name'],
                'description'    => "{$f['dept_name']} has an approval rate of only {$f['approval_rate']}% this month ({$f['approved']} approved, {$f['rejected']} rejected out of {$f['total']} total). Investigate report quality and reviewer bottlenecks.",
                'severity'       => $f['approval_rate'] < 20 ? 'critical' : 'warning',
                'confidence_pct' => 90,
                'source_data'    => json_encode($f),
            ]);
        }
    }

    // ── 6. IT SLA Breaches ────────────────────────────────────
    public function detectSLABreaches(): void {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS breached,
                    SUM(priority='critical') AS critical_breached,
                    SUM(priority='high') AS high_breached
             FROM it_tickets
             WHERE sla_deadline < NOW()
               AND status IN ('open','in_progress','pending_user')"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['breached'] > 0) {
            $this->saveInsight([
                'insight_type'   => 'IT_SLA_BREACH',
                'department_id'  => null,
                'user_id'        => null,
                'title'          => 'IT Support SLA Breaches Detected',
                'description'    => "{$row['breached']} IT tickets have exceeded their SLA deadline. Critical: {$row['critical_breached']}, High: {$row['high_breached']}. Immediate IT team action required.",
                'severity'       => (int)$row['critical_breached'] > 0 ? 'critical' : 'warning',
                'confidence_pct' => 100,
                'source_data'    => json_encode($row),
            ]);
        }
    }

    // ── 7. Duplicate / Suspicious Financial Transactions ──────
    public function detectFinancialDuplicates(): void {
        // Transactions of the same amount, same category, same date, different ref
        $stmt = $this->db->query(
            "SELECT category, amount, transaction_date,
                    COUNT(*) AS dup_count,
                    GROUP_CONCAT(reference_no SEPARATOR ', ') AS refs
             FROM transactions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               AND status != 'rejected'
             GROUP BY category, amount, transaction_date
             HAVING dup_count >= 2"
        );
        $findings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($findings as $f) {
            $this->saveInsight([
                'insight_type'   => 'DUPLICATE_TRANSACTION',
                'department_id'  => null,
                'user_id'        => null,
                'title'          => 'Possible Duplicate Financial Transaction',
                'description'    => "Found {$f['dup_count']} transactions with identical amount (ZMW " . number_format($f['amount'], 2) . "), category ({$f['category']}), and date ({$f['transaction_date']}). Reference numbers: {$f['refs']}. Verify these are not duplicates.",
                'severity'       => 'warning',
                'confidence_pct' => 80,
                'source_data'    => json_encode($f),
            ]);
        }
    }

    // ── 8. High Outstanding Student Fees ─────────────────────
    public function detectHighOutstandingFees(): void {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(balance), 0) AS total_outstanding,
                    COUNT(*) AS overdue_count
             FROM student_fees
             WHERE status IN ('unpaid','partial','overdue')
               AND due_date < CURDATE()"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (float)$row['total_outstanding'] > 10000) {
            $this->saveInsight([
                'insight_type'   => 'HIGH_OUTSTANDING_FEES',
                'department_id'  => null,
                'user_id'        => null,
                'title'          => 'High Outstanding Student Fee Balance',
                'description'    => "ZMW " . number_format($row['total_outstanding'], 2) . " in student fees is overdue across {$row['overdue_count']} student accounts. Finance team should initiate follow-up.",
                'severity'       => $row['total_outstanding'] > 50000 ? 'critical' : 'warning',
                'confidence_pct' => 100,
                'source_data'    => json_encode($row),
            ]);
        }
    }

    // ── Save insight (deduplicate by type + user + dept in same day) ─
    private function saveInsight(array $data): void {
        $today = date('Y-m-d');
        // Check if same insight type was already generated today for same target
        $check = $this->db->prepare(
            "SELECT id FROM ai_insights
             WHERE insight_type = ?
               AND (user_id IS NULL OR user_id = ?)
               AND (department_id IS NULL OR department_id = ?)
               AND DATE(created_at) = ?
               AND is_reviewed = 0
             LIMIT 1"
        );
        $check->execute([
            $data['insight_type'],
            $data['user_id'] ?? null,
            $data['department_id'] ?? null,
            $today,
        ]);
        if ($check->fetch()) {
            return; // Already flagged today — don't duplicate
        }

        $stmt = $this->db->prepare(
            "INSERT INTO ai_insights
             (insight_type, department_id, user_id, title, description, severity, confidence_pct, source_data)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['insight_type'],
            $data['department_id'] ?? null,
            $data['user_id'] ?? null,
            $data['title'],
            $data['description'],
            $data['severity'],
            $data['confidence_pct'],
            $data['source_data'] ?? null,
        ]);
        $this->generated[] = $data['insight_type'];
    }

    // ── Get unreviewed insights (for dashboards) ─────────────
    public function getActiveInsights(?int $deptId = null, int $limit = 10): array {
        if ($deptId !== null) {
            $stmt = $this->db->prepare(
                "SELECT i.*, u.first_name, u.last_name, d.name AS dept_name
                 FROM ai_insights i
                 LEFT JOIN users u ON i.user_id = u.id
                 LEFT JOIN departments d ON i.department_id = d.id
                 WHERE i.is_reviewed = 0
                   AND (i.department_id = ? OR i.department_id IS NULL)
                 ORDER BY FIELD(i.severity,'critical','warning','info'), i.created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$deptId, $limit]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT i.*, u.first_name, u.last_name, d.name AS dept_name
                 FROM ai_insights i
                 LEFT JOIN users u ON i.user_id = u.id
                 LEFT JOIN departments d ON i.department_id = d.id
                 WHERE i.is_reviewed = 0
                 ORDER BY FIELD(i.severity,'critical','warning','info'), i.created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Count unreviewed insights ─────────────────────────────
    public function getInsightCount(?int $deptId = null): int {
        if ($deptId !== null) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM ai_insights
                 WHERE is_reviewed=0 AND (department_id=? OR department_id IS NULL)"
            );
            $stmt->execute([$deptId]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) FROM ai_insights WHERE is_reviewed=0");
        }
        return (int)$stmt->fetchColumn();
    }

    // ── Generate performance scorecards for current month ─────
    public function generateScorecards(string $period = ''): int {
        if (!$period) $period = date('Y-m');
        $monthStart = $period . '-01';
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        $users = $this->db->query(
            "SELECT u.id, u.department_id FROM users u
             JOIN roles r ON u.role_id=r.id
             WHERE u.is_active=1 AND r.slug='staff'"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($users as $u) {
            $uid    = (int)$u['id'];
            $deptId = (int)$u['department_id'];

            // Weekdays in period (approximate)
            $weekdays = $this->countWeekdays($monthStart, $monthEnd);

            // Report score: submitted / weekdays * 100
            $reports = (int)$this->db->query(
                "SELECT COUNT(*) FROM reports WHERE user_id=$uid AND report_date BETWEEN '$monthStart' AND '$monthEnd'"
            )->fetchColumn();
            $reportScore = $weekdays > 0 ? min(100, round(($reports / $weekdays) * 100, 2)) : 0;

            // KPI score: avg quality_score
            $kpiScore = (float)$this->db->query(
                "SELECT COALESCE(AVG(quality_score), 0) FROM kpi_submissions
                 WHERE user_id=$uid AND submission_date BETWEEN '$monthStart' AND '$monthEnd'
                 AND quality_score IS NOT NULL"
            )->fetchColumn();

            // Approval rate
            $totalRep = (int)$this->db->query(
                "SELECT COUNT(*) FROM reports WHERE user_id=$uid AND report_date BETWEEN '$monthStart' AND '$monthEnd'"
            )->fetchColumn();
            $approvedRep = (int)$this->db->query(
                "SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='approved' AND report_date BETWEEN '$monthStart' AND '$monthEnd'"
            )->fetchColumn();
            $approvalRate = $totalRep > 0 ? round(($approvedRep / $totalRep) * 100, 2) : 0;

            // Consistency score: check for copy-paste flags
            $flags = (int)$this->db->query(
                "SELECT COUNT(*) FROM ai_insights WHERE user_id=$uid AND insight_type='COPY_PASTE_REPORT' AND DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd'"
            )->fetchColumn();
            $consistencyScore = max(0, 100 - ($flags * 20));

            $overall = round(
                ($reportScore * 0.30) +
                ($kpiScore * 0.30) +
                ($approvalRate * 0.25) +
                ($consistencyScore * 0.15),
                2
            );

            $stmt = $this->db->prepare(
                "INSERT INTO performance_scorecards
                 (user_id, department_id, period, report_score, kpi_score, approval_rate, consistency_score, overall_score)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   report_score=VALUES(report_score),
                   kpi_score=VALUES(kpi_score),
                   approval_rate=VALUES(approval_rate),
                   consistency_score=VALUES(consistency_score),
                   overall_score=VALUES(overall_score),
                   generated_at=NOW()"
            );
            $stmt->execute([$uid, $deptId, $period, $reportScore, $kpiScore, $approvalRate, $consistencyScore, $overall]);
            $count++;
        }

        // Update ranks within department
        $this->db->exec(
            "SET @dept_rank=0, @dept=NULL;
             UPDATE performance_scorecards ps
             JOIN (
               SELECT id,
                 @dept_rank := IF(@dept=department_id, @dept_rank+1, 1) AS rnk,
                 @dept := department_id
               FROM performance_scorecards
               WHERE period='$period'
               ORDER BY department_id, overall_score DESC
             ) ranked ON ps.id=ranked.id
             SET ps.rank_in_dept = ranked.rnk
             WHERE ps.period='$period'"
        );

        return $count;
    }

    // ── Helper: count weekdays in date range ──────────────────
    private function countWeekdays(string $start, string $end): int {
        $count = 0;
        $d = strtotime($start);
        $e = strtotime($end);
        while ($d <= $e) {
            $dow = (int)date('N', $d);
            if ($dow < 6) $count++;
            $d = strtotime('+1 day', $d);
        }
        return $count;
    }
}
