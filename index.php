<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<style>
    body {
        margin: 20px
    }

    .title-mitm {
        text-align: center;
    }

    .col {
        border: 1px solid #000;
        padding: 20px;
    }
</style>
<body>
    <h1 class="title-mitm">Emergency MITM logger dashboard</h1>

    <div class="col">
        <h3>recent log (200 entries)</h3>

    <table border="1" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Timestamp</th>
                <th>URL</th>
                <th>ACTION</th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            // Example data, replace with actual log fetching logic
            // Database connection
            try {
                $pdo = new PDO(
                    sprintf(
                        'pgsql:host=%s;dbname=%s',
                        getenv('DB_HOST') ?: 'localhost',
                        getenv('DB_NAME') ?: 'mitm_logs'
                    ),
                    getenv('DB_USERNAME') ?: 'username',
                    getenv('DB_PASSWD') ?: 'password'
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }

            // Fetch logs from the database
            try {
                $stmt = $pdo->prepare("
                SELECT 
                    public.addr.address, 
                    public.qs_mitm_history.path, 
                    public.qs_mitm_history.qs
                FROM 
                    public.addr
                INNER JOIN 
                    public.qs_mitm_history 
                ON 
                    public.addr.id = public.qs_mitm_history.addr
                ORDER BY 
                    public.qs_mitm_history.unix DESC NULLS LAST
                LIMIT 200;
                ");
                $stmt->execute();
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Error fetching logs: " . $e->getMessage());
            }

            // var_dump($logs);
            foreach ($logs as $index => $log) {
                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . htmlspecialchars($log['address']) . "</td>";
                echo "<td>" . htmlspecialchars($log['path']) . "</td>";
                echo "<td>" . $log['qs'] . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</body>
</html>